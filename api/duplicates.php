<?php
/**
 * PRISMA-SLR - API: Duplicate Detection and Management
 *
 * GET    /api/duplicates.php?project_id=N          → list pending pairs
 * POST   /api/duplicates.php                        → detect duplicates (runs the algorithm)
 * PUT    /api/duplicates.php?id=N                   → confirm/reject pair
 * DELETE /api/duplicates.php?id=N                   → remove pair
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/api_guard.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

requireApiAuth();

$pdo    = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int) $_GET['id'] : null;

// -----------------------------------------------------------------
switch ($method) {
// -----------------------------------------------------------------

case 'GET':
    $projectId = isset($_GET['project_id']) ? (int) $_GET['project_id'] : null;
    if ($projectId) requireProjectAccess(getDB(), (int) $projectId);
    if (!$projectId) jsonResponse(['error' => true, 'message' => 'project_id obrigatório'], 422);

    $status = $_GET['status'] ?? 'pending';

    $stmt = $pdo->prepare("
        SELECT d.*,
               a1.title AS title_1, a1.year AS year_1, a1.doi AS doi_1,
               a1.journal AS journal_1, a1.source_type AS source_1,
               a2.title AS title_2, a2.year AS year_2, a2.doi AS doi_2,
               a2.journal AS journal_2, a2.source_type AS source_2
        FROM duplicates d
        JOIN articles a1 ON a1.id = d.article_id_1
        JOIN articles a2 ON a2.id = d.article_id_2
        WHERE d.project_id = ? AND d.status = ?
        ORDER BY d.match_score DESC, d.id DESC
    ");
    $stmt->execute([$projectId, $status]);

    $pairs  = $stmt->fetchAll();

    // Status summary
    $stmtSum = $pdo->prepare("
        SELECT status, COUNT(*) AS total
        FROM duplicates WHERE project_id = ?
        GROUP BY status
    ");
    $stmtSum->execute([$projectId]);
    $summary = [];
    foreach ($stmtSum->fetchAll() as $row) {
        $summary[$row['status']] = (int) $row['total'];
    }

    // Count of unique articles (not marked as duplicates)
    $stmtUnique = $pdo->prepare("SELECT COUNT(*) FROM articles WHERE project_id = ? AND is_duplicate = 0");
    $stmtUnique->execute([$projectId]);
    $summary['unique_count'] = (int) $stmtUnique->fetchColumn();

    jsonResponse(['pairs' => $pairs, 'summary' => $summary]);
    break;

// -----------------------------------------------------------------
case 'POST':
    $data      = getJsonBody();
    $projectId = (int) ($data['project_id'] ?? 0);
    if ($projectId) requireProjectAccess(getDB(), $projectId);
    if (!$projectId) jsonResponse(['error' => true, 'message' => 'project_id obrigatório'], 422);

    // Confirm all pending pairs in bulk (always keeps article A / article_id_1)
    if (($data['action'] ?? '') === 'confirm_all_pending') {
        $stmt = $pdo->prepare("SELECT * FROM duplicates WHERE project_id = ? AND status = 'pending' ORDER BY match_score DESC, id ASC");
        $stmt->execute([$projectId]);
        $pendingPairs = $stmt->fetchAll();

        $confirmed = 0;
        $pdo->beginTransaction();
        try {
            foreach ($pendingPairs as $pair) {
                $canonicalId = (int) $pair['article_id_1'];
                $duplicateId = (int) $pair['article_id_2'];

                // If article A was already marked as a duplicate, swap the order
                $chk = $pdo->prepare("SELECT is_duplicate FROM articles WHERE id = ?");
                $chk->execute([$canonicalId]);
                $art = $chk->fetch();
                if ($art && (int)$art['is_duplicate'] === 1) {
                    [$canonicalId, $duplicateId] = [$duplicateId, $canonicalId];
                }

                $pdo->prepare("UPDATE duplicates SET status='confirmed', canonical_id=?, reviewed_at=NOW() WHERE id=?")
                    ->execute([$canonicalId, (int)$pair['id']]);
                $pdo->prepare("UPDATE articles SET is_duplicate=1, duplicate_of=?, status='excluded' WHERE id=? AND is_duplicate=0")
                    ->execute([$canonicalId, $duplicateId]);
                $confirmed++;
            }
            $pdo->commit();
            jsonResponse(['success' => true, 'confirmed' => $confirmed]);
        } catch (Throwable $e) {
            $pdo->rollBack();
            jsonResponse(['error' => true, 'message' => $e->getMessage()], 500);
        }
    }

    // Detect duplicates for a project
    // Algorithm settings
    $titleThreshold = (float) ($data['title_threshold'] ?? 85); // minimum title similarity %

    // Fetch non-duplicate articles
    $stmt = $pdo->prepare("
        SELECT id, title, year, doi, publisher
        FROM articles
        WHERE project_id = ? AND is_duplicate = 0
        ORDER BY id
    ");
    $stmt->execute([$projectId]);
    $articles = $stmt->fetchAll();

    $detected = 0;
    $skipped  = 0;
    $n        = count($articles);

    // Remove old pending pairs to reprocess
    $pdo->prepare("DELETE FROM duplicates WHERE project_id = ? AND status = 'pending'")->execute([$projectId]);

    $stmtInsert = $pdo->prepare("
        INSERT IGNORE INTO duplicates
            (project_id, article_id_1, article_id_2, match_score, match_type, status)
        VALUES (?, ?, ?, ?, ?, 'pending')
    ");

    // O(n²) algorithm with early-exit by DOI
    $byDoi = [];
    foreach ($articles as $art) {
        if (!empty($art['doi'])) {
            $doi = normalizeDoi($art['doi']);
            if ($doi) {
                $byDoi[$doi][] = $art['id'];
            }
        }
    }

    // 1. Exact match by DOI
    foreach ($byDoi as $doi => $ids) {
        if (count($ids) < 2) continue;
        for ($i = 0; $i < count($ids); $i++) {
            for ($j = $i + 1; $j < count($ids); $j++) {
                $stmtInsert->execute([$projectId, $ids[$i], $ids[$j], 100.0, 'doi']);
                $detected++;
            }
        }
    }

    // 2. Match by similar title
    // Processed in chunks to avoid timeouts
    $titleNormMap = [];
    foreach ($articles as $art) {
        $titleNormMap[$art['id']] = normalizeTitle($art['title'] ?? '');
    }

    $processedDoi = []; // pairs already detected by DOI
    foreach ($byDoi as $doi => $ids) {
        foreach ($ids as $id1) {
            foreach ($ids as $id2) {
                if ($id1 < $id2) $processedDoi[$id1 . '_' . $id2] = true;
            }
        }
    }

    for ($i = 0; $i < $n; $i++) {
        $art1   = $articles[$i];
        $title1 = $titleNormMap[$art1['id']];
        if (empty($title1)) continue;

        for ($j = $i + 1; $j < $n; $j++) {
            $art2 = $articles[$j];

            // Skip if already detected by DOI
            if (isset($processedDoi[$art1['id'] . '_' . $art2['id']])) continue;

            $title2 = $titleNormMap[$art2['id']];
            if (empty($title2)) continue;

            // Optimization: skip if years are too far apart (> 1 year)
            if ($art1['year'] && $art2['year'] && abs($art1['year'] - $art2['year']) > 1) {
                $skipped++;
                continue;
            }

            // Compute title similarity
            $sim = titleSimilarity($title1, $title2);

            if ($sim >= $titleThreshold) {
                $type = $sim >= 95 ? 'title' : 'combined';
                $stmtInsert->execute([$projectId, $art1['id'], $art2['id'], round($sim, 2), $type]);
                $detected++;
            }
        }
    }

    jsonResponse([
        'success'          => true,
        'message'          => "Detecção concluída",
        'articles_checked' => $n,
        'pairs_detected'   => $detected,
        'pairs_skipped'    => $skipped,
        'threshold_used'   => $titleThreshold,
    ]);
    break;

// -----------------------------------------------------------------
case 'PUT':
    // Confirm (canonical) or reject a duplicate pair
    if (!$id) jsonResponse(['error' => true, 'message' => 'ID obrigatório'], 422);

    $data   = getJsonBody();
    $action = $data['action'] ?? ''; // 'confirm' or 'reject'

    if (!in_array($action, ['confirm', 'reject'])) {
        jsonResponse(['error' => true, 'message' => "action deve ser 'confirm' ou 'reject'"], 422);
    }

    // Fetch the pair
    $stmt = $pdo->prepare("SELECT * FROM duplicates WHERE id = ?");
    $stmt->execute([$id]);
    $pair = $stmt->fetch();
    if (!$pair) jsonResponse(['error' => true, 'message' => 'Par não encontrado'], 404);

    if ($action === 'confirm') {
        $canonicalId = (int) ($data['canonical_id'] ?? $pair['article_id_1']);
        $duplicateId = ($canonicalId === (int) $pair['article_id_1'])
                     ? (int) $pair['article_id_2']
                     : (int) $pair['article_id_1'];

        $pdo->beginTransaction();
        try {
            // Update the pair
            $pdo->prepare("
                UPDATE duplicates
                SET status = 'confirmed', canonical_id = ?, reviewed_at = NOW()
                WHERE id = ?
            ")->execute([$canonicalId, $id]);

            // Mark the duplicate article
            $pdo->prepare("
                UPDATE articles SET is_duplicate = 1, duplicate_of = ?, status = 'excluded'
                WHERE id = ?
            ")->execute([$canonicalId, $duplicateId]);

            $pdo->commit();
            jsonResponse(['success' => true, 'message' => 'Duplicata confirmada', 'canonical_id' => $canonicalId, 'duplicate_id' => $duplicateId]);
        } catch (Throwable $e) {
            $pdo->rollBack();
            jsonResponse(['error' => true, 'message' => $e->getMessage()], 500);
        }

    } else {
        // Reject
        $pdo->prepare("
            UPDATE duplicates SET status = 'rejected', reviewed_at = NOW() WHERE id = ?
        ")->execute([$id]);
        jsonResponse(['success' => true, 'message' => 'Par rejeitado como duplicata']);
    }
    break;

// -----------------------------------------------------------------
case 'DELETE':
    if ($id) {
        // Remove specific pair
        $pdo->prepare("DELETE FROM duplicates WHERE id = ?")->execute([$id]);
        jsonResponse(['success' => true]);
    } else {
        // Full reset: removes all pairs and undoes duplicate markings
        $projectId = isset($_GET['project_id']) ? (int) $_GET['project_id'] : null;
        if ($projectId) requireProjectAccess(getDB(), (int) $projectId);
        if (!$projectId) jsonResponse(['error' => true, 'message' => 'id ou project_id obrigatório'], 422);

        $pdo->beginTransaction();
        try {
            // Revert articles marked as duplicates back to pending (only for this detection run)
            $pdo->prepare("
                UPDATE articles SET is_duplicate=0, duplicate_of=NULL, status='pending'
                WHERE project_id=? AND is_duplicate=1
            ")->execute([$projectId]);
            // Remove all pairs for the project
            $pdo->prepare("DELETE FROM duplicates WHERE project_id=?")->execute([$projectId]);
            $pdo->commit();
            jsonResponse(['success' => true, 'message' => 'Detecção redefinida com sucesso']);
        } catch (Throwable $e) {
            $pdo->rollBack();
            jsonResponse(['error' => true, 'message' => $e->getMessage()], 500);
        }
    }
    break;

// -----------------------------------------------------------------
default:
    jsonResponse(['error' => true, 'message' => 'Método não suportado'], 405);
}

// -----------------------------------------------------------------
// Helpers
// -----------------------------------------------------------------

function normalizeDoi(string $doi): string
{
    $doi = strtolower(trim($doi));
    $doi = preg_replace('#^https?://(dx\.)?doi\.org/#i', '', $doi);
    return trim($doi);
}

function normalizeTitle(string $title): string
{
    $title = mb_strtolower($title);
    $title = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $title);
    $title = preg_replace('/\s+/', ' ', $title);
    return trim($title);
}

/**
 * Similarity between two normalized titles (0–100)
 * Uses similar_text for efficiency + Levenshtein for short strings
 */
function titleSimilarity(string $a, string $b): float
{
    if ($a === $b) return 100.0;
    if (empty($a) || empty($b)) return 0.0;

    // similar_text is efficient for long strings
    similar_text($a, $b, $percent);
    return $percent;
}
