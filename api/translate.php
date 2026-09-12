<?php
/**
 * PRISMA-SLR - Translation proxy (server-side to avoid CORS)
 *
 * Primary source: MyMemory (api.mymemory.translated.net) — a free API built
 * for translating standalone snippets of text, no key required, with a
 * documented limit (raised via the "de" parameter — a contact email — for
 * the service's "polite mode"). Fallback: unofficial Google Translate
 * (client=gtx), used only if MyMemory fails — this endpoint is undocumented
 * and can be IP-blocked under heavy use, which is why it is no longer the
 * primary source.
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/api_guard.php';

header('Content-Type: application/json; charset=utf-8');

requireApiAuth();

const TRANSLATE_CONTACT_EMAIL = 'rafaelgross123@gmail.com';

$text = $_GET['text'] ?? '';
if (empty(trim($text))) {
    echo json_encode(['error' => true, 'message' => 'Texto vazio']);
    exit;
}

// Target language — defaults to pt-BR (original use case: translating abstracts for reading).
// Restricted to a closed list so arbitrary input is never passed through to the external API.
$allowedTargets = ['pt-BR', 'en', 'es'];
$targetLang     = $_GET['tl'] ?? 'pt-BR';
if (!in_array($targetLang, $allowedTargets, true)) $targetLang = 'pt-BR';

// Source language — if the caller already knows it (e.g. exclusion reasons,
// always in PT), it can be passed via "sl" to skip the heuristic detection below.
$allowedSources = ['en', 'pt-BR', 'es', 'fr'];
$sourceLang     = $_GET['sl'] ?? null;
if ($sourceLang !== null && !in_array($sourceLang, $allowedSources, true)) $sourceLang = null;

$text = mb_substr(trim($text), 0, 3000);

if ($sourceLang === null) {
    $sourceLang = detectSourceLang($text);
}

// Source and target are the same (e.g. an inconclusive heuristic that lands
// on the target language) — nothing to translate, and some providers reject that pair.
if ($sourceLang === $targetLang) {
    echo json_encode([
        'error' => false, 'translated' => $text, 'chunks' => 0, 'source_lang' => $sourceLang,
    ]);
    exit;
}

/**
 * Lightweight language detection by counting common function words.
 * Not perfect, but handles the real case: academic abstracts are almost
 * always in English, sometimes in Portuguese/Spanish/French.
 */
function detectSourceLang(string $text): string
{
    $t = ' ' . mb_strtolower($text) . ' ';
    $markers = [
        'en'    => [' the ', ' and ', ' this ', ' study ', ' with ', ' were ', ' that ', ' which ', ' have ', ' results '],
        'pt-BR' => [' os ', ' as ', ' este ', ' estudo ', ' com ', ' foram ', ' não ', ' para ', ' uma ', ' resultados '],
        'es'    => [' los ', ' las ', ' este ', ' estudio ', ' con ', ' fueron ', ' pero ', ' para ', ' una ', ' resultados '],
        'fr'    => [' les ', ' cette ', ' étude ', ' avec ', ' étaient ', ' pour ', ' mais ', ' dans ', ' été ', ' résultats '],
    ];
    $scores = array_fill_keys(array_keys($markers), 0);
    foreach ($markers as $lang => $words) {
        foreach ($words as $w) {
            $scores[$lang] += substr_count($t, $w);
        }
    }

    // Short texts (e.g. exclusion reasons) rarely contain these full stopwords
    // — use characteristic accented letters as a second signal, cheaper than
    // calling an external API just to detect the language.
    if (preg_match('/[ãõç]/u', $t))                        $scores['pt-BR'] += 2;
    if (preg_match('/[ñ¿¡]/u', $t))                        $scores['es']    += 2;
    if (preg_match('/[àâêîôûœ]/u', $t))                     $scores['fr']    += 2;

    arsort($scores);
    $best = array_key_first($scores);
    return $scores[$best] > 0 ? $best : 'en';
}

/**
 * MyMemory — primary source. Returns null (does not throw) on any problem,
 * so it can fall back to Google without interrupting the request.
 */
function translateChunkMyMemory(string $chunk, string $sourceLang, string $targetLang): ?string
{
    $url = 'https://api.mymemory.translated.net/get?' . http_build_query([
        'q'        => $chunk,
        'langpair' => "{$sourceLang}|{$targetLang}",
        'de'       => TRANSLATE_CONTACT_EMAIL,
    ]);

    $ctx = stream_context_create([
        'http' => ['timeout' => 12, 'ignore_errors' => true, 'header' => "User-Agent: Mozilla/5.0\r\n"],
    ]);
    $response = @file_get_contents($url, false, $ctx);
    if ($response === false) return null;

    $data = json_decode($response, true);
    if (!$data || (int) ($data['responseStatus'] ?? 200) !== 200) return null;

    $translated = $data['responseData']['translatedText'] ?? null;
    if (!$translated) return null;
    // MyMemory returns error messages as 200 OK text (e.g. length limit)
    if (preg_match('/QUERY LENGTH LIMIT|INVALID (SOURCE|TARGET) LANGUAGE|MYMEMORY WARNING/i', $translated)) {
        return null;
    }
    return $translated;
}

/**
 * Unofficial Google Translate — fallback, used only if MyMemory fails.
 */
function translateChunkGoogle(string $chunk, string $targetLang): ?string
{
    $googleTarget = $targetLang === 'pt-BR' ? 'pt' : $targetLang;
    $encoded = urlencode($chunk);
    $url     = "https://translate.googleapis.com/translate_a/single?client=gtx&sl=auto&tl={$googleTarget}&dt=t&q={$encoded}";

    $ctx = stream_context_create([
        'http' => ['timeout' => 12, 'ignore_errors' => true, 'header' => "User-Agent: Mozilla/5.0\r\n"],
    ]);
    $response = @file_get_contents($url, false, $ctx);
    if ($response === false) return null;

    $data = json_decode($response, true);
    if (!$data || !isset($data[0])) return null;

    $result = '';
    foreach ($data[0] as $segment) {
        if (isset($segment[0])) $result .= $segment[0];
    }
    return $result ?: null;
}

function translateChunk(string $chunk, string $sourceLang, string $targetLang): string
{
    $result = translateChunkMyMemory($chunk, $sourceLang, $targetLang);
    if ($result !== null) return $result;

    $result = translateChunkGoogle($chunk, $targetLang);
    if ($result !== null) return $result;

    throw new RuntimeException('Nenhum serviço de tradução disponível no momento');
}

/**
 * Splits the text into chunks, breaking at sentence end or whitespace.
 * 450-character limit: MyMemory rejects texts above 500 chars/request.
 */
function splitText(string $text, int $maxLen = 450): array {
    $chunks = [];
    while (mb_strlen($text) > 0) {
        if (mb_strlen($text) <= $maxLen) {
            $chunks[] = $text;
            break;
        }
        $slice = mb_substr($text, 0, $maxLen);
        $cut   = max(
            mb_strrpos($slice, '. '),
            mb_strrpos($slice, '! '),
            mb_strrpos($slice, '? ')
        );
        if ($cut === false || $cut < 50) {
            $cut = mb_strrpos($slice, ' ');
        }
        if ($cut === false || $cut < 10) {
            $cut = $maxLen;
        } else {
            $cut += 1;
        }
        $chunks[] = mb_substr($text, 0, $cut);
        $text     = ltrim(mb_substr($text, $cut));
    }
    return $chunks;
}

// --- Main ---
$chunks     = splitText($text);
$translated = [];

foreach ($chunks as $chunk) {
    try {
        $translated[] = translateChunk($chunk, $sourceLang, $targetLang);
        if (count($chunks) > 1) {
            usleep(150000); // 0.15s between requests, courtesy delay for the API
        }
    } catch (RuntimeException $e) {
        echo json_encode(['error' => true, 'message' => $e->getMessage()]);
        exit;
    }
}

echo json_encode([
    'error'      => false,
    'translated' => implode(' ', $translated),
    'chunks'     => count($chunks),
    'source_lang'=> $sourceLang,
]);
