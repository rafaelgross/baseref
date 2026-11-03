/**
 * BaseRef — tx.js
 * Interface labels translated by BaseRef itself (PRISMA 2020 dashboard counters and short labels
 * that machine translation gets wrong, e.g. "Atualizar" -> "To update").
 * [data-tx] elements get translate="no" and are filled from this dictionary.
 * Language: window.PRISMA_LANG ('pt' | 'en' | 'es').
 */
'use strict';

const TX = {
  pt: {
    logout:          'Sair',
    dash_screened:   'Triados',
    dash_sought:        'Relatos buscados para recuperação',
    dash_not_retrieved: 'Relatos não recuperados',
    dup_title:       'Gerenciamento de Duplicatas',
    dup_detect:      'Detectar Duplicatas',
    dup_refresh:     'Atualizar',
    dup_run:         'Executar',
    dup_detecting:   'Detectando...',
  },
  en: {
    logout:          'Log out',
    dash_screened:   'Screened',
    dash_sought:        'Reports sought for retrieval',
    dash_not_retrieved: 'Reports not retrieved',
    dup_title:       'Duplicate Management',
    dup_detect:      'Detect Duplicates',
    dup_refresh:     'Refresh',
    dup_run:         'Run',
    dup_detecting:   'Detecting...',
  },
  es: {
    logout:          'Cerrar sesión',
    dash_screened:   'Cribados',
    dash_sought:        'Publicaciones buscadas para su recuperación',
    dash_not_retrieved: 'Publicaciones no recuperadas',
    dup_title:       'Gestión de Duplicados',
    dup_detect:      'Detectar Duplicados',
    dup_refresh:     'Actualizar',
    dup_run:         'Ejecutar',
    dup_detecting:   'Detectando...',
  },
};

function tx(key) {
  const l = String(window.PRISMA_LANG || 'pt').toLowerCase().slice(0, 2);
  return (TX[l] && TX[l][key]) || TX.pt[key] || key;
}

// Marks the text as "do not translate" so the optional gtranslate widget does not overwrite the dictionary.
function txSpan(key) {
  return `<span data-tx="${key}" translate="no" class="notranslate">${tx(key)}</span>`;
}

function applyTx(root) {
  (root || document).querySelectorAll('[data-tx]').forEach(el => {
    el.setAttribute('translate', 'no');
    el.classList.add('notranslate');
    el.textContent = tx(el.getAttribute('data-tx'));
  });
}

document.addEventListener('prismaLangChanged', () => applyTx());
document.addEventListener('DOMContentLoaded', () => applyTx());
