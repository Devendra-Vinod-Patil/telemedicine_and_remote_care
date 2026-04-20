<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    exit("Unauthorized access");
}

// DB connection
include 'database.php';

// Get patient id
$patient_id = $_SESSION['user_id'];

// Fetch appointments (join with doctor table)
$sql = "SELECT a.id, a.appointment_date, a.appointment_time, a.status,
               d.full_name AS doctor_name, d.specialization
        FROM appointments a
        JOIN doctors d ON a.doctor_id = d.id
        WHERE a.patient_id = ?
        ORDER BY a.appointment_date DESC, a.appointment_time DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0): ?>

    <!-- Table View (desktop/tablet) -->
    <div class="d-none d-md-block">
        <table class="table table-hover table-bordered align-middle">
            <thead class="table-light">
                <tr>
                    <th>Doctor</th>
                    <th>Specialization</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['doctor_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['specialization']); ?></td>
                    <td><?php echo htmlspecialchars($row['appointment_date']); ?></td>
                    <td><?php echo htmlspecialchars($row['appointment_time']); ?></td>
                    <td>
                        <span class="badge status-badge <?php echo strtolower($row['status']); ?>">
                            <?php echo ucfirst($row['status']); ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($row['status'] === 'confirmed'): ?>
                            <a href="video.php?roomID=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm">
                                <i class="fas fa-video me-1"></i>Join Call
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Card View (mobile only) -->
    <div class="d-md-none">
        <?php
        $result->data_seek(0); // Reset pointer
        while($row = $result->fetch_assoc()): ?>
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title"><?php echo htmlspecialchars($row['doctor_name']); ?></h5>
                    <h6 class="card-subtitle mb-2 text-muted"><?php echo htmlspecialchars($row['specialization']); ?></h6>
                    <p class="card-text mb-1"><strong>Date:</strong> <?php echo htmlspecialchars($row['appointment_date']); ?></p>
                    <p class="card-text mb-1"><strong>Time:</strong> <?php echo htmlspecialchars($row['appointment_time']); ?></p>
                    <p class="card-text mb-2">
                        <span class="badge status-badge <?php echo strtolower($row['status']); ?>">
                            <?php echo ucfirst($row['status']); ?>
                        </span>
                    </p>
                    <?php if ($row['status'] === 'confirmed'): ?>
                        <a href="video.php?roomID=<?php echo $row['id']; ?>" class="btn btn-primary w-100">
                            <i class="fas fa-video me-1"></i>Join Call
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; ?>
    </div>

<?php else: ?>
    <div class="alert alert-info">You have no appointments booked.</div>
<?php endif; ?>
