<?php
require_once __DIR__ . '/bootstrap.php';

if (isset($_SESSION['role'], $_SESSION['admin_id']) && $_SESSION['role'] === 'admin') {
    header('Location: index.php');
    exit();
}

$next = trim((string)($_GET['next'] ?? ($_POST['next'] ?? '')));
if ($next !== '') {
    $next = basename($next);
    if (!preg_match('/^[a-zA-Z0-9._-]+\\.php$/', $next)) {
        $next = '';
    }
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string)($_POST['email'] ?? ''));
    $password = trim((string)($_POST['password'] ?? ''));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        $error = 'Please enter a valid email and password.';
    } else {
        $stmt = $conn->prepare('SELECT * FROM admins WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $admin = $result->fetch_assoc();
            if (password_verify($password, (string)$admin['password'])) {
                $_SESSION['role'] = 'admin';
                $_SESSION['admin_id'] = (int)$admin['id'];
                $_SESSION['full_name'] = (string)$admin['full_name'];
                $_SESSION['email'] = (string)$admin['email'];

                header('Location: ' . ($next !== '' ? $next : 'index.php'));
                exit();
            }

            $error = 'Incorrect password.';
        } else {
            $count = $conn->query('SELECT COUNT(*) AS c FROM admins');
            $row = $count ? $count->fetch_assoc() : null;
            $has_admin = (int)($row['c'] ?? 0) > 0;
            $error = $has_admin ? 'Account not found.' : 'No admin exists yet. Create the first admin account.';
        }
    }
}

$count = $conn->query('SELECT COUNT(*) AS c FROM admins');
$row = $count ? $count->fetch_assoc() : null;
$has_admin = (int)($row['c'] ?? 0) > 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login - Virtual-Chikitsa</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root{--bg:#f4f7fb;--card:#fff;--line:#d8e1ea;--text:#102033;--muted:#607284;--brand:#0b4f67;--brand2:#0f766e;}
    body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;background:linear-gradient(180deg,#f8fbfd 0%,var(--bg) 100%);font-family:'Manrope',sans-serif;color:var(--text);}
    .auth{width:100%;max-width:480px;background:var(--card);border:1px solid var(--line);border-radius:24px;box-shadow:0 20px 40px rgba(15,23,42,.08);padding:28px;}
    h1{font-size:1.8rem;font-weight:900;margin:0 0 8px;}
    p{color:var(--muted);margin:0 0 18px;}
    .form-label{font-weight:900}
    .form-control{min-height:50px;border-radius:14px;border-color:var(--line)}
    .btnx{width:100%;min-height:50px;border:0;border-radius:14px;font-weight:900;color:#fff;background:linear-gradient(135deg,var(--brand),var(--brand2))}
    a{color:var(--brand);font-weight:900;text-decoration:none}
  </style>
</head>
<body>
  <div class="auth">
    <h1>Admin Login</h1>
    <p>Manage doctors, patients, and medicines.</p>

    <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (!$has_admin): ?>
      <div class="alert alert-info">
        Admin account is being initialized. Refresh this page once.
      </div>
    <?php endif; ?>

    <form method="POST" class="mt-2">
      <?php if ($next !== ''): ?>
        <input type="hidden" name="next" value="<?= htmlspecialchars($next) ?>">
      <?php endif; ?>

      <div class="mb-3">
        <label class="form-label" for="email">Email</label>
        <input class="form-control" type="email" id="email" name="email" required>
      </div>
      <div class="mb-3">
        <label class="form-label" for="password">Password</label>
        <input class="form-control" type="password" id="password" name="password" required>
      </div>
      <button class="btnx" type="submit">Login</button>
    </form>

    <div class="d-flex justify-content-between mt-3 flex-wrap gap-2">
      <a href="../index.php">Back to site</a>
    </div>
  </div>
</body>
</html>
