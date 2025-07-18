# SmartEd - Educational Management System

SmartEd is a comprehensive educational management system that provides features for both administrators and students. The system includes exam management, library management, and user authentication functionalities.

## Features

- **Admin Management**
  - Secure admin login system
  - Password reset functionality with OTP verification
  - Dashboard for managing educational resources

- **Student Management**
  - Student authentication
  - Access to educational resources
  - Exam participation

- **Library Management**
  - Resource cataloging
  - Book management
  - Digital resource access

## Tech Stack

- PHP
- MySQL
- Tailwind CSS
- PHPMailer for email functionality

## Setup Instructions

1. Clone the repository:
   ```bash
   git clone https://github.com/your-username/SmartEd.git
   ```

2. Install dependencies:
   ```bash
   composer install
   ```

3. Configure your database:
   - Create a new MySQL database
   - Copy `shared/db_connect.example.php` to `shared/db_connect.php`
   - Update database credentials in `db_connect.php`

4. Configure email settings:
   - Copy `shared/mailer_config.example.php` to `shared/mailer_config.php`
   - Update email configuration in `mailer_config.php`

5. Set up your web server:
   - Point your web server to the project directory
   - Ensure PHP version 7.4 or higher is installed
   - Enable required PHP extensions (mysqli, mbstring)

## Security

- Passwords are securely hashed
- OTP-based password reset
- Session management
- Input sanitization
- Prepared SQL statements

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details. 