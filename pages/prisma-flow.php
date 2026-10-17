<?php
/**
 * PRISMA-SLR - Diagrama de Fluxo PRISMA 2020
 * Renderização em três estilos (publicação P&B, colorido, escuro) com
 * exportação vetorial (SVG) e raster em 150/300/600 dpi para submissão.
 */
if (!$projectId): ?>
<div class="empty-state"><i class="fa fa-triangle-exclamation"></i>
  <p>Selecione um projeto para ver o diagrama PRISMA.</p></div>
<?php else: ?>

<div class="page-header">
  <div>
    <h1 class="page-title"><i class="fa fa-diagram-project"></i> Diagrama PRISMA 2020</h1>
    <p class="page-subtitle" id="pf-subtitle">Fluxo de identificação, triagem e inclusão de estudos — modelo PRISMA 2020 para bases de dados.</p>
  </div>
  <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
    <button class="btn btn-outline" onclick="exportDiagram('png')">
      <i class="fa fa-image"></i> <span id="pf-btn-png">Exportar PNG</span>
    </button>
    <button class="btn btn-ghost" onclick="exportDiagram('svg')">
      <i class="fa fa-file-code"></i> <span id="pf-btn-svg">Exportar SVG</span>
    </button>
    <button class="btn btn-ghost" onclick="loadFlow()">
      <i class="fa fa-rotate"></i> <span id="pf-btn-refresh">Atualizar</span>
    </button>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 260px;gap:20px;align-items:start;min-width:0">
  <!-- Diagrama: ocupa todo espaço restante, scroll interno se SVG for largo -->
  <div class="card" style="min-width:0;overflow:hidden;padding:20px 24px 24px">

    <!-- Barra de opções de figura -->
    <div class="pf-toolbar">
      <div class="pf-tool">
        <label id="pf-lbl-style">Estilo</label>
        <div class="pf-seg" id="pf-style-seg">
          <button type="button" data-style="publication" onclick="setPfStyle('publication')"><span id="pf-st-pub">Publicação</span></button>
          <button type="button" data-style="color"       onclick="setPfStyle('color')"><span id="pf-st-col">Colorido</span></button>
          <button type="button" data-style="dark"        onclick="setPfStyle('dark')"><span id="pf-st-dark">Escuro</span></button>
        </div>
      </div>
      <div class="pf-tool">
        <label id="pf-lbl-dpi">Resolução (PNG)</label>
        <select id="pf-dpi" class="pf-select" onchange="setPfDpi(this.value)">
          <option value="150">150 dpi</option>
          <option value="300" selected>300 dpi</option>
          <option value="600">600 dpi</option>
        </select>
      </div>
      <div class="pf-tool">
        <label id="pf-lbl-opts">Conteúdo</label>
        <div class="pf-checks">
          <label class="pf-check"><input type="checkbox" id="pf-opt-reasons" checked onchange="renderCurrent()">
            <span id="pf-opt-reasons-lbl">Motivos na figura</span></label>
          <label class="pf-check"><input type="checkbox" id="pf-opt-caption" onchange="renderCurrent()">
            <span id="pf-opt-caption-lbl">Legenda</span></label>
        </div>
      </div>
    </div>

    <div id="prisma-loading" class="text-center" style="padding:60px"><div class="spinner"></div></div>
    <div style="overflow-x:auto">
      <div id="prisma-diagram" class="pf-paper notranslate" translate="no" style="display:none"></div>
    </div>
    <p class="pf-hint" id="pf-hint">
      SVG = vetorial (recomendado para o periódico). PNG a 300 dpi atende à maioria das diretrizes de submissão.
    </p>
  </div>

  <!-- Painel de dados: coluna fixa 260px, nunca sai da tela -->
  <div>
    <div class="card" style="margin-bottom:12px">
      <h3 class="card-title" style="font-size:0.85rem" id="pf-panel-id">Identificação</h3>
      <div id="detail-identification" class="detail-section"></div>
    </div>
    <div class="card" style="margin-bottom:12px">
      <h3 class="card-title" style="font-size:0.85rem" id="pf-panel-sc">Triagem</h3>
      <div id="detail-screening" class="detail-section"></div>
    </div>
    <div class="card">
      <h3 class="card-title" style="font-size:0.85rem" id="pf-panel-inc">Incluídos</h3>
      <div id="detail-included" class="detail-section"></div>
    </div>
  </div>
</div>

<!-- Canvas oculto para export -->
<canvas id="export-canvas" style="display:none"></canvas>

<style>
.detail-section { font-size:0.82rem; }
.detail-row { display:flex; justify-content:space-between; align-items:flex-start; gap:8px; padding:5px 0; border-bottom:1px solid var(--border); }
.detail-row:last-child { border-bottom:none; }
.detail-row > span:first-child { flex:1; min-width:0; word-break:break-word; overflow-wrap:break-word; line-height:1.35; }
.detail-row > span:last-child { flex-shrink:0; text-align:right; }
.detail-num { font-weight:700; color:var(--primary); }
.prisma-box { cursor:default; }

/* ── Barra de opções da figura ───────────────────────────── */
.pf-toolbar { display:flex; flex-wrap:wrap; gap:22px; align-items:flex-end;
  padding:0 0 16px; margin-bottom:18px; border-bottom:1px solid var(--border); }
.pf-tool { display:flex; flex-direction:column; gap:6px; }
.pf-tool > label { font-size:.68rem; font-weight:700; letter-spacing:.06em;
  text-transform:uppercase; color:var(--text-muted); }
.pf-seg { display:inline-flex; border:1px solid var(--border); border-radius:8px; overflow:hidden; }
.pf-seg button { background:transparent; border:none; border-right:1px solid var(--border);
  padding:6px 13px; font-size:.8rem; font-family:inherit; color:var(--text-secondary);
  cursor:pointer; transition:var(--transition); }
.pf-seg button:last-child { border-right:none; }
.pf-seg button:hover { background:var(--bg-hover); color:var(--text-primary); }
.pf-seg button.active { background:var(--primary); color:var(--text-inverse); font-weight:600; }
.pf-select { padding:6px 10px; font-size:.8rem; font-family:inherit; border-radius:8px;
  border:1px solid var(--border); background:var(--bg-input); color:var(--text-primary); cursor:pointer; }
.pf-checks { display:flex; gap:14px; flex-wrap:wrap; }
.pf-check { display:inline-flex; align-items:center; gap:6px; font-size:.8rem;
  color:var(--text-secondary); cursor:pointer; }
.pf-check input { accent-color:var(--primary); cursor:pointer; }
.pf-hint { margin:14px 0 0; font-size:.74rem; color:var(--text-muted); line-height:1.5; }

/* Área "papel": mostra a figura exatamente como será exportada */
.pf-paper { display:inline-block; }
.pf-paper.pf-light { background:#fff; padding:10px; border-radius:4px;
  box-shadow:0 1px 10px rgba(0,0,0,.16); }

@media print {
  .pf-toolbar, .pf-hint { display:none !important; }
  #prisma-diagram svg { background:#fff !important; }
}
</style>

<script>
const PF_PROJECT_ID = <?= $projectId ?>;

// ── Translations ────────────────────────────────────────────────
const PF_T = {
  // Rótulos das caixas seguem o modelo oficial PRISMA 2020 para revisões novas que
  // buscaram apenas em bases de dados e registros (Page et al., 2021) e suas
  // traduções publicadas (PT: Galvão et al., 2022; ES: Yepes-Nuñez et al., 2021).
  pt: {
    subtitle:     'Fluxo de identificação, triagem e inclusão de estudos — modelo PRISMA 2020 para bases de dados.',
    btn_png:      'Exportar PNG',
    btn_svg:      'Exportar SVG',
    btn_refresh:  'Atualizar',
    lbl_style:    'Estilo',
    st_pub:       'Publicação',
    st_col:       'Colorido',
    st_dark:      'Escuro',
    lbl_dpi:      'Resolução (PNG)',
    lbl_opts:     'Conteúdo',
    opt_reasons:  'Motivos na figura',
    opt_caption:  'Legenda',
    hint:         'SVG = vetorial (recomendado para o periódico). PNG a 300 dpi atende à maioria das diretrizes de submissão.',
    panel_id:     'Identificação',
    panel_sc:     'Triagem',
    panel_inc:    'Incluídos',
    total_identified:      'Registros identificados',
    duplicates_removed:    'Duplicatas removidas',
    unique_screening:      'Registros únicos',
    records_screened:      'Registros triados',
    records_excluded:      'Registros excluídos',
    reports_sought:        'Relatos buscados para recuperação',
    reports_not_retrieved: 'Relatos não recuperados',
    reports_assessed:      'Relatos avaliados para elegibilidade',
    reports_excluded:      'Relatos excluídos',
    awaiting_ft:           'Aguardando decisão do texto completo',
    pending_sc:            'Aguardando triagem',
    studies_included:      'Estudos incluídos na revisão',
    no_reason:             'Sem motivo informado',
    other_sources:         'Outras fontes',
    d_identified_from:     'Registros identificados de:',
    d_databases:           'Bases de dados',
    d_removed_before:      'Registros removidos antes da triagem:',
    d_dup_removed:         'Registros duplicados removidos',
    d_screened:            'Registros triados',
    d_excluded:            'Registros excluídos',
    d_sought:              'Relatos buscados para recuperação',
    d_not_retrieved:       'Relatos não recuperados',
    d_assessed:            'Relatos avaliados para elegibilidade',
    d_reports_excluded:    'Relatos excluídos:',
    d_included:            'Estudos incluídos na revisão',
    d_phase_id:      'IDENTIFICAÇÃO',
    d_phase_sc:      'TRIAGEM',
    d_phase_inc:     'INCLUÍDOS',
    d_no_db:         'Bases não disponíveis',
    d_more:          'outros motivos',
    d_caption:       'Fluxograma PRISMA 2020',
    filename:        'prisma-flow',
  },
  en: {
    subtitle:     'Identification, screening and inclusion of studies — PRISMA 2020 flow diagram for databases.',
    btn_png:      'Export PNG',
    btn_svg:      'Export SVG',
    btn_refresh:  'Refresh',
    lbl_style:    'Style',
    st_pub:       'Publication',
    st_col:       'Color',
    st_dark:      'Dark',
    lbl_dpi:      'Resolution (PNG)',
    lbl_opts:     'Content',
    opt_reasons:  'Reasons in figure',
    opt_caption:  'Caption',
    hint:         'SVG = vector (recommended for the journal). PNG at 300 dpi meets most submission guidelines.',
    panel_id:     'Identification',
    panel_sc:     'Screening',
    panel_inc:    'Included',
    total_identified:      'Records identified',
    duplicates_removed:    'Duplicates removed',
    unique_screening:      'Unique records',
    records_screened:      'Records screened',
    records_excluded:      'Records excluded',
    reports_sought:        'Reports sought for retrieval',
    reports_not_retrieved: 'Reports not retrieved',
    reports_assessed:      'Reports assessed for eligibility',
    reports_excluded:      'Reports excluded',
    awaiting_ft:           'Awaiting full-text decision',
    pending_sc:            'Awaiting screening',
    studies_included:      'Studies included in review',
    no_reason:             'No reason given',
    other_sources:         'Other sources',
    d_identified_from:     'Records identified from:',
    d_databases:           'Databases',
    d_removed_before:      'Records removed before screening:',
    d_dup_removed:         'Duplicate records removed',
    d_screened:            'Records screened',
    d_excluded:            'Records excluded',
    d_sought:              'Reports sought for retrieval',
    d_not_retrieved:       'Reports not retrieved',
    d_assessed:            'Reports assessed for eligibility',
    d_reports_excluded:    'Reports excluded:',
    d_included:            'Studies included in review',
    d_phase_id:      'IDENTIFICATION',
    d_phase_sc:      'SCREENING',
    d_phase_inc:     'INCLUDED',
    d_no_db:         'Databases not available',
    d_more:          'other reasons',
    d_caption:       'PRISMA 2020 flow diagram',
    filename:        'prisma-flow-en',
  },
  es: {
    subtitle:     'Identificación, cribado e inclusión de estudios — diagrama de flujo PRISMA 2020 para bases de datos.',
    btn_png:      'Exportar PNG',
    btn_svg:      'Exportar SVG',
    btn_refresh:  'Actualizar',
    lbl_style:    'Estilo',
    st_pub:       'Publicación',
    st_col:       'Color',
    st_dark:      'Oscuro',
    lbl_dpi:      'Resolución (PNG)',
    lbl_opts:     'Contenido',
    opt_reasons:  'Motivos en la figura',
    opt_caption:  'Leyenda',
    hint:         'SVG = vectorial (recomendado para la revista). PNG a 300 dpi cumple la mayoría de las directrices de envío.',
    panel_id:     'Identificación',
    panel_sc:     'Cribado',
    panel_inc:    'Incluidos',
    total_identified:      'Registros identificados',
    duplicates_removed:    'Duplicados eliminados',
    unique_screening:      'Registros únicos',
    records_screened:      'Registros cribados',
    records_excluded:      'Registros excluidos',
    reports_sought:        'Publicaciones buscadas para su recuperación',
    reports_not_retrieved: 'Publicaciones no recuperadas',
    reports_assessed:      'Publicaciones evaluadas para decidir su elegibilidad',
    reports_excluded:      'Publicaciones excluidas',
    awaiting_ft:           'Pendientes de decisión a texto completo',
    pending_sc:            'Pendientes de cribado',
    studies_included:      'Estudios incluidos en la revisión',
    no_reason:             'Sin motivo indicado',
    other_sources:         'Otras fuentes',
    d_identified_from:     'Registros identificados desde:',
    d_databases:           'Bases de datos',
    d_removed_before:      'Registros eliminados antes del cribado:',
    d_dup_removed:         'Registros duplicados eliminados',
    d_screened:            'Registros cribados',
    d_excluded:            'Registros excluidos',
    d_sought:              'Publicaciones buscadas para su recuperación',
    d_not_retrieved:       'Publicaciones no recuperadas',
    d_assessed:            'Publicaciones evaluadas para decidir su elegibilidad',
    d_reports_excluded:    'Publicaciones excluidas:',
    d_included:            'Estudios incluidos en la revisión',
    d_phase_id:      'IDENTIFICACIÓN',
    d_phase_sc:      'CRIBADO',
    d_phase_inc:     'INCLUIDOS',
    d_no_db:         'Bases no disponibles',
    d_more:          'otros motivos',
    d_caption:       'Diagrama de flujo PRISMA 2020',
    filename:        'prisma-flow-es',
  },
};

function getT() { return PF_T[window.PRISMA_LANG || 'pt']; }

// Motivos de exclusão são texto livre digitado pelo pesquisador (geralmente em
// português) e não fazem parte do dicionário de tradução da interface — por
// isso precisam ser traduzidos à parte quando o idioma ativo não é PT.
const REASON_TRANSLATE_CACHE = {};
async function translateReasonsIfNeeded(prisma) {
  const lang      = window.PRISMA_LANG || 'pt';
  const targetMap = { en: 'en', es: 'es' };
  const target    = targetMap[lang];
  const sc        = prisma?.screening || {};
  const lists     = [sc.records_reasons, sc.not_retrieved_reasons, sc.reports_reasons];

  if (!target) {
    // Voltou para PT — restaura o texto original sem precisar traduzir de novo
    lists.forEach(list => (list || []).forEach(r => { if (r._original) r.reason = r._original; }));
    return;
  }

  const tasks = [];
  lists.forEach(list => {
    (list || []).forEach(r => {
      if (!r.reason) return;
      if (!r._original) r._original = r.reason;
      const key = `${target}::${r._original}`;
      if (REASON_TRANSLATE_CACHE[key]) {
        r.reason = REASON_TRANSLATE_CACHE[key];
        return;
      }
      tasks.push(
        fetch(`api/translate.php?tl=${target}&sl=pt-BR&text=${encodeURIComponent(r._original)}`)
          .then(res => res.json())
          .then(d => {
            if (!d.error && d.translated) {
              REASON_TRANSLATE_CACHE[key] = d.translated;
              r.reason = d.translated;
            }
          })
          .catch(() => {})
      );
    });
  });

  if (tasks.length) await Promise.all(tasks);
}

function applyPFLang() {
  const t = getT();
  const set = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };
  set('pf-subtitle',    t.subtitle);
  set('pf-btn-png',     t.btn_png);
  set('pf-btn-svg',     t.btn_svg);
  set('pf-btn-refresh', t.btn_refresh);
  set('pf-panel-id',    t.panel_id);
  set('pf-panel-sc',    t.panel_sc);
  set('pf-panel-inc',   t.panel_inc);
  set('pf-lbl-style',   t.lbl_style);
  set('pf-st-pub',      t.st_pub);
  set('pf-st-col',      t.st_col);
  set('pf-st-dark',     t.st_dark);
  set('pf-lbl-dpi',     t.lbl_dpi);
  set('pf-lbl-opts',    t.lbl_opts);
  set('pf-opt-reasons-lbl', t.opt_reasons);
  set('pf-opt-caption-lbl', t.opt_caption);
  set('pf-hint',        t.hint);
}

// ── Estilos da figura ───────────────────────────────────────────
// "publication" é o padrão: preto-e-branco, seguro em impressão e em
// conversão para escala de cinza, tipografia Arial (aceita por periódicos).
const PF_STYLES = {
  publication: {
    paperLight: true,
    font:       "Arial, Helvetica, 'Liberation Sans', sans-serif",
    bg:         '#ffffff',
    radius:     2,
    numFirst:   false,          // rótulo em cima, "(n = X)" embaixo
    main:  { fill:'#ffffff', stroke:'#111111', sw:1.1, num:'#000000', label:'#111111', sub:'#333333' },
    db:    { fill:'#ffffff', stroke:'#111111', sw:1.1, num:'#000000', label:'#111111', sub:'#333333' },
    exc:   { fill:'#f2f2f2', stroke:'#111111', sw:1.1, num:'#000000', label:'#111111', sub:'#333333', dash:'' },
    arrow:    { color:'#111111', sw:1.2, dash:'' },
    arrowExc: { color:'#111111', sw:1.2, dash:'' },
    phase: { fill:'#e6e6e6', bar:'#111111', text:'#111111', opacity:1 },
    caption:  '#111111',
    icons:    false,
    fs: { num:12.5, label:11.5, sub:9.8, phase:10, caption:11 },
  },
  color: {
    paperLight: true,
    font:       "Arial, Helvetica, 'Liberation Sans', sans-serif",
    bg:         '#ffffff',
    radius:     6,
    numFirst:   true,
    main:  { fill:'#eef4ff', stroke:'#1b4a99', sw:1.4, num:'#123c7d', label:'#20344f', sub:'#4a5b70' },
    db:    { fill:'#eef4ff', stroke:'#1b4a99', sw:1.4, num:'#123c7d', label:'#20344f', sub:'#4a5b70' },
    exc:   { fill:'#fdeff1', stroke:'#a8324a', sw:1.4, num:'#8e2138', label:'#5a2430', sub:'#7d5560', dash:'4,3' },
    arrow:    { color:'#1b4a99', sw:1.5, dash:'' },
    arrowExc: { color:'#a8324a', sw:1.3, dash:'4,3' },
    phase: { fill:'#dce6f7', bar:'#1b4a99', text:'#123c7d', opacity:1 },
    caption:  '#20344f',
    icons:    false,
    fs: { num:20, label:11.5, sub:9.8, phase:9.5, caption:11 },
  },
  dark: {
    paperLight: false,
    font:       "Inter, Arial, Helvetica, sans-serif",
    bg:         '#0b1220',
    radius:     8,
    numFirst:   true,
    main:  { fill:'#0d1b3e', stroke:'#00D4FF', sw:2, num:'#00D4FF', label:'#9fb3cc', sub:'#7d90a8' },
    db:    { fill:'#0f1a3a', stroke:'#3b5bdb', sw:2, num:'#5b7bff', label:'#9fb3cc', sub:'#7d90a8' },
    exc:   { fill:'#2a0f15', stroke:'#ff4d6a', sw:2, num:'#ff4d6a', label:'#c8a3ac', sub:'#b08b95', dash:'5,3' },
    arrow:    { color:'#00D4FF', sw:2, dash:'' },
    arrowExc: { color:'#ff4d6a', sw:1.5, dash:'5,3' },
    phase: { fill:'#1a2b4d', bar:'#00D4FF', text:'#8fb6d9', opacity:1 },
    caption:  '#9fb3cc',
    icons:    true,
    fs: { num:21, label:11.5, sub:9.8, phase:9.5, caption:11 },
  },
};

let flowData  = null;
let pfStyle   = localStorage.getItem('pf-style') || 'publication';
let pfDpi     = parseInt(localStorage.getItem('pf-dpi') || '300', 10);
const PF_MAX_REASONS = 6;

function setPfStyle(name) {
  if (!PF_STYLES[name]) return;
  pfStyle = name;
  localStorage.setItem('pf-style', name);
  syncToolbar();
  renderCurrent();
}
function setPfDpi(v) {
  pfDpi = parseInt(v, 10) || 300;
  localStorage.setItem('pf-dpi', String(pfDpi));
}
function syncToolbar() {
  document.querySelectorAll('#pf-style-seg button').forEach(b =>
    b.classList.toggle('active', b.dataset.style === pfStyle));
  const dpiSel = document.getElementById('pf-dpi');
  if (dpiSel) dpiSel.value = String(pfDpi);
}
function renderCurrent() { if (flowData) renderDiagram(flowData.prisma); }

async function loadFlow() {
  document.getElementById('prisma-loading').style.display = 'flex';
  document.getElementById('prisma-diagram').style.display = 'none';
  try {
    const r = await fetch(`api/prisma-flow.php?project_id=${PF_PROJECT_ID}`);
    flowData = await r.json();
    if (flowData.error) throw new Error(flowData.message);
    applyPFLang();
    await translateReasonsIfNeeded(flowData.prisma);
    syncToolbar();
    renderDiagram(flowData.prisma);
    renderDetails(flowData.prisma);
  } catch(e) {
    document.getElementById('prisma-loading').innerHTML = `<div class="alert alert-error">Erro: ${e.message}</div>`;
  }
}

function renderDetails(d) {
  const t  = getT();
  const id = d.identification, sc = d.screening;
  const row = (label, n, opts = {}) =>
    `<div class="detail-row"${opts.indent ? ' style="padding-left:12px' + (opts.small ? ';font-size:0.75rem' : '') + '"' : ''}>`
    + `<span>${escHtml(label)}</span><span class="${opts.indent ? '' : 'detail-num'}"`
    + `${opts.color ? ` style="color:${opts.color}"` : ''}>${n}</span></div>`;
  const reasons = (list, small) => (list || []).map(r => row(r.reason || t.no_reason, r.total, { indent: true, small })).join('');

  // Identification
  let h = row(t.total_identified, id.total_identified);
  (id.by_database || []).forEach(b => { h += row(b.name || t.other_sources, b.count, { indent: true }); });
  h += row(t.duplicates_removed, id.duplicates_removed, { color: 'var(--accent-red)' });
  h += row(t.unique_screening, id.unique_after_dedup);
  document.getElementById('detail-identification').innerHTML = h;

  // Screening (título/resumo + recuperação + elegibilidade do texto completo)
  h  = row(t.records_screened, sc.records_screened);
  h += row(t.records_excluded, sc.records_excluded, { color: 'var(--accent-red)' }) + reasons(sc.records_reasons, true);
  h += row(t.reports_sought, sc.reports_sought);
  h += row(t.reports_not_retrieved, sc.reports_not_retrieved, { color: 'var(--accent-red)' }) + reasons(sc.not_retrieved_reasons, true);
  h += row(t.reports_assessed, sc.reports_assessed);
  h += row(t.reports_excluded, sc.reports_excluded, { color: 'var(--accent-red)' }) + reasons(sc.reports_reasons, false);
  if (sc.records_pending > 0)    h += row(t.pending_sc, sc.records_pending, { color: 'var(--text-muted)' });
  if (sc.awaiting_full_text > 0) h += row(t.awaiting_ft, sc.awaiting_full_text, { color: 'var(--text-muted)' });
  document.getElementById('detail-screening').innerHTML = h;

  // Included
  document.getElementById('detail-included').innerHTML =
    row(t.studies_included, d.included.studies_included, { color: 'var(--accent-green)' });
}

// ═══════════════════════════════════════════════════════════════════════════
//  Motor de desenho do diagrama
//  Layout dirigido pelo conteúdo: a altura de cada caixa é calculada a partir
//  do texto que ela contém, então nada é cortado nem sobreposto.
// ═══════════════════════════════════════════════════════════════════════════

// Geometria (unidades de usuário SVG = px a 96 dpi)
const PF_GEO = {
  M:        16,   // margem
  phaseW:   26,   // largura da barra de fase
  phaseGap: 14,
  mainW:    300,  // coluna principal
  colGap:   72,   // espaço entre coluna principal e caixas de exclusão
  excW:     300,  // coluna de exclusões
  rowGap:   34,   // espaço vertical entre linhas (comprimento da seta)
  padX:     12,
  padY:     11,
};
PF_GEO.mainX = PF_GEO.M + PF_GEO.phaseW + PF_GEO.phaseGap;
PF_GEO.excX  = PF_GEO.mainX + PF_GEO.mainW + PF_GEO.colGap;
PF_GEO.W     = PF_GEO.excX + PF_GEO.excW + PF_GEO.M;
PF_GEO.mainCX = PF_GEO.mainX + PF_GEO.mainW / 2;

/** Quebra de linha aproximada por métrica média de caractere (Arial). */
function pfWrap(text, maxW, fs, bold) {
  const k   = bold ? 0.585 : 0.525;
  const max = Math.max(6, Math.floor(maxW / (fs * k)));
  const out = [];
  String(text == null ? '' : text).split(/\s+/).filter(Boolean).forEach(w => {
    if (!out.length) { out.push(w); return; }
    const last = out[out.length - 1];
    if ((last + ' ' + w).length <= max) out[out.length - 1] = last + ' ' + w;
    else out.push(w);
  });
  // Palavra única maior que a caixa: corta em pedaços
  const fixed = [];
  out.forEach(l => {
    if (l.length <= max) { fixed.push(l); return; }
    for (let i = 0; i < l.length; i += max) fixed.push(l.slice(i, i + max));
  });
  return fixed.length ? fixed : [''];
}

/** Altura total de um conjunto de linhas de texto. */
function pfMeasure(lines) {
  let h = PF_GEO.padY * 2;
  lines.forEach(l => { h += (l.gapBefore || 0) + l.lh; });
  return Math.round(h);
}

/** Desenha caixa + linhas de texto centralizadas. */
function pfDrawBox(x, y, w, lines, box, S, extraAttrs) {
  let cy = y + PF_GEO.padY;
  let txt = '';
  lines.forEach(l => {
    cy += (l.gapBefore || 0);
    const base   = cy + l.fs * 0.78;
    const left   = l.align === 'start';
    const tx     = left ? x + PF_GEO.padX : x + w / 2;
    txt += `<text x="${round(tx)}" y="${round(base)}" text-anchor="${left ? 'start' : 'middle'}"`
         + ` font-family="${S.font}" font-size="${l.fs}" font-weight="${l.fw}"`
         + ` fill="${l.fill}">${escHtml(l.t)}</text>`;
    cy += l.lh;
  });
  const h = pfMeasure(lines);
  return `<g class="prisma-box" ${extraAttrs || ''}>`
    + `<rect x="${x}" y="${y}" width="${w}" height="${h}" rx="${S.radius}"`
    + ` fill="${box.fill}" stroke="${box.stroke}" stroke-width="${box.sw}"`
    + (box.dash ? ` stroke-dasharray="${box.dash}"` : '') + `/>`
    + txt + `</g>`;
}

function round(n) { return Math.round(n * 100) / 100; }

/** Linhas de uma caixa principal (número + rótulo). */
function pfBoxLines(S, box, num, label, w) {
  const inner = w - PF_GEO.padX * 2;
  const L = [];
  if (S.numFirst) {
    L.push({ t: String(num), fs: S.fs.num, fw: 800, fill: box.num, lh: S.fs.num + 4 });
    pfWrap(label, inner, S.fs.label).forEach((t, i) =>
      L.push({ t, fs: S.fs.label, fw: 400, fill: box.label, lh: S.fs.label + 3.5, gapBefore: i === 0 ? 3 : 0 }));
  } else {
    pfWrap(label, inner, S.fs.label, true).forEach(t =>
      L.push({ t, fs: S.fs.label, fw: 700, fill: box.label, lh: S.fs.label + 3.5 }));
    L.push({ t: `(n = ${num})`, fs: S.fs.num, fw: 700, fill: box.num, lh: S.fs.num + 3, gapBefore: 4 });
  }
  return L;
}

/** Linhas extras com os motivos de exclusão. */
function pfReasonLines(S, box, reasons, w) {
  const inner = w - PF_GEO.padX * 2;
  const L = [];
  if (!reasons || !reasons.length) return L;
  const t = getT();
  const shown = reasons.slice(0, PF_MAX_REASONS);
  shown.forEach((r, i) => {
    const label = `${r.reason || t.no_reason} (n = ${r.total})`;
    pfWrap(label, inner - 10, S.fs.sub).forEach((line, j) =>
      L.push({ t: (j === 0 ? '• ' : '   ') + line, fs: S.fs.sub, fw: 400, fill: box.sub,
               lh: S.fs.sub + 3, align: 'start', gapBefore: (i === 0 && j === 0) ? 6 : 0 }));
  });
  const rest = reasons.length - shown.length;
  if (rest > 0) L.push({ t: `+ ${rest} ${t.d_more}`, fs: S.fs.sub, fw: 400, fill: box.sub,
                         lh: S.fs.sub + 3, align: 'start' });
  return L;
}

/**
 * Caixa em formato de lista (título em negrito + itens "rótulo (n = X)"), usada nas
 * caixas compostas do PRISMA 2020: "Records identified from:" e "Records removed
 * before screening:". Itens com indent = 1 aparecem como marcadores.
 */
function pfListLines(S, box, title, items, w) {
  const inner = w - PF_GEO.padX * 2;
  const L = [];
  pfWrap(title, inner, S.fs.label, true).forEach(tx =>
    L.push({ t: tx, fs: S.fs.label, fw: 700, fill: box.label, lh: S.fs.label + 3.5, align: 'start' }));
  items.forEach((it, i) => {
    const bullet = it.indent ? '• ' : '';
    const pad    = it.indent ? '   ' : '';
    const fs     = it.indent ? S.fs.sub : S.fs.label;
    pfWrap(`${it.label} (n = ${it.n})`, inner - (it.indent ? 14 : 0), fs).forEach((line, j) =>
      L.push({ t: (j === 0 ? bullet : pad) + line, fs, fw: it.indent ? 400 : 600,
               fill: it.indent ? box.sub : box.num, lh: fs + 3.5, align: 'start',
               gapBefore: (i === 0 && j === 0) ? 4 : 0 }));
  });
  return L;
}

function renderDiagram(d) {
  const S   = PF_STYLES[pfStyle] || PF_STYLES.publication;
  const G   = PF_GEO;
  const t   = getT();
  const id  = d.identification;
  const sc  = d.screening;
  const inc = d.included;

  const showReasons = document.getElementById('pf-opt-reasons')?.checked !== false;
  const showCaption = document.getElementById('pf-opt-caption')?.checked === true;
  const repReasons  = (sc.reports_reasons || []).filter(r => r.total > 0);

  // ── Linhas do modelo oficial PRISMA 2020 (bases de dados e registros) ───────
  // phase: 0 = Identification, 1 = Screening, 2 = Included
  const dbs = id.by_database || [];
  const idLines = dbs.length
    ? pfListLines(S, S.db, t.d_identified_from,
        [{ label: t.d_databases, n: id.total_identified }]
          .concat(dbs.map(b => ({ label: b.name || t.other_sources, n: b.count, indent: 1 }))), G.mainW)
    : [{ t: t.d_no_db, fs: S.fs.label, fw: 400, fill: S.main.label, lh: S.fs.label + 4 }];
  const rows = [
    { phase: 0, mLines: idLines, mBox: S.db,
      exc: { lines: pfListLines(S, S.exc, t.d_removed_before,
               [{ label: t.d_dup_removed, n: id.duplicates_removed }], G.excW) } },
    { phase: 1, num: sc.records_screened, label: t.d_screened,
      exc: { num: sc.records_excluded, label: t.d_excluded } },
    { phase: 1, num: sc.reports_sought, label: t.d_sought,
      exc: { num: sc.reports_not_retrieved, label: t.d_not_retrieved } },
    { phase: 1, num: sc.reports_assessed, label: t.d_assessed,
      exc: { num: sc.reports_excluded, label: t.d_reports_excluded, reasons: repReasons } },
    { phase: 2, num: inc.studies_included, label: t.d_included, exc: null },
  ];

  let svg = '';
  let y   = G.M + 6;
  const laid = [];
  rows.forEach((r, i) => {
    const mBox   = r.mBox || S.main;
    const mLines = r.mLines || pfBoxLines(S, mBox, r.num ?? 0, r.label, G.mainW);
    const mH     = pfMeasure(mLines);
    let eLines = null, eH = 0;
    if (r.exc) {
      eLines = r.exc.lines || pfBoxLines(S, S.exc, r.exc.num ?? 0, r.exc.label, G.excW);
      if (showReasons && r.exc.reasons) eLines = eLines.concat(pfReasonLines(S, S.exc, r.exc.reasons, G.excW));
      eH = pfMeasure(eLines);
    }
    const rowH = Math.max(mH, eH);
    laid.push({ phase: r.phase, top: y, rowH, mY: y + (rowH - mH) / 2, mH, mLines, mBox,
                eY: eLines ? y + (rowH - eH) / 2 : 0, eH, eLines, exc: r.exc });
    y += rowH + (i < rows.length - 1 ? G.rowGap : 0);
  });

  // Faixas de fase: Identification (linha 0), Screening (linhas 1–3), Included (linha 4)
  const phaseLabels = [t.d_phase_id, t.d_phase_sc, t.d_phase_inc];
  [0, 1, 2].forEach(ph => {
    const rs   = laid.filter(r => r.phase === ph);
    const first = rs[0], last = rs[rs.length - 1];
    const top  = laid.indexOf(first) === 0 ? first.top - 6 : first.top - G.rowGap / 2;
    const bot  = laid.indexOf(last) === laid.length - 1 ? last.top + last.rowH + 6 : last.top + last.rowH + G.rowGap / 2;
    svg = pfPhaseBar(G.M, top, G.phaseW, bot - top - 4, phaseLabels[ph], S) + svg;
  });

  // Caixas + setas
  laid.forEach((r, i) => {
    svg += pfDrawBox(G.mainX, r.mY, G.mainW, r.mLines, r.mBox, S);
    if (r.eLines) {
      const yMid = r.mY + r.mH / 2;
      svg += pfArrowH(G.mainX + G.mainW, yMid, G.excX, S.arrowExc, S);
      const reasons = r.exc.reasons || [];
      const clickable = reasons.length
        ? `onclick="openReasonsModal('${encodeURIComponent(JSON.stringify(
              reasons.map(x => `${x.reason || getT().no_reason}: ${x.total}`)))}','${
              String(r.exc.label).replace(/'/g, "\\'")}')" style="cursor:pointer"`
        : '';
      svg += pfDrawBox(G.excX, r.eY, G.excW, r.eLines, S.exc, S, clickable);
    }
    if (i < laid.length - 1) {
      svg += pfArrow(G.mainCX, r.mY + r.mH, laid[i + 1].mY, S.arrow, S);
    }
  });

  let H = laid[laid.length - 1].top + laid[laid.length - 1].rowH + G.M + 6;

  // Legenda opcional (para slides/relatórios; periódicos pedem no texto)
  if (showCaption) {
    const cap = `${t.d_caption}${flowData?.project_title ? ' — ' + flowData.project_title : ''}`;
    const capLines = pfWrap(cap, G.W - G.M * 2, S.fs.caption);
    capLines.forEach((line, i) => {
      svg += `<text x="${G.W / 2}" y="${H + 6 + i * (S.fs.caption + 4)}" text-anchor="middle"`
           + ` font-family="${S.font}" font-size="${S.fs.caption}" fill="${S.caption}">${escHtml(line)}</text>`;
    });
    H += 12 + capLines.length * (S.fs.caption + 4);
  }

  const out = pfSvgShell(svg, G.W, H, S, false);

  document.getElementById('prisma-loading').style.display = 'none';
  const diag = document.getElementById('prisma-diagram');
  diag.style.display = 'inline-block';
  diag.classList.toggle('pf-light', !!S.paperLight);
  diag.innerHTML = out;
}

/** Iguala a altura das caixas de bases adicionando espaçamento antes da 1ª linha. */
function padTo(lines, targetH, S) {
  const h = pfMeasure(lines);
  if (h >= targetH) return lines;
  const copy = lines.map(l => Object.assign({}, l));
  copy[0].gapBefore = (copy[0].gapBefore || 0) + (targetH - h) / 2;
  return copy;
}

function pfSvgShell(inner, W, H, S, forExport) {
  const dim = forExport ? ` width="${W}" height="${H}"` : ` style="width:100%;max-width:${W}px;height:auto"`;
  return `<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"`
    + ` viewBox="0 0 ${W} ${H}"${dim} id="prisma-svg" font-family="${S.font}">
    <defs>
      <marker id="pf-arr" markerWidth="9" markerHeight="9" refX="6.5" refY="3" orient="auto" markerUnits="strokeWidth">
        <path d="M0,0 L6.5,3 L0,6 Z" fill="${S.arrow.color}"/>
      </marker>
      <marker id="pf-arr-exc" markerWidth="9" markerHeight="9" refX="6.5" refY="3" orient="auto" markerUnits="strokeWidth">
        <path d="M0,0 L6.5,3 L0,6 Z" fill="${S.arrowExc.color}"/>
      </marker>
    </defs>
    <rect x="0" y="0" width="${W}" height="${H}" fill="${S.bg}"/>
    ${inner}
  </svg>`;
}

function pfArrow(cx, y1, y2, A, S) {
  return `<line x1="${round(cx)}" y1="${round(y1)}" x2="${round(cx)}" y2="${round(y2 - 7)}"`
    + ` stroke="${A.color}" stroke-width="${A.sw}"`
    + (A.dash ? ` stroke-dasharray="${A.dash}"` : '') + ` marker-end="url(#pf-arr)"/>`;
}

function pfArrowH(x1, y, x2, A, S) {
  return `<line x1="${round(x1)}" y1="${round(y)}" x2="${round(x2 - 7)}" y2="${round(y)}"`
    + ` stroke="${A.color}" stroke-width="${A.sw}"`
    + (A.dash ? ` stroke-dasharray="${A.dash}"` : '') + ` marker-end="url(#pf-arr-exc)"/>`;
}

function pfPhaseBar(x, y, w, h, label, S) {
  const midX = x + w / 2, midY = y + h / 2;
  // Encolhe o rótulo (girado 90°) se a barra for curta, para nunca vazar
  const need = label.length * (S.fs.phase * 0.62 + 1.1);
  let fs = S.fs.phase, ls = 1.1;
  if (need > h - 12) {
    ls = 0.4;
    fs = Math.max(6, Math.min(S.fs.phase, (h - 12) / (label.length * 0.62 + 0.4)));
  }
  // dy em em: mais portável que dominant-baseline em conversores/Word/Illustrator
  return `<g>
    <rect x="${x}" y="${round(y)}" width="${w}" height="${round(h)}" rx="${S.radius}"
          fill="${S.phase.fill}" opacity="${S.phase.opacity}"/>
    <rect x="${x}" y="${round(y)}" width="3.5" height="${round(h)}" rx="1.5" fill="${S.phase.bar}"/>
    <text x="${midX}" y="${round(midY)}" dy="0.34em" text-anchor="middle"
          font-family="${S.font}" font-size="${round(fs)}" font-weight="700" letter-spacing="${ls}"
          fill="${S.phase.text}" transform="rotate(-90,${midX},${round(midY)})">${escHtml(label)}</text>
  </g>`;
}

// ── Exportação ──────────────────────────────────────────────────────────────
function exportDiagram(format) {
  const svgEl = document.getElementById('prisma-svg');
  if (!svgEl) { showToast('Gere o diagrama primeiro', 'warning'); return; }
  const t = getT();
  const S = PF_STYLES[pfStyle] || PF_STYLES.publication;
  const W = svgEl.viewBox.baseVal.width;
  const H = svgEl.viewBox.baseVal.height;
  const fname = `${t.filename}-${pfStyle}`;

  // Clone limpo: sem onclick/cursor, com width/height explícitos (Word, Illustrator, LaTeX)
  const clone = svgEl.cloneNode(true);
  clone.removeAttribute('style');
  clone.setAttribute('width',  W);
  clone.setAttribute('height', H);
  clone.querySelectorAll('[onclick]').forEach(n => { n.removeAttribute('onclick'); n.removeAttribute('style'); });
  // concatenado para não colidir com short_open_tag do PHP
  const svgData = '<' + '?xml version="1.0" encoding="UTF-8"?' + '>\n'
                + new XMLSerializer().serializeToString(clone);

  if (format === 'svg') {
    downloadBlob(new Blob([svgData], { type: 'image/svg+xml;charset=utf-8' }), fname + '.svg');
    showToast('SVG exportado (vetorial)', 'success');
    return;
  }

  // PNG: escala = dpi / 96 (unidades de usuário SVG são px a 96 dpi)
  const scale = Math.max(1, pfDpi / 96);
  const img   = new Image();
  const url   = URL.createObjectURL(new Blob([svgData], { type: 'image/svg+xml;charset=utf-8' }));
  img.onload = () => {
    const canvas  = document.getElementById('export-canvas');
    canvas.width  = Math.round(W * scale);
    canvas.height = Math.round(H * scale);
    const ctx = canvas.getContext('2d');
    ctx.fillStyle = S.bg;
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
    URL.revokeObjectURL(url);
    canvas.toBlob(blob => {
      downloadBlob(blob, `${fname}-${pfDpi}dpi.png`);
      showToast(`PNG ${canvas.width}×${canvas.height}px (${pfDpi} dpi) exportado`, 'success');
    }, 'image/png');
  };
  img.onerror = () => { URL.revokeObjectURL(url); showToast('Falha ao gerar o PNG', 'error'); };
  img.src = url;
}

function downloadBlob(blob, filename) {
  const url = URL.createObjectURL(blob);
  const a   = document.createElement('a');
  a.href = url; a.download = filename;
  document.body.appendChild(a); a.click(); a.remove();
  setTimeout(() => URL.revokeObjectURL(url), 1000);
}

// ── Modal de motivos de exclusão ────────────────────────────────────────────
function openReasonsModal(jsonStr, title) {
  let reasons = [];
  try { reasons = JSON.parse(decodeURIComponent(jsonStr)); } catch(e) { return; }
  reasons = reasons.filter(r => r && r.trim());
  if (!reasons.length) return;

  const modal = document.createElement('div');
  modal.style.cssText = `position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;
    display:flex;align-items:center;justify-content:center;padding:20px`;
  modal.innerHTML = `
    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:14px;
      padding:26px 28px;max-width:420px;width:100%;box-shadow:0 20px 60px rgba(0,0,0,.4)">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
        <h2 style="margin:0;font-size:1rem;font-weight:700">
          <i class="fa fa-list" style="color:var(--primary);margin-right:8px"></i>${escHtml(title)}
        </h2>
        <button onclick="this.closest('[style*=fixed]').remove()"
          style="background:none;border:none;cursor:pointer;font-size:1.1rem;color:var(--text-muted);padding:4px">✕</button>
      </div>
      <ul style="margin:0;padding:0;list-style:none;display:flex;flex-direction:column;gap:8px">
        ${reasons.map(r => {
          const match = r.match(/^(.+?):\s*(\d+)$/);
          if (match) {
            return `<li style="display:flex;justify-content:space-between;align-items:center;
              padding:8px 12px;background:var(--bg);border-radius:8px;font-size:.85rem">
              <span>${escHtml(match[1].trim())}</span>
              <span style="font-weight:700;color:var(--primary);background:rgba(99,102,241,.12);
                padding:2px 8px;border-radius:20px">${escHtml(match[2])}</span>
            </li>`;
          }
          return `<li style="padding:8px 12px;background:var(--bg);border-radius:8px;font-size:.85rem">${escHtml(r)}</li>`;
        }).join('')}
      </ul>
    </div>`;
  document.body.appendChild(modal);
  modal.addEventListener('click', e => { if (e.target === modal) modal.remove(); });
}

// ── Deletar duplicatas ──────────────────────────────────────────────────────
function deleteDuplicates() {
  const count = flowData?.prisma?.identification?.duplicates_removed ?? 0;
  if (!count) { showToast('Nenhuma duplicata encontrada.', 'info'); return; }

  // Modal de confirmação
  const modal = document.createElement('div');
  modal.style.cssText = `position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:9999;
    display:flex;align-items:center;justify-content:center;padding:20px`;
  modal.innerHTML = `
    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:14px;
      padding:28px 32px;max-width:400px;width:100%;box-shadow:0 20px 60px rgba(0,0,0,.4)">
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px">
        <span style="font-size:1.6rem">🗑️</span>
        <h2 style="margin:0;font-size:1.05rem;font-weight:700">Excluir duplicatas?</h2>
      </div>
      <p style="color:var(--text-muted);font-size:.87rem;margin:0 0 22px;line-height:1.55">
        Isso removerá permanentemente <strong style="color:var(--text)">${count} artigo(s)</strong>
        marcados como duplicata do projeto. Esta ação não pode ser desfeita.
      </p>
      <div style="display:flex;gap:10px;justify-content:flex-end">
        <button id="dup-cancel" class="btn btn-ghost">Cancelar</button>
        <button id="dup-confirm" class="btn btn-danger" style="background:#ef4444;color:#fff;border-color:#ef4444">
          <i class="fa fa-trash"></i> Excluir ${count} duplicata(s)
        </button>
      </div>
    </div>`;
  document.body.appendChild(modal);

  modal.querySelector('#dup-cancel').onclick  = () => modal.remove();
  modal.addEventListener('click', e => { if (e.target === modal) modal.remove(); });

  modal.querySelector('#dup-confirm').onclick = async () => {
    const btn = modal.querySelector('#dup-confirm');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Excluindo...';
    try {
      const r = await fetch(`api/prisma-flow.php?action=delete_duplicates&project_id=${PF_PROJECT_ID}`, { method: 'POST' });
      const d = await r.json();
      if (d.error) throw new Error(d.message);
      showToast(`${d.deleted ?? count} duplicata(s) excluída(s) com sucesso.`, 'success');
      modal.remove();
      loadFlow(); // recarrega diagrama
    } catch(e) {
      showToast('Erro: ' + e.message, 'error');
      modal.remove();
    }
  };
}

// Re-render when language changes (triggered by header lang switcher)
document.addEventListener('prismaLangChanged', async () => {
  applyPFLang();
  if (flowData) {
    await translateReasonsIfNeeded(flowData.prisma);
    renderDiagram(flowData.prisma);
    renderDetails(flowData.prisma);
  }
});

syncToolbar();
loadFlow();
</script>
<?php endif; ?>
