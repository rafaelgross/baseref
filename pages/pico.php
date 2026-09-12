<?php
/**
 * PRISMA-SLR - Page: PICO(C) data extraction
 */
if (!$projectId): ?>
<div class="empty-state"><i class="fa fa-triangle-exclamation"></i>
  <p>Selecione um projeto para extrair dados PICO.</p></div>
<?php else: ?>

<div class="page-header">
  <div>
    <h1 class="page-title"><i class="fa fa-table-cells"></i> Extração de Dados — PICO</h1>
    <p class="page-subtitle">População · Intervenção · Comparação · Desfecho (+ Contexto) para cada estudo incluído. Salvamento automático.</p>
  </div>
  <div style="display:flex;gap:10px">
    <button class="btn btn-outline" onclick="window.location='api/pico.php?project_id=<?= $projectId ?>&csv=1'">
      <i class="fa fa-download"></i> Exportar CSV
    </button>
  </div>
</div>

<div id="pico-list"><div class="spinner"></div></div>

<script>
const PICO_PROJECT_ID = <?= $projectId ?>;
const picoTimers = {};

const PICO_FIELDS = [
  ['population',   'População',    'fa-users'],
  ['intervention', 'Intervenção',  'fa-flask'],
  ['comparison',   'Comparação',   'fa-scale-balanced'],
  ['outcome',      'Desfecho',     'fa-bullseye'],
  ['context',      'Contexto',     'fa-location-dot'],
];

async function loadPico() {
  const r = await fetch(`api/pico.php?project_id=${PICO_PROJECT_ID}`);
  const d = await r.json();
  const el = document.getElementById('pico-list');

  if (d.error) { el.innerHTML = `<div class="alert alert-error">${escHtml(d.message)}</div>`; return; }
  if (!d.articles.length) {
    el.innerHTML = '<div class="empty-state"><i class="fa fa-circle-check"></i><p>Nenhum estudo incluído ainda — conclua a fase de elegibilidade.</p></div>';
    return;
  }

  el.innerHTML = d.articles.map(a => {
    const firstAuthor = (a.authors || '').split(';')[0].trim();
    const filled = PICO_FIELDS.filter(([f]) => (a[f] || '').trim() !== '').length;
    return `
    <div class="card" style="margin-bottom:14px">
      <div class="card-header" style="cursor:pointer" onclick="togglePico(${a.id})">
        <div class="card-title" style="font-size:.88rem;line-height:1.4">
          <i class="fa fa-chevron-down" id="pico-chev-${a.id}"></i>
          ${escHtml(firstAuthor ? firstAuthor + ' (' + (a.year || 's.d.') + ')' : a.title)}
          <small class="text-muted" style="font-weight:400"> — ${escHtml((a.title || '').substring(0, 80))}${(a.title || '').length > 80 ? '…' : ''}</small>
        </div>
        <span class="badge ${filled === PICO_FIELDS.length ? 'badge-included' : filled > 0 ? 'badge-pending' : 'badge-excluded'}"
              id="pico-badge-${a.id}">${filled}/${PICO_FIELDS.length}</span>
      </div>
      <div id="pico-body-${a.id}" style="display:none;padding-top:6px">
        ${PICO_FIELDS.map(([field, label, icon]) => `
          <div class="form-group" style="margin-bottom:10px">
            <label class="form-label" style="font-size:.76rem"><i class="fa ${icon}"></i> ${label}</label>
            <textarea class="form-control" rows="2" style="font-size:.82rem" id="pico-${a.id}-${field}"
                      oninput="savePicoDebounced(${a.id})">${escHtml(a[field] || '')}</textarea>
          </div>`).join('')}
        <div class="form-group" style="margin-bottom:4px">
          <label class="form-label" style="font-size:.76rem"><i class="fa fa-note-sticky"></i> Notas</label>
          <textarea class="form-control" rows="2" style="font-size:.82rem" id="pico-${a.id}-notes"
                    oninput="savePicoDebounced(${a.id})">${escHtml(a.notes || '')}</textarea>
        </div>
        <small class="text-muted" id="pico-status-${a.id}" style="font-size:.7rem"></small>
      </div>
    </div>`;
  }).join('');
}

function togglePico(id) {
  const body = document.getElementById(`pico-body-${id}`);
  const chev = document.getElementById(`pico-chev-${id}`);
  const open = body.style.display !== 'none';
  body.style.display = open ? 'none' : 'block';
  chev.className = `fa fa-chevron-${open ? 'down' : 'up'}`;
}

function savePicoDebounced(id) {
  clearTimeout(picoTimers[id]);
  document.getElementById(`pico-status-${id}`).textContent = 'digitando…';
  picoTimers[id] = setTimeout(() => savePico(id), 800);
}

async function savePico(id) {
  const payload = { article_id: id };
  PICO_FIELDS.forEach(([f]) => payload[f] = document.getElementById(`pico-${id}-${f}`).value);
  payload.notes = document.getElementById(`pico-${id}-notes`).value;

  try {
    const r = await fetch('api/pico.php', {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    });
    const d = await r.json();
    if (d.error) throw new Error(d.message);
    const filled = PICO_FIELDS.filter(([f]) => payload[f].trim() !== '').length;
    const badge = document.getElementById(`pico-badge-${id}`);
    badge.textContent = `${filled}/${PICO_FIELDS.length}`;
    badge.className = `badge ${filled === PICO_FIELDS.length ? 'badge-included' : filled > 0 ? 'badge-pending' : 'badge-excluded'}`;
    document.getElementById(`pico-status-${id}`).textContent = '✓ salvo';
  } catch (e) {
    document.getElementById(`pico-status-${id}`).textContent = 'erro ao salvar: ' + e.message;
  }
}

loadPico();
</script>
<?php endif; ?>
