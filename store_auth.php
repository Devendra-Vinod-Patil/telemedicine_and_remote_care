<?php
// Store pages require a logged-in user.

function store_require_login(string $next = 'storeindex.php', bool $with_popup = true): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['user_id'])) {
        $loginUrl = 'login.php?next=' . urlencode($next);

        if (!$with_popup) {
            header('Location: ' . $loginUrl);
            exit;
        }

        header('Content-Type: text/html; charset=UTF-8');
        echo '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
        echo '<title>Login Required</title></head><body>';
        echo '<script>';
        echo 'alert("Please login to buy medicines.");';
        echo 'window.location.href=' . json_encode($loginUrl) . ';';
        echo '</script></body></html>';
        exit;
    }
}

