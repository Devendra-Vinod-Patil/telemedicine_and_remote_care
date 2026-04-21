<?php
// logout.php - Secure logout functionality for Virtual-Chikitsa

// Start session
session_start();

// Regenerate session ID to prevent session fixation attacks
session_regenerate_id(true);

// Unset all session variables
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session completely
session_destroy();

// Clear any existing output buffers
if (ob_get_length()) {
    ob_end_clean();
}

// Redirect to index.php with logout success message
header("Location: index.php?logout=success");
exit();
?>
