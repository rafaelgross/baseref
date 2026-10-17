<?php
/**
 * PRISMA-SLR - Proxy de Tradução (server-side para evitar CORS)
 *
 * Serviço único: MyMemory (api.mymemory.translated.net) — API pública e documentada
 * para tradução de textos avulsos, sem chave, com limite documentado (aumentado
 * via parâmetro "de" — e-mail de contato — para o "modo cortês" do serviço).
 * Não há mais fallback para o endpoint não oficial do Google Translate: se a
 * MyMemory falhar, a requisição devolve erro e o texto original continua visível.
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

// Idioma de destino — padrão pt-BR (uso original: traduzir resumos para leitura).
// Restrito a uma lista fechada para não repassar entrada arbitrária à API externa.
$allowedTargets = ['pt-BR', 'en', 'es'];
$targetLang     = $_GET['tl'] ?? 'pt-BR';
if (!in_array($targetLang, $allowedTargets, true)) $targetLang = 'pt-BR';

// Idioma de origem — se o chamador já sabe (ex: motivos de exclusão, sempre em
// PT), pode informar via "sl" e evita a detecção heurística abaixo.
$allowedSources = ['en', 'pt-BR', 'es', 'fr'];
$sourceLang     = $_GET['sl'] ?? null;
if ($sourceLang !== null && !in_array($sourceLang, $allowedSources, true)) $sourceLang = null;

$text = mb_substr(trim($text), 0, 3000);

if ($sourceLang === null) {
    $sourceLang = detectSourceLang($text);
}

// Origem e destino iguais (ex: heurística inconclusiva caindo no mesmo idioma
// do alvo) — nada a traduzir, e alguns provedores rejeitam esse par.
if ($sourceLang === $targetLang) {
    echo json_encode([
        'error' => false, 'translated' => $text, 'chunks' => 0, 'source_lang' => $sourceLang,
    ]);
    exit;
}

/**
 * Detecção leve de idioma por contagem de palavras funcionais comuns.
 * Não é perfeita, mas resolve o caso real: resumos acadêmicos quase sempre
 * em inglês, às vezes em português/espanhol/francês.
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

    // Textos curtos (ex: motivos de exclusão) raramente contêm essas stopwords
    // inteiras — usa acentuação característica como segundo sinal, mais barato
    // que chamar uma API externa só para detectar o idioma.
    if (preg_match('/[ãõç]/u', $t))                        $scores['pt-BR'] += 2;
    if (preg_match('/[ñ¿¡]/u', $t))                        $scores['es']    += 2;
    if (preg_match('/[àâêîôûœ]/u', $t))                     $scores['fr']    += 2;

    arsort($scores);
    $best = array_key_first($scores);
    return $scores[$best] > 0 ? $best : 'en';
}

/**
 * MyMemory. Retorna null (não lança) em qualquer problema; quem chama decide o erro.
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
    // MyMemory devolve mensagens de erro como texto 200 (ex: limite de tamanho)
    if (preg_match('/QUERY LENGTH LIMIT|INVALID (SOURCE|TARGET) LANGUAGE|MYMEMORY WARNING/i', $translated)) {
        return null;
    }
    return $translated;
}

function translateChunk(string $chunk, string $sourceLang, string $targetLang): string
{
    $result = translateChunkMyMemory($chunk, $sourceLang, $targetLang);
    if ($result !== null) return $result;

    throw new RuntimeException('Serviço de tradução (MyMemory) indisponível no momento. Tente novamente em instantes.');
}

/**
 * Divide o texto em blocos quebrando em fim de frase ou espaço.
 * Limite de 450 caracteres: a MyMemory rejeita textos acima de 500/requisição.
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
            usleep(150000); // 0.15s entre requisições, cortesia com a API
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
