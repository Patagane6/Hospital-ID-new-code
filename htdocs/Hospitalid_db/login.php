<?php
require_once __DIR__ . '/Includes/auth.php';
require_once __DIR__ . '/Includes/database.php';

// If already logged in, redirect to the main dashboard.
if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$username = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please provide both username and password.';
    } elseif (authenticate($username, $password)) {
        login_user($username);

        // Redirect back to the original page if supplied (preserved via hidden field)
        $redirect = $_POST['redirect'] ?? $_GET['redirect'] ?? 'index.php';
        $redirect = str_replace(["\r", "\n", "\0"], '', $redirect);

        // Prevent open redirect: reject full URLs and keep only local paths
        $parsed = parse_url($redirect);
        if (!empty($parsed['scheme']) || !empty($parsed['host'])) {
            $redirect = 'index.php';
        }

        header('Location: ' . $redirect);
        exit;
    } else {
        $error = 'Invalid credentials. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Hospital Visitors ID Recording System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    <div class="header-content">
        <h1>🏥 Hospital Visitors ID Recording System</h1>
    </div>
</header>

<div class="container" style="max-width: 480px; padding-top: 48px;">
    <div class="card form-card" style="padding: 32px;">
        <h3>🔐 Staff Login</h3>
        <p style="color:#6fa39e; margin-bottom: 18px;">Please sign in to access visitor registration.</p>

        <?php if ($error !== ''): ?>
            <div id="flash-msg" class="alert alert-error" style="margin-bottom:16px;">❌ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($_GET['redirect'] ?? ''); ?>">
            <div class="form-grid">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($username); ?>" placeholder="Enter username" required autofocus>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" placeholder="Enter password" required>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn">Log In</button>
                <button type="reset" class="btn secondary">Clear</button>
            </div>
        </form>

        <div style="margin-top: 18px; font-size: 0.85rem; color: #8ca2a0;">
            Use the front desk account to sign in.
        </div>
    </div>
</div>

<footer style="margin-top: 40px; text-align: center; color:#8ca2a0;">
    &copy; <?php echo date("Y"); ?> Hospital Visitors ID Recording System
</footer>

<script>
// Hide the login error message after a short delay
setTimeout(function() {
    var el = document.getElementById('flash-msg');
    if (!el) return;
    el.style.transition = 'opacity 0.5s';
    el.style.opacity = '0';
    setTimeout(function() { el.remove(); }, 500);
}, 3000);
</script>

</body>
</html>
