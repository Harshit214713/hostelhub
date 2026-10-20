<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('Warden');

$wardenId = (int)($_SESSION['user']['WardenID'] ?? 0);
$wStmt = $pdo->prepare("SELECT w.*, b.BlockName FROM wardens w LEFT JOIN blocks b ON w.BlockID = b.BlockID WHERE w.WardenID = :wid");
$wStmt->execute([':wid' => $wardenId]);
$warden = $wStmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf()) {
        set_flash('danger', 'Security token expired.');
        redirect('warden/announcements.php');
    }

    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $target = trim($_POST['target_audience'] ?? 'Students');
    $postedBy = "Warden (" . ($warden['Name'] ?? 'Warden') . ")";

    if (!empty($title) && !empty($content)) {
        $stmt = $pdo->prepare("INSERT INTO announcements (Title, Content, TargetAudience, PostedBy) VALUES (:t, :c, :ta, :pb)");
        $stmt->execute([':t' => $title, ':c' => $content, ':ta' => $target, ':pb' => $postedBy]);
        set_flash('success', 'Notice posted for students.');
    }
    redirect('warden/announcements.php');
}

$announcements = $pdo->query("SELECT * FROM announcements WHERE TargetAudience IN ('All', 'Wardens', 'Students') ORDER BY IsPinned DESC, AnnouncementID DESC")->fetchAll();

$pageTitle = 'Hostel Notices - ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-container">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
            <div>
                <h3 class="fw-bold text-dark mb-1">Hostel Notices & Announcements</h3>
                <p class="text-muted small mb-0">View administrative circulars and post notices for residents of <?= htmlspecialchars($warden['BlockName'] ?? 'your block') ?>.</p>
            </div>
            <button class="btn btn-teal btn-sm" data-bs-toggle="modal" data-bs-target="#wardenNoticeModal">
                <i class="bi bi-megaphone me-1"></i> Post Resident Notice
            </button>
        </div>

        <?php display_flash(); ?>

        <div class="row g-4">
            <?php if (empty($announcements)): ?>
                <div class="col-12"><div class="content-card text-center py-5 text-muted">No notices currently posted.</div></div>
            <?php else: ?>
                <?php foreach ($announcements as $a): ?>
                    <div class="col-md-6 col-xl-4">
                        <div class="content-card h-100 <?= $a['IsPinned'] ? 'border-primary' : '' ?>" style="<?= $a['IsPinned'] ? 'border-top: 4px solid var(--primary-teal);' : '' ?>">
                            <div class="content-card-body d-flex flex-column h-100">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <span class="badge bg-light text-secondary border">Audience: <?= htmlspecialchars($a['TargetAudience']) ?></span>
                                    <?php if ($a['IsPinned']): ?>
                                        <span class="badge bg-teal">Pinned</span>
                                    <?php endif; ?>
                                </div>
                                <h5 class="fw-bold text-dark mb-2"><?= htmlspecialchars($a['Title']) ?></h5>
                                <p class="text-muted small flex-grow-1" style="white-space: pre-line;"><?= htmlspecialchars($a['Content']) ?></p>
                                <div class="pt-2 border-top mt-auto small text-muted">
                                    <div><i class="bi bi-person me-1"></i><?= htmlspecialchars($a['PostedBy']) ?></div>
                                    <div style="font-size: 0.75rem;"><i class="bi bi-clock me-1"></i><?= format_date($a['CreatedAt']) ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </main>
</div>

<!-- Modal -->
<div class="modal fade" id="wardenNoticeModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="announcements.php" class="modal-content needs-validation" novalidate>
            <?= csrf_field() ?>
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Post Notice for Residents</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Notice Subject *</label>
                    <input type="text" name="title" class="form-control" required placeholder="e.g. Curfew Timings / Room Inspection">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Audience</label>
                    <select name="target_audience" class="form-select">
                        <option value="Students">Students Only</option>
                        <option value="All">All Residents & Staff</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Message Content *</label>
                    <textarea name="content" class="form-control" rows="4" required placeholder="Write message..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-teal">Post Notice</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
