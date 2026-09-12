<?php
/**
 * PRISMA-SLR - API: Metadata enrichment via OpenAlex
 *
 * POST /api/enrich.php  body: { "project_id": N }
 *   For each article with a DOI, queries OpenAlex (free, no key required) and fills in:
 *     - abstract (only if empty; reconstructed from abstract_inverted_index)
 *     - cited_by (if the OpenAlex count is higher)
 *     - open_access (if OpenAlex indicates OA and the record is marked as closed)
 *
 * GET /api/enrich.php?project_id=N → how many articles are eligible for enrichment
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/api_guard.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

requireApiAuth();

$pdo    = getDB();
$method = $_SERVER['REQUEST_METHOD'];

const OPENALEX_BATCH   = 50;   // DOIs per request (limit of OpenAlex's OR filter)
const OPENALEX_MAILTO  = 'rafaelgross123@gmail.com'; // "polite pool" — faster and more stable

if ($method === 'GET') {
    $projectId = (int) ($_GET['project_id'] ?? 0);
    if (!$projectId) jsonResponse(['error' => true, 'message' => 'project_id obrigatório'], 422);
    requireProjectAccess($pdo, $projectId);

    $stmt = $pdo->prepare("
        SELECT
            COUNT(*) AS with_doi,
            SUM(CASE WHEN abstract IS NULL OR abstract = '' THEN 1 ELSE 0 END) AS missing_abstract,
            SUM(CASE WHEN cited_by = 0 THEN 1 ELSE 0 END) AS zero_citations,
            SUM(CASE WHEN open_access = 0 THEN 1 ELSE 0 END) AS closed_access
        FROM articles
        WHERE project_id = ? AND is_duplicate = 0 AND doi IS NOT NULL AND doi != ''
    ");
    $stmt->execute([$projectId]);
    jsonResponse($stmt->fetch());
}

if ($method !== 'POST') jsonResponse(['error' => true, 'message' => 'Método não suportado'], 405);

$data      = getJsonBody();
$projectId = (int) ($data['project_id'] ?? 0);
if (!$projectId) jsonResponse(['error' => true, 'message' => 'project_id obrigatório'], 422);
requireProjectAccess($pdo, $projectId);

// Articles with a DOI that have something to fill in
$stmt = $pdo->prepare("
    SELECT id, doi, abstract, cited_by, open_access
    FROM articles
    WHERE project_id = ? AND is_duplicate = 0 AND doi IS NOT NULL AND doi != ''
    ORDER BY id
");
$stmt->execute([$projectId]);
$articles = $stmt->fetchAll();

if (empty($articles)) {
    jsonResponse(['success' => true, 'message' => 'Nenhum artigo com DOI para enriquecer',
                  'processed' => 0, 'updated' => 0]);
}

// Index by normalized DOI
$byDoi = [];
foreach ($articles as $a) {
    $doi = normalizeDoi($a['doi']);
    if ($doi) $byDoi[$doi][] = $a;
}

set_time_limit(300);

$stmtUpd = $pdo->prepare("
    UPDATE articles SET
        abstract    = COALESCE(NULLIF(abstract, ''), ?),
        cited_by    = GREATEST(cited_by, ?),
        open_access = GREATEST(open_access, ?)
    WHERE id = ?
");

$processed = 0; $updated = 0; $notFound = 0; $failures = 0;
$dois = array_keys($byDoi);

foreach (array_chunk($dois, OPENALEX_BATCH) as $chunk) {
    $filter = 'doi:' . implode('|', $chunk);
    $url = 'https://api.openalex.org/works?filter=' . urlencode($filter)
         . '&per-page=' . OPENALEX_BATCH
         . '&select=doi,cited_by_count,open_access,abstract_inverted_index'
         . '&mailto=' . urlencode(OPENALEX_MAILTO);

    $resp = httpGetJson($url);
    if ($resp === null) { $failures += count($chunk); continue; }

    $found = [];
    foreach (($resp['results'] ?? []) as $work) {
        $doi = normalizeDoi($work['doi'] ?? '');
        if (!$doi || empty($byDoi[$doi])) continue;
        $found[$doi] = true;

        $abstract = reconstructAbstract($work['abstract_inverted_index'] ?? null);
        $citedBy  = (int) ($work['cited_by_count'] ?? 0);
        $isOa     = !empty($work['open_access']['is_oa']) ? 1 : 0;

        foreach ($byDoi[$doi] as $a) {
            $hadGap = empty($a['abstract']) || $citedBy > (int) $a['cited_by']
                      || ($isOa && !(int) $a['open_access']);
            $stmtUpd->execute([$abstract, $citedBy, $isOa, $a['id']]);
            if ($hadGap) $updated++;
        }
    }
    $processed += count($chunk);
    $notFound  += count(array_diff($chunk, array_keys($found)));

    usleep(120000); // courtesy delay for the API (polite pool: ~10 req/s allowed)
}

jsonResponse([
    'success'   => true,
    'processed' => $processed,
    'updated'   => $updated,
    'not_found' => $notFound,
    'failures'  => $failures,
    'message'   => "Enriquecimento concluído: $updated artigo(s) atualizados, $notFound DOI(s) não encontrados na OpenAlex",
]);

// -----------------------------------------------------------------
// Helpers
// -----------------------------------------------------------------

function normalizeDoi(?string $doi): ?string
{
    if (!$doi) return null;
    $doi = strtolower(trim($doi));
    $doi = preg_replace('#^https?://(dx\.)?doi\.org/#', '', $doi);
    return $doi ?: null;
}

function httpGetJson(string $url): ?array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_USERAGENT      => 'PRISMA-SLR (mailto:' . OPENALEX_MAILTO . ')',
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($resp === false || $code !== 200) return null;
    $data = json_decode($resp, true);
    return is_array($data) ? $data : null;
}

/**
 * OpenAlex delivers the abstract as an inverted index {word: [positions]};
 * this reconstructs the text by ordering the words by their positions.
 */
function reconstructAbstract(?array $inverted): ?string
{
    if (empty($inverted)) return null;
    $words = [];
    foreach ($inverted as $word => $positions) {
        foreach ($positions as $pos) $words[$pos] = $word;
    }
    ksort($words);
    $text = implode(' ', $words);
    return $text !== '' ? $text : null;
}
