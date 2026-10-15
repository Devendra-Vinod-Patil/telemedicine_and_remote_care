<?php
$next = basename(__FILE__);
require_once 'store_auth.php';
store_require_login($next, true);

$page_title = 'Buy Medicine';
include 'header.php';
include 'database.php';

$result = $conn->query("SELECT id, name, price, image FROM products ORDER BY id DESC");
?>

<div class="container py-5">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-3 mb-4">
        <div>
            <h1 class="fw-bold mb-1">Pharmacy Store</h1>
            <p class="text-muted mb-0">Genuine medicines and healthcare essentials.</p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-primary" href="cart.php">
                <i class="fa-solid fa-cart-shopping me-1"></i>Cart
            </a>
        </div>
    </div>

    <?php if (!$result || $result->num_rows === 0): ?>
        <div class="alert alert-warning mb-0">
            No products found. Import `store_schema.sql` and run `store_seed_products.php`.
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php while ($product = $result->fetch_assoc()): ?>
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="ratio ratio-4x3 bg-light">
                            <img
                                src="<?php echo htmlspecialchars($product['image'] ?: 'default.png'); ?>"
                                class="card-img-top object-fit-cover"
                                alt="<?php echo htmlspecialchars($product['name']); ?>"
                                onerror="this.onerror=null;this.src='default.png';"
                            >
                        </div>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title fw-bold"><?php echo htmlspecialchars($product['name']); ?></h5>
                            <div class="mt-auto d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-primary">₹<?php echo number_format((float)$product['price'], 2); ?></span>
                                <a class="btn btn-sm btn-primary" href="add_to_cart.php?id=<?php echo (int)$product['id']; ?>">
                                    Add
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
