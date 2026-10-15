<?php
require_once __DIR__ . '/layout_top.php';

$message = '';
$error = '';

function fetch_product(mysqli $conn, int $id): ?array
{
    $stmt = $conn->prepare('SELECT * FROM products WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    return ($res && $res->num_rows > 0) ? $res->fetch_assoc() : null;
}

$action = (string)($_GET['action'] ?? '');
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$editing = $action === 'edit' && $id > 0;

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $post_action = (string)($_POST['action'] ?? '');

        if ($post_action === 'save_product') {
            $product_id = (int)($_POST['id'] ?? 0);
            $name = admin_normalize_text((string)($_POST['name'] ?? ''));
            $price = (string)($_POST['price'] ?? '');
            $price_val = (float)$price;

            if ($name === '' || $price === '' || $price_val < 0) {
                throw new RuntimeException('Please provide a valid name and price.');
            }

            $image = null;
            if (!empty($_FILES['image']['name'])) {
                $image = admin_upload_image('image', 'uploads/products');
            }

            if ($product_id > 0) {
                $fields = 'name = ?, price = ?';
                $types = 'sd';
                $params = [$name, $price_val];

                if ($image !== null) {
                    $fields .= ', image = ?';
                    $types .= 's';
                    $params[] = $image;
                }

                $sql = "UPDATE products SET {$fields} WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $types .= 'i';
                $params[] = $product_id;
                admin_bind_params($stmt, $types, $params);
                if (!$stmt->execute()) {
                    throw new RuntimeException($stmt->error);
                }
                $message = 'Medicine updated.';
            } else {
                $stmt = $conn->prepare('INSERT INTO products (name, price, image) VALUES (?, ?, ?)');
                $stmt->bind_param('sds', $name, $price_val, $image);
                if (!$stmt->execute()) {
                    throw new RuntimeException($stmt->error);
                }
                $message = 'Medicine created.';
            }
        } elseif ($post_action === 'delete_product') {
            $product_id = (int)($_POST['id'] ?? 0);
            if ($product_id <= 0) {
                throw new RuntimeException('Invalid product id.');
            }
            $stmt = $conn->prepare('DELETE FROM products WHERE id = ?');
            $stmt->bind_param('i', $product_id);
            if (!$stmt->execute()) {
                throw new RuntimeException($stmt->error);
            }
            $message = 'Medicine deleted.';
        }
    }
} catch (Throwable $e) {
    $error = $e->getMessage();
}

$form = [
    'id' => 0,
    'name' => '',
    'price' => '',
    'image' => '',
];

if ($editing) {
    $existing = fetch_product($conn, $id);
    if (!$existing) {
        $error = 'Medicine not found.';
        $editing = false;
    } else {
        foreach ($form as $k => $_) {
            if (isset($existing[$k])) {
                $form[$k] = $existing[$k];
            }
        }
        $form['id'] = (int)$existing['id'];
    }
}

$list = $conn->query('SELECT id, name, price, image FROM products ORDER BY id DESC');
?>

<?php if ($message): ?>
  <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
  <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="row g-3">
  <div class="col-12 col-lg-5">
    <div class="cardx p-3">
      <div class="d-flex align-items-center justify-content-between mb-2">
        <h5 class="m-0 fw-bold"><?= $editing ? 'Edit medicine' : 'Add medicine' ?></h5>
        <?php if ($editing): ?>
          <a class="btn btn-sm btn-outline-secondary rounded-pill" href="medicines.php">Add new</a>
        <?php endif; ?>
      </div>

      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="save_product">
        <input type="hidden" name="id" value="<?= (int)$form['id'] ?>">

        <div class="mb-2">
          <label class="form-label">Medicine name</label>
          <input class="form-control" name="name" value="<?= htmlspecialchars((string)$form['name']) ?>" required>
        </div>
        <div class="mb-2">
          <label class="form-label">Price</label>
          <input class="form-control" type="number" step="0.01" min="0" name="price" value="<?= htmlspecialchars((string)$form['price']) ?>" required>
        </div>
        <div class="mb-2">
          <label class="form-label">Image (optional)</label>
          <input class="form-control" type="file" name="image" accept=".jpg,.jpeg,.png,.webp">
          <?php if ($editing && !empty($form['image'])): ?>
            <div class="text-muted mt-1 small">Current: <?= htmlspecialchars((string)$form['image']) ?></div>
          <?php endif; ?>
        </div>

        <button class="btn btn-brand mt-3 w-100" type="submit"><?= $editing ? 'Save changes' : 'Create medicine' ?></button>
      </form>
    </div>
  </div>

  <div class="col-12 col-lg-7">
    <div class="cardx p-3">
      <div class="d-flex align-items-center justify-content-between mb-2">
        <h5 class="m-0 fw-bold">All medicines</h5>
        <span class="text-muted">Products table</span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover table-sm m-0">
          <thead>
            <tr>
              <th>ID</th>
              <th>Name</th>
              <th>Price</th>
              <th>Image</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($list && $list->num_rows > 0): ?>
              <?php while ($row = $list->fetch_assoc()): ?>
                <tr>
                  <td><?= (int)$row['id'] ?></td>
                  <td class="fw-bold"><?= htmlspecialchars((string)$row['name']) ?></td>
                  <td>₹<?= htmlspecialchars(number_format((float)$row['price'], 2)) ?></td>
                  <td class="text-muted small"><?= htmlspecialchars((string)$row['image']) ?></td>
                  <td class="text-nowrap">
                    <a class="btn btn-sm btn-outline-secondary rounded-pill" href="medicines.php?action=edit&id=<?= (int)$row['id'] ?>">Edit</a>
                    <form method="POST" class="d-inline" onsubmit="return confirm('Delete this medicine?');">
                      <input type="hidden" name="action" value="delete_product">
                      <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                      <button class="btn btn-sm btn-outline-danger rounded-pill" type="submit">Delete</button>
                    </form>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr><td colspan="5" class="text-muted">No medicines found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/layout_bottom.php'; ?>
