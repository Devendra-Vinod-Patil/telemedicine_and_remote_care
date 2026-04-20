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
   CHECK OPTIONAL PATIENT COLUMNS
====================== */
$patient_columns = [];
$columns_result = $conn->query("SHOW COLUMNS FROM patients");
if ($columns_result) {
    while ($column = $columns_result->fetch_assoc()) {
        $patient_columns[] = $column['Field'];
    }
}

$age_select = in_array('age', $patient_columns, true) ? 'p.age AS patient_age' : 'NULL AS patient_age';
$gender_select = in_array('gender', $patient_columns, true) ? 'p.gender AS patient_gender' : 'NULL AS patient_gender';

/* ======================
   FETCH APPOINTMENTS
====================== */
$stmt = $conn->prepare(
    "SELECT a.id, a.appointment_date, a.appointment_time,
            a.status, a.prescription, a.room_id,
            p.full_name AS patient_name,
            {$age_select}, {$gender_select}
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
.medicine-row { border:1px solid #e9ecef; border-radius:8px; padding:10px; margin-bottom:10px; background:#fff; }
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

<?php if (isset($_GET['upload']) && $_GET['upload'] === 'success'): ?>
<div class="alert alert-success alert-dismissible fade show">
    <i class="fas fa-check-circle me-2"></i>
    Digital prescription saved successfully. Appointment marked as completed.
    <button class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php elseif (isset($_GET['upload']) && $_GET['upload'] === 'error'): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <i class="fas fa-times-circle me-2"></i>
    Could not save digital prescription. Please try again.
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
    <th>Digital Prescription</th>
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
<span class="badge bg-success">Saved</span>
<?php else: ?>
<form action="upload_prescription.php" method="POST">
<input type="hidden" name="appointment_id" value="<?= $row['id'] ?>">

<div class="mb-2">
    <label class="form-label mb-1 small">Patient Name</label>
    <input type="text" name="patient_name" class="form-control form-control-sm" value="<?= htmlspecialchars($row['patient_name']) ?>" readonly>
</div>

<div class="row g-2 mb-2">
    <div class="col-6">
        <label class="form-label mb-1 small">Age</label>
        <input type="number" min="0" max="130" name="age" class="form-control form-control-sm" value="<?= htmlspecialchars((string)($row['patient_age'] ?? '')) ?>">
    </div>
    <div class="col-6">
        <label class="form-label mb-1 small">Gender</label>
        <?php $gender = strtolower(trim((string)($row['patient_gender'] ?? ''))); ?>
        <select name="gender" class="form-select form-select-sm">
            <option value="">Select</option>
            <option value="male" <?= $gender === 'male' ? 'selected' : '' ?>>Male</option>
            <option value="female" <?= $gender === 'female' ? 'selected' : '' ?>>Female</option>
            <option value="other" <?= $gender === 'other' ? 'selected' : '' ?>>Other</option>
        </select>
    </div>
</div>

<div class="mb-2">
    <label class="form-label mb-1 small">Diagnosis</label>
    <textarea name="diagnosis" class="form-control form-control-sm" rows="2" required></textarea>
</div>

<div class="mb-2">
    <label class="form-label mb-1 small">Medicine</label>
    <div class="medicine-container" id="medicine-container-<?= $row['id'] ?>">
        <div class="medicine-row">
            <div class="row g-2 align-items-end">
                <div class="col-4">
                    <label class="form-label mb-1 small">Name</label>
                    <input list="medicine-suggest-<?= $row['id'] ?>" name="medicine_name[]" class="form-control form-control-sm" required>
                </div>
                <div class="col-4">
                    <label class="form-label mb-1 small">Dose</label>
                    <input type="text" name="medicine_dose[]" class="form-control form-control-sm" placeholder="1 tablet" required>
                </div>
                <div class="col-4">
                    <label class="form-label mb-1 small">Duration</label>
                    <input type="text" name="medicine_duration[]" class="form-control form-control-sm" placeholder="5 days" required>
                </div>
            </div>
        </div>
    </div>
    <datalist id="medicine-suggest-<?= $row['id'] ?>">
        <option value="Paracetamol"></option>
        <option value="Amoxicillin"></option>
        <option value="Ibuprofen"></option>
        <option value="Cetirizine"></option>
        <option value="Azithromycin"></option>
        <option value="Pantoprazole"></option>
        <option value="Metformin"></option>
        <option value="Amlodipine"></option>
        <option value="Atorvastatin"></option>
        <option value="Omeprazole"></option>
    </datalist>
    <button type="button" class="btn btn-outline-secondary btn-sm mt-1 add-medicine-row" data-target="medicine-container-<?= $row['id'] ?>" data-list="medicine-suggest-<?= $row['id'] ?>">
        + Add Medicine
    </button>
</div>

<div class="mb-2">
    <label class="form-label mb-1 small">Note and Advice</label>
    <textarea name="note_advice" class="form-control form-control-sm" rows="2" placeholder="Diet, hydration, follow-up advice..."></textarea>
</div>

<button class="btn btn-dark btn-sm w-100">Save</button>
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
<span class="badge bg-success w-100">Digital Prescription Saved</span>
<?php else: ?>
<form action="upload_prescription.php" method="POST">
<input type="hidden" name="appointment_id" value="<?= $row['id'] ?>">

<div class="mb-2">
    <label class="form-label mb-1 small">Patient Name</label>
    <input type="text" name="patient_name" class="form-control form-control-sm" value="<?= htmlspecialchars($row['patient_name']) ?>" readonly>
</div>

<div class="row g-2 mb-2">
    <div class="col-6">
        <label class="form-label mb-1 small">Age</label>
        <input type="number" min="0" max="130" name="age" class="form-control form-control-sm" value="<?= htmlspecialchars((string)($row['patient_age'] ?? '')) ?>">
    </div>
    <div class="col-6">
        <label class="form-label mb-1 small">Gender</label>
        <?php $gender = strtolower(trim((string)($row['patient_gender'] ?? ''))); ?>
        <select name="gender" class="form-select form-select-sm">
            <option value="">Select</option>
            <option value="male" <?= $gender === 'male' ? 'selected' : '' ?>>Male</option>
            <option value="female" <?= $gender === 'female' ? 'selected' : '' ?>>Female</option>
            <option value="other" <?= $gender === 'other' ? 'selected' : '' ?>>Other</option>
        </select>
    </div>
</div>

<div class="mb-2">
    <label class="form-label mb-1 small">Diagnosis</label>
    <textarea name="diagnosis" class="form-control form-control-sm" rows="2" required></textarea>
</div>

<div class="mb-2">
    <label class="form-label mb-1 small">Medicine</label>
    <div class="medicine-container" id="medicine-mobile-container-<?= $row['id'] ?>">
        <div class="medicine-row">
            <div class="row g-2 align-items-end">
                <div class="col-12">
                    <label class="form-label mb-1 small">Name</label>
                    <input list="medicine-mobile-suggest-<?= $row['id'] ?>" name="medicine_name[]" class="form-control form-control-sm" required>
                </div>
                <div class="col-6">
                    <label class="form-label mb-1 small">Dose</label>
                    <input type="text" name="medicine_dose[]" class="form-control form-control-sm" placeholder="1 tablet" required>
                </div>
                <div class="col-6">
                    <label class="form-label mb-1 small">Duration</label>
                    <input type="text" name="medicine_duration[]" class="form-control form-control-sm" placeholder="5 days" required>
                </div>
            </div>
        </div>
    </div>
    <datalist id="medicine-mobile-suggest-<?= $row['id'] ?>">
        <option value="Paracetamol"></option>
        <option value="Amoxicillin"></option>
        <option value="Ibuprofen"></option>
        <option value="Cetirizine"></option>
        <option value="Azithromycin"></option>
        <option value="Pantoprazole"></option>
        <option value="Metformin"></option>
        <option value="Amlodipine"></option>
        <option value="Atorvastatin"></option>
        <option value="Omeprazole"></option>
    </datalist>
    <button type="button" class="btn btn-outline-secondary btn-sm mt-1 add-medicine-row" data-target="medicine-mobile-container-<?= $row['id'] ?>" data-list="medicine-mobile-suggest-<?= $row['id'] ?>">
        + Add Medicine
    </button>
</div>

<div class="mb-2">
    <label class="form-label mb-1 small">Note and Advice</label>
    <textarea name="note_advice" class="form-control form-control-sm" rows="2" placeholder="Diet, hydration, follow-up advice..."></textarea>
</div>

<button class="btn btn-dark btn-sm w-100">Save</button>
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

$(document).on('click', '.add-medicine-row', function(){
    const containerId = $(this).data('target');
    const datalistId = $(this).data('list');
    const container = $('#' + containerId);

    const rowHtml = `
        <div class="medicine-row">
            <div class="row g-2 align-items-end">
                <div class="col-md-4 col-12">
                    <label class="form-label mb-1 small">Name</label>
                    <input list="${datalistId}" name="medicine_name[]" class="form-control form-control-sm">
                </div>
                <div class="col-md-4 col-6">
                    <label class="form-label mb-1 small">Dose</label>
                    <input type="text" name="medicine_dose[]" class="form-control form-control-sm" placeholder="1 tablet">
                </div>
                <div class="col-md-3 col-6">
                    <label class="form-label mb-1 small">Duration</label>
                    <input type="text" name="medicine_duration[]" class="form-control form-control-sm" placeholder="5 days">
                </div>
                <div class="col-md-1 col-12">
                    <button type="button" class="btn btn-outline-danger btn-sm w-100 remove-medicine-row">×</button>
                </div>
            </div>
        </div>`;

    container.append(rowHtml);
});

$(document).on('click', '.remove-medicine-row', function(){
    $(this).closest('.medicine-row').remove();
});
</script>

</body>
</html>
