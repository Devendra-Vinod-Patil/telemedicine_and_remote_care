<?php
require_once __DIR__ . '/layout_top.php';

$message = '';
$error = '';

function fetch_patient(mysqli $conn, int $id): ?array
{
    $stmt = $conn->prepare('SELECT * FROM patients WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    return ($res && $res->num_rows > 0) ? $res->fetch_assoc() : null;
}

$action = (string)($_GET['action'] ?? '');
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$editing = $action === 'edit' && $id > 0;
$resetting = $action === 'reset' && $id > 0;

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $post_action = (string)($_POST['action'] ?? '');

        if ($post_action === 'save_patient') {
            $patient_id = (int)($_POST['id'] ?? 0);
            $full_name = admin_normalize_text((string)($_POST['full_name'] ?? ''));
            $email = trim((string)($_POST['email'] ?? ''));
            $phone = admin_normalize_text((string)($_POST['phone'] ?? ''));
            $address = admin_normalize_text((string)($_POST['address'] ?? ''));
            $age = ($_POST['age'] ?? '') === '' ? null : (int)$_POST['age'];
            $gender = strtolower(trim((string)($_POST['gender'] ?? '')));

            $new_password = (string)($_POST['password'] ?? '');
            $confirm_password = (string)($_POST['confirm_password'] ?? '');

            if ($full_name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Please provide a valid name and email.');
            }
            if ($age !== null && ($age < 0 || $age > 130)) {
                throw new RuntimeException('Invalid age.');
            }
            $allowed_genders = ['male', 'female', 'other', ''];
            if (!in_array($gender, $allowed_genders, true)) {
                throw new RuntimeException('Invalid gender.');
            }

            if ($patient_id <= 0) {
                if ($new_password === '' || $new_password !== $confirm_password) {
                    throw new RuntimeException('Passwords do not match.');
                }
                if (!admin_user_password_is_valid($new_password)) {
                    throw new RuntimeException('Password must be exactly 8 digits (numbers only).');
                }
            } elseif ($new_password !== '' || $confirm_password !== '') {
                if ($new_password !== $confirm_password) {
                    throw new RuntimeException('Passwords do not match.');
                }
                if (!admin_user_password_is_valid($new_password)) {
                    throw new RuntimeException('Password must be exactly 8 digits (numbers only).');
                }
            }

            $photo = null;
            if (!empty($_FILES['photo']['name'])) {
                $photo = admin_upload_image('photo', 'uploads');
            }

            if ($patient_id > 0) {
                $fields = 'full_name = ?, email = ?, phone = ?, address = ?, age = ?, gender = ?';
                $types = 'ssssiss';
                $params = [$full_name, $email, $phone, $address, $age, $gender];

                if ($photo !== null) {
                    $fields .= ', photo = ?';
                    $types .= 's';
                    $params[] = $photo;
                }
                if ($new_password !== '') {
                    $fields .= ', password = ?';
                    $types .= 's';
                    $params[] = password_hash($new_password, PASSWORD_BCRYPT);
                }

                $sql = "UPDATE patients SET {$fields} WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $types .= 'i';
                $params[] = $patient_id;
                admin_bind_params($stmt, $types, $params);
                if (!$stmt->execute()) {
                    throw new RuntimeException($stmt->error);
                }
                $message = 'Patient updated.';
            } else {
                $hash = password_hash($new_password, PASSWORD_BCRYPT);
                $sql = 'INSERT INTO patients (full_name, email, phone, address, age, gender, password, photo) VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('ssssisss', $full_name, $email, $phone, $address, $age, $gender, $hash, $photo);
                if (!$stmt->execute()) {
                    throw new RuntimeException($stmt->error);
                }
                $message = 'Patient created.';
            }
        } elseif ($post_action === 'delete_patient') {
            $patient_id = (int)($_POST['id'] ?? 0);
            if ($patient_id <= 0) {
                throw new RuntimeException('Invalid patient id.');
            }
            $stmt = $conn->prepare('DELETE FROM patients WHERE id = ?');
            $stmt->bind_param('i', $patient_id);
            if (!$stmt->execute()) {
                throw new RuntimeException($stmt->error);
            }
            $message = 'Patient deleted.';
        } elseif ($post_action === 'reset_password') {
            $patient_id = (int)($_POST['id'] ?? 0);
            $new_password = (string)($_POST['new_password'] ?? '');
            $confirm = (string)($_POST['confirm_password'] ?? '');
            if ($patient_id <= 0) {
                throw new RuntimeException('Invalid patient id.');
            }
            if ($new_password === '' || $new_password !== $confirm) {
                throw new RuntimeException('Passwords do not match.');
            }
            if (!admin_user_password_is_valid($new_password)) {
                throw new RuntimeException('Password must be exactly 8 digits (numbers only).');
            }
            $hash = password_hash($new_password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare('UPDATE patients SET password = ? WHERE id = ?');
            $stmt->bind_param('si', $hash, $patient_id);
            if (!$stmt->execute()) {
                throw new RuntimeException($stmt->error);
            }
            $message = 'Patient password reset.';
        }
    }
} catch (Throwable $e) {
    $error = $e->getMessage();
}

$form = [
    'id' => 0,
    'full_name' => '',
    'email' => '',
    'phone' => '',
    'address' => '',
    'age' => '',
    'gender' => '',
    'photo' => '',
];

if ($editing) {
    $existing = fetch_patient($conn, $id);
    if (!$existing) {
        $error = 'Patient not found.';
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

if ($resetting) {
    $existing = fetch_patient($conn, $id);
    if (!$existing) {
        $error = 'Patient not found.';
        $resetting = false;
    }
}

$list = $conn->query('SELECT id, full_name, email, phone, age, gender, created_at FROM patients ORDER BY created_at DESC');
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
        <h5 class="m-0 fw-bold"><?= $editing ? 'Edit patient' : 'Add patient' ?></h5>
        <?php if ($editing): ?>
          <a class="btn btn-sm btn-outline-secondary rounded-pill" href="patients.php">Add new</a>
        <?php endif; ?>
      </div>

      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="save_patient">
        <input type="hidden" name="id" value="<?= (int)$form['id'] ?>">

        <div class="mb-2">
          <label class="form-label">Full name</label>
          <input class="form-control" name="full_name" value="<?= htmlspecialchars((string)$form['full_name']) ?>" required>
        </div>
        <div class="mb-2">
          <label class="form-label">Email</label>
          <input class="form-control" type="email" name="email" value="<?= htmlspecialchars((string)$form['email']) ?>" required>
        </div>
        <div class="mb-2">
          <label class="form-label">Phone</label>
          <input class="form-control" name="phone" value="<?= htmlspecialchars((string)$form['phone']) ?>">
        </div>
        <div class="mb-2">
          <label class="form-label">Address</label>
          <input class="form-control" name="address" value="<?= htmlspecialchars((string)$form['address']) ?>">
        </div>

        <div class="row g-2">
          <div class="col-12 col-md-6">
            <div class="mb-2">
              <label class="form-label">Age</label>
              <input class="form-control" type="number" min="0" max="130" name="age" value="<?= htmlspecialchars((string)$form['age']) ?>">
            </div>
          </div>
          <div class="col-12 col-md-6">
            <div class="mb-2">
              <label class="form-label">Gender</label>
              <select class="form-select" name="gender">
                <option value="" <?= $form['gender'] === '' ? 'selected' : '' ?>>Select</option>
                <option value="male" <?= $form['gender'] === 'male' ? 'selected' : '' ?>>Male</option>
                <option value="female" <?= $form['gender'] === 'female' ? 'selected' : '' ?>>Female</option>
                <option value="other" <?= $form['gender'] === 'other' ? 'selected' : '' ?>>Other</option>
              </select>
            </div>
          </div>
        </div>

        <div class="mb-2">
          <label class="form-label">Photo (optional)</label>
          <input class="form-control" type="file" name="photo" accept=".jpg,.jpeg,.png,.webp">
          <?php if ($editing && !empty($form['photo'])): ?>
            <div class="text-muted mt-1 small">Current: <?= htmlspecialchars((string)$form['photo']) ?></div>
          <?php endif; ?>
        </div>

        <div class="mt-3 p-3 rounded-3" style="border:1px dashed #d8e1ea;">
          <div class="fw-bold mb-2">Password <?= $editing ? '(leave blank to keep)' : '' ?></div>
          <div class="mb-2">
            <input class="form-control" type="password" name="password" placeholder="8 digits">
          </div>
          <div class="mb-0">
            <input class="form-control" type="password" name="confirm_password" placeholder="confirm password">
          </div>
          <div class="text-muted small mt-2">Admin can reset but cannot view old password.</div>
        </div>

        <button class="btn btn-brand mt-3 w-100" type="submit"><?= $editing ? 'Save changes' : 'Create patient' ?></button>
      </form>
    </div>

    <?php if ($resetting): ?>
      <div class="cardx p-3 mt-3">
        <h5 class="m-0 fw-bold mb-2">Reset patient password (ID <?= (int)$id ?>)</h5>
        <form method="POST">
          <input type="hidden" name="action" value="reset_password">
          <input type="hidden" name="id" value="<?= (int)$id ?>">
          <div class="mb-2">
            <label class="form-label">New password (8 digits)</label>
            <input class="form-control" type="password" name="new_password" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Confirm password</label>
            <input class="form-control" type="password" name="confirm_password" required>
          </div>
          <button class="btn btn-outline-primary w-100 rounded-pill fw-bold" type="submit">Reset password</button>
        </form>
      </div>
    <?php endif; ?>
  </div>

  <div class="col-12 col-lg-7">
    <div class="cardx p-3">
      <div class="d-flex align-items-center justify-content-between mb-2">
        <h5 class="m-0 fw-bold">All patients</h5>
        <span class="text-muted">Manage accounts</span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover table-sm m-0">
          <thead>
            <tr>
              <th>ID</th>
              <th>Name</th>
              <th>Email</th>
              <th>Phone</th>
              <th>Age</th>
              <th>Gender</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($list && $list->num_rows > 0): ?>
              <?php while ($row = $list->fetch_assoc()): ?>
                <tr>
                  <td><?= (int)$row['id'] ?></td>
                  <td class="fw-bold"><?= htmlspecialchars((string)$row['full_name']) ?></td>
                  <td><?= htmlspecialchars((string)$row['email']) ?></td>
                  <td><?= htmlspecialchars((string)$row['phone']) ?></td>
                  <td><?= htmlspecialchars((string)$row['age']) ?></td>
                  <td><?= htmlspecialchars((string)$row['gender']) ?></td>
                  <td class="text-nowrap">
                    <a class="btn btn-sm btn-outline-secondary rounded-pill" href="patients.php?action=edit&id=<?= (int)$row['id'] ?>">Edit</a>
                    <a class="btn btn-sm btn-outline-primary rounded-pill" href="patients.php?action=reset&id=<?= (int)$row['id'] ?>">Reset PW</a>
                    <form method="POST" class="d-inline" onsubmit="return confirm('Delete this patient? This will also delete related appointments.');">
                      <input type="hidden" name="action" value="delete_patient">
                      <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                      <button class="btn btn-sm btn-outline-danger rounded-pill" type="submit">Delete</button>
                    </form>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr><td colspan="7" class="text-muted">No patients found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/layout_bottom.php'; ?>
