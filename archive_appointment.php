<?php
session_start();
include 'database.php';

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

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['appointment_id'])) {
    header('Location: ' . ($_SESSION['role'] === 'doctor' ? 'doctors_dashboard.php' : 'patient_dashboard.php'));
    exit();
}

$appointment_id = (int) $_POST['appointment_id'];
$user_id = (int) $_SESSION['user_id'];
$role = $_SESSION['role'];

ensure_column($conn, 'appointments', 'patient_hidden', 'TINYINT(1) NOT NULL DEFAULT 0');
ensure_column($conn, 'appointments', 'doctor_hidden', 'TINYINT(1) NOT NULL DEFAULT 0');

if ($role === 'patient') {
    $stmt = $conn->prepare('UPDATE appointments SET patient_hidden = 1 WHERE id = ? AND patient_id = ?');
    $stmt->bind_param('ii', $appointment_id, $user_id);
    $redirect = 'patient_dashboard.php';
} else {
    $stmt = $conn->prepare('UPDATE appointments SET doctor_hidden = 1 WHERE id = ? AND doctor_id = ?');
    $stmt->bind_param('ii', $appointment_id, $user_id);
    $redirect = 'doctors_dashboard.php';
}

$stmt->execute();
$stmt->close();

$_SESSION['flash_success'] = 'Appointment removed from dashboard.';
header('Location: ' . $redirect);
exit();
?>
