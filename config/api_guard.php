<?php
/**
 * PRISMA-SLR — config/api_guard.php
 * API endpoint protection: session-based authentication and
 * project/article ownership verification.
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/database.php';

/**
 * Requires an authenticated user; responds with 401 in JSON (APIs don't redirect).
 */
function requireApiAuth(): array
{
    if (!isLoggedIn()) {
        jsonResponse(['error' => true, 'message' => 'Não autenticado'], 401);
    }
    return currentUser();
}

/**
 * Ensures the project belongs to the logged-in user (or the user is admin,
 * or the project is a legacy one without an owner). Responds with 403 otherwise.
 */
function requireProjectAccess(PDO $pdo, int $projectId): void
{
    $user = requireApiAuth();
    if (($user['role'] ?? 'user') === 'admin') return;

    $stmt = $pdo->prepare("SELECT user_id FROM projects WHERE id = ?");
    $stmt->execute([$projectId]);
    $row = $stmt->fetch();
    if (!$row) {
        jsonResponse(['error' => true, 'message' => 'Projeto não encontrado'], 404);
    }
    if ($row['user_id'] === null || (int) $row['user_id'] === (int) $user['id']) return;

    // Invited reviewers (multi-reviewer screening) also have access
    $stmt = $pdo->prepare("SELECT 1 FROM project_members WHERE project_id = ? AND user_id = ?");
    $stmt->execute([$projectId, (int) $user['id']]);
    if (!$stmt->fetch()) {
        jsonResponse(['error' => true, 'message' => 'Acesso negado a este projeto'], 403);
    }
}

/**
 * SQL fragment: article was PERSONALLY included by the logged-in reviewer
 * (include decision at eligibility), not the project's consolidated official
 * status (which may reflect another reviewer's decision). Use in queries
 * that already reference the "articles" table with alias "a".
 */
function personalIncludedExists(int $reviewerId): string
{
    return "EXISTS (
        SELECT 1 FROM screening_decisions sd_pi
        WHERE sd_pi.article_id = a.id AND sd_pi.phase = 'eligibility' AND sd_pi.decision = 'include'
              AND sd_pi.is_consensus = 0 AND (sd_pi.reviewer_id = $reviewerId OR sd_pi.reviewer_id IS NULL)
    )";
}

/**
 * Verifies access to the project that owns an article.
 */
function requireArticleAccess(PDO $pdo, int $articleId): void
{
    $stmt = $pdo->prepare("SELECT project_id FROM articles WHERE id = ?");
    $stmt->execute([$articleId]);
    $projectId = $stmt->fetchColumn();
    if (!$projectId) {
        jsonResponse(['error' => true, 'message' => 'Artigo não encontrado'], 404);
    }
    requireProjectAccess($pdo, (int) $projectId);
}
