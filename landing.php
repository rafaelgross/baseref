<?php
/**
 * BaseRef — landing.php
 * Public landing page (presentation + login).
 * Shown to unauthenticated visitors from index.php.
 * Supports PT / EN / ES and light/dark theme (default: dark, same as the app).
 */
$loginUrl     = '/login.php';
$githubUrl    = 'https://github.com/rafaelgross/baseref';
$contactEmail = 'rafaelgross123@gmail.com';
$doiSoftware  = 'https://doi.org/10.5281/zenodo.21119457'; // v1.0.0 code archived on Zenodo
$doiData      = 'https://doi.org/10.5281/zenodo.21133086'; // pilot review data (CC BY 4.0)
$videoId      = 'B_sD4ZO_xMU';                             // video tutorial (YouTube)
$videoUrl     = 'https://youtu.be/' . $videoId;
$manualUrl    = '/TUTORIAL.pdf';                           // user manual (generated from TUTORIAL.md)
?>
<!DOCTYPE html>
<html lang="pt" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>BaseRef — Revisão Sistemática da Literatura com PRISMA 2020 | Grátis</title>
<meta name="description" data-i18n="meta_desc"
      content="Plataforma gratuita para revisão sistemática da literatura: importe referências (BibTeX, RIS, PubMed), remova duplicatas, faça triagem com IA e gere o diagrama PRISMA 2020 automaticamente.">
<link rel="canonical" href="https://baseref.com.br/">
<meta name="robots" content="index, follow">
<meta name="author" content="Rafael Gross">
<link rel="icon" type="image/png" href="/assets/img/logo.png">
<link rel="apple-touch-icon" href="/assets/img/logo.png">
<!-- Open Graph -->
<meta property="og:type" content="website">
<meta property="og:site_name" content="BaseRef">
<meta property="og:url" content="https://baseref.com.br/">
<meta property="og:title" content="BaseRef — Revisão Sistemática da Literatura com PRISMA 2020">
<meta property="og:description" content="Plataforma gratuita e open source para revisões sistemáticas: importação de referências, deduplicação, triagem com IA e diagrama PRISMA 2020 automático.">
<meta property="og:image" content="https://baseref.com.br/assets/img/baseref_light-2.png">
<meta property="og:locale" content="pt_BR">
<meta property="og:locale:alternate" content="en_US">
<meta property="og:locale:alternate" content="es_ES">
<!-- Twitter Card -->
<meta name="twitter:card" content="summary">
<meta name="twitter:title" content="BaseRef — Revisão Sistemática da Literatura com PRISMA 2020">
<meta name="twitter:description" content="Plataforma gratuita e open source para revisões sistemáticas da literatura com protocolo PRISMA 2020.">
<meta name="twitter:image" content="https://baseref.com.br/assets/img/baseref_light-2.png">
<!-- Dados estruturados: aplicação -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebApplication",
  "name": "BaseRef",
  "url": "https://baseref.com.br/",
  "description": "Plataforma gratuita de apoio à pesquisa para revisões sistemáticas da literatura com protocolo PRISMA 2020: importação de referências, deduplicação, triagem assistida por IA, elegibilidade, bibliometria e diagrama de fluxo automático.",
  "applicationCategory": "EducationalApplication",
  "operatingSystem": "Web",
  "softwareVersion": "1.0.0",
  "license": "https://opensource.org/licenses/MIT",
  "identifier": "https://doi.org/10.5281/zenodo.21119457",
  "sameAs": [
    "https://github.com/rafaelgross/baseref",
    "https://doi.org/10.5281/zenodo.21119457"
  ],
  "inLanguage": ["pt-BR", "en", "es"],
  "offers": { "@type": "Offer", "price": "0", "priceCurrency": "BRL" },
  "author": {
    "@type": "Person",
    "name": "Rafael Gross",
    "email": "mailto:rafaelgross123@gmail.com"
  }
}
</script>
<!-- Dados estruturados: FAQ -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {"@type":"Question","name":"O BaseRef é gratuito?","acceptedAnswer":{"@type":"Answer","text":"Sim. O BaseRef é 100% gratuito e open source, distribuído sob licença MIT. Basta entrar com sua conta Google e criar seu projeto de revisão sistemática."}},
    {"@type":"Question","name":"O que é o protocolo PRISMA 2020?","acceptedAnswer":{"@type":"Answer","text":"PRISMA 2020 é o padrão internacional para relato de revisões sistemáticas da literatura. O BaseRef gera automaticamente o diagrama de fluxo PRISMA e acompanha os 27 itens do checklist obrigatório."}},
    {"@type":"Question","name":"De quais bases de dados posso importar referências?","acceptedAnswer":{"@type":"Answer","text":"Scopus, Web of Science, PubMed, Embase, IEEE Xplore, Mendeley, Zotero, EndNote e qualquer base que exporte em BibTeX, RIS ou PubMed XML — com deduplicação automática."}},
    {"@type":"Question","name":"Preciso instalar algum programa?","acceptedAnswer":{"@type":"Answer","text":"Não. O BaseRef roda inteiramente no navegador. Você entra com sua conta Google e seus dados ficam isolados e seguros."}},
    {"@type":"Question","name":"Como entro em contato para tirar dúvidas?","acceptedAnswer":{"@type":"Answer","text":"Envie um e-mail para rafaelgross123@gmail.com — respondemos dúvidas sobre uso da plataforma, revisões sistemáticas e o protocolo PRISMA 2020."}}
  ]
}
</script>
<!-- Dados estruturados: tutorial em vídeo -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "VideoObject",
  "name": "BaseRef — tutorial: revisão sistemática da literatura com PRISMA 2020",
  "description": "Demonstração completa do BaseRef: criação do projeto, importação de referências, deduplicação, triagem, elegibilidade e geração do diagrama de fluxo PRISMA 2020.",
  "thumbnailUrl": "https://i.ytimg.com/vi/<?php echo htmlspecialchars($videoId); ?>/maxresdefault.jpg",
  "uploadDate": "2026-05-22",
  "embedUrl": "https://www.youtube.com/embed/<?php echo htmlspecialchars($videoId); ?>",
  "contentUrl": "<?php echo htmlspecialchars($videoUrl); ?>",
  "inLanguage": "pt-BR",
  "publisher": { "@type": "Person", "name": "Rafael Gross" }
}
</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Lora:ital@0;1&display=swap" rel="stylesheet">
<style>
:root{
  /* dark (default — same as the app) */
  --bg:#0a0f1a; --bg-soft:#0e1626; --surface:#111c30; --surface-2:#16243c;
  --text:#e8eef8; --text-soft:#a4b3cc; --muted:#6f819e;
  --border:#1d3050; --border-strong:#294268;
  --brand:#00d4ff; --brand-2:#00e5a0; --brand-deep:#5fe0ff; --accent:#a855f7;
  --ok:#00e5a0; --warn:#ffb700;
  --shadow:0 18px 50px -16px rgba(0,0,0,.6); --shadow-sm:0 2px 12px rgba(0,0,0,.4);
  --radius:16px;
  --grad:linear-gradient(135deg,#00d4ff 0%,#a855f7 100%);
  --grad-soft:linear-gradient(135deg,rgba(0,212,255,.14),rgba(168,85,247,.14));
  /* buttons: lighter gradient + dark text (AA contrast across the whole range) */
  --btn-grad:linear-gradient(135deg,#38e1ff 0%,#c084fc 100%);
  --btn-text:#071018;
  --btn-glow:rgba(56,225,255,.45);
}
[data-theme="light"]{
  --bg:#f6f9fc; --bg-soft:#eef3f9; --surface:#ffffff; --surface-2:#f1f6fb;
  --text:#0f1b2d; --text-soft:#475569; --muted:#7b8aa0;
  --border:#e2e8f2; --border-strong:#cdd8e8;
  --brand:#0099cc; --brand-2:#059669; --brand-deep:#0b3c91; --accent:#7c3aed;
  --ok:#059669; --warn:#d97706;
  --shadow:0 10px 40px -12px rgba(13,42,92,.18); --shadow-sm:0 2px 8px rgba(13,42,92,.08);
  --grad:linear-gradient(135deg,#0099cc 0%,#7c3aed 100%);
  --grad-soft:linear-gradient(135deg,rgba(0,153,204,.1),rgba(124,58,237,.1));
  /* buttons: deeper gradient + white text (AA contrast across the whole range) */
  --btn-grad:linear-gradient(135deg,#0369a1 0%,#6d28d9 100%);
  --btn-text:#ffffff;
  --btn-glow:rgba(109,40,217,.35);
}
*{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body{
  font-family:'Inter',system-ui,-apple-system,Segoe UI,Roboto,sans-serif;
  background:var(--bg); color:var(--text); line-height:1.6;
  -webkit-font-smoothing:antialiased; transition:background .3s,color .3s;
}
a{color:inherit;text-decoration:none}
.wrap{max-width:1140px;margin:0 auto;padding:0 24px}

/* header */
header{position:sticky;top:0;z-index:50;backdrop-filter:saturate(160%) blur(12px);
  background:color-mix(in srgb,var(--bg) 80%,transparent);border-bottom:1px solid var(--border)}
.nav{display:flex;align-items:center;justify-content:space-between;height:68px}
.logo{display:flex;align-items:center;gap:11px;font-weight:800;font-size:1.18rem;letter-spacing:-.4px}
.logo img.logo-img{height:50px;width:auto;display:block}
.nav-links{display:flex;align-items:center;gap:26px}
.nav-links a.navlink{color:var(--text-soft);font-weight:500;font-size:.94rem;transition:color .2s}
.nav-links a.navlink:hover{color:var(--brand)}
.nav-tools{display:flex;align-items:center;gap:10px}

.lang{position:relative}
.lang-btn,.theme-btn{display:flex;align-items:center;gap:7px;cursor:pointer;background:var(--surface);
  border:1px solid var(--border);color:var(--text-soft);padding:8px 12px;border-radius:10px;font-size:.88rem;font-weight:600;transition:.2s}
.lang-btn:hover,.theme-btn:hover{border-color:var(--brand);color:var(--brand)}
.theme-btn{padding:8px 10px}
.lang-menu{position:absolute;right:0;top:46px;background:var(--surface);border:1px solid var(--border);
  border-radius:12px;box-shadow:var(--shadow);padding:6px;min-width:160px;display:none}
.lang-menu.open{display:block;animation:pop .14s ease}
@keyframes pop{from{opacity:0;transform:translateY(-6px)}to{opacity:1;transform:none}}
.lang-menu button{width:100%;display:flex;align-items:center;gap:10px;background:none;border:0;cursor:pointer;
  padding:9px 11px;border-radius:8px;color:var(--text);font-size:.9rem;font-weight:500;text-align:left}
.lang-menu button:hover{background:var(--surface-2)}
.lang-menu button.active{color:var(--brand);font-weight:700}
.flag{font-size:1.1rem;line-height:1}

.btn{display:inline-flex;align-items:center;gap:9px;font-weight:600;font-size:.94rem;padding:11px 20px;
  border-radius:11px;cursor:pointer;border:1px solid transparent;transition:transform .15s,box-shadow .2s,background .2s;white-space:nowrap}
.btn:active{transform:translateY(1px)}
.btn-primary{background:var(--btn-grad);color:var(--btn-text);box-shadow:0 8px 24px -8px var(--btn-glow)}
.btn-primary:hover{box-shadow:0 12px 32px -8px var(--btn-glow);filter:brightness(1.08)}
.btn-ghost{background:var(--surface);color:var(--text);border-color:var(--border-strong)}
.btn-ghost:hover{border-color:var(--brand);color:var(--brand)}
.btn-google{background:#fff;color:#1f2937;border:1px solid #e2e8f2}
.btn-google:hover{box-shadow:var(--shadow-sm)}
.btn-lg{padding:14px 26px;font-size:1rem}

/* hero */
.hero{position:relative;overflow:hidden;padding:84px 0 70px}
.hero::before{content:"";position:absolute;inset:0;z-index:0;opacity:.9;
  background:radial-gradient(620px 320px at 82% -10%,rgba(168,85,247,.18),transparent 70%),
            radial-gradient(560px 320px at 6% 30%,rgba(0,212,255,.16),transparent 70%)}
.hero .wrap{position:relative;z-index:1;display:grid;grid-template-columns:1.05fr .95fr;gap:54px;align-items:center}
.badge{display:inline-flex;align-items:center;gap:8px;background:var(--surface);border:1px solid var(--border);
  color:var(--brand);font-weight:600;font-size:.82rem;padding:6px 13px;border-radius:999px;box-shadow:var(--shadow-sm);margin-bottom:22px}
.badge .dot{width:7px;height:7px;border-radius:50%;background:var(--brand-2);box-shadow:0 0 0 4px rgba(0,229,160,.18)}
h1{font-size:3.05rem;line-height:1.08;letter-spacing:-1.4px;font-weight:800;margin-bottom:20px}
h1 .grad{background:var(--grad);-webkit-background-clip:text;background-clip:text;color:transparent}
.lead{font-size:1.18rem;color:var(--text-soft);max-width:540px;margin-bottom:30px}
.hero-cta{display:flex;gap:14px;flex-wrap:wrap;margin-bottom:26px}
.hero-meta{display:flex;gap:26px;flex-wrap:wrap;color:var(--muted);font-size:.88rem}
.hero-meta span{display:flex;align-items:center;gap:7px}
.hero-meta svg{width:16px;height:16px;color:var(--ok)}

.flowcard{background:var(--surface);border:1px solid var(--border);border-radius:20px;box-shadow:var(--shadow);padding:24px}
.flowcard h4{font-size:.78rem;text-transform:uppercase;letter-spacing:1px;color:var(--muted);margin-bottom:16px}
.flow-step{display:flex;align-items:center;gap:13px;background:var(--surface-2);border:1px solid var(--border);
  border-radius:12px;padding:13px 15px;margin-bottom:10px;position:relative}
.flow-step .n{width:30px;height:30px;border-radius:9px;background:var(--btn-grad);color:var(--btn-text);display:grid;place-items:center;font-weight:700;font-size:.86rem;flex-shrink:0}
.flow-step .lbl{font-weight:600;font-size:.93rem}
.flow-step .cnt{margin-left:auto;font-variant-numeric:tabular-nums;color:var(--brand);font-weight:700;font-size:.92rem}
.flow-step:not(:last-child)::after{content:"";position:absolute;left:29px;bottom:-11px;height:11px;width:2px;background:var(--border-strong)}

section.block{padding:72px 0}
.sec-head{text-align:center;max-width:680px;margin:0 auto 48px}
.kicker{color:var(--brand);font-weight:700;font-size:.82rem;text-transform:uppercase;letter-spacing:1.4px;margin-bottom:12px}
h2{font-size:2.1rem;letter-spacing:-.8px;font-weight:800;margin-bottom:14px}
.sec-head p{color:var(--text-soft);font-size:1.06rem}

.grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px}
.card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:26px 24px;transition:transform .18s,box-shadow .2s,border-color .2s}
.card:hover{transform:translateY(-4px);box-shadow:var(--shadow);border-color:var(--brand)}
.card .ico{width:46px;height:46px;border-radius:12px;display:grid;place-items:center;margin-bottom:16px;background:var(--grad-soft);color:var(--brand)}
.card .ico svg{width:24px;height:24px}
.card h3{font-size:1.08rem;margin-bottom:8px;font-weight:700}
.card p{color:var(--text-soft);font-size:.94rem}

/* tutorial: video + manual */
.tut-wrap{background:var(--bg-soft);border-top:1px solid var(--border);border-bottom:1px solid var(--border)}
.tut-grid{display:grid;grid-template-columns:1.4fr .6fr;gap:24px;align-items:start}
.video-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;box-shadow:var(--shadow)}
.video-frame{position:relative;padding-bottom:56.25%;height:0;background:#000;cursor:pointer;border:0;width:100%;display:block;padding-top:0}
.video-frame img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;transition:transform .4s,opacity .25s}
.video-frame:hover img{transform:scale(1.03);opacity:.86}
.video-frame iframe{position:absolute;inset:0;width:100%;height:100%;border:0}
.video-frame .play{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:74px;height:74px;border-radius:50%;
  background:rgba(220,30,45,.94);display:grid;place-items:center;box-shadow:0 10px 30px rgba(0,0,0,.45);transition:transform .2s,background .2s}
.video-frame:hover .play{transform:translate(-50%,-50%) scale(1.09);background:#e11d2e}
.video-frame .play svg{width:28px;height:28px;fill:#fff;margin-left:4px}
.video-cap{display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;padding:15px 20px;border-top:1px solid var(--border)}
.video-cap p{color:var(--text-soft);font-size:.9rem}
.video-cap a{color:var(--brand);font-weight:600;font-size:.88rem;white-space:nowrap}
.manual-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:26px 24px;box-shadow:var(--shadow-sm);height:100%}
.manual-card .ico{width:46px;height:46px;border-radius:12px;display:grid;place-items:center;margin-bottom:15px;background:var(--grad-soft);color:var(--brand)}
.manual-card .ico svg{width:24px;height:24px}
.manual-card h3{font-size:1.08rem;margin-bottom:8px;font-weight:700}
.manual-card p{color:var(--text-soft);font-size:.93rem;margin-bottom:16px}
.manual-list{list-style:none;margin-bottom:20px}
.manual-list li{display:flex;align-items:flex-start;gap:9px;color:var(--text-soft);font-size:.89rem;margin-bottom:8px}
.manual-list svg{width:15px;height:15px;flex-shrink:0;margin-top:3px;color:var(--ok)}
.manual-card .btn{width:100%;justify-content:center}

.db-wrap{background:var(--bg-soft);border-top:1px solid var(--border);border-bottom:1px solid var(--border)}
.table-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;box-shadow:var(--shadow-sm)}
table{width:100%;border-collapse:collapse}
th,td{padding:14px 22px;text-align:left;font-size:.95rem}
thead th{background:var(--surface-2);font-weight:700;color:var(--text);font-size:.82rem;text-transform:uppercase;letter-spacing:.6px}
tbody tr{border-top:1px solid var(--border)}
tbody td:first-child{font-weight:600}
.tag{display:inline-block;background:var(--surface-2);border:1px solid var(--border);color:var(--text-soft);font-size:.78rem;font-weight:600;padding:3px 9px;border-radius:7px;margin:2px 4px 2px 0}

.sec-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:18px}
.sec-item{display:flex;flex-direction:column;gap:8px;padding:22px;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius)}
.sec-item .ico{width:40px;height:40px;border-radius:11px;display:grid;place-items:center;background:rgba(0,229,160,.12);color:var(--ok)}
.sec-item .ico svg{width:21px;height:21px}
.sec-item h4{font-size:1rem}
.sec-item p{color:var(--text-soft);font-size:.88rem}

/* FAQ */
.faq-list{max-width:760px;margin:0 auto}
.faq-item{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);margin-bottom:12px;overflow:hidden;transition:border-color .2s}
.faq-item[open]{border-color:var(--brand)}
.faq-item summary{cursor:pointer;list-style:none;display:flex;align-items:center;justify-content:space-between;gap:14px;padding:18px 22px;font-weight:600;font-size:1rem}
.faq-item summary::-webkit-details-marker{display:none}
.faq-item summary .chev{flex-shrink:0;color:var(--muted);transition:transform .2s}
.faq-item[open] summary .chev{transform:rotate(180deg);color:var(--brand)}
.faq-item .faq-a{padding:0 22px 18px;color:var(--text-soft);font-size:.95rem}
.faq-item .faq-a a{color:var(--brand);font-weight:600}

.cta-band{padding:74px 0}
.cta-inner{position:relative;overflow:hidden;background:var(--btn-grad);border-radius:24px;padding:56px 48px;text-align:center;color:var(--btn-text);box-shadow:var(--shadow)}
.cta-inner::after{content:"";position:absolute;inset:0;opacity:.16;background:radial-gradient(400px 200px at 80% 0,#fff,transparent 60%);pointer-events:none}
.cta-inner h2{color:var(--btn-text);position:relative;font-size:2.2rem}
.cta-inner p{color:color-mix(in srgb,var(--btn-text) 85%,transparent);font-size:1.1rem;max-width:560px;margin:12px auto 28px;position:relative}
.cta-inner .btn-google{position:relative}
.cta-contact{position:relative;margin-top:20px;font-size:.95rem;color:color-mix(in srgb,var(--btn-text) 88%,transparent)}
.cta-contact a{font-weight:700;text-decoration:underline;color:var(--btn-text)}

footer{border-top:1px solid var(--border);background:var(--bg-soft);padding:54px 0 30px}
.foot-grid{display:grid;grid-template-columns:1.4fr 1fr 1fr 1fr;gap:32px;margin-bottom:34px}
.foot-grid p{color:var(--text-soft);font-size:.92rem;margin-top:12px;max-width:340px}
.foot-col h5{font-size:.8rem;text-transform:uppercase;letter-spacing:1px;color:var(--muted);margin-bottom:14px}
.foot-col a{display:block;color:var(--text-soft);font-size:.92rem;margin-bottom:9px;transition:color .2s}
.foot-col a:hover{color:var(--brand)}
.cite{background:var(--surface);border:1px solid var(--border);border-left:3px solid var(--brand);border-radius:10px;padding:16px 18px;font-size:.86rem;color:var(--text-soft);margin-bottom:26px}
.cite code{font-family:'Lora',serif;font-style:italic}
.foot-bottom{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;padding-top:22px;border-top:1px solid var(--border);color:var(--muted);font-size:.86rem}

@media(max-width:920px){
  .hero .wrap{grid-template-columns:1fr;gap:40px}
  .tut-grid{grid-template-columns:1fr}
  .grid{grid-template-columns:repeat(2,1fr)}
  .sec-grid{grid-template-columns:repeat(2,1fr)}
  .foot-grid{grid-template-columns:1fr 1fr}
  h1{font-size:2.5rem}.nav-links a.navlink{display:none}
}
@media(max-width:600px){
  .grid,.sec-grid,.foot-grid{grid-template-columns:1fr}
  h1{font-size:2.1rem}.cta-inner{padding:40px 24px}
  th,td{padding:12px 14px;font-size:.86rem}
}
</style>
</head>
<body>

<header>
  <div class="wrap nav">
    <a href="#" class="logo">
      <img class="logo-img" id="headerLogo" src="assets/img/baseref_light-2_trim.png" alt="BaseRef">
    </a>
    <nav class="nav-links">
      <a class="navlink" href="#tutorial"  data-i18n="nav_tutorial">Tutorial</a>
      <a class="navlink" href="#features"  data-i18n="nav_features">Recursos</a>
      <a class="navlink" href="#databases" data-i18n="nav_databases">Bases</a>
      <a class="navlink" href="#security"  data-i18n="nav_security">Segurança</a>
      <a class="navlink" href="#faq"       data-i18n="nav_faq">Dúvidas</a>
      <div class="nav-tools">
        <div class="lang">
          <button class="lang-btn" id="langBtn" aria-haspopup="true" aria-expanded="false">
            <span class="flag" id="curFlag">🇧🇷</span><span id="curLang">PT</span>
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M6 9l6 6 6-6"/></svg>
          </button>
          <div class="lang-menu" id="langMenu" role="menu">
            <button data-lang="pt" class="active"><span class="flag">🇧🇷</span> Português</button>
            <button data-lang="en"><span class="flag">🇺🇸</span> English</button>
            <button data-lang="es"><span class="flag">🇪🇸</span> Español</button>
          </div>
        </div>
        <button class="theme-btn" id="themeBtn" aria-label="Tema">
          <svg id="iconTheme" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
        </button>
        <a class="btn btn-primary" href="<?php echo htmlspecialchars($loginUrl); ?>" data-i18n="nav_login">Entrar</a>
      </div>
    </nav>
  </div>
</header>

<section class="hero">
  <div class="wrap">
    <div class="hero-text">
      <span class="badge"><span class="dot"></span><span data-i18n="hero_badge">Gratuito · Open source · PRISMA 2020</span></span>
      <h1><span data-i18n="hero_title_1">Sua revisão sistemática da literatura,</span> <span class="grad" data-i18n="hero_title_2">da busca ao artigo publicado.</span></h1>
      <p class="lead" data-i18n="hero_lead">Plataforma gratuita de apoio à pesquisa que centraliza toda a revisão sistemática: importação de referências, deduplicação, triagem com IA, elegibilidade e geração automática do diagrama PRISMA 2020.</p>
      <div class="hero-cta">
        <a class="btn btn-primary btn-lg" href="<?php echo htmlspecialchars($loginUrl); ?>">
          <svg width="19" height="19" viewBox="0 0 24 24"><path fill="currentColor" d="M21.35 11.1H12v3.83h5.4c-.24 1.4-1.66 4.1-5.4 4.1-3.25 0-5.9-2.69-5.9-6s2.65-6 5.9-6c1.85 0 3.09.79 3.8 1.47l2.6-2.5C16.7 3.9 14.6 3 12 3 6.99 3 3 6.99 3 12s3.99 9 9 9c5.2 0 8.64-3.65 8.64-8.8 0-.59-.06-1.04-.29-2.1z"/></svg>
          <span data-i18n="hero_cta_login">Entrar com Google</span>
        </a>
        <a class="btn btn-ghost btn-lg" href="#features" data-i18n="hero_cta_more">Conhecer recursos</a>
      </div>
      <div class="hero-meta">
        <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg><span data-i18n="hero_m1">Multiusuário e isolado</span></span>
        <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg><span data-i18n="hero_m2">PT · EN · ES</span></span>
        <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg><span data-i18n="hero_m3">100% gratuito</span></span>
      </div>
    </div>
    <div class="hero-visual">
      <div class="flowcard">
        <h4 data-i18n="flow_title">Fluxo PRISMA 2020</h4>
        <div class="flow-step"><span class="n">1</span><span class="lbl" data-i18n="flow_1">Identificação</span><span class="cnt">1.248</span></div>
        <div class="flow-step"><span class="n">2</span><span class="lbl" data-i18n="flow_2">Remoção de duplicatas</span><span class="cnt">−312</span></div>
        <div class="flow-step"><span class="n">3</span><span class="lbl" data-i18n="flow_3">Triagem (título/resumo)</span><span class="cnt">936</span></div>
        <div class="flow-step"><span class="n">4</span><span class="lbl" data-i18n="flow_4">Elegibilidade (texto completo)</span><span class="cnt">184</span></div>
        <div class="flow-step"><span class="n">5</span><span class="lbl" data-i18n="flow_5">Estudos incluídos</span><span class="cnt">47</span></div>
      </div>
    </div>
  </div>
</section>

<section class="block tut-wrap" id="tutorial">
  <div class="wrap">
    <div class="sec-head">
      <div class="kicker" data-i18n="tut_kicker">Tutorial</div>
      <h2 data-i18n="tut_title">Veja o BaseRef funcionando</h2>
      <p data-i18n="tut_sub">Assista à demonstração completa da plataforma e baixe o manual do usuário em PDF.</p>
    </div>

    <div class="tut-grid">
      <div class="video-card">
        <button type="button" class="video-frame" id="videoFrame"
                data-video="<?php echo htmlspecialchars($videoId); ?>" aria-label="Play">
          <img src="https://i.ytimg.com/vi/<?php echo htmlspecialchars($videoId); ?>/maxresdefault.jpg"
               alt="BaseRef — tutorial em vídeo" loading="lazy"
               onerror="this.onerror=null;this.src='https://i.ytimg.com/vi/<?php echo htmlspecialchars($videoId); ?>/hqdefault.jpg'">
          <span class="play"><svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg></span>
        </button>
        <div class="video-cap">
          <p data-i18n="tut_video_cap">Do primeiro projeto ao diagrama PRISMA 2020 pronto para publicação.</p>
          <a href="<?php echo htmlspecialchars($videoUrl); ?>" target="_blank" rel="noopener"
             data-i18n="tut_video_yt">Assistir no YouTube ↗</a>
        </div>
      </div>

      <div class="manual-card">
        <div class="ico">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 4h11l5 5v11H4z"/><path d="M15 4v5h5"/><path d="M8 13h8M8 17h5"/>
          </svg>
        </div>
        <h3 data-i18n="tut_manual_title">Manual do Usuário (PDF)</h3>
        <p data-i18n="tut_manual_desc">Guia passo a passo de todas as etapas da revisão sistemática, da importação ao checklist PRISMA.</p>
        <ul class="manual-list">
          <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"><path d="M20 6L9 17l-5-5"/></svg><span data-i18n="tut_manual_i1">Importação, deduplicação e triagem</span></li>
          <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"><path d="M20 6L9 17l-5-5"/></svg><span data-i18n="tut_manual_i2">Dois revisores e kappa de Cohen</span></li>
          <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"><path d="M20 6L9 17l-5-5"/></svg><span data-i18n="tut_manual_i3">Diagrama PRISMA em SVG e PNG 300 dpi</span></li>
          <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"><path d="M20 6L9 17l-5-5"/></svg><span data-i18n="tut_manual_i4">Bibliometria, referências e exportação</span></li>
        </ul>
        <a class="btn btn-primary" href="<?php echo htmlspecialchars($manualUrl); ?>" target="_blank" rel="noopener">
          <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12M8 11l4 4 4-4M4 21h16"/></svg>
          <span data-i18n="tut_manual_btn">Baixar o manual</span>
        </a>
      </div>
    </div>
  </div>
</section>

<section class="block" id="features">
  <div class="wrap">
    <div class="sec-head">
      <div class="kicker" data-i18n="feat_kicker">Recursos</div>
      <h2 data-i18n="feat_title">Tudo o que sua pesquisa precisa</h2>
      <p data-i18n="feat_sub">Da importação à publicação, com suporte completo ao protocolo PRISMA 2020.</p>
    </div>
    <div class="grid" id="featGrid"></div>
  </div>
</section>

<section class="block db-wrap" id="databases">
  <div class="wrap">
    <div class="sec-head">
      <div class="kicker" data-i18n="db_kicker">Bases bibliográficas</div>
      <h2 data-i18n="db_title">Importe de onde você já trabalha</h2>
      <p data-i18n="db_sub">Suporte a BibTeX, RIS, PubMed XML e texto puro, com deduplicação automática.</p>
    </div>
    <div class="table-card">
      <table>
        <thead><tr><th data-i18n="db_col1">Base de dados</th><th data-i18n="db_col2">Formato de exportação</th></tr></thead>
        <tbody>
          <tr><td>Scopus</td><td><span class="tag">BibTeX</span><span class="tag">RIS</span></td></tr>
          <tr><td>Web of Science</td><td><span class="tag">BibTeX</span><span class="tag">RIS</span></td></tr>
          <tr><td>PubMed</td><td><span class="tag">XML</span><span class="tag">RIS</span></td></tr>
          <tr><td>Embase</td><td><span class="tag">RIS</span></td></tr>
          <tr><td>Mendeley / Zotero / EndNote</td><td><span class="tag">RIS</span><span class="tag">BibTeX</span></td></tr>
          <tr><td>IEEE Xplore</td><td><span class="tag">BibTeX</span></td></tr>
          <tr><td data-i18n="db_others">Outras</td><td><span class="tag">BibTeX</span><span class="tag">RIS</span></td></tr>
        </tbody>
      </table>
    </div>
  </div>
</section>

<section class="block" id="security">
  <div class="wrap">
    <div class="sec-head">
      <div class="kicker" data-i18n="sec_kicker">Segurança & dados</div>
      <h2 data-i18n="sec_title">Login seguro, dados isolados</h2>
      <p data-i18n="sec_sub">Autenticação via Google OAuth 2.0 — cada pesquisador vê apenas seus próprios projetos.</p>
    </div>
    <div class="sec-grid" id="secGrid"></div>
  </div>
</section>

<section class="block" id="faq">
  <div class="wrap">
    <div class="sec-head">
      <div class="kicker" data-i18n="faq_kicker">Perguntas frequentes</div>
      <h2 data-i18n="faq_title">Dúvidas sobre revisão sistemática e o BaseRef</h2>
      <p><span data-i18n="faq_sub_pre">Não encontrou sua resposta? Escreva para</span> <a href="mailto:<?php echo htmlspecialchars($contactEmail); ?>" style="color:var(--brand);font-weight:600"><?php echo htmlspecialchars($contactEmail); ?></a>.</p>
    </div>
    <div class="faq-list" id="faqList"></div>
  </div>
</section>

<section class="cta-band">
  <div class="wrap">
    <div class="cta-inner">
      <h2 data-i18n="cta_title">Comece a organizar sua pesquisa hoje</h2>
      <p data-i18n="cta_sub">Entre com sua conta Google e crie seu primeiro projeto em segundos. Sem senhas, sem instalação local.</p>
      <a class="btn btn-google btn-lg" href="<?php echo htmlspecialchars($loginUrl); ?>">
        <svg width="20" height="20" viewBox="0 0 24 24"><path fill="#4285F4" d="M21.35 11.1H12v3.83h5.4c-.24 1.4-1.66 4.1-5.4 4.1-3.25 0-5.9-2.69-5.9-6s2.65-6 5.9-6c1.85 0 3.09.79 3.8 1.47l2.6-2.5C16.7 3.9 14.6 3 12 3 6.99 3 3 6.99 3 12s3.99 9 9 9c5.2 0 8.64-3.65 8.64-8.8 0-.59-.06-1.04-.29-2.1z"/></svg>
        <span data-i18n="cta_btn">Entrar com Google</span>
      </a>
      <p class="cta-contact"><span data-i18n="cta_contact">Dúvidas? Fale comigo:</span> <a href="mailto:<?php echo htmlspecialchars($contactEmail); ?>"><?php echo htmlspecialchars($contactEmail); ?></a></p>
    </div>
  </div>
</section>

<footer>
  <div class="wrap">
    <div class="foot-grid">
      <div class="foot-col">
        <a href="#" class="logo"><img class="logo-img" id="footerLogo" src="assets/img/baseref_light-2_trim.png" alt="BaseRef"></a>
        <p data-i18n="foot_about">Plataforma open-source de apoio à pesquisa e gestão de referências bibliográficas, com suporte ao protocolo PRISMA 2020.</p>
      </div>
      <div class="foot-col">
        <h5 data-i18n="foot_product">Produto</h5>
        <a href="#tutorial" data-i18n="nav_tutorial">Tutorial</a>
        <a href="#features" data-i18n="nav_features">Recursos</a>
        <a href="#databases" data-i18n="nav_databases">Bases</a>
        <a href="#security" data-i18n="nav_security">Segurança</a>
        <a href="<?php echo htmlspecialchars($loginUrl); ?>" data-i18n="nav_login">Entrar</a>
      </div>
      <div class="foot-col">
        <h5 data-i18n="foot_resources">Recursos</h5>
        <a href="<?php echo htmlspecialchars($manualUrl); ?>" target="_blank" rel="noopener" data-i18n="foot_manual">Manual do usuário (PDF)</a>
        <a href="<?php echo htmlspecialchars($videoUrl); ?>" target="_blank" rel="noopener" data-i18n="foot_video">Tutorial em vídeo</a>
        <a href="<?php echo htmlspecialchars($githubUrl); ?>" target="_blank" rel="noopener">GitHub</a>
        <a href="<?php echo htmlspecialchars($doiSoftware); ?>" target="_blank" rel="noopener" data-i18n="foot_zenodo_sw">Código no Zenodo (DOI)</a>
        <a href="<?php echo htmlspecialchars($doiData); ?>" target="_blank" rel="noopener" data-i18n="foot_zenodo_data">Dados do piloto (Zenodo)</a>
        <a href="https://www.bmj.com/content/372/bmj.n71" target="_blank" rel="noopener">PRISMA 2020 (BMJ)</a>
        <a href="<?php echo htmlspecialchars($githubUrl); ?>/blob/main/LICENSE" target="_blank" rel="noopener" data-i18n="foot_license">Licença MIT</a>
      </div>
      <div class="foot-col">
        <h5 data-i18n="foot_contact">Contato</h5>
        <a href="mailto:<?php echo htmlspecialchars($contactEmail); ?>"><?php echo htmlspecialchars($contactEmail); ?></a>
        <a href="#faq" data-i18n="nav_faq">Dúvidas</a>
      </div>
    </div>
    <div class="cite">
      <strong data-i18n="cite_label">Como citar:</strong>
      <code> GROSS, Rafael; GONÇALVES, Rodrigo Franco. BaseRef: plataforma de apoio à pesquisa e gestão de referências bibliográficas. Versão 1.0.0 [software]. Zenodo, 2026. DOI: <a href="<?php echo htmlspecialchars($doiSoftware); ?>" target="_blank" rel="noopener" style="color:var(--brand)">10.5281/zenodo.21119457</a>. Disponível em: &lt;https://baseref.com.br/&gt;. Acesso em: <span id="citeDate"></span>.</code>
    </div>
    <div class="foot-bottom">
      <span>© <span id="year"></span> BaseRef · <span data-i18n="foot_rights">Distribuído sob licença MIT</span></span>
      <span data-i18n="foot_made">Feito para pesquisadores</span>
    </div>
  </div>
</footer>

<script>
const I18N = {
  pt:{
    page_title:"BaseRef — Revisão Sistemática da Literatura com PRISMA 2020 | Grátis",
    meta_desc:"Plataforma gratuita para revisão sistemática da literatura: importe referências (BibTeX, RIS, PubMed), remova duplicatas, faça triagem com IA e gere o diagrama PRISMA 2020 automaticamente.",
    nav_tutorial:"Tutorial",
    nav_features:"Recursos", nav_databases:"Bases", nav_security:"Segurança", nav_faq:"Dúvidas", nav_login:"Entrar",
    tut_kicker:"Tutorial", tut_title:"Veja o BaseRef funcionando",
    tut_sub:"Assista à demonstração completa da plataforma e baixe o manual do usuário em PDF.",
    tut_video_cap:"Do primeiro projeto ao diagrama PRISMA 2020 pronto para publicação.",
    tut_video_yt:"Assistir no YouTube ↗",
    tut_manual_title:"Manual do Usuário (PDF)",
    tut_manual_desc:"Guia passo a passo de todas as etapas da revisão sistemática, da importação ao checklist PRISMA.",
    tut_manual_i1:"Importação, deduplicação e triagem",
    tut_manual_i2:"Dois revisores e kappa de Cohen",
    tut_manual_i3:"Diagrama PRISMA em SVG e PNG 300 dpi",
    tut_manual_i4:"Bibliometria, referências e exportação",
    tut_manual_btn:"Baixar o manual",
    foot_manual:"Manual do usuário (PDF)", foot_video:"Tutorial em vídeo",
    hero_badge:"Gratuito · Open source · PRISMA 2020",
    hero_title_1:"Sua revisão sistemática da literatura,", hero_title_2:"da busca ao artigo publicado.",
    hero_lead:"Plataforma gratuita de apoio à pesquisa que centraliza toda a revisão sistemática: importação de referências, deduplicação, triagem com IA, elegibilidade e geração automática do diagrama PRISMA 2020.",
    hero_cta_login:"Entrar com Google", hero_cta_more:"Conhecer recursos",
    hero_m1:"Multiusuário e isolado", hero_m2:"PT · EN · ES", hero_m3:"100% gratuito",
    flow_title:"Fluxo PRISMA 2020", flow_1:"Identificação", flow_2:"Remoção de duplicatas",
    flow_3:"Triagem (título/resumo)", flow_4:"Elegibilidade (texto completo)", flow_5:"Estudos incluídos",
    feat_kicker:"Recursos", feat_title:"Tudo o que sua pesquisa precisa", feat_sub:"Da importação à publicação, com suporte completo ao protocolo PRISMA 2020.",
    db_kicker:"Bases bibliográficas", db_title:"Importe de onde você já trabalha",
    db_sub:"Suporte a BibTeX, RIS, PubMed XML e texto puro, com deduplicação automática.",
    db_col1:"Base de dados", db_col2:"Formato de exportação", db_others:"Outras",
    sec_kicker:"Segurança & dados", sec_title:"Login seguro, dados isolados",
    sec_sub:"Autenticação via Google OAuth 2.0 — cada pesquisador vê apenas seus próprios projetos.",
    faq_kicker:"Perguntas frequentes", faq_title:"Dúvidas sobre revisão sistemática e o BaseRef",
    faq_sub_pre:"Não encontrou sua resposta? Escreva para",
    cta_title:"Comece a organizar sua pesquisa hoje",
    cta_sub:"Entre com sua conta Google e crie seu primeiro projeto em segundos. Sem senhas, sem instalação local.",
    cta_btn:"Entrar com Google", cta_contact:"Dúvidas? Fale comigo:",
    foot_about:"Plataforma open-source de apoio à pesquisa e gestão de referências bibliográficas, com suporte ao protocolo PRISMA 2020.",
    foot_product:"Produto", foot_resources:"Recursos", foot_contact:"Contato", foot_license:"Licença MIT",
    foot_zenodo_sw:"Código no Zenodo (DOI)", foot_zenodo_data:"Dados do piloto (Zenodo)",
    cite_label:"Como citar:", foot_rights:"Distribuído sob licença MIT", foot_made:"Feito para pesquisadores",
    faq:[
      ["O BaseRef é gratuito?","Sim. O BaseRef é 100% gratuito e open source, distribuído sob licença MIT. Basta entrar com sua conta Google e criar seu projeto de revisão sistemática."],
      ["O que é o protocolo PRISMA 2020?","PRISMA 2020 é o padrão internacional para relato de revisões sistemáticas da literatura. O BaseRef gera automaticamente o diagrama de fluxo PRISMA e acompanha os 27 itens do checklist obrigatório."],
      ["De quais bases de dados posso importar referências?","Scopus, Web of Science, PubMed, Embase, IEEE Xplore, Mendeley, Zotero, EndNote e qualquer base que exporte em BibTeX, RIS ou PubMed XML — com deduplicação automática."],
      ["Preciso instalar algum programa?","Não. O BaseRef roda inteiramente no navegador. Você entra com sua conta Google e seus dados ficam isolados e seguros."],
      ["Como entro em contato para tirar dúvidas?","Envie um e-mail para <a href='mailto:rafaelgross123@gmail.com'>rafaelgross123@gmail.com</a> — respondo dúvidas sobre o uso da plataforma, revisões sistemáticas e o protocolo PRISMA 2020."]
    ],
    features:[
      ["ai","Triagem assistida por IA","Inteligência artificial analisa títulos e resumos e sugere inclusão ou exclusão com base nos seus critérios de pesquisa."],
      ["collab","Colaboração em equipe","Convide múltiplos pesquisadores para o mesmo projeto com dados e permissões isoladas por usuário."],
      ["pico","Método PICO","Estruture sua questão de pesquisa com o framework PICO integrado ao projeto."],
      ["import","Importação de referências","BibTeX (.bib), RIS (.ris), PubMed XML (.xml) e texto puro (.txt) das principais bases."],
      ["dedup","Deduplicação automática","Detecta e sinaliza artigos duplicados entre bases de dados automaticamente."],
      ["screen","Triagem de título e resumo","Interface para incluir/excluir artigos com motivos configuráveis e suporte de IA."],
      ["full","Elegibilidade de texto completo","Segunda fase de avaliação com motivos personalizáveis e avaliação de qualidade."],
      ["agree","Concordância entre revisores","Calcula o índice de concordância entre os avaliadores do projeto."],
      ["prisma","Diagrama de fluxo PRISMA 2020","Fluxograma SVG automático com exportação em PNG e SVG."],
      ["biblio","Bibliometria","Gráficos por ano, periódico, autor, país e mapas de coautoria."],
      ["check","Checklist PRISMA 2020","Os 27 itens obrigatórios com acompanhamento de conformidade."],
      ["refs","Referências formatadas","Geração automática em ABNT NBR 6023:2018 e APA 7ª edição."]
    ],
    security:[
      ["Google OAuth 2.0","Login seguro com conta Google — nenhuma senha é armazenada localmente."],
      ["Proteção CSRF","Token de estado em todos os fluxos OAuth."],
      ["Sessões expiráveis","Expiração automática das sessões após 8 horas."],
      ["Consultas seguras","Consultas parametrizadas via PDO em todos os endpoints."]
    ]
  },
  en:{
    page_title:"BaseRef — Systematic Literature Review with PRISMA 2020 | Free",
    meta_desc:"Free platform for systematic literature reviews: import references (BibTeX, RIS, PubMed), remove duplicates, screen with AI and generate the PRISMA 2020 flow diagram automatically.",
    nav_tutorial:"Tutorial",
    nav_features:"Features", nav_databases:"Databases", nav_security:"Security", nav_faq:"FAQ", nav_login:"Sign in",
    tut_kicker:"Tutorial", tut_title:"See BaseRef in action",
    tut_sub:"Watch the full platform walkthrough and download the user manual in PDF.",
    tut_video_cap:"From your first project to a PRISMA 2020 diagram ready for publication.",
    tut_video_yt:"Watch on YouTube ↗",
    tut_manual_title:"User Manual (PDF)",
    tut_manual_desc:"Step-by-step guide to every stage of the systematic review, from import to the PRISMA checklist.",
    tut_manual_i1:"Import, deduplication and screening",
    tut_manual_i2:"Two reviewers and Cohen's kappa",
    tut_manual_i3:"PRISMA diagram in SVG and 300 dpi PNG",
    tut_manual_i4:"Bibliometrics, references and export",
    tut_manual_btn:"Download the manual",
    foot_manual:"User manual (PDF)", foot_video:"Video tutorial",
    hero_badge:"Free · Open source · PRISMA 2020",
    hero_title_1:"Your systematic literature review,", hero_title_2:"from search to published paper.",
    hero_lead:"A free research support platform that centralizes your entire systematic review: reference import, deduplication, AI-assisted screening, eligibility and automatic PRISMA 2020 flow diagram generation.",
    hero_cta_login:"Sign in with Google", hero_cta_more:"Explore features",
    hero_m1:"Multi-user & isolated", hero_m2:"PT · EN · ES", hero_m3:"100% free",
    flow_title:"PRISMA 2020 flow", flow_1:"Identification", flow_2:"Duplicates removed",
    flow_3:"Screening (title/abstract)", flow_4:"Eligibility (full text)", flow_5:"Studies included",
    feat_kicker:"Features", feat_title:"Everything your research needs", feat_sub:"From import to publication, with full PRISMA 2020 protocol support.",
    db_kicker:"Bibliographic databases", db_title:"Import from where you already work",
    db_sub:"Supports BibTeX, RIS, PubMed XML and plain text, with automatic deduplication.",
    db_col1:"Database", db_col2:"Export format", db_others:"Others",
    sec_kicker:"Security & data", sec_title:"Secure login, isolated data",
    sec_sub:"Authentication via Google OAuth 2.0 — each researcher sees only their own projects.",
    faq_kicker:"Frequently asked questions", faq_title:"Questions about systematic reviews and BaseRef",
    faq_sub_pre:"Didn't find your answer? Write to",
    cta_title:"Start organizing your research today",
    cta_sub:"Sign in with your Google account and create your first project in seconds. No passwords, no local install.",
    cta_btn:"Sign in with Google", cta_contact:"Questions? Contact me:",
    foot_about:"Open-source research support platform for managing bibliographic references with PRISMA 2020 protocol support.",
    foot_product:"Product", foot_resources:"Resources", foot_contact:"Contact", foot_license:"MIT License",
    foot_zenodo_sw:"Source code on Zenodo (DOI)", foot_zenodo_data:"Pilot data (Zenodo)",
    cite_label:"How to cite:", foot_rights:"Distributed under the MIT license", foot_made:"Built for researchers",
    faq:[
      ["Is BaseRef free?","Yes. BaseRef is 100% free and open source, distributed under the MIT license. Just sign in with your Google account and create your systematic review project."],
      ["What is the PRISMA 2020 protocol?","PRISMA 2020 is the international standard for reporting systematic literature reviews. BaseRef automatically generates the PRISMA flow diagram and tracks all 27 mandatory checklist items."],
      ["Which databases can I import references from?","Scopus, Web of Science, PubMed, Embase, IEEE Xplore, Mendeley, Zotero, EndNote and any database that exports BibTeX, RIS or PubMed XML — with automatic deduplication."],
      ["Do I need to install anything?","No. BaseRef runs entirely in your browser. You sign in with your Google account and your data stays isolated and secure."],
      ["How do I get in touch with questions?","Send an email to <a href='mailto:rafaelgross123@gmail.com'>rafaelgross123@gmail.com</a> — I answer questions about the platform, systematic reviews and the PRISMA 2020 protocol."]
    ],
    features:[
      ["ai","AI-assisted screening","Artificial intelligence analyzes titles and abstracts and suggests inclusion or exclusion based on your research criteria."],
      ["collab","Team collaboration","Invite multiple researchers to the same project with isolated data and permissions per user."],
      ["pico","PICO method","Structure your research question using the PICO framework integrated into the project."],
      ["import","Reference import","BibTeX (.bib), RIS (.ris), PubMed XML (.xml) and plain text (.txt) from major databases."],
      ["dedup","Automatic deduplication","Detects and flags duplicate articles across databases automatically."],
      ["screen","Title & abstract screening","Interface to include/exclude articles with configurable reasons and AI support."],
      ["full","Full-text eligibility","Second assessment phase with customizable reasons and quality assessment."],
      ["agree","Inter-rater agreement","Calculates the agreement index between project reviewers."],
      ["prisma","PRISMA 2020 flow diagram","Automatic SVG flowchart with PNG and SVG export."],
      ["biblio","Bibliometrics","Charts by year, journal, author, country and co-authorship maps."],
      ["check","PRISMA 2020 checklist","All 27 mandatory items with compliance tracking."],
      ["refs","Formatted references","Automatic generation in ABNT NBR 6023:2018 and APA 7th edition."]
    ],
    security:[
      ["Google OAuth 2.0","Secure login with a Google account — no passwords stored locally."],
      ["CSRF protection","State token across all OAuth flows."],
      ["Expiring sessions","Sessions expire automatically after 8 hours."],
      ["Safe queries","Parameterized queries via PDO in all endpoints."]
    ]
  },
  es:{
    page_title:"BaseRef — Revisión Sistemática de la Literatura con PRISMA 2020 | Gratis",
    meta_desc:"Plataforma gratuita para revisiones sistemáticas de la literatura: importa referencias (BibTeX, RIS, PubMed), elimina duplicados, criba con IA y genera el diagrama PRISMA 2020 automáticamente.",
    nav_tutorial:"Tutorial",
    nav_features:"Funciones", nav_databases:"Bases", nav_security:"Seguridad", nav_faq:"Dudas", nav_login:"Entrar",
    tut_kicker:"Tutorial", tut_title:"Mira BaseRef en acción",
    tut_sub:"Ve la demostración completa de la plataforma y descarga el manual del usuario en PDF.",
    tut_video_cap:"Del primer proyecto al diagrama PRISMA 2020 listo para publicar.",
    tut_video_yt:"Ver en YouTube ↗",
    tut_manual_title:"Manual del Usuario (PDF)",
    tut_manual_desc:"Guía paso a paso de todas las etapas de la revisión sistemática, de la importación al checklist PRISMA.",
    tut_manual_i1:"Importación, deduplicación y cribado",
    tut_manual_i2:"Dos revisores y kappa de Cohen",
    tut_manual_i3:"Diagrama PRISMA en SVG y PNG 300 dpi",
    tut_manual_i4:"Bibliometría, referencias y exportación",
    tut_manual_btn:"Descargar el manual",
    foot_manual:"Manual del usuario (PDF)", foot_video:"Tutorial en vídeo",
    hero_badge:"Gratis · Código abierto · PRISMA 2020",
    hero_title_1:"Tu revisión sistemática de la literatura,", hero_title_2:"de la búsqueda al artículo publicado.",
    hero_lead:"Plataforma gratuita de apoyo a la investigación que centraliza toda la revisión sistemática: importación de referencias, deduplicación, cribado con IA, elegibilidad y generación automática del diagrama PRISMA 2020.",
    hero_cta_login:"Entrar con Google", hero_cta_more:"Ver funciones",
    hero_m1:"Multiusuario y aislado", hero_m2:"PT · EN · ES", hero_m3:"100% gratis",
    flow_title:"Flujo PRISMA 2020", flow_1:"Identificación", flow_2:"Eliminación de duplicados",
    flow_3:"Cribado (título/resumen)", flow_4:"Elegibilidad (texto completo)", flow_5:"Estudios incluidos",
    feat_kicker:"Funciones", feat_title:"Todo lo que tu investigación necesita", feat_sub:"De la importación a la publicación, con soporte completo al protocolo PRISMA 2020.",
    db_kicker:"Bases bibliográficas", db_title:"Importa desde donde ya trabajas",
    db_sub:"Compatible con BibTeX, RIS, PubMed XML y texto plano, con deduplicación automática.",
    db_col1:"Base de datos", db_col2:"Formato de exportación", db_others:"Otras",
    sec_kicker:"Seguridad y datos", sec_title:"Inicio de sesión seguro, datos aislados",
    sec_sub:"Autenticación mediante Google OAuth 2.0 — cada investigador ve solo sus propios proyectos.",
    faq_kicker:"Preguntas frecuentes", faq_title:"Dudas sobre revisiones sistemáticas y BaseRef",
    faq_sub_pre:"¿No encontraste tu respuesta? Escribe a",
    cta_title:"Comienza a organizar tu investigación hoy",
    cta_sub:"Entra con tu cuenta de Google y crea tu primer proyecto en segundos. Sin contraseñas, sin instalación local.",
    cta_btn:"Entrar con Google", cta_contact:"¿Dudas? Contáctame:",
    foot_about:"Plataforma de código abierto de apoyo a la investigación y gestión de referencias bibliográficas, con soporte al protocolo PRISMA 2020.",
    foot_product:"Producto", foot_resources:"Recursos", foot_contact:"Contacto", foot_license:"Licencia MIT",
    foot_zenodo_sw:"Código en Zenodo (DOI)", foot_zenodo_data:"Datos del piloto (Zenodo)",
    cite_label:"Cómo citar:", foot_rights:"Distribuido bajo licencia MIT", foot_made:"Hecho para investigadores",
    faq:[
      ["¿BaseRef es gratuito?","Sí. BaseRef es 100% gratuito y de código abierto, distribuido bajo licencia MIT. Solo entra con tu cuenta de Google y crea tu proyecto de revisión sistemática."],
      ["¿Qué es el protocolo PRISMA 2020?","PRISMA 2020 es el estándar internacional para el reporte de revisiones sistemáticas de la literatura. BaseRef genera automáticamente el diagrama de flujo PRISMA y da seguimiento a los 27 ítems obligatorios del checklist."],
      ["¿De qué bases de datos puedo importar referencias?","Scopus, Web of Science, PubMed, Embase, IEEE Xplore, Mendeley, Zotero, EndNote y cualquier base que exporte BibTeX, RIS o PubMed XML — con deduplicación automática."],
      ["¿Necesito instalar algo?","No. BaseRef funciona completamente en el navegador. Entras con tu cuenta de Google y tus datos permanecen aislados y seguros."],
      ["¿Cómo contacto para resolver dudas?","Envía un correo a <a href='mailto:rafaelgross123@gmail.com'>rafaelgross123@gmail.com</a> — respondo dudas sobre la plataforma, revisiones sistemáticas y el protocolo PRISMA 2020."]
    ],
    features:[
      ["ai","Cribado asistido por IA","La inteligencia artificial analiza títulos y resúmenes y sugiere inclusión o exclusión según tus criterios de investigación."],
      ["collab","Colaboración en equipo","Invita a múltiples investigadores al mismo proyecto con datos y permisos aislados por usuario."],
      ["pico","Método PICO","Estructura tu pregunta de investigación con el framework PICO integrado al proyecto."],
      ["import","Importación de referencias","BibTeX (.bib), RIS (.ris), PubMed XML (.xml) y texto plano (.txt) de las principales bases."],
      ["dedup","Deduplicación automática","Detecta y marca artículos duplicados entre bases de datos automáticamente."],
      ["screen","Cribado de título y resumen","Interfaz para incluir/excluir artículos con motivos configurables y apoyo de IA."],
      ["full","Elegibilidad de texto completo","Segunda fase de evaluación con motivos personalizables y evaluación de calidad."],
      ["agree","Concordancia entre revisores","Calcula el índice de concordancia entre los evaluadores del proyecto."],
      ["prisma","Diagrama de flujo PRISMA 2020","Diagrama SVG automático con exportación en PNG y SVG."],
      ["biblio","Bibliometría","Gráficos por año, revista, autor, país y mapas de coautoría."],
      ["check","Lista PRISMA 2020","Los 27 ítems obligatorios con seguimiento de cumplimiento."],
      ["refs","Referencias formateadas","Generación automática en ABNT NBR 6023:2018 y APA 7ª edición."]
    ],
    security:[
      ["Google OAuth 2.0","Inicio de sesión seguro con cuenta de Google — sin contraseñas almacenadas."],
      ["Protección CSRF","Token de estado en todos los flujos OAuth."],
      ["Sesiones con caducidad","Las sesiones expiran automáticamente tras 8 horas."],
      ["Consultas seguras","Consultas parametrizadas mediante PDO en todos los endpoints."]
    ]
  }
};
const FLAGS={pt:"🇧🇷",en:"🇺🇸",es:"🇪🇸"};
const ICONS={
  ai:'<path d="M12 2a4 4 0 0 1 4 4c0 1.5-.8 2.8-2 3.5V11h1a1 1 0 0 1 0 2h-1v1.5c1.2.7 2 2 2 3.5a4 4 0 0 1-8 0c0-1.5.8-2.8 2-3.5V13H9a1 1 0 0 1 0-2h1V9.5C8.8 8.8 8 7.5 8 6a4 4 0 0 1 4-4z"/><circle cx="19" cy="7" r="2"/><circle cx="5" cy="7" r="2"/><path d="M17 9c1 .5 2 1.5 2 3M7 9c-1 .5-2 1.5-2 3"/>',
  collab:'<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
  pico:'<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/>',
  agree:'<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="M22 4L12 14.01l-3-3"/>',
  projects:'<path d="M3 7h7l2 2h9v11H3z"/>',
  import:'<path d="M12 3v12M8 11l4 4 4-4M4 21h16"/>',
  dedup:'<path d="M8 8h10v10H8z"/><path d="M4 4h10v10"/>',
  screen:'<circle cx="11" cy="11" r="7"/><path d="M21 21l-4-4"/>',
  full:'<path d="M4 4h12l4 4v12H4z"/><path d="M9 13l2 2 4-4"/>',
  prisma:'<path d="M3 4h18M4 4l5 7v6l6 3v-9l5-7"/>',
  biblio:'<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
  check:'<path d="M9 11l3 3 8-8M21 12v7H3V5h12"/>',
  refs:'<path d="M4 5h16M4 12h16M4 19h10"/>'
};
function renderDynamic(lang){
  const t=I18N[lang];
  document.getElementById('featGrid').innerHTML = t.features.map(f=>`
    <div class="card"><div class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${ICONS[f[0]]||''}</svg></div><h3>${f[1]}</h3><p>${f[2]}</p></div>`).join('');
  const lock='<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>';
  document.getElementById('secGrid').innerHTML = t.security.map(s=>`
    <div class="sec-item"><div class="ico">${lock}</div><h4>${s[0]}</h4><p>${s[1]}</p></div>`).join('');
  const chev='<svg class="chev" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg>';
  document.getElementById('faqList').innerHTML = t.faq.map((q,i)=>`
    <details class="faq-item"${i===0?' open':''}><summary>${q[0]}${chev}</summary><div class="faq-a">${q[1]}</div></details>`).join('');
}
function setLang(lang){
  const t=I18N[lang];
  document.querySelectorAll('[data-i18n]').forEach(el=>{
    const k=el.getAttribute('data-i18n'); if(t[k]===undefined) return;
    if(el.tagName==='META') el.setAttribute('content',t[k]); else el.textContent=t[k];
  });
  document.documentElement.lang=lang;
  if(t.page_title) document.title=t.page_title;
  document.getElementById('curLang').textContent=lang.toUpperCase();
  document.getElementById('curFlag').textContent=FLAGS[lang];
  document.querySelectorAll('#langMenu button').forEach(b=>b.classList.toggle('active',b.dataset.lang===lang));
  renderDynamic(lang);
  try{localStorage.setItem('prisma_lang',lang)}catch(e){}
}
const langBtn=document.getElementById('langBtn'), langMenu=document.getElementById('langMenu');
langBtn.addEventListener('click',e=>{e.stopPropagation();langMenu.classList.toggle('open');langBtn.setAttribute('aria-expanded',langMenu.classList.contains('open'))});
document.addEventListener('click',()=>langMenu.classList.remove('open'));
langMenu.querySelectorAll('button').forEach(b=>b.addEventListener('click',()=>{setLang(b.dataset.lang);langMenu.classList.remove('open')}));
const themeBtn=document.getElementById('themeBtn');
function setTheme(th){
  document.documentElement.setAttribute('data-theme',th);
  var logo=th==='light'?'assets/img/baseref_light-2_trim.png':'assets/img/baseref_dark_trim.png';
  ['headerLogo','footerLogo'].forEach(function(id){var el=document.getElementById(id);if(el)el.src=logo;});
  try{localStorage.setItem('prisma_theme',th)}catch(e){}
}
themeBtn.addEventListener('click',()=>setTheme(document.documentElement.getAttribute('data-theme')==='dark'?'light':'dark'));
/* video: only loads the YouTube player after the click (no third-party cookies/JS before that) */
(function(){
  const frame=document.getElementById('videoFrame'); if(!frame) return;
  frame.addEventListener('click',function(){
    const box=document.createElement('div');
    box.className='video-frame';
    box.style.cursor='default';
    const f=document.createElement('iframe');
    f.src='https://www.youtube-nocookie.com/embed/'+frame.dataset.video+'?autoplay=1&rel=0';
    f.title='BaseRef — tutorial em vídeo';
    f.allow='accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture';
    f.allowFullscreen=true;
    box.appendChild(f);
    frame.replaceWith(box);
  });
})();
(function(){
  let lang='pt';
  try{lang=localStorage.getItem('prisma_lang')||(navigator.language||'pt').slice(0,2)}catch(e){}
  if(!I18N[lang]) lang='pt';
  let th='light'; try{th=localStorage.getItem('prisma_theme')||'light'}catch(e){}
  setTheme(th); setLang(lang);
  var now=new Date();
  document.getElementById('year').textContent=now.getFullYear();
  var mAbnt=['jan.','fev.','mar.','abr.','maio','jun.','jul.','ago.','set.','out.','nov.','dez.'];
  var cd=document.getElementById('citeDate');
  if(cd) cd.textContent=now.getDate()+' '+mAbnt[now.getMonth()]+' '+now.getFullYear();
})();
</script>
</body>
</html>
