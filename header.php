<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_page = $current_page ?? basename($_SERVER['PHP_SELF'] ?? '');
$company_brand = 'Virtual-Chikitsa';
$page_title = $page_title ?? $company_brand;
$full_title = ($page_title === $company_brand) ? $company_brand : ($page_title . ' - ' . $company_brand);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($full_title); ?></title>
    <meta name="theme-color" content="#1f3fa8">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        :root {
            --primary: #1d4ed8;
            --primary-dark: #1e3a8a;
            --accent: #14b8a6;
            --surface: #ffffff;
            --surface-soft: #f8fafc;
            --ink: #0f172a;
            --muted: #64748b;
            --ring: rgba(20, 184, 166, 0.25);
        }

        body {
            font-family: 'Lato', sans-serif;
            background: linear-gradient(180deg, #f8fbff 0%, #f1f5f9 100%);
            color: var(--ink);
        }

        .bg-light-beige { background-color: var(--surface-soft) !important; }
        .text-terracotta { color: var(--primary) !important; }
        .text-muted-teal { color: var(--primary-dark) !important; }

        .navbar {
            background: linear-gradient(100deg, #1f3fa8 0%, #2d5bda 50%, #2f6fff 100%) !important;
            backdrop-filter: blur(10px);
            box-shadow: 0 14px 34px rgba(15, 23, 42, 0.24);
            border-bottom: 1px solid rgba(255,255,255,0.14);
            padding-top: 10px;
            padding-bottom: 10px;
        }

        .navbar-brand {
            font-weight: 800;
            color: #fff !important;
            font-size: 1.5rem;
            letter-spacing: .3px;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .brand-logo-wrap {
            width: 46px;
            height: 46px;
            border-radius: 14px;
            background: rgba(255,255,255,0.14);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: inset 0 0 0 1px rgba(255,255,255,0.18);
            overflow: hidden;
        }

        .brand-logo-img {
            width: 34px;
            height: 34px;
            object-fit: contain;
            display: block;
            filter: drop-shadow(0 2px 10px rgba(0, 0, 0, 0.25));
        }

        .brand-logo-wrap svg {
            width: 34px;
            height: 34px;
            display: block;
        }

        .brand-text {
            display: flex;
            flex-direction: column;
            line-height: 1.05;
        }

        .brand-name {
            font-weight: 900;
            letter-spacing: .4px;
            font-size: clamp(1.05rem, 1.5vw, 1.35rem);
            color: #fff;
            text-shadow: 0 10px 18px rgba(0,0,0,0.2);
        }

        .brand-subtitle {
            font-weight: 600;
            font-size: .82rem;
            color: rgba(255,255,255,0.78);
        }

        .navbar-toggler {
            border: 1px solid rgba(255,255,255,0.35);
            border-radius: 14px;
            padding: 8px 10px;
            box-shadow: inset 0 0 0 1px rgba(255,255,255,0.08);
        }

        .navbar-toggler:focus {
            box-shadow: 0 0 0 .25rem rgba(255,255,255,0.18);
        }

        .navbar-nav .nav-link {
            color: rgba(255,255,255,0.88) !important;
            font-weight: 600;
            margin: 0 4px;
            border-radius: 12px;
            padding: 9px 14px !important;
            transition: transform .25s ease, background-color .25s ease, color .25s ease;
        }

        .navbar-nav .nav-link:hover {
            color: #fff !important;
            background: rgba(255,255,255,0.15);
            transform: translateY(-1px);
        }

        .navbar-nav .nav-link.active {
            background: rgba(255,255,255,0.2);
            color: #fff !important;
            box-shadow: inset 0 0 0 1px rgba(255,255,255,0.16);
        }

        .btn-primary,
        .btn-custom {
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            border: none;
            border-radius: 999px;
            font-weight: 700;
            padding: 10px 24px;
            box-shadow: 0 8px 20px rgba(20, 184, 166, 0.25);
            transition: transform .25s ease, box-shadow .25s ease, filter .25s ease;
        }

        .btn-primary:hover,
        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(20, 184, 166, 0.3);
            filter: brightness(1.03);
        }

        .btn-outline-primary {
            color: var(--primary);
            border-color: rgba(29, 78, 216, 0.35);
            background: #fff;
            border-radius: 999px;
            font-weight: 700;
            padding: 10px 24px;
            transition: all .25s ease;
        }

        .btn-outline-primary:hover {
            background: var(--primary);
            border-color: var(--primary);
            color: #fff;
            transform: translateY(-1px);
        }

        .btn-outline-light {
            border-radius: 999px;
            font-weight: 600;
        }

        .btn-light {
            border-radius: 999px;
            font-weight: 700;
        }

        .card {
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 18px;
            background-color: var(--surface);
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
            transition: transform .3s ease, box-shadow .3s ease, border-color .3s ease;
            height: 100%;
        }

        .card:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 36px rgba(15, 23, 42, 0.12);
            border-color: rgba(20, 184, 166, 0.45);
        }

        .card-title {
            color: var(--primary-dark);
            font-weight: 700;
        }

        .user-welcome {
            color: #fff !important;
            font-weight: 700;
            margin-right: 12px;
            background: rgba(255,255,255,0.16);
            padding: 7px 14px;
            border-radius: 999px;
        }

        @media (max-width: 991.98px) {
            .navbar-brand {
                font-size: 1.25rem;
            }

            .brand-subtitle {
                display: none;
            }

            .brand-logo-wrap {
                width: 42px;
                height: 42px;
                border-radius: 13px;
            }

            .brand-logo-img,
            .brand-logo-wrap svg {
                width: 30px;
                height: 30px;
            }

            .navbar-collapse {
                margin-top: 14px;
                background: rgba(8, 16, 42, 0.22);
                border-radius: 18px;
                padding: 14px;
                backdrop-filter: blur(8px);
            }

            .navbar-nav .nav-link {
                margin: 3px 0;
            }

            .user-welcome {
                margin: 10px 0;
                text-align: center;
                display: block;
            }

            .mobile-auth-actions {
                display: grid;
                gap: 10px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation: none !important;
                transition: none !important;
            }
        }
    </style>
    <?php
    // Optional per-page head injection (extra CSS/links).
    if (!empty($extra_head)) {
        echo $extra_head;
    }
    ?>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container-fluid px-3 px-lg-4">
            <a class="navbar-brand" href="index.php" aria-label="Virtual Chikitsa">
                <span class="brand-logo-wrap" aria-hidden="true">
                    <img
                        class="brand-logo-img"
                        src="assets/virtual-chikitsa-logo.svg?v=1"
                        alt="Virtual-Chikitsa"
                        onerror="this.style.display='none'; var el=document.getElementById('brandSvg'); if(el){ el.style.display='block'; }"
                    >
                    <!-- Inline fallback (same logo) so it still shows even if SVG files are blocked/mis-typed by the server -->
                    <svg id="brandSvg" style="display:none" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 120" role="img" aria-label="Virtual Chikitsa">
                        <g fill="#ffffff" transform="translate(18 12)">
                            <path d="M42 18c-14 0-28 7-36 18 11-7 22-8 35-4-12 0-21 3-31 11 14-3 24-2 37 5-11 1-18 4-25 11 14-4 22-4 35 2 12-6 21-6 35-2-7-7-14-10-25-11 13-7 23-8 37-5-10-8-19-11-31-11 13-4 24-3 35 4C70 25 56 18 42 18z" opacity="0.95"/>
                            <rect x="38" y="26" width="8" height="72" rx="4"/>
                            <circle cx="42" cy="20" r="9"/>
                            <path d="M26 48c12-7 24-7 35 0 9 5 9 15 0 20-8 5-15 5-22 0-6-4-6-9 0-13 7-5 15-5 22 0" fill="none" stroke="#ffffff" stroke-width="7" stroke-linecap="round"/>
                            <path d="M58 78c-12 7-24 7-35 0-9-5-9-15 0-20 8-5 15-5 22 0 6 4 6 9 0 13-7 5-15 5-22 0" fill="none" stroke="#ffffff" stroke-width="7" stroke-linecap="round"/>
                        </g>
                    </svg>
                </span>
                <span class="brand-text">
                    <span class="brand-name"><?php echo htmlspecialchars($company_brand); ?></span>
                    <span class="brand-subtitle">online medical consultation</span>
                </span>
            </a>
            <button class="navbar-toggler" id="navbarToggle" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'index.php' ? 'active' : '' ?>" href="index.php">
                            <i class="fas fa-house me-1"></i>Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'doctors.php' ? 'active' : '' ?>" href="doctors.php">
                            <i class="fas fa-user-doctor me-1"></i>Doctors
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'about.php' ? 'active' : '' ?>" href="about.php">
                            <i class="fas fa-circle-info me-1"></i>About
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'contact.php' ? 'active' : '' ?>" href="contact.php">
                            <i class="fas fa-envelope me-1"></i>Contact
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'chatbot/index.php' ? 'active' : '' ?>" href="/chatbot/index.php">
                            <i class="fas fa-robot me-1"></i>Chatbot
                        </a>
                    </li>
                    <?php if (isset($_SESSION['user_id']) && isset($_SESSION['role'])): ?>
                        <?php if ($_SESSION['role'] === 'doctor'): ?>
                            <li class="nav-item">
                                <a class="nav-link <?= $current_page === 'doctors_dashboard.php' ? 'active' : '' ?>" href="doctors_dashboard.php">
                                    <i class="fas fa-tachometer-alt me-1"></i>Doctor Dashboard
                                </a>
                            </li>
                        <?php elseif ($_SESSION['role'] === 'patient'): ?>
                            <li class="nav-item">
                                <a class="nav-link <?= $current_page === 'patient_dashboard.php' ? 'active' : '' ?>" href="patient_dashboard.php">
                                    <i class="fas fa-tachometer-alt me-1"></i>Patient Dashboard
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>

                <div class="ms-lg-3 mt-3 mt-lg-0 mobile-auth-actions">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <span class="user-welcome d-none d-lg-inline">
                            <i class="fas fa-user me-1"></i>
                            Welcome,
                            <?php
                            if (($_SESSION['role'] ?? '') === 'doctor') {
                                echo 'Dr. ' . htmlspecialchars($_SESSION['full_name'] ?? '');
                            } else {
                                echo htmlspecialchars($_SESSION['full_name'] ?? '');
                            }
                            ?>
                        </span>
                        <a href="logout.php" class="btn btn-outline-light">
                            <i class="fas fa-sign-out-alt me-1"></i>Logout
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-outline-light me-lg-2">Login</a>
                        <a href="registration.html" class="btn btn-light" style="color: var(--primary-dark);">Sign Up</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function () {
            var toggle = document.getElementById('navbarToggle');
            var nav = document.getElementById('navbarNav');
            if (!toggle || !nav) return;

            toggle.addEventListener('click', function () {
                if (window.bootstrap && bootstrap.Collapse) {
                    return;
                }
                nav.classList.toggle('show');
                var expanded = nav.classList.contains('show');
                toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
            });
        })();
    </script>
