/**
 * PRISMA-SLR — bibliometrics.js
 * Funções auxiliares + visualizações bibliométricas clássicas (Lotka, Bradford,
 * Word Cloud, Trend Topics, Three-Field Plot, Citações por Ano).
 *
 * Convenções de qualidade acadêmica:
 *  - Eixos com título e unidade
 *  - N (tamanho amostral) e parâmetros estatísticos visíveis
 *  - Cores acessíveis e contrastantes
 *  - Exportação PNG em alta resolução com fundo claro (publicação)
 */
'use strict';

// ─── Defaults Chart.js ────────────────────────────────────────────────────────
if (typeof Chart !== 'undefined') {
  Chart.defaults.color = '#94a3b8';
  Chart.defaults.font.family = 'Inter, sans-serif';
  Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(15,23,41,0.95)';
  Chart.defaults.plugins.tooltip.borderColor = 'rgba(0,212,255,0.3)';
  Chart.defaults.plugins.tooltip.borderWidth = 1;
  Chart.defaults.plugins.tooltip.padding = 10;
  Chart.defaults.plugins.tooltip.titleColor = '#00D4FF';
  Chart.defaults.plugins.tooltip.bodyColor = '#e2e8f0';
  Chart.defaults.plugins.legend.labels.boxWidth = 12;
  Chart.defaults.plugins.legend.labels.padding = 16;
}

// ─── Helpers de paleta e eixo ────────────────────────────────────────────────
function generatePalette(n) {
  const base = [
    [0, 212, 255], [0, 229, 160], [116, 192, 252], [229, 153, 247],
    [255, 212, 59], [255, 135, 135], [255, 180, 80], [80, 200, 120],
    [200, 120, 200], [120, 160, 255], [255, 100, 80], [160, 220, 60],
  ];
  return Array.from({ length: n }, (_, i) => {
    const c = base[i % base.length];
    return `rgba(${c[0]},${c[1]},${c[2]},0.82)`;
  });
}

/**
 * Cor de texto que acompanha o tema. Títulos e eixos estavam fixos em cinza
 * claro (#cbd5e1/#94a3b8): no tema claro — e na exportação PNG, que força fundo
 * branco — o texto ficava quase ilegível na figura.
 */
function bibTextColor(strong) {
  const isLight = document.body.classList.contains('theme-light');
  if (isLight) return strong ? '#1e293b' : '#475569';
  return strong ? '#cbd5e1' : '#94a3b8';
}

function darkAxisConfig(opts = {}) {
  const isLight = document.body.classList.contains('theme-light');
  return {
    grid:   { color: isLight ? 'rgba(0,0,0,0.06)' : 'rgba(255,255,255,0.05)', drawBorder: false },
    border: { display: false },
    ticks:  { color: isLight ? '#475569' : '#94a3b8', font: { size: 11 } },
    ...opts,
  };
}

// ─── Exportação PNG (modo publicação: fundo claro, alta densidade de pixel) ──
// Se o canvas pertence a um gráfico Chart.js, redesenha nativamente numa
// resolução maior (em vez de esticar o raster já renderizado, que só borra a
// imagem) e depois devolve o gráfico à densidade normal da tela.
function exportCanvasPng(canvasId, filename) {
  const canvas = document.getElementById(canvasId);
  if (!canvas) { typeof showToast === 'function' && showToast('Canvas não encontrado', 'error'); return; }

  const capture = () => {
    const tmp = document.createElement('canvas');
    tmp.width  = canvas.width;
    tmp.height = canvas.height;
    const ctx  = tmp.getContext('2d');
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, tmp.width, tmp.height);
    ctx.drawImage(canvas, 0, 0);
    const a = document.createElement('a');
    a.href     = tmp.toDataURL('image/png');
    a.download = filename || 'chart.png';
    a.click();
    typeof showToast === 'function' && showToast('Gráfico exportado', 'success');
  };

  const chart = (typeof Chart !== 'undefined' && Chart.getChart) ? Chart.getChart(canvas) : null;
  if (chart) {
    const originalDpr = chart.options.devicePixelRatio;
    chart.options.devicePixelRatio = (window.devicePixelRatio || 1) * 3;
    chart.resize();
    capture();
    chart.options.devicePixelRatio = originalDpr;
    chart.resize();
  } else {
    capture();
  }
}

function exportContainerCanvasPng(containerId, filename) {
  const container = document.getElementById(containerId);
  const canvas = container && container.querySelector('canvas');
  if (!canvas) { typeof showToast === 'function' && showToast('Canvas não encontrado', 'error'); return; }

  if (!canvas.id) {
    canvas.id = `${containerId}-export-canvas`;
  }
  exportCanvasPng(canvas.id, filename);
}

function exportElementPng(elementId, filename) {
  const element = document.getElementById(elementId);
  if (!element) { typeof showToast === 'function' && showToast('Elemento não encontrado', 'error'); return; }

  const width = Math.ceil(element.scrollWidth || element.clientWidth || 800);
  const height = Math.ceil(element.scrollHeight || element.clientHeight || 600);
  if (!width || !height) { typeof showToast === 'function' && showToast('Elemento sem dimensões para exportar', 'error'); return; }

  const clone = element.cloneNode(true);
  clone.setAttribute('xmlns', 'http://www.w3.org/1999/xhtml');
  clone.style.width = `${width}px`;
  clone.style.minHeight = `${height}px`;
  clone.style.boxSizing = 'border-box';
  clone.style.margin = '0';
  clone.style.background = getComputedStyle(element).backgroundColor || '#0f172a';

  const svgMarkup = `
    <svg xmlns="http://www.w3.org/2000/svg" width="${width}" height="${height}" viewBox="0 0 ${width} ${height}">
      <foreignObject width="100%" height="100%">
        ${new XMLSerializer().serializeToString(clone)}
      </foreignObject>
    </svg>`;

  const blob = new Blob([svgMarkup], { type: 'image/svg+xml;charset=utf-8' });
  const url = URL.createObjectURL(blob);
  const img = new Image();
  img.onload = function() {
    const scale = 2;
    const canvas = document.createElement('canvas');
    canvas.width = width * scale;
    canvas.height = height * scale;
    const ctx = canvas.getContext('2d');
    ctx.scale(scale, scale);
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, width, height);
    ctx.drawImage(img, 0, 0, width, height);
    const a = document.createElement('a');
    a.href = canvas.toDataURL('image/png');
    a.download = filename || 'bloco.png';
    a.click();
    URL.revokeObjectURL(url);
    typeof showToast === 'function' && showToast('Imagem exportada', 'success');
  };
  img.onerror = function() {
    URL.revokeObjectURL(url);
    typeof showToast === 'function' && showToast('Não foi possível exportar este bloco', 'error');
  };
  img.src = url;
}

// =============================================================================
// ───── PRODUTIVIDADE DOS AUTORES (distribuição descritiva, ligada à Lei de Lotka)
// Barras de observado apenas: sem curva de modelo e sem teste de aderência.
// =============================================================================
function buildLotkaChart(canvasId, payload) {
  const canvas = document.getElementById(canvasId);
  if (!canvas || !payload || !payload.distribution) return;
  bibReplaySave(buildLotkaChart, arguments);
  if (window._lotkaChart) { window._lotkaChart.destroy(); }

  const dist     = payload.distribution;
  const labels   = dist.map(d => d.n_articles + bt(d.n_articles == 1 ? 'unit_article' : 'unit_articles'));
  const observed = dist.map(d => +d.n_authors);
  const totalA   = payload.total_authors || 0;
  const totalArt = payload.total_articles || 0;

  // Identifica a amostra: os recortes (todos sem duplicatas × só incluídos) dão resultados diferentes
  const sample = bt(payload.scope === 'included' ? 'lotka_sample_included' : 'lotka_sample_all',
                    { a: totalArt, n: totalA })
               + (payload.source_filter ? ' ' + bt('lotka_sample_db') : '');

  const isLight = document.body.classList.contains('theme-light');
  const tc = isLight ? '#475569' : '#94a3b8';

  window._lotkaChart = new Chart(canvas.getContext('2d'), {
    type: 'bar',
    data: {
      labels,
      datasets: [{
        label: bt('lotka_observed', { n: totalA }),
        data: observed,
        backgroundColor: 'rgba(0,212,255,0.72)',
        borderColor: '#00D4FF',
        borderWidth: 1, borderRadius: 4,
      }],
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        title: {
          display: true,
          text: [bt('lotka_title'), sample],
          color: tc, font: { size: 11, weight: '600' },
        },
      },
      scales: {
        x: { ticks: { color: tc }, grid: { color: isLight ? 'rgba(0,0,0,0.06)' : 'rgba(255,255,255,0.05)' },
             title: { display: true, text: bt('lotka_x'), color: tc } },
        y: { beginAtZero: true, ticks: { color: tc },
             grid: { color: isLight ? 'rgba(0,0,0,0.06)' : 'rgba(255,255,255,0.05)' },
             title: { display: true, text: bt('lotka_y'), color: tc } },
      },
    },
  });
}

// =============================================================================
// ───── LEI DE BRADFORD (zonas de periódicos) ─────────────────────────────────
// Barras coloridas por zona (núcleo / intermediária / periferia) ordenadas por
// produtividade. Mostra os 30 primeiros periódicos para não sobrecarregar.
// =============================================================================
function buildBradfordChart(canvasId, payload) {
  const canvas = document.getElementById(canvasId);
  if (!canvas || !payload || !payload.series) return;
  bibReplaySave(buildBradfordChart, arguments);
  if (window._bradfordChart) { window._bradfordChart.destroy(); }

  const z      = payload.zones;
  // Limita aos 30 mais produtivos para legibilidade
  const series = payload.series.slice(0, 30);

  const zoneColor = (rank) => {
    if (rank <= z.core.count)                          return 'rgba(0,212,255,0.82)';
    if (rank <= z.core.count + z.related.count)        return 'rgba(0,229,160,0.72)';
    return 'rgba(148,163,184,0.50)';
  };

  const isLight = document.body.classList.contains('theme-light');
  const tc = isLight ? '#475569' : '#94a3b8';

  // Label curto (máx 25 chars)
  const shorten = (s) => s.length > 25 ? s.slice(0, 24) + '…' : s;

  window._bradfordChart = new Chart(canvas.getContext('2d'), {
    type: 'bar',
    data: {
      labels: series.map(s => shorten(s.journal)),
      datasets: [{
        label: bt('articles'),
        data:  series.map(s => s.n),
        backgroundColor: series.map(s => zoneColor(s.rank)),
        borderColor:     series.map(s => zoneColor(s.rank).replace(/[\d.]+\)$/, '1)')),
        borderWidth: 0, borderRadius: 3,
      }],
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      indexAxis: 'y',
      plugins: {
        legend: { display: false },
        tooltip: { callbacks: {
          label: (c) => {
            const s = series[c.dataIndex];
            const zone = s.rank <= z.core.count ? bt('zone_core')
                       : s.rank <= z.core.count + z.related.count ? bt('zone_related')
                       : bt('zone_periphery');
            return bt('bradford_tooltip', { value: s.n, zone: zone });
          },
        }},
        title: {
          display: true,
          text: bt('bradford_title', { core: z.core.count, total: payload.total_journals }),
          color: tc, font: { size: 11, weight: '600' },
        },
      },
      scales: {
        x: { beginAtZero: true, ticks: { color: tc },
             grid: { color: isLight ? 'rgba(0,0,0,0.06)' : 'rgba(255,255,255,0.05)' },
             title: { display: true, text: bt('bradford_x'), color: tc } },
        y: { ticks: { color: tc, font: { size: 10 } },
             grid: { color: isLight ? 'rgba(0,0,0,0.06)' : 'rgba(255,255,255,0.05)' } },
      },
    },
  });
}

// =============================================================================
// ───── PRODUÇÃO ANUAL + CAGR ─────────────────────────────────────────────────
// =============================================================================
function buildAnnualGrowthChart(canvasId, payload) {
  const canvas = document.getElementById(canvasId);
  if (!canvas || !payload || !payload.series) return;
  bibReplaySave(buildAnnualGrowthChart, arguments);
  if (window._annualGrowthChart) { window._annualGrowthChart.destroy(); }

  const labels = payload.series.map(r => r.year);
  const annual = payload.series.map(r => +r.total);
  const cum    = payload.series.map(r => +r.cumulative);
  const cagr   = payload.cagr_percent;

  window._annualGrowthChart = new Chart(canvas.getContext('2d'), {
    type: 'bar',
    data: {
      labels,
      datasets: [
        {
          label: bt('annual_in_year'),
          data: annual,
          backgroundColor: 'rgba(0,212,255,0.72)',
          borderColor: '#00D4FF', borderWidth: 1, borderRadius: 5,
          yAxisID: 'y', order: 2,
        },
        {
          label: bt('annual_cumulative'),
          data: cum, type: 'line',
          borderColor: 'rgba(245,158,11,0.95)',
          backgroundColor: 'rgba(245,158,11,0.12)',
          pointBackgroundColor: '#f59e0b', pointRadius: 3,
          borderWidth: 2.5, tension: 0.3, fill: true,
          yAxisID: 'y1', order: 1,
        },
      ],
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: {
        legend: { position: 'top' },
        title: {
          display: true,
          text: cagr !== null
            ? bt('annual_title_cagr', { n: payload.total_articles, cagr: cagr })
            : bt('annual_title',      { n: payload.total_articles }),
          color: bibTextColor(true), font: { size: 12, weight: '600' }, padding: { bottom: 10 },
        },
      },
      scales: {
        y:  { position: 'left',  beginAtZero: true,
              title: { display: true, text: bt('annual_in_year'), color: bibTextColor() }, ...darkAxisConfig() },
        y1: { position: 'right', beginAtZero: true, grid: { drawOnChartArea: false },
              title: { display: true, text: bt('annual_cumulative'), color: bibTextColor() }, ...darkAxisConfig() },
        x:  darkAxisConfig(),
      },
    },
  });
}

// =============================================================================
// ───── WORD CLOUD (wordcloud2.js) ────────────────────────────────────────────
// =============================================================================
function buildWordCloud(containerId, rows) {
  const el = document.getElementById(containerId);
  if (!el) return;
  if (typeof WordCloud === 'undefined') {
    el.innerHTML = `<div class="alert alert-error">${bt('wordcloud_missing')}</div>`;
    return;
  }
  if (!rows || !rows.length) {
    el.innerHTML = `<div class="empty-state" style="padding:24px"><i class="fa fa-cloud"></i><p>${bt('wordcloud_empty')}</p></div>`;
    return;
  }
  bibReplaySave(buildWordCloud, arguments);

  // Garante que o layout foi calculado antes de pegar a largura
  requestAnimationFrame(() => {
  el.innerHTML = '';
  const canvas = document.createElement('canvas');
  const cssW = el.offsetWidth || el.clientWidth || 700;
  const cssH = 360;
  // Canvas nativo em alta densidade de pixel (mín. 2x), mostrado no tamanho
  // original via CSS — evita nuvem de palavras borrada ao exportar/baixar.
  const dpr = Math.max(window.devicePixelRatio || 1, 2);
  canvas.width  = cssW * dpr;
  canvas.height = cssH * dpr;
  canvas.style.width  = cssW + 'px';
  canvas.style.height = cssH + 'px';
  el.appendChild(canvas);

  const list = rows.map(r => [r.keyword, +r.total]);
  const max  = Math.max(...list.map(x => x[1]));
  const isLight = document.body.classList.contains('theme-light');
  const palette = ['#00D4FF','#00e5a0','#74C0FC','#E599F7','#FFD43B','#FFB347','#A0DC60','#80C8FF','#FF8787'];

  WordCloud(canvas, {
    list,
    gridSize: Math.round(8 * dpr),
    weightFactor: (size) => (10 + (size / max) * 48) * dpr,
    fontFamily: 'Inter, sans-serif',
    color: () => palette[Math.floor(Math.random() * palette.length)],
    backgroundColor: isLight ? '#ffffff' : 'transparent',
    rotateRatio: 0.25,
    rotationSteps: 2,
    drawOutOfBound: false,
    shrinkToFit: true,
  });
  }); // fecha requestAnimationFrame
}

// =============================================================================
// ───── TREND TOPICS (timeline de keywords) ───────────────────────────────────
// =============================================================================
function buildTrendTopicsChart(canvasId, rows) {
  const canvas = document.getElementById(canvasId);
  if (!canvas || !rows || !rows.length) return;
  bibReplaySave(buildTrendTopicsChart, arguments);
  if (window._trendTopicsChart) { window._trendTopicsChart.destroy(); }

  // Cada keyword vira um ponto: x = mediana do ano, y = índice (ordem), tamanho ∝ freq
  // Linha horizontal de q1 → q3 mostra a dispersão temporal.
  const data = rows.map((r, i) => ({
    x:    +r.year_median,
    y:    i + 1,
    q1:   +r.year_q1,
    q3:   +r.year_q3,
    label: r.keyword,
    freq: +r.freq,
  }));

  const labels = rows.map(r => r.keyword);
  const maxFreq = Math.max(...data.map(d => d.freq));

  // Plugin para desenhar as linhas q1-q3 (range temporal)
  const rangePlugin = {
    id: 'trendRange',
    afterDatasetsDraw(chart) {
      const ctx   = chart.ctx;
      const xAxis = chart.scales.x;
      const yAxis = chart.scales.y;
      ctx.save();
      ctx.strokeStyle = 'rgba(0,212,255,0.35)';
      ctx.lineWidth   = 2;
      data.forEach(d => {
        const x1 = xAxis.getPixelForValue(d.q1);
        const x2 = xAxis.getPixelForValue(d.q3);
        const y  = yAxis.getPixelForValue(d.y);
        ctx.beginPath();
        ctx.moveTo(x1, y); ctx.lineTo(x2, y); ctx.stroke();
      });
      ctx.restore();
    },
  };

  window._trendTopicsChart = new Chart(canvas.getContext('2d'), {
    type: 'bubble',
    data: {
      datasets: [{
        label: 'Keyword',
        data: data.map(d => ({ x: d.x, y: d.y, r: 6 + (d.freq / maxFreq) * 18, label: d.label, freq: d.freq, q1: d.q1, q3: d.q3 })),
        backgroundColor: 'rgba(0,212,255,0.65)',
        borderColor:    '#00D4FF',
        borderWidth: 1.5,
      }],
    },
    plugins: [rangePlugin],
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: { callbacks: {
          label: (c) => {
            const p = c.raw;
            return bt('trend_tooltip', { kw: p.label, freq: p.freq, q1: p.q1, med: c.parsed.x, q3: p.q3 });
          },
        }},
        title: {
          display: true,
          text: bt('trend_title'),
          color: bibTextColor(true), font: { size: 12, weight: '600' }, padding: { bottom: 10 },
        },
      },
      scales: {
        x: { title: { display: true, text: bt('trend_x'), color: bibTextColor() }, ...darkAxisConfig() },
        y: {
          ticks: { color: bibTextColor(), font: { size: 11 }, callback: (v) => labels[v - 1] || '' },
          grid: darkAxisConfig().grid,
          min: 0.5, max: labels.length + 0.5,
          title: { display: false },
        },
      },
    },
  });
}

// =============================================================================
// ───── CITAÇÕES MÉDIAS POR ANO (TC/Y) ────────────────────────────────────────
// =============================================================================
function buildCitationsPerYearChart(canvasId, rows) {
  const canvas = document.getElementById(canvasId);
  if (!canvas || !rows) return;
  bibReplaySave(buildCitationsPerYearChart, arguments);
  if (window._tcyChart) { window._tcyChart.destroy(); }

  const labels = rows.map(r => r.year);
  const avg    = rows.map(r => +r.avg_citations);
  const tcy    = rows.map(r => +r.tc_per_year);

  window._tcyChart = new Chart(canvas.getContext('2d'), {
    type: 'line',
    data: {
      labels,
      datasets: [
        { label: bt('cit_avg'), data: avg,
          borderColor: '#00D4FF', backgroundColor: 'rgba(0,212,255,0.10)',
          tension: 0.3, fill: true, pointBackgroundColor: '#00D4FF', pointRadius: 3, borderWidth: 2 },
        { label: bt('cit_tcy'), data: tcy,
          borderColor: '#f59e0b', backgroundColor: 'rgba(245,158,11,0.08)',
          tension: 0.3, fill: false, pointBackgroundColor: '#f59e0b', pointRadius: 3, borderWidth: 2,
          borderDash: [5, 4] },
      ],
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: {
        legend: { position: 'top' },
        title: {
          display: true,
          text: bt('cit_title'),
          color: bibTextColor(true), font: { size: 12, weight: '600' }, padding: { bottom: 10 },
        },
      },
      scales: {
        x: { title: { display: true, text: bt('cit_x'), color: bibTextColor() }, ...darkAxisConfig() },
        y: { beginAtZero: true,
             title: { display: true, text: bt('citations'), color: bibTextColor() }, ...darkAxisConfig() },
      },
    },
  });
}

// =============================================================================
// ───── THREE-FIELD PLOT (Sankey SVG nativo) ──────────────────────────────────
// =============================================================================
// Renderiza um Sankey de 3 colunas (Autores | Keywords | Periódicos) em SVG puro.
// Layout: cada nó é um retângulo cuja altura é proporcional ao total de ligações.
function buildThreeFieldPlot(containerId, payload) {
  const el = document.getElementById(containerId);
  if (!el || !payload) return;
  el.innerHTML = '';

  const { authors, keywords, journals, links_ak, links_kj } = payload;
  if (!authors.length || !keywords.length || !journals.length) {
    el.innerHTML = `<div class="empty-state" style="padding:32px"><i class="fa fa-diagram-project"></i><p>${bt('three_field_empty')}</p></div>`;
    return;
  }
  bibReplaySave(buildThreeFieldPlot, arguments);

  // Calcula peso total por nó
  const weight = { A: {}, K: {}, J: {} };
  authors.forEach(a => weight.A[a] = 0);
  keywords.forEach(k => weight.K[k] = 0);
  journals.forEach(j => weight.J[j] = 0);
  links_ak.forEach(l => { weight.A[l.author]  = (weight.A[l.author]||0)  + (+l.weight); weight.K[l.keyword]   = (weight.K[l.keyword]||0) + (+l.weight); });
  links_kj.forEach(l => { weight.K[l.keyword] = (weight.K[l.keyword]||0) + (+l.weight); weight.J[l.journal] = (weight.J[l.journal]||0) + (+l.weight); });

  const W = el.offsetWidth || el.clientWidth || 800;
  const H = Math.max(420, Math.max(authors.length, keywords.length, journals.length) * 32);
  const colX = [60, W * 0.5, W - 60];
  const nodeW = 18, gap = 8;

  // Função pra computar Y de cada nó dentro da coluna
  function layoutColumn(items, weights, totalH) {
    const total = items.reduce((s, k) => s + (weights[k] || 1), 0) || 1;
    const usableH = totalH - (items.length - 1) * gap - 40;
    let y = 20;
    const out = {};
    items.forEach(k => {
      const h = Math.max(14, (weights[k] || 1) / total * usableH);
      out[k] = { y, h };
      y += h + gap;
    });
    return out;
  }
  const layA = layoutColumn(authors,  weight.A, H);
  const layK = layoutColumn(keywords, weight.K, H);
  const layJ = layoutColumn(journals, weight.J, H);

  const isLight = document.body.classList.contains('theme-light');
  const txtCol  = isLight ? '#1e293b' : '#e2e8f0';
  const palette = ['#00D4FF','#00e5a0','#E599F7','#FFD43B','#FF8787','#74C0FC','#A0DC60','#FFB347','#FF6464','#C878C8','#78A0FF','#FFCC00'];
  const colorA = Object.fromEntries(authors.map((a,i)  => [a, palette[i % palette.length]]));
  const colorK = Object.fromEntries(keywords.map((k,i) => [k, palette[(i+3) % palette.length]]));
  const colorJ = Object.fromEntries(journals.map((j,i) => [j, palette[(i+6) % palette.length]]));

  // SVG
  const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
  svg.setAttribute('width', W);
  svg.setAttribute('height', H);
  svg.setAttribute('viewBox', `0 0 ${W} ${H}`);
  svg.style.display = 'block';

  // Links AK
  function curvePath(x1, y1, x2, y2) {
    const mx = (x1 + x2) / 2;
    return `M ${x1} ${y1} C ${mx} ${y1}, ${mx} ${y2}, ${x2} ${y2}`;
  }

  // Para empilhar links dentro de cada nó
  const offA = {}, offKin = {}, offKout = {}, offJ = {};
  function nextOff(map, key) { return map[key] = (map[key] || 0); }

  // Renderiza links AK
  links_ak.forEach(l => {
    const a = layA[l.author], k = layK[l.keyword];
    if (!a || !k) return;
    const wPct = +l.weight;
    const yA = a.y + nextOff(offA, l.author);     offA[l.author]   += Math.max(2, wPct * (a.h / (weight.A[l.author]||1)));
    const yK = k.y + nextOff(offKin, l.keyword);  offKin[l.keyword] += Math.max(2, wPct * (k.h / (weight.K[l.keyword]||1)));
    const hA = Math.max(2, wPct * (a.h / (weight.A[l.author]||1)));
    const hK = Math.max(2, wPct * (k.h / (weight.K[l.keyword]||1)));
    const path = document.createElementNS('http://www.w3.org/2000/svg','path');
    path.setAttribute('d', curvePath(colX[0]+nodeW, yA + hA/2, colX[1], yK + hK/2));
    path.setAttribute('stroke', colorA[l.author] || '#00D4FF');
    path.setAttribute('stroke-opacity', '0.28');
    path.setAttribute('stroke-width', Math.max(1.5, hA));
    path.setAttribute('fill', 'none');
    path.appendChild(svgTitle(`${l.author} → ${l.keyword}: ${l.weight}`));
    svg.appendChild(path);
  });

  // Renderiza links KJ
  links_kj.forEach(l => {
    const k = layK[l.keyword], j = layJ[l.journal];
    if (!k || !j) return;
    const wPct = +l.weight;
    const yK = k.y + nextOff(offKout, l.keyword); offKout[l.keyword] += Math.max(2, wPct * (k.h / (weight.K[l.keyword]||1)));
    const yJ = j.y + nextOff(offJ, l.journal);   offJ[l.journal]    += Math.max(2, wPct * (j.h / (weight.J[l.journal]||1)));
    const hK = Math.max(2, wPct * (k.h / (weight.K[l.keyword]||1)));
    const hJ = Math.max(2, wPct * (j.h / (weight.J[l.journal]||1)));
    const path = document.createElementNS('http://www.w3.org/2000/svg','path');
    path.setAttribute('d', curvePath(colX[1]+nodeW, yK + hK/2, colX[2], yJ + hJ/2));
    path.setAttribute('stroke', colorK[l.keyword] || '#00e5a0');
    path.setAttribute('stroke-opacity', '0.28');
    path.setAttribute('stroke-width', Math.max(1.5, hK));
    path.setAttribute('fill', 'none');
    path.appendChild(svgTitle(`${l.keyword} → ${l.journal}: ${l.weight}`));
    svg.appendChild(path);
  });

  // Desenha caixas e rótulos
  function drawColumn(items, lay, x, colorMap, labelSide) {
    items.forEach(name => {
      const n = lay[name]; if (!n) return;
      const rect = document.createElementNS('http://www.w3.org/2000/svg','rect');
      rect.setAttribute('x', x); rect.setAttribute('y', n.y);
      rect.setAttribute('width', nodeW); rect.setAttribute('height', n.h);
      rect.setAttribute('fill', colorMap[name] || '#00D4FF');
      rect.setAttribute('rx', '3');
      rect.appendChild(svgTitle(name));
      svg.appendChild(rect);

      const txt = document.createElementNS('http://www.w3.org/2000/svg','text');
      const isLeft = labelSide === 'left';
      txt.setAttribute('x', isLeft ? x - 6 : x + nodeW + 6);
      txt.setAttribute('y', n.y + n.h / 2 + 4);
      txt.setAttribute('text-anchor', isLeft ? 'end' : 'start');
      txt.setAttribute('fill', txtCol);
      txt.setAttribute('font-size', '11');
      txt.setAttribute('font-family', 'Inter, sans-serif');
      const display = name.length > 30 ? name.slice(0, 30) + '…' : name;
      txt.textContent = display;
      txt.appendChild(svgTitle(name));
      svg.appendChild(txt);
    });
  }
  drawColumn(authors,  layA, colX[0], colorA, 'left');
  drawColumn(keywords, layK, colX[1], colorK, 'right');
  drawColumn(journals, layJ, colX[2], colorJ, 'right');

  // Cabeçalhos
  [bt('authors_col'), bt('keywords_col'), bt('journals_col')].forEach((t, i) => {
    const h = document.createElementNS('http://www.w3.org/2000/svg','text');
    h.setAttribute('x', colX[i] + nodeW / 2);
    h.setAttribute('y', 14);
    h.setAttribute('text-anchor', 'middle');
    h.setAttribute('fill', txtCol);
    h.setAttribute('font-size', '12');
    h.setAttribute('font-weight', '700');
    h.textContent = t;
    svg.appendChild(h);
  });

  el.appendChild(svg);

  function svgTitle(txt) {
    const t = document.createElementNS('http://www.w3.org/2000/svg','title');
    t.textContent = txt;
    return t;
  }
}

// Exporta um SVG para PNG
function exportSvgPng(containerId, filename) {
  const el  = document.getElementById(containerId);
  const svg = el && el.querySelector('svg');
  if (!svg) { typeof showToast === 'function' && showToast('SVG não encontrado', 'error'); return; }
  const xml = new XMLSerializer().serializeToString(svg);
  const img = new Image();
  const blob = new Blob([xml], { type: 'image/svg+xml;charset=utf-8' });
  const url  = URL.createObjectURL(blob);
  img.onload = function() {
    const canvas = document.createElement('canvas');
    const scale = 2;
    canvas.width  = svg.clientWidth * scale;
    canvas.height = svg.clientHeight * scale;
    const ctx = canvas.getContext('2d');
    ctx.fillStyle = '#ffffff'; ctx.fillRect(0, 0, canvas.width, canvas.height);
    ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
    const a = document.createElement('a');
    a.href = canvas.toDataURL('image/png'); a.download = filename || 'chart.png'; a.click();
    URL.revokeObjectURL(url);
  };
  img.src = url;
}

// ─── Mapa coroplético — Country Scientific Production ──────────────────────
// Estilo bibliometrix: escala sequencial de azuis, legenda em 5 classes,
// países sem dados em cinza, tooltip com nome + N documentos.
function buildCountryMap(containerId, rows) {
  const el = document.getElementById(containerId);
  if (!el) return;

  // Feedback visível enquanto carrega
  el.innerHTML = `<p style="color:var(--text-muted);padding:24px;text-align:center"><i class="fa fa-spinner fa-spin"></i> ${bt('map_loading')}</p>`;
  bibReplaySave(buildCountryMap, arguments);

  if (typeof d3 === 'undefined' || typeof topojson === 'undefined') {
    el.innerHTML = `<p style="color:#f87171;padding:16px">${bt('map_libs_missing')}</p>`;
    return;
  }
  el.innerHTML = '';

  // Normalização de nomes para ISO 3166 alpha-2 → numeric (TopoJSON usa numeric)
  const nameToNumeric = {
    'Afghanistan':57,'Albania':8,'Algeria':12,'Angola':24,'Argentina':32,
    'Armenia':51,'Australia':36,'Austria':40,'Azerbaijan':31,
    'Bangladesh':50,'Belarus':112,'Belgium':56,'Bolivia':68,'Bosnia and Herzegovina':70,
    'Brazil':76,'Bulgaria':100,'Cambodia':116,'Cameroon':120,'Canada':124,
    'Chile':152,'China':156,'Colombia':170,'Congo':178,'Costa Rica':188,
    'Croatia':191,'Cuba':192,'Cyprus':196,'Czech Republic':203,'Czechia':203,
    'Denmark':208,'Ecuador':218,'Egypt':818,'El Salvador':222,'Estonia':233,
    'Ethiopia':231,'Finland':246,'France':250,'Georgia':268,'Germany':276,
    'Ghana':288,'Greece':300,'Guatemala':320,'Honduras':340,'Hungary':348,
    'India':356,'Indonesia':360,'Iran':364,'Iraq':368,'Ireland':372,
    'Israel':376,'Italy':380,'Jamaica':388,'Japan':392,'Jordan':400,
    'Kazakhstan':398,'Kenya':404,'Kuwait':414,'Latvia':428,'Lebanon':422,
    'Libya':434,'Lithuania':440,'Luxembourg':442,'Malaysia':458,'Mexico':484,
    'Moldova':498,'Morocco':504,'Mozambique':508,'Nepal':524,'Netherlands':528,
    'New Zealand':554,'Nigeria':566,'Norway':578,'Oman':512,'Pakistan':586,
    'Palestine':275,'Panama':591,'Paraguay':600,'Peru':604,'Philippines':608,
    'Poland':616,'Portugal':620,'Qatar':634,'Romania':642,'Russia':643,
    'Russian Federation':643,'Saudi Arabia':682,'Senegal':'686','Serbia':688,
    'Singapore':702,'Slovakia':703,'Slovenia':705,'South Africa':710,
    'South Korea':410,'Korea':410,'Republic of Korea':410,'Spain':724,
    'Sri Lanka':144,'Sudan':736,'Sweden':752,'Switzerland':756,'Syria':760,
    'Taiwan':158,'Thailand':764,'Tunisia':788,'Turkey':792,'Türkiye':792,
    'Uganda':800,'Ukraine':804,'United Arab Emirates':784,'UAE':784,
    'United Kingdom':826,'UK':826,'England':826,'Scotland':826,'Wales':826,
    'United States':840,'USA':840,
    'United States of America':840,'Uruguay':858,'Uzbekistan':860,
    'Venezuela':862,'Vietnam':704,'Viet Nam':704,'Yemen':887,'Zambia':894,
    'Zimbabwe':716,'Chad':148,'Ivory Coast':384,"Côte d'Ivoire":384,
    'Bolivia (Plurinational State of)':68,'Hong Kong':344,'Macao':446,
    'North Macedonia':807,'Kosovo':383,'Montenegro':499,'Bahrain':48,
    'Malta':470,'Iceland':352,'Cyprus':196,'Serbia':688,'Albania':8,
  };

  const width  = el.offsetWidth  || 700;
  const height = Math.round(width * 0.48);
  el.style.height = height + 'px';

  // Lookup: numeric → n
  const byNumeric = {};
  let maxN = 0;
  rows.forEach(r => {
    const num = nameToNumeric[r.country];
    if (num != null) {
      byNumeric[num] = (byNumeric[num] || 0) + (+r.n);
      if (byNumeric[num] > maxN) maxN = byNumeric[num];
    }
  });

  // Escala de azuis igual ao bibliometrix
  const color = d3.scaleSequentialLog(d3.interpolateBlues)
    .domain([1, Math.max(maxN, 2)]);

  const svg = d3.select(el)
    .append('svg')
    .attr('width', width)
    .attr('height', height)
    .attr('xmlns', 'http://www.w3.org/2000/svg');

  // projection será configurada com fitSize após carregar os dados
  const projection = d3.geoNaturalEarth1();
  const path = d3.geoPath().projection(projection);

  // Tooltip
  const tip = d3.select(el)
    .append('div')
    .style('position','absolute')
    .style('pointer-events','none')
    .style('background','rgba(0,0,0,.75)')
    .style('color','#fff')
    .style('padding','6px 10px')
    .style('border-radius','6px')
    .style('font-size','12px')
    .style('display','none');

  // Carrega TopoJSON local (assets/data/countries-110m.json)
  const topoUrl = (typeof APP !== 'undefined' ? APP.baseUrl + '/' : '') + 'assets/data/countries-110m.json';
  d3.json(topoUrl)
    .then(world => {
      const countries = topojson.feature(world, world.objects.countries);

      // fitSize ajusta escala e translate automaticamente ao tamanho do SVG
      projection.fitSize([width, height], countries);

      svg.append('g')
        .selectAll('path')
        .data(countries.features)
        .enter().append('path')
          .attr('d', path)
          .attr('fill', d => {
            const n = byNumeric[+d.id];
            return n ? color(n) : '#6b7280';
          })
          .attr('stroke', '#fff')
          .attr('stroke-width', 0.3)
          .on('mouseover', function(event, d) {
            const n = byNumeric[+d.id];
            if (!n) return;
            // find country name
            const name = rows.find(r => nameToNumeric[r.country] === +d.id)?.country || d.id;
            tip.style('display','block')
               .html(`<strong>${name}</strong><br>N Documents: ${n}`);
            d3.select(this).attr('stroke','#1d4ed8').attr('stroke-width',1.2);
          })
          .on('mousemove', function(event) {
            const rect = el.getBoundingClientRect();
            tip.style('left', (event.clientX - rect.left + 12) + 'px')
               .style('top',  (event.clientY - rect.top  - 28) + 'px');
          })
          .on('mouseout', function() {
            tip.style('display','none');
            d3.select(this).attr('stroke','#fff').attr('stroke-width',0.3);
          });

      // Legenda (5 níveis, estilo bibliometrix)
      const legendW = 16, legendH = 80;
      const legendX = 12, legendY = height - legendH - 50;

      const defs    = svg.append('defs');
      const gradId  = 'csp-grad-' + containerId;
      const grad    = defs.append('linearGradient')
        .attr('id', gradId).attr('x1','0').attr('y1','1').attr('x2','0').attr('y2','0');
      [0,.25,.5,.75,1].forEach(t => {
        grad.append('stop')
          .attr('offset', t)
          .attr('stop-color', color(Math.max(1, Math.exp(Math.log(maxN) * t))));
      });

      const leg = svg.append('g').attr('transform', `translate(${legendX},${legendY})`);
      leg.append('rect')
        .attr('width', legendW).attr('height', legendH)
        .style('fill', `url(#${gradId})`);
      leg.append('text').attr('x', legendW + 5).attr('y', 6)
        .attr('fill','#e5e7eb').attr('font-size','10').text(maxN);
      leg.append('text').attr('x', legendW + 5).attr('y', legendH)
        .attr('fill','#e5e7eb').attr('font-size','10').text('1');
      leg.append('text').attr('x', 0).attr('y', -6)
        .attr('fill','#9ca3af').attr('font-size','10').text(bt('map_legend'));

    })
    .catch(err => {
      console.error('buildCountryMap:', err);
      el.innerHTML = `<p style="color:#f87171;padding:16px"><i class="fa fa-triangle-exclamation"></i> ${bt('map_error', { msg: err.message })}</p>`;
    });
}

// =============================================================================
// ───── ÍNDICES DE IMPACTO LOCAL (h, g, m) ────────────────────────────────────
// Tabela ranqueada por índice h, no estilo "Local Impact" do bibliometrix.
// =============================================================================
function buildImpactTable(containerId, rows, kind) {
  const el = document.getElementById(containerId);
  if (!el) return;
  if (!rows || !rows.length) {
    el.innerHTML = `<div class="empty-state" style="padding:24px"><i class="fa fa-database"></i><p style="font-size:.82rem">${bt('impact_empty')}</p></div>`;
    return;
  }
  bibReplaySave(buildImpactTable, arguments);

  const isAuthor = kind === 'author';
  const th = (txt, align = 'center') =>
    `<th style="text-align:${align};padding:8px 10px;border-bottom:1px solid var(--border);color:var(--text-muted);font-weight:600">${txt}</th>`;

  let html = '<table style="width:100%;border-collapse:collapse;font-size:0.82rem"><thead><tr>'
    + th('#') + th(isAuthor ? bt('author') : bt('journal'), 'left')
    + th('h') + th('g') + (isAuthor ? th('m') : '')
    + th(bt('docs')) + th(bt('citations'))
    + '</tr></thead><tbody>';

  rows.forEach((r, i) => {
    const name = isAuthor ? r.author : r.journal;
    html += `<tr style="border-bottom:1px solid var(--border);${i % 2 === 0 ? '' : 'background:rgba(255,255,255,0.02)'}">
      <td style="padding:7px 10px;color:var(--text-muted);text-align:center;font-weight:700">${i + 1}</td>
      <td style="padding:7px 10px;font-weight:500" title="${escAttr(name)}">${escHtml((name || '').substring(0, 55))}</td>
      <td style="padding:7px 10px;text-align:center"><span style="background:rgba(0,212,255,0.15);color:#00D4FF;border-radius:10px;padding:1px 9px;font-weight:700">${r.h_index}</span></td>
      <td style="padding:7px 10px;text-align:center">${r.g_index}</td>
      ${isAuthor ? `<td style="padding:7px 10px;text-align:center">${r.m_index ?? '—'}</td>` : ''}
      <td style="padding:7px 10px;text-align:center">${r.documents}</td>
      <td style="padding:7px 10px;text-align:center">${r.total_citations}</td>
    </tr>`;
  });

  el.innerHTML = html + '</tbody></table>';
}

// =============================================================================
// ───── MAPA TEMÁTICO (diagrama estratégico de Callon) ───────────────────────
// Bolhas em centralidade × densidade, com linhas medianas dividindo os
// quadrantes: motor · nicho · emergente/declinante · básico/transversal.
// =============================================================================
function buildThematicMap(canvasId, clusters) {
  const canvas = document.getElementById(canvasId);
  if (!canvas) return;
  if (window._thematicChart) { window._thematicChart.destroy(); }

  if (!clusters || !clusters.length) {
    const card = document.getElementById('chart-card-thematic_map');
    if (card) card.querySelector('.chart-wrapper').innerHTML =
      `<div class="empty-state" style="padding:24px"><i class="fa fa-map"></i><p style="font-size:.82rem">${bt('thematic_empty')}</p></div>`;
    return;
  }
  bibReplaySave(buildThematicMap, arguments);

  const median = arr => {
    const s = [...arr].sort((a, b) => a - b);
    return s.length % 2 ? s[(s.length - 1) / 2] : (s[s.length / 2 - 1] + s[s.length / 2]) / 2;
  };
  const medX = median(clusters.map(c => c.centrality));
  const medY = median(clusters.map(c => c.density));

  const maxOcc  = Math.max(...clusters.map(c => c.occurrences), 1);
  const palette = generatePalette(clusters.length);

  const datasets = clusters.map((c, i) => ({
    label: c.name,
    data: [{ x: c.centrality, y: c.density, r: 8 + 28 * Math.sqrt(c.occurrences / maxOcc) }],
    backgroundColor: palette[i],
    borderColor: palette[i].replace('0.82', '1'),
    borderWidth: 1.5,
    _cluster: c,
  }));

  // Plugin: linhas medianas + rótulo de cada bolha
  const quadrantPlugin = {
    id: 'callonQuadrants',
    afterDatasetsDraw(chart) {
      const { ctx, chartArea, scales } = chart;
      const isLight = document.body.classList.contains('theme-light');

      const px = scales.x.getPixelForValue(medX);
      const py = scales.y.getPixelForValue(medY);
      ctx.save();
      ctx.strokeStyle = isLight ? 'rgba(0,0,0,0.25)' : 'rgba(255,255,255,0.25)';
      ctx.setLineDash([6, 5]);
      ctx.lineWidth = 1;
      ctx.beginPath();
      ctx.moveTo(px, chartArea.top);    ctx.lineTo(px, chartArea.bottom);
      ctx.moveTo(chartArea.left, py);   ctx.lineTo(chartArea.right, py);
      ctx.stroke();
      ctx.setLineDash([]);

      // Nomes dos quadrantes nos cantos
      ctx.font = '600 10px Inter, sans-serif';
      ctx.fillStyle = isLight ? 'rgba(0,0,0,0.35)' : 'rgba(255,255,255,0.35)';
      ctx.textAlign = 'left';
      ctx.fillText(bt('quad_niche'),    chartArea.left + 8, chartArea.top + 14);
      ctx.fillText(bt('quad_emerging'), chartArea.left + 8, chartArea.bottom - 8);
      ctx.textAlign = 'right';
      ctx.fillText(bt('quad_motor'),    chartArea.right - 8, chartArea.top + 14);
      ctx.fillText(bt('quad_basic'),    chartArea.right - 8, chartArea.bottom - 8);

      // Rótulo de cada cluster sobre a bolha
      ctx.textAlign = 'center';
      ctx.font = '600 11px Inter, sans-serif';
      ctx.fillStyle = isLight ? '#1e293b' : '#f1f5f9';
      chart.data.datasets.forEach((ds, i) => {
        const meta = chart.getDatasetMeta(i);
        if (!meta.data[0]) return;
        const { x, y } = meta.data[0];
        const lbl = ds.label.length > 24 ? ds.label.substring(0, 23) + '…' : ds.label;
        ctx.fillText(lbl, x, y - meta.data[0].options.r - 4);
      });
      ctx.restore();
    },
  };

  window._thematicChart = new Chart(canvas.getContext('2d'), {
    type: 'bubble',
    data: { datasets },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            title: items => items[0].dataset.label,
            label: item => {
              const c = item.dataset._cluster;
              return [
                bt('thematic_tip_cd',    { c: c.centrality.toFixed(2), d: c.density.toFixed(2) }),
                bt('thematic_tip_kw',    { k: c.n_keywords, o: c.occurrences }),
                bt('thematic_tip_terms', { terms: c.keywords.slice(0, 5).join(', ') }),
              ];
            },
          },
        },
      },
      scales: {
        x: darkAxisConfig({ title: { display: true, text: bt('thematic_x'), color: bibTextColor(), font: { size: 11 } } }),
        y: darkAxisConfig({ title: { display: true, text: bt('thematic_y'), color: bibTextColor(), font: { size: 11 } } }),
      },
    },
    plugins: [quadrantPlugin],
  });
}

// =============================================================================
// ───── 1. TOP INSTITUIÇÕES ────────────────────────────────────────────────────
// =============================================================================
function buildTopInstitutionsChart(canvasId, rows) {
  const canvas = document.getElementById(canvasId);
  if (!canvas) return;
  if (!rows || !rows.length) {
    canvas.insertAdjacentHTML('afterend', `<p style="color:var(--text-muted);font-size:.8rem;padding:12px 0">${bt('inst_empty')}</p>`);
    return;
  }
  bibReplaySave(buildTopInstitutionsChart, arguments);
  if (window._instChart) window._instChart.destroy();
  const isLight = document.body.classList.contains('theme-light');
  const tc = isLight ? '#475569' : '#94a3b8';
  const top = rows.slice(0, 20);
  window._instChart = new Chart(canvas.getContext('2d'), {
    type: 'bar',
    data: {
      labels: top.map(r => r.institution.length > 40 ? r.institution.slice(0, 39) + '…' : r.institution),
      datasets: [{
        label: bt('articles'),
        data: top.map(r => +r.n_articles),
        backgroundColor: 'rgba(139,92,246,0.72)',
        borderColor: '#8b5cf6', borderWidth: 1, borderRadius: 4,
      }],
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      indexAxis: 'y',
      plugins: {
        legend: { display: false },
        title: { display: true, text: bt('inst_title', { n: top.length }), color: tc, font: { size: 11, weight: '600' } },
        tooltip: { callbacks: { label: c => bt('n_articles', { n: c.raw }) } },
      },
      scales: {
        x: { beginAtZero: true, ticks: { color: tc }, grid: { color: isLight ? 'rgba(0,0,0,0.06)' : 'rgba(255,255,255,0.05)' },
             title: { display: true, text: bt('articles'), color: tc } },
        y: { ticks: { color: tc, font: { size: 9 } }, grid: { color: 'transparent' } },
      },
    },
  });
}

// =============================================================================
// ───── 2. MCP vs SCP ─────────────────────────────────────────────────────────
// =============================================================================
function buildMcpScpChart(canvasId, data) {
  const canvas = document.getElementById(canvasId);
  if (!canvas) return;
  if (!data || !data.mcp_scp || !data.mcp_scp.length) {
    canvas.insertAdjacentHTML('afterend', `<p style="color:var(--text-muted);font-size:.8rem;padding:12px 0">${bt('mcp_empty')}</p>`);
    return;
  }
  bibReplaySave(buildMcpScpChart, arguments);
  if (window._mcpChart) window._mcpChart.destroy();
  const isLight = document.body.classList.contains('theme-light');
  const tc = isLight ? '#475569' : '#94a3b8';
  const rows = data.mcp_scp.slice(0, 15);
  window._mcpChart = new Chart(canvas.getContext('2d'), {
    type: 'bar',
    data: {
      labels: rows.map(r => r.country),
      datasets: [
        { label: bt('scp_label'), data: rows.map(r => +r.scp), backgroundColor: 'rgba(0,212,255,0.72)', borderRadius: 3 },
        { label: bt('mcp_label'), data: rows.map(r => +r.mcp), backgroundColor: 'rgba(0,229,160,0.72)', borderRadius: 3 },
      ],
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: {
        legend: { position: 'top', labels: { color: tc } },
        title: { display: true,
          text: bt('mcp_title', { ci: data.collaboration_index }),
          color: tc, font: { size: 11, weight: '600' } },
      },
      scales: {
        x: { stacked: true, ticks: { color: tc }, grid: { color: 'transparent' } },
        y: { stacked: true, beginAtZero: true, ticks: { color: tc },
             grid: { color: isLight ? 'rgba(0,0,0,0.06)' : 'rgba(255,255,255,0.05)' },
             title: { display: true, text: bt('articles'), color: tc } },
      },
    },
  });
}

// =============================================================================
// ───── 3. PRODUÇÃO DOS AUTORES AO LONGO DO TEMPO ─────────────────────────────
// =============================================================================
function buildAuthorOverTimeChart(canvasId, data) {
  const canvas = document.getElementById(canvasId);
  if (!canvas || !data || !data.author_over_time) return;
  bibReplaySave(buildAuthorOverTimeChart, arguments);
  if (window._aotChart) window._aotChart.destroy();
  const isLight = document.body.classList.contains('theme-light');
  const tc = isLight ? '#475569' : '#94a3b8';
  const authors = data.authors || [];
  const rows = data.author_over_time;
  const years = [...new Set(rows.map(r => +r.year))].sort((a,b)=>a-b);
  const palette = ['#00D4FF','#00e5a0','#f59e0b','#8b5cf6','#f87171',
                   '#34d399','#60a5fa','#fb923c','#a78bfa','#4ade80'];
  const datasets = authors.map((author, i) => {
    const byYear = Object.fromEntries(rows.filter(r => r.full_name === author).map(r => [+r.year, +r.n]));
    return {
      label: author.split(',')[0].trim(),
      data: years.map(y => ({ x: y, y: author, r: byYear[y] ? 5 + byYear[y] * 6 : 0 })),
      backgroundColor: palette[i % palette.length] + 'bb',
      borderColor: palette[i % palette.length],
    };
  });
  window._aotChart = new Chart(canvas.getContext('2d'), {
    type: 'bubble',
    data: { datasets },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: {
        legend: { position: 'right', labels: { color: tc, font: { size: 9 }, boxWidth: 10 } },
        title: { display: true, text: bt('aot_title'), color: tc, font: { size: 11, weight: '600' } },
        tooltip: { callbacks: { label: c => `${c.dataset.label} · ${c.raw.x}: ` + bt('n_articles', { n: Math.round((c.raw.r - 5) / 6) }) } },
      },
      scales: {
        x: { type: 'linear', ticks: { stepSize: 1, color: tc, callback: v => v },
             grid: { color: isLight ? 'rgba(0,0,0,0.06)' : 'rgba(255,255,255,0.05)' },
             title: { display: true, text: bt('year'), color: tc } },
        y: { type: 'category', labels: authors.map(a => a.split(',')[0].trim()).reverse(),
             ticks: { color: tc, font: { size: 9 } }, grid: { color: isLight ? 'rgba(0,0,0,0.06)' : 'rgba(255,255,255,0.05)' } },
      },
    },
  });
}

// =============================================================================
// ───── 4. REDE DE COLABORAÇÃO ENTRE PAÍSES ───────────────────────────────────
// =============================================================================
function buildCountryCollabChart(containerId, data) {
  const el = document.getElementById(containerId);
  if (!el || !data) return;
  const nodes = data.nodes || [], edges = data.edges || [];

  // nodeMap é a lista de países válidos (com n >= 2 vindos do PHP)
  // Usa-se como whitelist: só mostra arestas onde AMBOS países são nós válidos
  const nodeMap = Object.fromEntries(nodes.map(n => [n.country, +n.n]));
  const validSet = new Set(Object.keys(nodeMap));
  const filteredEdges = edges.filter(e => validSet.has(e.c1) && validSet.has(e.c2));

  if (!filteredEdges.length) {
    el.innerHTML = `<p style="color:var(--text-muted);padding:24px;text-align:center">${bt('collab_empty')}</p>`;
    return;
  }
  bibReplaySave(buildCountryCollabChart, arguments);

  const maxN = nodes.length ? Math.max(...nodes.map(n => +n.n)) : 1;
  const maxW = Math.max(...filteredEdges.map(e => +e.weight));
  // Países que aparecem nas arestas filtradas
  const countries = [...new Set([...filteredEdges.map(e=>e.c1),...filteredEdges.map(e=>e.c2)])];
  const n = countries.length;
  const W = el.clientWidth || 700, H = Math.max(420, Math.min(600, n * 14));
  const cx = W / 2, cy = H / 2, R = Math.min(W, H) * 0.38;
  const pos = Object.fromEntries(countries.map((c, i) => {
    const a = (2 * Math.PI * i) / n - Math.PI / 2;
    return [c, { x: cx + R * Math.cos(a), y: cy + R * Math.sin(a) }];
  }));
  let svg = `<svg viewBox="0 0 ${W} ${H}" xmlns="http://www.w3.org/2000/svg" style="width:100%;height:${H}px">`;
  // Arestas
  filteredEdges.forEach(e => {
    if (!pos[e.c1] || !pos[e.c2]) return;
    const t = Math.max(1, Math.round(1 + 4 * +e.weight / maxW));
    const op = (0.2 + 0.6 * +e.weight / maxW).toFixed(2);
    svg += `<line x1="${pos[e.c1].x.toFixed(1)}" y1="${pos[e.c1].y.toFixed(1)}"
                  x2="${pos[e.c2].x.toFixed(1)}" y2="${pos[e.c2].y.toFixed(1)}"
                  stroke="#00D4FF" stroke-width="${t}" stroke-opacity="${op}">
              <title>${e.c1} &#8596; ${e.c2}: ${bt('n_articles', { n: e.weight })}</title></line>`;
  });
  // Nós
  countries.forEach(c => {
    const p = pos[c]; if (!p) return;
    const pn = nodeMap[c] || 1;
    const r = Math.max(6, Math.round(6 + 14 * pn / maxN));
    svg += `<circle cx="${p.x.toFixed(1)}" cy="${p.y.toFixed(1)}" r="${r}"
                    fill="#8b5cf6" fill-opacity="0.85" stroke="#a78bfa" stroke-width="1.5">
              <title>${c}: ${bt('n_articles', { n: pn })}</title></circle>`;
    const anchor = p.x < cx - 10 ? 'end' : p.x > cx + 10 ? 'start' : 'middle';
    const dy = p.y < cy ? -r - 3 : r + 11;
    svg += `<text x="${p.x.toFixed(1)}" y="${(p.y + dy).toFixed(1)}"
                  text-anchor="${anchor}" font-size="9" fill="#94a3b8">${c}</text>`;
  });
  svg += '</svg>';
  el.innerHTML = svg;
}

// =============================================================================
// ───── 5. EVOLUÇÃO TEMÁTICA ───────────────────────────────────────────────────
// =============================================================================
function buildThematicEvolution(containerId, data) {
  const el = document.getElementById(containerId);
  if (!el || !data || !data.thematic_evolution) return;
  const periods = data.thematic_evolution;

  const allEmpty = periods.every(p => !p.clusters || !p.clusters.length);
  if (!periods.length || allEmpty) {
    el.innerHTML = `<p style="color:var(--text-muted);padding:24px;text-align:center">${bt('evol_empty')}</p>`;
    return;
  }
  bibReplaySave(buildThematicEvolution, arguments);

  const esc = s => String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  const quadrant = (c) => {
    const medC = 5, medD = 50;
    const centrality = +c.centrality, density = +c.density;
    if (centrality >= medC && density >= medD) return { label: bt('q_motor'), color: '#00e5a0' };
    if (centrality  < medC && density >= medD) return { label: bt('q_niche'), color: '#8b5cf6' };
    if (centrality >= medC && density  < medD) return { label: bt('q_basic'), color: '#f59e0b' };
    return { label: bt('q_emerging'), color: '#94a3b8' };
  };

  try {
    el.innerHTML = `
      <div style="display:grid;grid-template-columns:repeat(${periods.length},1fr);gap:16px">
        ${periods.map(p => `
          <div>
            <div style="text-align:center;font-size:.75rem;font-weight:700;color:var(--primary);margin-bottom:10px;text-transform:uppercase;letter-spacing:.06em">
              <i class="fa fa-clock"></i> ${esc(p.period)}
            </div>
            ${p.clusters.length === 0
              ? `<p style="color:var(--text-muted);font-size:.75rem;text-align:center;padding:12px 0">${bt('evol_no_clusters')}</p>`
              : p.clusters.map(c => {
                const q = quadrant(c);
                const centrality = (+c.centrality).toFixed(1);
                const density    = (+c.density).toFixed(1);
                return `
                <div style="border:1px solid ${q.color}44;border-radius:8px;padding:8px 10px;margin-bottom:8px;background:${q.color}0d">
                  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px">
                    <strong style="font-size:.8rem;color:${q.color}">${esc(c.name)}</strong>
                    <span style="font-size:.65rem;background:${q.color}33;color:${q.color};padding:2px 6px;border-radius:10px">${q.label}</span>
                  </div>
                  <div style="font-size:.7rem;color:var(--text-muted);line-height:1.5">
                    ${c.keywords.slice(1).map(esc).join(' &middot; ')}<br>
                    <span style="font-size:.65rem">C=${centrality} &middot; D=${density} &middot; ${c.n_keywords} kw</span>
                  </div>
                </div>`;
              }).join('')}
          </div>
        `).join('')}
      </div>`;
  } catch (e) {
    el.innerHTML = `<p style="color:#f87171;padding:16px">${bt('evol_error', { msg: esc(e.message) })}</p>`;
  }
}
