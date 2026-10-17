<?php
/**
 * PRISMA-SLR - Status efetivo do artigo a partir das decisões de triagem.
 * Compartilhado entre api/screening.php e api/kappa.php (resolução de consenso).
 */

/**
 * Calcula o status do artigo com base nas decisões registradas
 */
function computeArticleStatusShared(PDO $pdo, int $articleId): string
{
    // Modo de triagem do projeto e dono (para desempate em modo single)
    $stmt = $pdo->prepare("
        SELECT p.screening_mode, p.user_id AS owner_id FROM projects p
        JOIN articles a ON a.project_id = p.id WHERE a.id = ?
    ");
    $stmt->execute([$articleId]);
    $projectRow = $stmt->fetch();
    $mode    = $projectRow['screening_mode'] ?: 'single';
    $ownerId = (int) ($projectRow['owner_id'] ?? 0);

    $stmt = $pdo->prepare("
        SELECT phase, decision, reviewer_id, is_consensus
        FROM screening_decisions WHERE article_id = ?
    ");
    $stmt->execute([$articleId]);
    $rows = $stmt->fetchAll();

    // Revisores de apoio não votam: suas decisões não contam para acordo, conflito nem status
    $stmt = $pdo->prepare("
        SELECT pm.user_id FROM project_members pm
        JOIN articles a ON a.project_id = pm.project_id
        WHERE a.id = ? AND pm.role = 'support'
    ");
    $stmt->execute([$articleId]);
    $supportIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    if ($supportIds) {
        $rows = array_values(array_filter($rows, fn($r) => !in_array((int) $r['reviewer_id'], $supportIds, true)));
    }

    // Decisão efetiva por fase: consenso > acordo entre 2+ revisores (dual) > decisão única (single)
    $decisions = [];
    foreach (['screening', 'eligibility'] as $ph) {
        $phaseRows = array_values(array_filter($rows, fn($r) => $r['phase'] === $ph));
        if (empty($phaseRows)) continue;

        $consensus = array_values(array_filter($phaseRows, fn($r) => (int) $r['is_consensus'] === 1));
        if (!empty($consensus)) {
            $decisions[$ph] = $consensus[0]['decision'];
            continue;
        }
        $individual = array_values(array_filter($phaseRows, fn($r) => (int) $r['is_consensus'] === 0));
        if ($mode === 'dual') {
            $unique = array_unique(array_column($individual, 'decision'));
            if (count($individual) >= 2 && count($unique) === 1) {
                $decisions[$ph] = $unique[0];           // acordo
            } elseif (count($individual) >= 2) {
                $decisions[$ph] = 'conflict';           // aguarda resolução
            }
            // 1 decisão apenas: aguarda o segundo revisor
        } elseif (!empty($individual)) {
            // Modo single com mais de um revisor divergindo: a decisão do dono
            // do projeto prevalece como oficial (demais votos ficam registrados,
            // mas não determinam o status sozinhos — resolução formal por
            // consenso continua disponível na página de Concordância).
            $ownerDecision = array_values(array_filter(
                $individual, fn($r) => (int) $r['reviewer_id'] === $ownerId
            ));
            $decisions[$ph] = $ownerDecision[0]['decision'] ?? $individual[0]['decision'];
        }
    }

    // Conflito não avança o artigo
    if (($decisions['screening']   ?? '') === 'conflict') return 'screened';

    // Excluído em alguma fase
    if (($decisions['screening']   ?? '') === 'exclude') return 'excluded';
    if (($decisions['eligibility'] ?? '') === 'exclude') return 'excluded';

    // Incluído na elegibilidade
    if (($decisions['eligibility'] ?? '') === 'include') return 'included';

    // Passou pela triagem
    if (($decisions['screening'] ?? '') === 'include') return 'eligible';

    // Passou pela triagem com incerteza
    if (($decisions['screening'] ?? '') === 'uncertain') return 'screened';

    return 'screened';
}
