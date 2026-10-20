<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('Student');

$studentId = (int)($_SESSION['user']['StudentID'] ?? 0);

// Handle New Complaint Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf()) {
        set_flash('danger', 'Security session expired.');
        redirect('student/complaints.php');
    }

    $category = trim($_POST['category'] ?? 'Other');
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $priority = trim($_POST['priority'] ?? 'Medium');

    if (empty($title) || empty($description)) {
        set_flash('danger', 'Please provide both title and a detailed description of the issue.');
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO complaints (StudentID, Category, Title, Description, Date, Priority, Status)
                VALUES (:sid, :cat, :t, :d, CURDATE(), :p, 'Pending')
            ");
            $stmt->execute([
                ':sid' => $studentId,
                ':cat' => $category,
                ':t'   => $title,
                ':d'   => $description,
                ':p'   => $priority
            ]);
            set_flash('success', 'Your complaint has been submitted. The warden and hostel maintenance team have been notified.');
        } catch (PDOException $e) {
            set_flash('danger', 'Error submitting complaint: ' . $e->getMessage());
        }
    }
    redirect('student/complaints.php');
}

// Fetch complaints for this student
$complaints = $pdo->prepare("
    SELECT * FROM complaints 
    WHERE StudentID = :sid 
    ORDER BY ComplaintID DESC
");
$complaints->execute([':sid' => $studentId]);
$complaintList = $complaints->fetchAll();

$pageTitle = 'Complaints - ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-container">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
            <div>
                <h3 class="fw-bold text-dark mb-1">Hostel Maintenance & Complaints</h3>
                <p class="text-muted small mb-0">Report facility issues in your room or common areas and track repairs in real-time.</p>
            </div>
            <button class="btn btn-teal btn-sm" data-bs-toggle="modal" data-bs-target="#newComplaintModal">
                <i class="bi bi-plus-circle me-1"></i> Submit New Complaint
            </button>
        </div>

        <?php display_flash(); ?>

        <div class="content-card">
            <div class="content-card-header">
                <h6 class="content-card-title"><i class="bi bi-chat-left-dots me-2 text-muted"></i>My Complaint History (<?= count($complaintList) ?>)</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-custom align-middle">
                    <thead>
                        <tr>
                            <th>Ticket #</th>
                            <th>Category</th>
                            <th>Issue Summary</th>
                            <th>Priority</th>
                            <th>Date Lodged</th>
                            <th>Status</th>
                            <th>Resolution & Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($complaintList)): ?>
                            <tr><td colspan="7" class="text-center py-4 text-muted">You have not submitted any complaints yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($complaintList as $c): ?>
                                <tr>
                                    <td>
                                        <span class="fw-bold text-dark">#CMP-<?= str_pad($c['ComplaintID'], 4, '0', STR_PAD_LEFT) ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border"><?= htmlspecialchars($c['Category']) ?></span>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($c['Title']) ?></div>
                                        <div class="small text-muted" style="max-width: 300px;"><?= htmlspecialchars($c['Description']) ?></div>
                                    </td>
                                    <td>
                                        <span class="badge <?= $c['Priority'] === 'High' ? 'bg-danger' : ($c['Priority'] === 'Medium' ? 'bg-warning text-dark' : 'bg-secondary') ?>">
                                            <?= htmlspecialchars($c['Priority']) ?>
                                        </span>
                                    </td>
                                    <td><?= format_date($c['Date']) ?></td>
                                    <td><?= get_status_badge($c['Status']) ?></td>
                                    <td>
                                        <?php if (!empty($c['AdminRemarks'])): ?>
                                            <div class="small text-success fw-semibold"><?= htmlspecialchars($c['AdminRemarks']) ?></div>
                                            <?php if (!empty($c['ResolvedAt'])): ?>
                                                <div class="small text-muted" style="font-size: 0.72rem;"><?= format_date($c['ResolvedAt'], true) ?></div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted small fst-italic">Assigned for technician review</span>
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

<!-- Modal: New Complaint -->
<div class="modal fade" id="newComplaintModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="complaints.php" class="modal-content needs-validation" novalidate>
            <?= csrf_field() ?>
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Lodge Maintenance Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Issue Category *</label>
                    <select name="category" class="form-select" required>
                        <option value="Electrical">Electrical (Fan, Light, Switch, Geyser)</option>
                        <option value="Plumbing">Plumbing (Tap leakage, Drain blockage)</option>
                        <option value="Carpentry">Carpentry (Door latch, Cupboard, Bed)</option>
                        <option value="Internet">Internet / Wi-Fi Connectivity</option>
                        <option value="Cleanliness">Cleanliness & Housekeeping</option>
                        <option value="Security">Security & Access</option>
                        <option value="Other" selected>Other Maintenance</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Subject / Brief Title *</label>
                    <input type="text" name="title" class="form-control" required placeholder="e.g. Bathroom tap leaking or tube light flickering">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Urgency Priority *</label>
                    <select name="priority" class="form-select" required>
                        <option value="Low">Low (Can be fixed in 2-3 days)</option>
                        <option value="Medium" selected>Medium (Within 24-48 hours)</option>
                        <option value="High">High (Immediate / Urgent)</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Detailed Description *</label>
                    <textarea name="description" class="form-control" rows="4" required placeholder="Describe the location, room number, and specifics of the problem..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-teal">Submit Complaint</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
