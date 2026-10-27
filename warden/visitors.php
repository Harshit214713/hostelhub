<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('Warden');

$wardenId = (int)($_SESSION['user']['WardenID'] ?? 0);

// Get warden block
$wStmt = $pdo->prepare("SELECT w.*, b.BlockName FROM wardens w LEFT JOIN blocks b ON w.BlockID = b.BlockID WHERE w.WardenID = :wid");
$wStmt->execute([':wid' => $wardenId]);
$warden = $wStmt->fetch();
$blockId = $warden['BlockID'] ?? 0;

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf()) {
        set_flash('danger', 'Security token invalid.');
        redirect('warden/visitors.php');
    }

    $action = $_POST['action'] ?? '';

    // Check-in
    if ($action === 'checkin') {
        $studentId = (int)($_POST['student_id'] ?? 0);
        $name = trim($_POST['visitor_name'] ?? '');
        $relation = trim($_POST['relation'] ?? 'Parent');
        $phone = trim($_POST['phone'] ?? '');
        $idProof = trim($_POST['id_proof'] ?? '');

        if ($studentId > 0 && !empty($name) && !empty($phone)) {
            $stmt = $pdo->prepare("
                INSERT INTO visitors (StudentID, Name, Relation, Phone, IDProof, VisitDate, CheckInTime, Status)
                VALUES (:sid, :n, :r, :p, :idp, CURDATE(), NOW(), 'Checked In')
            ");
            $stmt->execute([
                ':sid' => $studentId,
                ':n'   => $name,
                ':r'   => $relation,
                ':p'   => $phone,
                ':idp' => $idProof
            ]);
            set_flash('success', "Visitor {$name} checked in.");
        }
        redirect('warden/visitors.php');
    }

    // Check-out
    if ($action === 'checkout') {
        $visitorId = (int)($_POST['visitor_id'] ?? 0);
        if ($visitorId > 0) {
            $stmt = $pdo->prepare("UPDATE visitors SET Status = 'Checked Out', CheckOutTime = NOW() WHERE VisitorID = :id AND Status = 'Checked In'");
            $stmt->execute([':id' => $visitorId]);
            if ($stmt->rowCount() > 0) {
                set_flash('success', 'Visitor checked out successfully.');
            } else {
                set_flash('warning', 'Visitor is already checked out or does not exist.');
            }
        }
        redirect('warden/visitors.php');
    }
}

// Fetch visitors
$visitors = [];
$students = [];
if ($blockId > 0) {
    $vStmt = $pdo->prepare("
        SELECT v.*, s.Name as StudentName, r.RoomNumber
        FROM visitors v
        JOIN students s ON v.StudentID = s.StudentID
        JOIN rooms r ON s.RoomID = r.RoomID
        WHERE r.BlockID = :bid
        ORDER BY v.VisitorID DESC
    ");
    $vStmt->execute([':bid' => $blockId]);
    $visitors = $vStmt->fetchAll();

    $sStmt = $pdo->prepare("
        SELECT s.StudentID, s.Name, r.RoomNumber 
        FROM students s
        JOIN rooms r ON s.RoomID = r.RoomID
        WHERE r.BlockID = :bid
        ORDER BY r.RoomNumber ASC, s.Name ASC
    ");
    $sStmt->execute([':bid' => $blockId]);
    $students = $sStmt->fetchAll();
}

$pageTitle = 'Block Visitors - ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-container">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
            <div>
                <h3 class="fw-bold text-dark mb-1">Hostel Block Visitor Gate Log</h3>
                <p class="text-muted small mb-0">
                    Block: <strong><?= htmlspecialchars($warden['BlockName'] ?? 'Unassigned') ?></strong> &bull; Monitor guest entry and departure.
                </p>
            </div>
            <button class="btn btn-teal btn-sm" data-bs-toggle="modal" data-bs-target="#wardenCheckinModal">
                <i class="bi bi-person-check me-1"></i> Log Visitor Check-In
            </button>
        </div>

        <?php display_flash(); ?>

        <div class="content-card">
            <div class="content-card-header">
                <h6 class="content-card-title"><i class="bi bi-journal-text me-2 text-muted"></i>Guest Log (<?= count($visitors) ?>)</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-custom align-middle">
                    <thead>
                        <tr>
                            <th>Visitor</th>
                            <th>Relationship</th>
                            <th>Resident Student</th>
                            <th>ID Proof</th>
                            <th>Check-In</th>
                            <th>Check-Out</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($visitors)): ?>
                            <tr><td colspan="8" class="text-center py-4 text-muted">No visitors logged for this block.</td></tr>
                        <?php else: ?>
                            <?php foreach ($visitors as $v): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($v['Name']) ?></div>
                                        <div class="small text-muted"><?= htmlspecialchars($v['Phone']) ?></div>
                                    </td>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($v['Relation']) ?></span></td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?= htmlspecialchars($v['StudentName']) ?></div>
                                        <div class="small text-muted">Room <?= htmlspecialchars($v['RoomNumber']) ?></div>
                                    </td>
                                    <td><span class="small font-monospace"><?= htmlspecialchars($v['IDProof'] ?? 'None') ?></span></td>
                                    <td><div class="small fw-semibold"><?= format_date($v['CheckInTime'], true) ?></div></td>
                                    <td>
                                        <?php if ($v['Status'] === 'Checked Out'): ?>
                                            <div class="small text-muted"><?= format_date($v['CheckOutTime'], true) ?></div>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Inside Block</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= get_status_badge($v['Status']) ?></td>
                                    <td class="text-end">
                                        <?php if ($v['Status'] === 'Checked In'): ?>
                                            <form method="POST" action="visitors.php" class="d-inline">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="checkout">
                                                <input type="hidden" name="visitor_id" value="<?= $v['VisitorID'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-success py-1 px-2">
                                                    <i class="bi bi-box-arrow-right me-1"></i>Check-Out
                                                </button>
                                            </form>
                                        <?php endif; ?>
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

<!-- Modal: Warden Checkin -->
<div class="modal fade" id="wardenCheckinModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="visitors.php" class="modal-content needs-validation" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="checkin">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Log Visitor Entry</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Select Resident *</label>
                    <select name="student_id" class="form-select" required>
                        <option value="">-- Choose Resident --</option>
                        <?php foreach ($students as $st): ?>
                            <option value="<?= $st['StudentID'] ?>">
                                <?= htmlspecialchars($st['Name']) ?> (Room <?= htmlspecialchars($st['RoomNumber']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Visitor Name *</label>
                    <input type="text" name="visitor_name" class="form-control" required placeholder="Full Name">
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Relationship *</label>
                        <select name="relation" class="form-select" required>
                            <option value="Parent">Parent</option>
                            <option value="Sibling">Sibling</option>
                            <option value="Relative">Relative</option>
                            <option value="Friend">Friend</option>
                            <option value="Delivery">Delivery / Courier</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Phone Number *</label>
                        <input type="tel" name="phone" class="form-control" required placeholder="+91 98...">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">ID Proof (Optional)</label>
                    <input type="text" name="id_proof" class="form-control" placeholder="AADHAAR / Driving License">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-teal">Log Check-In</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
