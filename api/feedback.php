<?php
/**
 * PRISMA-SLR - API: Feedback do sistema
 * POST /api/feedback.php { rating, suggestion?, page?, project_id? }
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/api_guard.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$apiUser = requireApiAuth();
$pdo = getDB();

ensureFeedbackTable($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => true, 'message' => 'Método não suportado'], 405);
}

$data = getJsonBody();

$rating = isset($data['rating']) ? (int) $data['rating'] : 0;
$suggestion = trim((string) ($data['suggestion'] ?? ''));
$page = trim((string) ($data['page'] ?? 'dashboard'));
$projectId = !empty($data['project_id']) ? (int) $data['project_id'] : null;

if ($rating < 1 || $rating > 5) {
    jsonResponse(['error' => true, 'message' => 'Informe uma nota entre 1 e 5'], 422);
}

if ($suggestion !== '' && mb_strlen($suggestion) > 3000) {
    jsonResponse(['error' => true, 'message' => 'A sugestão deve ter no máximo 3000 caracteres'], 422);
}

if ($projectId) {
    requireProjectAccess($pdo, $projectId);
}

$stmt = $pdo->prepare('
    INSERT INTO system_feedback (user_id, project_id, page_context, rating, suggestion)
    VALUES (?, ?, ?, ?, ?)
');

$stmt->execute([
    (int) $apiUser['id'],
    $projectId,
    $page !== '' ? mb_substr($page, 0, 100) : 'dashboard',
    $rating,
    $suggestion !== '' ? $suggestion : null,
]);

jsonResponse([
    'success' => true,
    'message' => 'Obrigado pela sua avaliação. Seu feedback foi registrado.',
]);

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