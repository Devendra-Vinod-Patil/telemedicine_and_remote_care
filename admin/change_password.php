<?php
require_once __DIR__ . '/layout_top.php';

$message = '';
$error = '';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $current = (string)($_POST['current_password'] ?? '');
        $new_password = (string)($_POST['new_password'] ?? '');
        $confirm = (string)($_POST['confirm_password'] ?? '');

        if ($current === '' || $new_password === '' || $confirm === '') {
            throw new RuntimeException('Please fill all fields.');
        }
        if ($new_password !== $confirm) {
            throw new RuntimeException('New passwords do not match.');
        }
        if (!admin_admin_password_is_valid($new_password)) {
            throw new RuntimeException('Password must be 8 to 20 digits (numbers only).');
        }

        $admin_id = (int)($_SESSION['admin_id'] ?? 0);
        $stmt = $conn->prepare('SELECT password FROM admins WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $admin_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = ($res && $res->num_rows > 0) ? $res->fetch_assoc() : null;
        if (!$row) {
            throw new RuntimeException('Admin account not found.');
        }

        if (!password_verify($current, (string)$row['password'])) {
            throw new RuntimeException('Current password is incorrect.');
        }

        $hash = password_hash($new_password, PASSWORD_BCRYPT);
        $stmt2 = $conn->prepare('UPDATE admins SET password = ? WHERE id = ?');
        $stmt2->bind_param('si', $hash, $admin_id);
        if (!$stmt2->execute()) {
            throw new RuntimeException($stmt2->error);
        }

        $message = 'Password changed successfully.';
    }
} catch (Throwable $e) {
    $error = $e->getMessage();
}
?>

<?php if ($message): ?>
  <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
  <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="row justify-content-center">
  <div class="col-12 col-lg-6">
    <div class="cardx p-3">
      <h5 class="m-0 fw-bold mb-2">Change admin password</h5>
      <div class="text-muted mb-3">Password rule: 8 to 20 digits (numbers only).</div>
      <form method="POST">
        <div class="mb-2">
          <label class="form-label">Current password</label>
          <input class="form-control" type="password" name="current_password" required>
        </div>
        <div class="mb-2">
          <label class="form-label">New password</label>
          <input class="form-control" type="password" name="new_password" required>
        </div>
        <div class="mb-2">
          <label class="form-label">Confirm new password</label>
          <input class="form-control" type="password" name="confirm_password" required>
        </div>
        <button class="btn btn-brand w-100 mt-2" type="submit">Update password</button>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/layout_bottom.php'; ?>
