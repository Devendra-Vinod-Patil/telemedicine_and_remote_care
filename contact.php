<?php
// --- DATABASE CONNECTION (Your code is placed here) ---
include 'database.php';

// --- PAGE LOGIC (This remains unchanged) ---
$feedback_message = '';
$contact_info = ['address' => 'Not available', 'phone' => 'Not available', 'email' => 'Not available'];

$sql_info = "SELECT setting_key, setting_value FROM site_info WHERE setting_key IN ('contact_address', 'contact_phone', 'contact_email')";
if ($result = $conn->query($sql_info)) {
    while ($row = $result->fetch_assoc()) {
        if ($row['setting_key'] == 'contact_address') $contact_info['address'] = htmlspecialchars($row['setting_value']);
        if ($row['setting_key'] == 'contact_phone') $contact_info['phone'] = htmlspecialchars($row['setting_value']);
        if ($row['setting_key'] == 'contact_email') $contact_info['email'] = htmlspecialchars($row['setting_value']);
    }
    $result->free();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING));
    $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
    $subject = trim(filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_STRING));
    $message = trim(filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING));

    if (empty($name) || empty($email) || empty($subject) || empty($message) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $feedback_message = '<div class="alert alert-danger" role="alert">Please fill out all fields correctly.</div>';
    } else {
        $sql = "INSERT INTO contacts (name, email, subject, message) VALUES (?, ?, ?, ?)";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ssss", $name, $email, $subject, $message);
            if ($stmt->execute()) {
                $feedback_message = '<div class="alert alert-success" role="alert">Thank you for your message! We will get back to you shortly.</div>';
            } else {
                $feedback_message = '<div class="alert alert-danger" role="alert">Oops! Something went wrong. Please try again later.</div>';
            }
            $stmt->close();
        }
    }
}
$conn->close();

// --- HEADER ---
include 'header.php';
?>

<style>
    /* Animation for elements fading in */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .fade-in-section {
        animation: fadeIn 0.8s ease-out forwards;
    }
    
    /* Styling for the contact info blocks */
    .contact-info-block {
        transition: all 0.3s ease;
        border-left: 3px solid transparent;
    }
    .contact-info-block:hover {
        transform: translateX(5px);
        border-left-color: var(--terracotta-red);
        background-color: #fdfcf9;
    }
    
    /* Styling for form with floating labels and icons */
    .form-floating .form-control {
        padding-left: 2.5rem; /* Space for icon */
    }
    .form-floating .input-icon {
        position: absolute;
        top: 50%;
        left: 0.75rem;
        transform: translateY(-50%);
        color: #ced4da; /* Bootstrap's default border color */
        z-index: 2;
    }
    .form-floating .form-control:focus ~ .input-icon {
        color: var(--muted-teal); /* Change icon color on focus */
    }
</style>

<div class="container-fluid bg-light-beige py-5">
    <div class="container text-center py-4 fade-in-section">
        <h1 class="display-5 fw-bold text-muted-teal" style="text-shadow: 1px 1px 3px rgba(0,0,0,0.1);">Get In Touch</h1>
        <p class="fs-5 text-secondary col-md-8 mx-auto mt-3">
            Have questions? We're here to help. Reach out to us via the form below or through our contact channels.
        </p>
    </div>
</div>

<div class="container my-5 py-4">
    <div class="row g-5">
        <div class="col-lg-5 fade-in-section" style="animation-delay: 0.2s;">
            <h3 class="fw-bold text-terracotta mb-4">Contact Information</h3>
            <p class="text-secondary mb-4">You can find us at the following address or contact us directly via phone or email.</p>
            
            <div class="contact-info-block p-3 rounded mb-3">
                <div class="d-flex align-items-center">
                    <i class="bi bi-geo-alt-fill fs-2 text-muted-teal me-4"></i>
                    <div>
                        <h5 class="fw-bold mb-0">Address</h5>
                        <p class="text-secondary mb-0"><?php echo $contact_info['address']; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="contact-info-block p-3 rounded mb-3">
                <div class="d-flex align-items-center">
                    <i class="bi bi-telephone-fill fs-2 text-muted-teal me-4"></i>
                    <div>
                        <h5 class="fw-bold mb-0">Phone</h5>
                        <p class="text-secondary mb-0"><?php echo $contact_info['phone']; ?></p>
                    </div>
                </div>
            </div>
            
             <div class="contact-info-block p-3 rounded mb-4">
                <div class="d-flex align-items-center">
                    <i class="bi bi-envelope-fill fs-2 text-muted-teal me-4"></i>
                    <div>
                        <h5 class="fw-bold mb-0">Email</h5>
                        <p class="text-secondary mb-0"><?php echo $contact_info['email']; ?></p>
                    </div>
                </div>
            </div>

            <div class="ratio ratio-16x9 mt-4 rounded-3 shadow-sm overflow-hidden">
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3022.213962669176!2d-73.988242!3d40.757344!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x89c25855c6434221%3A0x71c269b3a4583e73!2sTimes%20Square!5e0!3m2!1sen!2sus!4v1694000000000" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Location Map"></iframe>
            </div>
        </div>

        <div class="col-lg-7 fade-in-section" style="animation-delay: 0.4s;">
             <div class="bg-white p-4 p-md-5 rounded-3 shadow-sm">
                <h3 class="fw-bold text-terracotta mb-4">Send Us a Message</h3>
                
                <?php echo $feedback_message; ?>

                <form action="contact.php" method="post" novalidate>
                    <div class="form-floating mb-3 position-relative">
                        <i class="bi bi-person-fill input-icon"></i>
                        <input type="text" class="form-control" id="name" name="name" placeholder="Full Name" required>
                        <label for="name">Full Name</label>
                    </div>
                    <div class="form-floating mb-3 position-relative">
                        <i class="bi bi-envelope-fill input-icon"></i>
                        <input type="email" class="form-control" id="email" name="email" placeholder="Email Address" required>
                        <label for="email">Email Address</label>
                    </div>
                    <div class="form-floating mb-3 position-relative">
                         <i class="bi bi-chat-left-dots-fill input-icon"></i>
                        <input type="text" class="form-control" id="subject" name="subject" placeholder="Subject" required>
                        <label for="subject">Subject</label>
                    </div>
                    <div class="form-floating mb-3 position-relative">
                        <i class="bi bi-pencil-fill input-icon" style="top: 1.5rem; transform: translateY(-50%);"></i>
                        <textarea class="form-control" id="message" name="message" placeholder="Message" style="height: 120px" required></textarea>
                        <label for="message">Message</label>
                    </div>
                    <button type="submit" class="btn btn-custom rounded-pill px-4 py-2 d-flex align-items-center">
                        <i class="bi bi-send-fill me-2"></i> Send Message
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>