<?php
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'patient') {
    header('Location: login.php');
    exit();
}

include 'database.php';

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

function parse_digital_prescription($prescription_raw): ?array
{
    if (!is_string($prescription_raw) || trim($prescription_raw) === '') {
        return null;
    }

    $decoded = json_decode($prescription_raw, true);
    if (!is_array($decoded) || trim((string) ($decoded['diagnosis'] ?? '')) === '') {
        return null;
    }

    return $decoded;
}

$patient_id = (int) $_SESSION['user_id'];
$flash_success = $_SESSION['flash_success'] ?? '';
$flash_error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

ensure_column($conn, 'appointments', 'patient_hidden', 'TINYINT(1) NOT NULL DEFAULT 0');

$booking_error = '';
$patient_columns = get_columns($conn, 'patients');
$doctor_columns = get_columns($conn, 'doctors');
$age_select = in_array('age', $patient_columns, true) ? 'age' : 'NULL AS age';
$gender_select = in_array('gender', $patient_columns, true) ? 'gender' : 'NULL AS gender';
$doctor_photo_select = in_array('photo', $doctor_columns, true) ? 'd.photo AS doctor_photo' : 'NULL AS doctor_photo';

if (isset($_POST['book_appointment'])) {
    $doctor_id = (int) ($_POST['doctor_id'] ?? 0);
    $date = trim($_POST['appointment_date'] ?? '');
    $time = trim($_POST['appointment_time'] ?? '');
    $appointment_timestamp = strtotime($date . ' ' . $time);

    if ($doctor_id <= 0 || $date === '' || $time === '' || !$appointment_timestamp) {
        $booking_error = 'Please select doctor, date, and time.';
    } elseif ($appointment_timestamp <= time()) {
        $booking_error = 'Please choose a future slot.';
    } else {
        $stmt = $conn->prepare('SELECT id FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ?');
        $stmt->bind_param('iss', $doctor_id, $date, $time);
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows;
        $stmt->close();

        if ($exists > 0) {
            $booking_error = 'This time slot is already booked.';
        } else {
            $room_id = uniqid('room_', true);
            $stmt = $conn->prepare(
                "INSERT INTO appointments (doctor_id, patient_id, appointment_date, appointment_time, status, room_id)
                 VALUES (?, ?, ?, ?, 'pending', ?)"
            );
            $stmt->bind_param('iisss', $doctor_id, $patient_id, $date, $time, $room_id);
            $saved = $stmt->execute();
            $stmt->close();

            if ($saved) {
                $_SESSION['flash_success'] = 'Appointment booked successfully.';
                header('Location: patient_dashboard.php');
                exit();
            }

            $booking_error = 'Unable to save appointment.';
        }
    }
}

$stmt = $conn->prepare("SELECT full_name, email, phone, {$age_select}, {$gender_select} FROM patients WHERE id = ?");
$stmt->bind_param('i', $patient_id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();
$stmt->close();

$doctors = $conn->query('SELECT id, full_name, specialization FROM doctors ORDER BY full_name');

$stmt = $conn->prepare(
    "SELECT a.id, a.appointment_date, a.appointment_time, a.status, a.prescription, a.room_id,
            d.full_name AS doctor_name, d.specialization, {$doctor_photo_select}
     FROM appointments a
     JOIN doctors d ON a.doctor_id = d.id
     WHERE a.patient_id = ? AND COALESCE(a.patient_hidden, 0) = 0
     ORDER BY a.appointment_date DESC, a.appointment_time DESC"
);
$stmt->bind_param('i', $patient_id);
$stmt->execute();
$appointments = $stmt->get_result();
$stmt->close();

include 'header.php';
?>
<style>
    .dash-wrap { padding: 28px 0 56px; }
    .dash-card {
        background: #fff;
        border: 1px solid rgba(148,163,184,.2);
        border-radius: 20px;
        box-shadow: 0 16px 30px rgba(15,23,42,.07);
        padding: 22px;
        margin-bottom: 20px;
    }
    .dash-card:hover { transform: none; }
    .table thead th { background: #f8fbfd; white-space: nowrap; }
    .badge-status { border-radius: 999px; padding: 8px 12px; font-weight: 700; font-size: .82rem; text-transform: capitalize; }
    .status-pending { background: #fff5cc; color: #8a6d00; }
    .status-confirmed { background: #dff6ff; color: #0c5f8a; }
    .status-completed { background: #dff7e7; color: #146c43; }
    .status-cancelled { background: #fde2e4; color: #a61e2d; }
    .form-control, .form-select { min-height: 46px; border-radius: 12px; }
    .doctor-mini {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #dbe4ec;
    }
    .doctor-cell {
        display: flex;
        align-items: center;
        gap: 10px;
    }
</style>

<div class="container dash-wrap">
    <div class="dash-card">
        <h2 class="h4 fw-bold mb-3">Patient Dashboard</h2>
        <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0">
                <tbody>
                    <tr>
                        <th style="width:220px;">Name</th>
                        <td><?= htmlspecialchars($patient['full_name'] ?? '') ?></td>
                        <th>Email</th>
                        <td><?= htmlspecialchars($patient['email'] ?? '') ?></td>
                    </tr>
                    <tr>
                        <th>Phone</th>
                        <td><?= htmlspecialchars($patient['phone'] ?? '') ?></td>
                        <th>Age / Gender</th>
                        <td><?= htmlspecialchars((string) ($patient['age'] ?? '')) ?> <?= !empty($patient['gender']) ? '/ ' . htmlspecialchars((string) $patient['gender']) : '' ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($flash_success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($flash_success) ?></div>
    <?php endif; ?>
    <?php if ($flash_error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($flash_error) ?></div>
    <?php endif; ?>

    <div class="dash-card">
        <h2 class="h5 fw-bold mb-3">Book Appointment</h2>

        <?php if ($booking_error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($booking_error) ?></div>
        <?php endif; ?>

        <form method="POST" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Doctor</label>
                <select name="doctor_id" class="form-select" required>
                    <option value="">Select doctor</option>
                    <?php while ($doctor = $doctors->fetch_assoc()): ?>
                        <option value="<?= (int) $doctor['id'] ?>"><?= htmlspecialchars($doctor['full_name'] . ' (' . $doctor['specialization'] . ')') ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Date</label>
                <input type="date" class="form-control" name="appointment_date" min="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Time</label>
                <input type="time" class="form-control" name="appointment_time" required>
            </div>
            <div class="col-12">
                <button class="btn btn-primary" type="submit" name="book_appointment">Book Appointment</button>
            </div>
        </form>
    </div>

    <div class="dash-card">
        <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
            <h2 class="h5 fw-bold mb-0">Appointments</h2>
            <span class="text-muted small">Your form will not be interrupted while booking</span>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0">
                <thead>
                    <tr>
                        <th>Doctor</th>
                        <th>Specialization</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Call</th>
                        <th>Prescription</th>
                        <th>Remove</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($appointments->num_rows > 0): ?>
                        <?php while ($row = $appointments->fetch_assoc()): ?>
                            <?php $prescription = parse_digital_prescription($row['prescription'] ?? ''); ?>
                            <?php $photo = !empty($row['doctor_photo']) ? $row['doctor_photo'] : 'default.png'; ?>
                            <tr>
                                <td>
                                    <div class="doctor-cell">
                                        <img class="doctor-mini" src="<?= htmlspecialchars($photo) ?>" alt="Doctor photo">
                                        <span><?= htmlspecialchars($row['doctor_name']) ?></span>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($row['specialization']) ?></td>
                                <td><?= htmlspecialchars(date('d M Y', strtotime($row['appointment_date']))) ?></td>
                                <td><?= htmlspecialchars(date('h:i A', strtotime($row['appointment_time']))) ?></td>
                                <td><span class="badge-status status-<?= htmlspecialchars($row['status']) ?>"><?= htmlspecialchars($row['status']) ?></span></td>
                                <td>
                                    <?php if ($row['status'] === 'confirmed'): ?>
                                        <a class="btn btn-sm btn-primary" href="video.php?appointment_id=<?= (int) $row['id'] ?>">Join</a>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($row['status'] === 'completed' && $prescription): ?>
                                        <a class="btn btn-sm btn-success" href="prescription.php?appointment_id=<?= (int) $row['id'] ?>">Download / View</a>
                                    <?php elseif ($row['status'] === 'completed' && !empty($row['prescription'])): ?>
                                        <span class="text-muted">Preparing</span>
                                    <?php elseif ($row['status'] === 'completed'): ?>
                                        <span class="text-muted">Pending</span>
                                    <?php else: ?>
                                        <span class="text-muted">After completion</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="POST" action="archive_appointment.php" onsubmit="return confirm('Remove this appointment from dashboard?');">
                                        <input type="hidden" name="appointment_id" value="<?= (int) $row['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="8" class="text-center text-muted">No appointments found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    (function () {
        let lastInteraction = Date.now();
        document.querySelectorAll('form input, form select, form textarea').forEach(function (element) {
            ['focus', 'input', 'change', 'click'].forEach(function (eventName) {
                element.addEventListener(eventName, function () {
                    lastInteraction = Date.now();
                });
            });
        });

        setInterval(function () {
            const active = document.activeElement;
            const isEditing = active && ['INPUT', 'SELECT', 'TEXTAREA'].includes(active.tagName);
            const recentlyActive = (Date.now() - lastInteraction) < 30000;

            if (!isEditing && !recentlyActive && document.visibilityState === 'visible') {
                window.location.reload();
            }
        }, 15000);
    })();
</script>
</body>
</html>
