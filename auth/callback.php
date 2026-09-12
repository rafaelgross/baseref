<?php
/**
 * PRISMA-SLR — auth/callback.php
 * Receives the authorization code from Google, exchanges it for an access token,
 * fetches the user's data, and creates/updates the record in the database.
 */
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';

startSecureSession();

// ─── 1. Check whether Google returned an error ──────────────────────────
if (isset($_GET['error'])) {
    $_SESSION['login_error'] = 'Acesso negado ou cancelado pelo usuário.';
    header('Location: /login.php');
    exit;
}

// ─── 2. Validate the CSRF state ───────────────────────────────────────────────────
$state = $_GET['state'] ?? '';
if (!validateOAuthState($state)) {
    $_SESSION['login_error'] = 'Erro de segurança (state inválido). Tente novamente.';
    header('Location: /login.php');
    exit;
}

// ─── 3. Exchange the code for an access token ───────────────────────────────────
$code = $_GET['code'] ?? '';
if (!$code) {
    $_SESSION['login_error'] = 'Código de autorização ausente. Tente novamente.';
    header('Location: /login.php');
    exit;
}

$tokenData = exchangeCodeForToken($code);
if (!$tokenData) {
    $_SESSION['login_error'] = 'Não foi possível obter o token de acesso. Verifique as credenciais OAuth.';
    header('Location: /login.php');
    exit;
}

// ─── 4. Fetch the user's profile data ──────────────────────────────────────
$userInfo = fetchGoogleUserInfo($tokenData['access_token']);
if (!$userInfo) {
    $_SESSION['login_error'] = 'Não foi possível obter os dados do perfil Google.';
    header('Location: /login.php');
    exit;
}

$googleId = $userInfo['sub']      ?? '';
$email    = $userInfo['email']    ?? '';
$name     = $userInfo['name']     ?? $email;
$picture  = normalizeProfilePictureUrl($userInfo['picture'] ?? '');

if (!$googleId || !$email) {
    $_SESSION['login_error'] = 'Dados do perfil Google incompletos.';
    header('Location: /login.php');
    exit;
}

// ─── 5. Create or update the user in the database ─────────────────────────
try {
    $pdo = getDB();

    // Upsert: insert if it doesn't exist, update last_login if it does
    $stmt = $pdo->prepare("
        INSERT INTO users (google_id, email, name, picture, role)
        VALUES (:google_id, :email, :name, :picture, 'user')
        ON DUPLICATE KEY UPDATE
            name       = VALUES(name),
            picture    = VALUES(picture),
            last_login = CURRENT_TIMESTAMP
    ");
    $stmt->execute([
        ':google_id' => $googleId,
        ':email'     => $email,
        ':name'      => $name,
        ':picture'   => $picture,
    ]);

    // Fetch the full user record (including role and id)
    $stmt = $pdo->prepare("SELECT * FROM users WHERE google_id = ?");
    $stmt->execute([$googleId]);
    $user = $stmt->fetch();

    if (!$user) throw new RuntimeException('Usuário não encontrado após upsert.');

    // Auto-assign legacy projects (without a user_id) to the first user who logs in
    // Ensures projects created before the auth system belong to the first researcher
    $totalUsers = (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($totalUsers === 1) {
        $pdo->prepare("UPDATE projects SET user_id = ? WHERE user_id IS NULL")
            ->execute([$user['id']]);
    }

} catch (Throwable $e) {
    $_SESSION['login_error'] = 'Erro ao salvar usuário no banco: ' . $e->getMessage();
    header('Location: /login.php');
    exit;
}

// ─── 6. Create the authenticated session ────────────────────────────────────────────
session_regenerate_id(true); // prevents session fixation

$_SESSION['user_id']      = $user['id'];
$_SESSION['user_email']   = $user['email'];
$_SESSION['user_name']    = $user['name'];
$_SESSION['user_picture'] = $user['picture'];
$_SESSION['user_role']    = $user['role'];
$_SESSION['logged_in_at'] = time();

// ─── 7. Redirect to the originally requested page ───────────────────
$redirect = $_SESSION['redirect_after_login'] ?? '/';
unset($_SESSION['redirect_after_login']);

header('Location: ' . $redirect);
exit;
