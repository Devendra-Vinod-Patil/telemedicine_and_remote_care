<?php
session_start();
include 'database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'doctor') {
    echo 'Unauthorized';
    exit();
}

if (!isset($_POST['appointment_id'], $_POST['status'])) {
    echo 'Invalid request';
    exit();
}

$doctor_id = (int) $_SESSION['user_id'];
$appointment_id = (int) $_POST['appointment_id'];
$status = $_POST['status'];

$valid_statuses = ['pending', 'confirmed', 'completed', 'cancelled'];
if (!in_array($status, $valid_statuses, true)) {
    echo 'Invalid status';
    exit();
}

$stmt = $conn->prepare('UPDATE appointments SET status = ? WHERE id = ? AND doctor_id = ?');
$stmt->bind_param('sii', $status, $appointment_id, $doctor_id);
$stmt->execute();
$stmt->close();

echo 'OK';
?>
