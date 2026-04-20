<?php
session_start();
include 'database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    die("Unauthorized access");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['appointment_id'])) {
    header("Location: doctors_dashboard.php?upload=error");
    exit();
}

$doctor_id = $_SESSION['user_id'];
$appointment_id = intval($_POST['appointment_id']);

$patient_name = trim($_POST['patient_name'] ?? '');
$age = isset($_POST['age']) && $_POST['age'] !== '' ? intval($_POST['age']) : null;
$gender = strtolower(trim($_POST['gender'] ?? ''));
$diagnosis = trim($_POST['diagnosis'] ?? '');
$note_advice = trim($_POST['note_advice'] ?? '');

if ($patient_name === '' || $diagnosis === '') {
    header("Location: doctors_dashboard.php?upload=error");
    exit();
}

if ($age !== null && ($age < 0 || $age > 130)) {
    header("Location: doctors_dashboard.php?upload=error");
    exit();
}

$allowed_genders = ['male', 'female', 'other', ''];
if (!in_array($gender, $allowed_genders, true)) {
    header("Location: doctors_dashboard.php?upload=error");
    exit();
}

$medicine_names = $_POST['medicine_name'] ?? [];
$medicine_doses = $_POST['medicine_dose'] ?? [];
$medicine_durations = $_POST['medicine_duration'] ?? [];

$medicines = [];
$max_count = max(count($medicine_names), count($medicine_doses), count($medicine_durations));

for ($i = 0; $i < $max_count; $i++) {
    $name = trim($medicine_names[$i] ?? '');
    $dose = trim($medicine_doses[$i] ?? '');
    $duration = trim($medicine_durations[$i] ?? '');

    if ($name === '' && $dose === '' && $duration === '') {
        continue;
    }

    if ($name === '' || $dose === '' || $duration === '') {
        header("Location: doctors_dashboard.php?upload=error");
        exit();
    }

    $medicines[] = [
        'name' => $name,
        'dose' => $dose,
        'duration' => $duration
    ];
}

if (empty($medicines)) {
    header("Location: doctors_dashboard.php?upload=error");
    exit();
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

$prescription_data = json_encode($payload, JSON_UNESCAPED_UNICODE);
if ($prescription_data === false) {
    header("Location: doctors_dashboard.php?upload=error");
    exit();
}

$stmt = $conn->prepare(
    "UPDATE appointments 
     SET prescription = ?, status = 'completed'
     WHERE id = ? AND doctor_id = ?"
);

if (!$stmt) {
    header("Location: doctors_dashboard.php?upload=error");
    exit();
}

$stmt->bind_param("sii", $prescription_data, $appointment_id, $doctor_id);
$stmt->execute();
$updated = $stmt->affected_rows > 0;
$stmt->close();

header("Location: doctors_dashboard.php?upload=" . ($updated ? 'success' : 'error'));
exit();
