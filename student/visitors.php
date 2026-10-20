<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('Student');

$studentId = (int)($_SESSION['user']['StudentID'] ?? 0);

// Fetch visitors
$vStmt = $pdo->prepare("
    SELECT * FROM visitors 
    WHERE StudentID = :sid 
    ORDER BY VisitorID DESC
");
$vStmt->execute([':sid' => $studentId]);
$visitors = $vStmt->fetchAll();

$pageTitle = 'My Visitors - ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-container">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
            <div>
                <h3 class="fw-bold text-dark mb-1">My Guest & Visitor History</h3>
                <p class="text-muted small mb-0">Record of family and guests logged through the hostel security gate.</p>
            </div>
        </div>

        <?php display_flash(); ?>

        <div class="content-card">
            <div class="content-card-header">
                <h6 class="content-card-title"><i class="bi bi-person-check me-2 text-muted"></i>Visitor Logs (<?= count($visitors) ?>)</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-custom align-middle">
                    <thead>
                        <tr>
                            <th>Visitor Name</th>
                            <th>Relationship</th>
                            <th>Contact Phone</th>
                            <th>Check-In Time</th>
                            <th>Check-Out Time</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($visitors)): ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">No visitors registered for your room.</td></tr>
                        <?php else: ?>
                            <?php foreach ($visitors as $v): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($v['Name']) ?></div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border"><?= htmlspecialchars($v['Relation']) ?></span>
                                    </td>
                                    <td>
                                        <span class="small text-muted"><?= htmlspecialchars($v['Phone']) ?></span>
                                    </td>
                                    <td>
                                        <div class="small fw-semibold"><?= format_date($v['CheckInTime'], true) ?></div>
                                    </td>
                                    <td>
                                        <?php if ($v['Status'] === 'Checked Out'): ?>
                                            <div class="small text-muted"><?= format_date($v['CheckOutTime'], true) ?></div>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Inside Campus</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= get_status_badge($v['Status']) ?></td>
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
