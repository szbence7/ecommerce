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
                transition: opacity 0.5s ease-out;
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
                transition: stroke-dashoffset 0.1s linear;
                transform: rotate(-90deg);
                transform-origin: 50%% 50%%;
                stroke-dasharray: 62.83;  /* 2 * π * 10 (radius) */
            }
            .progress-text {
                position: absolute;
                font-size: 10px;
                color: %s;
            }
            @keyframes fadeOut {
                from { opacity: 1; }
                to { opacity: 0; }
            }
            .alert-fade-out {
                animation: fadeOut 0.5s ease-out forwards;
            }
        </style>
        <script>
            // Azonnal indítjuk a számlálót
            document.getElementById("%s").setAttribute("data-start-time", Date.now());
            
            // Ez a függvény kezeli a visszaszámlálást
            function startCountdown(alertId, duration) {
                const alert = document.getElementById(alertId);
                if (!alert) return;

                const circle = alert.querySelector(".progress-ring__circle");
                const text = alert.querySelector(".progress-text");
                const radius = circle.r.baseVal.value;
                const circumference = radius * 2 * Math.PI;
                const startTime = parseInt(alert.getAttribute("data-start-time"));
                const endTime = startTime + (duration * 1000);
                
                circle.style.strokeDasharray = `${circumference} ${circumference}`;
                
                function updateProgress() {
                    const now = Date.now();
                    const timeLeft = Math.max(0, (endTime - now) / 1000);
                    
                    if (timeLeft <= 0) {
                        // Add fade out class
                        alert.classList.add("alert-fade-out");
                        // Remove after animation completes
                        setTimeout(() => alert.remove(), 500);
                        return;
                    }
                    
                    const progress = (timeLeft / duration) * 100;
                    const offset = circumference - (progress / 100 * circumference);
                    circle.style.strokeDashoffset = offset;
                    text.textContent = Math.ceil(timeLeft);
                    
                    requestAnimationFrame(updateProgress);
                }
                
                requestAnimationFrame(updateProgress);
            }

            // Azonnal indítjuk a visszaszámlálást
            startCountdown("%s", %d);
        </script>
    ', 
    $alert_class, 
    $alert_id,
    $message,
    $type === 'success' ? '#155724' : ($type === 'error' ? '#721c24' : ($type === 'warning' ? '#856404' : '#0c5460')), // Circle color based on alert type
    $timeout,
    $type === 'success' ? '#155724' : ($type === 'error' ? '#721c24' : ($type === 'warning' ? '#856404' : '#0c5460')), // Text color based on alert type
    $alert_id,
    $alert_id,
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
