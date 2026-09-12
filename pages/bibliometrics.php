<?php
/**
 * PRISMA-SLR - Bibliometrics
 */
if (!$projectId): ?>
<div class="empty-state"><i class="fa fa-triangle-exclamation"></i>
  <p>Selecione um projeto para ver as análises bibliométricas.</p></div>
<?php return; endif; ?>

<style>
/* ── Chart card layout ─────────────────────────────── */
.charts-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 18px;
  margin-bottom: 18px;
}
@media (max-width: 900px) { .charts-grid { grid-template-columns: 1fr; } }

.chart-card {
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 18px 20px;
  display: flex;
  flex-direction: column;
}
.chart-card-wide  { grid-column: span 2; }
.chart-card-full  { grid-column: 1 / -1; }
@media (max-width: 900px) {
  .chart-card-wide, .chart-card-full { grid-column: 1; }
}

.chart-card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 14px;
}
.chart-title {
  font-size: .9rem;
  font-weight: 700;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 7px;
}

/* Canvas wrapper height */
.chart-wrapper       { height: 280px; }
.chart-wrapper-tall  { height: 420px; }
</style>

<?php /* ── Main content ── */ ?>

<div class="page-header">
  <div>
    <h1 class="page-title"><i class="fa fa-chart-bar"></i> Bibliometria</h1>
    <p class="page-subtitle">Análises gráficas e estatísticas sobre a base de artigos do projeto.</p>
  </div>
  <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
    <label style="font-size:0.82rem;color:var(--text-muted)">Base de dados:</label>
    <select class="form-control" id="bib-source" style="width:auto" onchange="reloadAll()">
      <option value="0">Todas as bases (combinadas)</option>
    </select>
    <label style="font-size:0.82rem;color:var(--text-muted)">Escopo:</label>
    <select class="form-control" id="bib-scope" style="width:auto" onchange="reloadAll()">
      <option value="all">Todos (não-duplicatas)</option>
      <option value="included">Apenas incluídos</option>
    </select>
    <button class="btn btn-outline btn-sm" onclick="reloadAll()"><i class="fa fa-rotate"></i> Atualizar</button>
  </div>
</div>

<!-- ============================================================
     SECTION 1 — CLASSIC BIBLIOMETRIC LAWS
     (Lotka · Bradford · Annual production with CAGR)
     ============================================================ -->
<h2 style="font-size:1rem;font-weight:700;margin:8px 0 14px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em">
  <i class="fa fa-scale-balanced" style="color:var(--primary)"></i> Leis Bibliométricas Clássicas
</h2>

<div class="charts-grid">

  <!-- Lotka's Law -->
  <div class="chart-card" id="chart-card-lotka">
    <div class="chart-card-header">
      <h3 class="chart-title"><i class="fa fa-chart-scatter"></i> Lei de Lotka</h3>
      <button class="btn btn-ghost btn-sm" onclick="exportCanvasPng('chart-lotka','lotka.png')" title="Exportar PNG"><i class="fa fa-download"></i></button>
      <button class="btn btn-ghost btn-sm" onclick="exportChartXLS('lotka','lotka.xlsx')" title="Exportar XLS"><i class="fa fa-file-excel"></i></button>
    </div>
    <div class="chart-wrapper"><canvas id="chart-lotka"></canvas></div>
    <p style="font-size:.72rem;color:var(--text-muted);margin:8px 4px 0;line-height:1.4">
      Distribuição de produtividade: nº de autores que publicaram cada quantidade de artigos. A linha tracejada é a previsão teórica de Lotka (1926), f(n)=f(1)/nⁿ, ancorada nos autores com 1 artigo; expoente n ≈ 2 indica padrão lotkaiano clássico.
    </p>
  </div>

  <!-- Bradford's Law -->
  <div class="chart-card" id="chart-card-bradford">
    <div class="chart-card-header">
      <h3 class="chart-title"><i class="fa fa-layer-group"></i> Lei de Bradford</h3>
      <button class="btn btn-ghost btn-sm" onclick="exportCanvasPng('chart-bradford','bradford.png')" title="Exportar PNG"><i class="fa fa-download"></i></button>
      <button class="btn btn-ghost btn-sm" onclick="exportChartXLS('bradford','bradford.xlsx')" title="Exportar XLS"><i class="fa fa-file-excel"></i></button>
    </div>
    <div class="chart-wrapper"><canvas id="chart-bradford"></canvas></div>
    <p style="font-size:.72rem;color:var(--text-muted);margin:8px 4px 0;line-height:1.4">
      Periódicos ordenados por produtividade. Azul = núcleo (1/3 das publicações); verde = zona intermediária; cinza = periferia.
    </p>
  </div>

  <!-- Annual Production with CAGR -->
  <div class="chart-card chart-card-wide" id="chart-card-annual_growth">
    <div class="chart-card-header">
      <h3 class="chart-title"><i class="fa fa-chart-line"></i> Produção Científica Anual + CAGR</h3>
      <button class="btn btn-ghost btn-sm" onclick="exportCanvasPng('chart-annual_growth','producao-anual.png')" title="Exportar PNG"><i class="fa fa-download"></i></button>
      <button class="btn btn-ghost btn-sm" onclick="exportChartXLS('annual_growth','producao-anual.xlsx')" title="Exportar XLS"><i class="fa fa-file-excel"></i></button>
    </div>
    <div class="chart-wrapper"><canvas id="chart-annual_growth"></canvas></div>
    <p style="font-size:.72rem;color:var(--text-muted);margin:8px 4px 0;line-height:1.4">
      Barras: publicações no ano. Linha: total acumulado. O CAGR (Compound Annual Growth Rate) quantifica a taxa de crescimento composto do campo.
    </p>
  </div>

</div>

<!-- ============================================================
     SECTION 1B — LOCAL IMPACT INDICES (h, g, m)
     ============================================================ -->
<h2 style="font-size:1rem;font-weight:700;margin:28px 0 14px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em">
  <i class="fa fa-ranking-star" style="color:var(--primary)"></i> Índices de Impacto Local
</h2>

<div class="charts-grid">

  <!-- Author Impact -->
  <div class="chart-card chart-card-wide" id="chart-card-author_impact">
    <div class="chart-card-header">
      <h3 class="chart-title"><i class="fa fa-user-graduate"></i> Impacto dos Autores (índices h, g, m)</h3>
      <button class="btn btn-ghost btn-sm" onclick="exportElementPng('chart-card-author_impact','impacto-autores.png')" title="Exportar PNG"><i class="fa fa-download"></i></button>
      <button class="btn btn-ghost btn-sm" onclick="exportChartXLS('author_impact','impacto-autores.xlsx')" title="Exportar XLS"><i class="fa fa-file-excel"></i></button>
    </div>
    <div id="table-author_impact" style="overflow-x:auto">
      <div class="text-center" style="padding:30px"><div class="spinner"></div></div>
    </div>
    <p style="font-size:.72rem;color:var(--text-muted);margin:8px 4px 0;line-height:1.4">
      Índices calculados sobre as citações dos artigos dentro do corpus (impacto local, como em bibliometrix).
      h: h artigos com ≥ h citações · g: g artigos acumulando ≥ g² citações · m: h ÷ anos desde a 1ª publicação no corpus.
    </p>
  </div>

  <!-- Journal Impact -->
  <div class="chart-card chart-card-wide" id="chart-card-source_impact">
    <div class="chart-card-header">
      <h3 class="chart-title"><i class="fa fa-book-journal-whills"></i> Impacto dos Periódicos (índices h, g)</h3>
      <button class="btn btn-ghost btn-sm" onclick="exportElementPng('chart-card-source_impact','impacto-periodicos.png')" title="Exportar PNG"><i class="fa fa-download"></i></button>
      <button class="btn btn-ghost btn-sm" onclick="exportChartXLS('source_impact','impacto-periodicos.xlsx')" title="Exportar XLS"><i class="fa fa-file-excel"></i></button>
    </div>
    <div id="table-source_impact" style="overflow-x:auto">
      <div class="text-center" style="padding:30px"><div class="spinner"></div></div>
    </div>
    <p style="font-size:.72rem;color:var(--text-muted);margin:8px 4px 0;line-height:1.4">
      Índice h local de cada periódico dentro do corpus da revisão.
    </p>
  </div>

</div>

<!-- ============================================================
     SECTION 2 — CONTENT AND THEME ANALYSIS
     (Word Cloud · Trend Topics · Three-Field Plot)
     ============================================================ -->
<h2 style="font-size:1rem;font-weight:700;margin:28px 0 14px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em">
  <i class="fa fa-cloud" style="color:var(--primary)"></i> Análise de Conteúdo e Temas
</h2>

<div class="charts-grid">

  <!-- Word Cloud -->
  <div class="chart-card chart-card-wide" id="chart-card-word_cloud">
    <div class="chart-card-header">
      <h3 class="chart-title"><i class="fa fa-cloud"></i> Nuvem de Palavras-chave</h3>
      <button class="btn btn-ghost btn-sm" onclick="exportContainerCanvasPng('word-cloud-container','nuvem-palavras.png')" title="Exportar PNG"><i class="fa fa-download"></i></button>
      <button class="btn btn-ghost btn-sm" onclick="exportChartXLS('word_cloud','nuvem-palavras.xlsx')" title="Exportar XLS"><i class="fa fa-file-excel"></i></button>
    </div>
    <div id="word-cloud-container" style="width:100%;min-height:360px;display:flex;align-items:center;justify-content:center"></div>
    <p style="font-size:.72rem;color:var(--text-muted);margin:8px 4px 0;line-height:1.4">
      Tamanho da palavra proporcional à frequência das author-keywords no corpus.
    </p>
  </div>

  <!-- Trend Topics -->
  <div class="chart-card chart-card-wide" id="chart-card-trend_topics">
    <div class="chart-card-header">
      <h3 class="chart-title"><i class="fa fa-timeline"></i> Trend Topics — Evolução temporal</h3>
      <button class="btn btn-ghost btn-sm" onclick="exportCanvasPng('chart-trend_topics','trend-topics.png')" title="Exportar PNG"><i class="fa fa-download"></i></button>
      <button class="btn btn-ghost btn-sm" onclick="exportChartXLS('trend_topics','trend-topics.xlsx')" title="Exportar XLS"><i class="fa fa-file-excel"></i></button>
    </div>
    <div class="chart-wrapper chart-wrapper-tall"><canvas id="chart-trend_topics"></canvas></div>
    <p style="font-size:.72rem;color:var(--text-muted);margin:8px 4px 0;line-height:1.4">
      Cada bolha = uma keyword. Tamanho ∝ frequência. Posição horizontal = ano mediano de publicação; barra horizontal = amplitude Q1–Q3.
    </p>
  </div>

  <!-- Three-Field Plot (Sankey) -->
  <!-- Thematic Map (Callon) -->
  <div class="chart-card chart-card-wide" id="chart-card-thematic_map">
    <div class="chart-card-header">
      <h3 class="chart-title"><i class="fa fa-map"></i> Mapa Temático — Diagrama Estratégico de Callon</h3>
      <button class="btn btn-ghost btn-sm" onclick="exportCanvasPng('chart-thematic_map','mapa-tematico.png')" title="Exportar PNG"><i class="fa fa-download"></i></button>
      <button class="btn btn-ghost btn-sm" onclick="exportChartXLS('thematic_map','mapa-tematico.xlsx')" title="Exportar XLS"><i class="fa fa-file-excel"></i></button>
    </div>
    <div class="chart-wrapper chart-wrapper-tall"><canvas id="chart-thematic_map"></canvas></div>
    <p style="font-size:.72rem;color:var(--text-muted);margin:8px 4px 0;line-height:1.4">
      Clusters de palavras-chave posicionados por centralidade (relevância do tema) × densidade (grau de desenvolvimento), via índice de equivalência de Callon.
      Quadrantes: motor (↗) · nicho (↖) · emergente/declinante (↙) · básico/transversal (↘). Tamanho da bolha = ocorrências.
    </p>
  </div>

  <div class="chart-card chart-card-full" id="chart-card-three_field">
    <div class="chart-card-header">
      <h3 class="chart-title"><i class="fa fa-diagram-project"></i> Three-Field Plot — Autores · Keywords · Periódicos</h3>
      <button class="btn btn-ghost btn-sm" onclick="exportSvgPng('three-field-container','three-field.png')" title="Exportar PNG"><i class="fa fa-download"></i></button>
      <button class="btn btn-ghost btn-sm" onclick="exportChartXLS('three_field','three-field.xlsx')" title="Exportar XLS"><i class="fa fa-file-excel"></i></button>
    </div>
    <div id="three-field-container" style="width:100%;min-height:480px;overflow:auto"></div>
    <p style="font-size:.72rem;color:var(--text-muted);margin:8px 4px 0;line-height:1.4">
      Diagrama de fluxo (Sankey) ligando os 12 principais autores → keywords → periódicos. A espessura do fluxo representa a frequência de coocorrência.
    </p>
  </div>

</div>

<!-- ============================================================
     SECTION 2B — COLLABORATION & INSTITUTIONS
     ============================================================ -->
<h2 style="font-size:1rem;font-weight:700;margin:28px 0 14px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em">
  <i class="fa fa-building-columns" style="color:var(--primary)"></i> Colaboração &amp; Instituições
</h2>

<div class="charts-grid">

  <!-- Top Institutions -->
  <div class="chart-card chart-card-wide" id="chart-card-top_institutions">
    <div class="chart-card-header">
      <h3 class="chart-title"><i class="fa fa-university"></i> Top Instituições por Produção</h3>
      <button class="btn btn-ghost btn-sm" onclick="exportCanvasPng('chart-top_institutions','instituicoes.png')" title="Exportar PNG"><i class="fa fa-download"></i></button>
      <button class="btn btn-ghost btn-sm" onclick="exportChartXLS('top_institutions','instituicoes.xlsx')" title="Exportar XLS"><i class="fa fa-file-excel"></i></button>
    </div>
    <div class="chart-wrapper"><canvas id="chart-top_institutions"></canvas></div>
    <p style="font-size:.72rem;color:var(--text-muted);margin:8px 4px 0;line-height:1.4">
      Instituições com maior número de artigos no corpus. Extraído das afiliações dos autores.
    </p>
  </div>

  <!-- MCP vs SCP -->
  <div class="chart-card chart-card-wide" id="chart-card-mcp_scp">
    <div class="chart-card-header">
      <h3 class="chart-title"><i class="fa fa-globe"></i> MCP vs SCP — Colaboração Internacional por País</h3>
      <button class="btn btn-ghost btn-sm" onclick="exportCanvasPng('chart-mcp_scp','mcp_scp.png')" title="Exportar PNG"><i class="fa fa-download"></i></button>
      <button class="btn btn-ghost btn-sm" onclick="exportChartXLS('mcp_scp','mcp_scp.xlsx')" title="Exportar XLS"><i class="fa fa-file-excel"></i></button>
    </div>
    <div class="chart-wrapper"><canvas id="chart-mcp_scp"></canvas></div>
    <p style="font-size:.72rem;color:var(--text-muted);margin:8px 4px 0;line-height:1.4">
      SCP = artigos com autores de um único país; MCP = artigos com autores de múltiplos países. O Índice de Colaboração (IC) é a média de países por artigo.
    </p>
  </div>

  <!-- Author Production Over Time -->
  <div class="chart-card chart-card-full" id="chart-card-author_over_time">
    <div class="chart-card-header">
      <h3 class="chart-title"><i class="fa fa-chart-scatter"></i> Produção dos Autores ao Longo do Tempo</h3>
      <button class="btn btn-ghost btn-sm" onclick="exportCanvasPng('chart-author_over_time','autor_tempo.png')" title="Exportar PNG"><i class="fa fa-download"></i></button>
      <button class="btn btn-ghost btn-sm" onclick="exportChartXLS('author_over_time','autor_tempo.xlsx')" title="Exportar XLS"><i class="fa fa-file-excel"></i></button>
    </div>
    <div class="chart-wrapper" style="height:340px"><canvas id="chart-author_over_time"></canvas></div>
    <p style="font-size:.72rem;color:var(--text-muted);margin:8px 4px 0;line-height:1.4">
      Atividade anual dos 10 autores mais produtivos. Tamanho da bolha proporcional ao número de artigos publicados naquele ano.
    </p>
  </div>

  <!-- Country Collaboration Network -->
  <div class="chart-card chart-card-full" id="chart-card-country_collab">
    <div class="chart-card-header">
      <h3 class="chart-title"><i class="fa fa-diagram-project"></i> Rede de Colaboração entre Países</h3>
      <button class="btn btn-ghost btn-sm" onclick="exportSvgPng('country-collab-container','colaboracao-paises.png')" title="Exportar PNG"><i class="fa fa-download"></i></button>
      <button class="btn btn-ghost btn-sm" onclick="exportChartXLS('country_collab','colaboracao-paises.xlsx')" title="Exportar XLS"><i class="fa fa-file-excel"></i></button>
    </div>
    <div id="country-collab-container" style="width:100%;min-height:420px"></div>
    <p style="font-size:.72rem;color:var(--text-muted);margin:8px 4px 0;line-height:1.4">
      Nós = países (tamanho ∝ produção); arestas = coautorias internacionais (espessura ∝ nº de artigos compartilhados).
    </p>
  </div>

</div>

<!-- ============================================================
     SECTION 2C — THEMATIC EVOLUTION
     ============================================================ -->
<h2 style="font-size:1rem;font-weight:700;margin:28px 0 14px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em">
  <i class="fa fa-timeline" style="color:var(--primary)"></i> Evolução Temática
</h2>

<div class="chart-card chart-card-full" id="chart-card-thematic_evolution">
  <div class="chart-card-header">
    <h3 class="chart-title"><i class="fa fa-arrows-left-right"></i> Evolução do Mapa Temático por Período</h3>
    <div style="display:flex;gap:8px;align-items:center">
      <select class="form-control" id="evol-periods" style="width:auto;font-size:.78rem" onchange="loadThematicEvolution()">
        <option value="2">2 períodos</option>
        <option value="3">3 períodos</option>
      </select>
      <button class="btn btn-ghost btn-sm" onclick="exportElementPng('chart-card-thematic_evolution','evolucao-tematica.png')" title="Exportar PNG"><i class="fa fa-download"></i></button>
      <button class="btn btn-ghost btn-sm" onclick="exportChartXLS('thematic_evolution','evolucao-tematica.xlsx')" title="Exportar XLS"><i class="fa fa-file-excel"></i></button>
    </div>
  </div>
  <div id="thematic-evolution-container" style="padding:12px"><div class="spinner"></div></div>
  <p style="font-size:.72rem;color:var(--text-muted);margin:8px 4px 0;line-height:1.4">
    Mapa temático de Callon calculado separadamente para cada período temporal. Mostra como temas motores, de nicho, básicos e emergentes evoluíram ao longo do tempo.
  </p>
</div>

<!-- ============================================================
     SECTION 3 — CITATION ANALYSIS
     ============================================================ -->
<h2 style="font-size:1rem;font-weight:700;margin:28px 0 14px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em">
  <i class="fa fa-quote-right" style="color:var(--primary)"></i> Análise de Citações
</h2>

<div class="charts-grid">

  <!-- Citations per year -->
  <div class="chart-card chart-card-wide" id="chart-card-citations_per_year">
    <div class="chart-card-header">
      <h3 class="chart-title"><i class="fa fa-chart-line"></i> Citações por Ano de Publicação (TC/Y)</h3>
      <button class="btn btn-ghost btn-sm" onclick="exportCanvasPng('chart-citations_per_year','citacoes-ano.png')" title="Exportar PNG"><i class="fa fa-download"></i></button>
      <button class="btn btn-ghost btn-sm" onclick="exportChartXLS('citations_per_year','citacoes-ano.xlsx')" title="Exportar XLS"><i class="fa fa-file-excel"></i></button>
    </div>
    <div class="chart-wrapper"><canvas id="chart-citations_per_year"></canvas></div>
    <p style="font-size:.72rem;color:var(--text-muted);margin:8px 4px 0;line-height:1.4">
      Citações médias por artigo (sólido) e TC/Y normalizado pela idade do artigo (tracejado).
    </p>
  </div>

</div>

<!-- ============================================================
     SECTION 4 — SUPPLEMENTARY DESCRIPTIVES
     ============================================================ -->
<h2 style="font-size:1rem;font-weight:700;margin:28px 0 14px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em">
  <i class="fa fa-table-cells" style="color:var(--primary)"></i> Análises Descritivas
</h2>

<!-- Chart grid -->
<div class="charts-grid">

  <!-- Publications per Year -->
  <div class="chart-card" id="chart-card-publications_year">
    <div class="chart-card-header">
      <h3 class="chart-title"><i class="fa fa-chart-line"></i> Publicações por Ano</h3>
      <button class="btn btn-ghost btn-sm" onclick="exportCanvasPng('chart-publications_year','publicacoes-por-ano.png')" title="Exportar PNG"><i class="fa fa-download"></i></button>
      <button class="btn btn-ghost btn-sm" onclick="exportChartXLS('publications_year','publicacoes-por-ano.xlsx')" title="Exportar XLS"><i class="fa fa-file-excel"></i></button>
    </div>
    <div class="chart-wrapper"><canvas id="chart-publications_year"></canvas></div>
  </div>

  <!-- Distribution by Database -->
  <div class="chart-card" id="chart-card-source_types">
    <div class="chart-card-header">
      <h3 class="chart-title"><i class="fa fa-database"></i> Distribuição por Base</h3>
      <button class="btn btn-ghost btn-sm" onclick="exportCanvasPng('chart-source_types','bases.png')" title="Exportar PNG"><i class="fa fa-download"></i></button>
      <button class="btn btn-ghost btn-sm" onclick="exportChartXLS('source_types','bases.xlsx')" title="Exportar XLS"><i class="fa fa-file-excel"></i></button>
    </div>
    <div class="chart-wrapper"><canvas id="chart-source_types"></canvas></div>
  </div>

  <!-- Document Types -->
  <div class="chart-card" id="chart-card-document_types">
    <div class="chart-card-header">
      <h3 class="chart-title"><i class="fa fa-file-alt"></i> Tipos de Documento</h3>
      <button class="btn btn-ghost btn-sm" onclick="exportCanvasPng('chart-document_types','tipos-documento.png')" title="Exportar PNG"><i class="fa fa-download"></i></button>
      <button class="btn btn-ghost btn-sm" onclick="exportChartXLS('document_types','tipos-documento.xlsx')" title="Exportar XLS"><i class="fa fa-file-excel"></i></button>
    </div>
    <div class="chart-wrapper"><canvas id="chart-document_types"></canvas></div>
  </div>

  <!-- Open Access -->
  <div class="chart-card" id="chart-card-open_access">
    <div class="chart-card-header">
      <h3 class="chart-title"><i class="fa fa-lock-open"></i> Acesso Aberto</h3>
      <button class="btn btn-ghost btn-sm" onclick="exportCanvasPng('chart-open_access','acesso-aberto.png')" title="Exportar PNG"><i class="fa fa-download"></i></button>
      <button class="btn btn-ghost btn-sm" onclick="exportChartXLS('open_access','acesso-aberto.xlsx')" title="Exportar XLS"><i class="fa fa-file-excel"></i></button>
    </div>
    <div class="chart-wrapper"><canvas id="chart-open_access"></canvas></div>
  </div>

  <!-- Top Journals (full width) -->
  <div class="chart-card chart-card-wide" id="chart-card-top_journals">
    <div class="chart-card-header">
      <h3 class="chart-title"><i class="fa fa-journal-whills"></i> Top 15 Periódicos</h3>
      <button class="btn btn-ghost btn-sm" onclick="exportCanvasPng('chart-top_journals','periodicos.png')" title="Exportar PNG"><i class="fa fa-download"></i></button>
      <button class="btn btn-ghost btn-sm" onclick="exportChartXLS('top_journals','periodicos.xlsx')" title="Exportar XLS"><i class="fa fa-file-excel"></i></button>
    </div>
    <div class="chart-wrapper chart-wrapper-tall"><canvas id="chart-top_journals"></canvas></div>
  </div>

  <!-- Top Authors (full width) -->
  <div class="chart-card chart-card-wide" id="chart-card-top_authors">
    <div class="chart-card-header">
      <h3 class="chart-title"><i class="fa fa-users"></i> Top 15 Autores com Mais Publicações</h3>
      <button class="btn btn-ghost btn-sm" onclick="exportCanvasPng('chart-top_authors','autores.png')" title="Exportar PNG"><i class="fa fa-download"></i></button>
      <button class="btn btn-ghost btn-sm" onclick="exportChartXLS('top_authors','autores.xlsx')" title="Exportar XLS"><i class="fa fa-file-excel"></i></button>
    </div>
    <div class="chart-wrapper chart-wrapper-tall"><canvas id="chart-top_authors"></canvas></div>
  </div>

  <!-- Top Countries -->
  <div class="chart-card" id="chart-card-top_countries">
    <div class="chart-card-header">
      <h3 class="chart-title"><i class="fa fa-globe"></i> Top Países</h3>
      <button class="btn btn-ghost btn-sm" onclick="exportCanvasPng('chart-top_countries','paises.png')" title="Exportar PNG"><i class="fa fa-download"></i></button>
      <button class="btn btn-ghost btn-sm" onclick="exportChartXLS('top_countries','paises.xlsx')" title="Exportar XLS"><i class="fa fa-file-excel"></i></button>
    </div>
    <div class="chart-wrapper"><canvas id="chart-top_countries"></canvas></div>
  </div>

  <!-- World Map of Scientific Production -->
  <div class="chart-card chart-card-wide" id="chart-card-country_production">
    <div class="chart-card-header">
      <h3 class="chart-title"><i class="fa fa-earth-americas"></i> Country Scientific Production</h3>
      <button class="btn btn-ghost btn-sm" onclick="exportSvgPng('map-country_production','country-map.png')" title="Exportar PNG"><i class="fa fa-download"></i></button>
      <button class="btn btn-ghost btn-sm" onclick="exportChartXLS('country_production','country-map.xlsx')" title="Exportar XLS"><i class="fa fa-file-excel"></i></button>
    </div>
    <div id="map-country_production" style="width:100%;position:relative;"></div>
  </div>

  <!-- Citation Distribution -->
  <div class="chart-card" id="chart-card-citations_distribution">
    <div class="chart-card-header">
      <h3 class="chart-title"><i class="fa fa-quote-left"></i> Distribuição de Citações</h3>
      <button class="btn btn-ghost btn-sm" onclick="exportCanvasPng('chart-citations_distribution','citacoes.png')" title="Exportar PNG"><i class="fa fa-download"></i></button>
      <button class="btn btn-ghost btn-sm" onclick="exportChartXLS('citations_distribution','citacoes.xlsx')" title="Exportar XLS"><i class="fa fa-file-excel"></i></button>
    </div>
    <div class="chart-wrapper"><canvas id="chart-citations_distribution"></canvas></div>
  </div>

  <!-- Top Publishers -->
  <div class="chart-card" id="chart-card-top_publishers">
    <div class="chart-card-header">
      <h3 class="chart-title"><i class="fa fa-building"></i> Top Editoras</h3>
      <button class="btn btn-ghost btn-sm" onclick="exportCanvasPng('chart-top_publishers','editoras.png')" title="Exportar PNG"><i class="fa fa-download"></i></button>
      <button class="btn btn-ghost btn-sm" onclick="exportChartXLS('top_publishers','editoras.xlsx')" title="Exportar XLS"><i class="fa fa-file-excel"></i></button>
    </div>
    <div class="chart-wrapper"><canvas id="chart-top_publishers"></canvas></div>
  </div>

  <!-- Frequent Keywords (full width) -->
  <div class="chart-card chart-card-wide" id="chart-card-keywords_frequency">
    <div class="chart-card-header">
      <h3 class="chart-title"><i class="fa fa-tags"></i> Palavras-chave Mais Frequentes</h3>
      <button class="btn btn-ghost btn-sm" onclick="exportCanvasPng('chart-keywords_frequency','keywords.png')" title="Exportar PNG"><i class="fa fa-download"></i></button>
      <button class="btn btn-ghost btn-sm" onclick="exportChartXLS('keywords_frequency','keywords.xlsx')" title="Exportar XLS"><i class="fa fa-file-excel"></i></button>
    </div>
    <div class="chart-wrapper chart-wrapper-tall"><canvas id="chart-keywords_frequency"></canvas></div>
  </div>

  <!-- Keyword Evolution (full width) -->
  <div class="chart-card chart-card-wide" id="chart-card-keywords_by_year">
    <div class="chart-card-header">
      <h3 class="chart-title"><i class="fa fa-chart-area"></i> Evolução de Keywords por Ano</h3>
      <button class="btn btn-ghost btn-sm" onclick="exportCanvasPng('chart-keywords_by_year','keywords-ano.png')" title="Exportar PNG"><i class="fa fa-download"></i></button>
      <button class="btn btn-ghost btn-sm" onclick="exportChartXLS('keywords_by_year','keywords-ano.xlsx')" title="Exportar XLS"><i class="fa fa-file-excel"></i></button>
    </div>
    <div class="chart-wrapper"><canvas id="chart-keywords_by_year"></canvas></div>
  </div>
</div>

<!-- Table: Most Cited Articles -->
<div class="chart-card chart-card-full" style="margin-top:20px" id="section-top-cited">
  <div class="chart-card-header">
    <h3 class="chart-title"><i class="fa fa-trophy"></i> Top 10 Artigos Mais Citados</h3>
    <button class="btn btn-ghost btn-sm" onclick="exportElementPng('section-top-cited','top-artigos-citados.png')" title="Exportar PNG"><i class="fa fa-download"></i></button>
    <button class="btn btn-ghost btn-sm" onclick="exportChartXLS('top_cited','top-artigos-citados.xlsx')" title="Exportar XLS"><i class="fa fa-file-excel"></i></button>
  </div>
  <div id="top-cited-table" style="overflow-x:auto">
    <div class="text-center" style="padding:30px"><div class="spinner"></div></div>
  </div>
</div>

<!-- Co-authorship Network -->
<div class="chart-card chart-card-full" style="margin-top:20px" id="section-coauthorship">
  <div class="chart-card-header">
    <h3 class="chart-title"><i class="fa fa-network-wired"></i> Rede de Co-autoria</h3>
    <div style="display:flex;gap:8px;align-items:center">
      <label style="font-size:0.78rem;color:var(--text-muted)">Mín. artigos:</label>
      <select class="form-control" id="coauth-min" style="width:auto;font-size:0.78rem" onchange="loadCoauthorship()">
        <option value="1">1 artigo</option>
        <option value="2" selected>2 artigos</option>
        <option value="3">3 artigos</option>
      </select>
      <button class="btn btn-ghost btn-sm" onclick="exportContainerCanvasPng('network-coauthorship','rede-coautoria.png')" title="Exportar PNG"><i class="fa fa-download"></i></button>
      <button class="btn btn-ghost btn-sm" onclick="exportChartXLS('coauthorship','rede-coautoria.xlsx')" title="Exportar XLS"><i class="fa fa-file-excel"></i></button>
      <button class="btn btn-ghost btn-sm" onclick="loadCoauthorship()"><i class="fa fa-rotate"></i></button>
    </div>
  </div>
  <div id="network-coauthorship" style="height:480px;background:var(--bg-card2);border-radius:8px;position:relative">
    <div class="text-center" style="padding:60px"><div class="spinner"></div></div>
  </div>
</div>

<!-- Keyword Co-occurrence -->
<div class="chart-card chart-card-full" style="margin-top:20px" id="section-keyword-network">
  <div class="chart-card-header">
    <h3 class="chart-title"><i class="fa fa-diagram-project"></i> Co-ocorrência de Keywords</h3>
    <div style="display:flex;gap:8px;align-items:center">
      <label style="font-size:0.78rem;color:var(--text-muted)">Mín. ocorrências:</label>
      <select class="form-control" id="kwnet-min" style="width:auto;font-size:0.78rem" onchange="loadKeywordNetwork()">
        <option value="2" selected>2</option>
        <option value="3">3</option>
        <option value="5">5</option>
      </select>
      <button class="btn btn-ghost btn-sm" onclick="exportContainerCanvasPng('network-keywords','rede-keywords.png')" title="Exportar PNG"><i class="fa fa-download"></i></button>
      <button class="btn btn-ghost btn-sm" onclick="exportChartXLS('keyword_cooccurrence','rede-keywords.xlsx')" title="Exportar XLS"><i class="fa fa-file-excel"></i></button>
      <button class="btn btn-ghost btn-sm" onclick="loadKeywordNetwork()"><i class="fa fa-rotate"></i></button>
    </div>
  </div>
  <div id="network-keywords" style="height:480px;background:var(--bg-card2);border-radius:8px;position:relative">
    <div class="text-center" style="padding:60px"><div class="spinner"></div></div>
  </div>
</div>

<script>
const BIB_PROJECT_ID = <?= $projectId ?>;
const chartInstances  = {};

// Cache of each chart/table's last payload on this page, so it can be
// redrawn in another language without repeating the API calls (see
// bib-i18n.js) and to feed the XLS export (see chartToRows/exportChartXLS below).
const BIB_RAW = {};

// ─── XLS export (raw data for each chart, turned into a table) ──────

// Normalizes each chart type's payload into a plain list of rows
// (array of objects), a format SheetJS converts directly into a spreadsheet.
function chartToRows(type, raw) {
  if (!raw) return [];

  // Networks (nodes + edges): the most useful table is the edges (the links themselves)
  if (raw && Array.isArray(raw.edges)) return raw.edges;

  switch (type) {
    case 'open_access':
      return [{ acesso_aberto: raw.open_access || 0, acesso_fechado: raw.closed_access || 0 }];

    case 'lotka':
      return raw.distribution || [];

    case 'bradford':
      return raw.series || [];

    case 'annual_growth':
      return raw.series || [];

    case 'keywords_by_year':
      return raw.data || [];

    case 'three_field':
      return [
        ...(raw.links_ak || []).map(r => ({ tipo: 'autor→palavra-chave', de: r.author, para: r.keyword, peso: r.weight })),
        ...(raw.links_kj || []).map(r => ({ tipo: 'palavra-chave→periódico', de: r.keyword, para: r.journal, peso: r.weight })),
      ];

    case 'thematic_map':
      return (raw || []).map(c => ({ ...c, keywords: (c.keywords || []).join('; ') }));

    case 'thematic_evolution':
      return (raw || []).flatMap(p =>
        (p.clusters || []).map(c => ({ periodo: p.period, ...c, keywords: (c.keywords || []).join('; ') }))
      );

    default:
      // Most charts are already a plain array of objects {label, value, ...}
      return Array.isArray(raw) ? raw : [raw];
  }
}

function exportChartXLS(type, filename) {
  const raw = BIB_RAW[type];
  const rows = chartToRows(type, raw);
  if (!rows || !rows.length) { showToast('Sem dados para exportar neste gráfico ainda.', 'warning'); return; }

  const ws = XLSX.utils.json_to_sheet(rows);
  const wb = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(wb, ws, 'Dados');
  XLSX.writeFile(wb, filename);
}

// ─── Helpers ──────────────────────────────────────────────────────────────────

function getScope() {
  return document.getElementById('bib-scope').value;
}

function getSourceId() {
  return document.getElementById('bib-source').value;
}

// Populates the database selector with the project's imported sources
// (e.g. WOS, Scopus) — lets the user view charts for a single database or combined.
async function loadSourcesDropdown() {
  const sel = document.getElementById('bib-source');
  try {
    const r = await fetch(`api/import.php?project_id=${BIB_PROJECT_ID}`);
    const sources = await r.json();
    if (!Array.isArray(sources)) return;
    sources.forEach(s => {
      const opt = document.createElement('option');
      opt.value = s.id;
      opt.textContent = `${s.name} (${(s.source_type || '').toUpperCase()})`;
      sel.appendChild(opt);
    });
  } catch (e) { console.error('Erro ao carregar bases do projeto', e); }
}

function bibColors(n) {
  const base = [
    'rgba(0,212,255,.82)','rgba(0,229,160,.82)','rgba(116,192,252,.82)',
    'rgba(229,153,247,.82)','rgba(255,212,59,.82)','rgba(255,135,135,.82)',
    'rgba(255,180,80,.82)','rgba(80,200,120,.82)','rgba(200,120,200,.82)',
    'rgba(120,160,255,.82)','rgba(255,100,80,.82)','rgba(160,220,60,.82)',
  ];
  return Array.from({length: n}, (_, i) => base[i % base.length]);
}

function axis(extra = {}) {
  const isLight = document.body.classList.contains('theme-light');
  return {
    grid: { color: isLight ? 'rgba(0,0,0,0.06)' : 'rgba(255,255,255,0.05)', drawBorder: false },
    border: { display: false },
    ticks: { color: isLight ? '#475569' : '#94a3b8', font: { size: 11 } },
    ...extra,
  };
}

function destroyChart(key) {
  if (chartInstances[key]) { chartInstances[key].destroy(); delete chartInstances[key]; }
}

// ─── Transforms raw API data → Chart.js format ────────────────────────

function transformData(type, raw) {
  if (!raw) return null;

  switch (type) {
    case 'publications_year':
      return { labels: raw.map(r => r.year), datasets: [{ data: raw.map(r => +r.total) }] };

    case 'source_types':
      return { labels: raw.map(r => r.source_type), datasets: [{ data: raw.map(r => +r.total) }] };

    case 'document_types':
      return { labels: raw.map(r => r.type), datasets: [{ data: raw.map(r => +r.total) }] };

    case 'open_access':
      return {
        labels: [bt('open_access'), bt('closed_access')],
        datasets: [{ data: [+(raw.open_access||0), +(raw.closed_access||0)] }],
      };

    case 'top_journals':
      return { labels: raw.map(r => r.journal), datasets: [{ data: raw.map(r => +r.total) }] };

    case 'top_authors':
      return { labels: raw.map(r => r.author), datasets: [{ data: raw.map(r => +r.total) }] };

    case 'top_countries':
      return { labels: raw.map(r => r.country), datasets: [{ data: raw.map(r => +r.total) }] };

    case 'top_publishers':
      return { labels: raw.map(r => r.publisher), datasets: [{ data: raw.map(r => +r.total) }] };

    case 'citations_distribution':
      return { labels: raw.map(r => r.range), datasets: [{ data: raw.map(r => +r.total) }] };

    case 'keywords_frequency':
      return { labels: raw.map(r => r.keyword), datasets: [{ data: raw.map(r => +r.total) }] };

    case 'keywords_by_year': {
      if (!raw.top_keywords || !raw.data) return null;
      const years = [...new Set(raw.data.map(r => r.year))].sort();
      const datasets = raw.top_keywords.map(kw => ({
        label: kw,
        data: years.map(y => {
          const found = raw.data.find(r => r.year == y && r.keyword === kw);
          return found ? +found.total : 0;
        }),
      }));
      return { labels: years, datasets };
    }

    default:
      return null;
  }
}

// ─── Builds Chart.js charts ───────────────────────────────────────────────

function buildChart(type, data) {
  if (!data) return;
  const canvasId = `chart-${type}`;
  const canvas   = document.getElementById(canvasId);
  if (!canvas) return;

  destroyChart(type);
  const ctx    = canvas.getContext('2d');
  const labels = data.labels;
  const values = data.datasets[0].data;
  const n      = labels.length;
  const colors = bibColors(n);

  const isLight   = document.body.classList.contains('theme-light');
  const tickColor = isLight ? '#475569' : '#94a3b8';
  const pluginBase = {
    legend: { labels: { color: tickColor, font: { size: 11 }, padding: 14, usePointStyle: true } },
    tooltip: { mode: 'index', intersect: false },
  };

  let config;

  // ── Publications per Year ──────────────────────────────────────────────────
  if (type === 'publications_year') {
    config = {
      type: 'bar',
      data: { labels, datasets: [{
        label: bt('publications'),
        data: values,
        backgroundColor: 'rgba(0,212,255,0.7)',
        borderColor: '#00D4FF',
        borderWidth: 1, borderRadius: 5,
        order: 2,
      }, {
        label: bt('trend'),
        data: values,
        type: 'line',
        borderColor: 'rgba(0,229,160,0.9)',
        borderWidth: 2,
        pointRadius: 3,
        pointBackgroundColor: '#00e5a0',
        fill: false, tension: 0.4,
        order: 1,
      }]},
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { ...pluginBase, legend: { ...pluginBase.legend, position: 'top' } },
        scales: { y: { ...axis(), beginAtZero: true }, x: axis() },
      },
    };

  // ── Databases / Document Types / Open Access ───────────────────────────
  } else if (['source_types','document_types','open_access'].includes(type)) {
    const isDoughnut = type === 'document_types';
    config = {
      type: isDoughnut ? 'doughnut' : 'pie',
      data: { labels, datasets: [{ data: values, backgroundColor: colors, borderWidth: 2, borderColor: 'rgba(15,23,41,0.6)', hoverOffset: 8 }] },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { ...pluginBase, legend: { position: 'bottom', labels: { color: '#94a3b8', font: { size: 11 }, padding: 14, usePointStyle: true } } },
        cutout: isDoughnut ? '55%' : '0%',
      },
    };

  // ── Horizontal bars: journals, authors, countries, publishers ──────────────
  } else if (['top_journals','top_authors','top_countries','top_publishers','citations_distribution'].includes(type)) {
    const isHoriz = type !== 'citations_distribution';
    const isCit   = type === 'citations_distribution';

    // One color per chart type with an opacity gradient (bigger bar = more vivid)
    const colorMap = {
      top_journals:            [0, 212, 255],
      top_authors:             [0, 229, 160],
      top_countries:           [251, 191,  36],
      top_publishers:          [232, 121, 249],
      citations_distribution:  [116, 192, 252],
    };
    const [r2, g2, b2] = colorMap[type] || [0, 212, 255];
    const maxVal = Math.max(...values, 1);
    // Opacity proportional to the value: bigger bar becomes more solid
    const gradBg = values.map(v => `rgba(${r2},${g2},${b2},${(0.35 + 0.55 * (v / maxVal)).toFixed(2)})`);

    config = {
      type: 'bar',
      data: { labels, datasets: [{
        label: bt('articles'),
        data: values,
        backgroundColor: gradBg,
        borderColor:   `rgba(${r2},${g2},${b2},0.9)`,
        borderWidth:   0,
        borderRadius:  isHoriz ? 3 : 4,
        maxBarThickness: isHoriz ? 26 : 40,
        minBarLength:  4,
      }]},
      options: {
        responsive: true, maintainAspectRatio: false,
        indexAxis: isHoriz ? 'y' : 'x',
        plugins: { ...pluginBase, legend: { display: false } },
        scales: isHoriz
          ? {
              x: { ...axis(), beginAtZero: true, max: Math.ceil(maxVal * 1.18),
                   ticks: { color: '#94a3b8', font: { size: 11 }, stepSize: 1 } },
              y: { ...axis(), ticks: { color: '#94a3b8', font: { size: 11 } } },
            }
          : {
              y: { ...axis(), beginAtZero: true,
                   ...(isCit ? { type: 'logarithmic',
                     title: { display: true, text: bt('articles_log'), color: '#94a3b8', font:{ size:10 } } } : {}) },
              x: axis(),
            },
      },
    };

  // ── Frequent Keywords (horizontal bar) ─────────────────────────────────
  } else if (type === 'keywords_frequency') {
    config = {
      type: 'bar',
      data: { labels, datasets: [{ label: bt('occurrences'), data: values, backgroundColor: colors, borderWidth: 0, borderRadius: 4 }] },
      options: {
        responsive: true, maintainAspectRatio: false,
        indexAxis: 'y',
        plugins: { ...pluginBase, legend: { display: false } },
        scales: { x: { ...axis(), beginAtZero: true }, y: { ...axis(), ticks: { color: '#94a3b8', font: { size: 10 } } } },
      },
    };

  // ── Keywords per Year ──────────────────────────────────────────────────────
  } else if (type === 'keywords_by_year') {
    const kColors = bibColors(data.datasets.length);
    config = {
      type: 'bar',
      data: {
        labels,
        datasets: data.datasets.map((ds, i) => ({
          label: ds.label,
          data: ds.data,
          backgroundColor: kColors[i],
          borderColor: kColors[i].replace('0.82', '1').replace('.82)', ')'),
          borderWidth: 1,
          borderRadius: 2,
        })),
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { ...pluginBase },
        scales: {
          y: { ...axis(), beginAtZero: true, stacked: true },
          x: { ...axis(), stacked: true },
        },
      },
    };

  } else { return; }

  chartInstances[type] = new Chart(ctx, config);
}

// ─── Loading each chart ────────────────────────────────────────────────────

async function loadChart(type) {
  try {
    // FIXED: uses 'chart=' instead of 'type='
    const r = await fetch(`api/bibliometrics.php?project_id=${BIB_PROJECT_ID}&chart=${type}&scope=${getScope()}&source_id=${getSourceId()}`);
    const d = await r.json();
    if (d.error) return;
    BIB_RAW[type] = d[type];
    const data = transformData(type, d[type]);
    if (data) buildChart(type, data);
  } catch(e) { console.error('Erro ao carregar ' + type, e); }
}

// Redraws, in the current language, everything built in this file.
// Called by bib-i18n.js when the user switches the language flag.
function bibRerenderPageCharts() {
  Object.keys(BIB_RAW).forEach(type => {
    if (type.charAt(0) === '_') return;
    const data = transformData(type, BIB_RAW[type]);
    if (data) buildChart(type, data);
  });
  if (BIB_RAW._top_cited)  renderTopCited(BIB_RAW._top_cited);
  if (BIB_RAW._coauth)     renderCoauthorship(BIB_RAW._coauth);
  if (BIB_RAW._kwnet)      renderKeywordNetwork(BIB_RAW._kwnet);
}

// ─── Table: most cited articles ────────────────────────────────────────────

async function loadTopCited() {
  const el = document.getElementById('top-cited-table');
  try {
    const r = await fetch(`api/bibliometrics.php?project_id=${BIB_PROJECT_ID}&chart=top_cited&scope=${getScope()}&source_id=${getSourceId()}&limit=10`);
    const d = await r.json();
    BIB_RAW._top_cited = d.top_cited || [];
    BIB_RAW.top_cited  = BIB_RAW._top_cited;
    renderTopCited(BIB_RAW._top_cited);
  } catch(e) {
    el.innerHTML = `<div class="alert alert-error">${bt('error')}: ${e.message}</div>`;
  }
}

function renderTopCited(rows) {
  const el = document.getElementById('top-cited-table');
  if (!el) return;
  try {
    if (!rows.length) {
      el.innerHTML = `<div class="empty-state" style="padding:24px"><i class="fa fa-database"></i><p style="font-size:0.82rem">${bt('topcited_empty')}</p></div>`;
      return;
    }
    const th = (txt, align) =>
      `<th style="text-align:${align};padding:8px 12px;border-bottom:1px solid var(--border);color:var(--text-muted);font-weight:600">${txt}</th>`;
    let html = '<table style="width:100%;border-collapse:collapse;font-size:0.82rem"><thead><tr>'
      + th('#', 'left')
      + th(bt('title_col'),   'left')
      + th(bt('authors_col'), 'left')
      + th(bt('year'),        'center')
      + th(bt('citations'),   'center')
      + '</tr></thead><tbody>';

    rows.forEach((a, i) => {
      const titleEl = a.doi
        ? `<a href="https://doi.org/${escAttr(a.doi)}" target="_blank" style="color:var(--primary);text-decoration:none" title="${escAttr(a.title)}">${escHtml((a.title||'').substring(0,90))}${a.title.length>90?'…':''}</a>`
        : `<span title="${escAttr(a.title)}">${escHtml((a.title||'').substring(0,90))}${(a.title||'').length>90?'…':''}</span>`;
      const authShort = (a.authors||'').split(';')[0].trim() + ((a.authors||'').includes(';') ? ' et al.' : '');
      html += `<tr style="border-bottom:1px solid var(--border);${i%2===0?'':'background:rgba(255,255,255,0.02)'}">
        <td style="padding:8px 12px;color:var(--text-muted);text-align:center;font-weight:700">${i+1}</td>
        <td style="padding:8px 12px;font-weight:500">${titleEl}</td>
        <td style="padding:8px 12px;color:var(--text-muted)">${escHtml(authShort)}</td>
        <td style="padding:8px 12px;text-align:center">${a.year||'—'}</td>
        <td style="padding:8px 12px;text-align:center">
          <span style="background:rgba(0,212,255,0.15);color:#00D4FF;border-radius:12px;padding:2px 10px;font-weight:700">${a.cited_by}</span>
        </td>
      </tr>`;
    });
    html += '</tbody></table>';
    el.innerHTML = html;
  } catch(e) {
    el.innerHTML = `<div class="alert alert-error">${bt('error')}: ${e.message}</div>`;
  }
}

// ─── Networks ────────────────────────────────────────────────────────────────────

async function loadCoauthorship() {
  const container = document.getElementById('network-coauthorship');
  container.innerHTML = '<div class="text-center" style="padding:60px"><div class="spinner"></div></div>';
  try {
    const minA = document.getElementById('coauth-min').value;
    // FIXED: uses chart= and min_articles=
    const r = await fetch(`api/bibliometrics.php?project_id=${BIB_PROJECT_ID}&chart=coauthorship&scope=${getScope()}&source_id=${getSourceId()}&min_articles=${minA}`);
    const d = await r.json();
    BIB_RAW._coauth = d;
    BIB_RAW.coauthorship = d;
    renderCoauthorship(d);
  } catch(e) {
    container.innerHTML = `<div class="alert alert-error">${bt('error')}: ${e.message}</div>`;
  }
}

function renderCoauthorship(d) {
  const container = document.getElementById('network-coauthorship');
  if (!container) return;
  if (!d || !d.nodes || !d.nodes.length) {
    container.innerHTML = `<div class="empty-state"><i class="fa fa-network-wired"></i><p>${bt('net_few_articles')}</p></div>`;
    return;
  }
  container.innerHTML = '';
  // FIXED: maps full_name → label and article_count → value
  const nodes = d.nodes.map(n => ({
    id:    n.id,
    label: n.full_name,
    value: n.article_count,
    title: `${n.full_name}\n` + bt('n_articles', { n: n.article_count }),
  }));
  // FIXED: maps source/target → from/to
  const edges = d.edges.map(e => ({
    from: e.source, to: e.target, value: +e.weight,
    title: bt('net_joint_articles', { n: e.weight }),
  }));
  buildNetwork(container, nodes, edges, { nodeColor:'#00D4FF', edgeColor:'rgba(0,212,255,0.25)' });
}

async function loadKeywordNetwork() {
  const container = document.getElementById('network-keywords');
  container.innerHTML = '<div class="text-center" style="padding:60px"><div class="spinner"></div></div>';
  try {
    const minO = document.getElementById('kwnet-min').value;
    // FIXED: uses chart= and min_occ=
    const r = await fetch(`api/bibliometrics.php?project_id=${BIB_PROJECT_ID}&chart=keyword_cooccurrence&scope=${getScope()}&source_id=${getSourceId()}&min_occ=${minO}`);
    const d = await r.json();
    BIB_RAW._kwnet = d;
    BIB_RAW.keyword_cooccurrence = d;
    renderKeywordNetwork(d);
  } catch(e) {
    container.innerHTML = `<div class="alert alert-error">${bt('error')}: ${e.message}</div>`;
  }
}

function renderKeywordNetwork(d) {
  const container = document.getElementById('network-keywords');
  if (!container) return;
  if (!d || !d.nodes || !d.nodes.length) {
    container.innerHTML = `<div class="empty-state"><i class="fa fa-diagram-project"></i><p>${bt('net_few_occurrences')}</p></div>`;
    return;
  }
  container.innerHTML = '';
  // Keywords use strings as IDs — build a numeric index
  const kwMap = {};
  d.nodes.forEach((n, i) => { kwMap[n.keyword] = i; });
  const nodes = d.nodes.map((n, i) => ({
    id:    i,
    label: n.keyword,
    value: n.freq,
    title: `"${n.keyword}"\n` + bt('net_occurrences', { n: n.freq }),
  }));
  const edges = d.edges.map(e => ({
    from:  kwMap[e.source],
    to:    kwMap[e.target],
    value: +e.weight,
    title: bt('net_cooccurrences', { n: e.weight }),
  }));
  buildNetwork(container, nodes, edges, { nodeColor:'#00e5a0', edgeColor:'rgba(0,229,160,0.2)' });
}

function buildNetwork(container, nodes, edges, opts) {
  if (typeof vis === 'undefined') {
    container.innerHTML = `<div class="alert alert-error">${bt('net_missing')}</div>`;
    return;
  }

  // Detects whether we're in light theme to adjust the font color
  const isLight   = document.body.classList.contains('theme-light');
  const fontColor = isLight ? '#1e293b' : '#f1f5f9';
  const strokeCol = isLight ? 'rgba(255,255,255,0.95)' : 'rgba(15,23,41,0.9)';

  const visNodes = new vis.DataSet(nodes.map(n => ({
    id:    n.id,
    label: n.label,
    value: n.value || 1,
    title: n.title,
    color: {
      background: opts.nodeColor + '55',
      border:     opts.nodeColor,
      highlight:  { background: opts.nodeColor + 'bb', border: isLight ? '#0f172a' : '#fff' },
      hover:      { background: opts.nodeColor + '99', border: opts.nodeColor },
    },
    font: {
      color:       fontColor,
      size:        15,
      strokeWidth: 4,
      strokeColor: strokeCol,
      bold:        { color: fontColor, size: 15 },
    },
    shape:       'dot',
    borderWidth: 2,
  })));

  const visEdges = new vis.DataSet(edges.map(e => ({
    from:  e.from,
    to:    e.to,
    value: e.value || 1,
    title: e.title,
    color: { color: opts.nodeColor + '55', highlight: opts.nodeColor + 'cc', opacity: 0.7 },
    smooth: { type: 'continuous' },
    width: Math.max(1, (e.value || 1)),
  })));

  new vis.Network(container, { nodes: visNodes, edges: visEdges }, {
    physics: {
      stabilization:  { iterations: 250 },
      barnesHut:      { springLength: 160, damping: 0.15, gravitationalConstant: -4000, springConstant: 0.04 },
    },
    interaction: { hover: true, tooltipDelay: 80, zoomView: true, dragView: true },
    nodes:       { scaling: { min: 16, max: 55 }, font: { size: 15 } },
    edges:       { scaling: { min: 1, max: 12 } },
  });
}

// ─── Reload everything ─────────────────────────────────────────────────────────

async function reloadAll() {
  const charts = [
    'publications_year','source_types','document_types','open_access',
    'top_journals','top_authors','top_countries','top_publishers',
    'citations_distribution',
    'keywords_frequency','keywords_by_year',
  ];
  await Promise.all(charts.map(t => loadChart(t)));
  loadTopCited();
  loadCoauthorship();
  loadKeywordNetwork();
  // ── New classic bibliometric analyses ────────────────────
  loadAdvanced();
}

// ─── Advanced bibliometric analyses ───────────────────────────────────────
function bibErrCard(canvasId, msg) {
  const el = document.getElementById(canvasId);
  if (el) el.insertAdjacentHTML('afterend', `<p style="color:#f87171;font-size:.78rem;padding:8px 0">${msg}</p>`);
}

async function loadAdvanced() {
  const scope = getScope();
  const baseUrl = `api/bibliometrics.php?project_id=${BIB_PROJECT_ID}&scope=${scope}&source_id=${getSourceId()}`;

  // Lotka's Law
  fetch(`${baseUrl}&chart=lotka`)
    .then(r => r.json())
    .then(d => { if (d.lotka) { BIB_RAW.lotka = d.lotka; buildLotkaChart('chart-lotka', d.lotka); } })
    .catch(e => { console.error('lotka', e); bibErrCard('chart-lotka', 'Erro: ' + e.message); });

  // Bradford's Law
  fetch(`${baseUrl}&chart=bradford`)
    .then(r => r.json())
    .then(d => { if (d.bradford) { BIB_RAW.bradford = d.bradford; buildBradfordChart('chart-bradford', d.bradford); } })
    .catch(e => { console.error('bradford', e); bibErrCard('chart-bradford', 'Erro: ' + e.message); });

  // Annual production + CAGR
  fetch(`${baseUrl}&chart=annual_growth`)
    .then(r => r.json())
    .then(d => { if (d.annual_growth) { BIB_RAW.annual_growth = d.annual_growth; buildAnnualGrowthChart('chart-annual_growth', d.annual_growth); } })
    .catch(e => { console.error('annual_growth', e); bibErrCard('chart-annual_growth', 'Erro: ' + e.message); });

  // Word Cloud — uses requestAnimationFrame to ensure dimensions have been computed
  fetch(`${baseUrl}&chart=word_cloud&limit=100`)
    .then(r => r.json())
    .then(d => { if (d.word_cloud) { BIB_RAW.word_cloud = d.word_cloud; requestAnimationFrame(() => buildWordCloud('word-cloud-container', d.word_cloud)); } })
    .catch(e => console.error('word_cloud', e));

  // Trend Topics
  fetch(`${baseUrl}&chart=trend_topics&min_freq=2&limit=25`)
    .then(r => r.json())
    .then(d => { if (d.trend_topics) { BIB_RAW.trend_topics = d.trend_topics; requestAnimationFrame(() => buildTrendTopicsChart('chart-trend_topics', d.trend_topics)); } })
    .catch(e => console.error('trend_topics', e));

  // Three-Field Plot — SVG needs the container's real dimensions
  fetch(`${baseUrl}&chart=three_field&n_top=12`)
    .then(r => r.json())
    .then(d => { if (d.three_field) { BIB_RAW.three_field = d.three_field; requestAnimationFrame(() => buildThreeFieldPlot('three-field-container', d.three_field)); } })
    .catch(e => console.error('three_field', e));

  // Citations per year
  fetch(`${baseUrl}&chart=citations_per_year`)
    .then(r => r.json())
    .then(d => { if (d.citations_per_year) { BIB_RAW.citations_per_year = d.citations_per_year; buildCitationsPerYearChart('chart-citations_per_year', d.citations_per_year); } })
    .catch(e => console.error('citations_per_year', e));

  // World map of production
  fetch(`${baseUrl}&chart=country_production`)
    .then(r => r.json())
    .then(d => { if (d.country_production) { BIB_RAW.country_production = d.country_production; requestAnimationFrame(() => buildCountryMap('map-country_production', d.country_production)); } })
    .catch(e => console.error('country_production', e));

  // Local impact indices (h, g, m)
  fetch(`${baseUrl}&chart=author_impact&limit=15`)
    .then(r => r.json())
    .then(d => { if (d.author_impact) { BIB_RAW.author_impact = d.author_impact; buildImpactTable('table-author_impact', d.author_impact, 'author'); } })
    .catch(e => console.error('author_impact', e));

  fetch(`${baseUrl}&chart=source_impact&limit=15`)
    .then(r => r.json())
    .then(d => { if (d.source_impact) { BIB_RAW.source_impact = d.source_impact; buildImpactTable('table-source_impact', d.source_impact, 'source'); } })
    .catch(e => console.error('source_impact', e));

  // Callon's thematic map
  fetch(`${baseUrl}&chart=thematic_map&min_freq=2&limit=50`)
    .then(r => r.json())
    .then(d => { if (d.thematic_map) { BIB_RAW.thematic_map = d.thematic_map; buildThematicMap('chart-thematic_map', d.thematic_map); } })
    .catch(e => console.error('thematic_map', e));

  // Top Institutions
  fetch(`${baseUrl}&chart=top_institutions&limit=20`)
    .then(r => r.json())
    .then(d => {
      if (d.debug) console.warn('top_institutions PHP error:', d.debug);
      BIB_RAW.top_institutions = d.top_institutions || [];
      buildTopInstitutionsChart('chart-top_institutions', d.top_institutions || []);
    })
    .catch(e => {
      const c = document.getElementById('chart-top_institutions');
      if (c) c.insertAdjacentHTML('afterend', `<p style="color:#f87171;font-size:.78rem">Erro: ${e.message}</p>`);
    });

  // MCP vs SCP
  fetch(`${baseUrl}&chart=mcp_scp`)
    .then(r => r.json())
    .then(d => { if (d.mcp_scp) { BIB_RAW.mcp_scp = d.mcp_scp; buildMcpScpChart('chart-mcp_scp', d); } })
    .catch(e => console.error('mcp_scp', e));

  // Author production over time
  fetch(`${baseUrl}&chart=author_over_time&top=10`)
    .then(r => r.json())
    .then(d => { if (d.author_over_time) { BIB_RAW.author_over_time = d.author_over_time; buildAuthorOverTimeChart('chart-author_over_time', d); } })
    .catch(e => console.error('author_over_time', e));

  // Country collaboration network
  fetch(`${baseUrl}&chart=country_collab&min_weight=1`)
    .then(r => r.json())
    .then(d => { BIB_RAW.country_collab = d; requestAnimationFrame(() => buildCountryCollabChart('country-collab-container', d)); })
    .catch(e => console.error('country_collab', e));

  // Thematic evolution
  loadThematicEvolution();
}

function loadThematicEvolution() {
  const scope = getScope();
  const periods = document.getElementById('evol-periods')?.value || 2;
  const baseUrl = `api/bibliometrics.php?project_id=${BIB_PROJECT_ID}&scope=${scope}&source_id=${getSourceId()}`;
  document.getElementById('thematic-evolution-container').innerHTML = '<div class="spinner"></div>';
  fetch(`${baseUrl}&chart=thematic_evolution&min_freq=2&periods=${periods}`)
    .then(r => {
      if (!r.ok) throw new Error(`HTTP ${r.status}`);
      return r.text();
    })
    .then(txt => {
      let d;
      try { d = JSON.parse(txt); } catch(pe) {
        throw new Error('Resposta inválida do servidor: ' + txt.slice(0, 120));
      }
      if (d.debug) console.warn('thematic_evolution PHP error:', d.debug);
      BIB_RAW.thematic_evolution = d.thematic_evolution || [];
      buildThematicEvolution('thematic-evolution-container', d);
    })
    .catch(e => {
      document.getElementById('thematic-evolution-container').innerHTML =
        `<p style="color:#f87171;padding:16px">${bt('error')}: ${e.message}</p>`;
    });
}

// Start
loadSourcesDropdown();
reloadAll();
</script>
