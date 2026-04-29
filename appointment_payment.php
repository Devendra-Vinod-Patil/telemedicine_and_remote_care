<?php
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'patient') {
    header('Location: login.php');
    exit();
}

include 'database.php';
include 'store_config.php';
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

$patient_id = (int) $_SESSION['user_id'];
$stmt = $conn->prepare(
    "SELECT a.id, a.status, a.appointment_date, a.appointment_time, a.payment_status, a.payment_amount,
            d.full_name AS doctor_name, d.specialization
     FROM appointments a
     JOIN doctors d ON a.doctor_id = d.id
     WHERE a.id = ? AND a.patient_id = ?
     LIMIT 1"
);
$stmt->bind_param('ii', $appointment_id, $patient_id);
$stmt->execute();
$appointment = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$appointment) {
    die('Appointment not found.');
}

// Payment allowed only after appointment completion.
if (($appointment['status'] ?? '') !== 'completed') {
    $_SESSION['flash_error'] = 'You can pay only after the appointment is completed.';
    header('Location: patient_dashboard.php');
    exit();
}

if (($appointment['payment_status'] ?? 'unpaid') === 'paid') {
    header('Location: prescription.php?appointment_id=' . (int) $appointment_id);
    exit();
}

$amount_inr = (float) CONSULTATION_FEE_INR;

$page_title = 'Pay Consultation';
include 'header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4 p-md-5">
                    <h2 class="fw-bold mb-1">Consultation Payment</h2>
                    <p class="text-muted mb-4">Pay to download the prescription after appointment completion.</p>

                    <div class="p-3 bg-light rounded-3 mb-4">
                        <div class="row g-2">
                            <div class="col-md-6"><span class="text-muted">Doctor:</span> <span class="fw-semibold"><?= htmlspecialchars((string)$appointment['doctor_name']) ?></span></div>
                            <div class="col-md-6"><span class="text-muted">Specialization:</span> <span class="fw-semibold"><?= htmlspecialchars((string)$appointment['specialization']) ?></span></div>
                            <div class="col-md-6"><span class="text-muted">Date:</span> <span class="fw-semibold"><?= htmlspecialchars(date('d M Y', strtotime((string)$appointment['appointment_date']))) ?></span></div>
                            <div class="col-md-6"><span class="text-muted">Time:</span> <span class="fw-semibold"><?= htmlspecialchars(date('h:i A', strtotime((string)$appointment['appointment_time']))) ?></span></div>
                            <div class="col-12"><span class="text-muted">Amount:</span> <span class="fw-bold text-primary">₹<?= number_format((float)$amount_inr, 2) ?></span></div>
                        </div>
                    </div>

                    <?php if (!defined('RAZORPAY_KEY_ID') || RAZORPAY_KEY_ID === ''): ?>
                        <div class="alert alert-warning">Razorpay key is not configured. Please set it in <code>store_config.php</code>.</div>
                        <a class="btn btn-outline-primary" href="patient_dashboard.php">Back</a>
                    <?php else: ?>
                        <form id="payForm" method="post" action="appointment_pay.php">
                            <input type="hidden" name="appointment_id" value="<?= (int)$appointment_id ?>">
                            <input type="hidden" name="amount" value="<?= htmlspecialchars((string)$amount_inr) ?>">
                            <input type="hidden" name="payment_id" id="payment_id" value="">
                            <button type="button" id="payBtn" class="btn btn-success btn-lg w-100">
                                Pay ₹<?= number_format((float)$amount_inr, 2) ?>
                            </button>
                            <a class="btn btn-link w-100 mt-2" href="patient_dashboard.php">Cancel</a>
                        </form>
                        <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
                        <script>
                        document.getElementById('payBtn').addEventListener('click', function () {
                            const btn = this;
                            btn.disabled = true;
                            const options = {
                                key: <?= json_encode(RAZORPAY_KEY_ID) ?>,
                                amount: <?= json_encode((int)round($amount_inr * 100)) ?>,
                                currency: "INR",
                                name: "Virtual-Chikitsa",
                                description: "Consultation Payment",
                                handler: function (response) {
                                    document.getElementById('payment_id').value = response.razorpay_payment_id || '';
                                    document.getElementById('payForm').submit();
                                },
                                theme: { color: "#1d4ed8" }
                            };
                            const rzp = new Razorpay(options);
                            rzp.on('payment.failed', function () {
                                alert('Payment failed.');
                                btn.disabled = false;
                            });
                            rzp.open();
                        });
                        </script>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
