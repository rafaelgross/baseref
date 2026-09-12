/**
 * PRISMA-SLR — screening.js
 * Keyboard shortcuts for screening/eligibility (arrows + I/E/U keys)
 */
'use strict';

(function initScreeningKeyboard() {
  // Only active on the screening/eligibility pages
  const page = new URLSearchParams(window.location.search).get('page');
  if (page !== 'screening' && page !== 'eligibility') return;

  document.addEventListener('keydown', (e) => {
    // Ignore when focus is on inputs/textareas
    const tag = document.activeElement?.tagName;
    if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') return;

    // Shortcuts for card view: navigate between articles
    if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
      e.preventDefault();
      const btns = document.querySelectorAll('[onclick^="decide("]');
      // Scroll to first pending card
      const pending = document.querySelector('.article-card:not([class*="decided"])');
      if (pending) pending.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  });
})();

/**
 * Exports the list of screened articles to CSV
 */
async function exportScreeningCSV(phase) {
  const projectId = typeof SCR_PROJECT_ID !== 'undefined' ? SCR_PROJECT_ID : null;
  if (!projectId) return;
  window.location.href = `api/export.php?project_id=${projectId}&format=csv&scope=${phase === 'eligibility' ? 'screened' : 'all'}`;
}
