<?php
// Simple authentication helpers for the Hospital Visitor Registration System.
//
// This is intentionally designed to be easy to extend later (e.g. database-backed
// users, roles/permissions, password reset, etc.).

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// -----------------------------------------------------------------------------
// Configuration: single shared "front desk" account
// -----------------------------------------------------------------------------
// Change these values to update the shared login credentials.
// In a real system, you would store users in a database and use a stronger policy.
const AUTH_USERNAME = 'frontdesk';
// Password: 'frontdesk101'
const AUTH_PASSWORD_HASH = '$2y$10$X53Xpfc0IW1lcnxAv1ot6.AKOGgjKU46XkrE09xwlESkr.TCu0ZVS';

// -----------------------------------------------------------------------------
// Authentication helpers
// -----------------------------------------------------------------------------

/**
 * Verify the provided username/password against the shared account.
 *
 * Upgrade notes:
 * - To support multiple accounts, query a users table with (username, password_hash).
 * - Store password hashes using password_hash() and verify with password_verify().
 * - Add roles/permissions (e.g. `role` column) and check them in `require_login()`.
 */
function authenticate(string $username, string $password): bool
{
    if (trim($username) === '' || trim($password) === '') {
        return false;
    }

    if (hash_equals(AUTH_USERNAME, $username) === false) {
        return false;
    }

    return password_verify($password, AUTH_PASSWORD_HASH);
}

/**
 * Returns true if the user is currently logged in.
 */
function is_logged_in(): bool
{
    return isset($_SESSION['user']) && is_array($_SESSION['user']);
}

/**
 * Require a logged in user for normal pages.
 * Redirects to login.php if not authenticated.
 */
function require_login(): void
{
    if (!is_logged_in()) {
        // Ensure no partial output before redirect
        if (!headers_sent()) {
            header('Location: login.php');
        }
        exit;
    }
}

/**
 * Require login for API endpoints (JSON).
 * Responds with 401 + JSON error instead of redirect.
 */
function require_login_api(): void
{
    if (!is_logged_in()) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Authentication required']);
        exit;
    }
}

/**
 * Log the user in by storing session state.
 */
function login_user(string $username): void
{
    // In future, store user metadata (id, name, roles) here.
    $_SESSION['user'] = [
        'username' => $username,
        'logged_in_at' => time(),
    ];
}

/**
 * Clear the session and log the user out.
 */
function logout_user(): void
{
    // Clear session data and destroy session cookie.
    $_SESSION = [];

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

    session_destroy();
}
