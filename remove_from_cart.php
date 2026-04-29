<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'store_auth.php';
store_require_login('cart.php', false);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id > 0 && isset($_SESSION['cart']) && is_array($_SESSION['cart']) && isset($_SESSION['cart'][$id])) {
    unset($_SESSION['cart'][$id]);
}

header("Location: cart.php");
exit;
