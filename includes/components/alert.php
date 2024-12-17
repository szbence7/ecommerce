<?php
function show_alert($message, $type = 'success', $timeout = 5) {
    // Define alert types and their corresponding Bootstrap classes
    $alert_types = [
        'success' => 'alert-success',
        'error' => 'alert-danger',
        'warning' => 'alert-warning',
        'info' => 'alert-info'
    ];

    // Get the Bootstrap class for the alert type, default to info if type not found
    $alert_class = $alert_types[$type] ?? 'alert-info';

    // Generate a unique ID for this alert
    $alert_id = 'alert_' . uniqid();

    // Return the alert HTML with circular progress bar
    return sprintf('
        <div class="alert %s alert-dismissible fade show" role="alert" id="%s">
            %s
            <div class="circular-progress">
                <svg class="progress-ring" width="24" height="24">
                    <circle class="progress-ring__circle" stroke="%s" stroke-width="2" fill="transparent" r="10" cx="12" cy="12"/>
                </svg>
                <span class="progress-text">%d</span>
            </div>
        </div>
        <style>
            .alert {
                position: relative;
            }
            .circular-progress {
                position: absolute;
                right: 1rem;
                top: 50%%;
                transform: translateY(-50%%);
                width: 24px;
                height: 24px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .progress-ring__circle {
                transition: stroke-dashoffset 0.1s;
                transform: rotate(-90deg);
                transform-origin: 50%% 50%%;
                stroke-dasharray: 62.83;  /* 2 * π * 10 (radius) */
            }
            .progress-text {
                position: absolute;
                font-size: 10px;
                color: %s;
            }
        </style>
        <script>
            (function() {
                const alert = document.getElementById("%s");
                const circle = alert.querySelector(".progress-ring__circle");
                const text = alert.querySelector(".progress-text");
                const radius = circle.r.baseVal.value;
                const circumference = radius * 2 * Math.PI;
                
                circle.style.strokeDasharray = `${circumference} ${circumference}`;
                
                let timeLeft = %d;
                const duration = %d;
                
                function setProgress(percent) {
                    const offset = circumference - (percent / 100 * circumference);
                    circle.style.strokeDashoffset = offset;
                }
                
                const timer = setInterval(() => {
                    if (timeLeft <= 0) {
                        clearInterval(timer);
                        alert.remove();
                        return;
                    }
                    
                    const progress = (timeLeft / duration) * 100;
                    setProgress(progress);
                    text.textContent = timeLeft;
                    timeLeft--;
                }, 1000);
                
                setProgress(100);
            })();
        </script>
    ', 
    $alert_class, 
    $alert_id,
    $message,
    $type === 'success' ? '#155724' : ($type === 'error' ? '#721c24' : ($type === 'warning' ? '#856404' : '#0c5460')), // Circle color based on alert type
    $timeout,
    $type === 'success' ? '#155724' : ($type === 'error' ? '#721c24' : ($type === 'warning' ? '#856404' : '#0c5460')), // Text color based on alert type
    $alert_id,
    $timeout,
    $timeout
    );
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
