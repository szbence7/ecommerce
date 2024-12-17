<?php
function show_alert($message, $type = 'success') {
    // Define alert types and their corresponding Bootstrap classes
    $alert_types = [
        'success' => 'alert-success',
        'error' => 'alert-danger',
        'warning' => 'alert-warning',
        'info' => 'alert-info'
    ];

    // Get the Bootstrap class for the alert type, default to info if type not found
    $alert_class = $alert_types[$type] ?? 'alert-info';

    // Return the alert HTML
    return sprintf('
        <div class="alert %s alert-dismissible fade show" role="alert">
            %s
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    ', $alert_class, $message);
}

// Helper function to set a flash message in the session
function set_alert($message, $type = 'success') {
    if (!isset($_SESSION)) {
        session_start();
    }
    $_SESSION['alert'] = [
        'message' => $message,
        'type' => $type
    ];
}

// Helper function to display and clear the flash message
function display_alert() {
    if (!isset($_SESSION)) {
        session_start();
    }
    
    if (isset($_SESSION['alert'])) {
        $alert = $_SESSION['alert'];
        unset($_SESSION['alert']); // Clear the message after displaying
        echo show_alert($alert['message'], $alert['type']);
    }
}
?>
