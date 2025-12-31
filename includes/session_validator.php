<?php
// includes/session_validator.php
// Optional: Include this file in protected pages for additional session security

// Check if session is valid
if (isset($_SESSION['user_id'])) {
    // Optional: Session timeout (30 minutes of inactivity)
    $inactive_timeout = 1800; // 30 minutes
    if (isset($_SESSION['login_time'])) {
        $elapsed_time = time() - $_SESSION['login_time'];
        if ($elapsed_time > $inactive_timeout) {
            // Session expired
            session_destroy();
            header('Location: ../auth/login.php?timeout=1');
            exit;
        }
    }

    // Optional: IP address validation (warning: can cause issues with mobile networks)
    // Uncomment if you want strict IP checking
    /*
    if (isset($_SESSION['ip_address'])) {
        $current_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        if ($_SESSION['ip_address'] !== $current_ip) {
            // IP mismatch - possible session hijacking
            session_destroy();
            header('Location: ../auth/login.php?error=session_invalid');
            exit;
        }
    }
    */
}
?>