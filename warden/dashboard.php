<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('Warden');

$wardenId = (int)($_SESSION['user']['WardenID'] ?? 0);

// Fetch warden's profile and assigned block
$wStmt = $pdo->prepare("
    SELECT w.*, b.BlockName, b.Gender as BlockGender 
    FROM wardens w
    LEFT JOIN blocks b ON w.BlockID = b.BlockID
    WHERE w.WardenID = :wid
");
$wStmt->execute([':wid' => $wardenId]);
$warden = $wStmt->fetch();

$blockId = $warden['BlockID'] ?? null;

// Statistics
if ($blockId) {
    // Rooms & Beds in this block
    $roomStats = $pdo->prepare("
        SELECT 
            COUNT(*) as total_rooms,
            COALESCE(SUM(Capacity), 0) as total_capacity,
            COALESCE(SUM(Occupied), 0) as total_occupied
        FROM rooms WHERE BlockID = :bid
    ");
    $roomStats->execute([':bid' => $blockId]);
    $rData = $roomStats->fetch();

    // Resident students
    $studentCount = $pdo->prepare("
        SELECT COUNT(*) FROM students s
        JOIN rooms r ON s.RoomID = r.RoomID
        WHERE r.BlockID = :bid
    ");
    $studentCount->execute([':bid' => $blockId]);
    $totalResidents = (int)$studentCount->fetchColumn();

    // Block Complaints
    $complaintCount = $pdo->prepare("
        SELECT COUNT(*) FROM complaints c
        JOIN students s ON c.StudentID = s.StudentID
        JOIN rooms r ON s.RoomID = r.RoomID
        WHERE r.BlockID = :bid AND c.Status IN ('Pending', 'In Progress')
    ");
    $complaintCount->execute([':bid' => $blockId]);
    $openComplaints = (int)$complaintCount->fetchColumn();

    // Today's visitors for this block
    $visitorCount = $pdo->prepare("
        SELECT COUNT(*) FROM visitors v
        JOIN students s ON v.StudentID = s.StudentID
        JOIN rooms r ON s.RoomID = r.RoomID
        WHERE r.BlockID = :bid AND v.Status = 'Checked In'
    ");
    $visitorCount->execute([':bid' => $blockId]);
    $activeVisitors = (int)$visitorCount->fetchColumn();

    // Recent complaints in block
    $recentComplaints = $pdo->prepare("
        SELECT c.*, s.Name as StudentName, r.RoomNumber
        FROM complaints c
        JOIN students s ON c.StudentID = s.StudentID
        JOIN rooms r ON s.RoomID = r.RoomID
        WHERE r.BlockID = :bid
        ORDER BY c.ComplaintID DESC LIMIT 5
    ");
    $recentComplaints->execute([':bid' => $blockId]);
    $blockComplaints = $recentComplaints->fetchAll();

} else {
    $rData = ['total_rooms' => 0, 'total_capacity' => 0, 'total_occupied' => 0];
    $totalResidents = 0;
    $openComplaints = 0;
    $activeVisitors = 0;
    $blockComplaints = [];
}

// Announcements
$announcements = $pdo->query("
    SELECT * FROM announcements 
    WHERE TargetAudience IN ('All', 'Wardens') 
    ORDER BY IsPinned DESC, AnnouncementID DESC LIMIT 3
")->fetchAll();

$pageTitle = 'Warden Dashboard - ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-container">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
            <div>
                <h3 class="fw-bold text-dark mb-1">Hostel Warden Portal</h3>
                <p class="text-muted small mb-0">
                    Assigned: <strong><?= !empty($warden['BlockName']) ? htmlspecialchars($warden['BlockName']) : 'Unassigned Block' ?></strong>
                    &bull; Supervising resident welfare, attendance, and discipline.
                </p>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= BASE_URL ?>/warden/visitors.php" class="btn btn-teal btn-sm">
                    <i class="bi bi-person-check me-1"></i> Log Visitor Entry
                </a>
                <a href="<?= BASE_URL ?>/warden/complaints.php" class="btn btn-ghost-muted btn-sm">
                    <i class="bi bi-tools me-1"></i> Review Complaints
                </a>
            </div>
        </div>

        <?php display_flash(); ?>

        <!-- Stat Cards -->
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div>
                        <div class="stat-label">Block Residents</div>
                        <div class="stat-value"><?= $totalResidents ?></div>
                        <span class="small text-muted">Students residing</span>
                    </div>
                    <div class="stat-icon stat-icon-blue">
                        <i class="bi bi-people-fill"></i>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div>
                        <div class="stat-label">Bed Occupancy</div>
                        <div class="stat-value"><?= (int)$rData['total_occupied'] ?> / <?= (int)$rData['total_capacity'] ?></div>
                        <span class="small text-muted"><?= (int)$rData['total_rooms'] ?> Total Rooms</span>
                    </div>
                    <div class="stat-icon stat-icon-teal">
                        <i class="bi bi-door-closed-fill"></i>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div>
                        <div class="stat-label">Active Complaints</div>
                        <div class="stat-value text-danger"><?= $openComplaints ?></div>
                        <span class="small text-muted">Requires inspection</span>
                    </div>
                    <div class="stat-icon stat-icon-amber">
                        <i class="bi bi-tools"></i>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div>
                        <div class="stat-label">Current Visitors</div>
                        <div class="stat-value text-success"><?= $activeVisitors ?></div>
                        <span class="small text-muted">Present inside block</span>
                    </div>
                    <div class="stat-icon stat-icon-emerald">
                        <i class="bi bi-person-badge"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Announcements for Wardens -->
        <?php if (!empty($announcements)): ?>
            <div class="content-card mb-4" style="border-left: 4px solid var(--primary-teal);">
                <div class="content-card-header bg-white py-2">
                    <h6 class="content-card-title text-dark small fw-bold">
                        <i class="bi bi-megaphone-fill text-teal me-2" style="color: var(--primary-teal);"></i>
                        Notice Board
                    </h6>
                </div>
                <div class="p-3">
                    <div class="row g-3">
                        <?php foreach ($announcements as $ann): ?>
                            <div class="col-md-4">
                                <div class="p-3 bg-light rounded-3 border h-100">
                                    <div class="fw-bold small text-dark mb-1"><?= htmlspecialchars($ann['Title']) ?></div>
                                    <p class="text-muted small mb-1"><?= htmlspecialchars($ann['Content']) ?></p>
                                    <span class="text-secondary" style="font-size: 0.72rem;"><?= format_date($ann['CreatedAt']) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Block Complaints -->
        <div class="content-card">
            <div class="content-card-header">
                <h6 class="content-card-title"><i class="bi bi-tools me-2 text-muted"></i>Recent Maintenance Grievances in <?= htmlspecialchars($warden['BlockName'] ?? 'Your Block') ?></h6>
                <a href="<?= BASE_URL ?>/warden/complaints.php" class="small text-decoration-none fw-semibold" style="color: var(--primary-teal);">View All Complaints &rarr;</a>
            </div>
            <div class="table-responsive">
                <table class="table table-custom align-middle">
                    <thead>
                        <tr>
                            <th>Student & Room</th>
                            <th>Category</th>
                            <th>Issue Title</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($blockComplaints)): ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">No complaints recorded for this hostel block.</td></tr>
                        <?php else: ?>
                            <?php foreach ($blockComplaints as $bc): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($bc['StudentName']) ?></div>
                                        <span class="badge bg-light text-dark border">Room <?= htmlspecialchars($bc['RoomNumber']) ?></span>
                                    </td>
                                    <td><span class="badge bg-light text-muted border"><?= htmlspecialchars($bc['Category']) ?></span></td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?= htmlspecialchars($bc['Title']) ?></div>
                                        <div class="small text-muted text-truncate" style="max-width: 250px;"><?= htmlspecialchars($bc['Description']) ?></div>
                                    </td>
                                    <td><?= format_date($bc['Date']) ?></td>
                                    <td><?= get_status_badge($bc['Status']) ?></td>
                                    <td class="text-end">
                                        <a href="<?= BASE_URL ?>/warden/complaints.php" class="btn btn-sm btn-outline-secondary">Review</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
