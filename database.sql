-- HostelHub - College Hostel Management System
-- Database Schema & Sample Records
-- Compatible with MySQL 5.7+ / MySQL 8.0+ / MariaDB (XAMPP Default)

CREATE DATABASE IF NOT EXISTS `hostelhub_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `hostelhub_db`;

-- Drop existing tables in reverse dependency order
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `room_allocations`;
DROP TABLE IF EXISTS `complaints`;
DROP TABLE IF EXISTS `visitors`;
DROP TABLE IF EXISTS `fees`;
DROP TABLE IF EXISTS `announcements`;
DROP TABLE IF EXISTS `logins`;
DROP TABLE IF EXISTS `students`;
DROP TABLE IF EXISTS `wardens`;
DROP TABLE IF EXISTS `admins`;
DROP TABLE IF EXISTS `rooms`;
DROP TABLE IF EXISTS `blocks`;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. BLOCKS TABLE
CREATE TABLE `blocks` (
    `BlockID` INT AUTO_INCREMENT PRIMARY KEY,
    `BlockName` VARCHAR(50) NOT NULL UNIQUE,
    `Gender` ENUM('Boys', 'Girls', 'Coed') NOT NULL,
    `Description` VARCHAR(255) NULL,
    `CreatedAt` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. ROOMS TABLE
CREATE TABLE `rooms` (
    `RoomID` INT AUTO_INCREMENT PRIMARY KEY,
    `RoomNumber` VARCHAR(20) NOT NULL,
    `Type` VARCHAR(50) NOT NULL DEFAULT 'Double',
    `Capacity` INT NOT NULL DEFAULT 2,
    `Occupied` INT NOT NULL DEFAULT 0,
    `FeePerSemester` DECIMAL(10,2) NOT NULL DEFAULT 25000.00,
    `Status` ENUM('Available', 'Full', 'Maintenance') NOT NULL DEFAULT 'Available',
    `BlockID` INT NOT NULL,
    `CreatedAt` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`BlockID`) REFERENCES `blocks`(`BlockID`) ON DELETE CASCADE,
    UNIQUE KEY `unique_block_room` (`BlockID`, `RoomNumber`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. ADMINS TABLE
CREATE TABLE `admins` (
    `AdminID` INT AUTO_INCREMENT PRIMARY KEY,
    `Name` VARCHAR(100) NOT NULL,
    `Username` VARCHAR(50) NOT NULL UNIQUE,
    `Email` VARCHAR(100) NOT NULL UNIQUE,
    `Phone` VARCHAR(20) NOT NULL DEFAULT '+91 9876543210',
    `Role` VARCHAR(50) NOT NULL DEFAULT 'Admin',
    `CreatedAt` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. WARDENS TABLE
CREATE TABLE `wardens` (
    `WardenID` INT AUTO_INCREMENT PRIMARY KEY,
    `Name` VARCHAR(100) NOT NULL,
    `Email` VARCHAR(100) NOT NULL UNIQUE,
    `Phone` VARCHAR(20) NOT NULL,
    `BlockID` INT NULL,
    `CreatedAt` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`BlockID`) REFERENCES `blocks`(`BlockID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. STUDENTS TABLE
CREATE TABLE `students` (
    `StudentID` INT AUTO_INCREMENT PRIMARY KEY,
    `Name` VARCHAR(100) NOT NULL,
    `Email` VARCHAR(100) NOT NULL UNIQUE,
    `Phone` VARCHAR(20) NOT NULL,
    `Gender` ENUM('Male', 'Female', 'Other') NOT NULL,
    `Course` VARCHAR(100) NOT NULL,
    `Year` INT NOT NULL DEFAULT 1,
    `EmergencyContact` VARCHAR(100) NOT NULL,
    `EmergencyPhone` VARCHAR(20) NOT NULL,
    `Address` TEXT NULL,
    `RoomID` INT NULL,
    `CreatedAt` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`RoomID`) REFERENCES `rooms`(`RoomID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. LOGINS TABLE (Authentication credentials)
CREATE TABLE `logins` (
    `LoginID` INT AUTO_INCREMENT PRIMARY KEY,
    `Username` VARCHAR(50) NOT NULL UNIQUE,
    `Password` VARCHAR(255) NOT NULL,
    `Role` ENUM('Admin', 'Warden', 'Student') NOT NULL,
    `StudentID` INT NULL UNIQUE,
    `WardenID` INT NULL UNIQUE,
    `AdminID` INT NULL UNIQUE,
    `CreatedAt` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`StudentID`) REFERENCES `students`(`StudentID`) ON DELETE CASCADE,
    FOREIGN KEY (`WardenID`) REFERENCES `wardens`(`WardenID`) ON DELETE CASCADE,
    FOREIGN KEY (`AdminID`) REFERENCES `admins`(`AdminID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. FEES TABLE
CREATE TABLE `fees` (
    `FeeID` INT AUTO_INCREMENT PRIMARY KEY,
    `StudentID` INT NOT NULL,
    `Title` VARCHAR(100) NOT NULL DEFAULT 'Hostel & Mess Fee',
    `DueDate` DATE NOT NULL,
    `Amount` DECIMAL(10,2) NOT NULL,
    `PaidDate` DATE NULL,
    `PaymentMethod` ENUM('Cash', 'UPI', 'Net Banking', 'Card') NULL,
    `TransactionRef` VARCHAR(100) NULL,
    `Status` ENUM('Pending', 'Paid', 'Overdue') NOT NULL DEFAULT 'Pending',
    `CreatedAt` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`StudentID`) REFERENCES `students`(`StudentID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. VISITORS TABLE
CREATE TABLE `visitors` (
    `VisitorID` INT AUTO_INCREMENT PRIMARY KEY,
    `StudentID` INT NOT NULL,
    `Name` VARCHAR(100) NOT NULL,
    `VisitDate` DATE NOT NULL,
    `Relation` VARCHAR(50) NOT NULL,
    `Role` VARCHAR(50) NOT NULL DEFAULT 'Visitor',
    `Phone` VARCHAR(20) NOT NULL,
    `IDProof` VARCHAR(50) NULL,
    `CheckInTime` DATETIME NOT NULL,
    `CheckOutTime` DATETIME NULL,
    `Status` ENUM('Checked In', 'Checked Out') NOT NULL DEFAULT 'Checked In',
    `CreatedAt` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`StudentID`) REFERENCES `students`(`StudentID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. COMPLAINTS TABLE
CREATE TABLE `complaints` (
    `ComplaintID` INT AUTO_INCREMENT PRIMARY KEY,
    `StudentID` INT NOT NULL,
    `Category` ENUM('Electrical', 'Plumbing', 'Carpentry', 'Internet', 'Cleanliness', 'Security', 'Other') NOT NULL DEFAULT 'Other',
    `Title` VARCHAR(150) NOT NULL,
    `Description` TEXT NOT NULL,
    `Date` DATE NOT NULL,
    `Priority` ENUM('Low', 'Medium', 'High') NOT NULL DEFAULT 'Medium',
    `Status` ENUM('Pending', 'In Progress', 'Resolved', 'Rejected') NOT NULL DEFAULT 'Pending',
    `AdminRemarks` TEXT NULL,
    `ResolvedAt` DATETIME NULL,
    `CreatedAt` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`StudentID`) REFERENCES `students`(`StudentID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. ANNOUNCEMENTS TABLE
CREATE TABLE `announcements` (
    `AnnouncementID` INT AUTO_INCREMENT PRIMARY KEY,
    `Title` VARCHAR(200) NOT NULL,
    `Content` TEXT NOT NULL,
    `TargetAudience` ENUM('All', 'Students', 'Wardens') NOT NULL DEFAULT 'All',
    `PostedBy` VARCHAR(100) NOT NULL DEFAULT 'Admin Office',
    `IsPinned` TINYINT(1) NOT NULL DEFAULT 0,
    `CreatedAt` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11. ROOM ALLOCATIONS HISTORY
CREATE TABLE `room_allocations` (
    `AllocationID` INT AUTO_INCREMENT PRIMARY KEY,
    `StudentID` INT NOT NULL,
    `RoomID` INT NOT NULL,
    `AllocationDate` DATE NOT NULL,
    `VacatedDate` DATE NULL,
    `Status` ENUM('Active', 'Transferred', 'Vacated') NOT NULL DEFAULT 'Active',
    `Notes` VARCHAR(255) NULL,
    `CreatedAt` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`StudentID`) REFERENCES `students`(`StudentID`) ON DELETE CASCADE,
    FOREIGN KEY (`RoomID`) REFERENCES `rooms`(`RoomID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- SAMPLE SEED DATA
-- Default Demo Password for all accounts: password123
-- ==========================================

-- Insert Blocks
INSERT INTO `blocks` (`BlockID`, `BlockName`, `Gender`, `Description`) VALUES
(1, 'Block A (Boys Hostel)', 'Boys', 'Main engineering boys wing - 3 floors'),
(2, 'Block B (Girls Hostel)', 'Girls', 'Girls hostel block with biometric security'),
(3, 'Block C (International & PG)', 'Coed', 'Single and deluxe double rooms for PG scholars');

-- Insert Rooms
INSERT INTO `rooms` (`RoomID`, `RoomNumber`, `Type`, `Capacity`, `Occupied`, `FeePerSemester`, `Status`, `BlockID`) VALUES
(1, 'A-101', 'Double', 2, 2, 28000.00, 'Full', 1),
(2, 'A-102', 'Double', 2, 1, 28000.00, 'Available', 1),
(3, 'A-103', 'Triple', 3, 0, 22000.00, 'Available', 1),
(4, 'A-201', 'Single', 1, 0, 36000.00, 'Maintenance', 1),
(5, 'B-101', 'Double', 2, 1, 30000.00, 'Available', 2),
(6, 'B-102', 'Double', 2, 0, 30000.00, 'Available', 2),
(7, 'B-201', 'Single', 1, 1, 38000.00, 'Full', 2),
(8, 'C-101', 'Single', 1, 0, 42000.00, 'Available', 3);

-- Insert Admin Account
INSERT INTO `admins` (`AdminID`, `Name`, `Username`, `Email`, `Phone`, `Role`) VALUES
(1, 'Chief Warden Sharma', 'admin', 'admin@hostelhub.edu', '+91 9822012345', 'Admin');

-- Insert Wardens
INSERT INTO `wardens` (`WardenID`, `Name`, `Email`, `Phone`, `BlockID`) VALUES
(1, 'Ramesh Patil', 'warden.patil@hostelhub.edu', '+91 9422033445', 1),
(2, 'Sunita Deshmukh', 'warden.deshmukh@hostelhub.edu', '+91 9765432109', 2);

-- Insert Students
INSERT INTO `students` (`StudentID`, `Name`, `Email`, `Phone`, `Gender`, `Course`, `Year`, `EmergencyContact`, `EmergencyPhone`, `Address`, `RoomID`) VALUES
(1, 'Rahil Shah', 'rahil@hostelhub.edu', '+91 9123456780', 'Male', 'B.Tech AI & Data Science', 2, 'Mukesh Shah (Father)', '+91 9823011223', 'Flat 402, Royal Palms, Pune', 1),
(2, 'Tejas Sonawane', 'tejas@hostelhub.edu', '+91 9234567891', 'Male', 'B.Tech AI & Data Science', 2, 'Sanjay Sonawane (Father)', '+91 9823044556', 'Plot 12, Anand Nagar, Nashik', 1),
(3, 'Varad Patil', 'varad@hostelhub.edu', '+91 9345678902', 'Male', 'B.Tech Computer Engg', 2, 'Sunil Patil (Father)', '+91 9823077889', 'Shree Krupa Niwas, Jalgaon', 2),
(4, 'Ananya Kulkarni', 'ananya@hostelhub.edu', '+91 9456789013', 'Female', 'B.Tech Information Tech', 3, 'Prakash Kulkarni (Father)', '+91 9823099001', 'Green Acres, Aurangabad', 5),
(5, 'Sneha Joshi', 'sneha@hostelhub.edu', '+91 9567890124', 'Female', 'M.Tech Data Science', 1, 'Mahesh Joshi (Father)', '+91 9823033221', 'Shivneri Colony, Thane', 7);

-- Insert Logins
-- Uses standard bcrypt hash for 'password123': $2y$10$wT4c1e4k1N1mYf4C5E6nO.5b6M7t8U9v0W1x2Y3z4A5B6C7D8E9F or plaintext fallback 'password123'
-- The system's login script will transparently verify and auto-upgrade plaintext to secure hash on first login!
INSERT INTO `logins` (`LoginID`, `Username`, `Password`, `Role`, `StudentID`, `WardenID`, `AdminID`) VALUES
(1, 'admin', 'password123', 'Admin', NULL, NULL, 1),
(2, 'warden_patil', 'password123', 'Warden', NULL, 1, NULL),
(3, 'warden_sunita', 'password123', 'Warden', NULL, 2, NULL),
(4, 'rahil', 'password123', 'Student', 1, NULL, NULL),
(5, 'tejas', 'password123', 'Student', 2, NULL, NULL),
(6, 'varad', 'password123', 'Student', 3, NULL, NULL),
(7, 'ananya', 'password123', 'Student', 4, NULL, NULL),
(8, 'sneha', 'password123', 'Student', 5, NULL, NULL);

-- Insert Allocations
INSERT INTO `room_allocations` (`AllocationID`, `StudentID`, `RoomID`, `AllocationDate`, `VacatedDate`, `Status`, `Notes`) VALUES
(1, 1, 1, '2026-07-01', NULL, 'Active', 'Bed 1 allocated for Academic Year 2026-2027'),
(2, 2, 1, '2026-07-01', NULL, 'Active', 'Bed 2 allocated for Academic Year 2026-2027'),
(3, 3, 2, '2026-07-05', NULL, 'Active', 'Bed 1 allocated'),
(4, 4, 5, '2026-07-10', NULL, 'Active', 'Bed 1 allocated'),
(5, 5, 7, '2026-07-12', NULL, 'Active', 'Single deluxe room allocated');

-- Insert Fees
INSERT INTO `fees` (`FeeID`, `StudentID`, `Title`, `DueDate`, `Amount`, `PaidDate`, `PaymentMethod`, `TransactionRef`, `Status`) VALUES
(1, 1, 'Fall Semester 2026 Hostel & Mess', '2026-08-15', 28000.00, '2026-08-10', 'UPI', 'UPI/260810/987123', 'Paid'),
(2, 2, 'Fall Semester 2026 Hostel & Mess', '2026-08-15', 28000.00, '2026-08-12', 'Net Banking', 'NEFT-88992147', 'Paid'),
(3, 3, 'Fall Semester 2026 Hostel & Mess', '2026-08-15', 28000.00, NULL, NULL, NULL, 'Pending'),
(4, 4, 'Fall Semester 2026 Hostel & Mess', '2026-08-15', 30000.00, '2026-08-14', 'Card', 'TXN-CARD-44129', 'Paid'),
(5, 5, 'Fall Semester 2026 Hostel & Mess', '2026-07-30', 38000.00, NULL, NULL, NULL, 'Overdue');

-- Insert Complaints
INSERT INTO `complaints` (`ComplaintID`, `StudentID`, `Category`, `Title`, `Description`, `Date`, `Priority`, `Status`, `AdminRemarks`, `ResolvedAt`) VALUES
(1, 1, 'Electrical', 'Ceiling fan regulator not working', 'The fan regulator in room A-101 operates only on speed 5. Needs repair or replacement.', '2026-10-15', 'Medium', 'Resolved', 'Electrician replaced the rotary capacitor on Oct 16.', '2026-10-16 14:30:00'),
(2, 3, 'Internet', 'Slow Wi-Fi connectivity on 2nd floor', 'Signals drop frequently in the evenings between 8 PM and 11 PM.', '2026-10-18', 'Low', 'In Progress', 'Campus IT team checking access point router firmware.', NULL),
(3, 4, 'Plumbing', 'Bathroom tap leaking in B-101', 'Water continues to drip from the washbasin faucet.', '2026-10-19', 'High', 'Pending', NULL, NULL);

-- Insert Visitors
INSERT INTO `visitors` (`VisitorID`, `StudentID`, `Name`, `VisitDate`, `Relation`, `Role`, `Phone`, `IDProof`, `CheckInTime`, `CheckOutTime`, `Status`) VALUES
(1, 1, 'Mukesh Shah', '2026-10-14', 'Parent', 'Visitor', '+91 9823011223', 'AADHAAR: ****4512', '2026-10-14 10:30:00', '2026-10-14 16:45:00', 'Checked Out'),
(2, 2, 'Sanjay Sonawane', '2026-10-17', 'Parent', 'Visitor', '+91 9823044556', 'AADHAAR: ****8921', '2026-10-17 11:15:00', '2026-10-17 15:30:00', 'Checked Out'),
(3, 3, 'Akash Patil', '2026-10-20', 'Brother', 'Visitor', '+91 9823077889', 'COLLEGE ID: 412', '2026-10-20 15:00:00', NULL, 'Checked In');

-- Insert Announcements
INSERT INTO `announcements` (`AnnouncementID`, `Title`, `Content`, `TargetAudience`, `PostedBy`, `IsPinned`) VALUES
(1, 'Hostel Annual Maintenance & Geyser Inspection', 'Dear residents, routine electrical and plumbing inspections will be conducted in Block A and Block B on Saturday between 9:00 AM and 1:00 PM. Please ensure your rooms are accessible.', 'All', 'Chief Warden Office', 1),
(2, 'Mess Menu Revision & Feedback Meeting', 'The student mess committee will convene this Thursday at 6:00 PM in the Common Hall. All student representatives are invited.', 'Students', 'Hostel Council', 0),
(3, 'Notice for Wardens: Night Curfew Compliance', 'Wardens are requested to submit the daily hostel entry log by 10:30 PM each night via the portal.', 'Wardens', 'Chief Warden Office', 0);
