<?php
session_start();

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header('Location: login.php');
    exit();
}

require_once 'store_auth.php';
store_require_login('storeindex.php', false);

include 'database.php';

function ensure_column(mysqli $conn, string $table, string $column, string $definition): bool
{
    $table_safe = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $column_safe = preg_replace('/[^a-zA-Z0-9_]/', '', $column);

    $result = $conn->query("SHOW COLUMNS FROM `{$table_safe}` LIKE '{$column_safe}'");
    if ($result && $result->num_rows > 0) {
        return true;
    }

    return (bool) $conn->query("ALTER TABLE `{$table_safe}` ADD COLUMN `{$column_safe}` {$definition}");
}

ensure_column($conn, 'orders', 'user_id', 'INT NULL AFTER `id`');
ensure_column($conn, 'orders', 'user_role', "VARCHAR(20) NULL AFTER `user_id`");

$order_id = isset($_GET['order_id']) ? (int) $_GET['order_id'] : 0;
if ($order_id <= 0) {
    die('Invalid order.');
}

$user_id = (int) $_SESSION['user_id'];
$role = (string) $_SESSION['role'];

$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ? AND user_role = ? LIMIT 1");
$stmt->bind_param('iis', $order_id, $user_id, $role);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    die('Bill not found.');
}

$items = [];
$stmt = $conn->prepare("SELECT product_name, quantity, price, subtotal FROM order_items WHERE order_id = ? ORDER BY id ASC");
$stmt->bind_param('i', $order_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $items[] = $row;
}
$stmt->close();

$page_title = 'Bill';
include 'header.php';
?>
<style>
    @media print {
        .no-print { display: none !important; }
        body { background: #fff !important; }
    }
</style>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center gap-2 mb-3 no-print">
        <div>
            <h1 class="fw-bold mb-0">Invoice / Bill</h1>
            <div class="text-muted">Order #<?= (int) $order_id ?></div>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-primary" href="patient_dashboard.php">Dashboard</a>
            <button class="btn btn-primary" type="button" onclick="window.print()">Download / Print</button>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4 p-md-5">
            <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-4">
                <div>
                    <h4 class="fw-bold mb-1">Virtual-Chikitsa Pharmacy</h4>
                    <div class="text-muted">Medicine order invoice</div>
                </div>
                <div class="text-md-end">
                    <div><span class="text-muted">Date:</span> <?= htmlspecialchars(date('d M Y, h:i A', strtotime((string)$order['created_at']))) ?></div>
                    <div><span class="text-muted">Status:</span> <?= htmlspecialchars((string)$order['status']) ?></div>
                    <?php if (!empty($order['payment_id'])): ?>
                        <div><span class="text-muted">Payment ID:</span> <?= htmlspecialchars((string)$order['payment_id']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded-3">
                        <div class="fw-bold mb-1">Billed To</div>
                        <div><?= htmlspecialchars((string)$order['name']) ?></div>
                        <div class="text-muted small"><?= nl2br(htmlspecialchars((string)$order['address'])) ?></div>
                        <div class="text-muted small">Phone: <?= htmlspecialchars((string)$order['phone']) ?></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded-3">
                        <div class="fw-bold mb-1">Delivery Notes</div>
                        <div class="text-muted small"><?= $order['details'] ? nl2br(htmlspecialchars((string)$order['details'])) : '—' ?></div>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Item</th>
                            <th class="text-end">Price</th>
                            <th class="text-end">Qty</th>
                            <th class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($items) > 0): ?>
                            <?php foreach ($items as $it): ?>
                                <tr>
                                    <td><?= htmlspecialchars((string)$it['product_name']) ?></td>
                                    <td class="text-end">₹<?= number_format((float)$it['price'], 2) ?></td>
                                    <td class="text-end"><?= (int)$it['quantity'] ?></td>
                                    <td class="text-end fw-bold">₹<?= number_format((float)$it['subtotal'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center text-muted">No items found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3" class="text-end">Total</th>
                            <th class="text-end">₹<?= number_format((float)$order['amount'], 2) ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>

