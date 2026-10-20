<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('Student');

$studentId = (int)($_SESSION['user']['StudentID'] ?? 0);

// Fetch Room & Block & Allocation Details
$sStmt = $pdo->prepare("
    SELECT s.*, r.RoomNumber, r.Type as RoomType, r.Capacity, r.Occupied, r.FeePerSemester, r.Status as RoomStatus,
           b.BlockName, b.Gender as BlockGender, b.Description as BlockDesc,
           w.Name as WardenName, w.Phone as WardenPhone, w.Email as WardenEmail,
           ra.AllocationDate, ra.Notes as AllocationNotes
    FROM students s
    LEFT JOIN rooms r ON s.RoomID = r.RoomID
    LEFT JOIN blocks b ON r.BlockID = b.BlockID
    LEFT JOIN wardens w ON b.BlockID = w.BlockID
    LEFT JOIN room_allocations ra ON ra.StudentID = s.StudentID AND ra.Status = 'Active'
    WHERE s.StudentID = :sid
");
$sStmt->execute([':sid' => $studentId]);
$student = $sStmt->fetch();

$roomId = $student['RoomID'] ?? null;

// Fetch Roommates
$roommates = [];
if ($roomId) {
    $rmStmt = $pdo->prepare("
        SELECT Name, Email, Phone, Course, Year 
        FROM students 
        WHERE RoomID = :rid AND StudentID != :sid
    ");
    $rmStmt->execute([':rid' => $roomId, ':sid' => $studentId]);
    $roommates = $rmStmt->fetchAll();
}

$pageTitle = 'My Room - ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-container">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
            <div>
                <h3 class="fw-bold text-dark mb-1">My Allocated Hostel Room</h3>
                <p class="text-muted small mb-0">Overview of your current room allotment, facilities, roommates, and hostel warden.</p>
            </div>
            <?php if (!empty($roomId)): ?>
                <a href="<?= BASE_URL ?>/student/complaints.php" class="btn btn-teal btn-sm">
                    <i class="bi bi-tools me-1"></i> Report Room Issue
                </a>
            <?php endif; ?>
        </div>

        <?php display_flash(); ?>

        <?php if (empty($roomId)): ?>
            <div class="content-card text-center py-5">
                <i class="bi bi-door-closed fs-1 text-muted mb-3"></i>
                <h4 class="fw-bold text-dark">No Room Allocated Yet</h4>
                <p class="text-muted max-w-500 mx-auto mb-4" style="max-width: 500px;">
                    Your accommodation application is currently in queue. Once the hostel administrator processes and approves your bed allocation, details will be accessible here.
                </p>
                <div class="d-flex justify-content-center gap-2">
                    <a href="<?= BASE_URL ?>/student/dashboard.php" class="btn btn-teal">Back to Dashboard</a>
                </div>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <!-- Room Specs Card -->
                <div class="col-lg-7">
                    <div class="content-card h-100">
                        <div class="content-card-header">
                            <h6 class="content-card-title"><i class="bi bi-door-open me-2 text-muted"></i>Room Details</h6>
                            <span class="badge bg-success">Active Allotment</span>
                        </div>
                        <div class="content-card-body">
                            <div class="d-flex align-items-center gap-4 mb-4 pb-3 border-bottom">
                                <div class="stat-icon stat-icon-teal" style="width: 64px; height: 64px; font-size: 1.8rem;">
                                    <i class="bi bi-buildings"></i>
                                </div>
                                <div>
                                    <h2 class="fw-bold text-dark mb-0">Room <?= htmlspecialchars($student['RoomNumber']) ?></h2>
                                    <div class="text-muted fw-semibold"><?= htmlspecialchars($student['BlockName']) ?></div>
                                    <div class="small text-secondary"><?= htmlspecialchars($student['BlockDesc'] ?? '') ?></div>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <div class="p-3 bg-light rounded-3 border">
                                        <div class="small text-muted mb-1">Room Configuration</div>
                                        <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($student['RoomType']) ?> Room</div>
                                        <div class="small text-muted"><?= (int)$student['Capacity'] ?> Bed Capacity</div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="p-3 bg-light rounded-3 border">
                                        <div class="small text-muted mb-1">Current Occupancy</div>
                                        <div class="fw-bold text-teal fs-6"><?= (int)$student['Occupied'] ?> / <?= (int)$student['Capacity'] ?> Beds Occupied</div>
                                        <div class="small text-muted"><?= ((int)$student['Capacity'] - (int)$student['Occupied']) ?> beds currently free</div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="p-3 bg-light rounded-3 border">
                                        <div class="small text-muted mb-1">Semester Tariff</div>
                                        <div class="fw-bold text-dark fs-6"><?= format_currency($student['FeePerSemester']) ?></div>
                                        <div class="small text-muted">Per Semester</div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="p-3 bg-light rounded-3 border">
                                        <div class="small text-muted mb-1">Allotted On</div>
                                        <div class="fw-bold text-dark fs-6"><?= format_date($student['AllocationDate']) ?></div>
                                        <div class="small text-muted"><?= htmlspecialchars($student['AllocationNotes'] ?? 'Standard allocation') ?></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Amenities & Perks -->
                            <div class="mt-4 pt-3 border-top">
                                <h6 class="fw-bold text-dark small mb-3">Room Amenities Included:</h6>
                                <div class="row g-2 small text-secondary">
                                    <div class="col-6"><i class="bi bi-check-circle-fill text-teal me-2" style="color:var(--primary-teal);"></i>Individual Bed & Mattress</div>
                                    <div class="col-6"><i class="bi bi-check-circle-fill text-teal me-2" style="color:var(--primary-teal);"></i>Study Desk & Ergonomic Chair</div>
                                    <div class="col-6"><i class="bi bi-check-circle-fill text-teal me-2" style="color:var(--primary-teal);"></i>Secure Steel Wardrobe</div>
                                    <div class="col-6"><i class="bi bi-check-circle-fill text-teal me-2" style="color:var(--primary-teal);"></i>High-Speed Wi-Fi Access</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Roommates & Warden Info -->
                <div class="col-lg-5">
                    <!-- Roommates -->
                    <div class="content-card mb-4">
                        <div class="content-card-header">
                            <h6 class="content-card-title"><i class="bi bi-people me-2 text-muted"></i>My Roommates (<?= count($roommates) ?>)</h6>
                        </div>
                        <div class="content-card-body">
                            <?php if (empty($roommates)): ?>
                                <p class="text-muted small mb-0">No other roommates are currently assigned to this room (single occupancy or vacancy).</p>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($roommates as $rm): ?>
                                        <div class="list-group-item px-0 py-2 border-bottom">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div>
                                                    <div class="fw-bold text-dark"><?= htmlspecialchars($rm['Name']) ?></div>
                                                    <div class="small text-muted"><?= htmlspecialchars($rm['Course']) ?> &bull; Year <?= $rm['Year'] ?></div>
                                                </div>
                                                <a href="tel:<?= htmlspecialchars($rm['Phone']) ?>" class="btn btn-sm btn-outline-secondary py-1 px-2">
                                                    <i class="bi bi-telephone-fill"></i>
                                                </a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Warden Contact Card -->
                    <div class="content-card">
                        <div class="content-card-header">
                            <h6 class="content-card-title"><i class="bi bi-shield-check me-2 text-muted"></i>Hostel Block Warden</h6>
                        </div>
                        <div class="content-card-body">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="stat-icon stat-icon-amber" style="width: 48px; height: 48px;">
                                    <i class="bi bi-person-badge"></i>
                                </div>
                                <div>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($student['WardenName'] ?? 'Warden Office') ?></div>
                                    <div class="small text-muted">Incharge: <?= htmlspecialchars($student['BlockName']) ?></div>
                                </div>
                            </div>
                            <?php if (!empty($student['WardenPhone'])): ?>
                                <div class="d-grid mb-2">
                                    <a href="tel:<?= htmlspecialchars($student['WardenPhone']) ?>" class="btn btn-teal-outline btn-sm">
                                        <i class="bi bi-telephone me-1"></i> Call Warden: <?= htmlspecialchars($student['WardenPhone']) ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($student['WardenEmail'])): ?>
                                <div class="small text-muted text-center">
                                    <i class="bi bi-envelope me-1"></i><?= htmlspecialchars($student['WardenEmail']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
