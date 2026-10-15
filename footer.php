<?php
$company_name = 'Virtual Chikitsa';
$support_email = 'virtualchikitsa@gmail.com';
$support_phone = '+91 9192939495';
?>

<footer class="mt-5 pt-5 pb-4" style="background: linear-gradient(135deg, #0f172a, #1e293b); color:#cbd5e1;">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-4">
        <h5 class="text-white fw-bold mb-3">
          <i class="fas fa-heartbeat me-2 text-info"></i><?php echo htmlspecialchars($company_name); ?>
        </h5>
        <p class="mb-3">Virtual care for patients and doctors.</p>
        <div class="d-flex gap-2">
          <a href="#" class="btn btn-sm btn-outline-light rounded-circle" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
          <a href="#" class="btn btn-sm btn-outline-light rounded-circle" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
          <a href="#" class="btn btn-sm btn-outline-light rounded-circle" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
          <a href="#" class="btn btn-sm btn-outline-light rounded-circle" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
        </div>
      </div>

      <div class="col-6 col-lg-2">
        <h6 class="text-white fw-semibold mb-3">Platform</h6>
        <ul class="list-unstyled small">
          <li class="mb-2"><a href="index.php" class="text-decoration-none text-light-emphasis">Home</a></li>
          <li class="mb-2"><a href="doctors.php" class="text-decoration-none text-light-emphasis">Find Doctors</a></li>
          <li class="mb-2"><a href="about.php" class="text-decoration-none text-light-emphasis">About</a></li>
          <li><a href="contact.php" class="text-decoration-none text-light-emphasis">Contact</a></li>
        </ul>
      </div>

      <div class="col-6 col-lg-3">
        <h6 class="text-white fw-semibold mb-3">For Users</h6>
        <ul class="list-unstyled small">
          <li class="mb-2"><a href="login.php" class="text-decoration-none text-light-emphasis">Login</a></li>
          <li class="mb-2"><a href="registration.html" class="text-decoration-none text-light-emphasis">Create Account</a></li>
          <li class="mb-2"><span class="text-light-emphasis">Video Consultations</span></li>
          <li><span class="text-light-emphasis">Appointment Tracking</span></li>
        </ul>
      </div>

      <div class="col-lg-3">
        <h6 class="text-white fw-semibold mb-3">Support</h6>
        <p class="small mb-1"><i class="fas fa-envelope me-2 text-info"></i><?php echo htmlspecialchars($support_email); ?></p>
        <p class="small mb-1"><i class="fas fa-phone me-2 text-info"></i><?php echo htmlspecialchars($support_phone); ?></p>
        <p class="small mb-0"><i class="fas fa-clock me-2 text-info"></i>Support hours: 9 AM - 7 PM</p>
      </div>
    </div>

    <hr class="my-4 border-secondary-subtle">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 small">
      <p class="mb-0">&copy; <?php echo date('Y'); ?> <span class="text-white fw-semibold"><?php echo htmlspecialchars($company_name); ?></span>. All rights reserved.</p>
      <p class="mb-0">Contact: <?php echo htmlspecialchars($support_email); ?> | <?php echo htmlspecialchars($support_phone); ?></p>
    </div>
  </div>
</footer>

