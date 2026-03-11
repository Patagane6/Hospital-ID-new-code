<?php
require_once __DIR__ . '/Includes/auth.php';

// Clear the session and return to login.
logout_user();

header('Location: login.php');
exit;
