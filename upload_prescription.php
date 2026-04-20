<?php
session_start();
include 'database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    die("Unauthorized access");
}

if (isset($_FILES['prescription']) && isset($_POST['appointment_id'])) {

    $appointment_id = intval($_POST['appointment_id']);
    $upload_dir = "uploads/prescriptions/";

    // Create folder if not exists
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Generate unique filename
    $file_name = time() . "_" . basename($_FILES["prescription"]["name"]);
    $target_path = $upload_dir . $file_name;

    // Allow only safe file types
    $allowed_types = ['application/pdf', 'image/jpeg', 'image/png'];

    if (!in_array($_FILES['prescription']['type'], $allowed_types)) {
        die("Invalid file type. Only PDF, JPG, PNG allowed.");
    }

    // Upload file
    if (move_uploaded_file($_FILES["prescription"]["tmp_name"], $target_path)) {

        // 🔥 UPDATE PRESCRIPTION + STATUS
        $stmt = $conn->prepare(
            "UPDATE appointments 
             SET prescription = ?, status = 'completed' 
             WHERE id = ?"
        );

        if (!$stmt) {
            die("SQL Error: " . $conn->error);
        }

        $stmt->bind_param("si", $target_path, $appointment_id);
        $stmt->execute();
        $stmt->close();

        // Redirect back safely (no hardcoded file name)
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }
}

echo "Upload failed";
