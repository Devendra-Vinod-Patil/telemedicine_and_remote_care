<?php
$next = basename(__FILE__);
require_once 'store_auth.php';
store_require_login($next, true);

$page_title = 'Order Confirmation';
include 'header.php';
include 'database.php';

$cart_items = (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) ? $_SESSION['cart'] : [];

function render_message(string $title, string $body, string $type = 'success'): void {
    $alert = $type === 'success' ? 'alert-success' : 'alert-danger';
    echo '<div class="container py-5"><div class="row justify-content-center"><div class="col-lg-7">';
    echo '<div class="card border-0 shadow-sm"><div class="card-body p-4 p-md-5">';
    echo '<div class="alert ' . $alert . ' mb-4"><strong>' . htmlspecialchars($title) . '</strong><br>' . htmlspecialchars($body) . '</div>';
    echo '<div class="d-flex gap-2 flex-wrap">';
    echo '<a class="btn btn-primary" href="storeindex.php">Back to store</a>';
    echo '<a class="btn btn-outline-primary" href="cart.php">View cart</a>';
    echo '</div></div></div></div></div></div>';
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    render_message('Invalid request', 'Please place your order from the delivery page.', 'error');
    include 'footer.php';
    exit;
}

$name = trim((string)($_POST['name'] ?? ''));
$address = trim((string)($_POST['address'] ?? ''));
$phone = trim((string)($_POST['phone'] ?? ''));
$details = trim((string)($_POST['details'] ?? ''));
$payment_id = trim((string)($_POST['payment_id'] ?? ''));
$amount = (float)($_POST['amount'] ?? 0);

if ($name === '' || $address === '' || $phone === '' || $amount <= 0 || empty($cart_items)) {
    render_message('Missing details', 'Please fill delivery details and make sure your cart is not empty.', 'error');
    include 'footer.php';
    exit;
}

if (!preg_match('/^[0-9]{10}$/', $phone)) {
    render_message('Invalid phone', 'Please enter a valid 10-digit phone number.', 'error');
    include 'footer.php';
    exit;
}

$status = $payment_id !== '' ? 'Paid' : 'Pending';

if (!empty($cart_items)) {
    $ids_csv = implode(',', array_map('intval', array_keys($cart_items)));
} else {
    render_message('Cart empty', 'Please add medicines to your cart before ordering.', 'error');
    include 'footer.php';
    exit;
}

$profile_table = null;
$profile_user_id = null;
if (isset($_SESSION['user_id'], $_SESSION['role']) && in_array($_SESSION['role'], ['patient', 'doctor'], true)) {
    $profile_user_id = (int) $_SESSION['user_id'];
    $profile_table = $_SESSION['role'] === 'doctor' ? 'doctors' : 'patients';

    $result = $conn->query("SHOW COLUMNS FROM `{$profile_table}` LIKE 'address'");
    if (!$result || $result->num_rows === 0) {
        $conn->query("ALTER TABLE `{$profile_table}` ADD COLUMN `address` TEXT NULL AFTER `phone`");
    }
}

// ensure order ownership fields exist
$ordersCols = $conn->query("SHOW COLUMNS FROM `orders` LIKE 'user_id'");
if (!$ordersCols || $ordersCols->num_rows === 0) {
    $conn->query("ALTER TABLE `orders` ADD COLUMN `user_id` INT NULL AFTER `id`");
}
$ordersCols = $conn->query("SHOW COLUMNS FROM `orders` LIKE 'user_role'");
if (!$ordersCols || $ordersCols->num_rows === 0) {
    $conn->query("ALTER TABLE `orders` ADD COLUMN `user_role` VARCHAR(20) NULL AFTER `user_id`");
}

$conn->begin_transaction();
try {
    $orderStmt = $conn->prepare("
        INSERT INTO orders (user_id, user_role, name, address, phone, details, payment_id, amount, status)
        VALUES (?, ?, ?, ?, ?, ?, NULLIF(?, ''), ?, ?)
    ");
    $user_role = (string)($_SESSION['role'] ?? '');
    $user_id_for_order = (int)($_SESSION['user_id'] ?? 0);
    $orderStmt->bind_param("issssssds", $user_id_for_order, $user_role, $name, $address, $phone, $details, $payment_id, $amount, $status);
    $orderStmt->execute();
    $order_id = (int)$orderStmt->insert_id;
    $orderStmt->close();

    $productMap = [];
    $productRes = $conn->query("SELECT id, name, price FROM products WHERE id IN ($ids_csv)");
    if ($productRes) {
        while ($row = $productRes->fetch_assoc()) {
            $productMap[(int)$row['id']] = $row;
        }
    }

    $itemStmt = $conn->prepare("
        INSERT INTO order_items (order_id, product_id, product_name, quantity, price, subtotal, user_name)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($cart_items as $pid => $qty) {
        $pid = (int)$pid;
        $qty = (int)$qty;
        if ($qty <= 0 || !isset($productMap[$pid])) continue;

        $pname = (string)$productMap[$pid]['name'];
        $pprice = (float)$productMap[$pid]['price'];
        $subtotal = $pprice * $qty;

        $itemStmt->bind_param("iisidds", $order_id, $pid, $pname, $qty, $pprice, $subtotal, $name);
        $itemStmt->execute();
    }
    $itemStmt->close();

    if ($profile_table && $profile_user_id && $address !== '') {
        $checkStmt = $conn->prepare("SELECT address FROM `{$profile_table}` WHERE id = ? LIMIT 1");
        if ($checkStmt) {
            $checkStmt->bind_param('i', $profile_user_id);
            $checkStmt->execute();
            $res = $checkStmt->get_result();
            $current_address = '';
            if ($res && $row = $res->fetch_assoc()) {
                $current_address = trim((string)($row['address'] ?? ''));
            }
            $checkStmt->close();

            if ($current_address === '') {
                $updStmt = $conn->prepare("UPDATE `{$profile_table}` SET address = ? WHERE id = ?");
                if ($updStmt) {
                    $updStmt->bind_param('si', $address, $profile_user_id);
                    $updStmt->execute();
                    $updStmt->close();
                }
            }
        }
    }

    $conn->commit();

    unset($_SESSION['cart']);

    echo '<div class="container py-5"><div class="row justify-content-center"><div class="col-lg-7">';
    echo '<div class="card border-0 shadow-sm"><div class="card-body p-4 p-md-5">';
    echo '<div class="alert alert-success mb-4"><strong>Order placed</strong><br>Thank you! Your order has been placed successfully.</div>';
    echo '<div class="d-flex gap-2 flex-wrap">';
    echo '<a class="btn btn-primary" href="storeindex.php">Back to store</a>';
    echo '<a class="btn btn-outline-primary" href="bill.php?order_id=' . (int)$order_id . '">Download bill</a>';
    echo '<a class="btn btn-outline-primary" href="patient_dashboard.php">Dashboard</a>';
    echo '</div></div></div></div></div></div>';
} catch (Throwable $e) {
    $conn->rollback();
    render_message('Order failed', 'Database error while placing the order.', 'error');
}

include 'footer.php';
