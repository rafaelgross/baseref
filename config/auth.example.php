<?php
/**
 * BaseRef — config/auth.php (copy this file to config/auth.php and fill in the values)
 * Google OAuth 2.0 configuration and session helpers.
 *
 * Step-by-step setup of the Google credentials: see "Google sign-in (OAuth 2.0)"
 * in README.md. In short:
 * 1. https://console.cloud.google.com/ → create (or select) a project
 * 2. Configure the OAuth consent screen (scopes: openid, email, profile)
 * 3. Credentials → Create credentials → OAuth client ID → "Web application"
 * 4. Authorized redirect URI = the exact URL of auth/callback.php, e.g.
 *      http://localhost/auth/callback.php           (local, XAMPP)
 *      http://localhost:8888/auth/callback.php      (local, MAMP default port)
 *      https://your-domain.org/auth/callback.php    (production)
 * 5. Paste the Client ID and Client secret below.
 *
 * BaseRef must be served from the web server's document root (not a sub-folder):
 * redirects such as /login.php are absolute paths.
 */

// ─── Google OAuth credentials ─────────────────────────────────────────────────
define('GOOGLE_CLIENT_ID',     getenv('GOOGLE_CLIENT_ID') ?: 'PASTE_YOUR_CLIENT_ID_HERE.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET') ?: 'PASTE_YOUR_CLIENT_SECRET_HERE');

// Redirect URI — must be identical (scheme, host, port, path) to the one
// registered in the Google Cloud Console
define('GOOGLE_REDIRECT_URI',  getenv('GOOGLE_REDIRECT_URI') ?: 'http://localhost/auth/callback.php');

// Scopes solicitados (email + perfil básico)
define('GOOGLE_SCOPES', getenv('GOOGLE_SCOPES') ?: 'openid email profile');

// URLs do Google OAuth 2.0
define('GOOGLE_AUTH_URL',  'https://accounts.google.com/o/oauth2/v2/auth');
define('GOOGLE_TOKEN_URL', 'https://oauth2.googleapis.com/token');
define('GOOGLE_USER_URL',  'https://www.googleapis.com/oauth2/v3/userinfo');

// Duração da sessão: 8 horas (ou valor definido por variável de ambiente em segundos)
define('SESSION_LIFETIME', getenv('SESSION_LIFETIME') ? (int) getenv('SESSION_LIFETIME') : (8 * 3600));

// ─── Inicializa sessão segura ──────────────────────────────────────────────────
function startSecureSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path'     => '/',
            'secure'   => false, // set to true when the site is served over HTTPS (production)
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

// ─── Verifica se o usuário está autenticado ────────────────────────────────────
function isLoggedIn(): bool
{
    startSecureSession();
    if (empty($_SESSION['user_id']) || empty($_SESSION['logged_in_at'])) return false;
    // Expira sessão após SESSION_LIFETIME segundos
    if (time() - $_SESSION['logged_in_at'] > SESSION_LIFETIME) {
        session_destroy();
        return false;
    }
    return true;
}

// ─── Retorna dados do usuário logado ──────────────────────────────────────────
function currentUser(): ?array
{
    if (!isLoggedIn()) return null;
    return [
        'id'      => $_SESSION['user_id'],
        'email'   => $_SESSION['user_email']   ?? '',
        'name'    => $_SESSION['user_name']    ?? '',
        'picture' => $_SESSION['user_picture'] ?? '',
        'role'    => $_SESSION['user_role']    ?? 'user',
    ];
}

// ─── Redireciona para login se não autenticado ────────────────────────────────
function requireLogin(): void
{
    if (!isLoggedIn()) {
        // Salva URL desejada para redirecionar após login
        startSecureSession();
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '/';
        header('Location: /login.php');
        exit;
    }
}

// ─── Gera state CSRF para o OAuth ────────────────────────────────────────────
function generateOAuthState(): string
{
    startSecureSession();
    $state = bin2hex(random_bytes(16));
    $_SESSION['oauth_state'] = $state;
    return $state;
}

// ─── Valida state CSRF retornado pelo Google ─────────────────────────────────
function validateOAuthState(string $state): bool
{
    startSecureSession();
    $stored = $_SESSION['oauth_state'] ?? '';
    unset($_SESSION['oauth_state']);
    return $stored !== '' && hash_equals($stored, $state);
}

// ─── Troca código de autorização por token de acesso via cURL ────────────────
function exchangeCodeForToken(string $code): ?array
{
    $postData = http_build_query([
        'code'          => $code,
        'client_id'     => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri'  => GOOGLE_REDIRECT_URI,
        'grant_type'    => 'authorization_code',
    ]);

    $ch = curl_init(GOOGLE_TOKEN_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $postData,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $response = curl_exec($ch);
    $err      = curl_error($ch);
    curl_close($ch);

    if ($err || !$response) return null;
    $data = json_decode($response, true);
    return isset($data['access_token']) ? $data : null;
}

// ─── Busca dados do usuário com o access_token ───────────────────────────────
function fetchGoogleUserInfo(string $accessToken): ?array
{
    $ch = curl_init(GOOGLE_USER_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $accessToken],
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $response = curl_exec($ch);
    $err      = curl_error($ch);
    curl_close($ch);

    if ($err || !$response) return null;
    $data = json_decode($response, true);
    return isset($data['email']) ? $data : null;
}

// ─── Impersonation (admin views/edits as another user) ────────────────────────
function startImpersonation(int $targetUserId): bool
{
    startSecureSession();
    $stmt = getDB()->prepare('SELECT id, email, name, picture, role FROM users WHERE id = ?');
    $stmt->execute([$targetUserId]);
    $target = $stmt->fetch();
    if (!$target) return false;

    // Stores the admin's real identity so they can return to it later
    $_SESSION['impersonator'] = [
        'id'      => $_SESSION['user_id'],
        'email'   => $_SESSION['user_email']   ?? '',
        'name'    => $_SESSION['user_name']    ?? '',
        'picture' => $_SESSION['user_picture'] ?? '',
        'role'    => $_SESSION['user_role']    ?? 'user',
    ];

    $_SESSION['user_id']      = (int) $target['id'];
    $_SESSION['user_email']   = $target['email'];
    $_SESSION['user_name']    = $target['name'];
    $_SESSION['user_picture'] = $target['picture'];
    $_SESSION['user_role']    = $target['role'];

    error_log(sprintf(
        '[IMPERSONATE] admin %s (#%d) started viewing as %s (#%d)',
        $_SESSION['impersonator']['email'], $_SESSION['impersonator']['id'],
        $target['email'], $target['id']
    ));

    return true;
}

function stopImpersonation(): bool
{
    startSecureSession();
    if (empty($_SESSION['impersonator'])) return false;

    $orig = $_SESSION['impersonator'];
    $_SESSION['user_id']      = $orig['id'];
    $_SESSION['user_email']   = $orig['email'];
    $_SESSION['user_name']    = $orig['name'];
    $_SESSION['user_picture'] = $orig['picture'];
    $_SESSION['user_role']    = $orig['role'];
    unset($_SESSION['impersonator']);

    return true;
}

function isImpersonating(): bool
{
    startSecureSession();
    return !empty($_SESSION['impersonator']);
}

function impersonatorInfo(): ?array
{
    startSecureSession();
    return $_SESSION['impersonator'] ?? null;
}

function normalizeProfilePictureUrl(?string $picture, int $maxLength = 500): string
{
    $picture = trim((string) $picture);
    if ($picture === '') return '';

    $parts = parse_url($picture);
    if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
        return '';
    }

    $normalized = $parts['scheme'] . '://' . $parts['host'];
    if (!empty($parts['port'])) {
        $normalized .= ':' . $parts['port'];
    }
    $normalized .= $parts['path'] ?? '';

    if (strlen($normalized) > $maxLength) {
        return '';
    }

    return filter_var($normalized, FILTER_VALIDATE_URL) ? $normalized : '';
}
