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
        set_flash('danger', 'Security token expired.');
        redirect('admin/wardens.php');
    }

    $action = $_POST['action'] ?? '';

    // Add Warden
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $blockId = !empty($_POST['block_id']) ? (int)$_POST['block_id'] : null;
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? 'password123');

        if (empty($name) || empty($email) || empty($username)) {
            set_flash('danger', 'Name, email, and username are required.');
        } else {
            try {
                $pdo->beginTransaction();

                // Check username uniqueness
                $check = $pdo->prepare("SELECT COUNT(*) FROM logins WHERE Username = :u");
                $check->execute([':u' => $username]);
                if ($check->fetchColumn() > 0) {
                    throw new Exception("Username {$username} is already taken.");
                }

                // Insert Warden
                $stmt = $pdo->prepare("INSERT INTO wardens (Name, Email, Phone, BlockID) VALUES (:n, :e, :p, :b)");
                $stmt->execute([':n' => $name, ':e' => $email, ':p' => $phone, ':b' => $blockId]);
                $wardenId = $pdo->lastInsertId();

                // Insert Login Account
                $pwdHash = password_hash($password, PASSWORD_BCRYPT);
                $lStmt = $pdo->prepare("INSERT INTO logins (Username, Password, Role, WardenID) VALUES (:u, :p, 'Warden', :wid)");
                $lStmt->execute([':u' => $username, ':p' => $pwdHash, ':wid' => $wardenId]);

                $pdo->commit();
                set_flash('success', "Warden {$name} registered successfully!");
            } catch (Exception $e) {
                $pdo->rollBack();
                set_flash('danger', 'Error: ' . $e->getMessage());
            }
        }
        redirect('admin/wardens.php');
    }

    // Edit Warden
    if ($action === 'edit') {
        $wardenId = (int)($_POST['warden_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $blockId = !empty($_POST['block_id']) ? (int)$_POST['block_id'] : null;

        if ($wardenId > 0 && !empty($name)) {
            try {
                $stmt = $pdo->prepare("UPDATE wardens SET Name = :n, Email = :e, Phone = :p, BlockID = :b WHERE WardenID = :id");
                $stmt->execute([':n' => $name, ':e' => $email, ':p' => $phone, ':b' => $blockId, ':id' => $wardenId]);
                set_flash('success', "Warden {$name} updated successfully.");
            } catch (PDOException $e) {
                set_flash('danger', 'Error updating warden: ' . $e->getMessage());
            }
        }
        redirect('admin/wardens.php');
    }

    // Delete Warden
    if ($action === 'delete') {
        $wardenId = (int)($_POST['warden_id'] ?? 0);
        if ($wardenId > 0) {
            try {
                $pdo->prepare("DELETE FROM wardens WHERE WardenID = :id")->execute([':id' => $wardenId]);
                set_flash('success', 'Warden account deleted.');
            } catch (PDOException $e) {
                set_flash('danger', 'Error deleting warden: ' . $e->getMessage());
            }
        }
        redirect('admin/wardens.php');
    }
}

// Fetch all blocks
$blocks = $pdo->query("SELECT * FROM blocks ORDER BY BlockName ASC")->fetchAll();

// Fetch all wardens
$wardens = $pdo->query("
    SELECT 
        w.*,
        b.BlockName,
        b.Gender as BlockGender,
        l.Username,
        (SELECT COUNT(*) FROM rooms r JOIN students s ON s.RoomID = r.RoomID WHERE r.BlockID = w.BlockID) as total_students_managed
    FROM wardens w
    LEFT JOIN blocks b ON w.BlockID = b.BlockID
    LEFT JOIN logins l ON l.WardenID = w.WardenID
    ORDER BY w.WardenID ASC
")->fetchAll();

$pageTitle = 'Hostel Wardens - ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-container">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
            <div>
                <h3 class="fw-bold text-dark mb-1">Hostel Wardens Management</h3>
                <p class="text-muted small mb-0">Appoint wardens, assign supervision blocks, and manage warden portal access.</p>
            </div>
            <button class="btn btn-teal btn-sm" data-bs-toggle="modal" data-bs-target="#addWardenModal">
                <i class="bi bi-shield-plus me-1"></i> Add New Warden
            </button>
        </div>

        <?php display_flash(); ?>

        <div class="content-card">
            <div class="content-card-header">
                <h6 class="content-card-title"><i class="bi bi-shield-check me-2 text-muted"></i>Hostel Wardens (<?= count($wardens) ?>)</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-custom align-middle">
                    <thead>
                        <tr>
                            <th>Warden Name</th>
                            <th>Contact Information</th>
                            <th>Portal Login</th>
                            <th>Supervised Block</th>
                            <th>Residents Under Care</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($wardens)): ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">No wardens registered.</td></tr>
                        <?php else: ?>
                            <?php foreach ($wardens as $w): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($w['Name']) ?></div>
                                        <span class="small text-muted">ID #<?= $w['WardenID'] ?></span>
                                    </td>
                                    <td>
                                        <div class="text-dark small"><i class="bi bi-envelope me-1 text-muted"></i><?= htmlspecialchars($w['Email']) ?></div>
                                        <div class="text-muted small"><i class="bi bi-telephone me-1 text-muted"></i><?= htmlspecialchars($w['Phone']) ?></div>
                                    </td>
                                    <td>
                                        <code><?= htmlspecialchars($w['Username'] ?? 'None') ?></code>
                                    </td>
                                    <td>
                                        <?php if (!empty($w['BlockName'])): ?>
                                            <span class="fw-semibold text-dark"><?= htmlspecialchars($w['BlockName']) ?></span>
                                            <span class="badge bg-light text-muted border ms-1"><?= htmlspecialchars($w['BlockGender']) ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Unassigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="fw-semibold text-primary"><?= (int)$w['total_students_managed'] ?></span> students
                                    </td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-outline-secondary me-1" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editWardenModal"
                                                data-id="<?= $w['WardenID'] ?>"
                                                data-name="<?= htmlspecialchars($w['Name']) ?>"
                                                data-email="<?= htmlspecialchars($w['Email']) ?>"
                                                data-phone="<?= htmlspecialchars($w['Phone']) ?>"
                                                data-block="<?= $w['BlockID'] ?? '' ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form method="POST" action="wardens.php" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this warden?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="warden_id" value="<?= $w['WardenID'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
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

<!-- Modal: Add Warden -->
<div class="modal fade" id="addWardenModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="wardens.php" class="modal-content needs-validation" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Register New Warden</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Full Name *</label>
                    <input type="text" name="name" class="form-control" required placeholder="e.g. Ramesh Patil">
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Email Address *</label>
                        <input type="email" name="email" class="form-control" required placeholder="warden@college.edu">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Phone Number *</label>
                        <input type="text" name="phone" class="form-control" required placeholder="+91 9422011223">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Assign Hostel Block</label>
                    <select name="block_id" class="form-select">
                        <option value="">-- Unassigned --</option>
                        <?php foreach ($blocks as $b): ?>
                            <option value="<?= $b['BlockID'] ?>"><?= htmlspecialchars($b['BlockName']) ?> (<?= $b['Gender'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Login Username *</label>
                        <input type="text" name="username" class="form-control" required placeholder="e.g. warden_patil">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Password *</label>
                        <input type="password" name="password" class="form-control" required value="password123">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-teal">Create Warden</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Warden -->
<div class="modal fade" id="editWardenModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="wardens.php" class="modal-content needs-validation" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="warden_id" id="edit_warden_id">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Edit Warden Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Full Name *</label>
                    <input type="text" name="name" id="edit_warden_name" class="form-control" required>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Email *</label>
                        <input type="email" name="email" id="edit_warden_email" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Phone *</label>
                        <input type="text" name="phone" id="edit_warden_phone" class="form-control" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Assigned Hostel Block</label>
                    <select name="block_id" id="edit_warden_block" class="form-select">
                        <option value="">-- None --</option>
                        <?php foreach ($blocks as $b): ?>
                            <option value="<?= $b['BlockID'] ?>"><?= htmlspecialchars($b['BlockName']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-teal">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const editModal = document.getElementById('editWardenModal');
    editModal.addEventListener('show.bs.modal', function (event) {
        const btn = event.relatedTarget;
        document.getElementById('edit_warden_id').value = btn.getAttribute('data-id');
        document.getElementById('edit_warden_name').value = btn.getAttribute('data-name');
        document.getElementById('edit_warden_email').value = btn.getAttribute('data-email');
        document.getElementById('edit_warden_phone').value = btn.getAttribute('data-phone');
        document.getElementById('edit_warden_block').value = btn.getAttribute('data-block');
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
