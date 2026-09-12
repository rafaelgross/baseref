<?php
/**
 * PRISMA-SLR - Page: Inter-rater Agreement (Cohen's kappa)
 * Multi-reviewer mode: reviewer management, kappa, contingency table,
 * and conflict resolution by consensus.
 */
if (!$projectId): ?>
<div class="empty-state"><i class="fa fa-triangle-exclamation"></i>
  <p>Selecione um projeto para ver a concordância entre revisores.</p></div>
<?php else: ?>

<div class="page-header">
  <div>
    <h1 class="page-title"><i class="fa fa-people-arrows"></i> Concordância entre Revisores</h1>
    <p class="page-subtitle">Triagem dupla-cega com kappa de Cohen e resolução de conflitos por consenso.</p>
  </div>
  <div style="display:flex;gap:10px;align-items:center">
    <label style="font-size:0.82rem;color:var(--text-muted)">Fase:</label>
    <select class="form-control" id="agr-phase" style="width:auto" onchange="loadAgreement()">
      <option value="screening">Triagem (título/resumo)</option>
      <option value="eligibility">Elegibilidade (texto completo)</option>
    </select>
  </div>
</div>

<div class="grid-2" style="margin-bottom:20px">

  <!-- Configuração -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="fa fa-gear"></i> Modo de Triagem</div>
    </div>
    <div class="form-group">
      <select class="form-control" id="agr-mode" onchange="setMode()">
        <option value="single">Revisor único (padrão)</option>
        <option value="dual">Dupla-cega (2+ revisores independentes)</option>
      </select>
      <p style="font-size:.76rem;color:var(--text-muted);margin-top:8px;line-height:1.5">
        No modo dupla-cega, cada revisor decide sem ver o voto do outro. O artigo só avança
        quando os revisores concordam; divergências aparecem abaixo para resolução por consenso.
      </p>
    </div>

    <div class="card-header" style="margin-top:14px">
      <div class="card-title"><i class="fa fa-users"></i> Revisores do Projeto</div>
    </div>
    <div id="agr-members"><div class="spinner"></div></div>
    <div style="display:flex;gap:8px;margin-top:10px" id="agr-add-member-row">
      <input type="email" class="form-control" id="agr-member-email" placeholder="email do revisor (precisa já ter feito login)" style="flex:1">
      <button class="btn btn-primary btn-sm" onclick="addMember()"><i class="fa fa-user-plus"></i> Adicionar</button>
    </div>
  </div>

  <!-- Kappa -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="fa fa-chart-simple"></i> Kappa de Cohen</div>
    </div>
    <div id="agr-kappa"><div class="spinner"></div></div>
  </div>
</div>

<!-- Conflitos -->
<div class="card">
  <div class="card-header">
    <div class="card-title"><i class="fa fa-bolt"></i> Conflitos Pendentes</div>
  </div>
  <div id="agr-conflicts"><div class="spinner"></div></div>
</div>

<script>
const AGR_PROJECT_ID = <?= $projectId ?>;
let AGR_IS_OWNER = false;

async function loadAgreement() {
  const phase = document.getElementById('agr-phase').value;
  try {
    const r = await fetch(`api/kappa.php?project_id=${AGR_PROJECT_ID}&phase=${phase}`);
    const d = await r.json();
    if (d.error) throw new Error(d.message);

    AGR_IS_OWNER = !!d.is_owner;
    document.getElementById('agr-mode').value = d.screening_mode;
    document.getElementById('agr-mode').disabled = !AGR_IS_OWNER;
    document.getElementById('agr-add-member-row').style.display = AGR_IS_OWNER ? 'flex' : 'none';

    renderMembers(d.members);
    renderKappa(d);
    renderConflicts(d.conflicts, phase);
  } catch (e) {
    document.getElementById('agr-kappa').innerHTML = `<div class="alert alert-error">Erro: ${escHtml(e.message)}</div>`;
  }
}

function renderMembers(members) {
  const el = document.getElementById('agr-members');
  if (!members || !members.length) { el.innerHTML = '<p style="font-size:.8rem;color:var(--text-muted)">Nenhum revisor.</p>'; return; }
  el.innerHTML = members.map(m => `
    <div style="display:flex;align-items:center;justify-content:space-between;padding:6px 4px;border-bottom:1px solid var(--border)">
      <span style="font-size:.84rem">
        <i class="fa ${m.role === 'owner' ? 'fa-crown' : 'fa-user'}" style="color:var(--primary);width:18px"></i>
        ${escHtml(m.name)} <small class="text-muted">(${escHtml(m.email)})</small>
      </span>
      ${m.role !== 'owner' && AGR_IS_OWNER
        ? `<button class="btn btn-ghost btn-sm" title="Remover revisor" onclick="removeMember(${m.id})"><i class="fa fa-xmark"></i></button>`
        : `<span class="badge badge-pending" style="font-size:.66rem">${m.role === 'owner' ? 'dono' : 'revisor'}</span>`}
    </div>`).join('');
}

function renderKappa(d) {
  const el = document.getElementById('agr-kappa');
  if (d.kappa === null) {
    el.innerHTML = `<div class="empty-state" style="padding:24px"><i class="fa fa-chart-simple"></i>
      <p style="font-size:.82rem">Kappa indisponível: são necessários 2 revisores com decisões<br>
      independentes sobre os mesmos artigos nesta fase (atualmente: ${d.n_joint} em comum).</p></div>`;
    return;
  }
  const k = d.kappa;
  const color = k >= 0.61 ? 'var(--accent-green, #00e5a0)' : k >= 0.41 ? '#fbbf24' : '#f87171';
  const cats  = { include: 'Incluir', exclude: 'Excluir', uncertain: 'Incerto' };

  let table = '<table style="width:100%;border-collapse:collapse;font-size:.78rem;margin-top:12px"><thead><tr><th></th>';
  d.categories.forEach(c => table += `<th style="padding:6px;color:var(--text-muted)">R2: ${cats[c]}</th>`);
  table += '</tr></thead><tbody>';
  d.categories.forEach(c1 => {
    table += `<tr><td style="padding:6px;color:var(--text-muted);font-weight:600">R1: ${cats[c1]}</td>`;
    d.categories.forEach(c2 => {
      const v = d.contingency[c1][c2];
      table += `<td style="padding:6px;text-align:center;${c1 === c2 ? 'font-weight:700;color:' + color : ''}">${v}</td>`;
    });
    table += '</tr>';
  });
  table += '</tbody></table>';

  el.innerHTML = `
    <div style="text-align:center;padding:8px 0 4px">
      <span style="font-size:2.6rem;font-weight:800;color:${color}">κ = ${k.toFixed(3)}</span><br>
      <span style="font-size:.9rem;color:var(--text-muted)">${escHtml(d.interpretation)} (Landis &amp; Koch)</span><br>
      <span style="font-size:.78rem;color:var(--text-muted)">Concordância observada: ${d.observed_agreement_percent}% · ${d.n_joint} artigos avaliados em comum</span>
    </div>
    ${table}`;
}

function renderConflicts(conflicts, phase) {
  const el = document.getElementById('agr-conflicts');
  if (!conflicts || !conflicts.length) {
    el.innerHTML = '<div class="empty-state" style="padding:24px"><i class="fa fa-check-circle" style="color:var(--accent-green,#00e5a0)"></i><p style="font-size:.84rem">Nenhum conflito pendente nesta fase.</p></div>';
    return;
  }
  el.innerHTML = conflicts.map(c => `
    <div style="display:flex;gap:14px;align-items:center;justify-content:space-between;padding:10px 4px;border-bottom:1px solid var(--border);flex-wrap:wrap">
      <div style="flex:1;min-width:260px">
        <div style="font-weight:600;font-size:.86rem">${escHtml(c.title)}</div>
        <small class="text-muted">${c.year || '—'} · ${escHtml(c.journal || '')}</small><br>
        <small style="color:#fbbf24"><i class="fa fa-bolt"></i> ${escHtml(c.votes || '')}</small>
      </div>
      <div style="display:flex;gap:6px">
        <button class="btn btn-success btn-sm" onclick="resolveConflict(${c.id}, '${phase}', 'include')"><i class="fa fa-check"></i> Incluir</button>
        <button class="btn btn-danger btn-sm"  onclick="resolveConflict(${c.id}, '${phase}', 'exclude')"><i class="fa fa-ban"></i> Excluir</button>
      </div>
    </div>`).join('');
}

async function resolveConflict(articleId, phase, decision) {
  try {
    const r = await fetch('api/kappa.php', {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'consensus', article_id: articleId, phase, decision }),
    });
    const d = await r.json();
    if (d.error) throw new Error(d.message);
    typeof showToast === 'function' && showToast('Consenso registrado', 'success');
    loadAgreement();
  } catch (e) { alert('Erro: ' + e.message); }
}

async function setMode() {
  const mode = document.getElementById('agr-mode').value;
  try {
    const r = await fetch('api/kappa.php', {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'set_mode', project_id: AGR_PROJECT_ID, screening_mode: mode }),
    });
    const d = await r.json();
    if (d.error) throw new Error(d.message);
    typeof showToast === 'function' && showToast('Modo de triagem atualizado', 'success');
  } catch (e) { alert('Erro: ' + e.message); loadAgreement(); }
}

async function addMember() {
  const email = document.getElementById('agr-member-email').value.trim();
  if (!email) return;
  try {
    const r = await fetch('api/kappa.php', {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'add_member', project_id: AGR_PROJECT_ID, email }),
    });
    const d = await r.json();
    if (d.error) throw new Error(d.message);
    document.getElementById('agr-member-email').value = '';
    typeof showToast === 'function' && showToast(d.message, 'success');
    loadAgreement();
  } catch (e) { alert('Erro: ' + e.message); }
}

async function removeMember(userId) {
  if (!confirm('Remover este revisor do projeto?')) return;
  try {
    const r = await fetch('api/kappa.php', {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'remove_member', project_id: AGR_PROJECT_ID, user_id: userId }),
    });
    const d = await r.json();
    if (d.error) throw new Error(d.message);
    loadAgreement();
  } catch (e) { alert('Erro: ' + e.message); }
}

loadAgreement();
</script>
<?php endif; ?>
