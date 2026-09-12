<?php
/**
 * PRISMA-SLR - Page: Quality Assessment / Risk of Bias (CASP/JBI)
 */
if (!$projectId): ?>
<div class="empty-state"><i class="fa fa-triangle-exclamation"></i>
  <p>Selecione um projeto para avaliar a qualidade dos estudos incluídos.</p></div>
<?php else: ?>

<div class="page-header">
  <div>
    <h1 class="page-title"><i class="fa fa-clipboard-check"></i> Avaliação de Qualidade / Risco de Viés</h1>
    <p class="page-subtitle">Aplique um instrumento de avaliação crítica (CASP, JBI ou personalizado) aos estudos incluídos.</p>
  </div>
  <div style="display:flex;gap:10px">
    <button class="btn btn-outline" onclick="window.location='api/quality.php?project_id=<?= $projectId ?>&csv=1'">
      <i class="fa fa-download"></i> Exportar CSV
    </button>
  </div>
</div>

<!-- Instrumento -->
<div class="card" style="margin-bottom:18px">
  <div class="card-header">
    <div class="card-title"><i class="fa fa-toolbox"></i> Instrumento de Avaliação</div>
  </div>
  <div id="ql-instrument"><div class="spinner"></div></div>
</div>

<!-- Artigos -->
<div class="card">
  <div class="card-header">
    <div class="card-title"><i class="fa fa-list-check"></i> Estudos Incluídos</div>
  </div>
  <div id="ql-articles"><div class="spinner"></div></div>
</div>

<!-- Modal de avaliação -->
<div id="ql-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:1000;align-items:center;justify-content:center" onclick="if(event.target===this)closeQlModal()">
  <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:12px;max-width:760px;width:92%;max-height:86vh;overflow-y:auto;padding:22px">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;margin-bottom:14px">
      <h3 id="ql-modal-title" style="font-size:0.95rem;line-height:1.4;margin:0"></h3>
      <button class="btn btn-ghost btn-sm" onclick="closeQlModal()"><i class="fa fa-xmark"></i></button>
    </div>
    <div id="ql-modal-body"></div>
  </div>
</div>

<script>
const QL_PROJECT_ID = <?= $projectId ?>;
let QL_DATA = null;
let QL_CURRENT_ARTICLE = null;

async function loadQuality() {
  const r = await fetch(`api/quality.php?project_id=${QL_PROJECT_ID}`);
  QL_DATA = await r.json();
  renderInstrument();
  renderArticles();
}

function renderInstrument() {
  const el = document.getElementById('ql-instrument');
  const d  = QL_DATA;
  if (!d.instrument) {
    el.innerHTML = `
      <p style="font-size:.84rem;color:var(--text-muted);margin-bottom:10px">
        Nenhum instrumento configurado. Escolha um template consagrado ou comece do zero:</p>
      <div style="display:flex;gap:8px;flex-wrap:wrap">
        <button class="btn btn-primary btn-sm" onclick="createInstrument('casp')"><i class="fa fa-clipboard-list"></i> CASP (10 itens)</button>
        <button class="btn btn-primary btn-sm" onclick="createInstrument('jbi')"><i class="fa fa-clipboard-list"></i> JBI (8 itens)</button>
        <button class="btn btn-outline btn-sm" onclick="createInstrument('blank')"><i class="fa fa-plus"></i> Personalizado (vazio)</button>
      </div>`;
    return;
  }
  let qHtml = d.questions.map((q, i) => `
    <li style="padding:5px 0;border-bottom:1px solid var(--border);font-size:.82rem;display:flex;justify-content:space-between;gap:10px">
      <span><strong>Q${i + 1}.</strong> ${escHtml(q.question)}</span>
      <button class="btn btn-ghost btn-sm" title="Remover pergunta" onclick="deleteQuestion(${q.id})"><i class="fa fa-trash" style="font-size:.7rem"></i></button>
    </li>`).join('');
  el.innerHTML = `
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px">
      <strong style="font-size:.88rem"><i class="fa fa-check-circle" style="color:var(--accent-green,#00e5a0)"></i> ${escHtml(d.instrument.name)}</strong>
      <button class="btn btn-ghost btn-sm" onclick="if(confirm('Trocar de instrumento apaga todas as respostas. Continuar?')) { QL_DATA.instrument=null; renderInstrument(); }">
        <i class="fa fa-rotate"></i> Trocar instrumento
      </button>
    </div>
    <ul style="list-style:none;margin-top:10px">${qHtml}</ul>
    <div style="display:flex;gap:8px;margin-top:10px">
      <input type="text" class="form-control" id="ql-new-question" placeholder="Nova pergunta..." style="flex:1">
      <button class="btn btn-outline btn-sm" onclick="addQuestion()"><i class="fa fa-plus"></i> Adicionar</button>
    </div>`;
}

function renderArticles() {
  const el = document.getElementById('ql-articles');
  const d  = QL_DATA;
  if (!d.articles.length) {
    el.innerHTML = '<div class="empty-state" style="padding:24px"><i class="fa fa-circle-check"></i><p style="font-size:.84rem">Nenhum estudo incluído ainda — conclua a fase de elegibilidade.</p></div>';
    return;
  }
  el.innerHTML = d.articles.map(a => {
    const total = d.n_questions;
    const pct   = total ? Math.round(100 * a.answered / total) : 0;
    const score = a.answered > 0 ? Math.round(100 * (a.yes_count || 0) / a.answered) : null;
    return `
    <div style="display:flex;gap:14px;align-items:center;justify-content:space-between;padding:10px 4px;border-bottom:1px solid var(--border);flex-wrap:wrap">
      <div style="flex:1;min-width:260px">
        <div style="font-weight:600;font-size:.86rem">${escHtml(a.title)}</div>
        <small class="text-muted">${a.year || '—'} · ${escHtml(a.journal || '')}</small>
      </div>
      <div style="display:flex;gap:14px;align-items:center">
        <span style="font-size:.76rem;color:var(--text-muted)">${a.answered}/${total} respondidas</span>
        ${score !== null ? `<span class="badge ${score >= 70 ? 'badge-included' : score >= 40 ? 'badge-pending' : 'badge-excluded'}">${score}% sim</span>` : ''}
        <button class="btn btn-primary btn-sm" ${total ? '' : 'disabled title="Configure o instrumento primeiro"'} onclick="openQlModal(${a.id})">
          <i class="fa fa-pen-to-square"></i> Avaliar
        </button>
      </div>
    </div>`;
  }).join('');
}

async function openQlModal(articleId) {
  QL_CURRENT_ARTICLE = articleId;
  const art = QL_DATA.articles.find(a => a.id === articleId);
  document.getElementById('ql-modal-title').textContent = art ? art.title : '';
  document.getElementById('ql-modal').style.display = 'flex';
  const body = document.getElementById('ql-modal-body');
  body.innerHTML = '<div class="spinner"></div>';

  const r = await fetch(`api/quality.php?project_id=${QL_PROJECT_ID}&article_id=${articleId}`);
  const d = await r.json();
  const opts = { yes: 'Sim', no: 'Não', unclear: 'Incerto', na: 'N/A' };

  body.innerHTML = d.questions.map((q, i) => {
    const current = d.answers[q.id]?.answer || '';
    return `
    <div style="padding:10px 0;border-bottom:1px solid var(--border)">
      <div style="font-size:.84rem;margin-bottom:8px"><strong>Q${i + 1}.</strong> ${escHtml(q.question)}</div>
      <div style="display:flex;gap:6px;flex-wrap:wrap">
        ${Object.entries(opts).map(([k, label]) => `
          <button class="btn btn-sm ${current === k ? (k === 'yes' ? 'btn-success' : k === 'no' ? 'btn-danger' : 'btn-primary') : 'btn-ghost'}"
                  id="ql-q${q.id}-${k}" onclick="answerQuestion(${q.id}, '${k}')">${label}</button>`).join('')}
      </div>
    </div>`;
  }).join('');
}

async function answerQuestion(questionId, answer) {
  await fetch('api/quality.php', {
    method: 'POST', headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'answer', article_id: QL_CURRENT_ARTICLE, question_id: questionId, answer }),
  });
  openQlModal(QL_CURRENT_ARTICLE); // re-render with the answer marked
}

function closeQlModal() {
  document.getElementById('ql-modal').style.display = 'none';
  loadQuality();
}

async function createInstrument(template) {
  const r = await fetch('api/quality.php', {
    method: 'POST', headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'create_instrument', project_id: QL_PROJECT_ID, template }),
  });
  const d = await r.json();
  if (d.error) { alert(d.message); return; }
  loadQuality();
}

async function addQuestion() {
  const q = document.getElementById('ql-new-question').value.trim();
  if (!q || !QL_DATA.instrument) return;
  await fetch('api/quality.php', {
    method: 'POST', headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'add_question', instrument_id: QL_DATA.instrument.id, question: q }),
  });
  loadQuality();
}

async function deleteQuestion(id) {
  if (!confirm('Remover esta pergunta e suas respostas?')) return;
  await fetch(`api/quality.php?question_id=${id}`, { method: 'DELETE' });
  loadQuality();
}

loadQuality();
</script>
<?php endif; ?>
