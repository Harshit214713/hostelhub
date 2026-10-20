<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

// Quick stats for social proof
$totalRoomsCount = $pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
$totalStudentsCount = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$totalBlocksCount = $pdo->query("SELECT COUNT(*) FROM blocks")->fetchColumn();

$pageTitle = 'HostelHub - Modern Hostel Management Made Simple';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Section (Matches Report Screenshot Page 13) -->
<section class="hero-section">
    <div class="container px-3">
        <h1 class="hero-title">
            Modern Hostel <br class="d-sm-none">Management <br class="d-sm-none"><span class="teal-highlight">Made Simple</span>
        </h1>
        <p class="hero-subtitle">
            A comprehensive solution for hostel administrators and students. Manage rooms, payments, and maintenance requests all in one place.
        </p>
        <div class="d-flex justify-content-center gap-2 gap-sm-3 flex-column flex-sm-row mx-auto" style="max-width: 360px;">
            <a href="<?= BASE_URL ?>/auth/register.php" class="btn btn-teal btn-lg px-4 py-2">
                Get Started
            </a>
            <a href="#features" class="btn btn-ghost-muted btn-lg px-4 py-2 d-inline-flex justify-content-center align-items-center gap-2">
                <span>Learn More</span>
                <i class="bi bi-arrow-right"></i>
            </a>
        </div>
    </div>
</section>

<!-- Key Features Section (Matches Report Screenshot Page 13) -->
<section class="features-section" id="features">
    <div class="container px-3 px-lg-5">
        <h2 class="section-heading">Key Features</h2>

        <div class="row g-4">
            <!-- Feature Card 1: Admin Management -->
            <div class="col-md-4">
                <div class="feature-card">
                    <div class="feature-icon-circle">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <h3 class="feature-title">Admin Management</h3>
                    <p class="feature-desc">
                        Complete control over room allocations, student records, fee collections, and maintenance tickets with detailed audit reporting.
                    </p>
                </div>
            </div>

            <!-- Feature Card 2: Student Portal -->
            <div class="col-md-4">
                <div class="feature-card">
                    <div class="feature-icon-circle">
                        <i class="bi bi-person-badge"></i>
                    </div>
                    <h3 class="feature-title">Student Portal</h3>
                    <p class="feature-desc">
                        Easy access to room details, payment history, maintenance complaint filing, electronic receipts, and warden communication.
                    </p>
                </div>
            </div>

            <!-- Feature Card 3: Room Management -->
            <div class="col-md-4">
                <div class="feature-card">
                    <div class="feature-icon-circle">
                        <i class="bi bi-door-closed"></i>
                    </div>
                    <h3 class="feature-title">Room Management</h3>
                    <p class="feature-desc">
                        Efficiently manage room capacity, prevent overbooking, track occupancy in real-time, and handle room reallocation requests seamlessly.
                    </p>
                </div>
            </div>
        </div>

        <!-- Live Metrics Strip -->
        <div class="row g-3 mt-5 pt-3 justify-content-center text-center">
            <div class="col-6 col-md-3">
                <div class="p-3 bg-white rounded-3 border">
                    <div class="fw-bold fs-3 text-dark"><?= number_format($totalBlocksCount) ?></div>
                    <div class="small text-muted fw-semibold">Hostel Blocks</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-3 bg-white rounded-3 border">
                    <div class="fw-bold fs-3 text-dark"><?= number_format($totalRoomsCount) ?></div>
                    <div class="small text-muted fw-semibold">Configured Rooms</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-3 bg-white rounded-3 border">
                    <div class="fw-bold fs-3 text-dark"><?= number_format($totalStudentsCount) ?>+</div>
                    <div class="small text-muted fw-semibold">Active Residents</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-3 bg-white rounded-3 border">
                    <div class="fw-bold fs-3 text-teal">100%</div>
                    <div class="small text-muted fw-semibold">Digital Workflow</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- About Section -->
<section class="py-5 bg-white border-top" id="about">
    <div class="container px-3 px-lg-5">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6">
                <span class="badge bg-light text-teal border px-3 py-2 mb-3 fw-bold" style="color: var(--primary-teal);">ABOUT THE PROJECT</span>
                <h2 class="fw-bold text-dark mb-3">Digitizing Campus Accommodations</h2>
                <p class="text-muted leading-relaxed mb-4">
                    The <strong>College Hostel Management System (HostelHub)</strong> eliminates manual paper registers, handwriting errors, and delayed communication. Built with PHP 8, MySQL, and Bootstrap 5, it provides automated room assignment, anti-overbooking safeguards, fee tracking, visitor logging, and real-time maintenance complaint resolutions.
                </p>
                <div class="p-3 rounded-3 bg-light border">
                    <div class="small text-dark fw-bold mb-1">Project Creator & Developer:</div>
                    <p class="small text-muted mb-0">
                        <strong>Rahil Shah</strong><br>
                        Lead Full-Stack Developer & Designer
                    </p>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="content-card p-4 shadow-sm">
                    <h5 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-person-badge-fill text-teal" style="color: var(--primary-teal);"></i> Project Ownership & Architecture
                    </h5>
                    <div class="d-flex align-items-center gap-3 mb-3 p-3 bg-light rounded-3">
                        <div class="avatar-circle" style="width: 48px; height: 48px; background-color: var(--primary-teal); color: #fff; display: flex; align-items: center; justify-content: center; border-radius: 50%; font-weight: bold; font-size: 1.15rem;">
                            RS
                        </div>
                        <div>
                            <h6 class="mb-0 fw-bold text-dark">Rahil Shah</h6>
                            <span class="small text-muted">Project Creator & Lead Full-Stack Architect</span>
                        </div>
                    </div>
                    <ul class="list-group list-group-flush small">
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-muted">Application</span>
                            <span class="fw-semibold text-dark">HostelHub Management System</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-muted">Technology Stack</span>
                            <span class="badge bg-light text-secondary border">PHP 8 &bull; MySQL &bull; Bootstrap 5</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-muted">Core Modules</span>
                            <span class="fw-semibold text-dark">RBAC, Allocations, Fees, Complaints, Visitors</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Contact / Support Section -->
<section class="py-5 bg-light border-top" id="contact">
    <div class="container px-3 px-lg-5">
        <div class="row justify-content-center text-center">
            <div class="col-lg-7">
                <h3 class="fw-bold text-dark mb-2">Need Assistance with Hostel Admission?</h3>
                <p class="text-muted small mb-4">
                    Hostel administrative office is open Monday to Saturday, 9:00 AM to 5:00 PM for campus room allotment inquiries and verification.
                </p>
                <div class="d-flex justify-content-center gap-3">
                    <a href="<?= BASE_URL ?>/auth/login.php" class="btn btn-teal">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Sign In to Portal
                    </a>
                    <a href="<?= BASE_URL ?>/auth/register.php" class="btn btn-outline-secondary">
                        <i class="bi bi-pencil-square me-1"></i> Register Student Account
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
