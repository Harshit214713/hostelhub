# HostelHub

A role-based hostel management system built to streamline room allocation, fee tracking, complaint handling, visitor records, announcements, and hostel reporting.

Developed by **Rahil Shah**.

## Features

### Administrator

- Manage hostel blocks, rooms, students, wardens, and announcements
- Track room capacity, occupancy, availability, and maintenance status
- Allocate or reallocate rooms with overbooking protection
- Issue fee records, collect payments, and generate receipts
- Review and resolve complaints
- Monitor visitor entry and exit records
- Generate printable occupancy, pending-fee, and complaint reports

### Warden

- View assigned block occupancy and room status
- Access student and emergency-contact details
- Review complaints for supervised blocks
- Maintain visitor entry and exit logs
- View block-specific announcements

### Student

- Register and securely sign in
- View profile, room details, roommates, and fee status
- Submit fee-payment details and print receipts
- Lodge and track maintenance complaints
- View visitor history
- Access relevant announcements

## Security and Reliability

- Role-based access control for Admin, Warden, and Student accounts
- Secure password hashing with `password_hash()` and `password_verify()`
- CSRF protection for form submissions
- Prepared SQL statements through PDO
- Session-based authentication and authorization checks
- Database transactions and row locking to prevent room overbooking
- Responsive interface for desktop and mobile devices

## Technology Stack

| Layer | Technology |
| --- | --- |
| Backend | PHP 8+ |
| Database | MySQL / MariaDB |
| Frontend | HTML5, CSS3, JavaScript, Bootstrap 5 |
| Database Access | PDO with prepared statements |
| Local Environment | XAMPP or PHP built-in server |

## Project Structure

```text
hms/
├── admin/          # Administrator dashboard and management modules
├── assets/         # CSS, JavaScript, icons, and other static assets
├── auth/           # Login, registration, and logout
├── config/         # Application and database configuration
├── includes/       # Shared layout, authentication, CSRF, and helper files
├── screenshots/    # Project UI screenshots
├── student/        # Student portal modules
├── warden/         # Warden portal modules
├── index.php       # Landing page
├── database.sql    # Database schema and demo records
└── README.md
```

## Local Setup

### Prerequisites

- PHP 8 or later
- MySQL or MariaDB
- Apache through XAMPP, or the PHP built-in development server

### Option 1: Run with XAMPP

1. Copy the project folder into:

   ```text
   C:\xampp\htdocs\hms
   ```

2. Start **Apache** and **MySQL** from the XAMPP Control Panel.

3. Open phpMyAdmin:

   ```text
   http://localhost/phpmyadmin
   ```

4. Create a database named:

   ```sql
   hostelhub_db
   ```

5. Import the project’s `database.sql` file into `hostelhub_db`.

6. Check the database connection settings in:

   ```text
   config/database.php
   ```

7. Open the application:

   ```text
   http://localhost/hms
   ```

### Option 2: Run with PHP Built-in Server

1. Import `database.sql` into MySQL/MariaDB.

2. Confirm the database settings in `config/database.php`.

3. Open a terminal in the project folder and run:

   ```bash
   php -S 127.0.0.1:8080
   ```

4. Open:

   ```text
   http://127.0.0.1:8080
   ```

## Demo Credentials

All demo accounts use the password:

```text
password123
```

| Role | Username | Purpose |
| --- | --- | --- |
| Admin | `admin` | Full system administration |
| Warden | `warden_patil` | Block A warden |
| Warden | `warden_sunita` | Block B warden |
| Student | `rahil` | Primary student demo account |
| Student | `tejas` | Student demo account |
| Student | `varad` | Student demo account |
| Student | `ananya` | Student demo account |
| Student | `sneha` | Student demo account |

> Change all demo passwords before using the project in a public or production environment.



## Future Enhancements

- Online payment-gateway integration
- Email and SMS notifications
- File attachments for complaints
- QR-based visitor verification
- Advanced analytics and exportable reports
- Dedicated mobile application

## Project Creator

**Rahil Shah**