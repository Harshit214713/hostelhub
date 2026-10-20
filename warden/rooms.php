<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('Warden');

$wardenId = (int)($_SESSION['user']['WardenID'] ?? 0);

// Get warden's block
$wStmt = $pdo->prepare("SELECT w.*, b.BlockName, b.Gender as BlockGender FROM wardens w LEFT JOIN blocks b ON w.BlockID = b.BlockID WHERE w.WardenID = :wid");
$wStmt->execute([':wid' => $wardenId]);
$warden = $wStmt->fetch();
$blockId = $warden['BlockID'] ?? 0;

// Fetch rooms in this block
$rooms = [];
if ($blockId > 0) {
    $rStmt = $pdo->prepare("
        SELECT r.*,
               GROUP_CONCAT(s.Name SEPARATOR ', ') as occupants_names
        FROM rooms r
        LEFT JOIN students s ON s.RoomID = r.RoomID
        WHERE r.BlockID = :bid
        GROUP BY r.RoomID
        ORDER BY r.RoomNumber ASC
    ");
    $rStmt->execute([':bid' => $blockId]);
    $rooms = $rStmt->fetchAll();
}

$pageTitle = 'Block Rooms & Beds - ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-container">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
            <div>
                <h3 class="fw-bold text-dark mb-1">Rooms & Bed Occupancy</h3>
                <p class="text-muted small mb-0">
                    Hostel Block: <strong><?= htmlspecialchars($warden['BlockName'] ?? 'Unassigned') ?></strong> &bull; Inspect rooms and occupant lists.
                </p>
            </div>
            <div style="max-width: 250px;">
                <input type="text" class="form-control form-control-sm" placeholder="Filter room number..." data-table-search="#wardenRoomsTable">
            </div>
        </div>

        <?php display_flash(); ?>

        <div class="content-card">
            <div class="content-card-header">
                <h6 class="content-card-title"><i class="bi bi-door-closed me-2 text-muted"></i>Configured Rooms (<?= count($rooms) ?>)</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-custom align-middle" id="wardenRoomsTable">
                    <thead>
                        <tr>
                            <th>Room No.</th>
                            <th>Room Type</th>
                            <th>Capacity & Occupancy</th>
                            <th>Current Resident(s)</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rooms)): ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">No rooms found for this block.</td></tr>
                        <?php else: ?>
                            <?php foreach ($rooms as $r): ?>
                                <tr>
                                    <td><div class="fw-bold text-dark fs-6"><?= htmlspecialchars($r['RoomNumber']) ?></div></td>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($r['Type']) ?></span></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-grow-1" style="height: 6px; width: 60px;">
                                                <?php 
                                                    $pct = ($r['Capacity'] > 0) ? round(($r['Occupied'] / $r['Capacity']) * 100) : 0;
                                                    $barColor = $pct >= 100 ? 'bg-danger' : ($pct > 0 ? 'bg-teal' : 'bg-secondary');
                                                ?>
                                                <div class="progress-bar <?= $barColor ?>" style="<?= $barColor === 'bg-teal' ? 'background-color: var(--primary-teal);' : '' ?> width: <?= $pct ?>%;"></div>
                                            </div>
                                            <span class="small fw-semibold"><?= (int)$r['Occupied'] ?> / <?= (int)$r['Capacity'] ?> Beds</span>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if (!empty($r['occupants_names'])): ?>
                                            <div class="text-dark fw-semibold small"><?= htmlspecialchars($r['occupants_names']) ?></div>
                                        <?php else: ?>
                                            <span class="text-muted small fst-italic">Vacant (No residents)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= get_status_badge($r['Status']) ?></td>
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
