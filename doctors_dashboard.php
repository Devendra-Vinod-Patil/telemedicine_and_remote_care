<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: login.php");
    exit();
}

include 'database.php';
include 'header.php';

$doctor_id = $_SESSION['user_id'];

/* ======================
   FETCH DOCTOR INFO
====================== */
$stmt = $conn->prepare(
    "SELECT full_name, specialization, photo, clinic, experience 
     FROM doctors WHERE id = ?"
);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$doctor = $stmt->get_result()->fetch_assoc();
$stmt->close();

/* ======================
   FETCH APPOINTMENTS
====================== */
$stmt = $conn->prepare(
    "SELECT a.id, a.appointment_date, a.appointment_time,
            a.status, a.prescription, a.room_id,
            p.full_name AS patient_name
     FROM appointments a
     JOIN patients p ON a.patient_id = p.id
     WHERE a.doctor_id = ?
     ORDER BY a.appointment_date, a.appointment_time"
);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$appointments = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Doctor Dashboard</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
body { background:#f5f2eb; font-family:'Lato',sans-serif; }
.card { border-radius:15px; box-shadow:0 5px 15px rgba(0,0,0,.05); }
.section-title { border-left:5px solid #e2725b; padding-left:10px; font-weight:700; }
.doctor-img { width:120px;height:120px;border-radius:50%;border:4px solid #e2725b;object-fit:cover; }
</style>
</head>

<body>
<div class="container py-5">

<!-- ================= HEADER ================= -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold">Doctor Dashboard</h3>
    <a href="logout.php" class="btn btn-outline-danger btn-sm">
        <i class="fas fa-sign-out-alt me-1"></i> Logout
    </a>
</div>

<!-- ================= PROFILE ================= -->
<div class="card p-4 mb-4">
<div class="row align-items-center">
    <div class="col-md-2 text-center">
        <?php
        $photo = (!empty($doctor['photo']) && file_exists($doctor['photo']))
                 ? $doctor['photo']
                 : "https://via.placeholder.com/120x120/2f3e46/ffffff?text=DR";
        ?>
        <img src="<?= $photo ?>" class="doctor-img">
    </div>
    <div class="col-md-10">
        <h4 class="fw-bold"><?= htmlspecialchars($doctor['full_name']) ?></h4>
        <p class="mb-1"><i class="fas fa-stethoscope me-2"></i><?= htmlspecialchars($doctor['specialization']) ?></p>
        <p class="mb-1"><i class="fas fa-hospital me-2"></i><?= htmlspecialchars($doctor['clinic']) ?></p>
        <p><i class="fas fa-award me-2"></i><?= $doctor['experience'] ?> years experience</p>
    </div>
</div>
</div>

<!-- ================= APPOINTMENTS ================= -->
<div class="card p-4">
<h4 class="section-title mb-3">Appointments</h4>

<!-- ✅ SUCCESS MESSAGE -->
<?php if (isset($_GET['upload']) && $_GET['upload'] === 'success'): ?>
<div class="alert alert-success alert-dismissible fade show">
    <i class="fas fa-check-circle me-2"></i>
    Prescription uploaded successfully. Appointment marked as completed.
    <button class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($appointments->num_rows > 0): ?>

<!-- DESKTOP -->
<div class="table-responsive d-none d-md-block">
<table class="table table-hover align-middle">
<thead class="table-light">
<tr>
    <th>Patient</th>
    <th>Date</th>
    <th>Time</th>
    <th>Status</th>
    <th>Action</th>
    <th>Prescription</th>
</tr>
</thead>
<tbody>

<?php while($row = $appointments->fetch_assoc()): ?>
<tr>
<td><?= htmlspecialchars($row['patient_name']) ?></td>
<td><?= date("d M Y", strtotime($row['appointment_date'])) ?></td>
<td><?= date("h:i A", strtotime($row['appointment_time'])) ?></td>

<td>
<select class="form-select status-select" data-id="<?= $row['id'] ?>">
    <?php foreach (['pending','confirmed','completed','cancelled'] as $s): ?>
        <option value="<?= $s ?>" <?= $row['status']===$s?'selected':'' ?>>
            <?= ucfirst($s) ?>
        </option>
    <?php endforeach; ?>
</select>
</td>

<td>
<?php if ($row['status'] === 'confirmed'): ?>
<a href="video.php?appointment_id=<?= $row['id'] ?>" class="btn btn-primary btn-sm">
<i class="fas fa-video me-1"></i> Start Call
</a>
<?php else: ?>
<span class="text-muted">N/A</span>
<?php endif; ?>
</td>

<td>
<?php if (!empty($row['prescription'])): ?>
<span class="badge bg-success">Uploaded</span>
<?php else: ?>
<form action="upload_prescription.php" method="POST" enctype="multipart/form-data">
<input type="hidden" name="appointment_id" value="<?= $row['id'] ?>">
<input type="file" name="prescription" class="form-control form-control-sm mb-1" required>
<button class="btn btn-dark btn-sm w-100">Upload</button>
</form>
<?php endif; ?>
</td>
</tr>
<?php endwhile; ?>

</tbody>
</table>
</div>

<!-- MOBILE -->
<div class="d-md-none">
<?php
$appointments->data_seek(0);
while($row = $appointments->fetch_assoc()):
?>
<div class="card mb-3">
<div class="card-body">
<h6 class="fw-bold"><?= htmlspecialchars($row['patient_name']) ?></h6>
<p><?= date("d M Y", strtotime($row['appointment_date'])) ?> · <?= date("h:i A", strtotime($row['appointment_time'])) ?></p>

<select class="form-select status-select mb-2" data-id="<?= $row['id'] ?>">
<?php foreach (['pending','confirmed','completed','cancelled'] as $s): ?>
<option value="<?= $s ?>" <?= $row['status']===$s?'selected':'' ?>>
<?= ucfirst($s) ?>
</option>
<?php endforeach; ?>
</select>

<?php if ($row['status']==='confirmed'): ?>
<a href="video.php?appointment_id=<?= $row['id'] ?>" class="btn btn-primary btn-sm w-100 mb-2">
Start Call
</a>
<?php endif; ?>

<?php if (!empty($row['prescription'])): ?>
<span class="badge bg-success w-100">Prescription Uploaded</span>
<?php else: ?>
<form action="upload_prescription.php" method="POST" enctype="multipart/form-data">
<input type="hidden" name="appointment_id" value="<?= $row['id'] ?>">
<input type="file" name="prescription" class="form-control form-control-sm mb-2" required>
<button class="btn btn-dark btn-sm w-100">Upload</button>
</form>
<?php endif; ?>
</div>
</div>
<?php endwhile; ?>
</div>

<?php else: ?>
<p class="text-muted text-center">No appointments found</p>
<?php endif; ?>

</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
$('.status-select').change(function(){
    const el = $(this);
    el.prop('disabled', true);

    $.post('update_status.php',{
        appointment_id: el.data('id'),
        status: el.val()
    },()=>el.prop('disabled', false));
});
</script>

</body>
</html>
