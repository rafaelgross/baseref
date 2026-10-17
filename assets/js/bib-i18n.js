/**
 * PRISMA-SLR — bib-i18n.js
 *
 * Tradução dos textos DESENHADOS DENTRO dos gráficos (canvas/SVG) da página
 * de bibliometria.
 *
 * Motivo: o widget gtranslate.io traduz o DOM, mas texto pintado em <canvas>
 * (Chart.js) nunca é alcançado por ele, e conteúdo injetado depois da carga
 * (tabelas, empty states) é traduzido de forma inconsistente. Resultado: com o
 * site em inglês, os títulos e eixos das figuras continuavam em português —
 * inutilizando a figura para submissão em periódico internacional.
 *
 * Uso:  bt('lotka_title', { slope: 2.31, se: 0.12, n: 880 })
 * Idioma corrente: window.PRISMA_LANG ('pt' | 'en' | 'es'), definido no index.php.
 */
'use strict';

const BIB_I18N = {

  // ─────────────────────────────────────────────────────────── PORTUGUÊS ────
  pt: {
    // Genéricos
    articles:              'Artigos',
    articles_log:          'Artigos (escala log)',
    occurrences:           'Ocorrências',
    publications:          'Publicações',
    trend:                 'Tendência',
    citations:             'Citações',
    docs:                  'Docs',
    author:                'Autor',
    journal:               'Periódico',
    title_col:             'Título',
    authors_col:           'Autores',
    year:                  'Ano',
    keywords_col:          'Keywords',
    journals_col:          'Periódicos',
    error:                 'Erro',
    n_articles:            '{n} artigo(s)',
    unit_article:          ' artigo',
    unit_articles:         ' artigos',

    // Lei de Lotka
    lotka_observed:        'Autores (N={n})',
    lotka_title:           'Distribuição da produtividade dos autores',
    lotka_sample_all:      'Todos os registros sem duplicatas: {a} artigos, {n} autores',
    lotka_sample_included: 'Apenas estudos incluídos: {a} artigos, {n} autores',
    lotka_sample_db:       '(somente a base selecionada)',
    lotka_title_short:     'Produtividade dos autores',
    lotka_x:               'Artigos por autor',
    lotka_y:               'Nº de autores',

    // Lei de Bradford
    bradford_title:        'Lei de Bradford — Núcleo: {core} periódicos · {total} periódicos totais',
    bradford_x:            'Artigos publicados',
    zone_core:             'Núcleo',
    zone_related:          'Intermediária',
    zone_periphery:        'Periferia',
    bradford_tooltip:      '{value} artigos · zona: {zone}',

    // Produção anual
    annual_title:          'Produção Científica Anual — N={n} artigos',
    annual_title_cagr:     'Produção Científica Anual — N={n} artigos · CAGR ≈ {cagr}%/ano',
    annual_in_year:        'Publicações no ano',
    annual_cumulative:     'Acumulado',

    // Word cloud
    wordcloud_missing:     'WordCloud2.js não carregado',
    wordcloud_empty:       'Sem keywords suficientes.',

    // Trend topics
    trend_title:           'Trend Topics — mediana e amplitude (Q1–Q3) do ano de publicação por keyword',
    trend_x:               'Ano de publicação (mediana)',
    trend_tooltip:         '{kw} — {freq} ocorrências · Q1={q1} / Mediana={med} / Q3={q3}',

    // Citações por ano
    cit_title:             'Citações por Ano de Publicação',
    cit_x:                 'Ano de publicação',
    cit_avg:               'Citações médias por artigo',
    cit_tcy:               'TC/Y (citações totais / idade)',

    // Three-field plot
    three_field_empty:     'Dados insuficientes para o Three-Field Plot.',

    // Mapa de países
    map_loading:           'Carregando mapa...',
    map_libs_missing:      'Erro: D3.js ou TopoJSON não carregados. Verifique a conexão.',
    map_error:             'Erro ao carregar mapa: {msg}',
    map_legend:            'Nº de documentos',

    // Tabelas de impacto
    impact_empty:          'Dados de citação não disponíveis.',
    topcited_empty:        'Dados de citação não disponíveis.<br>A base WoS exporta o campo "Times Cited".',

    // Mapa temático (Callon)
    thematic_empty:        'Keywords insuficientes para formar clusters temáticos.',
    quad_niche:            'TEMAS DE NICHO',
    quad_motor:            'TEMAS MOTORES',
    quad_emerging:         'TEMAS EMERGENTES OU EM DECLÍNIO',
    quad_basic:            'TEMAS BÁSICOS E TRANSVERSAIS',
    thematic_x:            'Centralidade (relevância do tema)',
    thematic_y:            'Densidade (desenvolvimento do tema)',
    thematic_tip_cd:       'Centralidade: {c} · Densidade: {d}',
    thematic_tip_kw:       '{k} keywords · {o} ocorrências',
    thematic_tip_terms:    'Termos: {terms}',

    // Instituições
    inst_empty:            'Sem dados de afiliação institucional neste corpus.',
    inst_title:            'Top {n} Instituições por Produção',

    // MCP / SCP
    mcp_empty:             'Sem dados de afiliação por país neste corpus.',
    mcp_title:             'MCP vs SCP · Índice de Colaboração = {ci}',
    scp_label:             'SCP (nacional)',
    mcp_label:             'MCP (internacional)',

    // Autores ao longo do tempo
    aot_title:             'Produção dos Autores ao Longo do Tempo',

    // Colaboração entre países
    collab_empty:          'Sem dados de colaboração internacional.',

    // Evolução temática
    evol_empty:            'Palavras-chave insuficientes para análise de evolução temática neste corpus.',
    evol_no_clusters:      'Sem clusters neste período.',
    evol_error:            'Erro ao renderizar mapa temático: {msg}',
    q_motor:               'Motor',
    q_niche:               'Nicho',
    q_basic:               'Básico',
    q_emerging:            'Emergente',

    // Gráficos simples da página
    open_access:           'Acesso Aberto',
    closed_access:         'Acesso Restrito',

    // Redes
    net_missing:           'vis.js não disponível',
    net_few_articles:      'Dados insuficientes. Tente reduzir o mínimo de artigos.',
    net_few_occurrences:   'Dados insuficientes. Tente reduzir o mínimo de ocorrências.',
    net_joint_articles:    '{n} artigo(s) em conjunto',
    net_occurrences:       '{n} ocorrência(s)',
    net_cooccurrences:     'Co-ocorrências: {n}',
  },

  // ──────────────────────────────────────────────────────────── ENGLISH ────
  en: {
    articles:              'Articles',
    articles_log:          'Articles (log scale)',
    occurrences:           'Occurrences',
    publications:          'Publications',
    trend:                 'Trend',
    citations:             'Citations',
    docs:                  'Docs',
    author:                'Author',
    journal:               'Journal',
    title_col:             'Title',
    authors_col:           'Authors',
    year:                  'Year',
    keywords_col:          'Keywords',
    journals_col:          'Journals',
    error:                 'Error',
    n_articles:            '{n} article(s)',
    unit_article:          ' article',
    unit_articles:         ' articles',

    lotka_observed:        'Authors (N={n})',
    lotka_title:           'Author productivity distribution',
    lotka_sample_all:      'All records after deduplication: {a} articles, {n} authors',
    lotka_sample_included: 'Included studies only: {a} articles, {n} authors',
    lotka_sample_db:       '(selected database only)',
    lotka_title_short:     'Author productivity distribution',
    lotka_x:               'Articles per author',
    lotka_y:               'No. of authors',

    bradford_title:        "Bradford's Law — Core: {core} journals · {total} journals in total",
    bradford_x:            'Articles published',
    zone_core:             'Core',
    zone_related:          'Related',
    zone_periphery:        'Periphery',
    bradford_tooltip:      '{value} articles · zone: {zone}',

    annual_title:          'Annual Scientific Output — N={n} articles',
    annual_title_cagr:     'Annual Scientific Output — N={n} articles · CAGR ≈ {cagr}%/year',
    annual_in_year:        'Publications in the year',
    annual_cumulative:     'Cumulative',

    wordcloud_missing:     'WordCloud2.js not loaded',
    wordcloud_empty:       'Not enough keywords.',

    trend_title:           'Trend Topics — median and range (Q1–Q3) of publication year per keyword',
    trend_x:               'Publication year (median)',
    trend_tooltip:         '{kw} — {freq} occurrences · Q1={q1} / Median={med} / Q3={q3}',

    cit_title:             'Citations per Publication Year',
    cit_x:                 'Publication year',
    cit_avg:               'Mean citations per article',
    cit_tcy:               'TC/Y (total citations / age)',

    three_field_empty:     'Not enough data for the Three-Field Plot.',

    map_loading:           'Loading map...',
    map_libs_missing:      'Error: D3.js or TopoJSON not loaded. Check your connection.',
    map_error:             'Error loading map: {msg}',
    map_legend:            'No. of documents',

    impact_empty:          'Citation data not available.',
    topcited_empty:        'Citation data not available.<br>The WoS database exports the "Times Cited" field.',

    thematic_empty:        'Not enough keywords to form thematic clusters.',
    quad_niche:            'NICHE THEMES',
    quad_motor:            'MOTOR THEMES',
    quad_emerging:         'EMERGING OR DECLINING THEMES',
    quad_basic:            'BASIC AND TRANSVERSAL THEMES',
    thematic_x:            'Centrality (relevance of the theme)',
    thematic_y:            'Density (development of the theme)',
    thematic_tip_cd:       'Centrality: {c} · Density: {d}',
    thematic_tip_kw:       '{k} keywords · {o} occurrences',
    thematic_tip_terms:    'Terms: {terms}',

    inst_empty:            'No institutional affiliation data in this corpus.',
    inst_title:            'Top {n} Institutions by Output',

    mcp_empty:             'No country affiliation data in this corpus.',
    mcp_title:             'MCP vs SCP · Collaboration Index = {ci}',
    scp_label:             'SCP (single country)',
    mcp_label:             'MCP (multiple countries)',

    aot_title:             "Authors' Production over Time",

    collab_empty:          'No international collaboration data.',

    evol_empty:            'Not enough keywords for thematic evolution analysis in this corpus.',
    evol_no_clusters:      'No clusters in this period.',
    evol_error:            'Error rendering thematic map: {msg}',
    q_motor:               'Motor',
    q_niche:               'Niche',
    q_basic:               'Basic',
    q_emerging:            'Emerging',

    open_access:           'Open Access',
    closed_access:         'Restricted Access',

    net_missing:           'vis.js not available',
    net_few_articles:      'Not enough data. Try lowering the minimum number of articles.',
    net_few_occurrences:   'Not enough data. Try lowering the minimum number of occurrences.',
    net_joint_articles:    '{n} joint article(s)',
    net_occurrences:       '{n} occurrence(s)',
    net_cooccurrences:     'Co-occurrences: {n}',
  },

  // ──────────────────────────────────────────────────────────── ESPAÑOL ────
  es: {
    articles:              'Artículos',
    articles_log:          'Artículos (escala log)',
    occurrences:           'Ocurrencias',
    publications:          'Publicaciones',
    trend:                 'Tendencia',
    citations:             'Citas',
    docs:                  'Docs',
    author:                'Autor',
    journal:               'Revista',
    title_col:             'Título',
    authors_col:           'Autores',
    year:                  'Año',
    keywords_col:          'Keywords',
    journals_col:          'Revistas',
    error:                 'Error',
    n_articles:            '{n} artículo(s)',
    unit_article:          ' artículo',
    unit_articles:         ' artículos',

    lotka_observed:        'Autores (N={n})',
    lotka_title:           'Distribución de la productividad de los autores',
    lotka_sample_all:      'Todos los registros sin duplicados: {a} artículos, {n} autores',
    lotka_sample_included: 'Solo estudios incluidos: {a} artículos, {n} autores',
    lotka_sample_db:       '(solo la base seleccionada)',
    lotka_title_short:     'Productividad de los autores',
    lotka_x:               'Artículos por autor',
    lotka_y:               'Nº de autores',

    bradford_title:        'Ley de Bradford — Núcleo: {core} revistas · {total} revistas en total',
    bradford_x:            'Artículos publicados',
    zone_core:             'Núcleo',
    zone_related:          'Intermedia',
    zone_periphery:        'Periferia',
    bradford_tooltip:      '{value} artículos · zona: {zone}',

    annual_title:          'Producción Científica Anual — N={n} artículos',
    annual_title_cagr:     'Producción Científica Anual — N={n} artículos · CAGR ≈ {cagr}%/año',
    annual_in_year:        'Publicaciones en el año',
    annual_cumulative:     'Acumulado',

    wordcloud_missing:     'WordCloud2.js no cargado',
    wordcloud_empty:       'Keywords insuficientes.',

    trend_title:           'Trend Topics — mediana y amplitud (Q1–Q3) del año de publicación por keyword',
    trend_x:               'Año de publicación (mediana)',
    trend_tooltip:         '{kw} — {freq} ocurrencias · Q1={q1} / Mediana={med} / Q3={q3}',

    cit_title:             'Citas por Año de Publicación',
    cit_x:                 'Año de publicación',
    cit_avg:               'Citas medias por artículo',
    cit_tcy:               'TC/Y (citas totales / edad)',

    three_field_empty:     'Datos insuficientes para el Three-Field Plot.',

    map_loading:           'Cargando mapa...',
    map_libs_missing:      'Error: D3.js o TopoJSON no cargados. Verifique la conexión.',
    map_error:             'Error al cargar el mapa: {msg}',
    map_legend:            'Nº de documentos',

    impact_empty:          'Datos de citación no disponibles.',
    topcited_empty:        'Datos de citación no disponibles.<br>La base WoS exporta el campo "Times Cited".',

    thematic_empty:        'Keywords insuficientes para formar clústeres temáticos.',
    quad_niche:            'TEMAS DE NICHO',
    quad_motor:            'TEMAS MOTORES',
    quad_emerging:         'TEMAS EMERGENTES O EN DECLIVE',
    quad_basic:            'TEMAS BÁSICOS Y TRANSVERSALES',
    thematic_x:            'Centralidad (relevancia del tema)',
    thematic_y:            'Densidad (desarrollo del tema)',
    thematic_tip_cd:       'Centralidad: {c} · Densidad: {d}',
    thematic_tip_kw:       '{k} keywords · {o} ocurrencias',
    thematic_tip_terms:    'Términos: {terms}',

    inst_empty:            'Sin datos de afiliación institucional en este corpus.',
    inst_title:            'Top {n} Instituciones por Producción',

    mcp_empty:             'Sin datos de afiliación por país en este corpus.',
    mcp_title:             'MCP vs SCP · Índice de Colaboración = {ci}',
    scp_label:             'SCP (nacional)',
    mcp_label:             'MCP (internacional)',

    aot_title:             'Producción de los Autores a lo Largo del Tiempo',

    collab_empty:          'Sin datos de colaboración internacional.',

    evol_empty:            'Palabras clave insuficientes para el análisis de evolución temática en este corpus.',
    evol_no_clusters:      'Sin clústeres en este período.',
    evol_error:            'Error al renderizar el mapa temático: {msg}',
    q_motor:               'Motor',
    q_niche:               'Nicho',
    q_basic:               'Básico',
    q_emerging:            'Emergente',

    open_access:           'Acceso Abierto',
    closed_access:         'Acceso Restringido',

    net_missing:           'vis.js no disponible',
    net_few_articles:      'Datos insuficientes. Intente reducir el mínimo de artículos.',
    net_few_occurrences:   'Datos insuficientes. Intente reducir el mínimo de ocurrencias.',
    net_joint_articles:    '{n} artículo(s) en conjunto',
    net_occurrences:       '{n} ocurrencia(s)',
    net_cooccurrences:     'Co-ocurrencias: {n}',
  },
};

/** Idioma corrente, com fallback para pt. */
function bibLang() {
  const l = String(window.PRISMA_LANG || 'pt').toLowerCase().slice(0, 2);
  return BIB_I18N[l] ? l : 'pt';
}

/** Texto traduzido. bt('lotka_title', { slope: 2.3, n: 880 }) */
function bt(key, vars) {
  const dict = BIB_I18N[bibLang()];
  let s = (dict && dict[key] !== undefined) ? dict[key]
        : (BIB_I18N.pt[key] !== undefined ? BIB_I18N.pt[key] : key);
  if (vars) Object.keys(vars).forEach(k => { s = s.split('{' + k + '}').join(vars[k]); });
  return s;
}

// ── Replay: redesenha os gráficos já carregados quando o idioma muda ────────
// Cada build*() registra os argumentos com que foi chamada; na troca de idioma
// as mesmas funções são reexecutadas com os dados em cache, sem nova ida à API.
const BIB_REPLAY = {};

function bibReplaySave(fn, args) {
  const key = args && args[0];
  if (typeof key === 'string' && key) {
    BIB_REPLAY[key] = { fn: fn, args: Array.prototype.slice.call(args) };
  }
}

function bibReplayAll() {
  Object.keys(BIB_REPLAY).forEach(k => {
    const r = BIB_REPLAY[k];
    try { r.fn.apply(null, r.args); }
    catch (e) { console.error('replay i18n falhou em ' + k, e); }
  });
}

document.addEventListener('prismaLangChanged', function () {
  bibReplayAll();
  // Gráficos simples e tabelas vivem no script inline da página
  if (typeof bibRerenderPageCharts === 'function') bibRerenderPageCharts();
});
