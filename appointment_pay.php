<?php
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'patient') {
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: patient_dashboard.php');
    exit();
}

$appointment_id = (int)($_POST['appointment_id'] ?? 0);
$payment_id = trim((string)($_POST['payment_id'] ?? ''));
$amount = (float) CONSULTATION_FEE_INR;

if ($appointment_id <= 0 || $payment_id === '' || $amount <= 0) {
    $_SESSION['flash_error'] = 'Payment failed: missing payment info.';
    header('Location: patient_dashboard.php');
    exit();
}

$patient_id = (int) $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT id, status FROM appointments WHERE id = ? AND patient_id = ? LIMIT 1");
$stmt->bind_param('ii', $appointment_id, $patient_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$exists = (bool)$row;
$stmt->close();

if (!$exists) {
    $_SESSION['flash_error'] = 'Payment failed: appointment not found.';
    header('Location: patient_dashboard.php');
    exit();
}

if (($row['status'] ?? '') !== 'completed') {
    $_SESSION['flash_error'] = 'Payment is allowed only after appointment completion.';
    header('Location: patient_dashboard.php');
    exit();
}

$stmt = $conn->prepare("
    UPDATE appointments
    SET payment_status = 'paid', payment_id = ?, payment_amount = ?, paid_at = NOW()
    WHERE id = ? AND patient_id = ?
");
$stmt->bind_param('sdii', $payment_id, $amount, $appointment_id, $patient_id);
$ok = $stmt->execute();
$stmt->close();

if ($ok) {
    $_SESSION['flash_success'] = 'Payment successful. You can download your prescription now.';
    header('Location: prescription.php?appointment_id=' . (int)$appointment_id);
    exit();
}

$_SESSION['flash_error'] = 'Payment failed due to database error.';
header('Location: patient_dashboard.php');
