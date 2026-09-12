<?php
/**
 * PRISMA-SLR - API: Quality Assessment / Risk of Bias (CASP, JBI, or custom)
 *
 * GET  /api/quality.php?project_id=N            → instrument + included articles with progress
 * GET  /api/quality.php?project_id=N&article_id=M → questions + article's answers
 * GET  /api/quality.php?project_id=N&csv=1      → exports article × question matrix as CSV
 * POST { action: "create_instrument", project_id, template: "casp"|"jbi"|"blank", name? }
 * POST { action: "answer", article_id, question_id, answer, notes? }
 * POST { action: "add_question", instrument_id, question }
 * DELETE ?question_id=N                          → removes question (and answers, cascading)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/api_guard.php';

header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$apiUser    = requireApiAuth();
$reviewerId = (int) $apiUser['id'];
$pdo    = getDB();
$method = $_SERVER['REQUEST_METHOD'];

// Well-established instrument templates
const QUALITY_TEMPLATES = [
    'casp' => [
        'name'      => 'CASP — Critical Appraisal Skills Programme (genérico, 10 itens)',
        'questions' => [
            'O estudo apresenta objetivos claramente definidos?',
            'A metodologia utilizada é apropriada para responder à questão de pesquisa?',
            'O desenho do estudo é adequado aos objetivos?',
            'A estratégia de recrutamento/seleção é apropriada?',
            'Os dados foram coletados de forma a abordar a questão de pesquisa?',
            'A relação entre pesquisador e participantes foi considerada adequadamente?',
            'Questões éticas foram levadas em consideração?',
            'A análise dos dados foi suficientemente rigorosa?',
            'Os resultados são apresentados de forma clara?',
            'O estudo traz contribuições relevantes (valor da pesquisa)?',
        ],
    ],
    'jbi' => [
        'name'      => 'JBI — Joanna Briggs Institute (estudos transversais analíticos, 8 itens)',
        'questions' => [
            'Os critérios de inclusão da amostra foram claramente definidos?',
            'Os sujeitos do estudo e o contexto foram descritos em detalhe?',
            'A exposição foi medida de forma válida e confiável?',
            'Critérios objetivos e padronizados foram usados para medir a condição?',
            'Fatores de confusão foram identificados?',
            'Estratégias para lidar com fatores de confusão foram declaradas?',
            'Os desfechos foram medidos de forma válida e confiável?',
            'A análise estatística utilizada foi apropriada?',
        ],
    ],
];

// -----------------------------------------------------------------
if ($method === 'GET') {
    $projectId = (int) ($_GET['project_id'] ?? 0);
    if (!$projectId) jsonResponse(['error' => true, 'message' => 'project_id obrigatório'], 422);
    requireProjectAccess($pdo, $projectId);

    // Project's instrument
    $stmt = $pdo->prepare("SELECT * FROM quality_instruments WHERE project_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$projectId]);
    $instrument = $stmt->fetch() ?: null;

    $questions = [];
    if ($instrument) {
        $stmt = $pdo->prepare("SELECT id, question, sort_order FROM quality_questions WHERE instrument_id = ? ORDER BY sort_order, id");
        $stmt->execute([$instrument['id']]);
        $questions = $stmt->fetchAll();
    }

    // CSV export: article × question matrix
    if (!empty($_GET['csv'])) {
        exportQualityCsv($pdo, $projectId, $instrument, $questions, $reviewerId);
    }

    // Detail for a single article
    if (!empty($_GET['article_id'])) {
        $articleId = (int) $_GET['article_id'];
        requireArticleAccess($pdo, $articleId);
        $stmt = $pdo->prepare("SELECT question_id, answer, notes FROM quality_answers WHERE article_id = ?");
        $stmt->execute([$articleId]);
        jsonResponse([
            'instrument' => $instrument,
            'questions'  => $questions,
            'answers'    => array_column($stmt->fetchAll(), null, 'question_id'),
        ]);
    }

    // List of included articles with assessment progress
    $stmt = $pdo->prepare("
        SELECT a.id, a.title, a.year, a.journal, a.doi,
               COUNT(qa.id) AS answered,
               SUM(qa.answer = 'yes') AS yes_count
        FROM articles a
        LEFT JOIN quality_answers qa ON qa.article_id = a.id
        WHERE a.project_id = ? AND " . personalIncludedExists($reviewerId) . " AND a.is_duplicate = 0
        GROUP BY a.id
        ORDER BY a.id
    ");
    $stmt->execute([$projectId]);

    jsonResponse([
        'instrument'  => $instrument,
        'questions'   => $questions,
        'n_questions' => count($questions),
        'articles'    => $stmt->fetchAll(),
        'templates'   => array_map(fn($t) => $t['name'], QUALITY_TEMPLATES),
    ]);
}

// -----------------------------------------------------------------
if ($method === 'DELETE') {
    $questionId = (int) ($_GET['question_id'] ?? 0);
    if (!$questionId) jsonResponse(['error' => true, 'message' => 'question_id obrigatório'], 422);

    $stmt = $pdo->prepare("
        SELECT qi.project_id FROM quality_questions qq
        JOIN quality_instruments qi ON qi.id = qq.instrument_id WHERE qq.id = ?
    ");
    $stmt->execute([$questionId]);
    $pid = (int) $stmt->fetchColumn();
    if (!$pid) jsonResponse(['error' => true, 'message' => 'Pergunta não encontrada'], 404);
    requireProjectAccess($pdo, $pid);

    $pdo->prepare("DELETE FROM quality_questions WHERE id = ?")->execute([$questionId]);
    jsonResponse(['success' => true]);
}

if ($method !== 'POST') jsonResponse(['error' => true, 'message' => 'Método não suportado'], 405);

$data   = getJsonBody();
$action = $data['action'] ?? '';

switch ($action) {

case 'create_instrument':
    $projectId = (int) ($data['project_id'] ?? 0);
    $template  = $data['template'] ?? 'blank';
    if (!$projectId) jsonResponse(['error' => true, 'message' => 'project_id obrigatório'], 422);
    requireProjectAccess($pdo, $projectId);

    $tpl  = QUALITY_TEMPLATES[$template] ?? null;
    $name = trim($data['name'] ?? '') ?: ($tpl['name'] ?? 'Instrumento personalizado');

    $pdo->beginTransaction();
    try {
        // Replaces the previous instrument (and answers, cascading)
        $pdo->prepare("DELETE FROM quality_instruments WHERE project_id = ?")->execute([$projectId]);
        $pdo->prepare("INSERT INTO quality_instruments (project_id, name) VALUES (?, ?)")
            ->execute([$projectId, $name]);
        $instrumentId = (int) $pdo->lastInsertId();

        if ($tpl) {
            $stmtQ = $pdo->prepare("INSERT INTO quality_questions (instrument_id, question, sort_order) VALUES (?, ?, ?)");
            foreach ($tpl['questions'] as $i => $q) $stmtQ->execute([$instrumentId, $q, $i + 1]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        jsonResponse(['error' => true, 'message' => $e->getMessage()], 500);
    }
    jsonResponse(['success' => true, 'instrument_id' => $instrumentId]);

case 'answer':
    $articleId  = (int) ($data['article_id'] ?? 0);
    $questionId = (int) ($data['question_id'] ?? 0);
    $answer     = $data['answer'] ?? '';
    if (!$articleId || !$questionId || !in_array($answer, ['yes', 'no', 'unclear', 'na'])) {
        jsonResponse(['error' => true, 'message' => 'article_id, question_id e answer válidos são obrigatórios'], 422);
    }
    requireArticleAccess($pdo, $articleId);

    $pdo->prepare("
        INSERT INTO quality_answers (article_id, question_id, answer, notes)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE answer = VALUES(answer), notes = VALUES(notes)
    ")->execute([$articleId, $questionId, $answer, $data['notes'] ?? null]);
    jsonResponse(['success' => true]);

case 'add_question':
    $instrumentId = (int) ($data['instrument_id'] ?? 0);
    $question     = trim($data['question'] ?? '');
    if (!$instrumentId || !$question) jsonResponse(['error' => true, 'message' => 'instrument_id e question são obrigatórios'], 422);

    $stmt = $pdo->prepare("SELECT project_id FROM quality_instruments WHERE id = ?");
    $stmt->execute([$instrumentId]);
    $pid = (int) $stmt->fetchColumn();
    if (!$pid) jsonResponse(['error' => true, 'message' => 'Instrumento não encontrado'], 404);
    requireProjectAccess($pdo, $pid);

    $pdo->prepare("
        INSERT INTO quality_questions (instrument_id, question, sort_order)
        VALUES (?, ?, (SELECT COALESCE(MAX(q2.sort_order), 0) + 1 FROM quality_questions q2 WHERE q2.instrument_id = ?))
    ")->execute([$instrumentId, $question, $instrumentId]);
    jsonResponse(['success' => true, 'question_id' => (int) $pdo->lastInsertId()]);

default:
    jsonResponse(['error' => true, 'message' => 'action inválida'], 422);
}

// -----------------------------------------------------------------
function exportQualityCsv(PDO $pdo, int $projectId, ?array $instrument, array $questions, int $reviewerId): void
{
    $stmt = $pdo->prepare("
        SELECT a.id, a.title, a.year, qa.question_id, qa.answer
        FROM articles a
        LEFT JOIN quality_answers qa ON qa.article_id = a.id
        WHERE a.project_id = ? AND " . personalIncludedExists($reviewerId) . " AND a.is_duplicate = 0
        ORDER BY a.id
    ");
    $stmt->execute([$projectId]);

    $byArticle = [];
    foreach ($stmt->fetchAll() as $r) {
        $byArticle[$r['id']]['title'] = $r['title'];
        $byArticle[$r['id']]['year']  = $r['year'];
        if ($r['question_id']) $byArticle[$r['id']]['answers'][$r['question_id']] = $r['answer'];
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="quality_assessment.csv"');
    echo "\xEF\xBB\xBF"; // BOM for Excel

    $out = fopen('php://output', 'w');
    $head = ['ID', 'Título', 'Ano'];
    foreach ($questions as $i => $q) $head[] = 'Q' . ($i + 1);
    $head[] = 'Score (% sim)';
    fputcsv($out, $head);

    foreach ($byArticle as $id => $a) {
        $row = [$id, $a['title'], $a['year']];
        $yes = 0; $applicable = 0;
        foreach ($questions as $q) {
            $ans = $a['answers'][$q['id']] ?? '';
            $row[] = $ans;
            if ($ans !== '' && $ans !== 'na') { $applicable++; if ($ans === 'yes') $yes++; }
        }
        $row[] = $applicable ? round(100 * $yes / $applicable, 1) : '';
        fputcsv($out, $row);
    }
    fclose($out);
    exit;
}
