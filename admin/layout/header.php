<?php
// Admin header should not start session or check access
// These checks are done in the individual admin files
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
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
        .sidebar-link i {
            margin-right: 20px;
            width: 18px;
            height: 18px;
        }
    </style>
</head>
<body>