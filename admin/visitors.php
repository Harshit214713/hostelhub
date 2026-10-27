<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('Admin');

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf()) {
        set_flash('danger', 'Security token invalid.');
        redirect('admin/visitors.php');
    }

    $action = $_POST['action'] ?? '';

    // Log Visitor Check-In
    if ($action === 'checkin') {
        $studentId = (int)($_POST['student_id'] ?? 0);
        $name = trim($_POST['visitor_name'] ?? '');
        $relation = trim($_POST['relation'] ?? 'Parent');
        $phone = trim($_POST['phone'] ?? '');
        $idProof = trim($_POST['id_proof'] ?? '');

        if ($studentId > 0 && !empty($name) && !empty($phone)) {
            if (!preg_match('/^[0-9+\s\-]{7,15}$/', $phone)) {
                set_flash('danger', 'Please provide a valid contact phone number.');
            } else {
                try {
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
                    set_flash('success', "Visitor {$name} checked in successfully.");
                } catch (PDOException $e) {
                    set_flash('danger', 'Error logging visitor: ' . $e->getMessage());
                }
            }
        }
        redirect('admin/visitors.php');
    }

    // Mark Check-Out
    if ($action === 'checkout') {
        $visitorId = (int)($_POST['visitor_id'] ?? 0);
        if ($visitorId > 0) {
            $stmt = $pdo->prepare("UPDATE visitors SET Status = 'Checked Out', CheckOutTime = NOW() WHERE VisitorID = :id");
            $stmt->execute([':id' => $visitorId]);
            set_flash('success', 'Visitor marked as checked out.');
        }
        redirect('admin/visitors.php');
    }

    // Delete Visitor Log
    if ($action === 'delete') {
        $visitorId = (int)($_POST['visitor_id'] ?? 0);
        if ($visitorId > 0) {
            $pdo->prepare("DELETE FROM visitors WHERE VisitorID = :id")->execute([':id' => $visitorId]);
            set_flash('success', 'Visitor log deleted.');
        }
        redirect('admin/visitors.php');
    }
}

// Fetch all visitors
$visitors = $pdo->query("
    SELECT 
        v.*,
        s.Name as StudentName, s.Phone as StudentPhone,
        r.RoomNumber, b.BlockName
    FROM visitors v
    JOIN students s ON v.StudentID = s.StudentID
    LEFT JOIN rooms r ON s.RoomID = r.RoomID
    LEFT JOIN blocks b ON r.BlockID = b.BlockID
    ORDER BY v.VisitorID DESC
")->fetchAll();

// Fetch students for dropdown
$students = $pdo->query("
    SELECT s.StudentID, s.Name, r.RoomNumber, b.BlockName 
    FROM students s
    LEFT JOIN rooms r ON s.RoomID = r.RoomID
    LEFT JOIN blocks b ON r.BlockID = b.BlockID
    ORDER BY s.Name ASC
")->fetchAll();

$pageTitle = 'Visitor Log - ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-container">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
            <div>
                <h3 class="fw-bold text-dark mb-1">Campus Visitor Log</h3>
                <p class="text-muted small mb-0">Record and track external visitors, check-in and check-out timestamps, and ID proofs.</p>
            </div>
            <button class="btn btn-teal btn-sm" data-bs-toggle="modal" data-bs-target="#checkinModal">
                <i class="bi bi-person-check me-1"></i> Log Visitor Entry
            </button>
        </div>

        <?php display_flash(); ?>

        <div class="content-card">
            <div class="content-card-header">
                <h6 class="content-card-title"><i class="bi bi-journal-text me-2 text-muted"></i>Visitor Audit Trail (<?= count($visitors) ?>)</h6>
                <div style="max-width: 250px;">
                    <input type="text" class="form-control form-control-sm" placeholder="Search visitor or student..." data-table-search="#visitorsTable">
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-custom align-middle" id="visitorsTable">
                    <thead>
                        <tr>
                            <th>Visitor Details</th>
                            <th>Relationship</th>
                            <th>Hostel Resident Visited</th>
                            <th>ID Proof</th>
                            <th>Check-In Time</th>
                            <th>Check-Out Time</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($visitors)): ?>
                            <tr><td colspan="8" class="text-center py-4 text-muted">No visitors logged yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($visitors as $v): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($v['Name']) ?></div>
                                        <div class="small text-muted"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($v['Phone']) ?></div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border"><?= htmlspecialchars($v['Relation']) ?></span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?= htmlspecialchars($v['StudentName']) ?></div>
                                        <div class="small text-muted">Room <?= htmlspecialchars($v['RoomNumber'] ?? 'N/A') ?> (<?= htmlspecialchars($v['BlockName'] ?? '-') ?>)</div>
                                    </td>
                                    <td>
                                        <span class="small font-monospace"><?= htmlspecialchars($v['IDProof'] ?? 'None recorded') ?></span>
                                    </td>
                                    <td>
                                        <div class="small fw-semibold"><?= format_date($v['CheckInTime'], true) ?></div>
                                    </td>
                                    <td>
                                        <?php if ($v['Status'] === 'Checked Out'): ?>
                                            <div class="small text-muted"><?= format_date($v['CheckOutTime'], true) ?></div>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Currently Inside</span>
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

                                        <form method="POST" action="visitors.php" class="d-inline" onsubmit="return confirm('Delete this visitor record?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="visitor_id" value="<?= $v['VisitorID'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger p-1 ms-1">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
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

<!-- Modal: Log Visitor -->
<div class="modal fade" id="checkinModal" tabindex="-1">
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
                    <label class="form-label small fw-semibold">Select Resident Student *</label>
                    <select name="student_id" class="form-select" required>
                        <option value="">-- Choose Student --</option>
                        <?php foreach ($students as $st): ?>
                            <option value="<?= $st['StudentID'] ?>">
                                <?= htmlspecialchars($st['Name']) ?> (Room: <?= htmlspecialchars($st['RoomNumber'] ?? 'Unassigned') ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Visitor Full Name *</label>
                    <input type="text" name="visitor_name" class="form-control" required placeholder="e.g. Mukesh Shah">
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Relationship to Student *</label>
                        <select name="relation" class="form-select" required>
                            <option value="Parent">Parent / Guardian</option>
                            <option value="Sibling">Brother / Sister</option>
                            <option value="Relative">Relative</option>
                            <option value="Friend">Friend / Classmate</option>
                            <option value="Official">Official / Delivery</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Contact Phone *</label>
                        <input type="tel" name="phone" class="form-control" required placeholder="+91 9823011223">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">ID Proof Number / Document</label>
                    <input type="text" name="id_proof" class="form-control" placeholder="e.g. AADHAAR **** 4512 or DL">
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
