<?php include 'header.php';?>
<?php
require_once "database.php"; // DB connection

$search_query = '';
if (isset($_GET['search'])) {
    $search_query = $conn->real_escape_string($_GET['search']);
}

// Fetch doctors based on search
$sql = "SELECT id, full_name, specialization, experience, clinic, photo 
        FROM doctors 
        WHERE full_name LIKE '%$search_query%' 
           OR specialization LIKE '%$search_query%' 
           OR clinic LIKE '%$search_query%'";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Find Doctors - TeleMedCare</title>
  
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  
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
        color: #333;
    }
    
    .hero-section {
        background: linear-gradient(135deg, var(--muted-teal) 0%, var(--light-teal) 100%);
        color: white;
        padding: 80px 0;
        border-radius: 0 0 30px 30px;
    }
    
    .search-box {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        border-radius: 15px;
        padding: 30px;
        margin-top: 30px;
    }
    
    .search-input {
        background: white;
        border: none;
        border-radius: 10px 0 0 10px;
        padding: 15px 20px;
        font-size: 1rem;
    }
    
    .search-btn {
        background: var(--terracotta);
        border: none;
        border-radius: 0 10px 10px 0;
        color: white;
        padding: 15px 25px;
        transition: all 0.3s;
    }
    
    .search-btn:hover {
        background: #d1654f;
    }
    
    .doctor-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        transition: all 0.3s;
        height: 100%;
        overflow: hidden;
    }
    
    .doctor-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }
    
    .doctor-image-container {
        height: 200px;
        display: flex;
        justify-content: center;
        align-items: center;
        overflow: hidden;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    }
    
    .doctor-image-container img {
        object-fit: contain;
        height: 100%;
        width: auto;
        max-width: 100%;
    }
    
    .doctor-info {
        padding: 20px;
    }
    
    .doctor-name {
        color: var(--muted-teal);
        font-weight: 700;
        margin-bottom: 5px;
    }
    
    .doctor-specialization {
        color: var(--terracotta);
        font-weight: 600;
        margin-bottom: 15px;
    }
    
    .doctor-detail {
        display: flex;
        align-items: center;
        margin-bottom: 8px;
        color: #666;
        font-size: 0.9rem;
    }
    
    .doctor-detail i {
        color: var(--terracotta);
        margin-right: 10px;
        width: 16px;
    }
    
    .book-btn {
        background: var(--muted-teal);
        color: white;
        border: none;
        border-radius: 50px;
        padding: 10px 20px;
        font-weight: 600;
        transition: all 0.3s;
        width: 100%;
        margin-top: 15px;
    }
    
    .book-btn:hover {
        background: var(--terracotta);
        transform: translateY(-2px);
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
    
    .no-doctors {
        text-align: center;
        padding: 60px 20px;
        color: #666;
    }
    
    .no-doctors i {
        font-size: 4rem;
        color: #ddd;
        margin-bottom: 20px;
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .hero-section {
            padding: 60px 0;
            text-align: center;
        }
        
        .search-box {
            padding: 20px;
        }
        
        .section-title {
            text-align: center;
        }
        
        .section-title:after {
            left: 50%;
            transform: translateX(-50%);
        }
        
        .doctor-image-container {
            height: 150px;
        }
    }
  </style>
</head>
<body>
  <!-- Hero Section -->
  <section class="hero-section">
    <div class="container">
      <div class="row">
        <div class="col-lg-8 mx-auto text-center">
          <h1 class="display-5 fw-bold mb-4 animate__animated animate__fadeInDown">Find the Best Doctors</h1>
          <p class="lead mb-4 animate__animated animate__fadeInUp animate__delay-1s">
            Book appointments with top specialists for all your healthcare needs.
          </p>
        </div>
      </div>
      
      <!-- Search Box -->
      <div class="row justify-content-center animate__animated animate__fadeInUp animate__delay-2s">
        <div class="col-lg-8">
          <div class="search-box">
            <form method="GET" action="" class="row g-0">
              <div class="col-md-10">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search_query); ?>" 
                       class="form-control search-input" 
                       placeholder="Search by doctor name, specialty or location...">
              </div>
              <div class="col-md-2">
                <button type="submit" class="btn search-btn w-100">
                  <i class="fas fa-search me-2"></i>Search
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Doctors Section -->
  <section class="py-5">
    <div class="container py-5">
      <div class="row">
        <div class="col-12">
          <h2 class="section-title">Available Doctors</h2>
        </div>
      </div>

      <div class="row g-4">
        <?php if ($result && $result->num_rows > 0): ?>
          <?php while ($row = $result->fetch_assoc()): ?>
            <div class="col-md-6 col-lg-4 animate__animated animate__fadeInUp">
              <div class="doctor-card">
                <div class="doctor-image-container">
                  <?php 
                  // Use the same logic from the working code
                  $photo_path = !empty($row['photo']) && file_exists($row['photo']) ? $row['photo'] : 'default-doctor.png';
                  
                  // If default-doctor.png doesn't exist, use a placeholder
                  if ($photo_path === 'default-doctor.png' && !file_exists('default-doctor.png')) {
                      $photo_path = 'https://via.placeholder.com/300x200/2f3e46/ffffff?text=Doctor+Image';
                  }
                  ?>
                  <img src="<?php echo htmlspecialchars($photo_path); ?>" 
                       alt="Dr. <?php echo htmlspecialchars($row['full_name']); ?>" 
                       onerror="this.src='https://via.placeholder.com/300x200/2f3e46/ffffff?text=Doctor+Image'">
                </div>
                <div class="doctor-info">
                  <h3 class="doctor-name"><?php echo htmlspecialchars($row['full_name']); ?></h3>
                  <p class="doctor-specialization"><?php echo htmlspecialchars($row['specialization']); ?></p>
                  
                  <div class="doctor-detail">
                    <i class="fas fa-map-marker-alt"></i>
                    <span><?php echo htmlspecialchars($row['clinic']); ?></span>
                  </div>
                  
                  <div class="doctor-detail">
                    <i class="fas fa-briefcase"></i>
                    <span><?php echo htmlspecialchars($row['experience']); ?> years experience</span>
                  </div>
                  
                  <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'patient'): ?>
                    <a href="patient_dashboard.php?doctor_id=<?php echo $row['id']; ?>&doctor_name=<?php echo urlencode($row['full_name']); ?>">
                      <button class="book-btn">
                        <i class="fas fa-calendar-check me-2"></i>Book Appointment
                      </button>
                    </a>
                  <?php elseif (!isset($_SESSION['user_id'])): ?>
                    <a href="login.php">
                      <button class="book-btn">
                        <i class="fas fa-sign-in-alt me-2"></i>Login to Book
                      </button>
                    </a>
                  <?php else: ?>
                    <button class="book-btn" disabled>
                      <i class="fas fa-info-circle me-2"></i>Available for Patients
                    </button>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endwhile; ?>
        <?php else: ?>
          <div class="col-12">
            <div class="no-doctors">
              <i class="fas fa-user-md"></i>
              <h3 class="h4 mb-3">No doctors found</h3>
              <p class="mb-4"><?php echo $search_query ? 'Try adjusting your search terms' : 'No doctors are currently available'; ?></p>
              <?php if ($search_query): ?>
                <a href="doctors.php" class="btn btn-primary">View All Doctors</a>
              <?php endif; ?>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- Call to Action Section -->
  <section class="py-5 bg-light">
    <div class="container py-5">
      <div class="row justify-content-center text-center">
        <div class="col-lg-8">
          <h2 class="h3 fw-bold mb-3">Can't Find the Right Specialist?</h2>
          <p class="mb-4">Our team can help match you with the perfect doctor for your needs.</p>
          <a href="contact.php" class="btn btn-primary me-3">Contact Us</a>
          <a href="index.php" class="btn btn-outline-primary">Back to Home</a>
        </div>
      </div>
    </div>
  </section>

  <!-- Bootstrap JS Bundle with Popper -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
  
  <script>
    // Add animation to cards on scroll
    document.addEventListener('DOMContentLoaded', function() {
      const doctorCards = document.querySelectorAll('.doctor-card');
      
      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
          }
        });
      }, { threshold: 0.1 });
      
      doctorCards.forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        observer.observe(card);
      });
      
      // Add hover animation to search button
      const searchBtn = document.querySelector('.search-btn');
      if (searchBtn) {
        searchBtn.addEventListener('mouseenter', function() {
          this.style.transform = 'scale(1.05)';
        });
        
        searchBtn.addEventListener('mouseleave', function() {
          this.style.transform = 'scale(1)';
        });
      }
      
      // Debug: Log image sources to console
      document.querySelectorAll('.doctor-image-container img').forEach(img => {
        console.log('Image source:', img.src);
      });
    });
  </script>
</body>
</html>
<?php include 'footer.php';?>

<?php $conn->close(); ?>