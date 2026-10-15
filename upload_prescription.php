<?php
session_start();
include 'database.php';

function redirect_upload_error(string $code = 'error'): void
{
    $_SESSION['flash_error'] = $code === 'validation_error'
        ? 'Please fill all prescription fields.'
        : 'Could not save prescription.';
    header('Location: doctors_dashboard.php');
    exit();
}

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

function ensure_prescription_storage(mysqli $conn): void
{
    $result = $conn->query("SHOW COLUMNS FROM `appointments` LIKE 'prescription'");
    $column = $result ? $result->fetch_assoc() : null;
    $type = strtolower((string) ($column['Type'] ?? ''));

    if ($type !== 'longtext') {
        $conn->query("ALTER TABLE `appointments` MODIFY `prescription` LONGTEXT NULL");
    }
}

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'doctor') {
    die('Unauthorized access');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['appointment_id'])) {
    redirect_upload_error('invalid_request');
}

ensure_prescription_storage($conn);

$doctor_id = (int) $_SESSION['user_id'];
$appointment_id = (int) $_POST['appointment_id'];

$patient_name = trim($_POST['patient_name'] ?? '');
$age = isset($_POST['age']) && $_POST['age'] !== '' ? (int) $_POST['age'] : null;
$gender = strtolower(trim($_POST['gender'] ?? ''));
$diagnosis = trim($_POST['diagnosis'] ?? '');
$note_advice = trim($_POST['note_advice'] ?? '');
$tests_reports = trim($_POST['tests_reports'] ?? '');

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
$medicine_timings = $_POST['medicine_timing'] ?? [];
$medicine_foods = $_POST['medicine_food'] ?? [];
$medicine_durations = $_POST['medicine_duration'] ?? [];

if (
    !is_array($medicine_names) ||
    !is_array($medicine_doses) ||
    !is_array($medicine_timings) ||
    !is_array($medicine_foods) ||
    !is_array($medicine_durations)
) {
    redirect_upload_error('validation_error');
}

$counts = [
    count($medicine_names),
    count($medicine_doses),
    count($medicine_timings),
    count($medicine_foods),
    count($medicine_durations)
];

if (count(array_unique($counts)) !== 1) {
    redirect_upload_error('validation_error');
}

$timing_labels = [
    '1-0-1' => 'Morning and night',
    '1-1-1' => 'Morning-Afternoon-Night',
    '1-0-0' => 'Morning only',
    '0-1-0' => 'Afternoon only',
    '0-0-1' => 'Night only',
    '1-1-0' => 'Morning-Afternoon',
    '0-1-1' => 'Afternoon-Night',
    'SOS' => 'When needed'
];

$food_labels = [
    'before_food' => 'Before food',
    'after_food' => 'After food',
    'with_food' => 'With food',
    'anytime' => 'Any time'
];

$medicines = [];
for ($i = 0; $i < $counts[0]; $i++) {
    $name = trim($medicine_names[$i] ?? '');
    $dose = trim($medicine_doses[$i] ?? '');
    $timing = trim($medicine_timings[$i] ?? '');
    $food = trim($medicine_foods[$i] ?? '');
    $duration = trim($medicine_durations[$i] ?? '');

    if ($name === '' && $dose === '' && $timing === '' && $food === '' && $duration === '') {
        continue;
    }

    if ($name === '' || $dose === '' || $timing === '' || $food === '' || $duration === '') {
        redirect_upload_error('validation_error');
    }

    if (!isset($timing_labels[$timing]) || !isset($food_labels[$food])) {
        redirect_upload_error('validation_error');
    }

    $medicines[] = [
        'name' => $name,
        'dose' => $dose,
        'timing' => $timing,
        'timing_label' => $timing_labels[$timing],
        'food' => $food,
        'food_label' => $food_labels[$food],
        'duration' => $duration
    ];
}

if (empty($medicines)) {
    redirect_upload_error('validation_error');
}

$doctor_columns = get_columns($conn, 'doctors');
$doctor_select = [
    'full_name',
    'specialization',
    'clinic'
];
if (in_array('signature', $doctor_columns, true)) {
    $doctor_select[] = 'signature';
}
if (in_array('photo', $doctor_columns, true)) {
    $doctor_select[] = 'photo';
}

$doctor_query = 'SELECT ' . implode(', ', $doctor_select) . ' FROM doctors WHERE id = ? LIMIT 1';
$doctor_stmt = $conn->prepare($doctor_query);
if (!$doctor_stmt) {
    redirect_upload_error('db_error');
}
$doctor_stmt->bind_param('i', $doctor_id);
$doctor_stmt->execute();
$doctor = $doctor_stmt->get_result()->fetch_assoc();
$doctor_stmt->close();

if (!$doctor) {
    redirect_upload_error('db_error');
}

$payload = [
    'type' => 'digital',
    'patient_name' => $patient_name,
    'age' => $age,
    'gender' => $gender,
    'diagnosis' => $diagnosis,
    'medicines' => $medicines,
    'tests_reports' => $tests_reports,
    'note_advice' => $note_advice,
    'doctor_name' => $doctor['full_name'] ?? '',
    'doctor_specialization' => $doctor['specialization'] ?? '',
    'doctor_clinic' => $doctor['clinic'] ?? '',
    'doctor_signature' => $doctor['signature'] ?? '',
    'doctor_photo' => $doctor['photo'] ?? '',
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

$stmt->bind_param('sii', $prescription_data, $appointment_id, $doctor_id);
$stmt->execute();
$updated = $stmt->affected_rows > 0;
$stmt->close();

if ($updated) {
    $_SESSION['flash_success'] = 'Prescription saved successfully.';
} else {
    $_SESSION['flash_error'] = 'Could not save prescription.';
}

header('Location: doctors_dashboard.php');
exit();
?>
