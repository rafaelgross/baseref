<?php
/**
 * PRISMA-SLR - API: AI-assisted screening (optional)
 *
 * GET  /api/ai-screening.php?status=1       → { enabled: bool, provider }
 * POST /api/ai-screening.php { article_id } → AI suggestion:
 *      { decision: include|exclude|uncertain, confidence: 0-100, rationale }
 *
 * The suggestion is NOT recorded as a decision — the human reviewer decides.
 * Supports Gemini (free) and Anthropic. See config/ai.php.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/api_guard.php';
require_once __DIR__ . '/../config/ai.php';

header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$apiUser = requireApiAuth();
$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    jsonResponse(['enabled' => aiEnabled(), 'provider' => aiEnabled() ? AI_PROVIDER : null, 'model' => aiEnabled() ? AI_MODEL : null]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => true, 'message' => 'Método não suportado'], 405);
}
if (!aiEnabled()) {
    jsonResponse(['error' => true, 'message' => 'Triagem assistida por IA não configurada (ver config/ai.local.php)'], 503);
}

// Global daily limit
if (AI_DAILY_LIMIT > 0) {
    $used = (int) $pdo->query(
        "SELECT COUNT(*) FROM ai_usage WHERE used_at >= CURDATE()"
    )->fetchColumn();
    if ($used >= AI_DAILY_LIMIT) {
        jsonResponse(['error' => true, 'message' => "Limite diário de sugestões IA atingido ({$used}/" . AI_DAILY_LIMIT . "). Tente novamente amanhã."], 429);
    }
}

$data      = getJsonBody();
$articleId = (int) ($data['article_id'] ?? 0);
if (!$articleId) jsonResponse(['error' => true, 'message' => 'article_id obrigatório'], 422);
requireArticleAccess($pdo, $articleId);

// Article + project criteria
$stmt = $pdo->prepare("
    SELECT a.title, a.abstract, a.year, a.journal,
           p.title AS project_title, p.objective, p.inclusion_criteria, p.exclusion_criteria
    FROM articles a JOIN projects p ON p.id = a.project_id
    WHERE a.id = ?
");
$stmt->execute([$articleId]);
$art = $stmt->fetch();
if (!$art) jsonResponse(['error' => true, 'message' => 'Artigo não encontrado'], 404);

$prompt = "Você é um assistente de revisão sistemática da literatura (protocolo PRISMA 2020). "
        . "Avalie se o artigo abaixo deve ser INCLUÍDO ou EXCLUÍDO na fase de triagem por título/resumo, "
        . "com base nos critérios do projeto. Responda em português.\n\n"
        . "## Projeto\n{$art['project_title']}\n\n"
        . "## Objetivo da revisão\n" . ($art['objective'] ?: '(não informado)') . "\n\n"
        . "## Critérios de inclusão\n" . ($art['inclusion_criteria'] ?: '(não informados)') . "\n\n"
        . "## Critérios de exclusão\n" . ($art['exclusion_criteria'] ?: '(não informados)') . "\n\n"
        . "## Artigo a avaliar\n"
        . "Título: {$art['title']}\n"
        . "Ano: {$art['year']} · Periódico: {$art['journal']}\n"
        . "Resumo: " . ($art['abstract'] ?: '(resumo indisponível — avalie apenas pelo título)') . "\n\n"
        . "Responda APENAS com um objeto JSON válido, sem texto adicional:\n"
        . "{\n"
        . "  \"decision\": \"include\" | \"exclude\" | \"uncertain\",\n"
        . "  \"confidence\": <inteiro 0-100>,\n"
        . "  \"rationale\": \"<justificativa curta em português>\"\n"
        . "}\n\n"
        . "Use 'uncertain' quando os critérios forem insuficientes para decidir.";

// Calls the API according to the configured provider
if (AI_PROVIDER === 'anthropic') {
    $resp = callAnthropic($prompt);
} elseif (AI_PROVIDER === 'gemini') {
    $resp = callGemini($prompt);
} else {
    $resp = callGroq($prompt);
}

if (isset($resp['error'])) {
    jsonResponse(['error' => true, 'message' => $resp['error']], 502);
}

$suggestion = parseJsonResponse($resp['text']);
if (!$suggestion) {
    jsonResponse(['error' => true, 'message' => 'Resposta da IA em formato inesperado: ' . mb_substr($resp['text'], 0, 200)], 502);
}

// Logs usage to track the daily limit
$pdo->prepare("INSERT INTO ai_usage (user_id) VALUES (?)")->execute([$apiUser['id']]);

jsonResponse([
    'success'    => true,
    'article_id' => $articleId,
    'provider'   => AI_PROVIDER,
    'model'      => AI_MODEL,
    'decision'   => $suggestion['decision'],
    'confidence' => (int) ($suggestion['confidence'] ?? 0),
    'rationale'  => $suggestion['rationale'] ?? '',
]);

// ---------------------------------------------------------------------------

function callGroq(string $prompt): array
{
    $payload = [
        'model'    => AI_MODEL,
        'messages' => [['role' => 'user', 'content' => $prompt]],
        'max_tokens' => 512,
        'temperature' => 0.1,
    ];

    $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . AI_API_KEY,
            'Content-Type: application/json',
        ],
    ]);
    $raw  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($raw === false) return ['error' => 'Falha de conexão com Groq: ' . $err];
    $body = json_decode($raw, true);
    if ($code !== 200) {
        return ['error' => 'Erro Groq ' . $code . ': ' . ($body['error']['message'] ?? $raw)];
    }
    $text = $body['choices'][0]['message']['content'] ?? null;
    if (!$text) return ['error' => 'Resposta vazia do Groq'];
    return ['text' => $text];
}

function callGemini(string $prompt): array
{
    $url     = 'https://generativelanguage.googleapis.com/v1beta/models/' . AI_MODEL . ':generateContent?key=' . AI_API_KEY;
    $payload = ['contents' => [['parts' => [['text' => $prompt]]]]];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    ]);
    $raw  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($raw === false) return ['error' => 'Falha de conexão com Gemini: ' . $err];
    $body = json_decode($raw, true);
    if ($code !== 200) {
        return ['error' => 'Erro Gemini ' . $code . ': ' . ($body['error']['message'] ?? $raw)];
    }
    $text = $body['candidates'][0]['content']['parts'][0]['text'] ?? null;
    if (!$text) return ['error' => 'Resposta vazia do Gemini'];
    return ['text' => $text];
}

function callAnthropic(string $prompt): array
{
    $payload = [
        'model'      => AI_MODEL,
        'max_tokens' => 512,
        'messages'   => [['role' => 'user', 'content' => $prompt]],
    ];

    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_HTTPHEADER     => [
            'x-api-key: ' . AI_API_KEY,
            'anthropic-version: 2023-06-01',
            'Content-Type: application/json',
        ],
    ]);
    $raw  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($raw === false) return ['error' => 'Falha de conexão com Anthropic: ' . $err];
    $body = json_decode($raw, true);
    if ($code !== 200) {
        return ['error' => 'Erro Anthropic ' . $code . ': ' . ($body['error']['message'] ?? $raw)];
    }
    $text = null;
    foreach (($body['content'] ?? []) as $block) {
        if (($block['type'] ?? '') === 'text') { $text = $block['text']; break; }
    }
    if (!$text) return ['error' => 'Resposta vazia da Anthropic'];
    return ['text' => $text];
}

function parseJsonResponse(string $text): ?array
{
    $decoded = json_decode(trim($text), true);
    if (!is_array($decoded)) {
        // Extract the first {...} block if there is extra text
        if (preg_match('/\{[^{}]*"decision"[^{}]*\}/s', $text, $m)) {
            $decoded = json_decode($m[0], true);
        }
    }
    if (!is_array($decoded)) return null;
    if (!in_array($decoded['decision'] ?? '', ['include', 'exclude', 'uncertain'], true)) return null;
    return $decoded;
}
