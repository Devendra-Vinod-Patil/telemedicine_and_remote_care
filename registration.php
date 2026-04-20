<?php
require_once "database.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $role = $_POST['role'];
    $fullName = $_POST['fullName'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    if ($role === "patient") {
        $sql = "INSERT INTO patients (full_name, email, phone, password) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $fullName, $email, $phone, $password);

    } elseif ($role === "doctor") {
        $specialization = $_POST['specialization'];
        $experience = $_POST['experience'];
        $clinic = $_POST['clinic'];

        // Upload photo if provided
        $photo = null;
        if (!empty($_FILES['photo']['name'])) {
            $targetDir = "uploads/";
            if (!is_dir($targetDir)) { mkdir($targetDir, 0777, true); }
            $photo = $targetDir . time() . "_" . basename($_FILES["photo"]["name"]);
            move_uploaded_file($_FILES["photo"]["tmp_name"], $photo);
        }

        $sql = "INSERT INTO doctors (full_name, email, phone, password, specialization, experience, clinic, photo) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssss", $fullName, $email, $phone, $password, $specialization, $experience, $clinic, $photo);
    }

    if ($stmt->execute()) {
        echo "<script>alert('Account Created Successfully!'); window.location='login.php';</script>";
    } else {
        echo "❌ Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>
