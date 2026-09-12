<?php
/**
 * PRISMA-SLR — api/admin.php
 * Global system statistics — access restricted to administrators.
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

requireLogin();
$user = currentUser();
$action = $_GET['action'] ?? 'overview';

// Stop impersonation: allowed even with the impersonated user's role,
// since $user['role'] reflects the viewed user, not the real admin.
if ($action === 'stop_impersonate') {
    if (!isImpersonating()) {
        jsonResponse(['error' => true, 'message' => 'Nenhuma sessão de impersonação ativa.'], 400);
    }
    stopImpersonation();
    jsonResponse(['success' => true]);
}

if (($user['role'] ?? '') !== 'admin') {
    jsonResponse(['error' => true, 'message' => 'Acesso negado.'], 403);
}

$pdo = getDB();

ensureFeedbackTable($pdo);

// ── Overview ──────────────────────────────────────────────────────────────
if ($action === 'overview') {
    $stats = [];

    // General totals
    $stats['total_users']    = (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $stats['total_projects'] = (int) $pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn();
    $stats['total_articles'] = (int) $pdo->query("SELECT COUNT(*) FROM articles")->fetchColumn();

    // Active today
    $stats['users_today'] = (int) $pdo->query(
        "SELECT COUNT(*) FROM users WHERE DATE(last_login) = CURDATE()"
    )->fetchColumn();

    // Active in the last 7 days
    $stats['users_7d'] = (int) $pdo->query(
        "SELECT COUNT(*) FROM users WHERE last_login >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
    )->fetchColumn();

    // New users in the last 30 days
    $stats['users_30d'] = (int) $pdo->query(
        "SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
    )->fetchColumn();

    jsonResponse($stats);
}

// ── List of users with their metrics ──────────────────────────────────────
if ($action === 'users') {
    $rows = $pdo->query("
        SELECT
            u.id,
            u.name,
            u.email,
            u.role,
            u.created_at,
            u.last_login,
            COUNT(DISTINCT p.id)  AS total_projects,
            COUNT(DISTINCT a.id)  AS total_articles
        FROM users u
        LEFT JOIN projects p ON p.user_id = u.id
        LEFT JOIN articles  a ON a.project_id = p.id
        GROUP BY u.id
        ORDER BY u.last_login DESC
    ")->fetchAll();

    jsonResponse($rows);
}

// ── User growth (last 12 months) ───────────────────────────────
if ($action === 'growth') {
    $rows = $pdo->query("
        SELECT
            DATE_FORMAT(created_at, '%Y-%m') AS month,
            COUNT(*) AS new_users
        FROM users
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY month
        ORDER BY month ASC
    ")->fetchAll();

    jsonResponse($rows);
}

// ── Feedback summary ───────────────────────────────────────────────────
if ($action === 'feedback_summary') {
    $summary = $pdo->query(" 
        SELECT
            COUNT(*) AS total_feedback,
            ROUND(AVG(rating), 2) AS avg_rating,
            SUM(rating = 5) AS rating_5,
            SUM(rating = 4) AS rating_4,
            SUM(rating = 3) AS rating_3,
            SUM(rating = 2) AS rating_2,
            SUM(rating = 1) AS rating_1,
            SUM(suggestion IS NOT NULL AND suggestion <> '') AS with_suggestion
        FROM system_feedback
    ")->fetch();

    $latest = $pdo->query(" 
        SELECT COUNT(*)
        FROM system_feedback
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ")->fetchColumn();

    jsonResponse([
        'total_feedback' => (int) ($summary['total_feedback'] ?? 0),
        'avg_rating' => isset($summary['avg_rating']) ? (float) $summary['avg_rating'] : 0,
        'rating_5' => (int) ($summary['rating_5'] ?? 0),
        'rating_4' => (int) ($summary['rating_4'] ?? 0),
        'rating_3' => (int) ($summary['rating_3'] ?? 0),
        'rating_2' => (int) ($summary['rating_2'] ?? 0),
        'rating_1' => (int) ($summary['rating_1'] ?? 0),
        'with_suggestion' => (int) ($summary['with_suggestion'] ?? 0),
        'last_7d' => (int) $latest,
    ]);
}

// ── Detailed feedback list ───────────────────────────────────────────
if ($action === 'feedback_list') {
    $rows = $pdo->query(" 
        SELECT
            sf.id,
            sf.rating,
            sf.suggestion,
            sf.page_context,
            sf.created_at,
            u.name AS user_name,
            u.email AS user_email,
            p.title AS project_title
        FROM system_feedback sf
        JOIN users u ON u.id = sf.user_id
        LEFT JOIN projects p ON p.id = sf.project_id
        ORDER BY sf.created_at DESC, sf.id DESC
        LIMIT 200
    ")->fetchAll();

    jsonResponse($rows);
}

// ── Start impersonation (view/edit as another user) ─────────────────────
if ($action === 'impersonate') {
    $body     = getJsonBody();
    $targetId = (int) ($body['user_id'] ?? $_GET['user_id'] ?? 0);

    if ($targetId <= 0) {
        jsonResponse(['error' => true, 'message' => 'Usuário inválido.'], 400);
    }
    if ($targetId === (int) $user['id']) {
        jsonResponse(['error' => true, 'message' => 'Você já está autenticado como este usuário.'], 400);
    }
    if (!startImpersonation($targetId)) {
        jsonResponse(['error' => true, 'message' => 'Usuário não encontrado.'], 404);
    }
    jsonResponse(['success' => true]);
}

jsonResponse(['error' => true, 'message' => 'Ação inválida.'], 400);

function ensureFeedbackTable(PDO $pdo): void
{
    static $ensured = false;
    if ($ensured) return;

    $pdo->exec("CREATE TABLE IF NOT EXISTS system_feedback (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        project_id INT NULL,
        page_context VARCHAR(100) NOT NULL DEFAULT 'dashboard',
        rating TINYINT UNSIGNED NOT NULL,
        suggestion TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_feedback_user (user_id),
        INDEX idx_feedback_project (project_id),
        INDEX idx_feedback_created (created_at),
        CONSTRAINT fk_feedback_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_feedback_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $ensured = true;
}
