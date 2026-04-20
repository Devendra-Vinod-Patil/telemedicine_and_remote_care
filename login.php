<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// DB connection
include 'database.php';

// Handle form submit
$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $role = $_POST['role']; // doctor or patient

    $sql = $role === "doctor" 
        ? "SELECT * FROM doctors WHERE email = ? LIMIT 1" 
        : "SELECT * FROM patients WHERE email = ? LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $userData = $result->fetch_assoc();
        if (password_verify($password, $userData['password'])) {
            $_SESSION['user_id']   = $userData['id'];
            $_SESSION['role']      = $role;
            $_SESSION['full_name'] = $userData['full_name'] ?? '';
            $_SESSION['email']     = $userData['email'];
            $_SESSION['photo']     = ($role === "doctor") 
                                        ? ($userData['photo'] ?? 'default.png') 
                                        : 'default.png';

            header("Location: " . ($role === "doctor" ? "doctors_dashboard.php" : "patient_dashboard.php"));
            exit();
        } else {
            $error = "❌ Invalid password.";
        }
    } else {
        $error = "❌ No account found with that email.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login - TeleMedCare</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Google Fonts - Lato -->
  <link href="https://fonts.googleapis.com/css2?family=Lato:wght@300;400;700&display=swap" rel="stylesheet">

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <!-- Animate.css -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">

  <style>
    :root {
        --terracotta: #e2725b;
        --light-beige: #f5f2eb;
        --muted-teal: #2f3e46;
        --light-teal: #3a4d57;
    }

    body {
        font-family: 'Lato', sans-serif;
        background-color: var(--light-beige);
        min-height: 100vh;
        display: flex;
        align-items: center;
    }

    .login-card {
        background: white;
        border-radius: 20px;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        margin: 20px auto;
        width: 100%;
    }

    .login-header {
        background: linear-gradient(135deg, var(--muted-teal) 0%, var(--light-teal) 100%);
        color: white;
        padding: 25px;
        text-align: center;
    }

    .login-body {
        padding: 30px;
    }

    .btn-primary {
        background-color: var(--muted-teal);
        border-color: var(--muted-teal);
        border-radius: 50px;
        font-weight: 600;
        padding: 12px 30px;
        transition: all 0.3s;
    }

    .btn-primary:hover {
        background-color: var(--terracotta);
        border-color: var(--terracotta);
        transform: translateY(-2px);
    }

    .form-control, .form-select {
        border-radius: 10px;
        padding: 12px 15px;
        border: 1px solid #ddd;
    }

    .form-control:focus, .form-select:focus {
        border-color: var(--terracotta);
        box-shadow: 0 0 0 0.2rem rgba(226, 114, 91, 0.25);
    }

    .back-link {
        color: var(--muted-teal);
        text-decoration: none;
    }

    .back-link:hover {
        color: var(--terracotta);
    }

    /* Responsive font sizes */
    @media (max-width: 768px) {
        .login-header h1 {
            font-size: 1.5rem;
        }
        .login-card {
            padding: 10px;
        }
    }
  </style>
</head>
<body>
  <div class="container login-container">
    <div class="row justify-content-center">
      <!-- Responsive column widths -->
      <div class="col-11 col-md-8 col-lg-6 col-xl-4">
        <div class="login-card animate__animated animate__fadeInUp">
          <div class="login-header">
            <div class="mb-2"><i class="fas fa-heartbeat fa-2x"></i></div>
            <h1 class="h4 fw-bold">Welcome Back</h1>
            <p class="mb-0">Sign in to your TeleMedCare account</p>
          </div>
          <div class="login-body">
            <?php if ($error): ?>
              <div class="alert alert-danger animate__animated animate__shakeX"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" class="needs-validation" novalidate>
              <div class="mb-3">
                <label for="role" class="form-label fw-semibold">Login As</label>
                <select name="role" id="role" class="form-select" required>
                  <option value="">-- Select Role --</option>
                  <option value="doctor">Doctor</option>
                  <option value="patient">Patient</option>
                </select>
                <div class="invalid-feedback">Please select your role.</div>
              </div>

              <div class="mb-3">
                <label for="email" class="form-label fw-semibold">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email" required>
                <div class="invalid-feedback">Please provide a valid email.</div>
              </div>

              <div class="mb-3">
                <label for="password" class="form-label fw-semibold">Password</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required>
                <div class="invalid-feedback">Please provide a password.</div>
              </div>

              <button type="submit" class="btn btn-primary w-100 mb-3">Sign In</button>
            </form>

            <div class="text-center mb-3">
              <p class="mb-0">Don't have an account? <a href="registration.html" class="fw-semibold" style="color: var(--muted-teal);">Sign up here</a></p>
            </div>

            <div class="text-center pt-2 border-top">
              <a href="index.php" class="back-link"><i class="fas fa-arrow-left me-2"></i>Back to Home</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap Bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    // Bootstrap validation
    (function () {
      'use strict'
      var forms = document.querySelectorAll('.needs-validation')
      Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
          if (!form.checkValidity()) {
            event.preventDefault()
            event.stopPropagation()
          }
          form.classList.add('was-validated')
        }, false)
      })
    })()
  </script>
</body>
</html>
