<?php
require_once __DIR__ . '/layout_top.php';

$message = '';
$error = '';

function fetch_doctor(mysqli $conn, int $id): ?array
{
    $stmt = $conn->prepare('SELECT * FROM doctors WHERE id = ? LIMIT 1');
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

        if ($post_action === 'save_doctor') {
            $doctor_id = (int)($_POST['id'] ?? 0);
            $full_name = admin_normalize_text((string)($_POST['full_name'] ?? ''));
            $email = trim((string)($_POST['email'] ?? ''));
            $phone = admin_normalize_text((string)($_POST['phone'] ?? ''));
            $address = admin_normalize_text((string)($_POST['address'] ?? ''));
            $specialization = admin_normalize_text((string)($_POST['specialization'] ?? ''));
            $experience = (int)($_POST['experience'] ?? 0);
            $clinic = admin_normalize_text((string)($_POST['clinic'] ?? ''));

            $new_password = (string)($_POST['password'] ?? '');
            $confirm_password = (string)($_POST['confirm_password'] ?? '');

            if ($full_name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Please provide a valid name and email.');
            }

            if ($doctor_id <= 0) {
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

            if ($doctor_id > 0) {
                $fields = 'full_name = ?, email = ?, phone = ?, address = ?, specialization = ?, experience = ?, clinic = ?';
                $params = [$full_name, $email, $phone, $address, $specialization, $experience, $clinic];
                $types = 'sssssis';

                if ($photo !== null) {
                    $fields .= ', photo = ?';
                    $params[] = $photo;
                    $types .= 's';
                }

                if ($new_password !== '') {
                    $fields .= ', password = ?';
                    $params[] = password_hash($new_password, PASSWORD_BCRYPT);
                    $types .= 's';
                }

                $sql = "UPDATE doctors SET {$fields} WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $types .= 'i';
                $params[] = $doctor_id;
                admin_bind_params($stmt, $types, $params);

                if (!$stmt->execute()) {
                    throw new RuntimeException($stmt->error);
                }
                $message = 'Doctor updated.';
            } else {
                $hash = password_hash($new_password, PASSWORD_BCRYPT);
                $sql = 'INSERT INTO doctors (full_name, email, phone, address, password, specialization, experience, clinic, photo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)';
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('ssssssiss', $full_name, $email, $phone, $address, $hash, $specialization, $experience, $clinic, $photo);
                if (!$stmt->execute()) {
                    throw new RuntimeException($stmt->error);
                }
                $message = 'Doctor created.';
            }
        } elseif ($post_action === 'toggle_verification') {
            $doctor_id = (int)($_POST['id'] ?? 0);
            $verified = isset($_POST['verified']) ? (int)$_POST['verified'] : 0;
            $verified = $verified === 1 ? 1 : 0;

            if ($doctor_id <= 0) {
                throw new RuntimeException('Invalid doctor id.');
            }

            admin_ensure_column($conn, 'doctors', 'is_verified', "TINYINT(1) NOT NULL DEFAULT 0 AFTER `clinic`");
            $stmt = $conn->prepare('UPDATE doctors SET is_verified = ? WHERE id = ?');
            $stmt->bind_param('ii', $verified, $doctor_id);
            if (!$stmt->execute()) {
                throw new RuntimeException($stmt->error);
            }
            $stmt->close();

            $message = $verified === 1 ? 'Doctor verified.' : 'Doctor marked unverified.';
        } elseif ($post_action === 'delete_doctor') {
            $doctor_id = (int)($_POST['id'] ?? 0);
            if ($doctor_id <= 0) {
                throw new RuntimeException('Invalid doctor id.');
            }
            $stmt = $conn->prepare('DELETE FROM doctors WHERE id = ?');
            $stmt->bind_param('i', $doctor_id);
            if (!$stmt->execute()) {
                throw new RuntimeException($stmt->error);
            }
            $message = 'Doctor deleted.';
        } elseif ($post_action === 'reset_password') {
            $doctor_id = (int)($_POST['id'] ?? 0);
            $new_password = (string)($_POST['new_password'] ?? '');
            $confirm = (string)($_POST['confirm_password'] ?? '');
            if ($doctor_id <= 0) {
                throw new RuntimeException('Invalid doctor id.');
            }
            if ($new_password === '' || $new_password !== $confirm) {
                throw new RuntimeException('Passwords do not match.');
            }
            if (!admin_user_password_is_valid($new_password)) {
                throw new RuntimeException('Password must be exactly 8 digits (numbers only).');
            }

            $hash = password_hash($new_password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare('UPDATE doctors SET password = ? WHERE id = ?');
            $stmt->bind_param('si', $hash, $doctor_id);
            if (!$stmt->execute()) {
                throw new RuntimeException($stmt->error);
            }
            $message = 'Doctor password reset.';
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
    'specialization' => '',
    'experience' => '',
    'clinic' => '',
    'photo' => '',
];

if ($editing) {
    $existing = fetch_doctor($conn, $id);
    if (!$existing) {
        $error = 'Doctor not found.';
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
    $existing = fetch_doctor($conn, $id);
    if (!$existing) {
        $error = 'Doctor not found.';
        $resetting = false;
    }
}

admin_ensure_column($conn, 'doctors', 'is_verified', "TINYINT(1) NOT NULL DEFAULT 0 AFTER `clinic`");
$list = $conn->query('SELECT id, full_name, email, phone, specialization, clinic, COALESCE(is_verified, 0) AS is_verified, created_at FROM doctors ORDER BY created_at DESC');
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
        <h5 class="m-0 fw-bold"><?= $editing ? 'Edit doctor' : 'Add doctor' ?></h5>
        <?php if ($editing): ?>
          <a class="btn btn-sm btn-outline-secondary rounded-pill" href="doctors.php">Add new</a>
        <?php endif; ?>
      </div>

      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="save_doctor">
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
              <label class="form-label">Specialization</label>
              <input class="form-control" name="specialization" value="<?= htmlspecialchars((string)$form['specialization']) ?>">
            </div>
          </div>
          <div class="col-12 col-md-6">
            <div class="mb-2">
              <label class="form-label">Experience (years)</label>
              <input class="form-control" type="number" min="0" name="experience" value="<?= htmlspecialchars((string)$form['experience']) ?>">
            </div>
          </div>
        </div>
        <div class="mb-2">
          <label class="form-label">Clinic</label>
          <input class="form-control" name="clinic" value="<?= htmlspecialchars((string)$form['clinic']) ?>">
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
          <div class="text-muted small mt-2">Note: passwords are stored as hashes; admin can reset but cannot view the old password.</div>
        </div>

        <button class="btn btn-brand mt-3 w-100" type="submit"><?= $editing ? 'Save changes' : 'Create doctor' ?></button>
      </form>
    </div>

    <?php if ($resetting): ?>
      <div class="cardx p-3 mt-3">
        <h5 class="m-0 fw-bold mb-2">Reset doctor password (ID <?= (int)$id ?>)</h5>
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
        <h5 class="m-0 fw-bold">All doctors</h5>
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
              <th>Specialization</th>
              <th>Clinic</th>
              <th>Verification</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($list && $list->num_rows > 0): ?>
              <?php while ($row = $list->fetch_assoc()): ?>
                <?php $is_verified = (int)($row['is_verified'] ?? 0) === 1; ?>
                <tr>
                  <td><?= (int)$row['id'] ?></td>
                  <td class="fw-bold">
                    <?= htmlspecialchars((string)$row['full_name']) ?>
                    <?php if ($is_verified): ?>
                      <span class="badge badge-soft rounded-pill ms-2">Verified</span>
                    <?php else: ?>
                      <span class="badge text-bg-warning rounded-pill ms-2">Unverified</span>
                    <?php endif; ?>
                  </td>
                  <td><?= htmlspecialchars((string)$row['email']) ?></td>
                  <td><?= htmlspecialchars((string)$row['phone']) ?></td>
                  <td><?= htmlspecialchars((string)$row['specialization']) ?></td>
                  <td><?= htmlspecialchars((string)$row['clinic']) ?></td>
                  <td class="text-nowrap">
                    <form method="POST" class="d-inline">
                      <input type="hidden" name="action" value="toggle_verification">
                      <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                      <input type="hidden" name="verified" value="<?= $is_verified ? 0 : 1 ?>">
                      <button class="btn btn-sm <?= $is_verified ? 'btn-outline-secondary' : 'btn-outline-success' ?> rounded-pill" type="submit">
                        <?= $is_verified ? 'Unverify' : 'Verify' ?>
                      </button>
                    </form>
                  </td>
                  <td class="text-nowrap">
                    <a class="btn btn-sm btn-outline-secondary rounded-pill" href="doctors.php?action=edit&id=<?= (int)$row['id'] ?>">Edit</a>
                    <a class="btn btn-sm btn-outline-primary rounded-pill" href="doctors.php?action=reset&id=<?= (int)$row['id'] ?>">Reset PW</a>
                    <form method="POST" class="d-inline" onsubmit="return confirm('Delete this doctor? This will also delete related appointments.');">
                      <input type="hidden" name="action" value="delete_doctor">
                      <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                      <button class="btn btn-sm btn-outline-danger rounded-pill" type="submit">Delete</button>
                    </form>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr><td colspan="8" class="text-muted">No doctors found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/layout_bottom.php'; ?>
