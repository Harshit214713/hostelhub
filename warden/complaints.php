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

// Handle Status Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf()) {
        set_flash('danger', 'Security token invalid.');
        redirect('warden/complaints.php');
    }

    $complaintId = (int)($_POST['complaint_id'] ?? 0);
    $status = trim($_POST['status'] ?? 'Pending');
    $remarks = trim($_POST['remarks'] ?? '');
    $resolvedAt = ($status === 'Resolved') ? date('Y-m-d H:i:s') : null;

    if ($complaintId > 0) {
        $stmt = $pdo->prepare("
            UPDATE complaints 
            SET Status = :s, AdminRemarks = :r, ResolvedAt = :ra 
            WHERE ComplaintID = :id
        ");
        $stmt->execute([
            ':s'  => $status,
            ':r'  => "Warden ({$warden['Name']}): " . $remarks,
            ':ra' => $resolvedAt,
            ':id' => $complaintId
        ]);
        set_flash('success', 'Complaint status updated.');
    }
    redirect('warden/complaints.php');
}

// Fetch complaints
$complaints = [];
if ($blockId > 0) {
    $cStmt = $pdo->prepare("
        SELECT c.*, s.Name as StudentName, s.Phone as StudentPhone, r.RoomNumber
        FROM complaints c
        JOIN students s ON c.StudentID = s.StudentID
        JOIN rooms r ON s.RoomID = r.RoomID
        WHERE r.BlockID = :bid
        ORDER BY c.ComplaintID DESC
    ");
    $cStmt->execute([':bid' => $blockId]);
    $complaints = $cStmt->fetchAll();
}

$pageTitle = 'Block Complaints - ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-container">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
            <div>
                <h3 class="fw-bold text-dark mb-1">Block Maintenance Grievances</h3>
                <p class="text-muted small mb-0">
                    Block: <strong><?= htmlspecialchars($warden['BlockName'] ?? 'Unassigned') ?></strong> &bull; Review student complaints and update repair status.
                </p>
            </div>
            <div style="max-width: 250px;">
                <input type="text" class="form-control form-control-sm" placeholder="Search complaints..." data-table-search="#wardenComplaintsTable">
            </div>
        </div>

        <?php display_flash(); ?>

        <div class="content-card">
            <div class="content-card-header">
                <h6 class="content-card-title"><i class="bi bi-tools me-2 text-muted"></i>Reported Issues (<?= count($complaints) ?>)</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-custom align-middle" id="wardenComplaintsTable">
                    <thead>
                        <tr>
                            <th>Ticket</th>
                            <th>Student & Room</th>
                            <th>Category</th>
                            <th>Issue & Description</th>
                            <th>Priority</th>
                            <th>Date Lodged</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($complaints)): ?>
                            <tr><td colspan="8" class="text-center py-4 text-muted">No maintenance complaints in this block.</td></tr>
                        <?php else: ?>
                            <?php foreach ($complaints as $c): ?>
                                <tr>
                                    <td>#CMP-<?= str_pad($c['ComplaintID'], 4, '0', STR_PAD_LEFT) ?></td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($c['StudentName']) ?></div>
                                        <div class="small text-muted">Room <?= htmlspecialchars($c['RoomNumber']) ?></div>
                                    </td>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($c['Category']) ?></span></td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?= htmlspecialchars($c['Title']) ?></div>
                                        <div class="small text-muted text-truncate" style="max-width: 240px;"><?= htmlspecialchars($c['Description']) ?></div>
                                        <?php if (!empty($c['AdminRemarks'])): ?>
                                            <div class="small text-success mt-1 fst-italic"><?= htmlspecialchars($c['AdminRemarks']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge <?= $c['Priority'] === 'High' ? 'bg-danger' : ($c['Priority'] === 'Medium' ? 'bg-warning text-dark' : 'bg-secondary') ?>">
                                            <?= htmlspecialchars($c['Priority']) ?>
                                        </span>
                                    </td>
                                    <td><?= format_date($c['Date']) ?></td>
                                    <td><?= get_status_badge($c['Status']) ?></td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-teal py-1 px-2"
                                                data-bs-toggle="modal"
                                                data-bs-target="#actionModal"
                                                data-id="<?= $c['ComplaintID'] ?>"
                                                data-title="<?= htmlspecialchars($c['Title']) ?>"
                                                data-status="<?= $c['Status'] ?>"
                                                data-remarks="<?= htmlspecialchars($c['AdminRemarks'] ?? '') ?>">
                                            <i class="bi bi-pencil-square me-1"></i>Resolve
                                        </button>
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

<!-- Modal: Action Complaint -->
<div class="modal fade" id="actionModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="complaints.php" class="modal-content needs-validation" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="complaint_id" id="act_cmp_id">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Update Complaint Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Complaint Subject</label>
                    <div class="fw-semibold text-dark p-2 bg-light rounded" id="act_cmp_title"></div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Status *</label>
                    <select name="status" id="act_cmp_status" class="form-select" required>
                        <option value="Pending">Pending</option>
                        <option value="In Progress">In Progress (Electrician / Plumber assigned)</option>
                        <option value="Resolved">Resolved (Completed)</option>
                        <option value="Rejected">Rejected</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Warden Remarks</label>
                    <textarea name="remarks" id="act_cmp_remarks" class="form-control" rows="3" placeholder="Add resolution note..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-teal">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const actModal = document.getElementById('actionModal');
    actModal.addEventListener('show.bs.modal', function (event) {
        const btn = event.relatedTarget;
        document.getElementById('act_cmp_id').value = btn.getAttribute('data-id');
        document.getElementById('act_cmp_title').textContent = btn.getAttribute('data-title');
        document.getElementById('act_cmp_status').value = btn.getAttribute('data-status');
        document.getElementById('act_cmp_remarks').value = btn.getAttribute('data-remarks');
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
