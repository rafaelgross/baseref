<?php
/**
 * PRISMA-SLR - API: Concordância entre revisores (kappa de Cohen) e multirrevisor
 *
 * GET  /api/kappa.php?project_id=N&phase=screening|eligibility
 *        → kappa de Cohen, tabela de contingência, conflitos pendentes,
 *          revisores do projeto e modo de triagem. Na elegibilidade, o kappa considera só os relatórios
 *          avaliados no texto completo do fluxograma PRISMA (ver lib/PrismaReasons.php).
 * POST /api/kappa.php  { action: "consensus", article_id, phase, decision, reason_id?, notes? }
 *        → registra decisão de consenso (resolução de conflito)
 * POST /api/kappa.php  { action: "set_mode", project_id, screening_mode: "single"|"dual" }
 * POST /api/kappa.php  { action: "add_member", project_id, email, role?: "reviewer"|"support" }
 * POST /api/kappa.php  { action: "set_role", project_id, user_id, role: "reviewer"|"support" }
 * POST /api/kappa.php  { action: "remove_member", project_id, user_id }
 *
 * Papéis: reviewer = revisor cego (entra no kappa); support = apoio (vê as decisões dos outros,
 * fora do kappa e do status oficial). Conflitos só aparecem para dono, admin e apoio.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/api_guard.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$apiUser = requireApiAuth();
$pdo     = getDB();
$method  = $_SERVER['REQUEST_METHOD'];

// -----------------------------------------------------------------
if ($method === 'GET') {
    $projectId = (int) ($_GET['project_id'] ?? 0);
    $phase     = $_GET['phase'] ?? 'screening';
    if (!$projectId) jsonResponse(['error' => true, 'message' => 'project_id obrigatório'], 422);
    if (!in_array($phase, ['screening', 'eligibility'])) $phase = 'screening';
    requireProjectAccess($pdo, $projectId);

    // Modo e revisores
    $stmt = $pdo->prepare("SELECT screening_mode, user_id FROM projects WHERE id = ?");
    $stmt->execute([$projectId]);
    $proj = $stmt->fetch();

    $stmt = $pdo->prepare("
        SELECT u.id, u.name, u.email, 'owner' AS role
        FROM users u JOIN projects p ON p.user_id = u.id WHERE p.id = ?
        UNION
        SELECT u.id, u.name, u.email, pm.role
        FROM users u JOIN project_members pm ON pm.user_id = u.id WHERE pm.project_id = ?
    ");
    $stmt->execute([$projectId, $projectId]);
    $members = $stmt->fetchAll();
    $supportIds = array_map('intval', array_column(array_filter($members, fn($m) => $m['role'] === 'support'), 'id'));
    $notSupport = $supportIds ? ' AND sd.reviewer_id NOT IN (' . implode(',', $supportIds) . ')' : '';
    $myRole     = projectRole($pdo, $projectId, (int) $apiUser['id']);
    $canSeeOthers = in_array($myRole, ['owner', 'admin', 'support'], true);

    // Pares de decisões individuais por artigo (dois revisores mais ativos)
    $stmt = $pdo->prepare("
        SELECT sd.article_id, sd.reviewer_id, sd.decision
        FROM screening_decisions sd
        JOIN articles a ON a.id = sd.article_id
        WHERE a.project_id = ? AND a.is_duplicate = 0
              AND sd.phase = ? AND sd.is_consensus = 0 AND sd.reviewer_id IS NOT NULL $notSupport
    ");
    $stmt->execute([$projectId, $phase]);
    $rows = $stmt->fetchAll();

    $byReviewer = [];
    foreach ($rows as $r) $byReviewer[$r['reviewer_id']][$r['article_id']] = $r['decision'];

    // Dois revisores com mais decisões
    uasort($byReviewer, fn($a, $b) => count($b) <=> count($a));
    $reviewerIds = array_slice(array_keys($byReviewer), 0, 2);

    $kappa = null; $contingency = null; $nJoint = 0; $agreement = null; $eligibilityScope = null;
    $cats = ['include', 'exclude', 'uncertain'];

    if (count($reviewerIds) === 2) {
        [$rA, $rB] = $reviewerIds;
        $joint = array_intersect_key($byReviewer[$rA], $byReviewer[$rB]);

        // Elegibilidade: só os relatórios avaliados no texto completo do fluxograma PRISMA (sem os não
        // recuperados e sem os registros vindos de divergência na triagem). Nenhuma decisão é alterada.
        if ($phase === 'eligibility') {
            require_once __DIR__ . '/../lib/PrismaReasons.php';
            ensureReasonFlagColumn($pdo);
            $ownerId = (int) ($proj['user_id'] ?? 0);
            $refId   = in_array($ownerId, [$rA, $rB], true) ? $ownerId : $rA;   // revisor do fluxograma: o dono do projeto
            $stmt = $pdo->prepare("
                SELECT sd.article_id, sd.phase, sd.decision, er.reason, er.not_retrieved
                FROM screening_decisions sd
                JOIN articles a ON a.id = sd.article_id
                LEFT JOIN exclusion_reasons er ON er.id = sd.reason_id
                WHERE a.project_id = ? AND a.is_duplicate = 0 AND sd.is_consensus = 0 AND sd.reviewer_id = ?
                ORDER BY sd.decided_at, sd.id
            ");
            $stmt->execute([$projectId, $refId]);
            $refScreening = []; $refEligibility = [];
            foreach ($stmt->fetchAll() as $r) {                     // a última decisão de cada fase prevalece
                if ($r['phase'] === 'screening') $refScreening[(int) $r['article_id']] = $r['decision'];
                else $refEligibility[(int) $r['article_id']] = ['decision' => $r['decision'], 'reason' => $r['reason'], 'not_retrieved' => $r['not_retrieved']];
            }
            $scope = eligibilityReportsAssessed(array_keys($joint), $refScreening, $refEligibility);
            $eligibilityScope = [
                'decided_by_both'      => count($joint),
                'reports_assessed'     => count($scope['ids']),
                'not_retrieved'        => $scope['not_retrieved'],
                'screening_divergence' => $scope['screening_divergence'],
            ];
            $joint = array_intersect_key($joint, array_flip($scope['ids']));
        }
        $nJoint = count($joint);

        if ($nJoint > 0) {
            // Tabela de contingência 3×3
            $table = array_fill_keys($cats, array_fill_keys($cats, 0));
            foreach ($joint as $articleId => $decA) {
                $decB = $byReviewer[$rB][$articleId];
                $table[$decA][$decB]++;
            }

            // Kappa de Cohen: κ = (po − pe) / (1 − pe)
            $po = 0; $margA = array_fill_keys($cats, 0); $margB = array_fill_keys($cats, 0);
            foreach ($cats as $c1) {
                $po += $table[$c1][$c1];
                foreach ($cats as $c2) {
                    $margA[$c1] += $table[$c1][$c2];
                    $margB[$c2] += $table[$c1][$c2];
                }
            }
            $po /= $nJoint;
            $pe  = 0;
            foreach ($cats as $c) $pe += ($margA[$c] / $nJoint) * ($margB[$c] / $nJoint);

            $kappa     = ($pe < 1) ? (($po - $pe) / (1 - $pe)) : 1.0;
            $agreement = round($po * 100, 2);
            $contingency = $table;
        }
    }

    // Conflitos pendentes: 2+ decisões individuais divergentes e sem consenso
    $stmt = $pdo->prepare("
        SELECT a.id, a.title, a.year, a.journal,
               GROUP_CONCAT(CONCAT(u.name, ': ', sd.decision) ORDER BY sd.reviewer_id SEPARATOR ' | ') AS votes
        FROM articles a
        JOIN screening_decisions sd ON sd.article_id = a.id AND sd.phase = ? AND sd.is_consensus = 0
        LEFT JOIN users u ON u.id = sd.reviewer_id
        WHERE a.project_id = ? AND a.is_duplicate = 0 $notSupport
              AND NOT EXISTS (SELECT 1 FROM screening_decisions sc
                              WHERE sc.article_id = a.id AND sc.phase = ? AND sc.is_consensus = 1)
        GROUP BY a.id
        HAVING COUNT(DISTINCT sd.reviewer_id) >= 2 AND COUNT(DISTINCT sd.decision) >= 2
        ORDER BY a.id
    ");
    $stmt->execute([$phase, $projectId, $phase]);
    // Revisor cego não vê o voto dos outros
    $conflicts = $canSeeOthers ? $stmt->fetchAll() : [];

    jsonResponse([
        'screening_mode' => $proj['screening_mode'] ?? 'single',
        'is_owner'       => (int) ($proj['user_id'] ?? 0) === (int) $apiUser['id'],
        'my_role'        => $myRole,
        'members'        => $members,
        'phase'          => $phase,
        'kappa'          => $kappa,
        'interpretation' => $kappa !== null ? interpretKappa($kappa) : null,
        'observed_agreement_percent' => $agreement,
        'n_joint'        => $nJoint,
        'eligibility_scope' => $eligibilityScope,   // só na fase de elegibilidade: o que entrou e o que ficou fora da conta
        'contingency'    => $contingency,
        'categories'     => $cats,
        'conflicts'      => $conflicts,
    ]);
}

// -----------------------------------------------------------------
if ($method !== 'POST') jsonResponse(['error' => true, 'message' => 'Método não suportado'], 405);

$data   = getJsonBody();
$action = $data['action'] ?? '';

switch ($action) {

case 'consensus':
    $articleId = (int) ($data['article_id'] ?? 0);
    $phase     = $data['phase'] ?? '';
    $decision  = $data['decision'] ?? '';
    if (!$articleId || !in_array($phase, ['screening', 'eligibility'])
        || !in_array($decision, ['include', 'exclude', 'uncertain'])) {
        jsonResponse(['error' => true, 'message' => 'article_id, phase e decision válidos são obrigatórios'], 422);
    }
    requireArticleAccess($pdo, $articleId);
    $role = projectRole($pdo, (int) $pdo->query("SELECT project_id FROM articles WHERE id = " . $articleId)->fetchColumn(), (int) $apiUser['id']);
    if (!in_array($role, ['owner', 'admin'], true)) {
        jsonResponse(['error' => true, 'message' => 'Apenas o dono do projeto resolve conflitos'], 403);
    }

    $reasonId = !empty($data['reason_id']) ? (int) $data['reason_id'] : null;
    $notes    = $data['notes'] ?? null;

    // Substitui qualquer consenso anterior
    $pdo->prepare("DELETE FROM screening_decisions WHERE article_id = ? AND phase = ? AND is_consensus = 1")
        ->execute([$articleId, $phase]);
    $pdo->prepare("
        INSERT INTO screening_decisions (article_id, reviewer_id, is_consensus, phase, decision, reason_id, notes)
        VALUES (?, ?, 1, ?, ?, ?, ?)
    ")->execute([$articleId, (int) $apiUser['id'], $phase, $decision, $reasonId, $notes]);

    // Recalcula status (mesma lógica do screening.php)
    require_once __DIR__ . '/screening_status.php';
    $newStatus = computeArticleStatusShared($pdo, $articleId);
    $pdo->prepare("UPDATE articles SET status = ? WHERE id = ?")->execute([$newStatus, $articleId]);

    jsonResponse(['success' => true, 'article_id' => $articleId, 'new_status' => $newStatus]);

case 'set_mode':
    $projectId = (int) ($data['project_id'] ?? 0);
    $mode      = $data['screening_mode'] ?? '';
    if (!$projectId || !in_array($mode, ['single', 'dual'])) {
        jsonResponse(['error' => true, 'message' => 'project_id e screening_mode válidos são obrigatórios'], 422);
    }
    requireProjectAccess($pdo, $projectId);
    requireProjectOwner($pdo, $projectId, (int) $apiUser['id']);

    $pdo->prepare("UPDATE projects SET screening_mode = ? WHERE id = ?")->execute([$mode, $projectId]);
    jsonResponse(['success' => true, 'screening_mode' => $mode]);

case 'add_member':
    $projectId = (int) ($data['project_id'] ?? 0);
    $email     = strtolower(trim($data['email'] ?? ''));
    if (!$projectId || !$email) jsonResponse(['error' => true, 'message' => 'project_id e email são obrigatórios'], 422);
    requireProjectAccess($pdo, $projectId);
    requireProjectOwner($pdo, $projectId, (int) $apiUser['id']);

    $stmt = $pdo->prepare("SELECT id, name FROM users WHERE LOWER(email) = ?");
    $stmt->execute([$email]);
    $member = $stmt->fetch();
    if (!$member) {
        jsonResponse(['error' => true, 'message' => 'Usuário não encontrado — o revisor precisa ter feito login no sistema ao menos uma vez'], 404);
    }
    $role = ($data['role'] ?? 'reviewer') === 'support' ? 'support' : 'reviewer';

    $pdo->prepare("INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, ?)
                   ON DUPLICATE KEY UPDATE role = VALUES(role)")
        ->execute([$projectId, $member['id'], $role]);
    jsonResponse(['success' => true, 'message' => 'Revisor ' . $member['name'] . ($role === 'support' ? ' (apoio)' : ' (cego)') . ' adicionado ao projeto']);

case 'set_role':
    $projectId = (int) ($data['project_id'] ?? 0);
    $userId    = (int) ($data['user_id'] ?? 0);
    $role      = $data['role'] ?? '';
    if (!$projectId || !$userId || !in_array($role, ['reviewer', 'support'], true)) {
        jsonResponse(['error' => true, 'message' => 'project_id, user_id e role (reviewer|support) são obrigatórios'], 422);
    }
    requireProjectAccess($pdo, $projectId);
    requireProjectOwner($pdo, $projectId, (int) $apiUser['id']);

    $pdo->prepare("UPDATE project_members SET role = ? WHERE project_id = ? AND user_id = ?")
        ->execute([$role, $projectId, $userId]);

    // O status oficial depende de quem vota: recalcula os artigos que esse revisor decidiu
    require_once __DIR__ . '/screening_status.php';
    $stmt = $pdo->prepare("SELECT DISTINCT sd.article_id FROM screening_decisions sd
                           JOIN articles a ON a.id = sd.article_id
                           WHERE a.project_id = ? AND sd.reviewer_id = ?");
    $stmt->execute([$projectId, $userId]);
    $upd = $pdo->prepare("UPDATE articles SET status = ? WHERE id = ?");
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $aid) {
        $upd->execute([computeArticleStatusShared($pdo, (int) $aid), $aid]);
    }
    jsonResponse(['success' => true, 'message' => 'Papel atualizado']);

case 'remove_member':
    $projectId = (int) ($data['project_id'] ?? 0);
    $userId    = (int) ($data['user_id'] ?? 0);
    if (!$projectId || !$userId) jsonResponse(['error' => true, 'message' => 'project_id e user_id são obrigatórios'], 422);
    requireProjectAccess($pdo, $projectId);
    requireProjectOwner($pdo, $projectId, (int) $apiUser['id']);

    $pdo->prepare("DELETE FROM project_members WHERE project_id = ? AND user_id = ?")
        ->execute([$projectId, $userId]);
    jsonResponse(['success' => true, 'message' => 'Revisor removido']);

default:
    jsonResponse(['error' => true, 'message' => 'action inválida'], 422);
}

// -----------------------------------------------------------------
// Helpers
// -----------------------------------------------------------------

/**
 * Interpretação de Landis & Koch (1977) para o kappa.
 */
function interpretKappa(float $k): string
{
    if ($k < 0)     return 'Concordância pior que o acaso';
    if ($k <= 0.20) return 'Concordância leve';
    if ($k <= 0.40) return 'Concordância razoável';
    if ($k <= 0.60) return 'Concordância moderada';
    if ($k <= 0.80) return 'Concordância substancial';
    return 'Concordância quase perfeita';
}

/**
 * Apenas o dono do projeto (ou admin) altera modo e membros.
 */
function requireProjectOwner(PDO $pdo, int $projectId, int $userId): void
{
    $user = currentUser();
    if (($user['role'] ?? '') === 'admin') return;
    $stmt = $pdo->prepare("SELECT 1 FROM projects WHERE id = ? AND user_id = ?");
    $stmt->execute([$projectId, $userId]);
    if (!$stmt->fetch()) {
        jsonResponse(['error' => true, 'message' => 'Apenas o dono do projeto pode fazer isso'], 403);
    }
}
