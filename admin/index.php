<?php
require_once __DIR__ . '/bootstrap.php';
admin_require_login();

$flash_success = (string)($_SESSION['flash_success'] ?? '');
$flash_error = (string)($_SESSION['flash_error'] ?? '');
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$valid_statuses = ['pending', 'confirmed', 'completed', 'cancelled'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');
    $appointment_id = isset($_POST['appointment_id']) ? (int)$_POST['appointment_id'] : 0;

    try {
        if ($action === 'update_appointment_status') {
            $status = strtolower(trim((string)($_POST['status'] ?? '')));
            if ($appointment_id <= 0 || !in_array($status, $valid_statuses, true)) {
                throw new RuntimeException('Invalid appointment update.');
            }

            $stmt = $conn->prepare('UPDATE appointments SET status = ? WHERE id = ?');
            $stmt->bind_param('si', $status, $appointment_id);
            if (!$stmt->execute()) {
                throw new RuntimeException($stmt->error);
            }
            $stmt->close();

            $_SESSION['flash_success'] = 'Appointment status updated.';
            header('Location: index.php');
            exit();
        }

        if ($action === 'delete_appointment') {
            if ($appointment_id <= 0) {
                throw new RuntimeException('Invalid appointment.');
            }

            $stmt = $conn->prepare('DELETE FROM appointments WHERE id = ?');
            $stmt->bind_param('i', $appointment_id);
            if (!$stmt->execute()) {
                throw new RuntimeException($stmt->error);
            }
            $stmt->close();

            $_SESSION['flash_success'] = 'Appointment deleted.';
            header('Location: index.php');
            exit();
        }
    } catch (Throwable $e) {
        $_SESSION['flash_error'] = $e->getMessage();
        header('Location: index.php');
        exit();
    }
}

require_once __DIR__ . '/layout_top.php';

function count_table(mysqli $conn, string $table): int
{
    $safe = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $exists = $conn->query("SHOW TABLES LIKE '{$safe}'");
    if (!$exists || $exists->num_rows === 0) {
        return 0;
    }

    $result = $conn->query("SELECT COUNT(*) AS c FROM `{$safe}`");
    $row = $result ? $result->fetch_assoc() : null;
    return (int)($row['c'] ?? 0);
}

$stats = [
    'Doctors' => count_table($conn, 'doctors'),
    'Patients' => count_table($conn, 'patients'),
    'Appointments' => count_table($conn, 'appointments'),
    'Medicines' => count_table($conn, 'products'),
    'Orders' => count_table($conn, 'orders'),
];

$recent = null;
$has_appointments = $conn->query("SHOW TABLES LIKE 'appointments'");
$has_doctors = $conn->query("SHOW TABLES LIKE 'doctors'");
$has_patients = $conn->query("SHOW TABLES LIKE 'patients'");
if (($has_appointments && $has_appointments->num_rows > 0) && ($has_doctors && $has_doctors->num_rows > 0) && ($has_patients && $has_patients->num_rows > 0)) {
    $recent = $conn->query(
        "SELECT a.id, a.appointment_date, a.appointment_time, a.status,
                d.full_name AS doctor_name, p.full_name AS patient_name
         FROM appointments a
         JOIN doctors d ON d.id = a.doctor_id
         JOIN patients p ON p.id = a.patient_id
         ORDER BY a.id DESC
         LIMIT 8"
    );
}
?>

<div class="dash-card p-3 mb-3">
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div>
      <h2 class="h4 fw-bold mb-1">Admin Dashboard</h2>
      <div class="text-muted">Manage doctors, patients, medicines, and appointments.</div>
    </div>
    <div class="d-flex flex-wrap gap-2">
      <a class="btn btn-outline-secondary rounded-pill px-3" href="doctors.php">Doctors</a>
      <a class="btn btn-outline-secondary rounded-pill px-3" href="patients.php">Patients</a>
      <a class="btn btn-outline-secondary rounded-pill px-3" href="medicines.php">Medicines</a>
    </div>
  </div>
</div>

<?php if ($flash_success): ?>
  <div class="alert alert-success"><?= htmlspecialchars($flash_success) ?></div>
<?php endif; ?>
<?php if ($flash_error): ?>
  <div class="alert alert-danger"><?= htmlspecialchars($flash_error) ?></div>
<?php endif; ?>

<div class="row g-3 mb-3">
  <?php foreach ($stats as $label => $value): ?>
    <div class="col-12 col-sm-6 col-lg-4 col-xl-2">
      <div class="dash-card p-3 h-100">
        <div class="text-muted fw-bold"><?= htmlspecialchars($label) ?></div>
        <div class="display-6 fw-black fw-bold mb-0"><?= (int)$value ?></div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="dash-card p-3">
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
    <h5 class="m-0 fw-bold">Recent appointments</h5>
    <span class="text-muted">Latest 8</span>
  </div>
  <div class="table-responsive">
    <table class="table table-bordered align-middle mb-0">
      <thead>
        <tr>
          <th>#</th>
          <th>Date</th>
          <th>Time</th>
          <th>Doctor</th>
          <th>Patient</th>
          <th>Status</th>
          <th style="width:260px;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($recent && $recent->num_rows > 0): ?>
          <?php while ($row = $recent->fetch_assoc()): ?>
            <tr>
              <td><?= (int)$row['id'] ?></td>
              <td><?= htmlspecialchars(date('d M Y', strtotime((string)$row['appointment_date']))) ?></td>
              <td><?= htmlspecialchars(date('h:i A', strtotime((string)$row['appointment_time']))) ?></td>
              <td><?= htmlspecialchars((string)$row['doctor_name']) ?></td>
              <td><?= htmlspecialchars((string)$row['patient_name']) ?></td>
              <?php $status = strtolower((string)($row['status'] ?? '')); ?>
              <?php $status_class = in_array($status, ['pending', 'confirmed', 'completed', 'cancelled'], true) ? 'status-' . $status : 'status-pending'; ?>
              <td><span class="badge-status <?= htmlspecialchars($status_class) ?>"><?= htmlspecialchars((string)$row['status']) ?></span></td>
              <td>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                  <form method="post" class="d-flex gap-2 align-items-center">
                    <input type="hidden" name="action" value="update_appointment_status">
                    <input type="hidden" name="appointment_id" value="<?= (int)$row['id'] ?>">
                    <select class="form-select form-select-sm" name="status" style="min-height:38px;border-radius:999px;">
                      <?php foreach ($valid_statuses as $s): ?>
                        <option value="<?= htmlspecialchars($s) ?>" <?= $s === $status ? 'selected' : '' ?>><?= htmlspecialchars(ucfirst($s)) ?></option>
                      <?php endforeach; ?>
                    </select>
                    <button class="btn btn-sm btn-outline-secondary rounded-pill px-3" type="submit">Update</button>
                  </form>
                  <form method="post" onsubmit="return confirm('Delete this appointment?');">
                    <input type="hidden" name="action" value="delete_appointment">
                    <input type="hidden" name="appointment_id" value="<?= (int)$row['id'] ?>">
                    <button class="btn btn-sm btn-outline-danger rounded-pill px-3" type="submit">Delete</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="7" class="text-muted">No appointments yet.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/layout_bottom.php'; ?>
