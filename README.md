# HostelHub - College Hostel Management System

A web-based database application designed to simplify and digitize the management of hostel accommodations within college campuses. Developed for the **Department of Artificial Intelligence & Data Science, K. K. Wagh Institute of Engineering Education and Research, Nashik** (Academic Year 2024-2025).

---

## 👥 Project Team & Credits
- **Tejas Sonawane** (Roll No. 57)
- **Rahil Shah** (Roll No. 49)
- **Varad Patil** (Roll No. 41)
- **Kushagra Vispute** (Roll No. 61)
- **Project Guide:** Prof. Dr. Y. D. Bhise
- **Source Code Repository:** [GitHub - Rahilshah2234/myfe](https://github.com/Rahilshah2234/myfe.git)

---

## 🛠️ Technology Stack
- **Backend:** PHP 8.x (Native PDO, Prepared Statements, Secure Sessions, BCrypt)
- **Database:** MySQL 5.7+ / 8.0+ / MariaDB (InnoDB engine with strict Foreign Keys)
- **Frontend:** HTML5, CSS3, Vanilla JavaScript, Bootstrap 5.3, Bootstrap Icons
- **Design System:** Teal Accent (`#149d96`), Modern card-based UI, Glassmorphism accents, Responsive mobile-friendly layout
- **Local Server:** XAMPP (Apache + MySQL)

---

## 🎯 Key Modules & Features
1. **Landing Page:** Recreated to match the project report screenshot with white navbar, teal accents, hero section, and 3 key feature cards.
2. **Role-Based Access Control (RBAC):** Three distinct roles: **Admin**, **Warden**, and **Student**.
3. **Authentication:** Secure passwords using `password_hash()` (BCRYPT) and `password_verify()` with auto-rehash upgrades and CSRF token protection.
4. **Student Profile:** Complete record tracking (Name, Email, Phone, Gender, Course, Year of Study, Emergency Contact, Emergency Phone, Address).
5. **Hostel Block & Room Management:** Manage blocks (Boys, Girls, Coed), room capacity, and semester fee tariffs.
6. **Room Allocation & Reallocation (Anti-Overbooking):** Atomic database transaction locks that ensure no room is booked beyond its capacity.
7. **Fee Records & Receipts:** Track fees, amounts, due dates, paid dates, and payment statuses (Pending, Paid, Overdue). Includes electronic receipt generator.
8. **Complaint & Grievance Ticketing:** Category-based maintenance requests (Electrical, Plumbing, Wi-Fi, etc.) with real-time status updates and resolution notes.
9. **Visitor Log:** Gatekeeper record of visitor entry/exit time, visited resident, and ID proofs.
10. **Announcements:** Campus notice broadcast system with pinned announcements.
11. **Printable Audit Reports:** Formatted reports for Room Occupancy, Pending Fee Receivables, and Maintenance Logs.

---

## 🚀 Setup & Installation Guide for XAMPP

### Step 1: Clone or Copy the Project into XAMPP
Copy the `hms` folder to your XAMPP web root:
- Windows default: `C:\xampp\htdocs\hms`
*(Or keep in your current directory and create a symlink / virtual host).*

### Step 2: Start Apache and MySQL
1. Open the **XAMPP Control Panel**.
2. Click **Start** next to **Apache**.
3. Click **Start** next to **MySQL**.

### Step 3: Setup the MySQL Database
#### Method A: Automatic Auto-Setup
The system includes an automatic database bootstrapper in `config/database.php`. When you first visit `http://localhost/hms` in your browser, it will automatically create `hostelhub_db` and import `database.sql` if the database does not exist!

#### Method B: Manual Import via phpMyAdmin
1. Open your browser and navigate to `http://localhost/phpmyadmin/`.
2. Click **New** in the left sidebar and create a database named:
   ```sql
   hostelhub_db
   ```
   (Collation: `utf8mb4_unicode_ci`).
3. Select `hostelhub_db` and click on the **Import** tab at the top.
4. Click **Choose File** and select `database.sql` from the project root (`d:\Projects\hms\database.sql`).
5. Click **Import** at the bottom.

### Step 4: Access the Application
Open your browser and navigate to:
```
http://localhost/hms
```

---

## 🔑 Demo Login Credentials
All demo accounts come pre-configured with the password: `password123`

| Role | Username | Password | Notes |
| :--- | :--- | :--- | :--- |
| **Admin** | `admin` | `password123` | Chief Hostel Administrator (Full Access) |
| **Warden** | `warden_patil` | `password123` | Warden for Block A (Boys Hostel) |
| **Warden** | `warden_sunita` | `password123` | Warden for Block B (Girls Hostel) |
| **Student** | `rahil` | `password123` | Rahil Shah (B.Tech AI & Data Science, Rm A-101) |
| **Student** | `tejas` | `password123` | Tejas Sonawane (B.Tech AI & Data Science, Rm A-101) |
| **Student** | `varad` | `password123` | Varad Patil (B.Tech Computer Engg, Rm A-102) |
| **Student** | `ananya` | `password123` | Ananya Kulkarni (B.Tech IT, Rm B-101) |
| **Student** | `sneha` | `password123` | Sneha Joshi (M.Tech Data Science, Rm B-201) |

*(Tip: On the login page, you can also click the quick demo buttons to auto-fill credentials instantly!)*

---

## 📁 Project Directory Structure
```
hms/
├── config/
│   ├── config.php               # Base URL detection, session security & constants
│   └── database.php             # PDO database connection with auto-installer
├── includes/
│   ├── header.php               # Bootstrap 5 head & responsive top navbar
│   ├── footer.php               # Footer with project team & college affiliation
│   ├── sidebar.php              # Dynamic role-based sidebar (Admin, Warden, Student)
│   ├── auth.php                 # RBAC authorization middleware & session checks
│   ├── csrf.php                 # CSRF protection tokens and validation
│   └── helpers.php              # Helper utilities (format_currency, flash messages, badges)
├── auth/
│   ├── login.php                # Unified role-based login with demo auto-fill
│   ├── register.php             # Student registration with full profile fields
│   └── logout.php               # Safe session termination
├── admin/
│   ├── dashboard.php            # Admin KPI metrics, complaint counts & occupancy rates
│   ├── blocks.php               # Manage hostel blocks and wings
│   ├── rooms.php                # Manage rooms, bed capacity, tariffs, and status
│   ├── allocations.php          # Room allocation & reallocation (anti-overbooking)
│   ├── students.php             # Student directory, emergency contacts & profiles
│   ├── wardens.php              # Warden appointment and block supervision assignment
│   ├── fees.php                 # Issue fees, collect payments & electronic receipts
│   ├── complaints.php           # Complaint triage & resolution status management
│   ├── visitors.php             # Campus visitor entry and exit audit log
│   ├── announcements.php        # Broadcast notices with pinning option
│   └── reports.php              # Printable reports (Occupancy, Fees, Complaints)
├── warden/
│   ├── dashboard.php            # Supervised block overview & active visitors
│   ├── rooms.php                # Room-by-room bed occupancy inspection
│   ├── students.php             # Resident student contact & emergency directory
│   ├── complaints.php           # Action maintenance tickets in supervised block
│   ├── visitors.php             # Security gate visitor log (entry/exit)
│   └── announcements.php        # View and post block-specific circulars
├── student/
│   ├── dashboard.php            # Student portal overview & fee reminders
│   ├── profile.php              # View academic profile & update emergency contacts
│   ├── my_room.php              # Allocated room amenities & roommates directory
│   ├── fees.php                 # View fee dues, submit payments & print receipts
│   ├── complaints.php           # Lodge maintenance grievances & track repairs
│   └── visitors.php             # Personal visitor history
├── assets/
│   ├── css/
│   │   └── style.css            # Custom CSS with #149d96 teal accent & clean layouts
│   └── js/
│       └── main.js              # Form validation, live table filters, and print triggers
├── index.php                    # Landing page matching report screenshot (Page 13)
├── database.sql                 # Complete MySQL schema, foreign keys & sample records
├── .gitignore                   # Clean repository ignore list
└── README.md                    # Setup documentation
```

---

## 🐙 Git Repository Commands
To publish or update your GitHub repository:
```bash
# 1. Initialize git (if not already done)
git init

# 2. Add all files
git add .

# 3. Commit
git commit -m "Complete HostelHub project matching college report specifications"

# 4. Set main branch and remote
git branch -M main
git remote add origin https://github.com/Rahilshah2234/myfe.git

# 5. Push to GitHub
git push -u origin main
```
