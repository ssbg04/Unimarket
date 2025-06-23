<?php
require_once __DIR__ . '/../includes/auth_functions.php';

// Destroy the current session
destroySession();

// Set a success flash message
flashMessage('logout_success', 'You have been successfully logged out.');

// Redirect to the homepage with a success message
header("Location: /index.php");
exit();
