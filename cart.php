<?php
$next = basename(__FILE__);
require_once 'store_auth.php';
store_require_login($next, true);

$page_title = 'Cart';
include 'header.php';
include 'database.php';

$cart_items = (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) ? $_SESSION['cart'] : [];
$total = 0.0;
$products = [];

if (!empty($cart_items)) {
    $ids = implode(',', array_map('intval', array_keys($cart_items)));
    $res = $conn->query("SELECT id, name, price, image FROM products WHERE id IN ($ids)");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $products[(int)$row['id']] = $row;
        }
    }

    foreach ($cart_items as $pid => $qty) {
        if (!isset($products[$pid])) continue;
        $total += ((float)$products[$pid]['price']) * (int)$qty;
    }
}
?>

<div class="container py-5">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-3 mb-4">
        <div>
            <h1 class="fw-bold mb-1">Your Cart</h1>
            <p class="text-muted mb-0">Review your medicines before checkout.</p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-primary" href="storeindex.php">+ Add medicines</a>
            <?php if (!empty($cart_items)): ?>
                <a class="btn btn-outline-secondary" href="clear_cart.php">Clear cart</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($cart_items) || empty($products)): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body p-5 text-center">
                <h3 class="fw-bold mb-2">Your cart is empty</h3>
                <p class="text-muted mb-4">Add medicines from the store to continue.</p>
                <a class="btn btn-primary" href="storeindex.php">Shop medicines</a>
            </div>
        </div>
    <?php else: ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th class="text-end">Price</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Subtotal</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($cart_items as $pid => $qty): ?>
                            <?php if (!isset($products[$pid])) continue; ?>
                            <?php
                                $p = $products[$pid];
                                $subtotal = ((float)$p['price']) * (int)$qty;
                            ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <img
                                            src="<?php echo htmlspecialchars($p['image'] ?: 'default.png'); ?>"
                                            alt=""
                                            style="width:56px;height:56px;object-fit:cover;border-radius:12px;"
                                            onerror="this.onerror=null;this.src='default.png';"
                                        >
                                        <div class="fw-semibold"><?php echo htmlspecialchars($p['name']); ?></div>
                                    </div>
                                </td>
                                <td class="text-end">₹<?php echo number_format((float)$p['price'], 2); ?></td>
                                <td class="text-end"><?php echo (int)$qty; ?></td>
                                <td class="text-end fw-bold text-primary">₹<?php echo number_format((float)$subtotal, 2); ?></td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-danger" href="remove_from_cart.php?id=<?php echo (int)$pid; ?>">Remove</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <hr class="my-4">

                <div class="d-flex justify-content-between align-items-center">
                    <div class="fw-bold fs-5">Total</div>
                    <div class="fw-bold fs-4 text-primary">₹<?php echo number_format((float)$total, 2); ?></div>
                </div>

                <div class="d-flex flex-column flex-md-row gap-2 mt-4">
                    <a class="btn btn-primary" href="delivery_form.php">Proceed to delivery</a>
                    <a class="btn btn-outline-primary" href="storeindex.php">Continue shopping</a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
