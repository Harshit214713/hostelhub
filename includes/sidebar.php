<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/auth.php';

$role = strtolower(current_role());
$currentUri = $_SERVER['SCRIPT_NAME'] ?? '';

function nav_active(string $path): string {
    global $currentUri;
    return (strpos($currentUri, $path) !== false) ? 'active' : '';
}
?>

<div class="sidebar">
    <div class="d-flex align-items-center gap-3 pb-3 mb-3 border-bottom">
        <div class="stat-icon stat-icon-teal" style="width: 40px; height: 40px; font-size: 1.1rem;">
            <i class="bi bi-person-badge"></i>
        </div>
        <div class="overflow-hidden">
            <div class="fw-bold text-dark text-truncate" style="font-size: 0.95rem;">
                <?= htmlspecialchars($_SESSION['user']['Username'] ?? 'User') ?>
            </div>
            <span class="badge bg-secondary" style="font-size: 0.72rem;">
                <?= htmlspecialchars(ucfirst($role)) ?>
            </span>
        </div>
    </div>

    <!-- ADMIN NAVIGATION -->
    <?php if ($role === 'admin'): ?>
        <div class="sidebar-heading">Main Overview</div>
        <ul class="sidebar-nav mb-3">
            <li class="sidebar-item">
                <a href="<?= BASE_URL ?>/admin/dashboard.php" class="sidebar-link <?= nav_active('dashboard.php') ?>">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
            </li>
        </ul>

        <div class="sidebar-heading">Hostel Inventory</div>
        <ul class="sidebar-nav mb-3">
            <li class="sidebar-item">
                <a href="<?= BASE_URL ?>/admin/blocks.php" class="sidebar-link <?= nav_active('blocks.php') ?>">
                    <i class="bi bi-building"></i>
                    <span>Blocks</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= BASE_URL ?>/admin/rooms.php" class="sidebar-link <?= nav_active('rooms.php') ?>">
                    <i class="bi bi-door-closed"></i>
                    <span>Rooms</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= BASE_URL ?>/admin/allocations.php" class="sidebar-link <?= nav_active('allocations.php') ?>">
                    <i class="bi bi-key"></i>
                    <span>Room Allocations</span>
                </a>
            </li>
        </ul>

        <div class="sidebar-heading">Users & Staff</div>
        <ul class="sidebar-nav mb-3">
            <li class="sidebar-item">
                <a href="<?= BASE_URL ?>/admin/students.php" class="sidebar-link <?= nav_active('students.php') ?>">
                    <i class="bi bi-people"></i>
                    <span>Students</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= BASE_URL ?>/admin/wardens.php" class="sidebar-link <?= nav_active('wardens.php') ?>">
                    <i class="bi bi-shield-check"></i>
                    <span>Wardens</span>
                </a>
            </li>
        </ul>

        <div class="sidebar-heading">Operations</div>
        <ul class="sidebar-nav mb-3">
            <li class="sidebar-item">
                <a href="<?= BASE_URL ?>/admin/fees.php" class="sidebar-link <?= nav_active('fees.php') ?>">
                    <i class="bi bi-cash-stack"></i>
                    <span>Fee Records</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= BASE_URL ?>/admin/complaints.php" class="sidebar-link <?= nav_active('complaints.php') ?>">
                    <i class="bi bi-tools"></i>
                    <span>Complaints</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= BASE_URL ?>/admin/visitors.php" class="sidebar-link <?= nav_active('visitors.php') ?>">
                    <i class="bi bi-journal-text"></i>
                    <span>Visitor Log</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= BASE_URL ?>/admin/announcements.php" class="sidebar-link <?= nav_active('announcements.php') ?>">
                    <i class="bi bi-megaphone"></i>
                    <span>Announcements</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= BASE_URL ?>/admin/reports.php" class="sidebar-link <?= nav_active('reports.php') ?>">
                    <i class="bi bi-file-earmark-bar-graph"></i>
                    <span>Reports</span>
                </a>
            </li>
        </ul>

    <!-- WARDEN NAVIGATION -->
    <?php elseif ($role === 'warden'): ?>
        <div class="sidebar-heading">Warden Workspace</div>
        <ul class="sidebar-nav mb-3">
            <li class="sidebar-item">
                <a href="<?= BASE_URL ?>/warden/dashboard.php" class="sidebar-link <?= nav_active('dashboard.php') ?>">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= BASE_URL ?>/warden/rooms.php" class="sidebar-link <?= nav_active('rooms.php') ?>">
                    <i class="bi bi-door-closed"></i>
                    <span>Block & Rooms</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= BASE_URL ?>/warden/students.php" class="sidebar-link <?= nav_active('students.php') ?>">
                    <i class="bi bi-people"></i>
                    <span>Resident Students</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= BASE_URL ?>/warden/complaints.php" class="sidebar-link <?= nav_active('complaints.php') ?>">
                    <i class="bi bi-tools"></i>
                    <span>Complaints</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= BASE_URL ?>/warden/visitors.php" class="sidebar-link <?= nav_active('visitors.php') ?>">
                    <i class="bi bi-journal-text"></i>
                    <span>Visitor Records</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= BASE_URL ?>/warden/announcements.php" class="sidebar-link <?= nav_active('announcements.php') ?>">
                    <i class="bi bi-megaphone"></i>
                    <span>Announcements</span>
                </a>
            </li>
        </ul>

    <!-- STUDENT NAVIGATION -->
    <?php elseif ($role === 'student'): ?>
        <div class="sidebar-heading">Student Portal</div>
        <ul class="sidebar-nav mb-3">
            <li class="sidebar-item">
                <a href="<?= BASE_URL ?>/student/dashboard.php" class="sidebar-link <?= nav_active('dashboard.php') ?>">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= BASE_URL ?>/student/profile.php" class="sidebar-link <?= nav_active('profile.php') ?>">
                    <i class="bi bi-person-lines-fill"></i>
                    <span>My Profile</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= BASE_URL ?>/student/my_room.php" class="sidebar-link <?= nav_active('my_room.php') ?>">
                    <i class="bi bi-door-closed"></i>
                    <span>My Room</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= BASE_URL ?>/student/fees.php" class="sidebar-link <?= nav_active('fees.php') ?>">
                    <i class="bi bi-credit-card"></i>
                    <span>Fee Dues & History</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= BASE_URL ?>/student/complaints.php" class="sidebar-link <?= nav_active('complaints.php') ?>">
                    <i class="bi bi-chat-left-dots"></i>
                    <span>Complaints</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= BASE_URL ?>/student/visitors.php" class="sidebar-link <?= nav_active('visitors.php') ?>">
                    <i class="bi bi-person-check"></i>
                    <span>My Visitors</span>
                </a>
            </li>
        </ul>
    <?php endif; ?>

    <div class="sidebar-heading mt-auto pt-3 border-top">Session</div>
    <ul class="sidebar-nav">
        <li class="sidebar-item">
            <a href="<?= BASE_URL ?>/auth/logout.php" class="sidebar-link text-danger">
                <i class="bi bi-box-arrow-right"></i>
                <span>Sign Out</span>
            </a>
        </li>
    </ul>
</div>
