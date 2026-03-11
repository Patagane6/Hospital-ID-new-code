<?php
// Simple session-based authentication helper for Hospital Visitors ID Recording System.
//
// This file is intentionally lightweight and stores a single shared credential in-code.
// It can be upgraded later to support a users table, multiple accounts, roles, etc.

// Start session if not already started.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ---------- Configuration (single shared front desk account) ----------
// NOTE: Update these values to change the credentials. For production, move to a users table.
const AUTH_CREDENTIALS = [
    // username => password hash
    'frontdesk' => '$2y$10$UFdIkkYgOK7pWqEe7yIo6OxF0W0Yb.Ro4PpKnsouC2rJMnx.8gR0e' // password: frontdesk101
];

// A chainable setting that can be used to show the current logged-in user.
// Can be used in templates to show who is logged in.
$current_user = $_SESSION['user'] ?? null;

/**
 * Check whether the current session is authenticated.
 *
 * @return bool
 */
function is_logged_in(): bool
{
    return isset($_SESSION['user']) && is_array($_SESSION['user']) && !empty($_SESSION['user']['username']);
}

/**
 * Authenticate credentials.
 *
 * Current implementation checks a hardcoded credentials map.
 * Future improvements can query a `users` table with proper password hashing, lockouts, roles, etc.
 *
 * @param string $username
 * @param string $password
 * @return bool
 */
function authenticate(string $username, string $password): bool
{
    $username = trim($username);
    if ($username === '') {
        return false;
    }

    if (!array_key_exists($username, AUTH_CREDENTIALS)) {
        return false;
    }

    $hash = AUTH_CREDENTIALS[$username];

    return password_verify($password, $hash);
}

/**
 * Enforce login for normal HTML pages. If not authenticated, redirect to login.
 *
 * @return void
 */
function require_login(): void
{
    if (is_logged_in()) {
        return;
    }

    // Redirect back to login, preserving the current page for after login.
    $current = $_SERVER['REQUEST_URI'] ?? '/';
    $loginUrl = 'login.php?redirect=' . urlencode($current);
    header('Location: ' . $loginUrl);
    exit;
}

/**
 * Enforce login for API/JSON endpoints. Returns a JSON 401 error if unauthenticated.
 *
 * @return void
 */
function require_login_api(): void
{
    if (is_logged_in()) {
        return;
    }

    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

/**
 * Log the user in for the current session.
 *
 * @param string $username
 * @return void
 */
function login_user(string $username): void
{
    $_SESSION['user'] = [
        'username' => $username,
        // future: add roles, display name, user ID, etc.
    ];
}

/**
 * Clears the current session and logs out.
 */
function logout_user(): void
{
    // Clear session data
    $_SESSION = [];

    // Destroy session cookie if present
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    // Destroy the session
    session_destroy();
}
