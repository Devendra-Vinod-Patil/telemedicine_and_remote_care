<?php
require_once 'database.php';

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

function normalize_text(string $value): string
{
    return trim(preg_replace('/\s+/', ' ', $value));
}

function upload_image(string $field_name, string $target_dir): ?string
{
    if (empty($_FILES[$field_name]['name']) || !is_uploaded_file($_FILES[$field_name]['tmp_name'])) {
        return null;
    }

    $extension = strtolower(pathinfo($_FILES[$field_name]['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($extension, $allowed, true)) {
        throw new RuntimeException('Only JPG, PNG, and WEBP images are allowed.');
    }

    if (!is_dir($target_dir) && !mkdir($target_dir, 0777, true) && !is_dir($target_dir)) {
        throw new RuntimeException('Unable to create upload directory.');
    }

    $filename = uniqid('upload_', true) . '.' . $extension;
    $relative_path = rtrim(str_replace('\\', '/', $target_dir), '/') . '/' . $filename;

    if (!move_uploaded_file($_FILES[$field_name]['tmp_name'], $relative_path)) {
        throw new RuntimeException('Could not upload file.');
    }

    return $relative_path;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: registration.html');
    exit();
}

$role = $_POST['role'] ?? '';
$full_name = normalize_text($_POST['fullName'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = normalize_text($_POST['phone'] ?? '');
$password_raw = $_POST['password'] ?? '';

if (!in_array($role, ['patient', 'doctor'], true) || $full_name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $password_raw === '') {
    echo "<script>alert('Please fill all required fields correctly.'); window.history.back();</script>";
    exit();
}

$password = password_hash($password_raw, PASSWORD_BCRYPT);

try {
    if ($role === 'patient') {
        ensure_column($conn, 'patients', 'age', 'INT NULL AFTER `phone`');
        ensure_column($conn, 'patients', 'gender', "VARCHAR(20) NULL AFTER `age`");

        $age = isset($_POST['age']) && $_POST['age'] !== '' ? (int) $_POST['age'] : null;
        $gender = strtolower(trim($_POST['gender'] ?? ''));
        $allowed_genders = ['male', 'female', 'other', ''];

        if (($age !== null && ($age < 0 || $age > 130)) || !in_array($gender, $allowed_genders, true)) {
            throw new RuntimeException('Please enter a valid age and gender.');
        }

        $sql = 'INSERT INTO patients (full_name, email, phone, age, gender, password) VALUES (?, ?, ?, ?, ?, ?)';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sssiss', $full_name, $email, $phone, $age, $gender, $password);
    } else {
        ensure_column($conn, 'doctors', 'signature', "VARCHAR(255) NULL AFTER `photo`");

        $specialization = normalize_text($_POST['specialization'] ?? '');
        $experience = isset($_POST['experience']) && $_POST['experience'] !== '' ? (int) $_POST['experience'] : 0;
        $clinic = normalize_text($_POST['clinic'] ?? '');

        if ($specialization === '' || $clinic === '') {
            throw new RuntimeException('Please complete the doctor information section.');
        }

        $photo = upload_image('photo', 'uploads');
        $signature = upload_image('signature', 'uploads/signatures');

        $sql = 'INSERT INTO doctors (full_name, email, phone, password, specialization, experience, clinic, photo, signature) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sssssisss', $full_name, $email, $phone, $password, $specialization, $experience, $clinic, $photo, $signature);
    }

    if (!$stmt) {
        throw new RuntimeException($conn->error);
    }

    if ($stmt->execute()) {
        echo "<script>alert('Account created successfully. Please login to continue.'); window.location='login.php';</script>";
    } else {
        throw new RuntimeException($stmt->error);
    }

    $stmt->close();
    $conn->close();
} catch (Throwable $e) {
    $message = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    echo "<script>alert('{$message}'); window.history.back();</script>";
}
?>
