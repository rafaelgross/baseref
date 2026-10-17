<?php
/**
 * PRISMA-SLR - API: Dados do Diagrama PRISMA 2020
 * GET /api/prisma-flow.php?project_id=N
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/api_guard.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$apiUser = requireApiAuth();
$reviewerId = (int) $apiUser['id'];

$pdo       = getDB();
$projectId = isset($_GET['project_id']) ? (int) $_GET['project_id'] : null;
if ($projectId) requireProjectAccess(getDB(), (int) $projectId);

if (!$projectId) jsonResponse(['error' => true, 'message' => 'project_id obrigatório'], 422);

// ── POST: excluir duplicatas permanentemente ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['action'] ?? '') === 'delete_duplicates') {
    $stmt = $pdo->prepare("DELETE FROM articles WHERE project_id = ? AND is_duplicate = 1");
    $stmt->execute([$projectId]);
    jsonResponse(['success' => true, 'deleted' => $stmt->rowCount()]);
}

// Verifica projeto
$stmt = $pdo->prepare("SELECT id, title FROM projects WHERE id = ?");
$stmt->execute([$projectId]);
$project = $stmt->fetch();
if (!$project) jsonResponse(['error' => true, 'message' => 'Projeto não encontrado'], 404);

require_once __DIR__ . '/../lib/PrismaReasons.php';
ensureReasonFlagColumn($pdo);

// =================================================================
// Diagrama no modelo oficial PRISMA 2020 (bases de dados e registros):
//   IDENTIFICATION  registros por base → "Records removed before screening"
//   SCREENING       Records screened → Records excluded
//                   Reports sought for retrieval → Reports not retrieved
//                   Reports assessed for eligibility → Reports excluded (motivos)
//   INCLUDED        Studies included in review
// Não existe mais a fase "Eligibility" separada (era do PRISMA 2009).
// =================================================================

// -----------------------------------------------------------------
// Identification — registros importados por base (inclui duplicatas)
// -----------------------------------------------------------------
$stmt = $pdo->prepare("
    SELECT ss.id, ss.name, ss.source_type, COUNT(a.id) AS actual_count
    FROM search_sources ss
    LEFT JOIN articles a ON a.source_id = ss.id AND a.project_id = ss.project_id
    WHERE ss.project_id = ?
    GROUP BY ss.id, ss.name, ss.source_type
    ORDER BY ss.id
");
$stmt->execute([$projectId]);
$byDatabase = [];
$totalIdentified = 0;
foreach ($stmt->fetchAll() as $src) {
    $count = (int) $src['actual_count'];
    $totalIdentified += $count;
    $byDatabase[] = ['name' => $src['name'], 'type' => $src['source_type'], 'count' => $count];
}
// Registros sem fonte vinculada (importações antigas) também foram identificados
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM articles a
    LEFT JOIN search_sources ss ON ss.id = a.source_id
    WHERE a.project_id = ? AND ss.id IS NULL
");
$stmt->execute([$projectId]);
$orphans = (int) $stmt->fetchColumn();
if ($orphans > 0) {
    $byDatabase[] = ['name' => null, 'type' => 'other', 'count' => $orphans];   // rótulo traduzido no front
    $totalIdentified += $orphans;
}

// Duplicatas removidas = registros marcados como duplicata (um por registro removido,
// mesmo quando um artigo aparece em mais de um par de duplicata)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM articles WHERE project_id = ? AND is_duplicate = 1");
$stmt->execute([$projectId]);
$duplicatesRemoved = (int) $stmt->fetchColumn();
$uniqueAfterDedup  = $totalIdentified - $duplicatesRemoved;

// -----------------------------------------------------------------
// Screening — decisões do revisor logado (mesma regra "mine" da Triagem e do
// Dashboard: cada revisor vê o próprio progresso; linhas legadas sem revisor
// contam para todos)
// -----------------------------------------------------------------
$mine = "AND sd.is_consensus = 0 AND (sd.reviewer_id = ? OR sd.reviewer_id IS NULL)";

$stmt = $pdo->prepare("
    SELECT a.id AS article_id, sd.phase, sd.decision, sd.reason_id,
           er.reason, er.not_retrieved
    FROM articles a
    JOIN screening_decisions sd ON sd.article_id = a.id
    LEFT JOIN exclusion_reasons er ON er.id = sd.reason_id
    WHERE a.project_id = ? AND a.is_duplicate = 0 $mine
    ORDER BY sd.decided_at, sd.id
");
$stmt->execute([$projectId, $reviewerId]);
$dec = [];   // [article_id][phase] = última decisão
foreach ($stmt->fetchAll() as $r) {
    $dec[$r['article_id']][$r['phase']] = $r;
}

$recordsScreened = $recordsExcluded = $reportsSought = 0;
$notRetrieved = $reportsAssessed = $reportsExcluded = $studiesIncluded = $awaitingFullText = 0;
$screeningReasons = $eligibilityReasons = $notRetrievedReasons = [];
$bump = function (array &$list, ?string $reason) {
    $k = $reason ?? '';
    $list[$k] = ($list[$k] ?? 0) + 1;
};

foreach ($dec as $phases) {
    $sc = $phases['screening'] ?? null;
    if (!$sc) continue;
    $recordsScreened++;

    if ($sc['decision'] === 'exclude') {
        $recordsExcluded++;
        $bump($screeningReasons, $sc['reason']);
        continue;
    }
    if ($sc['decision'] !== 'include') continue;   // 'uncertain': aguardando desfecho

    // Aprovado no título/resumo → texto completo buscado
    $reportsSought++;
    $el = $phases['eligibility'] ?? null;
    if (!$el || !in_array($el['decision'], ['include', 'exclude'], true)) {
        $awaitingFullText++;
        continue;
    }
    if ($el['decision'] === 'exclude' && isNotRetrievedReason($el['reason'], $el['not_retrieved'])) {
        $notRetrieved++;
        $bump($notRetrievedReasons, $el['reason']);
        continue;
    }
    $reportsAssessed++;
    if ($el['decision'] === 'exclude') {
        $reportsExcluded++;
        $bump($eligibilityReasons, $el['reason']);
    } else {
        $studiesIncluded++;
    }
}

$toList = function (array $counts): array {
    arsort($counts);
    $out = [];
    foreach ($counts as $reason => $total) {
        $out[] = ['reason' => $reason === '' ? null : $reason, 'total' => $total];
    }
    return $out;
};

// -----------------------------------------------------------------
// Resposta final
// -----------------------------------------------------------------
jsonResponse([
    'project_id'    => $projectId,
    'project_title' => $project['title'],
    'model'         => 'prisma2020_databases',
    'prisma' => [
        'identification' => [
            'total_identified'   => $totalIdentified,
            'by_database'        => $byDatabase,
            'duplicates_removed' => $duplicatesRemoved,
            'unique_after_dedup' => $uniqueAfterDedup,
        ],
        'screening' => [
            'records_screened'        => $recordsScreened,
            'records_excluded'        => $recordsExcluded,
            'records_reasons'         => $toList($screeningReasons),
            'reports_sought'          => $reportsSought,
            'reports_not_retrieved'   => $notRetrieved,
            'not_retrieved_reasons'   => $toList($notRetrievedReasons),
            'reports_assessed'        => $reportsAssessed,
            'reports_excluded'        => $reportsExcluded,
            'reports_reasons'         => $toList($eligibilityReasons),
            'awaiting_full_text'      => $awaitingFullText,   // aprovados ainda sem decisão de texto completo
            'records_pending'         => $uniqueAfterDedup - $recordsScreened,
        ],
        'included' => [
            'studies_included' => $studiesIncluded,
        ],
    ],
]);
