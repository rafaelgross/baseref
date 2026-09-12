/**
 * PRISMA-SLR — PT / EN / ES translation dictionary
 * No external dependencies. Works on every page.
 */

window.I18N = {

  pt: {
    // App
    'app.subtitle':        'Revisão Sistemática',

    // Sidebar
    'sidebar.active_project': 'Projeto Ativo',
    'sidebar.select_project': 'Selecione um projeto',

    // Menu sections
    'nav.sec_overview':  'Visão Geral',
    'nav.sec_prisma':    'Processo PRISMA',
    'nav.sec_analysis':  'Análise',
    'nav.sec_data':      'Dados',

    // Menu items
    'nav.dashboard':     'Dashboard',
    'nav.projects':      'Projetos',
    'nav.import':        'Importação',
    'nav.duplicates':    'Duplicatas',
    'nav.screening':     'Triagem',
    'nav.eligibility':   'Elegibilidade',
    'nav.included':      'Incluídos',
    'nav.bibliometrics': 'Bibliometria',
    'nav.prisma_flow':   'Diagrama PRISMA',
    'nav.checklist':     'Checklist PRISMA',
    'nav.articles':      'Todos os Artigos',
    'nav.export':        'Exportação',

    // Page titles (header)
    'page.dashboard':     'Dashboard',
    'page.projects':      'Projetos',
    'page.import':        'Importação',
    'page.articles':      'Artigos',
    'page.duplicates':    'Duplicatas',
    'page.screening':     'Triagem',
    'page.eligibility':   'Elegibilidade',
    'page.included':      'Incluídos',
    'page.prisma-flow':   'Diagrama PRISMA',
    'page.bibliometrics': 'Bibliometria',
    'page.export':        'Exportação',
    'page.checklist':     'Checklist PRISMA',

    // Status badges
    'status.active':      'active',
    'status.draft':       'draft',
    'status.completed':   'completed',
  },

  en: {
    'app.subtitle':        'Systematic Review',

    'sidebar.active_project': 'Active Project',
    'sidebar.select_project': 'Select a project',

    'nav.sec_overview':  'Overview',
    'nav.sec_prisma':    'PRISMA Process',
    'nav.sec_analysis':  'Analysis',
    'nav.sec_data':      'Data',

    'nav.dashboard':     'Dashboard',
    'nav.projects':      'Projects',
    'nav.import':        'Import',
    'nav.duplicates':    'Duplicates',
    'nav.screening':     'Screening',
    'nav.eligibility':   'Eligibility',
    'nav.included':      'Included',
    'nav.bibliometrics': 'Bibliometrics',
    'nav.prisma_flow':   'PRISMA Diagram',
    'nav.checklist':     'PRISMA Checklist',
    'nav.articles':      'All Articles',
    'nav.export':        'Export',

    'page.dashboard':     'Dashboard',
    'page.projects':      'Projects',
    'page.import':        'Import',
    'page.articles':      'Articles',
    'page.duplicates':    'Duplicates',
    'page.screening':     'Screening',
    'page.eligibility':   'Eligibility',
    'page.included':      'Included',
    'page.prisma-flow':   'PRISMA Diagram',
    'page.bibliometrics': 'Bibliometrics',
    'page.export':        'Export',
    'page.checklist':     'PRISMA Checklist',

    'status.active':      'active',
    'status.draft':       'draft',
    'status.completed':   'completed',
  },

  es: {
    'app.subtitle':        'Revisión Sistemática',

    'sidebar.active_project': 'Proyecto Activo',
    'sidebar.select_project': 'Seleccione un proyecto',

    'nav.sec_overview':  'Visión General',
    'nav.sec_prisma':    'Proceso PRISMA',
    'nav.sec_analysis':  'Análisis',
    'nav.sec_data':      'Datos',

    'nav.dashboard':     'Panel',
    'nav.projects':      'Proyectos',
    'nav.import':        'Importación',
    'nav.duplicates':    'Duplicados',
    'nav.screening':     'Cribado',
    'nav.eligibility':   'Elegibilidad',
    'nav.included':      'Incluidos',
    'nav.bibliometrics': 'Bibliometría',
    'nav.prisma_flow':   'Diagrama PRISMA',
    'nav.checklist':     'Lista de verificación',
    'nav.articles':      'Todos los artículos',
    'nav.export':        'Exportación',

    'page.dashboard':     'Panel',
    'page.projects':      'Proyectos',
    'page.import':        'Importación',
    'page.articles':      'Artículos',
    'page.duplicates':    'Duplicados',
    'page.screening':     'Cribado',
    'page.eligibility':   'Elegibilidad',
    'page.included':      'Incluidos',
    'page.prisma-flow':   'Diagrama PRISMA',
    'page.bibliometrics': 'Bibliometría',
    'page.export':        'Exportación',
    'page.checklist':     'Lista de verificación',

    'status.active':      'activo',
    'status.draft':       'borrador',
    'status.completed':   'completado',
  },
};

/**
 * Applies translations to every element with [data-i18n] on the page.
 * For nav items, preserves the <i> icon before the text.
 */
function applyI18n(lang) {
  var dict = window.I18N[lang] || window.I18N['pt'];

  document.querySelectorAll('[data-i18n]').forEach(function(el) {
    var key = el.getAttribute('data-i18n');
    var val = dict[key];
    if (!val) return;

    // If the element has a child icon, preserve it and swap only the text
    var icon = el.querySelector('i.fa, i.fas, i.far, i.fab');
    if (icon) {
      // Keep the icon's HTML, rewrite the content
      var iconHtml = icon.outerHTML;
      el.innerHTML = iconHtml + ' ' + val;
    } else {
      el.textContent = val;
    }
  });
}
