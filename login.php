<?php
/**
 * PRISMA-SLR — login.php
 * Página de login com Google OAuth 2.0
 */
require_once __DIR__ . '/config/auth.php';

// Se já está logado, redireciona para o app
if (isLoggedIn()) {
    header('Location: /');
    exit;
}

startSecureSession();
$error = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Entrar — BaseRef</title>
  <script>
    // Aplica o tema salvo pela landing antes da primeira pintura (evita flash)
    (function(){
      var t = 'light';
      try { t = localStorage.getItem('prisma_theme') || 'light'; } catch(e) {}
      document.documentElement.setAttribute('data-theme', t);
    })();
  </script>
  <link rel="stylesheet" href="assets/vendor/inter-gfonts-v20/inter.css">
  <link rel="stylesheet" href="assets/vendor/fontawesome-6.5.1/css/all.min.css">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --bg:        #0f1117;
      --bg-card:   #1a1d27;
      --border:    #2a2d3a;
      --primary:   #6c63ff;
      --primary-h: #5a52e0;
      --text:      #e8eaf0;
      --text-muted:#8b8fa8;
      --radius:    14px;
      --box-shadow: 0 24px 80px rgba(0,0,0,.4);
    }

    [data-theme="light"] {
      --bg:        #f6f9fc;
      --bg-card:   #ffffff;
      --border:    #e2e8f2;
      --text:      #0f1b2d;
      --text-muted:#5b6579;
      --box-shadow: 0 24px 80px rgba(13,42,92,.14);
    }

    body {
      font-family: 'Inter', sans-serif;
      background: var(--bg);
      color: var(--text);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px;
      background-image:
        radial-gradient(ellipse 800px 600px at 30% 20%, rgba(108,99,255,.12) 0%, transparent 60%),
        radial-gradient(ellipse 600px 400px at 70% 80%, rgba(108,99,255,.08) 0%, transparent 60%);
    }

    .login-box {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: clamp(28px, 4vw, 48px) clamp(20px, 4vw, 40px);
      width: 100%;
      max-width: 440px;
      text-align: center;
      box-shadow: var(--box-shadow);
    }

    [data-theme="light"] .btn-google { border: 1px solid #dadce0; box-shadow: 0 1px 4px rgba(13,42,92,.08); }
    [data-theme="light"] .error-msg  { color: #b91c1c; }

    /* logos: um por tema, trocados via CSS (sem flash) */
    .logo img.logo-light { display: none; }
    [data-theme="light"] .logo img.logo-dark  { display: none; }
    [data-theme="light"] .logo img.logo-light { display: block; }

    .theme-btn {
      position: fixed;
      top: 18px;
      right: 18px;
      display: flex;
      align-items: center;
      justify-content: center;
      width: 40px;
      height: 40px;
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: 10px;
      color: var(--text-muted);
      cursor: pointer;
      transition: color .2s, border-color .2s;
    }
    .theme-btn:hover { color: var(--primary); border-color: var(--primary); }

    .logo {
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 20px;
    }
    .logo img {
      width: min(100%, 180px);
      height: auto;
    }

    h1 { font-size: clamp(1.35rem, 2vw, 1.6rem); font-weight: 700; margin-bottom: 6px; }
    .subtitle {
      color: var(--text-muted);
      font-size: clamp(.92rem, 2.5vw, 1rem);
      margin-bottom: 36px;
      line-height: 1.55;
      text-wrap: balance;
    }

    .btn-google {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 12px;
      width: 100%;
      padding: 13px 20px;
      background: #fff;
      color: #3c4043;
      border: none;
      border-radius: 10px;
      font-size: .95rem;
      font-weight: 600;
      font-family: 'Inter', sans-serif;
      cursor: pointer;
      text-decoration: none;
      transition: background .15s, box-shadow .15s, transform .1s;
      box-shadow: 0 2px 8px rgba(0,0,0,.25);
    }
    .btn-google:hover {
      background: #f8f9fa;
      box-shadow: 0 4px 16px rgba(0,0,0,.35);
      transform: translateY(-1px);
    }
    .btn-google:active { transform: translateY(0); }

    .google-icon {
      width: 20px;
      height: 20px;
      flex-shrink: 0;
    }

    .divider {
      border: none;
      border-top: 1px solid var(--border);
      margin: 28px 0;
    }

    .features {
      display: flex;
      flex-direction: column;
      gap: 10px;
      text-align: left;
    }
    .feature-item {
      display: flex;
      align-items: flex-start;
      gap: 10px;
      font-size: .88rem;
      line-height: 1.45;
      color: var(--text-muted);
    }
    .feature-item i {
      color: var(--primary);
      width: 16px;
      text-align: center;
      flex-shrink: 0;
    }

    .error-msg {
      background: rgba(239,68,68,.1);
      border: 1px solid rgba(239,68,68,.3);
      color: #fca5a5;
      border-radius: 8px;
      padding: 12px 14px;
      font-size: .9rem;
      line-height: 1.5;
      overflow-wrap: anywhere;
      margin-bottom: 20px;
    }

    .footer-note {
      margin-top: 28px;
      font-size: .8rem;
      color: var(--text-muted);
      line-height: 1.6;
      text-wrap: balance;
    }

    @media (max-width: 640px) {
      body {
        align-items: stretch;
        padding: 16px;
      }

      .login-box {
        margin: auto;
        min-height: auto;
        border-radius: 18px;
      }

      .logo {
        margin-bottom: 16px;
      }

      .logo img {
        width: min(100%, 160px);
      }

      .subtitle {
        margin-bottom: 28px;
      }

      .btn-google {
        padding: 14px 16px;
        font-size: 1rem;
      }

      .divider {
        margin: 24px 0;
      }
    }

    @media (max-width: 420px) {
      body {
        padding: 12px;
      }

      .login-box {
        padding: 24px 16px;
      }

      .features {
        gap: 12px;
      }
    }
  </style>
</head>
<body>

<button class="theme-btn" id="themeBtn" aria-label="Alternar tema claro/escuro">
  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
</button>

<div class="login-box">
  <div class="logo">
    <img class="logo-dark"  src="assets/img/baseref_dark.png"    alt="BaseRef" style="height:200px;width:auto;">
    <img class="logo-light" src="assets/img/baseref_light-2.png" alt="BaseRef" style="height:200px;width:auto;">
  </div>
  <p class="subtitle">Plataforma de apoio à pesquisa e gestão de referências bibliográficas</p>

  <?php if ($error): ?>
    <div class="error-msg">
      <i class="fa fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <a href="auth/google.php" class="btn-google">
    <!-- Google "G" SVG oficial -->
    <svg class="google-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
      <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
      <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
      <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/>
      <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
    </svg>
    Entrar com Google
  </a>

  <hr class="divider">

  <div class="features">
    <div class="feature-item">
      <i class="fa fa-shield-halved"></i>
      <span>Login seguro via conta Google — sem senha adicional</span>
    </div>
    <div class="feature-item">
      <i class="fa fa-users"></i>
      <span>Múltiplos pesquisadores podem acessar o mesmo projeto</span>
    </div>
    <div class="feature-item">
      <i class="fa fa-lock"></i>
      <span>Dados protegidos e sessão com expiração automática</span>
    </div>
  </div>

  <p class="footer-note">
    Ao entrar, você concorda que seus dados de perfil do Google
    (nome, e-mail e foto) serão armazenados neste sistema.
  </p>
</div>

<script>
document.getElementById('themeBtn').addEventListener('click', function () {
  var t = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
  document.documentElement.setAttribute('data-theme', t);
  try { localStorage.setItem('prisma_theme', t); } catch (e) {}
});
</script>

</body>
</html>
