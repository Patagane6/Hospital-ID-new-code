<?php
require_once __DIR__ . '/Includes/auth.php';

// If already logged in, go straight to dashboard
if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$login_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (authenticate($username, $password)) {
        login_user($username);
        header('Location: index.php');
        exit;
    }

    $login_error = 'Invalid username or password. Please try again.';
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
        <nav class="header-nav">
            <a href="login.php" class="nav-link active">Login</a>
        </nav>
    </div>
</header>

<div class="login-wrapper">
    <div class="container" style="max-width: 520px;">
        <div class="page-header">
            <h2>Sign in to continue</h2>
            <p>Only authorized staff can access visitor registration.</p>
        </div>

        <div class="card form-card login-card">
            <h3>Front Desk Login</h3>

        <?php if ($login_error): ?>
            <div class="alert alert-error">❌ <?php echo htmlspecialchars($login_error); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <input id="username" name="username" type="text" autocomplete="username" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn">Log In</button>
            </div>
        </form>

        <p style="margin-top: 16px; color: #6fa39e; font-size: 0.9rem;">Use the front desk account to access the system.</p>
    </div>
    </div>
</div>

</body>
</html>
