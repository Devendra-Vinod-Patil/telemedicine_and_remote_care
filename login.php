<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'database.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role = $_POST['role'] ?? '';

    if (!in_array($role, ['doctor', 'patient'], true)) {
        $error = 'Please select a role.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        $error = 'Please enter a valid email and password.';
    } else {
        $sql = $role === 'doctor'
            ? 'SELECT * FROM doctors WHERE email = ? LIMIT 1'
            : 'SELECT * FROM patients WHERE email = ? LIMIT 1';

        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $user_data = $result->fetch_assoc();
            if (password_verify($password, $user_data['password'])) {
                $_SESSION['user_id'] = $user_data['id'];
                $_SESSION['role'] = $role;
                $_SESSION['full_name'] = $user_data['full_name'] ?? '';
                $_SESSION['email'] = $user_data['email'] ?? '';
                $_SESSION['photo'] = $user_data['photo'] ?? 'default.png';

                header('Location: ' . ($role === 'doctor' ? 'doctors_dashboard.php' : 'patient_dashboard.php'));
                exit();
            }

            $error = 'Incorrect password.';
        } else {
            $error = 'Account not found.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - Virtual-Chikitsa</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg: #f4f7fb;
      --card: #ffffff;
      --line: #d8e1ea;
      --text: #102033;
      --muted: #607284;
      --brand: #0b4f67;
      --brand-2: #0f766e;
    }

    body {
      margin: 0;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px;
      background: linear-gradient(180deg, #f8fbfd 0%, var(--bg) 100%);
      font-family: 'Manrope', sans-serif;
      color: var(--text);
    }

    .auth-card {
      width: 100%;
      max-width: 460px;
      background: var(--card);
      border: 1px solid var(--line);
      border-radius: 24px;
      box-shadow: 0 20px 40px rgba(15, 23, 42, 0.08);
      padding: 28px;
    }

    .auth-card h1 {
      font-size: 1.9rem;
      font-weight: 800;
      margin-bottom: 8px;
    }

    .auth-card p {
      color: var(--muted);
      margin-bottom: 22px;
    }

    .form-label {
      font-weight: 700;
      margin-bottom: 8px;
    }

    .form-control,
    .form-select {
      min-height: 50px;
      border-radius: 14px;
      border-color: var(--line);
    }

    .btn-login {
      width: 100%;
      min-height: 50px;
      border: none;
      border-radius: 14px;
      font-weight: 800;
      color: #fff;
      background: linear-gradient(135deg, var(--brand), var(--brand-2));
    }

    .auth-links {
      display: flex;
      justify-content: space-between;
      gap: 12px;
      flex-wrap: wrap;
      margin-top: 18px;
      font-size: .95rem;
    }

    .auth-links a {
      color: var(--brand);
      text-decoration: none;
      font-weight: 700;
    }
  </style>
</head>
<body>
  <div class="auth-card">
    <h1>Login</h1>
    <p>Enter your account details.</p>

    <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="mb-3">
        <label class="form-label" for="role">Role</label>
        <select class="form-select" id="role" name="role" required>
          <option value="">Select role</option>
          <option value="patient">Patient</option>
          <option value="doctor">Doctor</option>
        </select>
      </div>

      <div class="mb-3">
        <label class="form-label" for="email">Email</label>
        <input class="form-control" type="email" id="email" name="email" required>
      </div>

      <div class="mb-3">
        <label class="form-label" for="password">Password</label>
        <input class="form-control" type="password" id="password" name="password" required>
      </div>

      <button class="btn btn-login" type="submit">Login</button>
    </form>

    <div class="auth-links">
      <a href="registration.html">Registration</a>
      <a href="index.php">Back</a>
    </div>
  </div>
</body>
</html>
