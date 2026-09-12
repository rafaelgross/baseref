<?php
// Blocks direct access for non-admins (the main check is in index.php)
if (($user['role'] ?? '') !== 'admin') {
    echo '<div class="empty-state"><i class="fa fa-lock"></i><p>Acesso restrito a administradores.</p></div>';
    return;
}
?>

<style>
.admin-grid        { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:16px; margin-bottom:28px; }
.admin-stat        { background:var(--bg-card); border:1px solid var(--border); border-radius:12px; padding:20px 22px; }
.admin-stat-value  { font-size:2rem; font-weight:800; color:var(--primary); line-height:1; }
.admin-stat-label  { font-size:.78rem; color:var(--text-muted); margin-top:6px; }
.admin-stat-sub    { font-size:.72rem; color:var(--text-muted); margin-top:4px; }

.admin-section     { background:var(--bg-card); border:1px solid var(--border); border-radius:12px; padding:22px 24px; margin-bottom:24px; }
.admin-section h2  { font-size:1rem; font-weight:700; margin:0 0 18px; display:flex; align-items:center; gap:8px; }

.user-table        { width:100%; border-collapse:collapse; font-size:.82rem; }
.user-table th     { text-align:left; padding:8px 10px; border-bottom:1px solid var(--border); color:var(--text-muted); font-weight:600; font-size:.74rem; text-transform:uppercase; letter-spacing:.04em; }
.user-table td     { padding:10px 10px; border-bottom:1px solid var(--border); vertical-align:middle; }
.user-table tr:last-child td { border-bottom:none; }
.user-table tr:hover td { background:rgba(99,102,241,.05); }

.avatar-sm         { width:28px; height:28px; border-radius:50%; object-fit:cover; flex-shrink:0; }
.avatar-placeholder{ width:28px; height:28px; border-radius:50%; background:var(--primary); display:inline-flex; align-items:center; justify-content:center; font-size:12px; color:#fff; flex-shrink:0; }

.badge-admin       { background:rgba(234,179,8,.15); color:#ca8a04; border:1px solid rgba(234,179,8,.3); padding:2px 8px; border-radius:20px; font-size:.7rem; font-weight:600; }
.badge-user        { background:rgba(99,102,241,.12); color:var(--primary); border:1px solid rgba(99,102,241,.25); padding:2px 8px; border-radius:20px; font-size:.7rem; font-weight:600; }

.chart-wrap        { position:relative; height:200px; }
.rating-badge      { display:inline-flex; align-items:center; gap:4px; padding:3px 8px; border-radius:999px; font-size:.72rem; font-weight:700; }
.rating-good       { background:rgba(34,197,94,.14); color:#16a34a; border:1px solid rgba(34,197,94,.25); }
.rating-mid        { background:rgba(245,158,11,.14); color:#d97706; border:1px solid rgba(245,158,11,.25); }
.rating-low        { background:rgba(239,68,68,.14); color:#dc2626; border:1px solid rgba(239,68,68,.25); }
.feedback-grid     { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:16px; margin-bottom:18px; }
.feedback-card     { background:var(--bg-card); border:1px solid var(--border); border-radius:12px; padding:18px 20px; }
.feedback-value    { font-size:1.65rem; font-weight:800; color:var(--primary); line-height:1; }
.feedback-label    { font-size:.76rem; color:var(--text-muted); margin-top:8px; }
</style>

<!-- Cartões de resumo -->
<div class="admin-grid" id="admin-stats">
  <div class="admin-stat">
    <div class="admin-stat-value" id="stat-users">–</div>
    <div class="admin-stat-label"><i class="fa fa-users"></i> Usuários totais</div>
    <div class="admin-stat-sub" id="stat-users-sub"></div>
  </div>
  <div class="admin-stat">
    <div class="admin-stat-value" id="stat-today">–</div>
    <div class="admin-stat-label"><i class="fa fa-calendar-day"></i> Ativos hoje</div>
    <div class="admin-stat-sub" id="stat-7d"></div>
  </div>
  <div class="admin-stat">
    <div class="admin-stat-value" id="stat-projects">–</div>
    <div class="admin-stat-label"><i class="fa fa-folder-open"></i> Projetos totais</div>
  </div>
  <div class="admin-stat">
    <div class="admin-stat-value" id="stat-articles">–</div>
    <div class="admin-stat-label"><i class="fa fa-book"></i> Artigos totais</div>
  </div>
</div>

<!-- Gráfico de crescimento -->
<div class="admin-section">
  <h2><i class="fa fa-chart-line" style="color:var(--primary)"></i> Novos usuários (últimos 12 meses)</h2>
  <div class="chart-wrap"><canvas id="growth-chart"></canvas></div>
</div>

<!-- Tabela de usuários -->
<div class="admin-section">
  <h2><i class="fa fa-users" style="color:var(--primary)"></i> Todos os usuários</h2>
  <div style="overflow-x:auto">
    <table class="user-table">
      <thead>
        <tr>
          <th>Usuário</th>
          <th>E-mail</th>
          <th>Perfil</th>
          <th style="text-align:center">Projetos</th>
          <th style="text-align:center">Artigos</th>
          <th>Último acesso</th>
          <th>Membro desde</th>
          <th></th>
        </tr>
      </thead>
      <tbody id="users-tbody">
        <tr><td colspan="8" style="text-align:center;color:var(--text-muted);padding:24px">Carregando...</td></tr>
      </tbody>
    </table>
  </div>
</div>

<!-- Avaliações do sistema -->
<div class="admin-section">
  <h2><i class="fa fa-star" style="color:var(--primary)"></i> Avaliações do Sistema</h2>
  <div class="feedback-grid" id="feedback-stats">
    <div class="feedback-card">
      <div class="feedback-value" id="feedback-total">–</div>
      <div class="feedback-label">Avaliações recebidas</div>
    </div>
    <div class="feedback-card">
      <div class="feedback-value" id="feedback-average">–</div>
      <div class="feedback-label">Média geral das notas</div>
    </div>
    <div class="feedback-card">
      <div class="feedback-value" id="feedback-suggestions">–</div>
      <div class="feedback-label">Com sugestão escrita</div>
    </div>
    <div class="feedback-card">
      <div class="feedback-value" id="feedback-last7d">–</div>
      <div class="feedback-label">Novas nos últimos 7 dias</div>
    </div>
  </div>
  <div style="overflow-x:auto">
    <table class="user-table">
      <thead>
        <tr>
          <th>Usuário</th>
          <th>Nota</th>
          <th>Projeto</th>
          <th>Contexto</th>
          <th>Sugestão</th>
          <th>Enviada em</th>
        </tr>
      </thead>
      <tbody id="feedback-tbody">
        <tr><td colspan="6" style="text-align:center;color:var(--text-muted);padding:24px">Carregando...</td></tr>
      </tbody>
    </table>
  </div>
</div>

<script>
(function () {
  // ── Overview ────────────────────────────────────────────────────────────────
  fetch('api/admin.php?action=overview')
    .then(r => r.json())
    .then(d => {
      document.getElementById('stat-users').textContent    = d.total_users    ?? '–';
      document.getElementById('stat-today').textContent   = d.users_today    ?? '–';
      document.getElementById('stat-projects').textContent = d.total_projects ?? '–';
      document.getElementById('stat-articles').textContent = d.total_articles ?? '–';
      document.getElementById('stat-users-sub').textContent = `+${d.users_30d ?? 0} nos últimos 30 dias`;
      document.getElementById('stat-7d').textContent       = `${d.users_7d ?? 0} ativos nos últimos 7 dias`;
    });

  // ── Growth chart ────────────────────────────────────────────────────────────
  fetch('api/admin.php?action=growth')
    .then(r => r.json())
    .then(rows => {
      const labels = rows.map(r => r.month);
      const data   = rows.map(r => parseInt(r.new_users, 10));
      new Chart(document.getElementById('growth-chart'), {
        type: 'bar',
        data: {
          labels,
          datasets: [{
            label: 'Novos usuários',
            data,
            backgroundColor: 'rgba(99,102,241,.6)',
            borderColor: 'rgba(99,102,241,1)',
            borderWidth: 1,
            borderRadius: 6,
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { display: false } },
          scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1, color: 'var(--text-muted)' }, grid: { color: 'rgba(255,255,255,.06)' } },
            x: { ticks: { color: 'var(--text-muted)' }, grid: { display: false } }
          }
        }
      });
    });

  // ── User table ──────────────────────────────────────────────────────────────
  fetch('api/admin.php?action=users')
    .then(r => r.json())
    .then(rows => {
      const tbody = document.getElementById('users-tbody');
      if (!rows.length) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:var(--text-muted);padding:24px">Nenhum usuário encontrado.</td></tr>';
        return;
      }
      tbody.innerHTML = rows.map(u => {
        const avatar = u.picture
          ? `<img src="${escHtml(u.picture)}" class="avatar-sm" alt="">`
          : `<span class="avatar-placeholder"><i class="fa fa-user"></i></span>`;
        const badge  = u.role === 'admin'
          ? '<span class="badge-admin">admin</span>'
          : '<span class="badge-user">user</span>';
        const lastLogin = u.last_login ? formatDate(u.last_login) : '—';
        const createdAt = u.created_at ? formatDate(u.created_at) : '—';
        return `<tr>
          <td style="display:flex;align-items:center;gap:8px;white-space:nowrap">${avatar} ${escHtml(u.name)}</td>
          <td style="color:var(--text-muted)">${escHtml(u.email)}</td>
          <td>${badge}</td>
          <td style="text-align:center">${u.total_projects}</td>
          <td style="text-align:center">${u.total_articles}</td>
          <td style="color:var(--text-muted)">${lastLogin}</td>
          <td style="color:var(--text-muted)">${createdAt}</td>
          <td style="white-space:nowrap">
            <button type="button" class="btn btn-ghost btn-sm" onclick="viewAsUser(${u.id}, '${escHtml(u.name).replace(/'/g, "\\'")}')" title="Ver/editar como este usuário">
              <i class="fa fa-eye"></i> Ver como
            </button>
          </td>
        </tr>`;
      }).join('');
    });

  window.viewAsUser = function (userId, userName) {
    fetch('api/admin.php?action=impersonate', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ user_id: userId }),
    })
      .then(r => r.json())
      .then(d => {
        if (d.error) { alert(d.message || 'Não foi possível entrar como este usuário.'); return; }
        window.location.href = '?page=dashboard';
      })
      .catch(() => alert('Erro de conexão ao tentar entrar como este usuário.'));
  };

  // ── Feedback summary ────────────────────────────────────────────────────────
  fetch('api/admin.php?action=feedback_summary')
    .then(r => r.json())
    .then(d => {
      document.getElementById('feedback-total').textContent = d.total_feedback ?? '0';
      document.getElementById('feedback-average').textContent = d.total_feedback ? `${Number(d.avg_rating ?? 0).toFixed(2)} / 5` : '–';
      document.getElementById('feedback-suggestions').textContent = d.with_suggestion ?? '0';
      document.getElementById('feedback-last7d').textContent = d.last_7d ?? '0';
    });

  // ── Feedback list ───────────────────────────────────────────────────────────
  fetch('api/admin.php?action=feedback_list')
    .then(r => r.json())
    .then(rows => {
      const tbody = document.getElementById('feedback-tbody');
      if (!rows.length) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:var(--text-muted);padding:24px">Nenhuma avaliação registrada ainda.</td></tr>';
        return;
      }
      tbody.innerHTML = rows.map(item => {
        const badgeClass = item.rating >= 4 ? 'rating-good' : item.rating === 3 ? 'rating-mid' : 'rating-low';
        const suggestion = item.suggestion
          ? escHtml(item.suggestion.length > 180 ? item.suggestion.slice(0, 180) + '…' : item.suggestion)
          : '<span style="color:var(--text-muted)">—</span>';
        return `<tr>
          <td>
            <div style="font-weight:600">${escHtml(item.user_name || '—')}</div>
            <div style="font-size:.75rem;color:var(--text-muted)">${escHtml(item.user_email || '')}</div>
          </td>
          <td><span class="rating-badge ${badgeClass}"><i class="fa fa-star"></i> ${item.rating}/5</span></td>
          <td style="color:var(--text-muted)">${escHtml(item.project_title || 'Sem projeto')}</td>
          <td style="color:var(--text-muted)">${escHtml(item.page_context || '—')}</td>
          <td style="max-width:360px;line-height:1.5">${suggestion}</td>
          <td style="color:var(--text-muted)">${formatDate(item.created_at)}</td>
        </tr>`;
      }).join('');
    });

  function escHtml(s) {
    return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  }
  function formatDate(s) {
    if (!s) return '—';
    const d = new Date(s.replace(' ', 'T'));
    return isNaN(d) ? s : d.toLocaleDateString('pt-BR') + ' ' + d.toLocaleTimeString('pt-BR', {hour:'2-digit',minute:'2-digit'});
  }
})();
</script>
