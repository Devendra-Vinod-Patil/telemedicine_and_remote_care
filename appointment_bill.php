<?php
session_start();

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header('Location: login.php');
    exit();
}

include 'database.php';
include 'appointment_config.php';

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

ensure_column($conn, 'appointments', 'payment_status', "VARCHAR(20) NOT NULL DEFAULT 'unpaid'");
ensure_column($conn, 'appointments', 'payment_id', "VARCHAR(100) NULL");
ensure_column($conn, 'appointments', 'payment_amount', "DECIMAL(10,2) NULL");
ensure_column($conn, 'appointments', 'paid_at', "TIMESTAMP NULL DEFAULT NULL");

$appointment_id = isset($_GET['appointment_id']) ? (int) $_GET['appointment_id'] : 0;
if ($appointment_id <= 0) {
    die('Invalid appointment.');
}

$role = (string) $_SESSION['role'];
$user_id = (int) $_SESSION['user_id'];

$where = $role === 'doctor' ? 'a.doctor_id = ?' : 'a.patient_id = ?';

$stmt = $conn->prepare(
    "SELECT a.id, a.appointment_date, a.appointment_time, a.status,
            COALESCE(a.payment_status, 'unpaid') AS payment_status,
            a.payment_id, a.payment_amount, a.paid_at,
            d.full_name AS doctor_name, d.specialization, d.clinic,
            p.full_name AS patient_name, p.phone AS patient_phone
     FROM appointments a
     JOIN doctors d ON a.doctor_id = d.id
     JOIN patients p ON a.patient_id = p.id
     WHERE a.id = ? AND {$where}
     LIMIT 1"
);
$stmt->bind_param('ii', $appointment_id, $user_id);
$stmt->execute();
$appt = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$appt) {
    die('Bill not found.');
}

if (($appt['payment_status'] ?? 'unpaid') !== 'paid') {
    die('Bill available only after payment.');
}

$amount = (float)($appt['payment_amount'] ?? 0);
if ($amount <= 0) {
    $amount = (float) CONSULTATION_FEE_INR;
}

$page_title = 'Consultation Bill';
include 'header.php';
?>
<style>
    @media print {
        .no-print { display: none !important; }
        body { background: #fff !important; }
    }
</style>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center gap-2 mb-3 no-print flex-wrap">
        <div>
            <h1 class="fw-bold mb-0">Invoice / Bill</h1>
            <div class="text-muted">Appointment #<?= (int)$appointment_id ?></div>
        </div>
        <div class="d-flex gap-2">
            <?php if ($role === 'doctor'): ?>
                <a class="btn btn-outline-primary" href="doctors_dashboard.php">Dashboard</a>
            <?php else: ?>
                <a class="btn btn-outline-primary" href="patient_dashboard.php">Dashboard</a>
            <?php endif; ?>
            <button class="btn btn-primary" type="button" onclick="window.print()">Download / Print</button>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4 p-md-5">
            <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-4">
                <div>
                    <h4 class="fw-bold mb-1">Virtual-Chikitsa</h4>
                    <div class="text-muted">Consultation / Prescription Payment</div>
                </div>
                <div class="text-md-end">
                    <div><span class="text-muted">Paid at:</span> <?= htmlspecialchars(date('d M Y, h:i A', strtotime((string)($appt['paid_at'] ?? $appt['appointment_date'])))) ?></div>
                    <div><span class="text-muted">Status:</span> Paid</div>
                    <?php if (!empty($appt['payment_id'])): ?>
                        <div><span class="text-muted">Payment ID:</span> <?= htmlspecialchars((string)$appt['payment_id']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded-3">
                        <div class="fw-bold mb-1">Billed To</div>
                        <div><?= htmlspecialchars((string)$appt['patient_name']) ?></div>
                        <?php if (!empty($appt['patient_phone'])): ?>
                            <div class="text-muted small">Phone: <?= htmlspecialchars((string)$appt['patient_phone']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded-3">
                        <div class="fw-bold mb-1">Doctor</div>
                        <div><?= htmlspecialchars((string)$appt['doctor_name']) ?></div>
                        <div class="text-muted small"><?= htmlspecialchars((string)$appt['specialization']) ?></div>
                        <?php if (!empty($appt['clinic'])): ?>
                            <div class="text-muted small"><?= htmlspecialchars((string)$appt['clinic']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Description</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Consultation Fee</td>
                            <td><?= htmlspecialchars(date('d M Y', strtotime((string)$appt['appointment_date']))) ?></td>
                            <td><?= htmlspecialchars(date('h:i A', strtotime((string)$appt['appointment_time']))) ?></td>
                            <td class="text-end fw-bold">₹<?= number_format((float)$amount, 2) ?></td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3" class="text-end">Total</th>
                            <th class="text-end">₹<?= number_format((float)$amount, 2) ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>
