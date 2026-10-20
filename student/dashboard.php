<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('Student');

$studentId = (int)($_SESSION['user']['StudentID'] ?? 0);

// Fetch Student Profile & Room
$sStmt = $pdo->prepare("
    SELECT s.*, r.RoomNumber, r.Type as RoomType, r.Capacity, r.Occupied, b.BlockName, b.Gender as BlockGender,
           w.Name as WardenName, w.Phone as WardenPhone
    FROM students s
    LEFT JOIN rooms r ON s.RoomID = r.RoomID
    LEFT JOIN blocks b ON r.BlockID = b.BlockID
    LEFT JOIN wardens w ON b.BlockID = w.BlockID
    WHERE s.StudentID = :sid
");
$sStmt->execute([':sid' => $studentId]);
$student = $sStmt->fetch();

// Fetch Pending Fees
$feeStmt = $pdo->prepare("
    SELECT * FROM fees 
    WHERE StudentID = :sid AND Status IN ('Pending', 'Overdue')
    ORDER BY DueDate ASC
");
$feeStmt->execute([':sid' => $studentId]);
$pendingFees = $feeStmt->fetchAll();

$pendingFeeSum = 0;
foreach ($pendingFees as $pf) {
    $pendingFeeSum += (float)$pf['Amount'];
}

// Fetch Active Complaints
$cmpStmt = $pdo->prepare("
    SELECT * FROM complaints 
    WHERE StudentID = :sid 
    ORDER BY ComplaintID DESC LIMIT 3
");
$cmpStmt->execute([':sid' => $studentId]);
$recentComplaints = $cmpStmt->fetchAll();

// Fetch Roommates (if in a room)
$roommates = [];
if (!empty($student['RoomID'])) {
    $rmStmt = $pdo->prepare("
        SELECT Name, Course, Year, Phone 
        FROM students 
        WHERE RoomID = :rid AND StudentID != :sid
    ");
    $rmStmt->execute([':rid' => $student['RoomID'], ':sid' => $studentId]);
    $roommates = $rmStmt->fetchAll();
}

// Announcements for students
$announcements = $pdo->query("
    SELECT * FROM announcements 
    WHERE TargetAudience IN ('All', 'Students') 
    ORDER BY IsPinned DESC, AnnouncementID DESC LIMIT 4
")->fetchAll();

$pageTitle = 'Student Portal - ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-container">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
            <div>
                <h3 class="fw-bold text-dark mb-1">Welcome, <?= htmlspecialchars($student['Name'] ?? 'Student') ?>!</h3>
                <p class="text-muted small mb-0">
                    <?= htmlspecialchars($student['Course'] ?? '') ?> &bull; Year <?= htmlspecialchars($student['Year'] ?? '1') ?>
                </p>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= BASE_URL ?>/student/complaints.php" class="btn btn-teal btn-sm">
                    <i class="bi bi-plus-circle me-1"></i> Lodge Maintenance Complaint
                </a>
                <a href="<?= BASE_URL ?>/student/fees.php" class="btn btn-ghost-muted btn-sm">
                    <i class="bi bi-credit-card me-1"></i> Fee Status
                </a>
            </div>
        </div>

        <?php display_flash(); ?>

        <!-- Pending Fee Alert Banner -->
        <?php if ($pendingFeeSum > 0): ?>
            <div class="alert alert-warning d-flex justify-content-between align-items-center shadow-sm mb-4" role="alert">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-exclamation-triangle-fill fs-4 text-warning"></i>
                    <div>
                        <strong>Hostel Fee Pending:</strong> You have an outstanding balance of <strong><?= format_currency($pendingFeeSum) ?></strong>.
                        <div class="small text-muted">Please clear before due date to avoid penalty charges.</div>
                    </div>
                </div>
                <a href="<?= BASE_URL ?>/student/fees.php" class="btn btn-sm btn-dark">View & Pay Dues</a>
            </div>
        <?php endif; ?>

        <!-- Top Overview Cards -->
        <div class="row g-3 mb-4">
            <!-- Room Info Card -->
            <div class="col-lg-4">
                <div class="content-card h-100">
                    <div class="content-card-header">
                        <h6 class="content-card-title"><i class="bi bi-door-closed me-2 text-muted"></i>Room Allotment</h6>
                        <span class="badge <?= !empty($student['RoomID']) ? 'bg-success' : 'bg-warning text-dark' ?>">
                            <?= !empty($student['RoomID']) ? 'Allocated' : 'Pending Allotment' ?>
                        </span>
                    </div>
                    <div class="content-card-body">
                        <?php if (!empty($student['RoomID'])): ?>
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="stat-icon stat-icon-teal" style="width: 50px; height: 50px;">
                                    <i class="bi bi-building"></i>
                                </div>
                                <div>
                                    <div class="fw-bold fs-4 text-dark"><?= htmlspecialchars($student['RoomNumber']) ?></div>
                                    <div class="small text-muted"><?= htmlspecialchars($student['BlockName']) ?> (<?= htmlspecialchars($student['RoomType']) ?> Room)</div>
                                </div>
                            </div>
                            <div class="small mb-2">
                                <span class="text-muted">Hostel Warden:</span>
                                <strong><?= htmlspecialchars($student['WardenName'] ?? 'Unassigned') ?></strong>
                                <?php if (!empty($student['WardenPhone'])): ?>
                                    <a href="tel:<?= htmlspecialchars($student['WardenPhone']) ?>" class="ms-1 text-teal text-decoration-none">
                                        <i class="bi bi-telephone-fill"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($roommates)): ?>
                                <div class="pt-2 border-top">
                                    <div class="small text-muted fw-semibold mb-1">Roommates:</div>
                                    <ul class="list-unstyled mb-0 small">
                                        <?php foreach ($roommates as $rm): ?>
                                            <li class="d-flex align-items-center gap-1 text-dark mb-1">
                                                <i class="bi bi-person-circle text-muted"></i>
                                                <span><?= htmlspecialchars($rm['Name']) ?></span>
                                                <span class="text-muted">(<?= htmlspecialchars($rm['Course']) ?>)</span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="text-center py-4 text-muted">
                                <i class="bi bi-clock-history fs-1 text-secondary mb-2"></i>
                                <p class="mb-0">Your room allocation request is being processed by the hostel administration office.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Fee Summary Card -->
            <div class="col-lg-4">
                <div class="content-card h-100">
                    <div class="content-card-header">
                        <h6 class="content-card-title"><i class="bi bi-cash-stack me-2 text-muted"></i>Hostel Fee Dues</h6>
                        <a href="<?= BASE_URL ?>/student/fees.php" class="small text-decoration-none fw-semibold" style="color: var(--primary-teal);">Details &rarr;</a>
                    </div>
                    <div class="content-card-body">
                        <?php if ($pendingFeeSum > 0): ?>
                            <div class="stat-label">Outstanding Balance</div>
                            <div class="stat-value text-danger mb-2"><?= format_currency($pendingFeeSum) ?></div>
                            <p class="small text-muted mb-3">Due date: <?= format_date($pendingFees[0]['DueDate'] ?? null) ?></p>
                            <a href="<?= BASE_URL ?>/student/fees.php" class="btn btn-teal btn-sm w-100">Pay / Submit Transaction Ref</a>
                        <?php else: ?>
                            <div class="text-center py-4 text-success">
                                <i class="bi bi-check-circle-fill fs-1 text-success mb-2"></i>
                                <h6 class="fw-bold mb-1">All Dues Cleared!</h6>
                                <p class="small text-muted mb-0">You have no pending fees for the current semester.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Maintenance Status Card -->
            <div class="col-lg-4">
                <div class="content-card h-100">
                    <div class="content-card-header">
                        <h6 class="content-card-title"><i class="bi bi-tools me-2 text-muted"></i>Recent Complaints</h6>
                        <a href="<?= BASE_URL ?>/student/complaints.php" class="small text-decoration-none fw-semibold" style="color: var(--primary-teal);">All (<?= count($recentComplaints) ?>)</a>
                    </div>
                    <div class="content-card-body">
                        <?php if (empty($recentComplaints)): ?>
                            <div class="text-center py-4 text-muted">
                                <i class="bi bi-check2-all fs-1 text-teal mb-2" style="color: var(--primary-teal);"></i>
                                <p class="small mb-0">No open complaints for your room.</p>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($recentComplaints as $c): ?>
                                    <div class="list-group-item px-0 py-2 border-bottom">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="small fw-bold text-dark"><?= htmlspecialchars($c['Title']) ?></span>
                                            <?= get_status_badge($c['Status']) ?>
                                        </div>
                                        <div class="small text-muted"><?= htmlspecialchars($c['Category']) ?> &bull; <?= format_date($c['Date']) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Campus Notices -->
        <div class="content-card">
            <div class="content-card-header">
                <h6 class="content-card-title"><i class="bi bi-megaphone me-2 text-muted"></i>Campus Announcements & Notices</h6>
            </div>
            <div class="p-3">
                <div class="row g-3">
                    <?php if (empty($announcements)): ?>
                        <div class="col-12 text-center text-muted py-3">No announcements currently posted.</div>
                    <?php else: ?>
                        <?php foreach ($announcements as $ann): ?>
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded-3 border h-100">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <h6 class="fw-bold text-dark mb-1 small"><?= htmlspecialchars($ann['Title']) ?></h6>
                                        <?php if ($ann['IsPinned']): ?>
                                            <span class="badge bg-teal" style="font-size: 0.65rem;">Notice</span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-muted small mb-2"><?= htmlspecialchars($ann['Content']) ?></p>
                                    <div class="small text-secondary" style="font-size: 0.72rem;">
                                        By <?= htmlspecialchars($ann['PostedBy']) ?> &bull; <?= format_date($ann['CreatedAt']) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
