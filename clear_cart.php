<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'store_auth.php';
store_require_login('cart.php', false);

unset($_SESSION['cart']);
header("Location: cart.php");
exit;
