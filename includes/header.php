<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';

$pageTitle = $pageTitle ?? APP_NAME . ' - ' . APP_TITLE;
$currentUser = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="description" content="HostelHub - Modern, scalable College Hostel Management System for students, wardens and administrators.">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>

<!-- Header / Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-custom sticky-top">
    <div class="container-fluid px-lg-5">
        <a class="navbar-brand-custom" href="<?= BASE_URL ?>/index.php">
            <span class="brand-icon-box">
                <i class="bi bi-buildings"></i>
            </span>
            <span>HostelHub</span>
        </a>
        
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#hostelNavbar" aria-controls="hostelNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="hostelNavbar">
            <!-- Center navigation for landing page -->
            <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link nav-link-custom" href="<?= BASE_URL ?>/index.php#features">Features</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link nav-link-custom" href="<?= BASE_URL ?>/index.php#about">About</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link nav-link-custom" href="<?= BASE_URL ?>/index.php#contact">Contact</a>
                </li>
            </ul>

            <!-- Right action buttons -->
            <div class="d-flex align-items-center gap-2">
                <?php if ($currentUser): ?>
                    <?php 
                        $dashboardUrl = match (strtolower($currentUser['Role'])) {
                            'admin'   => BASE_URL . '/admin/dashboard.php',
                            'warden'  => BASE_URL . '/warden/dashboard.php',
                            'student' => BASE_URL . '/student/dashboard.php',
                            default   => BASE_URL . '/index.php',
                        };
                    ?>
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle"></i>
                            <span><?= htmlspecialchars($currentUser['Username']) ?></span>
                            <span class="badge bg-secondary"><?= htmlspecialchars($currentUser['Role']) ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                            <li><a class="dropdown-item" href="<?= $dashboardUrl ?>"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
                            <?php if (strtolower($currentUser['Role']) === 'student'): ?>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>/student/profile.php"><i class="bi bi-person me-2"></i>My Profile</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Sign Out</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/auth/login.php" class="nav-link-custom text-decoration-none px-3">Login</a>
                    <a href="<?= BASE_URL ?>/auth/register.php" class="btn btn-teal">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
