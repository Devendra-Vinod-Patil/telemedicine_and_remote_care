<!-- <?php
session_start();
?> -->

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
    
    <!-- Custom Styles -->
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
            color: #333;
        }
        
        .navbar {
            background-color: var(--terracotta) !important;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            font-weight: 700;
            color: white !important;
            font-size: 1.5rem;
        }
        
        .navbar-nav .nav-link {
            color: rgba(255,255,255,0.85) !important;
            font-weight: 500;
            margin: 0 5px;
            transition: all 0.3s;
        }
        
        .navbar-nav .nav-link:hover {
            color: white !important;
            background-color: rgba(255,255,255,0.1);
            border-radius: 5px;
        }
        
        .btn-primary {
            background-color: var(--muted-teal);
            border-color: var(--muted-teal);
            border-radius: 50px;
            font-weight: 600;
            padding: 10px 25px;
        }
        
        .btn-primary:hover {
            background-color: var(--terracotta);
            border-color: var(--terracotta);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .btn-outline-primary {
            color: var(--muted-teal);
            border-color: var(--muted-teal);
            border-radius: 50px;
            font-weight: 600;
            padding: 10px 25px;
        }
        
        .btn-outline-primary:hover {
            background-color: var(--muted-teal);
            border-color: var(--muted-teal);
            color: white;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s, box-shadow 0.3s;
            background-color: white;
            height: 100%;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .card-title {
            color: var(--muted-teal);
            font-weight: 700;
        }
        
        .hero-section {
            background: linear-gradient(135deg, var(--muted-teal) 0%, var(--light-teal) 100%);
            color: white;
            padding: 100px 0;
            border-radius: 0 0 30px 30px;
        }
        
        .section-title {
            color: var(--muted-teal);
            font-weight: 700;
            margin-bottom: 30px;
            position: relative;
            padding-bottom: 15px;
        }
        
        .section-title:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 3px;
            background-color: var(--terracotta);
        }
        
        .feature-icon {
            background-color: rgba(226, 114, 91, 0.1);
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: var(--terracotta);
            font-size: 1.8rem;
        }
        
        .testimonial-card {
            background-color: white;
            border-left: 4px solid var(--terracotta);
        }
        
        .testimonial-text {
            font-style: italic;
            color: #555;
        }
        
        .testimonial-author {
            font-weight: 700;
            color: var(--muted-teal);
        }
        
        .footer {
            background-color: var(--muted-teal);
            color: var(--light-beige);
            padding: 60px 0 30px;
        }
        
        .footer h5 {
            color: white;
            margin-bottom: 20px;
            font-weight: 700;
        }
        
        .footer a {
            color: rgba(245, 242, 235, 0.8);
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .footer a:hover {
            color: white;
        }
        
        .footer-bottom {
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 20px;
            margin-top: 40px;
        }
        
        .floating-chatbot {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
            background-color: var(--terracotta);
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            transition: all 0.3s;
        }
        
        .floating-chatbot:hover {
            transform: scale(1.1);
            background-color: var(--muted-teal);
            color: white;
        }
        
        .step-number {
            background-color: var(--terracotta);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            margin: 0 auto 15px;
        }
        
        .user-welcome {
            color: white !important;
            font-weight: 600;
            margin-right: 15px;
        }
        
        @media (max-width: 768px) {
            .hero-section {
                padding: 60px 0;
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
                           
                                <a class="nav-link" href="doctors_dashboard.php">
                                    <i class="fas fa-tachometer-alt me-1"></i>Doctor Dashboard
                                </a>
                      
                        <?php elseif($_SESSION['role'] === 'patient'): ?>
                          
                                <a class="nav-link" href="patient_dashboard.php">
                                    <i class="fas fa-tachometer-alt me-1"></i>Patient Dashboard
                                </a>
                      
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
                        <a href="registration.html" class="btn btn-light" style="color: var(--terracotta);">Sign Up</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>