<?php
/**
 * PRISMA-SLR — auth/google.php
 * Starts the OAuth 2.0 authentication flow with Google.
 * Redirects the user to Google's consent screen.
 */
require_once __DIR__ . '/../config/auth.php';

// Generates and saves the CSRF state
$state = generateOAuthState();

// Builds the authorization URL
$params = http_build_query([
    'client_id'     => GOOGLE_CLIENT_ID,
    'redirect_uri'  => GOOGLE_REDIRECT_URI,
    'response_type' => 'code',
    'scope'         => GOOGLE_SCOPES,
    'state'         => $state,
    'access_type'   => 'online',
    'prompt'        => 'select_account',  // allows switching accounts
]);

header('Location: ' . GOOGLE_AUTH_URL . '?' . $params);
exit;
