<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('Student');

$studentId = (int)($_SESSION['user']['StudentID'] ?? 0);

// Handle Profile Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf()) {
        set_flash('danger', 'Security session expired.');
        redirect('student/profile.php');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $phone = trim($_POST['phone'] ?? '');
        $eContact = trim($_POST['emergency_contact'] ?? '');
        $ePhone = trim($_POST['emergency_phone'] ?? '');
        $address = trim($_POST['address'] ?? '');

        if (empty($phone) || empty($eContact) || empty($ePhone)) {
            set_flash('danger', 'Phone and emergency contact details are required.');
        } else {
            try {
                $stmt = $pdo->prepare("
                    UPDATE students 
                    SET Phone = :p, EmergencyContact = :ec, EmergencyPhone = :ep, Address = :addr 
                    WHERE StudentID = :id
                ");
                $stmt->execute([
                    ':p'    => $phone,
                    ':ec'   => $eContact,
                    ':ep'   => $ePhone,
                    ':addr' => $address,
                    ':id'   => $studentId
                ]);
                set_flash('success', 'Profile contact details updated successfully.');
            } catch (PDOException $e) {
                set_flash('danger', 'Error updating profile: ' . $e->getMessage());
            }
        }
        redirect('student/profile.php');
    }

    if ($action === 'change_password') {
        $currentPwd = $_POST['current_password'] ?? '';
        $newPwd = $_POST['new_password'] ?? '';
        $confirmPwd = $_POST['confirm_password'] ?? '';

        if (strlen($newPwd) < 6) {
            set_flash('danger', 'New password must be at least 6 characters.');
        } elseif ($newPwd !== $confirmPwd) {
            set_flash('danger', 'New passwords do not match.');
        } else {
            // Verify current password
            $loginId = (int)$_SESSION['user']['LoginID'];
            $chk = $pdo->prepare("SELECT Password FROM logins WHERE LoginID = :id");
            $chk->execute([':id' => $loginId]);
            $currentHash = $chk->fetchColumn();

            if (password_verify($currentPwd, $currentHash) || $currentPwd === $currentHash) {
                $newHash = password_hash($newPwd, PASSWORD_BCRYPT);
                $up = $pdo->prepare("UPDATE logins SET Password = :p WHERE LoginID = :id");
                $up->execute([':p' => $newHash, ':id' => $loginId]);
                set_flash('success', 'Password changed successfully!');
            } else {
                set_flash('danger', 'Current password is incorrect.');
            }
        }
        redirect('student/profile.php');
    }
}

// Fetch Profile
$sStmt = $pdo->prepare("
    SELECT s.*, r.RoomNumber, b.BlockName, l.Username 
    FROM students s
    LEFT JOIN rooms r ON s.RoomID = r.RoomID
    LEFT JOIN blocks b ON r.BlockID = b.BlockID
    LEFT JOIN logins l ON l.StudentID = s.StudentID
    WHERE s.StudentID = :sid
");
$sStmt->execute([':sid' => $studentId]);
$student = $sStmt->fetch();

$pageTitle = 'My Profile - ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-container">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
            <div>
                <h3 class="fw-bold text-dark mb-1">My Resident Profile</h3>
                <p class="text-muted small mb-0">View student registration details, update emergency contacts, and change account password.</p>
            </div>
        </div>

        <?php display_flash(); ?>

        <div class="row g-4">
            <!-- Profile Details Form -->
            <div class="col-lg-8">
                <div class="content-card">
                    <div class="content-card-header">
                        <h6 class="content-card-title"><i class="bi bi-person-lines-fill me-2 text-muted"></i>Personal & Student Information</h6>
                    </div>
                    <div class="content-card-body">
                        <form method="POST" action="profile.php" class="needs-validation" novalidate>
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="update_profile">

                            <!-- Non-editable College Verified Fields -->
                            <div class="row g-3 mb-4 p-3 bg-light rounded-3 border">
                                <div class="col-md-6">
                                    <label class="form-label small text-muted">Full Name (Registered)</label>
                                    <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($student['Name']) ?></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small text-muted">College Email Address</label>
                                    <div class="fw-semibold text-dark"><?= htmlspecialchars($student['Email']) ?></div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small text-muted">Gender</label>
                                    <div class="fw-semibold text-dark"><?= htmlspecialchars($student['Gender']) ?></div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small text-muted">Course / Branch</label>
                                    <div class="fw-semibold text-dark"><?= htmlspecialchars($student['Course']) ?></div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small text-muted">Year of Study</label>
                                    <div class="fw-semibold text-dark">Year <?= $student['Year'] ?></div>
                                </div>
                                <div class="col-12 mt-2">
                                    <label class="form-label small text-muted">Allocated Accommodation</label>
                                    <div>
                                        <?php if (!empty($student['RoomNumber'])): ?>
                                            <span class="badge bg-teal fs-6 me-2"><?= htmlspecialchars($student['RoomNumber']) ?></span>
                                            <span class="fw-semibold text-dark"><?= htmlspecialchars($student['BlockName']) ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Not yet assigned</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Editable Contact Details -->
                            <h6 class="text-uppercase text-secondary fw-bold small mb-3" style="letter-spacing: 0.05em;">
                                Editable Contact Information
                            </h6>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">My Mobile Phone Number *</label>
                                    <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($student['Phone']) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Emergency Contact Person & Relation *</label>
                                    <input type="text" name="emergency_contact" class="form-control" value="<?= htmlspecialchars($student['EmergencyContact']) ?>" required placeholder="e.g. Mukesh Shah (Father)">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Emergency Contact Phone Number *</label>
                                    <input type="tel" name="emergency_phone" class="form-control" value="<?= htmlspecialchars($student['EmergencyPhone']) ?>" required placeholder="+91 98...">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Username</label>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($student['Username'] ?? '') ?>" disabled>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-semibold">Permanent Home Address</label>
                                    <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($student['Address'] ?? '') ?></textarea>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-teal">
                                    <i class="bi bi-save me-1"></i> Update Contact Details
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Password Change Form -->
            <div class="col-lg-4">
                <div class="content-card">
                    <div class="content-card-header">
                        <h6 class="content-card-title"><i class="bi bi-shield-lock me-2 text-muted"></i>Change Password</h6>
                    </div>
                    <div class="content-card-body">
                        <form method="POST" action="profile.php" class="needs-validation" novalidate>
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="change_password">

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Current Password *</label>
                                <input type="password" name="current_password" class="form-control" required placeholder="Enter current password">
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">New Password *</label>
                                <input type="password" name="new_password" class="form-control" required placeholder="Min 6 characters">
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Confirm New Password *</label>
                                <input type="password" name="confirm_password" class="form-control" required placeholder="Repeat new password">
                            </div>

                            <button type="submit" class="btn btn-outline-secondary w-100">
                                <i class="bi bi-key me-1"></i> Update Password
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
