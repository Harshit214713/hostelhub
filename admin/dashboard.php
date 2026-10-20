<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('Admin');

// Fetch dashboard KPIs
// 1. Total Students
$totalStudents = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();

// 2. Total Rooms & Occupancy
$roomStats = $pdo->query("
    SELECT 
        COUNT(*) as total_rooms,
        COALESCE(SUM(Capacity), 0) as total_capacity,
        COALESCE(SUM(Occupied), 0) as total_occupied
    FROM rooms
")->fetch();

$totalRooms = (int)$roomStats['total_rooms'];
$totalCapacity = (int)$roomStats['total_capacity'];
$totalOccupied = (int)$roomStats['total_occupied'];
$occupancyRate = ($totalCapacity > 0) ? round(($totalOccupied / $totalCapacity) * 100, 1) : 0;

// 3. Pending & Overdue Fees
$feeStats = $pdo->query("
    SELECT 
        COALESCE(SUM(CASE WHEN Status IN ('Pending', 'Overdue') THEN Amount ELSE 0 END), 0) as pending_amount,
        COALESCE(SUM(CASE WHEN Status = 'Paid' THEN Amount ELSE 0 END), 0) as collected_amount
    FROM fees
")->fetch();

// 4. Pending Complaints
$pendingComplaints = $pdo->query("SELECT COUNT(*) FROM complaints WHERE Status IN ('Pending', 'In Progress')")->fetchColumn();

// 5. Active Visitors checked-in today
$activeVisitors = $pdo->query("SELECT COUNT(*) FROM visitors WHERE Status = 'Checked In'")->fetchColumn();

// Fetch Recent Complaints
$recentComplaints = $pdo->query("
    SELECT c.*, s.Name as StudentName, r.RoomNumber, b.BlockName 
    FROM complaints c
    JOIN students s ON c.StudentID = s.StudentID
    LEFT JOIN rooms r ON s.RoomID = r.RoomID
    LEFT JOIN blocks b ON r.BlockID = b.BlockID
    ORDER BY c.ComplaintID DESC LIMIT 5
")->fetchAll();

// Fetch Recent Visitors
$recentVisitors = $pdo->query("
    SELECT v.*, s.Name as StudentName, r.RoomNumber
    FROM visitors v
    JOIN students s ON v.StudentID = s.StudentID
    LEFT JOIN rooms r ON s.RoomID = r.RoomID
    ORDER BY v.VisitorID DESC LIMIT 5
")->fetchAll();

// Fetch Active Announcements
$recentAnnouncements = $pdo->query("
    SELECT * FROM announcements ORDER BY IsPinned DESC, AnnouncementID DESC LIMIT 3
")->fetchAll();

$pageTitle = 'Admin Dashboard - ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-container">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
            <div>
                <h3 class="fw-bold text-dark mb-1">Hostel Administrator Dashboard</h3>
                <p class="text-muted small mb-0">Overview of campus hostel operations, occupancy, and service requests.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= BASE_URL ?>/admin/allocations.php" class="btn btn-teal btn-sm">
                    <i class="bi bi-key me-1"></i> Allocate Room
                </a>
                <a href="<?= BASE_URL ?>/admin/reports.php" class="btn btn-ghost-muted btn-sm">
                    <i class="bi bi-printer me-1"></i> Generate Reports
                </a>
            </div>
        </div>

        <?php display_flash(); ?>

        <!-- Stat Cards Row -->
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div>
                        <div class="stat-label">Total Students</div>
                        <div class="stat-value"><?= number_format($totalStudents) ?></div>
                        <span class="small text-muted">Registered residents</span>
                    </div>
                    <div class="stat-icon stat-icon-blue">
                        <i class="bi bi-people-fill"></i>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div>
                        <div class="stat-label">Room Occupancy</div>
                        <div class="stat-value"><?= $occupancyRate ?>%</div>
                        <span class="small text-muted"><?= $totalOccupied ?> / <?= $totalCapacity ?> Beds Occupied</span>
                    </div>
                    <div class="stat-icon stat-icon-teal">
                        <i class="bi bi-door-open-fill"></i>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div>
                        <div class="stat-label">Pending Fees</div>
                        <div class="stat-value" style="font-size: 1.55rem;"><?= format_currency($feeStats['pending_amount']) ?></div>
                        <span class="small text-success fw-semibold">Collected: <?= format_currency($feeStats['collected_amount']) ?></span>
                    </div>
                    <div class="stat-icon stat-icon-amber">
                        <i class="bi bi-cash-coin"></i>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div>
                        <div class="stat-label">Open Complaints</div>
                        <div class="stat-value text-danger"><?= $pendingComplaints ?></div>
                        <span class="small text-muted">Active maintenance requests</span>
                    </div>
                    <div class="stat-icon stat-icon-rose">
                        <i class="bi bi-exclamation-octagon-fill"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Announcements Banner -->
        <?php if (!empty($recentAnnouncements)): ?>
            <div class="content-card mb-4 border-teal" style="border-left: 4px solid var(--primary-teal);">
                <div class="content-card-header bg-white py-3">
                    <h6 class="content-card-title text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-megaphone-fill text-teal" style="color: var(--primary-teal);"></i>
                        Campus Announcements
                    </h6>
                    <a href="<?= BASE_URL ?>/admin/announcements.php" class="btn btn-sm btn-link text-decoration-none p-0" style="color: var(--primary-teal);">Manage Notices &rarr;</a>
                </div>
                <div class="p-3">
                    <div class="row g-3">
                        <?php foreach ($recentAnnouncements as $ann): ?>
                            <div class="col-md-4">
                                <div class="p-3 bg-light rounded-3 h-100 border">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <h6 class="fw-bold mb-1 small text-dark"><?= htmlspecialchars($ann['Title']) ?></h6>
                                        <?php if ($ann['IsPinned']): ?>
                                            <span class="badge bg-danger" style="font-size: 0.65rem;">Pinned</span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-muted small mb-2 text-truncate-2"><?= htmlspecialchars($ann['Content']) ?></p>
                                    <div class="small text-secondary" style="font-size: 0.75rem;">
                                        By <?= htmlspecialchars($ann['PostedBy']) ?> &bull; <?= format_date($ann['CreatedAt']) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Tables Row -->
        <div class="row g-4">
            <!-- Recent Complaints -->
            <div class="col-lg-7">
                <div class="content-card h-100">
                    <div class="content-card-header">
                        <h6 class="content-card-title"><i class="bi bi-tools me-2 text-muted"></i>Recent Maintenance Complaints</h6>
                        <a href="<?= BASE_URL ?>/admin/complaints.php" class="small text-decoration-none fw-semibold" style="color: var(--primary-teal);">View All (<?= $pendingComplaints ?>)</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-custom align-middle">
                            <thead>
                                <tr>
                                    <th>Student & Room</th>
                                    <th>Issue Title</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentComplaints)): ?>
                                    <tr><td colspan="5" class="text-center py-4 text-muted">No complaints reported.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($recentComplaints as $c): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-semibold text-dark"><?= htmlspecialchars($c['StudentName']) ?></div>
                                                <div class="small text-muted"><?= htmlspecialchars($c['RoomNumber'] ?? 'No room') ?> (<?= htmlspecialchars($c['BlockName'] ?? '-') ?>)</div>
                                            </td>
                                            <td>
                                                <div class="text-truncate" style="max-width: 180px;" title="<?= htmlspecialchars($c['Title']) ?>">
                                                    <?= htmlspecialchars($c['Title']) ?>
                                                </div>
                                                <div class="small text-muted"><?= format_date($c['Date']) ?></div>
                                            </td>
                                            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($c['Category']) ?></span></td>
                                            <td><?= get_status_badge($c['Status']) ?></td>
                                            <td>
                                                <a href="<?= BASE_URL ?>/admin/complaints.php" class="btn btn-sm btn-outline-secondary">Manage</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Recent Visitors -->
            <div class="col-lg-5">
                <div class="content-card h-100">
                    <div class="content-card-header">
                        <h6 class="content-card-title"><i class="bi bi-journal-text me-2 text-muted"></i>Recent Visitors</h6>
                        <a href="<?= BASE_URL ?>/admin/visitors.php" class="small text-decoration-none fw-semibold" style="color: var(--primary-teal);">All Logs &rarr;</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-custom align-middle">
                            <thead>
                                <tr>
                                    <th>Visitor</th>
                                    <th>Visiting Student</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentVisitors)): ?>
                                    <tr><td colspan="3" class="text-center py-4 text-muted">No recent visitors logged.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($recentVisitors as $v): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-semibold text-dark"><?= htmlspecialchars($v['Name']) ?></div>
                                                <div class="small text-muted"><?= htmlspecialchars($v['Relation']) ?></div>
                                            </td>
                                            <td>
                                                <div class="text-dark small"><?= htmlspecialchars($v['StudentName']) ?></div>
                                                <div class="small text-muted">Room <?= htmlspecialchars($v['RoomNumber'] ?? 'N/A') ?></div>
                                            </td>
                                            <td><?= get_status_badge($v['Status']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
