<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('Warden');

$wardenId = (int)($_SESSION['user']['WardenID'] ?? 0);

// Get warden's block
$wStmt = $pdo->prepare("SELECT w.*, b.BlockName FROM wardens w LEFT JOIN blocks b ON w.BlockID = b.BlockID WHERE w.WardenID = :wid");
$wStmt->execute([':wid' => $wardenId]);
$warden = $wStmt->fetch();
$blockId = $warden['BlockID'] ?? 0;

$students = [];
if ($blockId > 0) {
    $sStmt = $pdo->prepare("
        SELECT s.*, r.RoomNumber 
        FROM students s
        JOIN rooms r ON s.RoomID = r.RoomID
        WHERE r.BlockID = :bid
        ORDER BY r.RoomNumber ASC, s.Name ASC
    ");
    $sStmt->execute([':bid' => $blockId]);
    $students = $sStmt->fetchAll();
}

$pageTitle = 'Resident Students - ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-container">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
            <div>
                <h3 class="fw-bold text-dark mb-1">Resident Students Directory</h3>
                <p class="text-muted small mb-0">
                    Block: <strong><?= htmlspecialchars($warden['BlockName'] ?? 'Unassigned') ?></strong> &bull; Student emergency contacts and hostel records.
                </p>
            </div>
            <div style="max-width: 250px;">
                <input type="text" class="form-control form-control-sm" placeholder="Search residents..." data-table-search="#wardenStudentsTable">
            </div>
        </div>

        <?php display_flash(); ?>

        <div class="content-card">
            <div class="content-card-header">
                <h6 class="content-card-title"><i class="bi bi-people me-2 text-muted"></i>Enrolled Residents (<?= count($students) ?>)</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-custom align-middle" id="wardenStudentsTable">
                    <thead>
                        <tr>
                            <th>Room No.</th>
                            <th>Student Resident</th>
                            <th>Course & Year</th>
                            <th>Student Contact</th>
                            <th>Emergency Contact Person</th>
                            <th>Emergency Phone</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($students)): ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">No students allocated to this block yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($students as $st): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-light text-dark border fs-6"><?= htmlspecialchars($st['RoomNumber']) ?></span>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($st['Name']) ?></div>
                                        <div class="small text-muted"><?= htmlspecialchars($st['Email']) ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?= htmlspecialchars($st['Course']) ?></div>
                                        <div class="small text-muted">Year <?= $st['Year'] ?> (<?= htmlspecialchars($st['Gender']) ?>)</div>
                                    </td>
                                    <td>
                                        <a href="tel:<?= htmlspecialchars($st['Phone']) ?>" class="text-decoration-none fw-semibold" style="color: var(--primary-teal);">
                                            <i class="bi bi-telephone-fill me-1"></i><?= htmlspecialchars($st['Phone']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?= htmlspecialchars($st['EmergencyContact']) ?></div>
                                        <div class="small text-muted text-truncate" style="max-width: 180px;"><?= htmlspecialchars($st['Address'] ?? '') ?></div>
                                    </td>
                                    <td>
                                        <a href="tel:<?= htmlspecialchars($st['EmergencyPhone']) ?>" class="btn btn-sm btn-outline-danger py-1 px-2">
                                            <i class="bi bi-telephone-outbound-fill me-1"></i><?= htmlspecialchars($st['EmergencyPhone']) ?>
                                        </a>
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
