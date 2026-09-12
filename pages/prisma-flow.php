<?php
/**
 * PRISMA-SLR - PRISMA 2020 Flow Diagram
 * Renders in three styles (B&W publication, color, dark) with
 * vector export (SVG) and raster export at 150/300/600 dpi for submission.
 */
if (!$projectId): ?>
<div class="empty-state"><i class="fa fa-triangle-exclamation"></i>
  <p>Selecione um projeto para ver o diagrama PRISMA.</p></div>
<?php else: ?>

<div class="page-header">
  <div>
    <h1 class="page-title"><i class="fa fa-diagram-project"></i> Diagrama PRISMA 2020</h1>
    <p class="page-subtitle" id="pf-subtitle">Fluxo de identificação, triagem, elegibilidade e inclusão de estudos.</p>
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
  <!-- Diagram: occupies all remaining space, internal scroll if the SVG is wide -->
  <div class="card" style="min-width:0;overflow:hidden;padding:20px 24px 24px">

    <!-- Figure options bar -->
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
      <div id="prisma-diagram" class="pf-paper" style="display:none"></div>
    </div>
    <p class="pf-hint" id="pf-hint">
      SVG = vetorial (recomendado para o periódico). PNG a 300 dpi atende à maioria das diretrizes de submissão.
    </p>
  </div>

  <!-- Data panel: fixed 260px column, never leaves the viewport -->
  <div>
    <div class="card" style="margin-bottom:12px">
      <h3 class="card-title" style="font-size:0.85rem" id="pf-panel-id">Identificação</h3>
      <div id="detail-identification" class="detail-section"></div>
    </div>
    <div class="card" style="margin-bottom:12px">
      <h3 class="card-title" style="font-size:0.85rem" id="pf-panel-sc">Triagem</h3>
      <div id="detail-screening" class="detail-section"></div>
    </div>
    <div class="card" style="margin-bottom:12px">
      <h3 class="card-title" style="font-size:0.85rem" id="pf-panel-el">Elegibilidade</h3>
      <div id="detail-eligibility" class="detail-section"></div>
    </div>
    <div class="card">
      <h3 class="card-title" style="font-size:0.85rem" id="pf-panel-inc">Incluídos</h3>
      <div id="detail-included" class="detail-section"></div>
    </div>
  </div>
</div>

<!-- Hidden canvas for export -->
<canvas id="export-canvas" style="display:none"></canvas>

<style>
.detail-section { font-size:0.82rem; }
.detail-row { display:flex; justify-content:space-between; align-items:flex-start; gap:8px; padding:5px 0; border-bottom:1px solid var(--border); }
.detail-row:last-child { border-bottom:none; }
.detail-row > span:first-child { flex:1; min-width:0; word-break:break-word; overflow-wrap:break-word; line-height:1.35; }
.detail-row > span:last-child { flex-shrink:0; text-align:right; }
.detail-num { font-weight:700; color:var(--primary); }
.prisma-box { cursor:default; }

/* ── Figure options bar ───────────────────────────── */
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

/* "Paper" area: shows the figure exactly as it will be exported */
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
  pt: {
    // Page
    subtitle:     'Fluxo de identificação, triagem, elegibilidade e inclusão de estudos.',
    btn_png:      'Exportar PNG',
    btn_svg:      'Exportar SVG',
    btn_refresh:  'Atualizar',
    // Toolbar
    lbl_style:    'Estilo',
    st_pub:       'Publicação',
    st_col:       'Colorido',
    st_dark:      'Escuro',
    lbl_dpi:      'Resolução (PNG)',
    lbl_opts:     'Conteúdo',
    opt_reasons:  'Motivos na figura',
    opt_caption:  'Legenda',
    hint:         'SVG = vetorial (recomendado para o periódico). PNG a 300 dpi atende à maioria das diretrizes de submissão.',
    // Side panel headers
    panel_id:     'Identificação',
    panel_sc:     'Triagem',
    panel_el:     'Elegibilidade',
    panel_inc:    'Incluídos',
    // Detail rows
    total_identified:     'Total identificados',
    duplicates_removed:   'Duplicatas removidas',
    unique_screening:     'Únicos para triagem',
    screened:             'Avaliados',
    included_sc:          'Incluídos',
    excluded_sc:          'Excluídos',
    fulltext_assessed:    'Texto completo avaliados',
    excluded_ft:          'Excluídos',
    studies_included:     'Estudos incluídos',
    no_reason:            'Sem motivo',
    // SVG diagram boxes
    d_records_id:    'Registros identificados',
    d_for_screening: 'Para triagem (únicos)',
    d_dup_removed:   'Duplicatas removidas',
    d_for_elig:      'Para elegibilidade',
    d_exc_sc:        'Excluídos na triagem',
    d_exc_ft:        'Excluídos (texto completo)',
    d_studies_inc:   'Estudos incluídos',
    d_phase_id:      'IDENTIFICAÇÃO',
    d_phase_sc:      'TRIAGEM',
    d_phase_el:      'ELEGIBILIDADE',
    d_phase_inc:     'INCLUÍDOS',
    d_see_reasons:   '▼ ver motivos',
    d_no_db:         'Bases não disponíveis',
    d_sources:       'Registros identificados em:',
    d_more:          'outros motivos',
    d_caption:       'Fluxograma PRISMA 2020',
    // Export file name
    filename:        'prisma-flow',
  },
  en: {
    subtitle:     'Identification, screening, eligibility and inclusion flow.',
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
    panel_el:     'Eligibility',
    panel_inc:    'Included',
    total_identified:     'Total identified',
    duplicates_removed:   'Duplicates removed',
    unique_screening:     'Unique for screening',
    screened:             'Assessed',
    included_sc:          'Included',
    excluded_sc:          'Excluded',
    fulltext_assessed:    'Full text assessed',
    excluded_ft:          'Excluded',
    studies_included:     'Studies included',
    no_reason:            'No reason',
    d_records_id:    'Records identified',
    d_for_screening: 'For screening (unique)',
    d_dup_removed:   'Duplicates removed',
    d_for_elig:      'For eligibility',
    d_exc_sc:        'Excluded in screening',
    d_exc_ft:        'Excluded (full text)',
    d_studies_inc:   'Studies included',
    d_phase_id:      'IDENTIFICATION',
    d_phase_sc:      'SCREENING',
    d_phase_el:      'ELIGIBILITY',
    d_phase_inc:     'INCLUDED',
    d_see_reasons:   '▼ see reasons',
    d_no_db:         'Databases not available',
    d_sources:       'Records identified from:',
    d_more:          'other reasons',
    d_caption:       'PRISMA 2020 flow diagram',
    filename:        'prisma-flow-en',
  },
  es: {
    subtitle:     'Flujo de identificación, cribado, elegibilidad e inclusión de estudios.',
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
    panel_el:     'Elegibilidad',
    panel_inc:    'Incluidos',
    total_identified:     'Total identificados',
    duplicates_removed:   'Duplicados eliminados',
    unique_screening:     'Únicos para cribado',
    screened:             'Evaluados',
    included_sc:          'Incluidos',
    excluded_sc:          'Excluidos',
    fulltext_assessed:    'Texto completo evaluado',
    excluded_ft:          'Excluidos',
    studies_included:     'Estudios incluidos',
    no_reason:            'Sin motivo',
    d_records_id:    'Registros identificados',
    d_for_screening: 'Para cribado (únicos)',
    d_dup_removed:   'Duplicados eliminados',
    d_for_elig:      'Para elegibilidad',
    d_exc_sc:        'Excluidos en cribado',
    d_exc_ft:        'Excluidos (texto completo)',
    d_studies_inc:   'Estudios incluidos',
    d_phase_id:      'IDENTIFICACIÓN',
    d_phase_sc:      'CRIBADO',
    d_phase_el:      'ELEGIBILIDAD',
    d_phase_inc:     'INCLUIDOS',
    d_see_reasons:   '▼ ver motivos',
    d_no_db:         'Bases no disponibles',
    d_sources:       'Registros identificados en:',
    d_more:          'otros motivos',
    d_caption:       'Diagrama de flujo PRISMA 2020',
    filename:        'prisma-flow-es',
  },
};

function getT() { return PF_T[window.PRISMA_LANG || 'pt']; }

// Exclusion reasons are free text typed by the researcher (usually in
// Portuguese) and are not part of the interface's translation dictionary —
// that's why they need to be translated separately when the active language isn't PT.
const REASON_TRANSLATE_CACHE = {};
async function translateReasonsIfNeeded(prisma) {
  const lang      = window.PRISMA_LANG || 'pt';
  const targetMap = { en: 'en', es: 'es' };
  const target    = targetMap[lang];
  const lists     = [prisma?.screening?.exclusion_reasons, prisma?.eligibility?.exclusion_reasons];

  if (!target) {
    // Switched back to PT — restore the original text without re-translating
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
  set('pf-panel-el',    t.panel_el);
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

// ── Figure styles ───────────────────────────────────────────
// "publication" is the default: black-and-white, safe for printing and for
// grayscale conversion, Arial typography (accepted by journals).
const PF_STYLES = {
  publication: {
    paperLight: true,
    font:       "Arial, Helvetica, 'Liberation Sans', sans-serif",
    bg:         '#ffffff',
    radius:     2,
    numFirst:   false,          // label on top, "(n = X)" below
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
  const t = getT();

  // Identification
  let id_html = `<div class="detail-row"><span>${t.total_identified}</span><span class="detail-num">${d.identification.total_identified}</span></div>`;
  (d.identification.by_database||[]).forEach(b => {
    id_html += `<div class="detail-row" style="padding-left:12px"><span>${escHtml(b.name)}</span><span>${b.count}</span></div>`;
  });
  id_html += `<div class="detail-row"><span>${t.duplicates_removed}</span><span class="detail-num">${d.identification.duplicates_removed}</span></div>`;
  id_html += `<div class="detail-row"><span>${t.unique_screening}</span><span class="detail-num">${d.identification.unique_after_dedup}</span></div>`;
  document.getElementById('detail-identification').innerHTML = id_html;

  // Screening
  let sc_html = `<div class="detail-row"><span>${t.screened}</span><span class="detail-num">${d.screening.records_screened}</span></div>`;
  sc_html += `<div class="detail-row"><span>${t.included_sc}</span><span class="detail-num" style="color:var(--accent-green)">${d.screening.records_included}</span></div>`;
  sc_html += `<div class="detail-row"><span>${t.excluded_sc}</span><span class="detail-num" style="color:var(--accent-red)">${d.screening.records_excluded}</span></div>`;
  (d.screening.exclusion_reasons||[]).forEach(r => {
    sc_html += `<div class="detail-row" style="padding-left:12px;font-size:0.75rem"><span>${escHtml(r.reason||t.no_reason)}</span><span>${r.total}</span></div>`;
  });
  document.getElementById('detail-screening').innerHTML = sc_html;

  // Eligibility
  let el_html = `<div class="detail-row"><span>${t.fulltext_assessed}</span><span class="detail-num">${d.eligibility.assessed_full_text}</span></div>`;
  el_html += `<div class="detail-row"><span>${t.excluded_ft}</span><span class="detail-num">${d.eligibility.excluded_full_text}</span></div>`;
  (d.eligibility.exclusion_reasons||[]).forEach(r => {
    el_html += `<div class="detail-row" style="padding-left:12px"><span>${escHtml(r.reason||t.no_reason)}</span><span>${r.total}</span></div>`;
  });
  document.getElementById('detail-eligibility').innerHTML = el_html;

  // Included
  document.getElementById('detail-included').innerHTML = `
    <div class="detail-row"><span>${t.studies_included}</span><span class="detail-num">${d.included.studies_included}</span></div>`;
}

// ═══════════════════════════════════════════════════════════════════════════
//  Diagram drawing engine
//  Content-driven layout: each box's height is computed from the text it
//  contains, so nothing is ever cut off or overlapping.
// ═══════════════════════════════════════════════════════════════════════════

// Geometry (SVG user units = px at 96 dpi)
const PF_GEO = {
  M:        16,   // margin
  phaseW:   26,   // phase bar width
  phaseGap: 14,
  mainW:    300,  // main column
  colGap:   72,   // gap between the main column and the exclusion boxes
  excW:     300,  // exclusion column
  rowGap:   34,   // vertical gap between rows (arrow length)
  padX:     12,
  padY:     11,
};
PF_GEO.mainX = PF_GEO.M + PF_GEO.phaseW + PF_GEO.phaseGap;
PF_GEO.excX  = PF_GEO.mainX + PF_GEO.mainW + PF_GEO.colGap;
PF_GEO.W     = PF_GEO.excX + PF_GEO.excW + PF_GEO.M;
PF_GEO.mainCX = PF_GEO.mainX + PF_GEO.mainW / 2;

/** Approximate line wrapping using an average character-width metric (Arial). */
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
  // A single word longer than the box: split it into chunks
  const fixed = [];
  out.forEach(l => {
    if (l.length <= max) { fixed.push(l); return; }
    for (let i = 0; i < l.length; i += max) fixed.push(l.slice(i, i + max));
  });
  return fixed.length ? fixed : [''];
}

/** Total height of a set of text lines. */
function pfMeasure(lines) {
  let h = PF_GEO.padY * 2;
  lines.forEach(l => { h += (l.gapBefore || 0) + l.lh; });
  return Math.round(h);
}

/** Draws a box + centered text lines. */
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

/** Lines for a main box (number + label). */
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

/** Extra lines listing the exclusion reasons. */
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

function renderDiagram(d) {
  const S    = PF_STYLES[pfStyle] || PF_STYLES.publication;
  const G    = PF_GEO;
  const t    = getT();
  const id   = d.identification;
  const sc   = d.screening;
  const el   = d.eligibility;
  const inc  = d.included;

  const showReasons = document.getElementById('pf-opt-reasons')?.checked !== false;
  const showCaption = document.getElementById('pf-opt-caption')?.checked === true;

  const scReasons = (sc.exclusion_reasons || []).filter(r => r.total > 0);
  const elReasons = (el.exclusion_reasons || []).filter(r => r.total > 0);

  let svg = '';
  let y   = G.M + 6;

  // ── 1. Sources (databases) ────────────────────────────────
  const dbs = id.by_database || [];
  let dbBottom, dbTop = y;

  if (!dbs.length) {
    const lines = [{ t: t.d_no_db, fs: S.fs.label, fw: 400, fill: S.main.label, lh: S.fs.label + 4 }];
    svg += pfDrawBox(G.mainX, y, G.mainW, lines, S.main, S);
    dbBottom = y + pfMeasure(lines);
    svg += pfArrow(G.mainCX, dbBottom, dbBottom + G.rowGap, S.arrow, S);
    y = dbBottom + G.rowGap;
  } else if (dbs.length <= 3) {
    // Side-by-side boxes, converging into the total
    const gap = 12;
    const bw  = Math.floor((G.mainW - gap * (dbs.length - 1)) / dbs.length);
    let maxH  = 0;
    const boxes = dbs.map((s, i) => {
      const bx    = G.mainX + i * (bw + gap);
      const lines = pfBoxLines(S, S.db, s.count, s.name, bw);
      const h     = pfMeasure(lines);
      if (h > maxH) maxH = h;
      return { bx, bw, lines, h };
    });
    boxes.forEach(b => { svg += pfDrawBox(b.bx, y, b.bw, padTo(b.lines, maxH, S), S.db, S); });
    dbBottom = y + maxH;
    // Orthogonal connectors converging to the center
    const railY = dbBottom + 16;
    boxes.forEach(b => {
      const cx = b.bx + b.bw / 2;
      svg += `<path d="M${round(cx)},${dbBottom} V${railY}" fill="none"`
           + ` stroke="${S.arrow.color}" stroke-width="${S.arrow.sw}"/>`;
    });
    if (boxes.length > 1) {
      const x1 = boxes[0].bx + boxes[0].bw / 2;
      const x2 = boxes[boxes.length - 1].bx + boxes[boxes.length - 1].bw / 2;
      svg += `<path d="M${round(x1)},${railY} H${round(x2)}" fill="none"`
           + ` stroke="${S.arrow.color}" stroke-width="${S.arrow.sw}"/>`;
    }
    svg += pfArrow(G.mainCX, railY, railY + G.rowGap - 16, S.arrow, S);
    y = railY + G.rowGap - 16;
  } else {
    // Many databases: single box listing the sources (official PRISMA 2020 style)
    const inner = G.mainW - G.padX * 2;
    const lines = [];
    pfWrap(t.d_sources, inner, S.fs.label, true).forEach(tx =>
      lines.push({ t: tx, fs: S.fs.label, fw: 700, fill: S.db.label, lh: S.fs.label + 3.5 }));
    dbs.forEach((s, i) => {
      pfWrap(`${s.name} (n = ${s.count})`, inner - 10, S.fs.sub).forEach((line, j) =>
        lines.push({ t: (j === 0 ? '• ' : '   ') + line, fs: S.fs.sub, fw: 400, fill: S.db.sub,
                     lh: S.fs.sub + 3, align: 'start', gapBefore: (i === 0 && j === 0) ? 5 : 0 }));
    });
    svg += pfDrawBox(G.mainX, y, G.mainW, lines, S.db, S);
    dbBottom = y + pfMeasure(lines);
    svg += pfArrow(G.mainCX, dbBottom, dbBottom + G.rowGap, S.arrow, S);
    y = dbBottom + G.rowGap;
  }

  // ── 2..5. Main rows ───────────────────────────────────
  const rows = [
    { num: id.total_identified,   label: t.d_records_id,
      exc: { num: id.duplicates_removed, label: t.d_dup_removed, reasons: [] } },
    { num: id.unique_after_dedup, label: t.d_for_screening,
      exc: { num: sc.records_excluded || 0, label: t.d_exc_sc, reasons: scReasons } },
    { num: sc.records_included || 0, label: t.d_for_elig,
      exc: { num: el.excluded_full_text || 0, label: t.d_exc_ft, reasons: elReasons } },
    { num: inc.studies_included,  label: t.d_studies_inc, exc: null },
  ];

  const laid = [];
  rows.forEach((r, i) => {
    const mLines = pfBoxLines(S, S.main, r.num, r.label, G.mainW);
    const mH     = pfMeasure(mLines);
    let eLines = null, eH = 0;
    if (r.exc) {
      eLines = pfBoxLines(S, S.exc, r.exc.num, r.exc.label, G.excW);
      if (showReasons) eLines = eLines.concat(pfReasonLines(S, S.exc, r.exc.reasons, G.excW));
      eH = pfMeasure(eLines);
    }
    const rowH = Math.max(mH, eH);
    const top  = y;
    laid.push({ top, rowH, mY: top + (rowH - mH) / 2, mH, mLines,
                eY: eLines ? top + (rowH - eH) / 2 : 0, eH, eLines, exc: r.exc });
    y = top + rowH + (i < rows.length - 1 ? G.rowGap : 0);
  });

  // Phase bars (one per row), with boundaries at the midpoint of the gaps
  const bounds = [];
  bounds.push(dbTop - 6);
  bounds.push(laid[0].top + laid[0].rowH + G.rowGap / 2);   // end of Identification
  bounds.push(laid[1].top + laid[1].rowH + G.rowGap / 2);   // end of Screening
  bounds.push(laid[2].top + laid[2].rowH + G.rowGap / 2);   // end of Eligibility
  bounds.push(laid[3].top + laid[3].rowH + 6);              // end of Included
  const phaseLabels = [t.d_phase_id, t.d_phase_sc, t.d_phase_el, t.d_phase_inc];
  for (let i = 0; i < 4; i++) {
    svg = pfPhaseBar(G.M, bounds[i], G.phaseW, bounds[i+1] - bounds[i] - 4, phaseLabels[i], S) + svg;
  }

  // Boxes + arrows
  laid.forEach((r, i) => {
    svg += pfDrawBox(G.mainX, r.mY, G.mainW, r.mLines, S.main, S);
    if (r.eLines) {
      const yMid = r.mY + r.mH / 2;
      svg += pfArrowH(G.mainX + G.mainW, yMid, G.excX, S.arrowExc, S);
      const clickable = r.exc.reasons && r.exc.reasons.length
        ? `onclick="openReasonsModal('${encodeURIComponent(JSON.stringify(
              r.exc.reasons.map(x => `${x.reason || getT().no_reason}: ${x.total}`)))}','${
              String(r.exc.label).replace(/'/g, "\\'")}')" style="cursor:pointer"`
        : '';
      svg += pfDrawBox(G.excX, r.eY, G.excW, r.eLines, S.exc, S, clickable);
    }
    if (i < laid.length - 1) {
      const next = laid[i + 1];
      svg += pfArrow(G.mainCX, r.mY + r.mH, next.mY, S.arrow, S);
    }
  });

  let H = laid[laid.length - 1].top + laid[laid.length - 1].rowH + G.M + 6;

  // Optional caption (for slides/reports; journals require it in the manuscript text)
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

/** Equalizes the database boxes' height by adding spacing before the 1st line. */
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
  // Shrinks the label (rotated 90°) if the bar is short, so it never overflows
  const need = label.length * (S.fs.phase * 0.62 + 1.1);
  let fs = S.fs.phase, ls = 1.1;
  if (need > h - 12) {
    ls = 0.4;
    fs = Math.max(6, Math.min(S.fs.phase, (h - 12) / (label.length * 0.62 + 0.4)));
  }
  // dy in em units: more portable than dominant-baseline across converters/Word/Illustrator
  return `<g>
    <rect x="${x}" y="${round(y)}" width="${w}" height="${round(h)}" rx="${S.radius}"
          fill="${S.phase.fill}" opacity="${S.phase.opacity}"/>
    <rect x="${x}" y="${round(y)}" width="3.5" height="${round(h)}" rx="1.5" fill="${S.phase.bar}"/>
    <text x="${midX}" y="${round(midY)}" dy="0.34em" text-anchor="middle"
          font-family="${S.font}" font-size="${round(fs)}" font-weight="700" letter-spacing="${ls}"
          fill="${S.phase.text}" transform="rotate(-90,${midX},${round(midY)})">${escHtml(label)}</text>
  </g>`;
}

// ── Export ──────────────────────────────────────────────────────────────
function exportDiagram(format) {
  const svgEl = document.getElementById('prisma-svg');
  if (!svgEl) { showToast('Gere o diagrama primeiro', 'warning'); return; }
  const t = getT();
  const S = PF_STYLES[pfStyle] || PF_STYLES.publication;
  const W = svgEl.viewBox.baseVal.width;
  const H = svgEl.viewBox.baseVal.height;
  const fname = `${t.filename}-${pfStyle}`;

  // Clean clone: no onclick/cursor, with explicit width/height (Word, Illustrator, LaTeX)
  const clone = svgEl.cloneNode(true);
  clone.removeAttribute('style');
  clone.setAttribute('width',  W);
  clone.setAttribute('height', H);
  clone.querySelectorAll('[onclick]').forEach(n => { n.removeAttribute('onclick'); n.removeAttribute('style'); });
  // concatenated to avoid colliding with PHP's short_open_tag
  const svgData = '<' + '?xml version="1.0" encoding="UTF-8"?' + '>\n'
                + new XMLSerializer().serializeToString(clone);

  if (format === 'svg') {
    downloadBlob(new Blob([svgData], { type: 'image/svg+xml;charset=utf-8' }), fname + '.svg');
    showToast('SVG exportado (vetorial)', 'success');
    return;
  }

  // PNG: scale = dpi / 96 (SVG user units are px at 96 dpi)
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

// ── Exclusion reasons modal ────────────────────────────────────────────
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

// ── Delete duplicates ──────────────────────────────────────────────────────
function deleteDuplicates() {
  const count = flowData?.prisma?.identification?.duplicates_removed ?? 0;
  if (!count) { showToast('Nenhuma duplicata encontrada.', 'info'); return; }

  // Confirmation modal
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
      loadFlow(); // reload the diagram
    } catch(e) {
      showToast('Erro: ' + e.message, 'error');
      modal.remove();
    }
  };
}

// ── Delete excluded records (screening or eligibility) ────────────────────────────
function deleteExcluded(stage) {
  const labels = {
    screened: { label: 'Excluídos na triagem',       status: 'excluded', scope: 'screened' },
    eligible: { label: 'Excluídos (texto completo)', status: 'excluded', scope: 'eligible' },
  };
  const info  = labels[stage];
  const count = stage === 'screened'
    ? (flowData?.prisma?.screening?.records_excluded ?? 0)
    : (flowData?.prisma?.eligibility?.excluded_full_text ?? 0);

  if (!count) { showToast('Nenhum registro encontrado.', 'info'); return; }

  const modal = document.createElement('div');
  modal.style.cssText = `position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:9999;
    display:flex;align-items:center;justify-content:center;padding:20px`;
  modal.innerHTML = `
    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:14px;
      padding:28px 32px;max-width:400px;width:100%;box-shadow:0 20px 60px rgba(0,0,0,.4)">
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px">
        <span style="font-size:1.6rem">🗑️</span>
        <h2 style="margin:0;font-size:1.05rem;font-weight:700">Excluir registros?</h2>
      </div>
      <p style="color:var(--text-muted);font-size:.87rem;margin:0 0 22px;line-height:1.55">
        Isso removerá permanentemente <strong style="color:var(--text)">${count} artigo(s)</strong>
        da categoria <strong style="color:var(--text)">${info.label}</strong>. Esta ação não pode ser desfeita.
      </p>
      <div style="display:flex;gap:10px;justify-content:flex-end">
        <button id="exc-cancel" class="btn btn-ghost">Cancelar</button>
        <button id="exc-confirm" class="btn btn-danger" style="background:#ef4444;color:#fff;border-color:#ef4444">
          <i class="fa fa-trash"></i> Excluir ${count} registro(s)
        </button>
      </div>
    </div>`;
  document.body.appendChild(modal);

  modal.querySelector('#exc-cancel').onclick = () => modal.remove();
  modal.addEventListener('click', e => { if (e.target === modal) modal.remove(); });

  modal.querySelector('#exc-confirm').onclick = async () => {
    const btn = modal.querySelector('#exc-confirm');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Excluindo...';
    try {
      const r = await fetch(
        `api/prisma-flow.php?action=delete_excluded&stage=${stage}&project_id=${PF_PROJECT_ID}`,
        { method: 'POST' }
      );
      const d = await r.json();
      if (d.error) throw new Error(d.message);
      showToast(`${d.deleted ?? count} registro(s) excluído(s) com sucesso.`, 'success');
      modal.remove();
      loadFlow();
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
