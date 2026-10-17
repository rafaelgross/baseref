/**
 * PRISMA-SLR — prisma-flow.js
 * Utilitários para o diagrama PRISMA 2020 (complementa prisma-flow.php)
 */
'use strict';

/**
 * Imprime o diagrama PRISMA em uma nova aba
 */
function printPrismaFlow() {
  const svgEl = document.getElementById('prisma-svg');
  if (!svgEl) { showToast('Gere o diagrama primeiro', 'warning'); return; }

  const svgData = new XMLSerializer().serializeToString(svgEl);
  const win = window.open('', '_blank');
  win.document.write(`<!DOCTYPE html><html><head><title>Diagrama PRISMA 2020</title>
    <style>body{margin:0;background:#070b14;display:flex;justify-content:center;padding:40px}
    svg{max-width:900px;width:100%}</style></head>
    <body>${svgData}</body></html>`);
  win.document.close();
  setTimeout(() => win.print(), 300);
}

/**
 * Copia os números do diagrama PRISMA 2020 (modelo para bases de dados) como texto
 */
function copyFlowNumbers(data) {
  if (!data) { showToast('Carregue o diagrama primeiro', 'warning'); return; }
  const id  = data.identification;
  const sc  = data.screening;
  const inc = data.included;
  const reasons = list => (list || []).map(r => `    - ${r.reason || 'Sem motivo informado'}: ${r.total}\n`).join('');

  let text = `DIAGRAMA PRISMA 2020\n`;
  text += `${'═'.repeat(40)}\n`;
  text += `IDENTIFICAÇÃO\n`;
  text += `  Registros identificados: ${id.total_identified}\n`;
  (id.by_database || []).forEach(b => { text += `    - ${b.name || 'Outras fontes'}: ${b.count}\n`; });
  text += `  Registros duplicados removidos: ${id.duplicates_removed}\n\n`;

  text += `TRIAGEM\n`;
  text += `  Registros triados: ${sc.records_screened}\n`;
  text += `  Registros excluídos: ${sc.records_excluded}\n`;
  text += `  Relatos buscados para recuperação: ${sc.reports_sought}\n`;
  text += `  Relatos não recuperados: ${sc.reports_not_retrieved}\n`;
  text += `  Relatos avaliados para elegibilidade: ${sc.reports_assessed}\n`;
  text += `  Relatos excluídos: ${sc.reports_excluded}\n` + reasons(sc.reports_reasons) + '\n';

  text += `INCLUÍDOS\n`;
  text += `  Estudos incluídos na revisão: ${inc.studies_included}\n`;

  copyToClipboard(text);
}
