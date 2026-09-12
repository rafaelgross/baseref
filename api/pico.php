<?php
/**
 * PRISMA-SLR - API: PICO(C) data extraction
 *
 * GET  /api/pico.php?project_id=N        → included articles + extractions
 * GET  /api/pico.php?project_id=N&csv=1  → PICO table as CSV (for the manuscript)
 * POST { article_id, population, intervention, comparison, outcome, context?, notes? }
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/api_guard.php';

header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$apiUser    = requireApiAuth();
$reviewerId = (int) $apiUser['id'];
$pdo    = getDB();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $projectId = (int) ($_GET['project_id'] ?? 0);
    if (!$projectId) jsonResponse(['error' => true, 'message' => 'project_id obrigatório'], 422);
    requireProjectAccess($pdo, $projectId);

    $stmt = $pdo->prepare("
        SELECT a.id, a.title, a.year, a.journal, a.doi,
               pe.population, pe.intervention, pe.comparison, pe.outcome, pe.context, pe.notes,
               GROUP_CONCAT(au.full_name ORDER BY aa.position SEPARATOR '; ') AS authors
        FROM articles a
        LEFT JOIN pico_extractions pe ON pe.article_id = a.id
        LEFT JOIN article_authors aa ON aa.article_id = a.id
        LEFT JOIN authors au ON au.id = aa.author_id
        WHERE a.project_id = ? AND " . personalIncludedExists($reviewerId) . " AND a.is_duplicate = 0
        GROUP BY a.id
        ORDER BY a.year, a.id
    ");
    $stmt->execute([$projectId]);
    $articles = $stmt->fetchAll();

    if (!empty($_GET['csv'])) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="pico_extraction.csv"');
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID', 'Estudo', 'Ano', 'Periódico', 'DOI',
                       'População', 'Intervenção', 'Comparação', 'Desfecho', 'Contexto', 'Notas']);
        foreach ($articles as $a) {
            $firstAuthor = trim(explode(';', $a['authors'] ?? '')[0] ?? '');
            $study = $firstAuthor ? "$firstAuthor ({$a['year']})" : $a['title'];
            fputcsv($out, [$a['id'], $study, $a['year'], $a['journal'], $a['doi'],
                           $a['population'], $a['intervention'], $a['comparison'],
                           $a['outcome'], $a['context'], $a['notes']]);
        }
        fclose($out);
        exit;
    }

    jsonResponse(['articles' => $articles]);
}

if ($method !== 'POST') jsonResponse(['error' => true, 'message' => 'Método não suportado'], 405);

$data      = getJsonBody();
$articleId = (int) ($data['article_id'] ?? 0);
if (!$articleId) jsonResponse(['error' => true, 'message' => 'article_id obrigatório'], 422);
requireArticleAccess($pdo, $articleId);

$pdo->prepare("
    INSERT INTO pico_extractions (article_id, population, intervention, comparison, outcome, context, notes)
    VALUES (?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
        population   = VALUES(population),
        intervention = VALUES(intervention),
        comparison   = VALUES(comparison),
        outcome      = VALUES(outcome),
        context      = VALUES(context),
        notes        = VALUES(notes)
")->execute([
    $articleId,
    $data['population']   ?? null,
    $data['intervention'] ?? null,
    $data['comparison']   ?? null,
    $data['outcome']      ?? null,
    $data['context']      ?? null,
    $data['notes']        ?? null,
]);

jsonResponse(['success' => true, 'article_id' => $articleId]);
