<?php
/**
 * PRISMA-SLR - index.php
 * Main entry point / router
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';

// Unauthenticated visitors see the public landing page (presentation + login).
// Authenticated users proceed to the app as usual.
if (!isLoggedIn()) {
    require __DIR__ . '/landing.php';
    exit;
}
$user = currentUser();

// ----- Routing -----
$validPages = [
    'dashboard', 'projects', 'import', 'articles',
    'duplicates', 'screening', 'eligibility', 'included',
    'agreement', 'quality', 'pico',
    'prisma-flow', 'bibliometrics', 'export', 'checklist', 'references',
    'admin',
];

$page      = $_GET['page']       ?? 'dashboard';
$projectId = (int) ($_GET['project_id'] ?? 0);

if (!in_array($page, $validPages)) $page = 'dashboard';

// ----- Current project data (from the logged-in user, or any project if admin) -----
$isAdmin = ($user['role'] ?? '') === 'admin';
$currentProject = null;
if ($projectId) {
    try {
        if ($isAdmin) {
            $stmt = getDB()->prepare("SELECT id, title, status FROM projects WHERE id = ?");
            $stmt->execute([$projectId]);
        } else {
            $stmt = getDB()->prepare("
                SELECT id, title, status FROM projects
                WHERE id = ? AND (user_id = ?
                      OR id IN (SELECT project_id FROM project_members WHERE user_id = ?))");
            $stmt->execute([$projectId, $user['id'], $user['id']]);
        }
        $currentProject = $stmt->fetch();
        if (!$currentProject) { $projectId = 0; }
    } catch (Throwable) { $projectId = 0; }
}

// ----- List of the logged-in user's projects -----
$projectList = [];
try {
    $stmt = getDB()->prepare("
        SELECT id, title FROM projects
        WHERE user_id = ?
           OR id IN (SELECT project_id FROM project_members WHERE user_id = ?)
        ORDER BY created_at DESC LIMIT 50");
    $stmt->execute([$user['id'], $user['id']]);
    $projectList = $stmt->fetchAll();
} catch (Throwable) {}

// Admin accessing another user's project via URL: ensures it
// appears in the selector, since it isn't among the admin's own projects.
if ($isAdmin && $currentProject && !in_array($projectId, array_column($projectList, 'id'))) {
    array_unshift($projectList, ['id' => $currentProject['id'], 'title' => $currentProject['title']]);
}

// ----- Page labels -----
$pageLabels = [
    'dashboard'    => 'Dashboard',
    'projects'     => 'Projetos',
    'import'       => 'Importação',
    'articles'     => 'Artigos',
    'duplicates'   => 'Duplicatas',
    'screening'    => 'Triagem',
    'eligibility'  => 'Elegibilidade',
    'included'     => 'Incluídos',
    'agreement'    => 'Concordância entre Revisores',
    'quality'      => 'Avaliação de Qualidade',
    'pico'         => 'Extração PICO',
    'prisma-flow'  => 'Diagrama PRISMA',
    'bibliometrics'=> 'Bibliometria',
    'export'       => 'Exportação',
    'checklist'    => 'Checklist PRISMA',
    'references'   => 'Referências',
    'admin'        => 'Administração',
];

$pageTitle = $pageLabels[$page] ?? 'PRISMA-SLR';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?> — BaseRef</title>
  <meta name="description" content="BaseRef — Plataforma de apoio à pesquisa e gestão de referências bibliográficas">

  <!-- Fonts -->
  <link rel="stylesheet" href="assets/vendor/inter-gfonts-v20/inter.css">

  <!-- Icons -->
  <link rel="stylesheet" href="assets/vendor/fontawesome-6.5.1/css/all.min.css">

  <!-- Styles -->
  <link rel="stylesheet" href="assets/css/style.css?v=<?= @filemtime(__DIR__ . '/assets/css/style.css') ?>">

  <!-- GTranslate (gtranslate.io) - PT / EN / ES translation -->
  <style>
  .goog-te-banner-frame { display:none !important; }
  body { top: 0px !important; }
  .skiptranslate > iframe { display:none !important; }
  /* Styling for the gtranslate widget to match the theme */
  .gtranslate_wrapper { display:inline-flex; gap:4px; align-items:center; }
  .gtranslate_wrapper a.glink { display:inline-flex; align-items:center; padding:2px; border-radius:5px; transition:opacity .15s; opacity:.55; }
  .gtranslate_wrapper a.glink.gt-current-lang { opacity:1; background:var(--bg-card); border:1px solid var(--border); }
  .gtranslate_wrapper a.glink:hover { opacity:1; }
  .gtranslate_wrapper img { display:block; }
  </style>
  <script>
  window.gtranslateSettings = {
    "default_language": "pt",
    "languages": ["pt","en","es"],
    "wrapper_selector": ".gtranslate_wrapper",
    "alt_flags": {"pt":"brazil","en":"usa"},
    "flag_size": 24,
    "flag_style": "3d",
  };
  </script>
  <script src="https://cdn.gtranslate.net/widgets/latest/flags.js" defer></script>

  <!-- Chart.js -->
  <script src="assets/vendor/chart.js-4.4.3/chart.umd.min.js"></script>
  <!-- vis.js Network -->
  <script src="assets/vendor/vis-network-9.1.9/vis-network.min.js"></script>
  <!-- QRCode.js -->
  <script src="assets/vendor/qrcodejs-1.0.0/qrcode.min.js"></script>
  <!-- WordCloud2.js -->
  <script src="assets/vendor/wordcloud2-1.2.2/wordcloud2.min.js"></script>
  <!-- FileSaver.js -->
  <script src="assets/vendor/filesaver-2.0.5/FileSaver.min.js"></script>
  <!-- D3.js + TopoJSON (choropleth map) -->
  <script src="assets/vendor/d3-7.9.0/d3.min.js"></script>
  <script src="assets/vendor/topojson-client-3.1.0/topojson-client.min.js"></script>
</head>
<body>

<!-- Apply the saved theme BEFORE rendering to avoid a flash -->
<script>
(function(){
  var t = localStorage.getItem('prisma-theme') || 'dark';
  if (t === 'light') document.body.classList.add('theme-light');
})();
</script>

<!-- ================================================
     SIDEBAR
     ================================================ -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo" style="padding:14px 16px 10px;border-bottom:1px solid var(--border)">
    <img src="assets/img/baseref_dark.png" alt="BaseRef" id="sidebarLogo"
         style="width:100%;max-width:180px;height:auto;display:block;transition:opacity .2s">
  </div>

  <!-- Project Selector -->
  <div class="sidebar-project">
    <label for="project-select"><i class="fa fa-folder" style="margin-right:4px"></i> <span data-i18n="sidebar.active_project">Projeto Ativo</span></label>
    <select id="project-select" onchange="switchProject(this.value)">
      <option value="0" <?= $projectId === 0 ? 'selected' : '' ?>>— <span data-i18n="sidebar.select_project">Selecione um projeto</span> —</option>
      <?php foreach ($projectList as $p): ?>
        <option value="<?= $p['id'] ?>" <?= $p['id'] === $projectId ? 'selected' : '' ?>>
          <?= htmlspecialchars($p['title']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <!-- Navigation -->
  <nav class="sidebar-nav">
    <div class="nav-section-title" data-i18n="nav.sec_overview">Visão Geral</div>
    <?= navItem('dashboard', $page, $projectId, 'fa-house',          'Dashboard',       'nav.dashboard') ?>
    <?= navItem('projects',  $page, $projectId, 'fa-folder-open',    'Projetos',        'nav.projects') ?>

    <div class="nav-section-title" style="margin-top:6px" data-i18n="nav.sec_prisma">Processo PRISMA</div>
    <?= navItem('import',       $page, $projectId, 'fa-file-import',      'Importação',     'nav.import') ?>
    <?= navItem('duplicates',   $page, $projectId, 'fa-copy',             'Duplicatas',     'nav.duplicates') ?>
    <?= navItem('screening',    $page, $projectId, 'fa-magnifying-glass', 'Triagem',        'nav.screening') ?>
    <?= navItem('eligibility',  $page, $projectId, 'fa-check-double',     'Elegibilidade',  'nav.eligibility') ?>
    <?= navItem('included',     $page, $projectId, 'fa-circle-check',     'Incluídos',      'nav.included') ?>
    <?= navItem('agreement',    $page, $projectId, 'fa-people-arrows',    'Concordância (κ)', '') ?>
    <?= navItem('quality',      $page, $projectId, 'fa-clipboard-check',  'Qualidade/Viés',   '') ?>
    <?= navItem('pico',         $page, $projectId, 'fa-table-cells',      'Extração PICO',    '') ?>

    <div class="nav-section-title" style="margin-top:6px" data-i18n="nav.sec_analysis">Análise</div>
    <?= navItem('bibliometrics', $page, $projectId, 'fa-chart-bar',      'Bibliometria',       'nav.bibliometrics') ?>
    <?= navItem('prisma-flow',   $page, $projectId, 'fa-diagram-project','Diagrama PRISMA',    'nav.prisma_flow') ?>
    <?= navItem('checklist',     $page, $projectId, 'fa-list-check',     'Checklist PRISMA',   'nav.checklist') ?>

    <div class="nav-section-title" style="margin-top:6px" data-i18n="nav.sec_data">Dados</div>
    <?= navItem('articles',    $page, $projectId, 'fa-book',        'Todos os Artigos', 'nav.articles') ?>
    <?= navItem('references',  $page, $projectId, 'fa-quote-right', 'Referências',      'nav.references') ?>
    <?= navItem('export',      $page, $projectId, 'fa-upload',      'Exportação',       'nav.export') ?>

    <?php if (($user['role'] ?? '') === 'admin'): ?>
    <div class="nav-section-title" style="margin-top:6px">Sistema</div>
    <?= navItem('admin', $page, $projectId, 'fa-shield-halved', 'Administração', '') ?>
    <?php endif; ?>

    <div class="nav-section-title" style="margin-top:6px">Suporte</div>

    <!-- Help menu with submenu -->
    <div id="ajudaMenu">
      <button onclick="toggleAjudaMenu()"
        style="width:100%;display:flex;align-items:center;gap:10px;background:transparent;border:none;
               -webkit-appearance:none;appearance:none;
               cursor:pointer;padding:8px 12px;border-radius:8px;color:var(--text-muted);
               font-size:.875rem;font-weight:500;text-align:left;transition:background .15s,color .15s"
        onmouseover="this.style.background='var(--primary-dim)';this.style.color='var(--primary)'"
        onmouseout="if(!document.getElementById('ajudaSubmenu').classList.contains('open')){this.style.background='';this.style.color='var(--text-muted)'}">
        <i class="fa fa-circle-question" style="width:18px;text-align:center"></i>
        <span style="flex:1">Ajuda</span>
        <i class="fa fa-chevron-down" id="ajudaChevron" style="font-size:.65rem;transition:transform .2s"></i>
      </button>
      <div id="ajudaSubmenu" style="overflow:hidden;max-height:0;transition:max-height .25s ease">
        <button onclick="openVideoModal()"
          style="width:100%;display:flex;align-items:center;gap:10px;background:transparent;border:none;
                 -webkit-appearance:none;appearance:none;
                 cursor:pointer;padding:7px 12px 7px 40px;border-radius:8px;color:var(--text-muted);
                 font-size:.84rem;font-weight:500;text-align:left;transition:background .15s,color .15s"
          onmouseover="this.style.background='rgba(239,68,68,.1)';this.style.color='#ef4444'"
          onmouseout="this.style.background='';this.style.color='var(--text-muted)'">
          <i class="fa fa-circle-play" style="color:#ef4444;width:16px;text-align:center"></i> Tutorial em Vídeo
        </button>
        <a href="TUTORIAL.pdf" target="_blank" rel="noopener"
          style="display:flex;align-items:center;gap:10px;padding:7px 12px 7px 40px;border-radius:8px;
                 color:var(--text-muted);font-size:.84rem;font-weight:500;text-decoration:none;transition:background .15s,color .15s"
          onmouseover="this.style.background='var(--primary-dim)';this.style.color='var(--primary)'"
          onmouseout="this.style.background='';this.style.color='var(--text-muted)'">
          <i class="fa fa-file-pdf" style="color:#ef4444;width:16px;text-align:center"></i> Tutorial em PDF
        </a>
      </div>
    </div>
    <script>
    function toggleAjudaMenu(){
      var sub=document.getElementById('ajudaSubmenu');
      var chev=document.getElementById('ajudaChevron');
      var btn=document.querySelector('#ajudaMenu button');
      var open=sub.classList.toggle('open');
      sub.style.maxHeight=open?sub.scrollHeight+'px':'0';
      chev.style.transform=open?'rotate(180deg)':'rotate(0deg)';
      btn.style.background=open?'var(--primary-dim)':'';
      btn.style.color=open?'var(--primary)':'var(--text-muted)';
    }
    </script>
  </nav>

  <!-- Logged-in user -->
  <div class="sidebar-footer">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px">
      <?php if (!empty($user['picture'])): ?>
        <img src="<?= htmlspecialchars($user['picture']) ?>" alt="Foto"
             style="width:32px;height:32px;border-radius:50%;object-fit:cover;border:2px solid var(--primary);flex-shrink:0">
      <?php else: ?>
        <div style="width:32px;height:32px;border-radius:50%;background:var(--primary);display:flex;align-items:center;justify-content:center;flex-shrink:0">
          <i class="fa fa-user" style="color:#fff;font-size:14px"></i>
        </div>
      <?php endif; ?>
      <div style="min-width:0;flex:1">
        <div style="font-size:.8rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
          <?= htmlspecialchars($user['name']) ?>
        </div>
        <div style="font-size:.68rem;color:var(--text-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
          <?= htmlspecialchars($user['email']) ?>
        </div>
      </div>
    </div>
    <a href="auth/logout.php" style="display:flex;align-items:center;gap:6px;font-size:.78rem;color:var(--text-muted);text-decoration:none;padding:5px 8px;border-radius:6px;transition:background .15s,color .15s"
       onmouseover="this.style.background='rgba(239,68,68,.12)';this.style.color='#fca5a5'"
       onmouseout="this.style.background='';this.style.color='var(--text-muted)'">
      <i class="fa fa-right-from-bracket"></i> Sair
    </a>
    <div style="margin-top:8px;border-top:1px solid var(--border);padding-top:8px;font-size:0.68rem;color:var(--text-muted)">
      BaseRef v1.0 · PRISMA 2020
    </div>
  </div>
</aside>

<button type="button" class="sidebar-backdrop" id="sidebar-backdrop" aria-label="Fechar menu"
        onclick="closeSidebar()"></button>

<!-- ================================================
     MAIN CONTENT
     ================================================ -->
<div class="main">
  <?php if (isImpersonating()): $impersonator = impersonatorInfo(); ?>
  <div id="impersonation-banner" style="flex-shrink:0;display:flex;align-items:center;
       justify-content:center;gap:14px;flex-wrap:wrap;background:#ca8a04;color:#1c1300;
       padding:8px 16px;font-size:.82rem;font-weight:600">
    <span><i class="fa fa-user-secret"></i> Visualizando como <?= htmlspecialchars($user['name']) ?> (<?= htmlspecialchars($user['email']) ?>)
      — logado como <?= htmlspecialchars($impersonator['email'] ?? '') ?></span>
    <button type="button" onclick="stopImpersonation()"
            style="background:#1c1300;color:#fff;border:none;border-radius:6px;padding:4px 12px;cursor:pointer;font-weight:700;font-size:.78rem">
      <i class="fa fa-arrow-left"></i> Voltar ao admin
    </button>
  </div>
  <script>
  function stopImpersonation() {
    fetch('api/admin.php?action=stop_impersonate', { method: 'POST' })
      .then(r => r.json())
      .then(() => { window.location.href = '?page=admin'; })
      .catch(() => { window.location.href = '?page=admin'; });
  }
  </script>
  <?php endif; ?>
  <!-- Header -->
  <header class="header">
    <button class="btn btn-ghost btn-sm" id="sidebar-toggle" onclick="toggleSidebar()"
            aria-label="Abrir menu lateral" aria-controls="sidebar" aria-expanded="false">
      <i class="fa fa-bars"></i>
    </button>

    <div class="header-title">
      <?php if ($currentProject): ?>
        <span class="header-project-context text-muted text-sm"><i class="fa fa-folder" style="color:var(--primary)"></i>
          <?= htmlspecialchars($currentProject['title']) ?></span>
        <span class="header-separator" style="color:var(--text-muted);margin:0 8px">›</span>
      <?php endif; ?>
      <span data-i18n="page.<?= $page ?>"><?= htmlspecialchars($pageTitle) ?></span>
    </div>

    <div class="header-actions">
      <?php if ($currentProject): ?>
        <span class="badge badge-<?= $currentProject['status'] ?>"><?= $currentProject['status'] ?></span>
      <?php endif; ?>

      <!-- gtranslate.io translation widget (PT / EN / ES) -->
      <div class="gtranslate_wrapper" title="Idioma"></div>


      <a href="?page=projects" class="btn btn-ghost btn-sm" title="Gerenciar Projetos">
        <i class="fa fa-folder-open"></i>
      </a>

      <!-- Light/dark theme toggle button -->
      <button class="btn btn-ghost btn-sm" id="theme-toggle" onclick="toggleTheme()"
              title="Alternar tema claro/escuro" style="min-width:36px;justify-content:center">
        <i class="fa fa-moon" id="theme-icon"></i>
      </button>
    </div>
  </header>

  <!-- Page Content -->
  <main class="content">
    <?php
    // Load the page
    $pageFile = __DIR__ . '/pages/' . $page . '.php';
    if (file_exists($pageFile)) {
        include $pageFile;
    } else {
        echo '<div class="empty-state"><i class="fa fa-triangle-exclamation"></i>'
           . '<p>Página não encontrada: <code>' . htmlspecialchars($page) . '</code></p></div>';
    }
    ?>
  </main>
</div>

<!-- Toast Container -->
<div id="toast-container"></div>

<!-- ================================================
     MODAL: Video Tutorial
     ================================================ -->
<div id="video-modal" style="display:none;position:fixed;inset:0;z-index:9999;
     background:rgba(0,0,0,.75);align-items:center;justify-content:center"
     onclick="if(event.target===this)closeVideoModal()">
  <div style="position:relative;width:min(860px,96vw);background:var(--bg-card);
              border-radius:16px;overflow:hidden;box-shadow:0 24px 64px rgba(0,0,0,.6)">
    <!-- Modal header -->
    <div style="display:flex;align-items:center;justify-content:space-between;
                padding:16px 20px;border-bottom:1px solid var(--border)">
      <div style="display:flex;align-items:center;gap:10px">
        <i class="fa fa-circle-play" style="color:#ef4444;font-size:1.2rem"></i>
        <span style="font-weight:700;font-size:.95rem">Tutorial em Vídeo — BaseRef</span>
      </div>
      <button onclick="closeVideoModal()"
              style="background:none;border:none;cursor:pointer;color:var(--text-muted);
                     font-size:1.2rem;padding:4px;border-radius:6px;line-height:1"
              title="Fechar">
        <i class="fa fa-xmark"></i>
      </button>
    </div>
    <!-- YouTube iframe -->
    <div style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden">
      <iframe id="video-iframe"
              src=""
              allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
              allowfullscreen
              style="position:absolute;top:0;left:0;width:100%;height:100%;border:none">
      </iframe>
    </div>
    <!-- Footer -->
    <div style="padding:12px 20px;border-top:1px solid var(--border);
                display:flex;align-items:center;justify-content:space-between">
      <span style="font-size:.78rem;color:var(--text-muted)">
        Aprenda a usar o BaseRef passo a passo
      </span>
      <a href="https://youtu.be/B_sD4ZO_xMU" target="_blank" rel="noopener"
         class="btn btn-ghost btn-sm" style="font-size:.78rem">
        <i class="fa fa-arrow-up-right-from-square"></i> Abrir no YouTube
      </a>
    </div>
  </div>
</div>



<!-- ================================================
     SCRIPTS
     ================================================ -->
<!-- Global JS configuration -->
<script>
const APP = {
  projectId:   <?= $projectId ?>,
  currentPage: '<?= $page ?>',
  baseUrl:     '<?= rtrim((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
               . '://' . $_SERVER['HTTP_HOST']
               . dirname($_SERVER['PHP_SELF']), '/') ?>',
};
</script>

<?php
// Cache-busting based on the file's mtime (forces reload after edits)
$jsVer = fn($f) => '?v=' . @filemtime(__DIR__ . '/' . $f);
?>
<script src="assets/js/app.js<?= $jsVer('assets/js/app.js') ?>"></script>

<!-- Page-specific scripts -->
<?php if ($page === 'dashboard'): ?>
<script src="assets/js/dashboard.js<?= $jsVer('assets/js/dashboard.js') ?>"></script>
<?php elseif ($page === 'import'): ?>
<script src="assets/js/import.js<?= $jsVer('assets/js/import.js') ?>"></script>
<?php elseif ($page === 'screening' || $page === 'eligibility'): ?>
<script src="assets/js/screening.js<?= $jsVer('assets/js/screening.js') ?>"></script>
<?php elseif ($page === 'bibliometrics'): ?>
<!-- SheetJS — generates .xlsx files in the browser to export each chart's data -->
<script src="assets/vendor/xlsx-0.18.5/xlsx.full.min.js"></script>
<script src="assets/js/bib-i18n.js<?= $jsVer('assets/js/bib-i18n.js') ?>"></script>
<script src="assets/js/bibliometrics.js<?= $jsVer('assets/js/bibliometrics.js') ?>"></script>
<?php elseif ($page === 'prisma-flow'): ?>
<script src="assets/js/prisma-flow.js<?= $jsVer('assets/js/prisma-flow.js') ?>"></script>
<?php endif; ?>

<script>
// ----- Light / dark theme -----
function applyTheme(theme) {
  if (theme === 'light') {
    document.body.classList.add('theme-light');
    document.getElementById('theme-icon').className = 'fa-solid fa-sun';
    document.getElementById('theme-toggle').title   = 'Mudar para tema escuro';
  } else {
    document.body.classList.remove('theme-light');
    document.getElementById('theme-icon').className = 'fa-solid fa-moon';
    document.getElementById('theme-toggle').title   = 'Mudar para tema claro';
  }
  var logo = theme === 'light' ? 'assets/img/baseref_light-2.png' : 'assets/img/baseref_dark.png';
  ['sidebarLogo','dashLogo'].forEach(function(id){var el=document.getElementById(id);if(el)el.src=logo;});
  localStorage.setItem('prisma-theme', theme);
}

function toggleTheme() {
  var current = localStorage.getItem('prisma-theme') || 'dark';
  applyTheme(current === 'dark' ? 'light' : 'dark');
}

// ----- PRISMA language (PT / EN / ES) — synced with gtranslate.io -----
window.PRISMA_LANG = localStorage.getItem('prisma-lang') || 'pt';

function setPrismaLang(lang) {
  window.PRISMA_LANG = lang;
  localStorage.setItem('prisma-lang', lang);
  // Redraws the SVG diagram on the prisma-flow page (and any other listener)
  document.dispatchEvent(new CustomEvent('prismaLangChanged', { detail: { lang } }));
}

// Intercepts clicks on the gtranslate flags to also update PRISMA_LANG
document.addEventListener('click', function(e) {
  var flag = e.target.closest('.gtranslate_wrapper a.glink[data-gt-lang]');
  if (!flag) return;
  var lang = flag.getAttribute('data-gt-lang');
  if (lang) setPrismaLang(lang);
});

// Sync theme on load
document.addEventListener('DOMContentLoaded', function() {
  var saved = localStorage.getItem('prisma-theme') || 'dark';
  applyTheme(saved);

  var savedLang = localStorage.getItem('prisma-lang') || 'pt';
  window.PRISMA_LANG = savedLang;

  // Restore saved language: clicks the matching flag once the widget loads
  if (savedLang !== 'pt') {
    var tries = 0;
    var iv = setInterval(function() {
      var flag = document.querySelector('.gtranslate_wrapper a.glink[data-gt-lang="' + savedLang + '"]');
      if (flag) {
        clearInterval(iv);
        flag.click();
      } else if (++tries > 30) {
        clearInterval(iv);
      }
    }, 200);
  }

  if (typeof initPage === 'function') initPage();
});

// ----- Video tutorial modal -----
function openVideoModal() {
  var modal = document.getElementById('video-modal');
  document.getElementById('video-iframe').src =
    'https://www.youtube.com/embed/B_sD4ZO_xMU?autoplay=1&rel=0';
  modal.style.display = 'flex';
  document.body.style.overflow = 'hidden';
}
function closeVideoModal() {
  var modal = document.getElementById('video-modal');
  modal.style.display = 'none';
  document.getElementById('video-iframe').src = '';
  document.body.style.overflow = '';
}
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') closeVideoModal();
});
</script>

</body>
</html>

<?php
// ----- Helper function: generates a navigation item -----
function navItem(string $pg, string $current, int $projectId, string $icon, string $label, string $i18nKey = ''): string
{
    $active   = $pg === $current ? ' active' : '';
    $href     = "?page={$pg}" . ($projectId ? "&project_id={$projectId}" : '');
    $iconHtml = "<i class=\"fa {$icon}\"></i>";
    $i18nAttr = $i18nKey ? " data-i18n=\"{$i18nKey}\"" : '';
    return "<a href=\"{$href}\" class=\"nav-item{$active}\"{$i18nAttr}>{$iconHtml} {$label}</a>\n";
}
?>
