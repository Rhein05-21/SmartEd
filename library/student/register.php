<?php
session_start();
require_once '../config/db_connect.php';
require_once '../admin/config/mailer_config.php';

// In the PHP processing section
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $firstname = $_POST['firstname'];
        $lastname = $_POST['lastname'];
        $birthdate = $_POST['birthdate'];
        $email = $_POST['email'];
        $password = $_POST['password'];

        // Calculate age
        $today = new DateTime();
        $birth = new DateTime($birthdate);
        $age = $birth->diff($today)->y;

        // Check if email already exists
        // Check if email already exists in students or admin table
        $stmt = $pdo->prepare("SELECT 
            (SELECT COUNT(*) FROM students WHERE email = ?) +
            (SELECT COUNT(*) FROM admin WHERE email = ?) as total");
        $stmt->execute([$email, $email]);
        
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'message' => 'Email already registered in the system']);
            exit();
        }

        // Generate student ID (format: year + 4 digits)
        $year = date('Y');
        $stmt = $pdo->query("SELECT COUNT(*) FROM students");
        $count = $stmt->fetchColumn();
        $student_id = $year . str_pad($count + 1, 4, '0', STR_PAD_LEFT);

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO students (student_id, firstname, lastname, birthdate, age, email, password) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$student_id, $firstname, $lastname, $birthdate, $age, $email, $hash]);
        
        // Update email content
        $emailBody = "
            <h2>Welcome to e-Shelf!</h2>
            <p>Dear $firstname $lastname,</p>
            <p>Thank you for registering with e-Shelf. Your student ID is: <strong>$student_id</strong></p>
            <p>Please keep this ID safe as you will need it to log in to your account.</p>
            <p>Best regards,<br>e-Shelf Team</p>
        ";

        // Send email using the existing mailer configuration
        $mailResult = sendMail($email, 'Welcome to e-Shelf - Your Student ID', $emailBody);
        
        if ($mailResult['success']) {
            echo json_encode(['success' => true, 'message' => 'Registration successful! Your Student ID has been sent to your email.']);
        } else {
            echo json_encode(['success' => true, 'message' => 'Registration successful! Your Student ID is: ' . $student_id . ' (Email delivery failed)']);
        }
    } catch (PDOException $e) {
        error_log("Registration Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()]);
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration - e-Shelf</title>
    <link rel="icon" type="image/x-icon" href="version2.jpg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #000;
            color: #fff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .register-form {
            width: 100%;
            max-width: 800px;
            padding: 40px;
        }
        .form-control {
            background-color: transparent;
            border: 1px solid #333;
            color: #fff;
            padding: 12px;
            margin-bottom: 5px;
            border-color:rgba(45, 161, 152, 0.82);
        }
        .form-control::placeholder {
            color:rgba(255, 255, 255, 0.81);
            opacity: 1;
        }
        .form-control:focus {
            background-color: transparent;
            color: #fff;
            border-color: #00d8c7;
            box-shadow: none;
        }
        .row {
            margin-bottom: 20px;
            --bs-gutter-x: 1rem;
        }
        .form-group {
            display: inline-block;
            width: calc(33.333% - 10px);
            margin-right: 15px;
        }
        .form-group:last-child {
            margin-right: 0;
        }
        .form-control:focus {
            background-color: transparent;
            color: #fff;
            border-color: #00d8c7;
            box-shadow: none;
        }
        /* Date dropdown specific styles */
        select.form-control {
            background-color: #1a1f2c;
            color: #fff;
            cursor: pointer;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23fff' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 16px;
            padding-right: 30px;
        }
        select.form-control option {
            background-color: #1a1f2c;
            color: #fff;
            padding: 8px;
        }
        select.form-control:focus {
            background-color: #1a1f2c;
            border-color: #00d8c7;
        }
        .btn-register {
            width: 100%;
            padding: 12px;
            background-color: #00d8c7;
            border: none;
            color: #000;
            font-weight: bold;
            margin-top: 20px;
            height: 45px;
        }
        .password-requirements {
            font-size: 0.8rem;
            color: #666;
            margin: 20px 0;
        }
        .password-requirements ul {
            list-style-type: none;
            padding-left: 0;
            margin-bottom: 0;
        }
        .links {
            text-align: center;
            margin-top: 20px;
        }
        .links a {
            color: #888;
            text-decoration: none;
        }
        .links a:hover {
            color: #00d8c7;
        }
        .form-row {
            display: flex;
            margin-bottom: 20px;
            gap: 15px;
        }
        .form-row > * {
            flex: 1;
        }
        .date-label {
            display: block;
            margin-bottom: 8px;
            color: #fff;
        }
        /* Modal Styles */
        .modal-content {
            background-color: #1c2331;
            color: #fff;
            border: 1px solid #2d3748;
        }
        .modal-header {
            border-bottom: 1px solid #2d3748;
        }
        .modal-footer {
            border-top: 1px solid #2d3748;
        }
        .modal-body {
            max-height: 400px;
            overflow-y: auto;
        }
        .btn-accept {
            background-color: #00d8c7;
            color: #000;
            border: none;
        }
        .btn-accept:hover {
            background-color: #00a699;
            color: #000;
        }
        .form-check-input:checked {
            background-color: #00d8c7;
            border-color: #00d8c7;
        }
    </style>
</head>
<body>
    <div class="register-form">
        <h1 class="text-center mb-4">Student <span style="color: #00d8c7;">Register</span></h1>
        <p class="text-center text-light mb-5">Create your student portal account</p>

        
        <form id="registerForm" method="POST">
            <div class="form-row">
                <div class="form-group">
                    <input type="text" name="firstname" class="form-control" placeholder="First Name" required>
                </div>
                <div class="form-group">
                    <input type="text" name="lastname" class="form-control" placeholder="Last Name" required>
                </div>
                <div class="form-group">
                    <input type="email" name="email" class="form-control" placeholder="Email" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <select name="birth_day" class="form-control" required>
                        <option value="" disabled selected>Day</option>
                        <?php for($i = 1; $i <= 31; $i++): ?>
                            <option value="<?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?>">
                                <?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <select name="birth_month" class="form-control" required>
                        <option value="" disabled selected>Month</option>
                        <?php 
                        $months = [
                            '01' => 'January', '02' => 'February', '03' => 'March',
                            '04' => 'April', '05' => 'May', '06' => 'June',
                            '07' => 'July', '08' => 'August', '09' => 'September',
                            '10' => 'October', '11' => 'November', '12' => 'December'
                        ];
                        foreach($months as $value => $month): ?>
                            <option value="<?php echo $value; ?>"><?php echo substr($month, 0, 3); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <select name="birth_year" class="form-control" required>
                        <option value="" disabled selected>Year</option>
                        <?php 
                        $currentYear = (int)date('Y');
                        $startYear = $currentYear - 100;
                        for($year = $currentYear; $year >= $startYear; $year--): ?>
                            <option value="<?php echo $year; ?>"><?php echo $year; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group" style="flex: 1;">
                    <div class="position-relative">
                        <input type="password" name="password" class="form-control" placeholder="Password" required>
                        <i class="fas fa-eye position-absolute end-0 top-50 translate-middle-y me-3" 
                           style="cursor: pointer;" 
                           onclick="togglePassword(this)"></i>
                    </div>
                </div>
            </div>

            <div class="password-requirements">
                <p>Password requirements:</p>
                <ul>
                    <li>• Minimum 8 characters</li>
                    <li>• At least one uppercase letter</li>
                    <li>• At least one number</li>
                    <li>• At least one special character (!@#$%^&*())</li>
                    <li>• No spaces allowed</li>
                </ul>
            </div>
            <button type="submit" class="btn btn-register">REGISTER</button>
        </form>

        <div class="links">
            <p>Already have an account? <a href="login.php">Login</a></p>
        </div>
    </div>

    <!-- Terms and Agreement Modal -->
    <div class="modal fade" id="termsModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="termsModalLabel">Terms and Conditions</h5>
                </div>
                <div class="modal-body">
                    <h6 class="mb-3">Data Privacy Act Compliance</h6>
                    <p>This system is for educational purposes only. By using this system, you agree to the following terms:</p>
                    
                    <ol>
                        <li>This system is designed for educational purposes and is not intended for commercial use.</li>
                        <li>All personal data collected will be used solely for educational and administrative purposes.</li>
                        <li>The system complies with the Data Privacy Act of 2012 (Republic Act No. 10173).</li>
                        <li>Personal information will be collected, processed, and stored in accordance with the law.</li>
                        <li>Access to personal data is restricted to authorized personnel only.</li>
                        <li>Users have the right to access, correct, and request deletion of their personal data.</li>
                        <li>The system implements appropriate security measures to protect personal data.</li>
                        <li>Data will not be shared with third parties without explicit consent.</li>
                        <li>Users are responsible for maintaining the confidentiality of their account credentials.</li>
                        <li>Any unauthorized access or misuse of the system is strictly prohibited.</li>
                    </ol>

                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" id="agreeTerms" required>
                        <label class="form-check-label" for="agreeTerms">
                            I have read and agree to the terms and conditions
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="declineBtn">Decline</button>
                    <button type="button" class="btn btn-accept" id="acceptBtn" disabled>Accept</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show modal on page load
        document.addEventListener('DOMContentLoaded', function() {
            const termsModal = new bootstrap.Modal(document.getElementById('termsModal'));
            termsModal.show();

            // Handle terms checkbox
            const agreeTerms = document.getElementById('agreeTerms');
            const acceptBtn = document.getElementById('acceptBtn');
            const declineBtn = document.getElementById('declineBtn');

            agreeTerms.addEventListener('change', function() {
                acceptBtn.disabled = !this.checked;
            });

            acceptBtn.addEventListener('click', function() {
                termsAccepted = true;
                termsModal.hide();
            });

            declineBtn.addEventListener('click', function() {
                // Show notification message
                const notification = document.createElement('div');
                notification.className = 'alert alert-danger position-fixed top-0 start-50 translate-middle-x mt-3';
                notification.style.zIndex = '9999';
                notification.innerHTML = `
                    <div class="d-flex align-items-center">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <span>You failed to meet the requirements. Please try again.</span>
                    </div>
                `;
                document.body.appendChild(notification);

                // Remove notification after 3 seconds and redirect
                setTimeout(() => {
                    notification.remove();
                    window.location.href = 'login.php';
                }, 3000);
            });

            // Prevent modal from closing when clicking outside
            document.getElementById('termsModal').addEventListener('click', function(e) {
                if (e.target === this) {
                    e.preventDefault();
                }
            });
        });

        // Terms and Agreement handling
        let termsAccepted = false;

        document.getElementById('registerForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (!document.getElementById('agreeTerms').checked) {
                const termsModal = new bootstrap.Modal(document.getElementById('termsModal'));
                termsModal.show();
                return;
            }

            // Combine date values
            const day = this.querySelector('select[name="birth_day"]').value;
            const month = this.querySelector('select[name="birth_month"]').value;
            const year = this.querySelector('select[name="birth_year"]').value;
            
            const dateInput = document.createElement('input');
            dateInput.type = 'hidden';
            dateInput.name = 'birthdate';
            dateInput.value = `${year}-${month}-${day}`;
            this.appendChild(dateInput);

            const password = this.querySelector('input[name="password"]').value;
            if (!validatePassword(password)) {
                alert('Password does not meet requirements');
                return;
            }

            fetch('register.php', {
                method: 'POST',
                body: new FormData(this)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    window.location.href = 'login.php';
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred during registration');
            });
        });

        function validatePassword(password) {
            const minLength = password.length >= 8;
            const hasUpperCase = /[A-Z]/.test(password);
            const hasNumber = /[0-9]/.test(password);
            const hasSpecial = /[!@#$%^&*()]/.test(password);
            const noSpaces = !/\s/.test(password);

            return minLength && hasUpperCase && hasNumber && hasSpecial && noSpaces;
        }

        function togglePassword(icon) {
            const passwordInput = icon.previousElementSibling;
            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            } else {
                passwordInput.type = "password";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            }
        }
    </script>
</body>
</html>