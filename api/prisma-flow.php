<?php
/**
 * PRISMA-SLR - API: PRISMA 2020 Flow Diagram Data
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

// ── POST: permanently delete duplicates ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['action'] ?? '') === 'delete_duplicates') {
    $stmt = $pdo->prepare("DELETE FROM articles WHERE project_id = ? AND is_duplicate = 1");
    $stmt->execute([$projectId]);
    jsonResponse(['success' => true, 'deleted' => $stmt->rowCount()]);
}

// ── POST: delete articles excluded at screening or eligibility ──────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['action'] ?? '') === 'delete_excluded') {
    $stage = $_GET['stage'] ?? '';
    if ($stage === 'screened') {
        // Excluded at screening: status = 'excluded' with a decision in the screening phase
        $stmt = $pdo->prepare("
            DELETE a FROM articles a
            JOIN screening_decisions sd ON sd.article_id = a.id
            WHERE a.project_id = ? AND a.status = 'excluded' AND sd.stage = 'screening'
        ");
    } elseif ($stage === 'eligible') {
        // Excluded at full text: status = 'excluded' with a decision in the eligibility phase
        $stmt = $pdo->prepare("
            DELETE a FROM articles a
            JOIN screening_decisions sd ON sd.article_id = a.id
            WHERE a.project_id = ? AND a.status = 'excluded' AND sd.stage = 'eligibility'
        ");
    } else {
        jsonResponse(['error' => true, 'message' => 'stage inválido'], 422);
    }
    $stmt->execute([$projectId]);
    jsonResponse(['success' => true, 'deleted' => $stmt->rowCount()]);
}

// Verify project
$stmt = $pdo->prepare("SELECT id, title FROM projects WHERE id = ?");
$stmt->execute([$projectId]);
$project = $stmt->fetch();
if (!$project) jsonResponse(['error' => true, 'message' => 'Projeto não encontrado'], 404);

// -----------------------------------------------------------------
// Counts per search source
// -----------------------------------------------------------------
$stmt = $pdo->prepare("
    SELECT ss.name, ss.source_type, ss.total_imported,
           COUNT(a.id) AS actual_count
    FROM search_sources ss
    LEFT JOIN articles a ON a.source_id = ss.id
    WHERE ss.project_id = ?
    GROUP BY ss.id
    ORDER BY ss.source_type, ss.name
");
$stmt->execute([$projectId]);
$sources = $stmt->fetchAll();

// Total identified
$totalIdentified = 0;
$byDatabase = [];
foreach ($sources as $src) {
    $count = (int) $src['actual_count'];
    $totalIdentified += $count;
    $byDatabase[] = [
        'name'  => $src['name'],
        'type'  => $src['source_type'],
        'count' => $count,
    ];
}

// -----------------------------------------------------------------
// Duplicates removed (confirmed)
// -----------------------------------------------------------------
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT article_id_2) AS total
    FROM duplicates
    WHERE project_id = ? AND status = 'confirmed'
");
$stmt->execute([$projectId]);
$duplicatesRemoved = (int) $stmt->fetchColumn();

// Unique articles (after duplicate removal)
$uniqueAfterDedup = $totalIdentified - $duplicatesRemoved;

// -----------------------------------------------------------------
// Screening and eligibility phases — decisions from the logged-in reviewer
// (same "mine" rule as Screening/Dashboard: each reviewer sees their own
// progress, not the project's consolidated official status)
// -----------------------------------------------------------------
$mine = "AND sd.is_consensus = 0 AND (sd.reviewer_id = ? OR sd.reviewer_id IS NULL)";

// Total screened by me = articles with my own screening decision
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT a.id) AS total
    FROM articles a
    JOIN screening_decisions sd ON sd.article_id = a.id AND sd.phase = 'screening' $mine
    WHERE a.project_id = ? AND a.is_duplicate = 0
");
$stmt->execute([$reviewerId, $projectId]);
$screened = (int) $stmt->fetchColumn();

// Excluded by me at screening
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT sd.article_id) AS total
    FROM screening_decisions sd
    JOIN articles a ON a.id = sd.article_id
    WHERE a.project_id = ? AND a.is_duplicate = 0 AND sd.phase = 'screening' AND sd.decision = 'exclude' $mine
");
$stmt->execute([$projectId, $reviewerId]);
$excludedScreening = (int) $stmt->fetchColumn();

// Included by me at screening (moved on to eligibility)
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT sd.article_id) AS total
    FROM screening_decisions sd
    JOIN articles a ON a.id = sd.article_id
    WHERE a.project_id = ? AND a.is_duplicate = 0 AND sd.phase = 'screening' AND sd.decision = 'include' $mine
");
$stmt->execute([$projectId, $reviewerId]);
$includedScreening = (int) $stmt->fetchColumn();

// Exclusion reasons I used at screening
$stmt = $pdo->prepare("
    SELECT er.reason, COUNT(DISTINCT sd.article_id) AS total
    FROM screening_decisions sd
    JOIN articles a ON a.id = sd.article_id
    LEFT JOIN exclusion_reasons er ON er.id = sd.reason_id
    WHERE a.project_id = ? AND a.is_duplicate = 0 AND sd.phase = 'screening' AND sd.decision = 'exclude' $mine
    GROUP BY er.id, er.reason
    ORDER BY total DESC
");
$stmt->execute([$projectId, $reviewerId]);
$screeningReasons = $stmt->fetchAll();

// -----------------------------------------------------------------
// Eligibility phase (full text) — only among those I included at screening
// -----------------------------------------------------------------
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT a.id) AS total
    FROM articles a
    JOIN screening_decisions sd ON sd.article_id = a.id AND sd.phase = 'eligibility' $mine
    WHERE a.project_id = ? AND a.is_duplicate = 0
");
$stmt->execute([$reviewerId, $projectId]);
$assessedEligibility = (int) $stmt->fetchColumn();

// Excluded by me at eligibility
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT sd.article_id) AS total
    FROM screening_decisions sd
    JOIN articles a ON a.id = sd.article_id
    WHERE a.project_id = ? AND a.is_duplicate = 0 AND sd.phase = 'eligibility' AND sd.decision = 'exclude' $mine
");
$stmt->execute([$projectId, $reviewerId]);
$excludedEligibility = (int) $stmt->fetchColumn();

// Exclusion reasons I used at eligibility
$stmt = $pdo->prepare("
    SELECT er.reason, COUNT(DISTINCT sd.article_id) AS total
    FROM screening_decisions sd
    JOIN articles a ON a.id = sd.article_id
    LEFT JOIN exclusion_reasons er ON er.id = sd.reason_id
    WHERE a.project_id = ? AND a.is_duplicate = 0 AND sd.phase = 'eligibility' AND sd.decision = 'exclude' $mine
    GROUP BY er.id, er.reason
    ORDER BY total DESC
");
$stmt->execute([$projectId, $reviewerId]);
$eligibilityReasons = $stmt->fetchAll();

// -----------------------------------------------------------------
// Included by me in the review (final eligibility decision)
// -----------------------------------------------------------------
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT sd.article_id) AS total
    FROM screening_decisions sd
    JOIN articles a ON a.id = sd.article_id
    WHERE a.project_id = ? AND a.is_duplicate = 0 AND sd.phase = 'eligibility' AND sd.decision = 'include' $mine
");
$stmt->execute([$projectId, $reviewerId]);
$totalIncluded = (int) $stmt->fetchColumn();

// -----------------------------------------------------------------
// Final response
// -----------------------------------------------------------------
jsonResponse([
    'project_id'    => $projectId,
    'project_title' => $project['title'],
    'prisma' => [
        'identification' => [
            'total_identified'    => $totalIdentified,
            'by_database'         => $byDatabase,
            'duplicates_removed'  => $duplicatesRemoved,
            'unique_after_dedup'  => $uniqueAfterDedup,
        ],
        'screening' => [
            'records_screened'     => $screened,
            'records_excluded'     => $excludedScreening,
            'records_included'     => $includedScreening,
            'exclusion_reasons'    => $screeningReasons,
        ],
        'eligibility' => [
            'assessed_full_text'   => $assessedEligibility,
            'excluded_full_text'   => $excludedEligibility,
            'exclusion_reasons'    => $eligibilityReasons,
        ],
        'included' => [
            'studies_included'     => $totalIncluded,
        ],
    ],
]);
