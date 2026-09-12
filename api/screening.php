<?php
/**
 * PRISMA-SLR - API: Screening and Eligibility
 *
 * GET  /api/screening.php?project_id=N&phase=screening  → articles to screen
 * POST /api/screening.php                                → record decision
 * GET  /api/screening.php?project_id=N&summary=1        → phase summary
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/api_guard.php';
require_once __DIR__ . '/screening_status.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$apiUser = requireApiAuth();
$reviewerId = (int) $apiUser['id'];

$pdo    = getDB();
$method = $_SERVER['REQUEST_METHOD'];

// Migration: ensures the rating column exists on screening_decisions
try {
    $pdo->exec("ALTER TABLE screening_decisions ADD COLUMN rating TINYINT UNSIGNED NULL DEFAULT NULL");
} catch (Exception $e) { /* column already exists */ }

// -----------------------------------------------------------------
switch ($method) {
// -----------------------------------------------------------------

case 'GET':
    $projectId = isset($_GET['project_id']) ? (int) $_GET['project_id'] : null;
    if ($projectId) requireProjectAccess(getDB(), (int) $projectId);
    if (!$projectId) jsonResponse(['error' => true, 'message' => 'project_id obrigatório'], 422);

    // Summary mode: returns counts for each phase
    if (!empty($_GET['summary'])) {
        jsonResponse(getScreeningSummary($pdo, $projectId, $reviewerId));
        break;
    }

    $phase          = $_GET['phase']           ?? 'screening'; // 'screening' or 'eligibility'
    $pending        = !empty($_GET['pending_only']);            // only without a decision
    $decisionFilter = $_GET['decision_filter'] ?? '';          // 'include', 'exclude' or ''

    $page    = max(1, (int) ($_GET['page'] ?? 1));
    $perPage = min(50, max(5, (int) ($_GET['per_page'] ?? 20)));
    $offset  = ($page - 1) * $perPage;

    if ($phase === 'screening') {
        // Screening: articles that are not duplicates
        $baseWhere = "a.project_id = ? AND a.is_duplicate = 0";
        $params    = [$projectId];

        $mine = "AND sd.is_consensus = 0 AND (sd.reviewer_id = $reviewerId OR sd.reviewer_id IS NULL)";
        if ($pending) {
            $baseWhere .= " AND NOT EXISTS (SELECT 1 FROM screening_decisions sd WHERE sd.article_id = a.id AND sd.phase = 'screening' $mine)";
        } elseif ($decisionFilter === 'include') {
            $baseWhere .= " AND EXISTS (SELECT 1 FROM screening_decisions sd WHERE sd.article_id = a.id AND sd.phase = 'screening' AND sd.decision = 'include' $mine)";
        } elseif ($decisionFilter === 'exclude') {
            $baseWhere .= " AND EXISTS (SELECT 1 FROM screening_decisions sd WHERE sd.article_id = a.id AND sd.phase = 'screening' AND sd.decision = 'exclude' $mine)";
        }
    } else {
        // Eligibility: articles that passed screening (effective status) or already have an eligibility decision
        $baseWhere = "a.project_id = ? AND a.is_duplicate = 0
                      AND (a.status IN ('eligible','included')
                           OR EXISTS (SELECT 1 FROM screening_decisions se WHERE se.article_id = a.id AND se.phase = 'eligibility'))";
        $params    = [$projectId];

        $mine = "AND sd2.is_consensus = 0 AND (sd2.reviewer_id = $reviewerId OR sd2.reviewer_id IS NULL)";
        if ($pending) {
            $baseWhere .= " AND NOT EXISTS (SELECT 1 FROM screening_decisions sd2 WHERE sd2.article_id = a.id AND sd2.phase = 'eligibility' $mine)";
        } elseif ($decisionFilter === 'include') {
            $baseWhere .= " AND EXISTS (SELECT 1 FROM screening_decisions sd2 WHERE sd2.article_id = a.id AND sd2.phase = 'eligibility' AND sd2.decision = 'include' $mine)";
        } elseif ($decisionFilter === 'exclude') {
            $baseWhere .= " AND EXISTS (SELECT 1 FROM screening_decisions sd2 WHERE sd2.article_id = a.id AND sd2.phase = 'eligibility' AND sd2.decision = 'exclude' $mine)";
        }
    }

    // Search filter
    if (!empty($_GET['search'])) {
        $s = '%' . $_GET['search'] . '%';
        $baseWhere .= " AND (a.title LIKE ? OR a.abstract LIKE ?)";
        $params[] = $s;
        $params[] = $s;
    }

    // Total count
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM articles a WHERE $baseWhere");
    $stmtCount->execute($params);
    $total = (int) $stmtCount->fetchColumn();

    // Articles
    $stmtArt = $pdo->prepare("
        SELECT a.id, a.title, a.abstract, a.year, a.journal, a.doi,
               a.source_type, a.document_type, a.url,
               sd.decision AS current_decision,
               sd.reason_id, er.reason AS exclusion_reason,
               sd.notes, sd.rating
        FROM articles a
        LEFT JOIN screening_decisions sd ON sd.article_id = a.id AND sd.phase = ?
             AND sd.is_consensus = 0 AND (sd.reviewer_id = $reviewerId OR sd.reviewer_id IS NULL)
        LEFT JOIN exclusion_reasons er ON er.id = sd.reason_id
        WHERE $baseWhere
        ORDER BY a.id ASC
        LIMIT ? OFFSET ?
    ");
    $stmtArt->execute(array_merge([$phase], $params, [$perPage, $offset]));
    $articles = $stmtArt->fetchAll();

    // Authors for each article
    if (!empty($articles)) {
        $ids = array_column($articles, 'id');
        $ph  = implode(',', array_fill(0, count($ids), '?'));
        $stmtAut = $pdo->prepare("
            SELECT aa.article_id, GROUP_CONCAT(au.full_name ORDER BY aa.position SEPARATOR '; ') AS authors
            FROM article_authors aa JOIN authors au ON au.id = aa.author_id
            WHERE aa.article_id IN ($ph)
            GROUP BY aa.article_id
        ");
        $stmtAut->execute($ids);
        $authorMap = array_column($stmtAut->fetchAll(), 'authors', 'article_id');
        foreach ($articles as &$art) {
            $art['authors'] = $authorMap[$art['id']] ?? '';
        }
    }

    // Project exclusion reasons
    $stmtReasons = $pdo->prepare("
        SELECT id, reason, description
        FROM exclusion_reasons
        WHERE project_id = ? AND phase = ?
        ORDER BY sort_order
    ");
    $stmtReasons->execute([$projectId, $phase]);

    jsonResponse([
        'articles'         => $articles,
        'exclusion_reasons'=> $stmtReasons->fetchAll(),
        'total'            => $total,
        'page'             => $page,
        'per_page'         => $perPage,
        'last_page'        => (int) ceil($total / $perPage),
        'phase'            => $phase,
    ]);
    break;

// -----------------------------------------------------------------
case 'POST':
    $data      = getJsonBody();
    $articleId = (int) ($data['article_id'] ?? 0);
    $phase     = $data['phase']    ?? '';
    $decision  = $data['decision'] ?? '';

    if (!$articleId || !$phase || !$decision) {
        jsonResponse(['error' => true, 'message' => 'article_id, phase e decision são obrigatórios'], 422);
    }
    requireArticleAccess($pdo, $articleId);

    if (!in_array($phase,    ['screening', 'eligibility'])) {
        jsonResponse(['error' => true, 'message' => 'phase inválido'], 422);
    }
    if (!in_array($decision, ['include', 'exclude', 'uncertain'])) {
        jsonResponse(['error' => true, 'message' => 'decision inválido'], 422);
    }

    $reasonId = !empty($data['reason_id']) ? (int) $data['reason_id'] : null;
    $notes    = $data['notes'] ?? null;
    $rating   = isset($data['rating']) && $data['rating'] >= 1 && $data['rating'] <= 5
                ? (int) $data['rating'] : null;

    // Upsert this reviewer's decision (legacy decisions have reviewer_id NULL and
    // are absorbed by the current reviewer to avoid duplicates)
    $pdo->prepare("
        UPDATE screening_decisions SET reviewer_id = ?
        WHERE article_id = ? AND phase = ? AND reviewer_id IS NULL AND is_consensus = 0
    ")->execute([$reviewerId, $articleId, $phase]);

    $pdo->prepare("
        INSERT INTO screening_decisions (article_id, reviewer_id, is_consensus, phase, decision, reason_id, notes, rating)
        VALUES (?, ?, 0, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            decision   = VALUES(decision),
            reason_id  = VALUES(reason_id),
            notes      = VALUES(notes),
            rating     = VALUES(rating),
            decided_at = NOW()
    ")->execute([$articleId, $reviewerId, $phase, $decision, $reasonId, $notes, $rating]);

    // Update article status
    $newStatus = computeArticleStatus($pdo, $articleId);
    $pdo->prepare("UPDATE articles SET status = ? WHERE id = ?")->execute([$newStatus, $articleId]);

    jsonResponse(['success' => true, 'article_id' => $articleId, 'new_status' => $newStatus]);
    break;

// -----------------------------------------------------------------
case 'DELETE':
    // Remove the screening decision so the article goes back to pending
    $data      = getJsonBody();
    $articleId = (int) ($data['article_id'] ?? 0);
    $phase     = $data['phase'] ?? '';

    if (!$articleId || !in_array($phase, ['screening', 'eligibility'])) {
        jsonResponse(['error' => true, 'message' => 'article_id e phase são obrigatórios'], 422);
    }
    requireArticleAccess($pdo, $articleId);

    $pdo->prepare("
        DELETE FROM screening_decisions
        WHERE article_id = ? AND phase = ?
              AND is_consensus = 0 AND (reviewer_id = ? OR reviewer_id IS NULL)
    ")->execute([$articleId, $phase, $reviewerId]);

    // Recalculate article status
    $newStatus = computeArticleStatus($pdo, $articleId);
    $pdo->prepare("UPDATE articles SET status = ? WHERE id = ?")->execute([$newStatus, $articleId]);

    jsonResponse(['success' => true, 'article_id' => $articleId, 'new_status' => $newStatus]);
    break;

// -----------------------------------------------------------------
default:
    jsonResponse(['error' => true, 'message' => 'Método não suportado'], 405);
}

// -----------------------------------------------------------------
// Helpers
// -----------------------------------------------------------------

function computeArticleStatus(PDO $pdo, int $articleId): string
{
    return computeArticleStatusShared($pdo, $articleId);
}

/**
 * Summary of counts per phase — filtered by the current reviewer's decisions
 * (same "mine" rule used in the listing) so the chip totals always
 * match what appears when clicking each filter.
 */
function getScreeningSummary(PDO $pdo, int $projectId, int $reviewerId): array
{
    // Total non-duplicates
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM articles WHERE project_id = ? AND is_duplicate = 0");
    $stmt->execute([$projectId]);
    $totalNonDup = (int) $stmt->fetchColumn();

    $mine = "AND sd.is_consensus = 0 AND (sd.reviewer_id = ? OR sd.reviewer_id IS NULL)";

    // Screening: without a decision (from this reviewer)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM articles a
        WHERE a.project_id = ? AND a.is_duplicate = 0
        AND NOT EXISTS (SELECT 1 FROM screening_decisions sd WHERE sd.article_id = a.id AND sd.phase = 'screening' $mine)
    ");
    $stmt->execute([$projectId, $reviewerId]);
    $pendingScreening = (int) $stmt->fetchColumn();

    // Screening: included (by this reviewer)
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT sd.article_id) FROM screening_decisions sd
        JOIN articles a ON a.id = sd.article_id
        WHERE a.project_id = ? AND a.is_duplicate = 0 AND sd.phase = 'screening' AND sd.decision = 'include' $mine
    ");
    $stmt->execute([$projectId, $reviewerId]);
    $includedScreening = (int) $stmt->fetchColumn();

    // Screening: excluded (by this reviewer)
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT sd.article_id) FROM screening_decisions sd
        JOIN articles a ON a.id = sd.article_id
        WHERE a.project_id = ? AND a.is_duplicate = 0 AND sd.phase = 'screening' AND sd.decision = 'exclude' $mine
    ");
    $stmt->execute([$projectId, $reviewerId]);
    $excludedScreening = (int) $stmt->fetchColumn();

    // Eligibility: without a decision (passed screening, from this reviewer)
    $mine2 = "AND sd2.is_consensus = 0 AND (sd2.reviewer_id = ? OR sd2.reviewer_id IS NULL)";
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM articles a
        WHERE a.project_id = ? AND a.is_duplicate = 0
        AND EXISTS (SELECT 1 FROM screening_decisions sd WHERE sd.article_id = a.id AND sd.phase = 'screening' AND sd.decision = 'include' $mine)
        AND NOT EXISTS (SELECT 1 FROM screening_decisions sd2 WHERE sd2.article_id = a.id AND sd2.phase = 'eligibility' $mine2)
    ");
    $stmt->execute([$projectId, $reviewerId, $reviewerId]);
    $pendingEligibility = (int) $stmt->fetchColumn();

    // Eligibility: included by this reviewer
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM articles a
        WHERE a.project_id = ? AND a.is_duplicate = 0 AND " . personalIncludedExists($reviewerId) . "
    ");
    $stmt->execute([$projectId]);
    $included = (int) $stmt->fetchColumn();

    // Eligibility: excluded (by this reviewer)
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT sd.article_id) FROM screening_decisions sd
        JOIN articles a ON a.id = sd.article_id
        WHERE a.project_id = ? AND a.is_duplicate = 0 AND sd.phase = 'eligibility' AND sd.decision = 'exclude' $mine
    ");
    $stmt->execute([$projectId, $reviewerId]);
    $excludedEligibility = (int) $stmt->fetchColumn();

    // Exclusions by reason (eligibility)
    $stmt = $pdo->prepare("
        SELECT er.reason, COUNT(DISTINCT sd.article_id) AS total
        FROM screening_decisions sd
        JOIN articles a ON a.id = sd.article_id
        JOIN exclusion_reasons er ON er.id = sd.reason_id
        WHERE a.project_id = ? AND sd.phase = 'eligibility' AND sd.decision = 'exclude'
        GROUP BY er.id, er.reason
        ORDER BY total DESC
    ");
    $stmt->execute([$projectId]);
    $exclusionsByReason = $stmt->fetchAll();

    return [
        'total_non_duplicates'  => $totalNonDup,
        'screening' => [
            'pending'  => $pendingScreening,
            'included' => $includedScreening,
            'excluded' => $excludedScreening,
            'total'    => $pendingScreening + $includedScreening + $excludedScreening,
        ],
        'eligibility' => [
            'pending'            => $pendingEligibility,
            'included'           => $included,
            'excluded'           => $excludedEligibility,
            'total'              => $pendingEligibility + $included + $excludedEligibility,
            'exclusions_by_reason' => $exclusionsByReason,
        ],
        'final_included' => $included,
    ];
}
