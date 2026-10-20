<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('Admin');

// Handle Status Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf()) {
        set_flash('danger', 'Security token invalid.');
        redirect('admin/complaints.php');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'update_status') {
        $complaintId = (int)($_POST['complaint_id'] ?? 0);
        $status = trim($_POST['status'] ?? 'Pending');
        $remarks = trim($_POST['admin_remarks'] ?? '');
        $resolvedAt = ($status === 'Resolved') ? date('Y-m-d H:i:s') : null;

        if ($complaintId > 0) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE complaints 
                    SET Status = :s, AdminRemarks = :r, ResolvedAt = :ra 
                    WHERE ComplaintID = :id
                ");
                $stmt->execute([
                    ':s'  => $status,
                    ':r'  => $remarks,
                    ':ra' => $resolvedAt,
                    ':id' => $complaintId
                ]);
                set_flash('success', 'Complaint status updated successfully.');
            } catch (PDOException $e) {
                set_flash('danger', 'Error updating complaint: ' . $e->getMessage());
            }
        }
        redirect('admin/complaints.php');
    }

    if ($action === 'delete') {
        $complaintId = (int)($_POST['complaint_id'] ?? 0);
        if ($complaintId > 0) {
            $pdo->prepare("DELETE FROM complaints WHERE ComplaintID = :id")->execute([':id' => $complaintId]);
            set_flash('success', 'Complaint record deleted.');
        }
        redirect('admin/complaints.php');
    }
}

// Filters
$filterCategory = $_GET['category'] ?? '';
$filterStatus = $_GET['status'] ?? '';

$where = "1=1";
$params = [];

if (!empty($filterCategory)) {
    $where .= " AND c.Category = :cat";
    $params[':cat'] = $filterCategory;
}

if (!empty($filterStatus)) {
    $where .= " AND c.Status = :stat";
    $params[':stat'] = $filterStatus;
}

$stmt = $pdo->prepare("
    SELECT 
        c.*,
        s.Name as StudentName, s.Phone as StudentPhone,
        r.RoomNumber, b.BlockName
    FROM complaints c
    JOIN students s ON c.StudentID = s.StudentID
    LEFT JOIN rooms r ON s.RoomID = r.RoomID
    LEFT JOIN blocks b ON r.BlockID = b.BlockID
    WHERE {$where}
    ORDER BY c.ComplaintID DESC
");
$stmt->execute($params);
$complaints = $stmt->fetchAll();

$categories = ['Electrical', 'Plumbing', 'Carpentry', 'Internet', 'Cleanliness', 'Security', 'Other'];

$pageTitle = 'Complaints & Maintenance - ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-container">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
            <div>
                <h3 class="fw-bold text-dark mb-1">Hostel Complaints & Maintenance</h3>
                <p class="text-muted small mb-0">Track student grievances, assign maintenance tickets, and record resolutions.</p>
            </div>
        </div>

        <?php display_flash(); ?>

        <!-- Filters -->
        <div class="content-card mb-4">
            <div class="p-3">
                <form method="GET" action="complaints.php" class="row g-2 align-items-center">
                    <div class="col-md-3">
                        <select name="category" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat ?>" <?= $filterCategory === $cat ? 'selected' : '' ?>><?= $cat ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">All Statuses</option>
                            <option value="Pending" <?= $filterStatus === 'Pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="In Progress" <?= $filterStatus === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                            <option value="Resolved" <?= $filterStatus === 'Resolved' ? 'selected' : '' ?>>Resolved</option>
                            <option value="Rejected" <?= $filterStatus === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <input type="text" class="form-control form-control-sm" placeholder="Search title or student..." data-table-search="#complaintsTable">
                    </div>
                    <div class="col-md-2 text-end">
                        <?php if (!empty($filterCategory) || !empty($filterStatus)): ?>
                            <a href="<?= BASE_URL ?>/admin/complaints.php" class="btn btn-sm btn-outline-secondary w-100">Reset</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <div class="content-card">
            <div class="content-card-header">
                <h6 class="content-card-title"><i class="bi bi-tools me-2 text-muted"></i>Maintenance Tickets (<?= count($complaints) ?>)</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-custom align-middle" id="complaintsTable">
                    <thead>
                        <tr>
                            <th>Ticket #</th>
                            <th>Student & Location</th>
                            <th>Category</th>
                            <th>Title & Description</th>
                            <th>Priority</th>
                            <th>Date Lodged</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($complaints)): ?>
                            <tr><td colspan="8" class="text-center py-4 text-muted">No complaints match your criteria.</td></tr>
                        <?php else: ?>
                            <?php foreach ($complaints as $c): ?>
                                <tr>
                                    <td>
                                        <span class="fw-bold text-dark">#CMP-<?= str_pad($c['ComplaintID'], 4, '0', STR_PAD_LEFT) ?></span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?= htmlspecialchars($c['StudentName']) ?></div>
                                        <div class="small text-muted"><?= htmlspecialchars($c['RoomNumber'] ?? 'N/A') ?> (<?= htmlspecialchars($c['BlockName'] ?? '-') ?>)</div>
                                        <div class="small text-muted"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($c['StudentPhone']) ?></div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border"><?= htmlspecialchars($c['Category']) ?></span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?= htmlspecialchars($c['Title']) ?></div>
                                        <div class="small text-muted text-truncate" style="max-width: 260px;" title="<?= htmlspecialchars($c['Description']) ?>">
                                            <?= htmlspecialchars($c['Description']) ?>
                                        </div>
                                        <?php if (!empty($c['AdminRemarks'])): ?>
                                            <div class="small text-success mt-1 fst-italic">
                                                <strong>Note:</strong> <?= htmlspecialchars($c['AdminRemarks']) ?>
                                            </div>
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
                                                data-bs-target="#updateComplaintModal"
                                                data-id="<?= $c['ComplaintID'] ?>"
                                                data-title="<?= htmlspecialchars($c['Title']) ?>"
                                                data-status="<?= $c['Status'] ?>"
                                                data-remarks="<?= htmlspecialchars($c['AdminRemarks'] ?? '') ?>">
                                            <i class="bi bi-pencil-square me-1"></i>Action
                                        </button>
                                        <form method="POST" action="complaints.php" class="d-inline" onsubmit="return confirm('Delete this complaint?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="complaint_id" value="<?= $c['ComplaintID'] ?>">
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

<!-- Modal: Update Complaint -->
<div class="modal fade" id="updateComplaintModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="complaints.php" class="modal-content needs-validation" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="complaint_id" id="cmp_id">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Update Complaint Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Complaint Subject</label>
                    <div class="fw-semibold text-dark p-2 bg-light rounded" id="cmp_title"></div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Resolution Status *</label>
                    <select name="status" id="cmp_status" class="form-select" required>
                        <option value="Pending">Pending</option>
                        <option value="In Progress">In Progress (Assigned to Technician)</option>
                        <option value="Resolved">Resolved (Work Completed)</option>
                        <option value="Rejected">Rejected</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Administrative Remarks / Technician Report</label>
                    <textarea name="admin_remarks" id="cmp_remarks" class="form-control" rows="3" placeholder="e.g. Electrician fixed capacitor on Oct 20..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-teal">Save Resolution</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const cmpModal = document.getElementById('updateComplaintModal');
    cmpModal.addEventListener('show.bs.modal', function (event) {
        const btn = event.relatedTarget;
        document.getElementById('cmp_id').value = btn.getAttribute('data-id');
        document.getElementById('cmp_title').textContent = btn.getAttribute('data-title');
        document.getElementById('cmp_status').value = btn.getAttribute('data-status');
        document.getElementById('cmp_remarks').value = btn.getAttribute('data-remarks');
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
