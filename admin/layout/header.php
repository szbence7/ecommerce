<?php
session_start();
require_once __DIR__ . '/../../includes/db.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header('Location: /login.php');
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        #sidebar {
            min-height: 100vh;
            background: #f8f9fa;
            border-right: 1px solid #dee2e6;
        }
        .sidebar-link {
            padding: 10px 15px;
            display: block;
            color: #333;
            text-decoration: none;
        }
        .sidebar-link:hover, .sidebar-link.active {
            background: #e9ecef;
        }
        #content {
            padding: 20px;
        }
    </style>
</head>
<body> 