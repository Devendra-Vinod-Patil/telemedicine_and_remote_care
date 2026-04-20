<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: login.php");
    exit();
}

include 'database.php';
include 'header.php';

$patient_id = $_SESSION['user_id'];

/* ===============================
   FETCH PATIENT PROFILE
================================ */
$stmt = $conn->prepare("SELECT full_name, email, phone FROM patients WHERE id = ?");
if (!$stmt) die($conn->error);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();
$stmt->close();

/* ===============================
   HANDLE APPOINTMENT BOOKING
================================ */
$booking_error = "";

if (isset($_POST['book_appointment'])) {

    $doctor_id = intval($_POST['doctor_id']);
    $date = $_POST['appointment_date'];
    $time = $_POST['appointment_time'];

    // Check slot availability
    $stmt = $conn->prepare(
        "SELECT id FROM appointments 
         WHERE doctor_id=? AND appointment_date=? AND appointment_time=?"
    );
    if (!$stmt) die($conn->error);
    $stmt->bind_param("iss", $doctor_id, $date, $time);
    $stmt->execute();
    $exists = $stmt->get_result()->num_rows;
    $stmt->close();

    if ($exists > 0) {
        $booking_error = "This time slot is already booked.";
    } else {
        $room_id = uniqid("room_");

        $stmt = $conn->prepare(
            "INSERT INTO appointments 
            (doctor_id, patient_id, appointment_date, appointment_time, status, room_id)
            VALUES (?, ?, ?, ?, 'pending', ?)"
        );
        if (!$stmt) die($conn->error);
        $stmt->bind_param("iisss", $doctor_id, $patient_id, $date, $time, $room_id);
        $stmt->execute();
        $stmt->close();

        header("Location: patient_dashboard.php");
        exit();
    }
}

/* ===============================
   FETCH DOCTORS
================================ */
$doctors = $conn->query(
    "SELECT id, full_name, specialization FROM doctors ORDER BY full_name"
);

/* ===============================
   FETCH APPOINTMENTS
================================ */
$stmt = $conn->prepare(
    "SELECT a.id, a.appointment_date, a.appointment_time,
            a.status, a.prescription, a.room_id,
            d.full_name AS doctor_name, d.specialization
     FROM appointments a
     JOIN doctors d ON a.doctor_id = d.id
     WHERE a.patient_id = ?
     ORDER BY a.appointment_date, a.appointment_time"
);
if (!$stmt) die($conn->error);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$appointments = $stmt->get_result();
$stmt->close();

function parse_digital_prescription($prescription_raw) {
    if (!is_string($prescription_raw) || trim($prescription_raw) === '') {
        return null;
    }

    $decoded = json_decode($prescription_raw, true);
    if (!is_array($decoded) || empty($decoded['diagnosis'])) {
        return null;
    }

    return $decoded;
}

function is_file_prescription($prescription_raw) {
    if (!is_string($prescription_raw)) {
        return false;
    }

    return strpos($prescription_raw, 'uploads/') === 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Patient Dashboard</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
body { background:#f5f2eb; font-family: 'Lato', sans-serif; }
.card { border-radius:15px; box-shadow:0 5px 15px rgba(0,0,0,.05); }
.section-title { border-left:5px solid #e2725b; padding-left:10px; font-weight:700; }
.status-badge.pending { background:#fff3cd; color:#856404; }
.status-badge.confirmed { background:#d1ecf1; color:#0c5460; }
.status-badge.completed { background:#d4edda; color:#155724; }
.status-badge.cancelled { background:#f8d7da; color:#721c24; }
.rx-details summary { cursor:pointer; color:#0d6efd; }
.rx-details ul { padding-left:18px; }
</style>
</head>

<body>

<div class="container py-5">

<!-- ================= PROFILE ================= -->
<div class="card p-4 mb-4">
    <h5 class="fw-bold mb-3">Your Profile</h5>
    <div class="row">
        <div class="col-md-4"><strong>Name:</strong> <?= htmlspecialchars($patient['full_name']) ?></div>
        <div class="col-md-4"><strong>Email:</strong> <?= htmlspecialchars($patient['email']) ?></div>
        <div class="col-md-4"><strong>Phone:</strong> <?= htmlspecialchars($patient['phone']) ?></div>
    </div>
</div>

<!-- ================= BOOK APPOINTMENT ================= -->
<div class="card p-4 mb-4">
    <h5 class="fw-bold mb-3">Book New Appointment</h5>

    <?php if ($booking_error): ?>
        <div class="alert alert-danger"><?= $booking_error ?></div>
    <?php endif; ?>

    <form method="POST" class="row g-3">
        <div class="col-md-4">
            <label class="form-label">Doctor</label>
            <select name="doctor_id" class="form-select" required>
                <option value="">Select Doctor</option>
                <?php while($d = $doctors->fetch_assoc()): ?>
                    <option value="<?= $d['id'] ?>">
                        <?= htmlspecialchars($d['full_name']." (".$d['specialization'].")") ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="col-md-4">
            <label class="form-label">Date</label>
            <input type="date" name="appointment_date" class="form-control" required>
        </div>

        <div class="col-md-4">
            <label class="form-label">Time</label>
            <input type="time" name="appointment_time" class="form-control" required>
        </div>

        <div class="col-12">
            <button name="book_appointment" class="btn btn-primary">
                <i class="fas fa-calendar-plus me-1"></i> Book Appointment
            </button>
        </div>
    </form>
</div>

<!-- ================= APPOINTMENTS ================= -->
<div class="card p-4">
<h5 class="section-title mb-3">Your Appointments</h5>

<?php if ($appointments->num_rows > 0): ?>

<!-- ================= DESKTOP TABLE ================= -->
<div class="table-responsive d-none d-md-block">
<table class="table table-hover align-middle">
<thead class="table-light">
<tr>
    <th>Doctor</th>
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
    <td><?= htmlspecialchars($row['doctor_name']." (".$row['specialization'].")") ?></td>
    <td><?= date("d M Y", strtotime($row['appointment_date'])) ?></td>
    <td><?= date("h:i A", strtotime($row['appointment_time'])) ?></td>

    <td>
        <span class="badge status-badge <?= $row['status'] ?>">
            <?= ucfirst($row['status']) ?>
        </span>
    </td>

    <!-- ACTION -->
    <td>
        <?php if ($row['status'] === 'confirmed'): ?>
            <a href="video.php?appointment_id=<?= $row['id'] ?>" class="btn btn-primary btn-sm">
                <i class="fas fa-video"></i> Join Call
            </a>
        <?php else: ?>
            <span class="text-muted small">N/A</span>
        <?php endif; ?>
    </td>

    <!-- PRESCRIPTION -->
    <td>
        <?php if ($row['status'] === 'completed' && !empty($row['prescription'])): ?>
            <?php $digital_rx = parse_digital_prescription($row['prescription']); ?>
            <?php if ($digital_rx): ?>
                <details class="rx-details small">
                    <summary>View Digital Prescription</summary>
                    <div class="mt-2">
                        <div><strong>Patient:</strong> <?= htmlspecialchars($digital_rx['patient_name'] ?? $patient['full_name']) ?></div>
                        <?php if (!empty($digital_rx['age'])): ?>
                            <div><strong>Age:</strong> <?= htmlspecialchars((string)$digital_rx['age']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($digital_rx['gender'])): ?>
                            <div><strong>Gender:</strong> <?= htmlspecialchars(ucfirst($digital_rx['gender'])) ?></div>
                        <?php endif; ?>
                        <div><strong>Diagnosis:</strong> <?= htmlspecialchars($digital_rx['diagnosis']) ?></div>
                        <?php if (!empty($digital_rx['medicines']) && is_array($digital_rx['medicines'])): ?>
                            <div><strong>Medicines:</strong></div>
                            <ul class="mb-1">
                                <?php foreach ($digital_rx['medicines'] as $medicine): ?>
                                    <li>
                                        <?= htmlspecialchars($medicine['name'] ?? '') ?>
                                        <?php if (!empty($medicine['dose'])): ?> — <?= htmlspecialchars($medicine['dose']) ?><?php endif; ?>
                                        <?php if (!empty($medicine['duration'])): ?> (<?= htmlspecialchars($medicine['duration']) ?>)<?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        <?php if (!empty($digital_rx['note_advice'])): ?>
                            <div><strong>Note & Advice:</strong> <?= htmlspecialchars($digital_rx['note_advice']) ?></div>
                        <?php endif; ?>
                    </div>
                </details>
            <?php elseif (is_file_prescription($row['prescription'])): ?>
                <a href="<?= htmlspecialchars($row['prescription']) ?>" download class="btn btn-success btn-sm">
                    <i class="fas fa-download"></i> Download
                </a>
            <?php else: ?>
                <span class="text-muted small">Prescription available</span>
            <?php endif; ?>
        <?php elseif ($row['status'] === 'completed'): ?>
            <span class="text-muted small">Not uploaded</span>
        <?php else: ?>
            <span class="text-muted small">After completion</span>
        <?php endif; ?>
    </td>
</tr>
<?php endwhile; ?>

</tbody>
</table>
</div>

<!-- ================= MOBILE VIEW ================= -->
<div class="d-md-none">
<?php
$appointments->data_seek(0); // reset pointer
while($row = $appointments->fetch_assoc()):
?>
<div class="card mb-3 shadow-sm">
<div class="card-body">

    <h6 class="fw-bold mb-1">
        <i class="fas fa-user-md me-1 text-primary"></i>
        <?= htmlspecialchars($row['doctor_name']) ?>
    </h6>

    <p class="mb-1 text-muted">
        <?= htmlspecialchars($row['specialization']) ?>
    </p>

    <p class="mb-1">
        <i class="fas fa-calendar-alt me-1"></i>
        <?= date("d M Y", strtotime($row['appointment_date'])) ?>
    </p>

    <p class="mb-2">
        <i class="fas fa-clock me-1"></i>
        <?= date("h:i A", strtotime($row['appointment_time'])) ?>
    </p>

    <span class="badge status-badge <?= $row['status'] ?> mb-2">
        <?= ucfirst($row['status']) ?>
    </span>

    <!-- ACTION -->
    <?php if ($row['status'] === 'confirmed'): ?>
        <a href="video.php?appointment_id=<?= $row['id'] ?>" 
           class="btn btn-primary btn-sm w-100 mb-2">
            <i class="fas fa-video"></i> Join Call
        </a>
    <?php endif; ?>

    <!-- PRESCRIPTION -->
    <?php if ($row['status'] === 'completed' && !empty($row['prescription'])): ?>
        <?php $digital_rx = parse_digital_prescription($row['prescription']); ?>
        <?php if ($digital_rx): ?>
            <details class="rx-details small">
                <summary>View Digital Prescription</summary>
                <div class="mt-2">
                    <div><strong>Patient:</strong> <?= htmlspecialchars($digital_rx['patient_name'] ?? $patient['full_name']) ?></div>
                    <?php if (!empty($digital_rx['age'])): ?>
                        <div><strong>Age:</strong> <?= htmlspecialchars((string)$digital_rx['age']) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($digital_rx['gender'])): ?>
                        <div><strong>Gender:</strong> <?= htmlspecialchars(ucfirst($digital_rx['gender'])) ?></div>
                    <?php endif; ?>
                    <div><strong>Diagnosis:</strong> <?= htmlspecialchars($digital_rx['diagnosis']) ?></div>
                    <?php if (!empty($digital_rx['medicines']) && is_array($digital_rx['medicines'])): ?>
                        <div><strong>Medicines:</strong></div>
                        <ul class="mb-1">
                            <?php foreach ($digital_rx['medicines'] as $medicine): ?>
                                <li>
                                    <?= htmlspecialchars($medicine['name'] ?? '') ?>
                                    <?php if (!empty($medicine['dose'])): ?> — <?= htmlspecialchars($medicine['dose']) ?><?php endif; ?>
                                    <?php if (!empty($medicine['duration'])): ?> (<?= htmlspecialchars($medicine['duration']) ?>)<?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <?php if (!empty($digital_rx['note_advice'])): ?>
                        <div><strong>Note & Advice:</strong> <?= htmlspecialchars($digital_rx['note_advice']) ?></div>
                    <?php endif; ?>
                </div>
            </details>
        <?php elseif (is_file_prescription($row['prescription'])): ?>
            <a href="<?= htmlspecialchars($row['prescription']) ?>" 
               download 
               class="btn btn-success btn-sm w-100">
                <i class="fas fa-download"></i> Download Prescription
            </a>
        <?php else: ?>
            <div class="text-muted small text-center">
                Prescription available
            </div>
        <?php endif; ?>
    <?php elseif ($row['status'] === 'completed'): ?>
        <div class="text-muted small text-center">
            Prescription not uploaded
        </div>
    <?php else: ?>
        <div class="text-muted small text-center">
            Available after completion
        </div>
    <?php endif; ?>

</div>
</div>
<?php endwhile; ?>
</div>

<?php else: ?>
<div class="text-center py-5 text-muted">
    <i class="fas fa-calendar-times fa-2x mb-2"></i>
    <p>No appointments found</p>
</div>
<?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
