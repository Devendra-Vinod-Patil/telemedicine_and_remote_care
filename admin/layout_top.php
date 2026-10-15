<?php
require_once __DIR__ . '/bootstrap.php';
admin_require_login();

$admin_name = (string)($_SESSION['full_name'] ?? 'Admin');
$active = basename((string)($_SERVER['PHP_SELF'] ?? 'index.php'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin - Virtual-Chikitsa</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root{
      --bg:#f4f7fb;
      --card:#fff;
      --line:#d8e1ea;
      --text:#102033;
      --muted:#607284;
      --brand:#0b4f67;
      --brand2:#0f766e;
      --header-1:#1f3fa8;
      --header-2:#2d5bda;
      --header-3:#2f6fff;
    }
    body{font-family:'Manrope',sans-serif;background:linear-gradient(180deg,#f8fbfd 0%,var(--bg) 100%);color:var(--text);}

    .navbar{
      background: linear-gradient(100deg, var(--header-1) 0%, var(--header-2) 50%, var(--header-3) 100%) !important;
      backdrop-filter: blur(10px);
      box-shadow: 0 14px 34px rgba(15, 23, 42, 0.24);
      border-bottom: 1px solid rgba(255,255,255,0.14);
      padding-top: 10px;
      padding-bottom: 10px;
    }
    .navbar-brand{
      font-weight: 900;
      color: #fff !important;
      font-size: 1.25rem;
      letter-spacing: .3px;
      display: inline-flex;
      align-items: center;
      gap: 12px;
      text-decoration: none;
    }
    .brand-logo-wrap{
      width: 44px;
      height: 44px;
      border-radius: 14px;
      background: rgba(255,255,255,0.14);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      box-shadow: inset 0 0 0 1px rgba(255,255,255,0.18);
      color: #fff;
      font-weight: 900;
      letter-spacing: .4px;
    }
    .brand-text{display:flex;flex-direction:column;line-height:1.05}
    .brand-name{font-weight:900;letter-spacing:.4px;font-size:1.05rem;color:#fff;text-shadow:0 10px 18px rgba(0,0,0,0.2)}
    .brand-subtitle{font-weight:600;font-size:.82rem;color:rgba(255,255,255,0.78)}
    .user-badge{color:#fff !important;font-weight:800;background:rgba(255,255,255,0.16);padding:7px 14px;border-radius:999px}
    .btn-outline-light{border-radius:999px;font-weight:700}
    .navbar-toggler{border:1px solid rgba(255,255,255,0.35);border-radius:14px;padding:8px 10px;box-shadow:inset 0 0 0 1px rgba(255,255,255,0.08)}
    .navbar-toggler:focus{box-shadow:0 0 0 .25rem rgba(255,255,255,0.18)}

    .subnav-shell{background:rgba(255,255,255,0.72);backdrop-filter: blur(8px);border-bottom:1px solid rgba(148,163,184,.18)}
    .subnav-shell .nav-link{font-weight:900;color:var(--muted);border-radius:999px;padding:10px 14px}
    .subnav-shell .nav-link.active{background:rgba(15,118,110,.12);color:var(--brand2);box-shadow:inset 0 0 0 1px rgba(15,118,110,.18)}
    .subnav-shell .nav-link:hover{background:rgba(15,23,42,.06);color:var(--text)}

    .dash-wrap { padding: 28px 0 56px; }
    .dash-card,
    .cardx{
      background:var(--card);
      border:1px solid rgba(148,163,184,.2);
      border-radius:20px;
      box-shadow:0 16px 30px rgba(15,23,42,.07);
    }
    .badge-soft{background:rgba(15,118,110,.12);color:var(--brand2);border:1px solid rgba(15,118,110,.18)}
    .btn-brand{border:0;border-radius:12px;font-weight:900;color:#fff;background:linear-gradient(135deg,var(--brand),var(--brand2))}
    .form-control,.form-select{border-radius:12px;border-color:var(--line);min-height:44px}
    .table thead th { background: #f8fbfd; white-space: nowrap; }
    .table>:not(caption)>*>*{vertical-align:middle}
    .text-muted{color:var(--muted)!important}
    .badge-status { border-radius: 999px; padding: 8px 12px; font-weight: 800; font-size: .82rem; text-transform: capitalize; }
    .status-pending { background: #fff5cc; color: #8a6d00; }
    .status-confirmed { background: #dff6ff; color: #0c5f8a; }
    .status-completed { background: #dff7e7; color: #146c43; }
    .status-cancelled { background: #fde2e4; color: #a61e2d; }
  </style>
</head>
<body>
  <nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
      <a class="navbar-brand" href="index.php">
        <span class="brand-logo-wrap">VC</span>
        <span class="brand-text">
          <span class="brand-name">Virtual-Chikitsa</span>
          <span class="brand-subtitle">admin panel</span>
        </span>
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav" aria-controls="adminNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="adminNav">
        <div class="ms-auto d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center gap-2 mt-3 mt-lg-0">
          <span class="user-badge text-center text-lg-start">
            <i class="fas fa-user-shield me-1"></i>
            Hello, <?= htmlspecialchars($admin_name) ?>
          </span>
          <a class="btn btn-outline-light" href="../index.php" target="_blank" rel="noopener">
            <i class="fas fa-globe me-1"></i>View Website
          </a>
          <a class="btn btn-outline-light" href="change_password.php">
            <i class="fas fa-key me-1"></i>Change password
          </a>
          <a class="btn btn-outline-light" href="logout.php">
            <i class="fas fa-right-from-bracket me-1"></i>Logout
          </a>
        </div>
      </div>
    </div>
  </nav>

  <div class="subnav-shell">
    <div class="container py-3">
      <ul class="nav nav-pills gap-2 flex-wrap">
        <li class="nav-item"><a class="nav-link <?= $active === 'index.php' ? 'active' : '' ?>" href="index.php"><i class="fas fa-chart-line me-1"></i>Dashboard</a></li>
        <li class="nav-item"><a class="nav-link <?= $active === 'doctors.php' ? 'active' : '' ?>" href="doctors.php"><i class="fas fa-user-doctor me-1"></i>Doctors</a></li>
        <li class="nav-item"><a class="nav-link <?= $active === 'patients.php' ? 'active' : '' ?>" href="patients.php"><i class="fas fa-user-injured me-1"></i>Patients</a></li>
        <li class="nav-item"><a class="nav-link <?= $active === 'medicines.php' ? 'active' : '' ?>" href="medicines.php"><i class="fas fa-pills me-1"></i>Medicines</a></li>
      </ul>
    </div>
  </div>

  <div class="container dash-wrap">
