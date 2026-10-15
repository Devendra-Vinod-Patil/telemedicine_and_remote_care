<?php
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'doctor') {
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

$appointment_id = isset($_GET['appointment_id']) ? (int) $_GET['appointment_id'] : 0;
if ($appointment_id <= 0) {
    die('Invalid appointment.');
}

$doctor_id = (int) $_SESSION['user_id'];
$patient_columns = get_columns($conn, 'patients');
$age_select = in_array('age', $patient_columns, true) ? 'p.age AS patient_age' : 'NULL AS patient_age';
$gender_select = in_array('gender', $patient_columns, true) ? 'p.gender AS patient_gender' : 'NULL AS patient_gender';

$stmt = $conn->prepare(
    "SELECT a.id, a.appointment_date, a.appointment_time, a.status, a.prescription,
            p.full_name AS patient_name, {$age_select}, {$gender_select},
            d.full_name AS doctor_name, d.specialization
     FROM appointments a
     JOIN patients p ON a.patient_id = p.id
     JOIN doctors d ON a.doctor_id = d.id
     WHERE a.id = ? AND a.doctor_id = ?
     LIMIT 1"
);
$stmt->bind_param('ii', $appointment_id, $doctor_id);
$stmt->execute();
$appointment = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$appointment) {
    die('Appointment not found.');
}

if (parse_digital_prescription($appointment['prescription'] ?? '')) {
    header('Location: prescription.php?appointment_id=' . $appointment_id);
    exit();
}

include 'header.php';
?>
<style>
    .rx-page { padding: 28px 0 56px; }
    .rx-card {
        background: #fff;
        border: 1px solid rgba(148,163,184,.2);
        border-radius: 22px;
        box-shadow: 0 16px 30px rgba(15,23,42,.07);
        padding: 24px;
    }
    .rx-card:hover { transform: none; }
    .table th { background: #f8fbfd; white-space: nowrap; }
    .form-control, .form-select { min-height: 44px; border-radius: 10px; }
    .medicine-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        margin-bottom: 12px;
    }
</style>

<div class="container rx-page">
    <div class="rx-card">
        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
            <div>
                <h1 class="h4 fw-bold mb-1">Add Prescription</h1>
                <div class="text-muted"><?= htmlspecialchars($appointment['patient_name']) ?> | <?= htmlspecialchars(date('d M Y', strtotime($appointment['appointment_date']))) ?> | <?= htmlspecialchars(date('h:i A', strtotime($appointment['appointment_time']))) ?></div>
            </div>
            <a href="doctors_dashboard.php" class="btn btn-outline-secondary">Back</a>
        </div>

        <form action="upload_prescription.php" method="POST">
            <input type="hidden" name="appointment_id" value="<?= (int) $appointment['id'] ?>">

            <div class="table-responsive mb-3">
                <table class="table table-bordered align-middle mb-0">
                    <tbody>
                        <tr>
                            <th style="width:180px;">Patient Name</th>
                            <td><input class="form-control" type="text" name="patient_name" value="<?= htmlspecialchars($appointment['patient_name']) ?>" readonly></td>
                            <th>Age</th>
                            <td><input class="form-control" type="number" name="age" min="0" max="130" value="<?= htmlspecialchars((string) ($appointment['patient_age'] ?? '')) ?>"></td>
                            <th>Gender</th>
                            <td>
                                <?php $gender = strtolower(trim((string) ($appointment['patient_gender'] ?? ''))); ?>
                                <select name="gender" class="form-select">
                                    <option value="">Select</option>
                                    <option value="male" <?= $gender === 'male' ? 'selected' : '' ?>>Male</option>
                                    <option value="female" <?= $gender === 'female' ? 'selected' : '' ?>>Female</option>
                                    <option value="other" <?= $gender === 'other' ? 'selected' : '' ?>>Other</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th>Diagnosis</th>
                            <td colspan="5"><textarea class="form-control" name="diagnosis" rows="2" required></textarea></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="table-responsive mb-3">
                <div class="medicine-actions">
                    <label class="form-label fw-semibold mb-0">Medicines</label>
                    <button class="btn btn-outline-primary btn-sm" type="button" id="add-medicine-row">Add Medicine</button>
                </div>
                <table class="table table-bordered align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Medicine</th>
                            <th>Dose</th>
                            <th>Timing</th>
                            <th>Food</th>
                            <th>Duration</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="medicine-table-body">
                        <tr class="medicine-row">
                            <td>
                                <input class="form-control" type="text" name="medicine_name[]" list="medicine-suggestions" required>
                            </td>
                            <td><input class="form-control" type="text" name="medicine_dose[]" required></td>
                            <td>
                                <select class="form-select" name="medicine_timing[]" required>
                                    <option value="">Select</option>
                                    <option value="1-0-1">Mor-Night</option>
                                    <option value="1-1-1">Mor-Aft-Night</option>
                                    <option value="1-0-0">Mor</option>
                                    <option value="0-1-0">Aft</option>
                                    <option value="0-0-1">Night</option>
                                    <option value="1-1-0">Mor-Aft</option>
                                    <option value="0-1-1">Aft-Night</option>
                                    <option value="SOS">SOS</option>
                                </select>
                            </td>
                            <td>
                                <select class="form-select" name="medicine_food[]" required>
                                    <option value="">Select</option>
                                    <option value="after_food">After food</option>
                                    <option value="before_food">Before food</option>
                                    <option value="with_food">With food</option>
                                    <option value="anytime">Any time</option>
                                </select>
                            </td>
                            <td><input class="form-control" type="text" name="medicine_duration[]" required></td>
                            <td style="width:90px;"><button class="btn btn-outline-danger btn-sm remove-medicine-row" type="button">Remove</button></td>
                        </tr>
                    </tbody>
                </table>
                <datalist id="medicine-suggestions">
                    <option value="Paracetamol 500mg"></option>
                    <option value="Azithromycin 500mg"></option>
                    <option value="Amoxicillin 500mg"></option>
                    <option value="Cetirizine 10mg"></option>
                    <option value="Pantoprazole 40mg"></option>
                    <option value="Ibuprofen 400mg"></option>
                    <option value="Metformin 500mg"></option>
                    <option value="Amlodipine 5mg"></option>
                    <option value="Atorvastatin 10mg"></option>
                    <option value="Omeprazole 20mg"></option>
                </datalist>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Note and Advice</label>
                <textarea class="form-control" name="note_advice" rows="3"></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Tests / Reports</label>
                <textarea class="form-control" name="tests_reports" rows="2" placeholder="CBC, X-ray, Blood Sugar, etc."></textarea>
            </div>

            <button class="btn btn-primary" type="submit">Save Prescription</button>
        </form>
    </div>
</div>
<script>
    (function () {
        const tableBody = document.getElementById('medicine-table-body');
        const addButton = document.getElementById('add-medicine-row');

        function buildRow() {
            return `
                <tr class="medicine-row">
                    <td><input class="form-control" type="text" name="medicine_name[]" list="medicine-suggestions" required></td>
                    <td><input class="form-control" type="text" name="medicine_dose[]" required></td>
                    <td>
                        <select class="form-select" name="medicine_timing[]" required>
                            <option value="">Select</option>
                            <option value="1-0-1">Mor-Night</option>
                            <option value="1-1-1">Mor-Aft-Night</option>
                            <option value="1-0-0">Mor</option>
                            <option value="0-1-0">Aft</option>
                            <option value="0-0-1">Night</option>
                            <option value="1-1-0">Mor-Aft</option>
                            <option value="0-1-1">Aft-Night</option>
                            <option value="SOS">SOS</option>
                        </select>
                    </td>
                    <td>
                        <select class="form-select" name="medicine_food[]" required>
                            <option value="">Select</option>
                            <option value="after_food">After food</option>
                            <option value="before_food">Before food</option>
                            <option value="with_food">With food</option>
                            <option value="anytime">Any time</option>
                        </select>
                    </td>
                    <td><input class="form-control" type="text" name="medicine_duration[]" required></td>
                    <td style="width:90px;"><button class="btn btn-outline-danger btn-sm remove-medicine-row" type="button">Remove</button></td>
                </tr>
            `;
        }

        addButton.addEventListener('click', function () {
            tableBody.insertAdjacentHTML('beforeend', buildRow());
        });

        document.addEventListener('click', function (event) {
            const removeButton = event.target.closest('.remove-medicine-row');
            if (!removeButton) return;

            if (tableBody.querySelectorAll('.medicine-row').length > 1) {
                removeButton.closest('.medicine-row').remove();
            }
        });
    })();
</script>
</body>
</html>
