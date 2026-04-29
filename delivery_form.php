<?php
$next = basename(__FILE__);
require_once 'store_auth.php';
store_require_login($next, true);

$page_title = 'Delivery Details';
include 'header.php';
include 'database.php';
include 'store_config.php';

$prefill_name = '';
$prefill_phone = '';
$prefill_address = '';

if (isset($_SESSION['user_id'], $_SESSION['role']) && in_array($_SESSION['role'], ['patient', 'doctor'], true)) {
    $user_id = (int) $_SESSION['user_id'];
    $role = (string) $_SESSION['role'];
    $table = $role === 'doctor' ? 'doctors' : 'patients';

    $result = $conn->query("SHOW COLUMNS FROM `{$table}` LIKE 'address'");
    if (!$result || $result->num_rows === 0) {
        $conn->query("ALTER TABLE `{$table}` ADD COLUMN `address` TEXT NULL AFTER `phone`");
    }

    $stmt = $conn->prepare("SELECT full_name, phone, address FROM `{$table}` WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $row = $res->fetch_assoc()) {
            $prefill_name = (string)($row['full_name'] ?? '');
            $prefill_phone = (string)($row['phone'] ?? '');
            $prefill_address = (string)($row['address'] ?? '');
        }
        $stmt->close();
    }
}

$cart_items = (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) ? $_SESSION['cart'] : [];
$total = 0.0;

if (!empty($cart_items)) {
    $ids = implode(',', array_map('intval', array_keys($cart_items)));
    $res = $conn->query("SELECT id, price FROM products WHERE id IN ($ids)");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $pid = (int)$row['id'];
            $qty = (int)($cart_items[$pid] ?? 0);
            $total += ((float)$row['price']) * $qty;
        }
    }
}

if ($total <= 0) {
    header("Location: cart.php");
    exit;
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4 p-md-5">
                    <h2 class="fw-bold mb-1">Delivery details</h2>
                    <p class="text-muted mb-4">Total payable: <span class="fw-bold text-primary">₹<?php echo number_format((float)$total, 2); ?></span></p>

                    <form id="deliveryForm" method="post" action="save_delivery.php" class="vstack gap-3">
                        <div>
                            <label class="form-label">Full name</label>
                            <input class="form-control" name="name" required value="<?php echo htmlspecialchars($prefill_name); ?>">
                        </div>
                        <div>
                            <label class="form-label">Full address</label>
                            <input class="form-control" name="address" required value="<?php echo htmlspecialchars($prefill_address); ?>">
                        </div>
                        <div>
                            <label class="form-label">Phone</label>
                            <input class="form-control" name="phone" required pattern="[0-9]{10}" placeholder="10-digit number" value="<?php echo htmlspecialchars($prefill_phone); ?>">
                        </div>
                        <div>
                            <label class="form-label">Delivery instructions (optional)</label>
                            <textarea class="form-control" name="details" rows="3"></textarea>
                        </div>

                        <input type="hidden" name="amount" value="<?php echo htmlspecialchars((string)$total); ?>">

                        <?php if (defined('RAZORPAY_KEY_ID') && RAZORPAY_KEY_ID !== ''): ?>
                            <button type="button" id="payBtn" class="btn btn-success btn-lg">
                                Pay online ₹<?php echo number_format((float)$total, 2); ?>
                            </button>
                            <div class="small text-muted">Online payment uses Razorpay.</div>
                        <?php else: ?>
                            <button type="submit" class="btn btn-primary btn-lg">
                                Place order (Cash on Delivery)
                            </button>
                            <div class="small text-muted">To enable online payments, set `RAZORPAY_KEY_ID` in `store_config.php`.</div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (defined('RAZORPAY_KEY_ID') && RAZORPAY_KEY_ID !== ''): ?>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
document.getElementById('payBtn')?.addEventListener('click', function () {
    const form = document.getElementById('deliveryForm');
    if (!form || !form.reportValidity()) return;

    const amount = <?php echo json_encode((int)round($total * 100)); ?>;
    const options = {
        key: <?php echo json_encode(RAZORPAY_KEY_ID); ?>,
        amount: amount,
        currency: "INR",
        name: "Pharmacy Store",
        description: "Medicine Order Payment",
        handler: function (response) {
            const paymentId = document.createElement('input');
            paymentId.type = 'hidden';
            paymentId.name = 'payment_id';
            paymentId.value = response.razorpay_payment_id || '';
            form.appendChild(paymentId);
            form.submit();
        },
        prefill: {
            name: form.elements['name']?.value || '',
            contact: form.elements['phone']?.value || ''
        },
        theme: { color: "#1d4ed8" }
    };

    const rzp = new Razorpay(options);
    rzp.on('payment.failed', function () {
        alert('Payment failed.');
    });
    rzp.open();
});
</script>
<?php endif; ?>

<?php include 'footer.php'; ?>
