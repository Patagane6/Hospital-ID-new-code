<?php
require_once __DIR__ . '/Includes/auth.php';

logout_user();

// Ensure the user returns to login page after logout
header('Location: login.php');
exit;
