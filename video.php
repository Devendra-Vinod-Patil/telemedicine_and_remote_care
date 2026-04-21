<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header('Location: login.php');
    exit();
}

include 'database.php';

$userId = (int) $_SESSION['user_id'];
$role = $_SESSION['role']; // doctor / patient

$appointment_id = isset($_GET['appointment_id']) ? (int) $_GET['appointment_id'] : 0;
if ($appointment_id <= 0) {
    die('Invalid appointment.');
}

$fullName = 'Unknown User';
$photo = 'default.png';
$specialization = '';

if ($role === 'doctor') {
    $sql = 'SELECT full_name, specialization, photo FROM doctors WHERE id = ? LIMIT 1';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $fullName = $row['full_name'] ?? $fullName;
        $specialization = $row['specialization'] ?: 'General Practitioner';
        $photo = !empty($row['photo']) ? $row['photo'] : $photo;
    }
} elseif ($role === 'patient') {
    $sql = 'SELECT full_name FROM patients WHERE id = ? LIMIT 1';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $fullName = $row['full_name'] ?? $fullName;
    }
} else {
    die('Invalid role.');
}

// Fetch or create room_id for appointment
$sql = 'SELECT room_id FROM appointments WHERE id = ? LIMIT 1';
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $appointment_id);
$stmt->execute();
$res = $stmt->get_result();

$roomID = '';
if ($res && $res->num_rows > 0) {
    $row = $res->fetch_assoc();
    if (!empty($row['room_id'])) {
        $roomID = $row['room_id'];
    } else {
        $roomID = uniqid('room_', true);
        $update = 'UPDATE appointments SET room_id = ? WHERE id = ?';
        $stmt2 = $conn->prepare($update);
        $stmt2->bind_param('si', $roomID, $appointment_id);
        $stmt2->execute();
    }
} else {
    die('Appointment not found.');
}

$roleLabel = ucfirst($role);
$page_title = 'Video Call';
$extra_head = <<<'HTML'
  <style>
    body { background: #f8f9fa; font-family: 'Segoe UI', system-ui, sans-serif; }
    .user-card { background: white; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); padding: 1rem; margin-bottom: 1rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; }
    .user-info { display: flex; align-items: center; flex-wrap: wrap; }
    .user-avatar { width: 60px; height: 60px; border-radius: 50%; object-fit: cover; margin-right: 15px; border: 2px solid #fff; box-shadow: 0 2px 6px rgba(0,0,0,0.15); }
    .video-container { background: #000; border-radius: 12px; overflow: hidden; height: 75vh; position: relative; display: flex; justify-content: center; align-items: center; }
    .self-video { position: absolute; bottom: 90px; right: 15px; width: 180px; height: 130px; border-radius: 8px; overflow: hidden; border: 2px solid #fff; z-index: 20; }
    .call-controls { position: absolute; bottom: 12px; left: 50%; transform: translateX(-50%); display: flex; flex-wrap: wrap; gap: 15px; justify-content: center; width: 95%; z-index: 50; }
    .control-btn { width: 56px; height: 56px; border-radius: 50%; border: none; font-size: 1.3rem; display: flex; align-items: center; justify-content: center; box-shadow: 0 3px 6px rgba(0,0,0,0.25); transition: 0.25s; flex-shrink: 0; }
    .control-btn:hover { transform: scale(1.06); }
    .btn-mute { background: #ffc107; color: #000; }
    .btn-video { background: #198754; color: #fff; }
    .btn-end   { background: #dc3545; color: #fff; width: 64px; height: 64px; }
    .btn-chat  { background: #0dcaf0; color: #fff; }
    @media (max-width: 768px) { .video-container { height: 100vh; border-radius: 0; } .self-video { width: 120px; height: 90px; bottom: 80px; right: 10px; } .control-btn { width: 48px; height: 48px; font-size: 1.1rem; } .btn-end { width: 54px; height: 54px; } }
    @media (max-width: 480px) { .self-video { width: 100px; height: 75px; bottom: 70px; right: 8px; } .control-btn { width: 44px; height: 44px; font-size: 1rem; } .btn-end { width: 50px; height: 50px; } }
  </style>
HTML;

include 'header.php';
?>

<div class="container py-3">
  <div class="user-card">
    <div class="user-info">
      <img src="<?php echo htmlspecialchars($photo); ?>" alt="User Photo" class="user-avatar">
      <div>
        <h5 class="mb-0"><?php echo ($role === 'doctor' ? 'Dr. ' : '') . htmlspecialchars($fullName); ?></h5>
        <small class="text-muted">
          <?php echo htmlspecialchars($roleLabel); ?>
          <?php if ($role === 'doctor') echo ' • ' . htmlspecialchars($specialization); ?>
        </small>
      </div>
    </div>
    <div class="d-none d-md-block text-end">
      <span class="fw-bold text-primary">Virtual-Chikitsa</span><br>
      <small class="text-muted">Secure Video Consultation</small>
    </div>
  </div>

  <div class="video-container" id="root">
    <div class="self-video" id="selfVideo"></div>
    <div class="call-controls">
      <button class="control-btn btn-mute" title="Mute/Unmute" type="button"><i class="fas fa-microphone"></i></button>
      <button class="control-btn btn-video" title="Video On/Off" type="button"><i class="fas fa-video"></i></button>
      <button class="control-btn btn-end" title="End Call" type="button"><i class="fas fa-phone-slash"></i></button>
      <button class="control-btn btn-chat" title="Chat" type="button"><i class="fas fa-comment-dots"></i></button>
    </div>
  </div>
</div>

<script src="https://unpkg.com/@zegocloud/zego-uikit-prebuilt/zego-uikit-prebuilt.js"></script>
<script>
window.onload = function () {
  const roomID = "<?php echo htmlspecialchars($roomID); ?>";
  const userID = "<?php echo (string) $userId; ?>";
  const userName = "<?php echo htmlspecialchars($fullName . ' (' . $roleLabel . ')'); ?>";
  const backUrl = "<?php echo ($role === 'doctor') ? 'doctors_dashboard.php' : 'patient_dashboard.php'; ?>";

  const appID = 1020134815;
  const serverSecret = "c8717ab0bf61cb585b8df2d2d0c063ee";

  const kitToken = ZegoUIKitPrebuilt.generateKitTokenForTest(appID, serverSecret, roomID, userID, userName);
  const zp = ZegoUIKitPrebuilt.create(kitToken);

  zp.joinRoom({
    container: document.querySelector("#root"),
    scenario: { mode: ZegoUIKitPrebuilt.VideoConference },
    maxUsers: 2,
    showScreenSharingButton: true,
    showTextChat: true,
    showUserList: true,
  });

  document.querySelector('.btn-mute')?.addEventListener('click', () => zp.microphone.toggle());
  document.querySelector('.btn-video')?.addEventListener('click', () => zp.camera.toggle());
  document.querySelector('.btn-end')?.addEventListener('click', () => {
    if (confirm('End the call?')) {
      zp.leaveRoom();
      window.location.href = backUrl;
    }
  });
  document.querySelector('.btn-chat')?.addEventListener('click', () => zp.chat.toggle());
}
</script>

<?php include 'footer.php'; ?>
</body>
</html>

<?php $conn->close(); ?>

