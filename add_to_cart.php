<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'store_auth.php';
store_require_login('storeindex.php', false);

include 'database.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header("Location: storeindex.php");
    exit;
}

$stmt = $conn->prepare("SELECT id FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->store_result();
$exists = $stmt->num_rows > 0;
$stmt->close();

if ($exists) {
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    $_SESSION['cart'][$id] = (int)($_SESSION['cart'][$id] ?? 0) + 1;
}

header("Location: cart.php");
exit;
