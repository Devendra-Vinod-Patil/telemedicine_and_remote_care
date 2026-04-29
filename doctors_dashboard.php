<?php
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'doctor') {
    header('Location: login.php');
    exit();
}

include 'database.php';

function get_columns(mysqli $conn, string $table): array
{
    $columns = [];
    $result = $conn->query("SHOW COLUMNS FROM `{$table}`");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
    }
    return $columns;
}

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

function parse_digital_prescription($prescription_raw): ?array
{
    if (!is_string($prescription_raw) || trim($prescription_raw) === '') {
        return null;
    }

    $decoded = json_decode($prescription_raw, true);
    if (!is_array($decoded) || trim((string) ($decoded['diagnosis'] ?? '')) === '') {
        return null;
    }

    return $decoded;
}

$doctor_id = (int) $_SESSION['user_id'];
$flash_success = $_SESSION['flash_success'] ?? '';
$flash_error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

ensure_column($conn, 'appointments', 'doctor_hidden', 'TINYINT(1) NOT NULL DEFAULT 0');
ensure_column($conn, 'appointments', 'payment_status', "VARCHAR(20) NOT NULL DEFAULT 'unpaid'");
ensure_column($conn, 'appointments', 'payment_id', "VARCHAR(100) NULL");
ensure_column($conn, 'appointments', 'payment_amount', "DECIMAL(10,2) NULL");
ensure_column($conn, 'appointments', 'paid_at', "TIMESTAMP NULL DEFAULT NULL");

$doctor_columns = get_columns($conn, 'doctors');
$patient_columns = get_columns($conn, 'patients');

$signature_select = in_array('signature', $doctor_columns, true) ? 'signature' : 'NULL AS signature';
$photo_select = in_array('photo', $doctor_columns, true) ? 'photo' : 'NULL AS photo';
$age_select = in_array('age', $patient_columns, true) ? 'p.age AS patient_age' : 'NULL AS patient_age';
$gender_select = in_array('gender', $patient_columns, true) ? 'p.gender AS patient_gender' : 'NULL AS patient_gender';

$stmt = $conn->prepare("SELECT full_name, specialization, clinic, {$signature_select}, {$photo_select} FROM doctors WHERE id = ?");
$stmt->bind_param('i', $doctor_id);
$stmt->execute();
$doctor = $stmt->get_result()->fetch_assoc();
$stmt->close();

$stmt = $conn->prepare(
    "SELECT a.id, a.appointment_date, a.appointment_time, a.status, a.prescription,
            COALESCE(a.payment_status, 'unpaid') AS payment_status, a.payment_amount,
            p.full_name AS patient_name, {$age_select}, {$gender_select}
     FROM appointments a
     JOIN patients p ON a.patient_id = p.id
     WHERE a.doctor_id = ? AND COALESCE(a.doctor_hidden, 0) = 0
     ORDER BY a.appointment_date DESC, a.appointment_time DESC"
);
$stmt->bind_param('i', $doctor_id);
$stmt->execute();
$appointments = $stmt->get_result();
$stmt->close();

// Medicine orders (store) for this logged-in doctor account (if they also buy)
ensure_column($conn, 'orders', 'user_id', 'INT NULL AFTER `id`');
ensure_column($conn, 'orders', 'user_role', "VARCHAR(20) NULL AFTER `user_id`");

$stmt = $conn->prepare(
    "SELECT id, amount, status, created_at
     FROM orders
     WHERE user_id = ? AND user_role = 'doctor'
     ORDER BY created_at DESC
     LIMIT 50"
);
$stmt->bind_param('i', $doctor_id);
$stmt->execute();
$store_orders = $stmt->get_result();
$stmt->close();

include 'header.php';
?>
<style>
    .dash-wrap { padding: 28px 0 56px; }
    .dash-card {
        background: #fff;
        border: 1px solid rgba(148,163,184,.2);
        border-radius: 20px;
        box-shadow: 0 16px 30px rgba(15,23,42,.07);
        padding: 22px;
        margin-bottom: 20px;
    }
    .dash-card:hover { transform: none; }
    .table thead th { background: #f8fbfd; white-space: nowrap; }
    .badge-status { border-radius: 999px; padding: 8px 12px; font-weight: 700; font-size: .82rem; text-transform: capitalize; }
    .status-pending { background: #fff5cc; color: #8a6d00; }
    .status-confirmed { background: #dff6ff; color: #0c5f8a; }
    .status-completed { background: #dff7e7; color: #146c43; }
    .status-cancelled { background: #fde2e4; color: #a61e2d; }
    .form-control, .form-select { min-height: 42px; border-radius: 10px; }
    .doctor-photo {
        width: 78px;
        height: 78px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid #dbe4ec;
    }
</style>

<div class="container dash-wrap">
    <div class="dash-card">
        <div class="d-flex align-items-center gap-3 mb-3">
            <img class="doctor-photo" src="<?= htmlspecialchars(!empty($doctor['photo']) ? $doctor['photo'] : 'default.png') ?>" alt="Doctor photo">
            <div>
                <h2 class="h4 fw-bold mb-1">Doctor Dashboard</h2>
                <div class="text-muted"><?= htmlspecialchars($doctor['full_name'] ?? '') ?></div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0">
                <tbody>
                    <tr>
                        <th style="width:220px;">Doctor</th>
                        <td><?= htmlspecialchars($doctor['full_name'] ?? '') ?></td>
                        <th>Specialization</th>
                        <td><?= htmlspecialchars($doctor['specialization'] ?? '') ?></td>
                    </tr>
                    <tr>
                        <th>Clinic</th>
                        <td><?= htmlspecialchars($doctor['clinic'] ?? '') ?></td>
                        <th>Signature</th>
                        <td><?= !empty($doctor['signature']) ? 'Uploaded' : 'Not uploaded' ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($flash_success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($flash_success) ?></div>
    <?php endif; ?>
    <?php if ($flash_error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($flash_error) ?></div>
    <?php endif; ?>

    <div class="dash-card">
        <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
            <h2 class="h5 fw-bold mb-0">Appointments</h2>
            <span class="text-muted small">Updates refresh automatically</span>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0">
                <thead>
                    <tr>
                        <th>Patient</th>
                        <th>Age</th>
                        <th>Gender</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th>Call</th>
                        <th>Prescription</th>
                        <th>Remove</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($appointments->num_rows > 0): ?>
                        <?php while ($row = $appointments->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['patient_name']) ?></td>
                                <td><?= htmlspecialchars((string) ($row['patient_age'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string) ($row['patient_gender'] ?? '')) ?></td>
                                <td><?= htmlspecialchars(date('d M Y', strtotime($row['appointment_date']))) ?></td>
                                <td><?= htmlspecialchars(date('h:i A', strtotime($row['appointment_time']))) ?></td>
                                <td>
                                    <select class="form-select form-select-sm status-select" data-id="<?= (int) $row['id'] ?>">
                                        <?php foreach (['pending', 'confirmed', 'completed', 'cancelled'] as $status): ?>
                                            <option value="<?= $status ?>" <?= $row['status'] === $status ? 'selected' : '' ?>><?= ucfirst($status) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <?php if (($row['payment_status'] ?? 'unpaid') === 'paid'): ?>
                                        <span class="badge bg-success">Paid</span>
                                        <?php if (!empty($row['payment_amount'])): ?>
                                            <div class="text-muted small">₹<?= number_format((float)$row['payment_amount'], 2) ?></div>
                                        <?php endif; ?>
                                        <div class="mt-1">
                                            <a class="btn btn-sm btn-outline-primary" href="appointment_bill.php?appointment_id=<?= (int)$row['id'] ?>">Bill</a>
                                        </div>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Unpaid</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($row['status'] === 'confirmed'): ?>
                                        <a class="btn btn-sm btn-primary" href="video.php?appointment_id=<?= (int) $row['id'] ?>">Start</a>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php $rx = parse_digital_prescription($row['prescription'] ?? ''); ?>
                                    <?php if ($rx): ?>
                                        <span class="badge-status status-completed">Completed</span>
                                        <a class="btn btn-sm btn-success ms-1 mt-1 mt-md-0" href="prescription.php?appointment_id=<?= (int) $row['id'] ?>">View</a>
                                    <?php else: ?>
                                        <a class="btn btn-sm btn-outline-primary" href="add_prescription.php?appointment_id=<?= (int) $row['id'] ?>"><?= !empty($row['prescription']) ? 'Fix' : 'Add' ?></a>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="POST" action="archive_appointment.php" onsubmit="return confirm('Remove this appointment from dashboard?');">
                                        <input type="hidden" name="appointment_id" value="<?= (int) $row['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="10" class="text-center text-muted">No appointments found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="dash-card">
        <div class="d-flex justify-content-between align-items-center gap-2 mb-3 flex-wrap">
            <h2 class="h5 fw-bold mb-0">Medicine Orders</h2>
            <a class="btn btn-sm btn-outline-primary" href="storeindex.php">Buy Medicine</a>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th class="text-end">Amount</th>
                        <th>Bill</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (isset($store_orders) && $store_orders && $store_orders->num_rows > 0): ?>
                        <?php while ($o = $store_orders->fetch_assoc()): ?>
                            <tr>
                                <td>#<?= (int) $o['id'] ?></td>
                                <td><?= htmlspecialchars(date('d M Y, h:i A', strtotime((string) $o['created_at']))) ?></td>
                                <td><?= htmlspecialchars((string) $o['status']) ?></td>
                                <td class="text-end fw-bold">₹<?= number_format((float) $o['amount'], 2) ?></td>
                                <td><a class="btn btn-sm btn-outline-primary" href="bill.php?order_id=<?= (int) $o['id'] ?>">Download</a></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center text-muted">No medicine orders yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    document.querySelectorAll('.status-select').forEach(function (select) {
        select.addEventListener('change', function () {
            const body = new URLSearchParams({
                appointment_id: this.dataset.id,
                status: this.value
            });

            this.disabled = true;
            fetch('update_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body.toString()
            }).finally(() => {
                this.disabled = false;
                window.location.reload();
            });
        });
    });

    setTimeout(function () {
        if (document.visibilityState === 'visible') {
            window.location.reload();
        }
    }, 15000);
</script>
</body>
</html>
