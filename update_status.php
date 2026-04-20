<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    echo "Unauthorized";
    exit();
}

if(isset($_POST['appointment_id']) && isset($_POST['status'])) {
    $doctor_id = $_SESSION['user_id'];
    $appointment_id = intval($_POST['appointment_id']);
    $status = $_POST['status'];

    $valid_statuses = ['pending','confirmed','completed','cancelled'];
    if(!in_array($status, $valid_statuses)){
        echo "Invalid status";
        exit();
    }

    $conn = new mysqli("localhost","root","","medi");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    $stmt = $conn->prepare("UPDATE appointments SET status=? WHERE id=? AND doctor_id=?");
    $stmt->bind_param("sii", $status, $appointment_id, $doctor_id);
    if($stmt->execute()){
        echo "Status updated successfully!";
    } else {
        echo "Error updating status: " . $conn->error;
    }
    $stmt->close();
    $conn->close();
} else {
    echo "Invalid request";
}
?>
