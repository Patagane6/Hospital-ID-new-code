<?php
require_once __DIR__ . '/Includes/database.php';

// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

$add_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_visitor']) && $conn) {
    $full_name_raw = trim($_POST['full_name'] ?? '');
    if ($full_name_raw === '') {
        $add_error = 'Full name is required.';
    }

    // Contact number is optional; if provided, it must be exactly 11 digits.
    $contact_number_raw = $_POST['contact_number'] ?? '';
    $contact_number_digits = preg_replace('/\D+/', '', $contact_number_raw);
    if ($contact_number_digits !== '' && strlen($contact_number_digits) !== 11) {
        $add_error = $add_error ?: 'Contact number must be exactly 11 digits if provided.';
    }

    if ($add_error === '') {
        $full_name = $conn->real_escape_string($full_name_raw);
        $contact_number = $conn->real_escape_string($contact_number_digits);
        $valid_id = 'Not provided';

        $stmt = $conn->prepare("INSERT INTO visitor (full_name, contact_number, valid_id) VALUES (?, ?, ?)");
        $stmt->bind_param('sss', $full_name, $contact_number, $valid_id);

        if ($stmt->execute()) {
            $stmt->close();
            header('Location: visitor_registration.php?added=1');
            exit;
        }

        $add_error = $stmt->error;
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visitor Registration - Hospital Visitors ID Recording System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    <div class="header-content">
        <h1>🏥 Hospital Visitors ID Recording System</h1>
        <nav class="header-nav">
            <a href="visitor_registration.php" class="nav-link active">Visitor Registration</a>
        </nav>
    </div>
</header>

<div class="container">
    <div class="page-header">
        <h2>👥 Visitor Registration</h2>
        <p>Register your visit to the hospital</p>
    </div>

    <div class="card form-card">
        <h3>➕ Register New Visitor</h3>

        <form method="POST" action="visitor_registration.php" novalidate>
            <div class="form-grid">
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" name="full_name" id="full_name" placeholder="Enter full name" required>
                </div>

                <div class="form-group">
                    <label for="contact_number">Contact Number (Optional)</label>
                    <input
                        type="text"
                        name="contact_number"
                        id="contact_number"
                        placeholder="09XXXXXXXXX"
                        inputmode="numeric"
                        pattern="\d{11}"
                        maxlength="11"
                        minlength="11"
                        title="Enter exactly 11 digits if provided"
                    >
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" name="add_visitor" class="btn">Submit Registration</button>
                <button type="reset" class="btn secondary">Clear Form</button>
            </div>
        </form>

        <?php if (isset($_GET['added']) && $_GET['added'] === '1'): ?>
            <div id="flash-msg" class="alert alert-success">✅ Registration submitted successfully!</div>
        <?php endif; ?>

        <?php if (!empty($add_error)): ?>
            <div id="flash-msg" class="alert alert-error">❌ Error: <?php echo htmlspecialchars($add_error); ?></div>
        <?php endif; ?>

        <p style="margin-top: 16px; color: #6fa39e; font-size: 0.9rem;">Your registration will be reviewed by hospital staff.</p>
    </div>
</div>

<footer>
    &copy; <?php echo date('Y'); ?> Hospital Visitors ID Recording System • Visitor Registration
</footer>

<script>
setTimeout(function () {
    var el = document.getElementById('flash-msg');
    if (el) {
        el.style.transition = 'opacity 0.5s';
        el.style.opacity = '0';
        setTimeout(function () { el.remove(); }, 500);
    }
    if (window.history && window.history.replaceState) {
        var url = window.location.protocol + '//' + window.location.host + window.location.pathname;
        window.history.replaceState({}, document.title, url);
    }
}, 3000);
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var cn = document.getElementById('contact_number');
    if (!cn) return;

    cn.addEventListener('input', function () {
        this.value = this.value.replace(/\D/g, '').slice(0, 11);
    });

    cn.addEventListener('keydown', function (e) {
        var allowed = [8, 9, 13, 27, 37, 38, 39, 40, 46];
        if (allowed.indexOf(e.keyCode) !== -1) return;
        if (e.ctrlKey || e.metaKey) return;
        if ((e.keyCode >= 48 && e.keyCode <= 57) || (e.keyCode >= 96 && e.keyCode <= 105)) return;
        e.preventDefault();
    });
});
</script>

</body>
</html>
