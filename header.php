<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TeleMedCare - Virtual Healthcare Solutions</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Google Fonts - Lato -->
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Custom Styles -->
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
            background: linear-gradient(90deg, rgba(30, 58, 138, 0.95), rgba(29, 78, 216, 0.95)) !important;
            backdrop-filter: blur(8px);
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.2);
        }

        .navbar-brand {
            font-weight: 700;
            color: #fff !important;
            font-size: 1.4rem;
            letter-spacing: .2px;
        }

        .navbar-toggler {
            border: 1px solid rgba(255,255,255,0.35);
        }

        .navbar-nav .nav-link {
            color: rgba(255,255,255,0.88) !important;
            font-weight: 600;
            margin: 0 4px;
            border-radius: 10px;
            padding: 8px 14px !important;
            transition: transform .25s ease, background-color .25s ease;
        }

        .navbar-nav .nav-link:hover {
            color: #fff !important;
            background: rgba(255,255,255,0.15);
            transform: translateY(-1px);
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

        .hero-section {
            position: relative;
            overflow: hidden;
            background: linear-gradient(130deg, #1e3a8a 0%, #1d4ed8 45%, #14b8a6 100%);
            color: #fff;
            padding: 110px 0;
            border-radius: 0 0 36px 36px;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at 20% 30%, rgba(255,255,255,0.18), transparent 45%),
                        radial-gradient(circle at 80% 70%, rgba(255,255,255,0.16), transparent 45%);
            animation: pulseGlow 8s ease-in-out infinite;
            pointer-events: none;
        }

        .hero-section > .container {
            position: relative;
            z-index: 2;
        }

        .section-title {
            color: var(--primary-dark);
            font-weight: 700;
            margin-bottom: 30px;
            position: relative;
            padding-bottom: 12px;
        }

        .section-title:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 64px;
            height: 3px;
            border-radius: 999px;
            background: linear-gradient(90deg, var(--primary), var(--accent));
        }

        .feature-icon {
            background: linear-gradient(135deg, rgba(29, 78, 216, 0.12), rgba(20, 184, 166, 0.2));
            width: 72px;
            height: 72px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: var(--primary);
            font-size: 1.8rem;
        }

        .testimonial-card {
            background-color: #fff;
            border-left: 4px solid var(--accent);
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.07);
        }

        .testimonial-text {
            font-style: italic;
            color: #475569;
        }

        .testimonial-author {
            font-weight: 700;
            color: var(--primary-dark);
        }

        .step-number {
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: #fff;
            width: 42px;
            height: 42px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            margin: 0 auto 15px;
            box-shadow: 0 8px 18px rgba(20, 184, 166, 0.25);
        }

        .user-welcome {
            color: #fff !important;
            font-weight: 600;
            margin-right: 12px;
            background: rgba(255,255,255,0.12);
            padding: 6px 12px;
            border-radius: 999px;
        }

        .floating-chatbot {
            position: fixed;
            bottom: 28px;
            right: 28px;
            z-index: 1000;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            width: 58px;
            height: 58px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1.4rem;
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.28);
            transition: transform .25s ease;
        }

        .floating-chatbot:hover {
            transform: translateY(-3px) scale(1.05);
            color: #fff;
        }

        @keyframes pulseGlow {
            0%, 100% { opacity: 0.75; }
            50% { opacity: 1; }
        }

        @media (max-width: 768px) {
            .hero-section {
                padding: 72px 0;
                text-align: center;
            }

            .section-title {
                text-align: center;
            }

            .section-title:after {
                left: 50%;
                transform: translateX(-50%);
            }

            .user-welcome {
                margin: 10px 0;
                text-align: center;
                display: block;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation: none !important;
                transition: none !important;
            }
        }
    </style>
</head>
<body>
    <!-- Header/Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-heartbeat me-2"></i>Virtual-Chikitsa
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="doctors.php">Doctors</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">Contact</a>
                    </li>
                    
                    <!-- Conditionally show dashboard links based on login status and role -->
                    <?php if(isset($_SESSION['user_id']) && isset($_SESSION['role'])): ?>
                        <?php if($_SESSION['role'] === 'doctor'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="doctors_dashboard.php">
                                    <i class="fas fa-tachometer-alt me-1"></i>Doctor Dashboard
                                </a>
                            </li>
                        <?php elseif($_SESSION['role'] === 'patient'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="patient_dashboard.php">
                                    <i class="fas fa-tachometer-alt me-1"></i>Patient Dashboard
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
                
                <div class="ms-lg-3 mt-3 mt-lg-0">
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <!-- Show welcome message and logout when user is logged in -->
                        <span class="user-welcome d-none d-lg-inline">
                            <i class="fas fa-user me-1"></i>
                            Welcome, 
                            <?php 
                            if($_SESSION['role'] === 'doctor') {
                                echo 'Dr. ' . $_SESSION['full_name'];
                            } else {
                                echo $_SESSION['full_name'];
                            }
                            ?>
                        </span>
                        <a href="logout.php" class="btn btn-outline-light">
                            <i class="fas fa-sign-out-alt me-1"></i>Logout
                        </a>
                    <?php else: ?>
                        <!-- Show login/signup when user is not logged in -->
                        <a href="login.php" class="btn btn-outline-light me-2">Login</a>
                        <a href="registration.html" class="btn btn-light" style="color: var(--primary-dark);">Sign Up</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
