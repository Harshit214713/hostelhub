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
        redirect('admin/announcements.php');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $target = trim($_POST['target_audience'] ?? 'All');
        $isPinned = isset($_POST['is_pinned']) ? 1 : 0;
        $postedBy = $_SESSION['user']['FullName'] ?? 'Admin Office';

        if (!empty($title) && !empty($content)) {
            $stmt = $pdo->prepare("
                INSERT INTO announcements (Title, Content, TargetAudience, PostedBy, IsPinned)
                VALUES (:t, :c, :ta, :pb, :p)
            ");
            $stmt->execute([
                ':t'  => $title,
                ':c'  => $content,
                ':ta' => $target,
                ':pb' => $postedBy,
                ':p'  => $isPinned
            ]);
            set_flash('success', 'Hostel announcement published successfully.');
        }
        redirect('admin/announcements.php');
    }

    if ($action === 'delete') {
        $id = (int)($_POST['announcement_id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare("DELETE FROM announcements WHERE AnnouncementID = :id")->execute([':id' => $id]);
            set_flash('success', 'Announcement removed.');
        }
        redirect('admin/announcements.php');
    }
}

// Fetch all announcements
$announcements = $pdo->query("SELECT * FROM announcements ORDER BY IsPinned DESC, AnnouncementID DESC")->fetchAll();

$pageTitle = 'Announcements - ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-container">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
            <div>
                <h3 class="fw-bold text-dark mb-1">Hostel Notices & Announcements</h3>
                <p class="text-muted small mb-0">Publish official circulars, maintenance schedules, and student meeting notices.</p>
            </div>
            <button class="btn btn-teal btn-sm" data-bs-toggle="modal" data-bs-target="#newAnnouncementModal">
                <i class="bi bi-megaphone me-1"></i> Post New Announcement
            </button>
        </div>

        <?php display_flash(); ?>

        <div class="row g-4">
            <?php if (empty($announcements)): ?>
                <div class="col-12">
                    <div class="content-card text-center py-5 text-muted">
                        <i class="bi bi-megaphone fs-1 text-muted mb-2"></i>
                        <p>No active announcements currently posted.</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($announcements as $a): ?>
                    <div class="col-md-6 col-xl-4">
                        <div class="content-card h-100 <?= $a['IsPinned'] ? 'border-primary' : '' ?>" style="<?= $a['IsPinned'] ? 'border-top: 4px solid var(--primary-teal);' : '' ?>">
                            <div class="content-card-body d-flex flex-column h-100">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <span class="badge bg-light text-secondary border">Target: <?= htmlspecialchars($a['TargetAudience']) ?></span>
                                    <?php if ($a['IsPinned']): ?>
                                        <span class="badge bg-teal"><i class="bi bi-pin-fill me-1"></i>Pinned</span>
                                    <?php endif; ?>
                                </div>
                                <h5 class="fw-bold text-dark mb-2"><?= htmlspecialchars($a['Title']) ?></h5>
                                <p class="text-muted small flex-grow-1" style="white-space: pre-line;"><?= htmlspecialchars($a['Content']) ?></p>
                                
                                <div class="d-flex justify-content-between align-items-center pt-3 border-top mt-auto">
                                    <div class="small text-muted">
                                        <div><i class="bi bi-person me-1"></i><?= htmlspecialchars($a['PostedBy']) ?></div>
                                        <div style="font-size: 0.75rem;"><i class="bi bi-clock me-1"></i><?= format_date($a['CreatedAt']) ?></div>
                                    </div>
                                    <form method="POST" action="announcements.php" onsubmit="return confirm('Delete this announcement?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="announcement_id" value="<?= $a['AnnouncementID'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </main>
</div>

<!-- Modal: Post Announcement -->
<div class="modal fade" id="newAnnouncementModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="announcements.php" class="modal-content needs-validation" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Post New Announcement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Notice Title *</label>
                    <input type="text" name="title" class="form-control" required placeholder="e.g. Water Supply Interruption / Mess Timing">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Target Audience *</label>
                    <select name="target_audience" class="form-select" required>
                        <option value="All">All Residents & Staff</option>
                        <option value="Students">Students Only</option>
                        <option value="Wardens">Wardens Only</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Notice Details *</label>
                    <textarea name="content" class="form-control" rows="4" required placeholder="Write the full message..."></textarea>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_pinned" id="pinNotice">
                    <label class="form-check-label small fw-semibold" for="pinNotice">Pin this announcement at the top</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-teal">Publish Notice</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
