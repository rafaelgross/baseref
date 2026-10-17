<?php
/**
 * PRISMA-SLR - API: Bibliometria
 * GET /api/bibliometrics.php?project_id=N&chart=TIPO
 *
 * Tipos disponíveis:
 *   publications_year, document_types, top_journals, top_authors,
 *   top_countries, keywords_frequency, citations_distribution,
 *   open_access, top_publishers, keywords_by_year
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/api_guard.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$pdo       = getDB();
$projectId = isset($_GET['project_id']) ? (int) $_GET['project_id'] : null;
$chart     = $_GET['chart'] ?? 'all';
$scope     = $_GET['scope']  ?? 'all'; // 'all' ou 'included'

if (!$projectId) jsonResponse(['error' => true, 'message' => 'project_id obrigatório'], 422);
requireProjectAccess($pdo, $projectId);
$reviewerId = (int) requireApiAuth()['id'];

/**
 * Valida kw_type contra a lista fechada do enum keyword_type.
 */
function kwTypeWhere(string $kwType): string
{
    if (!in_array($kwType, ['author', 'indexed', 'plus'], true)) return '';
    return "AND ak.keyword_type = '$kwType'";
}

// Filtro de escopo (todos os artigos ou só os incluídos por mim)
$scopeWhere = $scope === 'included' ? "AND a.is_duplicate = 0 AND " . personalIncludedExists($reviewerId) : "AND a.is_duplicate = 0";

// Filtro opcional por uma única base/dataset importado (ex: só WOS, só Scopus);
// sem isso, os gráficos combinam todos os datasets do projeto.
$sourceId = isset($_GET['source_id']) ? (int) $_GET['source_id'] : 0;
if ($sourceId) {
    $scopeWhere .= " AND a.source_id = $sourceId";
}

$result = [];

if ($chart === 'all' || $chart === 'publications_year') {
    $stmt = $pdo->prepare("
        SELECT year, COUNT(*) AS total
        FROM articles a
        WHERE project_id = ? $scopeWhere AND year IS NOT NULL AND year > 1900 AND year <= YEAR(NOW())
        GROUP BY year ORDER BY year
    ");
    $stmt->execute([$projectId]);
    $result['publications_year'] = $stmt->fetchAll();
}

if ($chart === 'all' || $chart === 'document_types') {
    $stmt = $pdo->prepare("
        SELECT COALESCE(document_type, 'Não especificado') AS type, COUNT(*) AS total
        FROM articles a
        WHERE project_id = ? $scopeWhere
        GROUP BY document_type ORDER BY total DESC
    ");
    $stmt->execute([$projectId]);
    $result['document_types'] = $stmt->fetchAll();
}

if ($chart === 'all' || $chart === 'top_journals') {
    $limit = (int) ($_GET['limit'] ?? 15);
    $stmt  = $pdo->prepare("
        SELECT COALESCE(journal, 'Sem periódico') AS journal, COUNT(*) AS total
        FROM articles a
        WHERE project_id = ? $scopeWhere AND journal IS NOT NULL AND journal != ''
        GROUP BY journal ORDER BY total DESC LIMIT ?
    ");
    $stmt->execute([$projectId, $limit]);
    $result['top_journals'] = $stmt->fetchAll();
}

if ($chart === 'all' || $chart === 'top_authors') {
    $limit = (int) ($_GET['limit'] ?? 15);
    $stmt  = $pdo->prepare("
        SELECT au.full_name AS author, COUNT(DISTINCT aa.article_id) AS total
        FROM article_authors aa
        JOIN authors au ON au.id = aa.author_id
        JOIN articles a ON a.id = aa.article_id
        WHERE a.project_id = ? $scopeWhere
        GROUP BY au.id ORDER BY total DESC LIMIT ?
    ");
    $stmt->execute([$projectId, $limit]);
    $result['top_authors'] = $stmt->fetchAll();
}

if ($chart === 'all' || $chart === 'top_countries') {
    $limit = (int) ($_GET['limit'] ?? 15);
    $stmt  = $pdo->prepare("
        SELECT country, COUNT(DISTINCT af.article_id) AS total
        FROM article_affiliations af
        JOIN articles a ON a.id = af.article_id
        WHERE a.project_id = ? $scopeWhere AND country IS NOT NULL AND country != ''
        GROUP BY country ORDER BY total DESC LIMIT ?
    ");
    $stmt->execute([$projectId, $limit]);
    $result['top_countries'] = $stmt->fetchAll();
}

if ($chart === 'all' || $chart === 'keywords_frequency') {
    $limit    = (int)    ($_GET['limit']   ?? 30);
    $kwType   = $_GET['kw_type'] ?? 'author'; // 'author', 'plus', 'all'
    if (!in_array($kwType, ['author', 'plus', 'all'], true)) $kwType = 'author';
    $kwWhere  = kwTypeWhere($kwType);
    $stmt     = $pdo->prepare("
        SELECT ak.keyword, COUNT(*) AS total
        FROM article_keywords ak
        JOIN articles a ON a.id = ak.article_id
        WHERE a.project_id = ? $scopeWhere $kwWhere
        GROUP BY ak.keyword ORDER BY total DESC LIMIT ?
    ");
    $stmt->execute([$projectId, $limit]);
    $result['keywords_frequency'] = $stmt->fetchAll();
}

if ($chart === 'all' || $chart === 'citations_distribution') {
    $stmt = $pdo->prepare("
        SELECT cited_by, COUNT(*) AS total
        FROM articles a
        WHERE project_id = ? $scopeWhere AND cited_by IS NOT NULL
        GROUP BY cited_by ORDER BY cited_by
    ");
    $stmt->execute([$projectId]);
    $raw = $stmt->fetchAll();
    // Agrupa em faixas
    $buckets = ['0' => 0, '1-5' => 0, '6-10' => 0, '11-25' => 0, '26-50' => 0, '51-100' => 0, '100+' => 0];
    foreach ($raw as $row) {
        $c = (int) $row['cited_by'];
        if ($c === 0)       $buckets['0']      += $row['total'];
        elseif ($c <= 5)    $buckets['1-5']    += $row['total'];
        elseif ($c <= 10)   $buckets['6-10']   += $row['total'];
        elseif ($c <= 25)   $buckets['11-25']  += $row['total'];
        elseif ($c <= 50)   $buckets['26-50']  += $row['total'];
        elseif ($c <= 100)  $buckets['51-100'] += $row['total'];
        else                $buckets['100+']   += $row['total'];
    }
    $result['citations_distribution'] = array_map(
        fn($k, $v) => ['range' => $k, 'total' => $v],
        array_keys($buckets), $buckets
    );
}

if ($chart === 'all' || $chart === 'open_access') {
    $stmt = $pdo->prepare("
        SELECT
            SUM(CASE WHEN open_access = 1 THEN 1 ELSE 0 END) AS open_access,
            SUM(CASE WHEN open_access = 0 THEN 1 ELSE 0 END) AS closed_access
        FROM articles a
        WHERE project_id = ? $scopeWhere
    ");
    $stmt->execute([$projectId]);
    $result['open_access'] = $stmt->fetch();
}

if ($chart === 'all' || $chart === 'top_publishers') {
    $limit = (int) ($_GET['limit'] ?? 10);
    $stmt  = $pdo->prepare("
        SELECT COALESCE(publisher, 'Não informado') AS publisher, COUNT(*) AS total
        FROM articles a
        WHERE project_id = ? $scopeWhere AND publisher IS NOT NULL AND publisher != ''
        GROUP BY publisher ORDER BY total DESC LIMIT ?
    ");
    $stmt->execute([$projectId, $limit]);
    $result['top_publishers'] = $stmt->fetchAll();
}

if ($chart === 'all' || $chart === 'keywords_by_year') {
    // Top 5 keywords por ano (para gráfico de área empilhada)
    $limit = (int) ($_GET['limit'] ?? 8);

    // Primeiro, pega as top keywords globais
    $stmtTop = $pdo->prepare("
        SELECT ak.keyword
        FROM article_keywords ak
        JOIN articles a ON a.id = ak.article_id
        WHERE a.project_id = ? $scopeWhere AND ak.keyword_type = 'author'
        GROUP BY ak.keyword ORDER BY COUNT(*) DESC LIMIT ?
    ");
    $stmtTop->execute([$projectId, $limit]);
    $topKw = array_column($stmtTop->fetchAll(), 'keyword');

    if (!empty($topKw)) {
        $ph    = implode(',', array_fill(0, count($topKw), '?'));
        $params = [$projectId, ...$topKw];
        $stmt  = $pdo->prepare("
            SELECT a.year, ak.keyword, COUNT(*) AS total
            FROM article_keywords ak
            JOIN articles a ON a.id = ak.article_id
            WHERE a.project_id = ? $scopeWhere AND ak.keyword IN ($ph)
                  AND a.year IS NOT NULL AND a.year > 1990
            GROUP BY a.year, ak.keyword ORDER BY a.year, ak.keyword
        ");
        $stmt->execute($params);
        $result['keywords_by_year'] = [
            'top_keywords' => $topKw,
            'data'         => $stmt->fetchAll(),
        ];
    } else {
        $result['keywords_by_year'] = ['top_keywords' => [], 'data' => []];
    }
}

if ($chart === 'all' || $chart === 'source_types') {
    $stmt = $pdo->prepare("
        SELECT UPPER(COALESCE(source_type,'outros')) AS source_type, COUNT(*) AS total
        FROM articles a
        WHERE project_id = ? $scopeWhere
        GROUP BY source_type ORDER BY total DESC
    ");
    $stmt->execute([$projectId]);
    $result['source_types'] = $stmt->fetchAll();
}

if ($chart === 'all' || $chart === 'top_cited') {
    $limit = (int) ($_GET['limit'] ?? 10);
    $stmt  = $pdo->prepare("
        SELECT a.id, a.title, a.year, a.journal, a.cited_by, a.doi
        FROM articles a
        WHERE a.project_id = ? $scopeWhere AND a.cited_by IS NOT NULL AND a.cited_by > 0
        ORDER BY a.cited_by DESC LIMIT ?
    ");
    $stmt->execute([$projectId, $limit]);
    $top = $stmt->fetchAll();
    // Autores de cada artigo
    if (!empty($top)) {
        $ids = array_column($top, 'id');
        $ph  = implode(',', array_fill(0, count($ids), '?'));
        $stmtA = $pdo->prepare("
            SELECT aa.article_id, GROUP_CONCAT(au.full_name ORDER BY aa.position SEPARATOR '; ') AS authors
            FROM article_authors aa JOIN authors au ON au.id = aa.author_id
            WHERE aa.article_id IN ($ph) GROUP BY aa.article_id
        ");
        $stmtA->execute($ids);
        $authMap = array_column($stmtA->fetchAll(), 'authors', 'article_id');
        foreach ($top as &$t) { $t['authors'] = $authMap[$t['id']] ?? ''; }
    }
    $result['top_cited'] = $top;
}

// =============================================================
// ───── ANÁLISES BIBLIOMÉTRICAS CLÁSSICAS ──────────────────────
// =============================================================

// ── Lei de Lotka: distribuição de produtividade dos autores ──
// Para cada n (artigos por autor), conta quantos autores produziram exatamente n artigos.
// Apresentação apenas descritiva: sem ajuste de modelo nem teste de aderência.
if ($chart === 'all' || $chart === 'lotka') {
    $stmt = $pdo->prepare("
        SELECT n_articles, COUNT(*) AS n_authors FROM (
            SELECT au.id, COUNT(DISTINCT aa.article_id) AS n_articles
            FROM authors au
            JOIN article_authors aa ON aa.author_id = au.id
            JOIN articles a ON a.id = aa.article_id
            WHERE a.project_id = ? $scopeWhere
            GROUP BY au.id
        ) t
        GROUP BY n_articles ORDER BY n_articles
    ");
    $stmt->execute([$projectId]);
    $rows = $stmt->fetchAll();

    $totalAuthors = array_sum(array_column($rows, 'n_authors'));

    // Tamanho da amostra (artigos no mesmo escopo/base dos autores contados)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM articles a WHERE a.project_id = ? $scopeWhere");
    $stmt->execute([$projectId]);
    $totalArticles = (int) $stmt->fetchColumn();

    $result['lotka'] = [
        'distribution'   => $rows,
        'total_authors'  => $totalAuthors,
        'total_articles' => $totalArticles,
        'scope'          => $scope === 'included' ? 'included' : 'all',
        'source_filter'  => $sourceId > 0,
    ];
}

// ── Lei de Bradford: dispersão de periódicos ──────────────────
// Ordena periódicos por nº de artigos e divide em 3 zonas com ~mesmo total de artigos.
if ($chart === 'all' || $chart === 'bradford') {
    $stmt = $pdo->prepare("
        SELECT journal, COUNT(*) AS n
        FROM articles a
        WHERE project_id = ? $scopeWhere AND journal IS NOT NULL AND journal != ''
        GROUP BY journal ORDER BY n DESC
    ");
    $stmt->execute([$projectId]);
    $rawRows = $stmt->fetchAll();

    // Normaliza variantes do mesmo periódico (Scopus vs Web of Science):
    // "Sustainability (Switzerland)" e "SUSTAINABILITY" → mesma chave.
    // Mantém como rótulo a grafia mais frequente (desempate: a mais longa).
    $merged = []; // key => ['n' => int, 'labels' => [grafia => contagem]]
    foreach ($rawRows as $r) {
        $key = mb_strtoupper($r['journal']);
        $key = preg_replace('/\s*\([^)]*\)\s*$/', '', $key); // remove sufixo "(...)"
        $key = preg_replace('/[[:punct:]]/', ' ', $key);     // pontuação → espaço
        $key = trim(preg_replace('/\s+/', ' ', $key));        // colapsa espaços
        if (!isset($merged[$key])) $merged[$key] = ['n' => 0, 'labels' => []];
        $merged[$key]['n'] += (int) $r['n'];
        $merged[$key]['labels'][$r['journal']] = ((int) $r['n']);
    }
    $rows = [];
    foreach ($merged as $m) {
        // rótulo = grafia mais frequente; desempate pela mais longa
        arsort($m['labels']);
        $top = array_key_first($m['labels']);
        foreach ($m['labels'] as $lbl => $c) {
            if ($c === reset($m['labels']) && mb_strlen($lbl) > mb_strlen($top)) $top = $lbl;
        }
        $rows[] = ['journal' => $top, 'n' => $m['n']];
    }
    usort($rows, fn($a, $b) => $b['n'] <=> $a['n']);

    $totalArticles = array_sum(array_column($rows, 'n'));
    $third = $totalArticles / 3;

    // Acumula até cada terço
    $cum = 0; $zone = 1;
    $zones = [1 => [], 2 => [], 3 => []];
    foreach ($rows as $i => $r) {
        $zones[$zone][] = ['rank' => $i + 1, 'journal' => $r['journal'], 'n' => (int)$r['n'], 'cumulative' => $cum + (int)$r['n']];
        $cum += (int)$r['n'];
        if ($zone < 3 && $cum >= $zone * $third) $zone++;
    }

    $result['bradford'] = [
        'total_articles' => $totalArticles,
        'total_journals' => count($rows),
        'zones' => [
            'core'      => ['count' => count($zones[1]), 'articles' => array_sum(array_column($zones[1],'n'))],
            'related'   => ['count' => count($zones[2]), 'articles' => array_sum(array_column($zones[2],'n'))],
            'peripheral'=> ['count' => count($zones[3]), 'articles' => array_sum(array_column($zones[3],'n'))],
        ],
        'series' => array_merge($zones[1], $zones[2], $zones[3]), // para o gráfico de ranking
    ];
}

// ── Produção anual + taxa de crescimento composto (CAGR) ─────
if ($chart === 'all' || $chart === 'annual_growth') {
    $stmt = $pdo->prepare("
        SELECT year, COUNT(*) AS total
        FROM articles a
        WHERE project_id = ? $scopeWhere AND year IS NOT NULL AND year > 1900 AND year <= YEAR(NOW())
        GROUP BY year ORDER BY year
    ");
    $stmt->execute([$projectId]);
    $rows = $stmt->fetchAll();

    $cum = 0;
    foreach ($rows as &$r) {
        $cum += (int)$r['total'];
        $r['cumulative'] = $cum;
    }
    unset($r);

    // CAGR = (Vf / Vi)^(1/n) - 1
    $cagr = null;
    if (count($rows) >= 2) {
        $first = (int)$rows[0]['total'];
        $last  = (int)$rows[count($rows)-1]['total'];
        $n     = (int)$rows[count($rows)-1]['year'] - (int)$rows[0]['year'];
        if ($first > 0 && $n > 0) {
            $cagr = round((pow($last / $first, 1 / $n) - 1) * 100, 2);
        }
    }

    $result['annual_growth'] = [
        'series' => $rows,
        'cagr_percent' => $cagr,
        'total_articles' => $cum,
    ];
}

// ── Word Cloud: keywords e frequências para wordcloud2.js ────
if ($chart === 'all' || $chart === 'word_cloud') {
    $limit  = (int) ($_GET['limit'] ?? 100);
    $kwType = $_GET['kw_type'] ?? 'author';
    if (!in_array($kwType, ['author', 'plus', 'all'], true)) $kwType = 'author';
    $kwWhere = kwTypeWhere($kwType);
    $stmt = $pdo->prepare("
        SELECT ak.keyword, COUNT(*) AS total
        FROM article_keywords ak
        JOIN articles a ON a.id = ak.article_id
        WHERE a.project_id = ? $scopeWhere $kwWhere
        GROUP BY ak.keyword ORDER BY total DESC LIMIT ?
    ");
    $stmt->execute([$projectId, $limit]);
    $result['word_cloud'] = $stmt->fetchAll();
}

// ── Trend Topics: keywords posicionadas pela mediana do ano ──
// Estilo bibliometrix: cada keyword recebe q1, mediana e q3 do ano de publicação.
if ($chart === 'all' || $chart === 'trend_topics') {
    $minFreq = (int) ($_GET['min_freq'] ?? 3);
    $limit   = (int) ($_GET['limit']    ?? 25);

    // Top keywords (autor)
    $stmtTop = $pdo->prepare("
        SELECT ak.keyword, COUNT(*) AS freq
        FROM article_keywords ak
        JOIN articles a ON a.id = ak.article_id
        WHERE a.project_id = ? $scopeWhere AND ak.keyword_type='author'
              AND a.year IS NOT NULL AND a.year > 1990
        GROUP BY ak.keyword HAVING freq >= ?
        ORDER BY freq DESC LIMIT ?
    ");
    $stmtTop->execute([$projectId, $minFreq, $limit]);
    $topKw = $stmtTop->fetchAll();

    $out = [];
    foreach ($topKw as $kw) {
        // Pega anos das ocorrências para calcular quartis
        $stmt = $pdo->prepare("
            SELECT a.year FROM article_keywords ak
            JOIN articles a ON a.id = ak.article_id
            WHERE a.project_id = ? $scopeWhere AND ak.keyword = ? AND a.year IS NOT NULL
            ORDER BY a.year
        ");
        $stmt->execute([$projectId, $kw['keyword']]);
        $years = array_map('intval', array_column($stmt->fetchAll(), 'year'));
        if (empty($years)) continue;

        $n = count($years);
        $median = $years[(int) floor($n / 2)];
        $q1     = $years[(int) floor($n * 0.25)];
        $q3     = $years[(int) floor($n * 0.75)];

        $out[] = [
            'keyword'    => $kw['keyword'],
            'freq'       => (int) $kw['freq'],
            'year_q1'    => $q1,
            'year_median'=> $median,
            'year_q3'    => $q3,
        ];
    }
    // Ordena pela mediana ascendente (timeline)
    usort($out, fn($a,$b) => $a['year_median'] <=> $b['year_median']);
    $result['trend_topics'] = $out;
}

// ── Three-Field Plot (Sankey): Autores → Keywords → Periódicos
if ($chart === 'all' || $chart === 'three_field') {
    $nTop = (int) ($_GET['n_top'] ?? 12);

    // Top autores
    $stmt = $pdo->prepare("
        SELECT au.id, au.full_name FROM authors au
        JOIN article_authors aa ON aa.author_id = au.id
        JOIN articles a ON a.id = aa.article_id
        WHERE a.project_id = ? $scopeWhere
        GROUP BY au.id ORDER BY COUNT(DISTINCT aa.article_id) DESC LIMIT ?
    ");
    $stmt->execute([$projectId, $nTop]);
    $topAuthors = $stmt->fetchAll();
    $authorIds  = array_column($topAuthors, 'id');

    // Top keywords (autor)
    $stmt = $pdo->prepare("
        SELECT ak.keyword FROM article_keywords ak
        JOIN articles a ON a.id = ak.article_id
        WHERE a.project_id = ? $scopeWhere AND ak.keyword_type='author'
        GROUP BY ak.keyword ORDER BY COUNT(*) DESC LIMIT ?
    ");
    $stmt->execute([$projectId, $nTop]);
    $topKw = array_column($stmt->fetchAll(), 'keyword');

    // Top periódicos
    $stmt = $pdo->prepare("
        SELECT journal FROM articles a
        WHERE project_id = ? $scopeWhere AND journal IS NOT NULL AND journal != ''
        GROUP BY journal ORDER BY COUNT(*) DESC LIMIT ?
    ");
    $stmt->execute([$projectId, $nTop]);
    $topJournals = array_column($stmt->fetchAll(), 'journal');

    $linksAK = []; $linksKJ = [];

    if (!empty($authorIds) && !empty($topKw)) {
        $phA = implode(',', array_fill(0, count($authorIds), '?'));
        $phK = implode(',', array_fill(0, count($topKw), '?'));
        $stmt = $pdo->prepare("
            SELECT au.full_name AS author, ak.keyword, COUNT(*) AS weight
            FROM article_authors aa
            JOIN authors au ON au.id = aa.author_id
            JOIN article_keywords ak ON ak.article_id = aa.article_id
            JOIN articles a ON a.id = aa.article_id
            WHERE a.project_id = ? $scopeWhere AND au.id IN ($phA) AND ak.keyword IN ($phK)
            GROUP BY au.full_name, ak.keyword
        ");
        $stmt->execute([$projectId, ...$authorIds, ...$topKw]);
        $linksAK = $stmt->fetchAll();
    }

    if (!empty($topKw) && !empty($topJournals)) {
        $phK = implode(',', array_fill(0, count($topKw), '?'));
        $phJ = implode(',', array_fill(0, count($topJournals), '?'));
        $stmt = $pdo->prepare("
            SELECT ak.keyword, a.journal, COUNT(*) AS weight
            FROM article_keywords ak
            JOIN articles a ON a.id = ak.article_id
            WHERE a.project_id = ? $scopeWhere AND ak.keyword IN ($phK) AND a.journal IN ($phJ)
            GROUP BY ak.keyword, a.journal
        ");
        $stmt->execute([$projectId, ...$topKw, ...$topJournals]);
        $linksKJ = $stmt->fetchAll();
    }

    $result['three_field'] = [
        'authors'  => array_column($topAuthors, 'full_name'),
        'keywords' => $topKw,
        'journals' => $topJournals,
        'links_ak' => $linksAK,
        'links_kj' => $linksKJ,
    ];
}

// ── Citações médias por ano de publicação (TC/Y normalizado) ─
if ($chart === 'all' || $chart === 'citations_per_year') {
    $stmt = $pdo->prepare("
        SELECT year,
               COUNT(*) AS n,
               COALESCE(SUM(cited_by),0) AS total_citations,
               COALESCE(AVG(cited_by),0) AS avg_citations
        FROM articles a
        WHERE project_id = ? $scopeWhere AND year IS NOT NULL AND year > 1900
        GROUP BY year ORDER BY year
    ");
    $stmt->execute([$projectId]);
    $rows = $stmt->fetchAll();
    // TC/Y = total_citations / (current_year - year + 1)
    $cy = (int) date('Y');
    foreach ($rows as &$r) {
        $age = max(1, $cy - (int)$r['year'] + 1);
        $r['tc_per_year']  = round(((float)$r['total_citations']) / $age, 2);
        $r['avg_citations']= round((float)$r['avg_citations'], 2);
    }
    $result['citations_per_year'] = $rows;
}

// Mapa de produção científica por país
if ($chart === 'country_production') {
    // TRIM + strip trailing punctuation + min length 4 to filter out
    // author name fragments that sometimes appear in the country field
    $stmt = $pdo->prepare("
        SELECT
            TRIM(TRAILING '.' FROM TRIM(TRAILING ',' FROM TRIM(country))) AS country,
            COUNT(DISTINCT af.article_id) AS n
        FROM article_affiliations af
        JOIN articles a ON a.id = af.article_id
        WHERE a.project_id = ? $scopeWhere
          AND country IS NOT NULL
          AND LENGTH(TRIM(country)) >= 4
          AND TRIM(country) REGEXP '^[A-Za-zÀ-ÿ \\-\\.()]+$'
        GROUP BY 1
        ORDER BY n DESC
    ");
    $stmt->execute([$projectId]);
    $result['country_production'] = $stmt->fetchAll();
    jsonResponse($result);
}

// Rede de co-autoria
if ($chart === 'coauthorship') {
    $minArticles = (int) ($_GET['min_articles'] ?? 2);

    // Autores com pelo menos min_articles
    $stmt = $pdo->prepare("
        SELECT au.id, au.full_name, COUNT(DISTINCT aa.article_id) AS article_count
        FROM authors au
        JOIN article_authors aa ON aa.author_id = au.id
        JOIN articles a ON a.id = aa.article_id
        WHERE a.project_id = ? $scopeWhere
        GROUP BY au.id HAVING article_count >= ?
        ORDER BY article_count DESC
        LIMIT 100
    ");
    $stmt->execute([$projectId, $minArticles]);
    $nodes = $stmt->fetchAll();
    $nodeIds = array_column($nodes, 'id');

    $edges = [];
    if (count($nodeIds) >= 2) {
        $ph = implode(',', array_fill(0, count($nodeIds), '?'));
        $stmt = $pdo->prepare("
            SELECT aa1.author_id AS source, aa2.author_id AS target, COUNT(*) AS weight
            FROM article_authors aa1
            JOIN article_authors aa2 ON aa1.article_id = aa2.article_id AND aa1.author_id < aa2.author_id
            JOIN articles a ON a.id = aa1.article_id
            WHERE a.project_id = ? $scopeWhere
                  AND aa1.author_id IN ($ph) AND aa2.author_id IN ($ph)
            GROUP BY aa1.author_id, aa2.author_id
            HAVING weight >= 1
            LIMIT 500
        ");
        $stmt->execute([$projectId, ...$nodeIds, ...$nodeIds]);
        $edges = $stmt->fetchAll();
    }

    jsonResponse(['nodes' => $nodes, 'edges' => $edges]);
    return;
}

// Rede de co-ocorrência de palavras-chave
if ($chart === 'keyword_cooccurrence') {
    $minOccurrences = (int) ($_GET['min_occ'] ?? 2);
    $limit = (int) ($_GET['limit'] ?? 50);

    $stmt = $pdo->prepare("
        SELECT ak.keyword, COUNT(DISTINCT ak.article_id) AS freq
        FROM article_keywords ak
        JOIN articles a ON a.id = ak.article_id
        WHERE a.project_id = ? $scopeWhere AND ak.keyword_type = 'author'
        GROUP BY ak.keyword HAVING freq >= ?
        ORDER BY freq DESC LIMIT ?
    ");
    $stmt->execute([$projectId, $minOccurrences, $limit]);
    $nodes = $stmt->fetchAll();
    $topKw = array_column($nodes, 'keyword');

    $edges = [];
    if (count($topKw) >= 2) {
        $ph = implode(',', array_fill(0, count($topKw), '?'));
        $stmt = $pdo->prepare("
            SELECT LEAST(ak1.keyword, ak2.keyword) AS source,
                   GREATEST(ak1.keyword, ak2.keyword) AS target,
                   COUNT(*) AS weight
            FROM article_keywords ak1
            JOIN article_keywords ak2 ON ak1.article_id = ak2.article_id
                AND ak1.keyword < ak2.keyword
            JOIN articles a ON a.id = ak1.article_id
            WHERE a.project_id = ? $scopeWhere
                  AND ak1.keyword IN ($ph) AND ak2.keyword IN ($ph)
                  AND ak1.keyword_type = 'author' AND ak2.keyword_type = 'author'
            GROUP BY source, target
            HAVING weight >= 1
            ORDER BY weight DESC
            LIMIT 300
        ");
        $stmt->execute([$projectId, ...$topKw, ...$topKw]);
        $edges = $stmt->fetchAll();
    }

    jsonResponse(['nodes' => $nodes, 'edges' => $edges]);
    return;
}

// ── Impacto local dos autores: índices h, g e m ───────────────
// Calculados sobre as citações dos artigos DENTRO do corpus (índices "locais",
// como em bibliometrix::Hindex). h: maior h tal que h artigos têm >= h citações;
// g: maior g tal que os g artigos mais citados acumulam >= g² citações;
// m: h dividido pela idade acadêmica do autor no corpus (anos desde a 1ª publicação).
if ($chart === 'all' || $chart === 'author_impact') {
    $limit = (int) ($_GET['limit'] ?? 15);
    $stmt = $pdo->prepare("
        SELECT au.id, au.full_name, a.cited_by, a.year
        FROM article_authors aa
        JOIN authors au ON au.id = aa.author_id
        JOIN articles a ON a.id = aa.article_id
        WHERE a.project_id = ? $scopeWhere
    ");
    $stmt->execute([$projectId]);

    $byAuthor = [];
    foreach ($stmt->fetchAll() as $r) {
        $byAuthor[$r['id']]['name']        = $r['full_name'];
        $byAuthor[$r['id']]['citations'][] = (int) $r['cited_by'];
        if (!empty($r['year'])) {
            $y = (int) $r['year'];
            $byAuthor[$r['id']]['first_year'] = min($byAuthor[$r['id']]['first_year'] ?? $y, $y);
        }
    }

    $currentYear = (int) date('Y');
    $impact = [];
    foreach ($byAuthor as $a) {
        $cits = $a['citations'];
        rsort($cits);
        $metrics = computeImpactIndices($cits);
        $age = isset($a['first_year']) ? max(1, $currentYear - $a['first_year'] + 1) : null;
        $impact[] = [
            'author'          => $a['name'],
            'documents'       => count($cits),
            'total_citations' => array_sum($cits),
            'h_index'         => $metrics['h'],
            'g_index'         => $metrics['g'],
            'm_index'         => $age ? round($metrics['h'] / $age, 2) : null,
            'first_year'      => $a['first_year'] ?? null,
        ];
    }
    usort($impact, fn($x, $y) => [$y['h_index'], $y['total_citations']] <=> [$x['h_index'], $x['total_citations']]);
    $result['author_impact'] = array_slice($impact, 0, $limit);
}

// ── Impacto local dos periódicos: índices h e g ───────────────
if ($chart === 'all' || $chart === 'source_impact') {
    $limit = (int) ($_GET['limit'] ?? 15);
    $stmt = $pdo->prepare("
        SELECT journal, cited_by
        FROM articles a
        WHERE project_id = ? $scopeWhere AND journal IS NOT NULL AND journal != ''
    ");
    $stmt->execute([$projectId]);

    $bySource = [];
    foreach ($stmt->fetchAll() as $r) {
        $bySource[$r['journal']][] = (int) $r['cited_by'];
    }

    $impact = [];
    foreach ($bySource as $journal => $cits) {
        rsort($cits);
        $metrics = computeImpactIndices($cits);
        $impact[] = [
            'journal'         => $journal,
            'documents'       => count($cits),
            'total_citations' => array_sum($cits),
            'h_index'         => $metrics['h'],
            'g_index'         => $metrics['g'],
        ];
    }
    usort($impact, fn($x, $y) => [$y['h_index'], $y['total_citations']] <=> [$x['h_index'], $x['total_citations']]);
    $result['source_impact'] = array_slice($impact, 0, $limit);
}

// ── Mapa Temático (diagrama estratégico de Callon) ────────────
// Clusters de keywords via co-ocorrência normalizada pelo índice de equivalência
// e_ij = c_ij² / (c_i · c_j) (Callon et al., 1991), como em bibliometrix::thematicMap.
// Centralidade = grau de interação do cluster com os demais (relevância do tema);
// Densidade = força dos laços internos (grau de desenvolvimento do tema).
if ($chart === 'thematic_map') {
    $minFreq = (int) ($_GET['min_freq'] ?? 2);
    $limit   = (int) ($_GET['limit'] ?? 50);

    $stmt = $pdo->prepare("
        SELECT ak.keyword, COUNT(DISTINCT ak.article_id) AS freq
        FROM article_keywords ak
        JOIN articles a ON a.id = ak.article_id
        WHERE a.project_id = ? $scopeWhere AND ak.keyword_type = 'author'
        GROUP BY ak.keyword HAVING freq >= ?
        ORDER BY freq DESC LIMIT ?
    ");
    $stmt->execute([$projectId, $minFreq, $limit]);
    $kwRows = $stmt->fetchAll();
    // Normaliza para minúsculas: a collation do MySQL é case-insensitive,
    // mas as comparações de array no PHP não são
    $freq = [];
    foreach ($kwRows as $r) $freq[mb_strtolower($r['keyword'])] = (int) $r['freq'];
    $kws = array_keys($freq);

    $edges = [];
    if (count($kws) >= 2) {
        $ph = implode(',', array_fill(0, count($kws), '?'));
        $stmt = $pdo->prepare("
            SELECT LEAST(ak1.keyword, ak2.keyword) AS k1,
                   GREATEST(ak1.keyword, ak2.keyword) AS k2,
                   COUNT(DISTINCT ak1.article_id) AS w
            FROM article_keywords ak1
            JOIN article_keywords ak2 ON ak1.article_id = ak2.article_id AND ak1.keyword < ak2.keyword
            JOIN articles a ON a.id = ak1.article_id
            WHERE a.project_id = ? $scopeWhere
                  AND ak1.keyword IN ($ph) AND ak2.keyword IN ($ph)
                  AND ak1.keyword_type = 'author' AND ak2.keyword_type = 'author'
            GROUP BY k1, k2
        ");
        $stmt->execute([$projectId, ...$kws, ...$kws]);
        foreach ($stmt->fetchAll() as $e) {
            $k1 = mb_strtolower($e['k1']);
            $k2 = mb_strtolower($e['k2']);
            if (!isset($freq[$k1], $freq[$k2]) || $k1 === $k2) continue;
            // Índice de equivalência: normaliza a co-ocorrência pelas frequências marginais
            $eq = ($e['w'] ** 2) / ($freq[$k1] * $freq[$k2]);
            $edges[] = ['k1' => $k1, 'k2' => $k2, 'eq' => $eq];
        }
    }

    // Clusterização por propagação de rótulos (determinística: ordem por frequência)
    $label = array_combine($kws, $kws);
    $neighbors = [];
    foreach ($edges as $e) {
        $neighbors[$e['k1']][$e['k2']] = $e['eq'];
        $neighbors[$e['k2']][$e['k1']] = $e['eq'];
    }
    for ($pass = 0; $pass < 10; $pass++) {
        $changed = false;
        foreach ($kws as $kw) {
            if (empty($neighbors[$kw])) continue;
            $score = [];
            foreach ($neighbors[$kw] as $nb => $w) {
                $score[$label[$nb]] = ($score[$label[$nb]] ?? 0) + $w;
            }
            arsort($score);
            $best = array_key_first($score);
            if ($best !== $label[$kw]) { $label[$kw] = $best; $changed = true; }
        }
        if (!$changed) break;
    }

    // Métricas de Callon por cluster
    $clusters = [];
    foreach ($kws as $kw) $clusters[$label[$kw]]['keywords'][] = $kw;
    $out = [];
    foreach ($clusters as $cid => $c) {
        $members  = array_flip($c['keywords']);
        $internal = 0.0; $external = 0.0;
        foreach ($edges as $e) {
            $in1 = isset($members[$e['k1']]);
            $in2 = isset($members[$e['k2']]);
            if ($in1 && $in2)      { $internal += $e['eq']; }
            elseif ($in1 || $in2)  { $external += $e['eq']; }
        }
        // Nomeia o cluster pela keyword mais frequente
        usort($c['keywords'], fn($a, $b) => $freq[$b] <=> $freq[$a]);
        $out[] = [
            'name'       => $c['keywords'][0],
            'keywords'   => array_slice($c['keywords'], 0, 8),
            'n_keywords' => count($c['keywords']),
            'occurrences'=> array_sum(array_map(fn($k) => $freq[$k], $c['keywords'])),
            'centrality' => round(10 * $external, 4),
            'density'    => round(100 * $internal / max(1, count($c['keywords'])), 4),
        ];
    }
    // Apenas clusters com 2+ keywords são temas; isolados viram ruído
    $out = array_values(array_filter($out, fn($c) => $c['n_keywords'] >= 2));
    jsonResponse(['thematic_map' => $out]);
}

// ── 1. Top Instituições ───────────────────────────────────────
if ($chart === 'top_institutions') {
    try {
        $limit = min((int)($_GET['limit'] ?? 20), 50);
        $stmt = $pdo->prepare("
            SELECT
                TRIM(institution) AS institution,
                COUNT(DISTINCT af.article_id) AS n_articles
            FROM article_affiliations af
            JOIN articles a ON a.id = af.article_id
            WHERE a.project_id = ? $scopeWhere
              AND institution IS NOT NULL AND TRIM(institution) != ''
              AND LENGTH(TRIM(institution)) > 10
            GROUP BY TRIM(institution)
            ORDER BY n_articles DESC
            LIMIT 300
        ");
        $stmt->execute([$projectId]);
        $rawRows = $stmt->fetchAll();

        $merged = [];
        foreach ($rawRows as $r) {
            $name = extractInstitutionName($r['institution']);
            if (!$name) continue;
            if (!isset($merged[$name])) {
                $merged[$name] = ['institution' => $name, 'n_articles' => 0];
            }
            $merged[$name]['n_articles'] += (int)$r['n_articles'];
        }
        usort($merged, fn($a, $b) => $b['n_articles'] - $a['n_articles']);
        jsonResponse(['top_institutions' => array_slice(array_values($merged), 0, $limit)]);
    } catch (Throwable $e) {
        jsonResponse(['top_institutions' => [], 'debug' => $e->getMessage()]);
    }
}

function extractInstitutionName(string $aff): string
{
    $parts = array_map('trim', explode(',', $aff));
    $parts = array_filter($parts, fn($p) => $p !== '');
    $parts = array_values($parts);
    if (!$parts) return '';

    // If the string looks like "Lastname, Firstname" (≤2 parts, no digits, no known keywords), skip
    if (count($parts) <= 2 && !preg_match('/\d/', $aff)) {
        $words = explode(' ', trim($parts[0]));
        if (count($words) <= 2) return ''; // likely a person name
    }

    $kw1 = ['university', 'universidad', 'université', 'universidade', 'universiti',
            'polytechnic', 'politécnico', 'academy', 'hospital'];
    $kw2 = ['institute', 'instituto', 'institution', 'laboratory', 'laboratorio',
            'labs', 'school', 'escola', 'college', 'faculté', 'technology',
            'research center', 'research centre', 'federation'];
    $subunit = '/^(Department|School of|Faculty of|College of|Division of|Center for|'
              .'Centre for|Laboratory of|Institute of|Group of|Section of)\b/i';

    // First pass: non-sub-unit parts with strong keywords (university/polytechnic/academy/hospital)
    foreach ($parts as $p) {
        if (preg_match($subunit, $p)) continue;
        $lower = mb_strtolower($p);
        foreach ($kw1 as $k) {
            if (str_contains($lower, $k)) return $p;
        }
    }
    // Second pass: non-sub-unit parts with weaker institution keywords
    foreach ($parts as $p) {
        if (preg_match($subunit, $p)) continue;
        $lower = mb_strtolower($p);
        foreach ($kw2 as $k) {
            if (str_contains($lower, $k)) return $p;
        }
    }
    // Third pass: any part with any institution keyword (allow sub-units)
    foreach ($parts as $p) {
        $lower = mb_strtolower($p);
        foreach (array_merge($kw1, $kw2) as $k) {
            if (str_contains($lower, $k)) return $p;
        }
    }
    // Fallback: return first non-trivial segment (skip city-only or numeric segments)
    foreach ($parts as $p) {
        if (preg_match('/\d/', $p)) continue; // skip postal codes
        if (strlen($p) < 6)       continue; // skip short segments
        return $p;
    }
    return $parts[0] ?? '';
}

// ── 2. MCP vs SCP por País ────────────────────────────────────
// SCP = artigo só tem autores de 1 país | MCP = ≥ 2 países
if ($chart === 'mcp_scp') {
    $stmt = $pdo->prepare("
        SELECT
            c.country,
            COUNT(DISTINCT c.article_id) AS total,
            SUM(c.is_mcp) AS mcp,
            COUNT(DISTINCT c.article_id) - SUM(c.is_mcp) AS scp
        FROM (
            SELECT
                af.article_id,
                TRIM(TRAILING '.' FROM TRIM(af.country)) AS country,
                (COUNT(DISTINCT TRIM(TRAILING '.' FROM TRIM(af2.country))) > 1) AS is_mcp
            FROM article_affiliations af
            JOIN article_affiliations af2 ON af2.article_id = af.article_id
            JOIN articles a ON a.id = af.article_id
            WHERE a.project_id = ? $scopeWhere
              AND af.country IS NOT NULL AND LENGTH(TRIM(af.country)) >= 4
              AND af2.country IS NOT NULL AND LENGTH(TRIM(af2.country)) >= 4
              AND TRIM(af.country) REGEXP '^[A-Za-zÀ-ÿ \\-\\.()]+$'
            GROUP BY af.article_id, TRIM(TRAILING '.' FROM TRIM(af.country))
        ) c
        GROUP BY c.country
        HAVING total >= 1
        ORDER BY total DESC
        LIMIT 20
    ");
    $stmt->execute([$projectId]);
    $rows = $stmt->fetchAll();
    // índice de colaboração = média de países por artigo
    $stmt2 = $pdo->prepare("
        SELECT AVG(cnt) AS ci FROM (
            SELECT af.article_id, COUNT(DISTINCT TRIM(TRAILING '.' FROM TRIM(af.country))) AS cnt
            FROM article_affiliations af
            JOIN articles a ON a.id = af.article_id
            WHERE a.project_id = ? $scopeWhere AND country IS NOT NULL AND LENGTH(TRIM(country)) >= 4
            GROUP BY af.article_id
        ) t
    ");
    $stmt2->execute([$projectId]);
    $ci = round((float)($stmt2->fetch()['ci'] ?? 1), 3);
    jsonResponse(['mcp_scp' => $rows, 'collaboration_index' => $ci]);
}

// ── 3. Produção dos Autores ao Longo do Tempo ─────────────────
if ($chart === 'author_over_time') {
    $topN = min((int)($_GET['top'] ?? 10), 25);
    // Top N autores por nº de artigos
    $stmt = $pdo->prepare("
        SELECT au.id, au.full_name, COUNT(DISTINCT aa.article_id) AS total
        FROM authors au
        JOIN article_authors aa ON aa.author_id = au.id
        JOIN articles a ON a.id = aa.article_id
        WHERE a.project_id = ? $scopeWhere
        GROUP BY au.id ORDER BY total DESC LIMIT ?
    ");
    $stmt->execute([$projectId, $topN]);
    $topAuthors = $stmt->fetchAll();
    if (!$topAuthors) { jsonResponse(['author_over_time' => []]); }

    $ids = array_column($topAuthors, 'id');
    $ph  = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("
        SELECT au.full_name, a.year, COUNT(DISTINCT a.id) AS n
        FROM article_authors aa
        JOIN articles a  ON a.id  = aa.article_id
        JOIN authors  au ON au.id = aa.author_id
        WHERE a.project_id = ? $scopeWhere AND aa.author_id IN ($ph)
          AND a.year IS NOT NULL AND a.year > 1900
        GROUP BY au.full_name, a.year
        ORDER BY a.year, au.full_name
    ");
    $stmt->execute([$projectId, ...$ids]);
    $rows = $stmt->fetchAll();
    jsonResponse(['author_over_time' => $rows, 'authors' => array_column($topAuthors, 'full_name')]);
}

// ── 4. Rede de Colaboração entre Países ───────────────────────
if ($chart === 'country_collab') {
    $minWeight = (int)($_GET['min_weight'] ?? 1);
    // Para cada artigo, pega pares de países distintos dos autores
    $stmt = $pdo->prepare("
        SELECT
            LEAST(TRIM(af1.country), TRIM(af2.country)) AS c1,
            GREATEST(TRIM(af1.country), TRIM(af2.country)) AS c2,
            COUNT(DISTINCT af1.article_id) AS weight
        FROM article_affiliations af1
        JOIN article_affiliations af2
             ON  af1.article_id = af2.article_id
             AND TRIM(af1.country) < TRIM(af2.country)
        JOIN articles a ON a.id = af1.article_id
        WHERE a.project_id = ? $scopeWhere
          AND af1.country IS NOT NULL AND LENGTH(TRIM(af1.country)) >= 4
          AND af2.country IS NOT NULL AND LENGTH(TRIM(af2.country)) >= 4
          AND TRIM(af1.country) REGEXP '^[A-Za-zÀ-ÿ \\-\\.()]+$'
          AND TRIM(af2.country) REGEXP '^[A-Za-zÀ-ÿ \\-\\.()]+$'
        GROUP BY c1, c2 HAVING weight >= ?
        ORDER BY weight DESC LIMIT 100
    ");
    $stmt->execute([$projectId, $minWeight]);
    $edges = $stmt->fetchAll();

    // Produção por país (para tamanho dos nós) — mínimo 2 artigos para filtrar dados ruins
    $stmt2 = $pdo->prepare("
        SELECT TRIM(TRAILING '.' FROM TRIM(country)) AS country,
               COUNT(DISTINCT af.article_id) AS n
        FROM article_affiliations af
        JOIN articles a ON a.id = af.article_id
        WHERE a.project_id = ? $scopeWhere
          AND country IS NOT NULL AND LENGTH(TRIM(country)) >= 4
          AND TRIM(country) REGEXP '^[A-Za-zÀ-ÿ \\-\\.()]+$'
        GROUP BY 1 HAVING n >= 2 ORDER BY n DESC
    ");
    $stmt2->execute([$projectId]);
    $nodes = $stmt2->fetchAll();
    jsonResponse(['nodes' => $nodes, 'edges' => $edges]);
}

// ── 5. Evolução Temática (mapa de Callon por períodos) ────────
if ($chart === 'thematic_evolution') { try {
    $minFreq = (int)($_GET['min_freq'] ?? 2);
    $periods = (int)($_GET['periods'] ?? 2); // 2 ou 3 períodos
    $periods = max(2, min(3, $periods));

    // Determina os anos disponíveis e divide em períodos iguais
    $stmt = $pdo->prepare("
        SELECT MIN(a.year) AS y_min, MAX(a.year) AS y_max
        FROM articles a WHERE a.project_id = ? $scopeWhere AND a.year > 1900
    ");
    $stmt->execute([$projectId]);
    $yRange = $stmt->fetch();
    if (!$yRange || !$yRange['y_min']) { jsonResponse(['thematic_evolution' => []]); }

    $yMin = (int)$yRange['y_min']; $yMax = (int)$yRange['y_max'];
    $span = max(1, $yMax - $yMin + 1);
    $step = ceil($span / $periods);
    $cuts = [];
    for ($i = 0; $i < $periods; $i++) {
        $cuts[] = ['from' => $yMin + $i * $step, 'to' => min($yMax, $yMin + ($i + 1) * $step - 1)];
    }
    $cuts[count($cuts) - 1]['to'] = $yMax; // garante que o último cobre até yMax

    $result_periods = [];
    foreach ($cuts as $cut) {
        $extraWhere = "AND a.year BETWEEN {$cut['from']} AND {$cut['to']}";

        $stmt = $pdo->prepare("
            SELECT ak.keyword, COUNT(DISTINCT ak.article_id) AS freq
            FROM article_keywords ak
            JOIN articles a ON a.id = ak.article_id
            WHERE a.project_id = ? $scopeWhere $extraWhere AND ak.keyword_type = 'author'
            GROUP BY ak.keyword HAVING freq >= ?
            ORDER BY freq DESC LIMIT 40
        ");
        $stmt->execute([$projectId, $minFreq]);
        $kwRows = $stmt->fetchAll();
        $freq = [];
        foreach ($kwRows as $r) $freq[mb_strtolower($r['keyword'])] = (int)$r['freq'];
        $kws = array_keys($freq);

        $edges = [];
        if (count($kws) >= 2) {
            $ph = implode(',', array_fill(0, count($kws), '?'));
            // Use LOWER() to match case-insensitively; $kws are already lowercase
            $stmt2 = $pdo->prepare("
                SELECT LOWER(LEAST(ak1.keyword,ak2.keyword)) AS k1,
                       LOWER(GREATEST(ak1.keyword,ak2.keyword)) AS k2,
                       COUNT(DISTINCT ak1.article_id) AS w
                FROM article_keywords ak1
                JOIN article_keywords ak2 ON ak1.article_id=ak2.article_id
                     AND LOWER(ak1.keyword)<LOWER(ak2.keyword)
                JOIN articles a ON a.id=ak1.article_id
                WHERE a.project_id=? $scopeWhere $extraWhere
                  AND LOWER(ak1.keyword) IN ($ph) AND LOWER(ak2.keyword) IN ($ph)
                  AND ak1.keyword_type='author' AND ak2.keyword_type='author'
                GROUP BY k1,k2
            ");
            $stmt2->execute([$projectId, ...$kws, ...$kws]);
            foreach ($stmt2->fetchAll() as $e) {
                $k1=mb_strtolower($e['k1']); $k2=mb_strtolower($e['k2']);
                if (!isset($freq[$k1],$freq[$k2])||$k1===$k2) continue;
                $eq = ($e['w']**2)/($freq[$k1]*$freq[$k2]);
                $edges[] = ['k1'=>$k1,'k2'=>$k2,'eq'=>$eq];
            }
        }

        // Mesma clusterização do thematic_map
        $label = array_combine($kws,$kws);
        $neighbors = [];
        foreach ($edges as $e) {
            $neighbors[$e['k1']][$e['k2']] = $e['eq'];
            $neighbors[$e['k2']][$e['k1']] = $e['eq'];
        }
        for ($pass=0;$pass<10;$pass++) {
            $changed=false;
            foreach ($kws as $kw) {
                if (empty($neighbors[$kw])) continue;
                $score=[];
                foreach ($neighbors[$kw] as $nb=>$w) $score[$label[$nb]]=($score[$label[$nb]]??0)+$w;
                arsort($score); $best=array_key_first($score);
                if ($best!==$label[$kw]) { $label[$kw]=$best; $changed=true; }
            }
            if (!$changed) break;
        }
        $clusters=[];
        foreach ($kws as $kw) $clusters[$label[$kw]]['keywords'][]=$kw;
        $out=[];
        foreach ($clusters as $c) {
            $members=array_flip($c['keywords']);
            $internal=0.0; $external=0.0;
            foreach ($edges as $e) {
                $in1=isset($members[$e['k1']]); $in2=isset($members[$e['k2']]);
                if ($in1&&$in2) $internal+=$e['eq'];
                elseif ($in1||$in2) $external+=$e['eq'];
            }
            usort($c['keywords'],fn($a,$b)=>$freq[$b]<=>$freq[$a]);
            $out[]=[
                'name'       =>$c['keywords'][0],
                'keywords'   =>array_slice($c['keywords'],0,6),
                'n_keywords' =>count($c['keywords']),
                'occurrences'=>array_sum(array_map(fn($k)=>$freq[$k],$c['keywords'])),
                'centrality' =>round(10*$external,4),
                'density'    =>round(100*$internal/max(1,count($c['keywords'])),4),
            ];
        }
        $out=array_values(array_filter($out,fn($c)=>$c['n_keywords']>=2));
        $result_periods[]=['period'=>"{$cut['from']}–{$cut['to']}","clusters"=>$out];
    }
    jsonResponse(['thematic_evolution'=>$result_periods]);
} catch (Throwable $e) {
    jsonResponse(['thematic_evolution'=>[], 'debug'=>$e->getMessage()]);
} }

/**
 * Índices h e g a partir de um vetor de citações em ordem decrescente.
 */
function computeImpactIndices(array $citationsDesc): array
{
    $h = 0;
    foreach ($citationsDesc as $i => $c) {
        if ($c >= $i + 1) $h = $i + 1; else break;
    }
    $g = 0; $cum = 0;
    foreach ($citationsDesc as $i => $c) {
        $cum += $c;
        if ($cum >= ($i + 1) ** 2) $g = $i + 1;
    }
    return ['h' => $h, 'g' => $g];
}

jsonResponse($result);
