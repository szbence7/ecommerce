<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Update session with latest user data from database
updateUserSession();

// Check if user is logged in and has admin/manager role (1 or 2)
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], [1, 2])) {
    header('Location: /ecommerce/login.php?error=unauthorized');
    exit();
}
?>
