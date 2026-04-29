<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header('Location: login.php');
    exit();
}

include 'database.php';

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

function timing_detail(array $medicine): string
{
    $code = trim((string) ($medicine['timing'] ?? ''));
    $label = trim((string) ($medicine['timing_label'] ?? ''));

    $map = [
        '1-0-1' => 'Morning-Night',
        '1-1-1' => 'Morning-Afternoon-Night',
        '1-0-0' => 'Morning',
        '0-1-0' => 'Afternoon',
        '0-0-1' => 'Night',
        '1-1-0' => 'Morning-Afternoon',
        '0-1-1' => 'Afternoon-Night',
        'SOS' => 'When needed'
    ];

    if ($label !== '') {
        $normalized = str_replace([',', ' and '], ['-', '-'], strtolower($label));
        $normalized = preg_replace('/\s+/', '-', $normalized);
        $normalized = trim((string) $normalized, '-');

        $pretty = ucwords(str_replace('-', ' ', $normalized));
        $pretty = str_replace(' ', '-', $pretty);
        return $pretty;
    }

    return $map[$code] ?? $code;
}

$appointment_id = isset($_GET['appointment_id']) ? (int) $_GET['appointment_id'] : 0;
if ($appointment_id <= 0) {
    die('Invalid appointment.');
}

$role = $_SESSION['role'];
$user_id = (int) $_SESSION['user_id'];

$ownership_where = $role === 'doctor' ? 'a.doctor_id = ?' : 'a.patient_id = ?';
$person_name = $role === 'doctor' ? 'd.full_name AS doctor_name, p.full_name AS patient_name, d.specialization, d.clinic' : 'd.full_name AS doctor_name, p.full_name AS patient_name, d.specialization, d.clinic';

$stmt = $conn->prepare(
    "SELECT a.id, a.appointment_date, a.appointment_time, a.status, a.prescription,
            COALESCE(a.payment_status, 'unpaid') AS payment_status, a.payment_amount,
            {$person_name}
     FROM appointments a
     JOIN doctors d ON a.doctor_id = d.id
     JOIN patients p ON a.patient_id = p.id
     WHERE a.id = ? AND {$ownership_where}
     LIMIT 1"
);
$stmt->bind_param('ii', $appointment_id, $user_id);
$stmt->execute();
$appointment = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$appointment) {
    die('Prescription not found.');
}

// Patients must pay before downloading/viewing prescription
if ($role === 'patient' && (($appointment['payment_status'] ?? 'unpaid') !== 'paid')) {
    include 'header.php';
    ?>
    <div class="container py-5">
        <div class="alert alert-warning">
            Payment is required to download the prescription.
            <?php if (!empty($appointment['payment_amount'])): ?>
                Amount: <strong>₹<?= number_format((float)$appointment['payment_amount'], 2) ?></strong>
            <?php else: ?>
                Amount: <strong>₹<?= number_format((float)250, 2) ?></strong>
            <?php endif; ?>
        </div>
        <a class="btn btn-warning" href="appointment_payment.php?appointment_id=<?= (int)$appointment_id ?>">Pay Now</a>
        <a class="btn btn-outline-primary ms-2" href="patient_dashboard.php">Back</a>
    </div>
    </body>
    </html>
    <?php
    exit();
}

$prescription = parse_digital_prescription($appointment['prescription'] ?? '');
if (!$prescription) {
    include 'header.php';
    ?>
    <div class="container py-5">
        <div class="alert alert-warning">Prescription is not available yet or the saved data is incomplete.</div>
        <?php if ($role === 'doctor'): ?>
            <a href="add_prescription.php?appointment_id=<?= (int) $appointment_id ?>" class="btn btn-primary">Open Prescription Form</a>
        <?php else: ?>
            <a href="patient_dashboard.php" class="btn btn-primary">Back to Dashboard</a>
        <?php endif; ?>
    </div>
    </body>
    </html>
    <?php
    exit();
}

include 'header.php';
?>
<style>
    .prescription-page {
        padding: 32px 0 56px;
    }

    .prescription-card {
        background: #fff;
        border: 1px solid rgba(148, 163, 184, 0.2);
        border-radius: 22px;
        box-shadow: 0 18px 34px rgba(15, 23, 42, 0.08);
        padding: 24px;
    }

    .company-strip {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        padding-bottom: 18px;
        margin-bottom: 18px;
        border-bottom: 2px solid #dbe4ec;
    }

    .company-strip h2 {
        margin: 0 0 6px;
        font-size: 1.45rem;
        font-weight: 800;
    }

    .doctor-head {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .doctor-head img {
        width: 68px;
        height: 68px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid #dbe4ec;
    }

    .rx-title {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 12px;
        margin-bottom: 18px;
    }

    .rx-title h1 {
        margin: 0 0 6px;
        font-size: 1.7rem;
        font-weight: 800;
    }

    .rx-title p {
        margin: 0;
        color: #607284;
    }

    .info-table th {
        width: 180px;
        background: #f8fbfd;
    }

    .info-table th,
    .info-table td,
    .medicine-table th,
    .medicine-table td {
        padding: 12px 14px;
        border: 1px solid #dbe4ec;
        vertical-align: top;
    }

    .medicine-table th {
        background: #f8fbfd;
    }

    .signature-box {
        margin-top: 24px;
        text-align: right;
    }

    .signature-box img {
        max-width: 180px;
        max-height: 70px;
        object-fit: contain;
        display: inline-block;
        margin-bottom: 8px;
    }
  </style>

<div class="container prescription-page">
    <div class="prescription-card">
        <div class="company-strip">
            <div>
                <h2>Virtual-Chikitsa</h2>
                <div class="text-muted">Digital Prescription</div>
            </div>
            <div class="doctor-head">
                <img src="<?= htmlspecialchars(!empty($prescription['doctor_photo']) ? $prescription['doctor_photo'] : 'default.png') ?>" alt="Doctor photo">
                <div>
                    <div class="fw-bold"><?= htmlspecialchars($prescription['doctor_name'] ?? $appointment['doctor_name']) ?></div>
                    <div class="text-muted"><?= htmlspecialchars($prescription['doctor_specialization'] ?? ($appointment['specialization'] ?? '')) ?></div>
                </div>
            </div>
        </div>

        <div class="rx-title">
            <div>
                <h1>Prescription</h1>
                <p><?= htmlspecialchars($appointment['doctor_name']) ?> | <?= htmlspecialchars($appointment['specialization'] ?? '') ?></p>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-primary" onclick="window.print()">Print</button>
            </div>
        </div>

        <div class="table-responsive mb-4">
            <table class="table info-table mb-0">
                <tbody>
                    <tr><th>Patient Name</th><td><?= htmlspecialchars($prescription['patient_name'] ?? $appointment['patient_name']) ?></td></tr>
                    <tr><th>Age</th><td><?= htmlspecialchars((string) ($prescription['age'] ?? '')) ?></td></tr>
                    <tr><th>Gender</th><td><?= htmlspecialchars((string) ($prescription['gender'] ?? '')) ?></td></tr>
                    <tr><th>Doctor</th><td><?= htmlspecialchars($prescription['doctor_name'] ?? $appointment['doctor_name']) ?></td></tr>
                    <tr><th>Clinic</th><td><?= htmlspecialchars($prescription['doctor_clinic'] ?? ($appointment['clinic'] ?? '')) ?></td></tr>
                    <tr><th>Date</th><td><?= htmlspecialchars(date('d M Y', strtotime($appointment['appointment_date']))) ?></td></tr>
                    <tr><th>Diagnosis</th><td><?= htmlspecialchars($prescription['diagnosis'] ?? '') ?></td></tr>
                    <tr><th>Advice</th><td><?= nl2br(htmlspecialchars($prescription['note_advice'] ?? '')) ?></td></tr>
                    <?php if (!empty($prescription['tests_reports'])): ?>
                        <tr><th>Tests / Reports</th><td><?= nl2br(htmlspecialchars($prescription['tests_reports'])) ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="table-responsive">
            <table class="table medicine-table mb-0">
                <thead>
                    <tr>
                        <th>Medicine</th>
                        <th>Dose</th>
                        <th>Timing</th>
                        <th>Food</th>
                        <th>Duration</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (($prescription['medicines'] ?? []) as $medicine): ?>
                        <tr>
                            <td><?= htmlspecialchars($medicine['name'] ?? '') ?></td>
                            <td><?= htmlspecialchars($medicine['dose'] ?? '') ?></td>
                            <td>
                                <div class="fw-semibold"><?= htmlspecialchars(timing_detail(is_array($medicine) ? $medicine : [])) ?></div>
                                <?php if (!empty($medicine['timing'])): ?>
                                    <div class="text-muted small"><?= htmlspecialchars((string) $medicine['timing']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($medicine['food_label'] ?? ($medicine['food'] ?? '')) ?></td>
                            <td><?= htmlspecialchars($medicine['duration'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="signature-box">
            <?php if (!empty($prescription['doctor_signature'])): ?>
                <img src="<?= htmlspecialchars($prescription['doctor_signature']) ?>" alt="Doctor signature">
            <?php endif; ?>
            <div><?= htmlspecialchars($prescription['doctor_name'] ?? $appointment['doctor_name']) ?></div>
        </div>
    </div>
</div>
</body>
</html>
