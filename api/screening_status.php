<?php
/**
 * PRISMA-SLR - Effective article status derived from the screening decisions.
 * Shared between api/screening.php and api/kappa.php (consensus resolution).
 */

/**
 * Computes the article's status based on the recorded decisions
 */
function computeArticleStatusShared(PDO $pdo, int $articleId): string
{
    // Project's screening mode and owner (for tie-breaking in single mode)
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

    // Effective decision per phase: consensus > agreement between 2+ reviewers (dual) > single decision (single)
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
                $decisions[$ph] = $unique[0];           // agreement
            } elseif (count($individual) >= 2) {
                $decisions[$ph] = 'conflict';           // awaiting resolution
            }
            // Only 1 decision: awaiting the second reviewer
        } elseif (!empty($individual)) {
            // Single mode with more than one reviewer disagreeing: the project
            // owner's decision prevails as official (the other votes stay
            // recorded, but don't determine the status on their own — formal
            // resolution by consensus remains available on the Agreement page).
            $ownerDecision = array_values(array_filter(
                $individual, fn($r) => (int) $r['reviewer_id'] === $ownerId
            ));
            $decisions[$ph] = $ownerDecision[0]['decision'] ?? $individual[0]['decision'];
        }
    }

    // Conflict does not advance the article
    if (($decisions['screening']   ?? '') === 'conflict') return 'screened';

    // Excluded in some phase
    if (($decisions['screening']   ?? '') === 'exclude') return 'excluded';
    if (($decisions['eligibility'] ?? '') === 'exclude') return 'excluded';

    // Included at eligibility
    if (($decisions['eligibility'] ?? '') === 'include') return 'included';

    // Passed screening
    if (($decisions['screening'] ?? '') === 'include') return 'eligible';

    // Passed screening with uncertainty
    if (($decisions['screening'] ?? '') === 'uncertain') return 'screened';

    return 'screened';
}
