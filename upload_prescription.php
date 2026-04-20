<?php
session_start();
include 'database.php';

function redirect_upload_error($code = 'error') {
    header("Location: doctors_dashboard.php?upload=" . urlencode($code));
    exit();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    die("Unauthorized access");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['appointment_id'])) {
    redirect_upload_error('invalid_request');
}

$doctor_id = $_SESSION['user_id'];
$appointment_id = intval($_POST['appointment_id']);

$patient_name = trim($_POST['patient_name'] ?? '');
$age = isset($_POST['age']) && $_POST['age'] !== '' ? intval($_POST['age']) : null;
$gender = strtolower(trim($_POST['gender'] ?? ''));
$diagnosis = trim($_POST['diagnosis'] ?? '');
$note_advice = trim($_POST['note_advice'] ?? '');

if ($patient_name === '' || $diagnosis === '') {
    redirect_upload_error('validation_error');
}

if ($age !== null && ($age < 0 || $age > 130)) {
    redirect_upload_error('validation_error');
}

$allowed_genders = ['male', 'female', 'other', ''];
if (!in_array($gender, $allowed_genders, true)) {
    redirect_upload_error('validation_error');
}

$medicine_names = $_POST['medicine_name'] ?? [];
$medicine_doses = $_POST['medicine_dose'] ?? [];
$medicine_durations = $_POST['medicine_duration'] ?? [];

if (!is_array($medicine_names) || !is_array($medicine_doses) || !is_array($medicine_durations)) {
    redirect_upload_error('validation_error');
}

$names_count = count($medicine_names);
$doses_count = count($medicine_doses);
$durations_count = count($medicine_durations);
if ($names_count !== $doses_count || $doses_count !== $durations_count) {
    redirect_upload_error('validation_error');
}

$medicines = [];
$max_count = $names_count;

for ($i = 0; $i < $max_count; $i++) {
    $name = trim($medicine_names[$i] ?? '');
    $dose = trim($medicine_doses[$i] ?? '');
    $duration = trim($medicine_durations[$i] ?? '');

    if ($name === '' && $dose === '' && $duration === '') {
        continue;
    }

    if ($name === '' || $dose === '' || $duration === '') {
        redirect_upload_error('validation_error');
    }

    $medicines[] = [
        'name' => $name,
        'dose' => $dose,
        'duration' => $duration
    ];
}

if (empty($medicines)) {
    redirect_upload_error('validation_error');
}

$payload = [
    'type' => 'digital',
    'patient_name' => $patient_name,
    'age' => $age,
    'gender' => $gender,
    'diagnosis' => $diagnosis,
    'medicines' => $medicines,
    'note_advice' => $note_advice,
    'created_at' => date('c')
];

try {
    $prescription_data = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    redirect_upload_error('encoding_error');
}

$stmt = $conn->prepare(
    "UPDATE appointments 
     SET prescription = ?, status = 'completed'
     WHERE id = ? AND doctor_id = ?"
);

if (!$stmt) {
    redirect_upload_error('db_error');
}

$stmt->bind_param("sii", $prescription_data, $appointment_id, $doctor_id);
$stmt->execute();
$updated = $stmt->affected_rows > 0;
$stmt->close();

header("Location: doctors_dashboard.php?upload=" . ($updated ? 'success' : 'error'));
exit();
