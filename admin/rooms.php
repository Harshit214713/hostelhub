<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('Admin');

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf()) {
        set_flash('danger', 'Security token invalid. Please try again.');
        redirect('admin/rooms.php');
    }

    $action = $_POST['action'] ?? '';

    // Add Room
    if ($action === 'add') {
        $blockId = (int)($_POST['block_id'] ?? 0);
        $roomNum = trim($_POST['room_number'] ?? '');
        $type = trim($_POST['room_type'] ?? 'Double');
        $capacity = max(1, min(10, (int)($_POST['capacity'] ?? 2)));
        $fee = max(0, (float)($_POST['fee_per_semester'] ?? 25000));
        $status = trim($_POST['status'] ?? 'Available');

        if ($blockId <= 0 || empty($roomNum)) {
            set_flash('danger', 'Block and Room Number are required.');
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO rooms (BlockID, RoomNumber, Type, Capacity, Occupied, FeePerSemester, Status)
                    VALUES (:block, :num, :type, :cap, 0, :fee, :status)
                ");
                $stmt->execute([
                    ':block'  => $blockId,
                    ':num'    => $roomNum,
                    ':type'   => $type,
                    ':cap'    => $capacity,
                    ':fee'    => $fee,
                    ':status' => $status
                ]);
                set_flash('success', "Room {$roomNum} added successfully!");
            } catch (PDOException $e) {
                set_flash('danger', 'Error: A room with this number already exists in this block.');
            }
        }
        redirect('admin/rooms.php');
    }

    // Edit Room
    if ($action === 'edit') {
        $roomId = (int)($_POST['room_id'] ?? 0);
        $blockId = (int)($_POST['block_id'] ?? 0);
        $roomNum = trim($_POST['room_number'] ?? '');
        $type = trim($_POST['room_type'] ?? 'Double');
        $capacity = max(1, min(10, (int)($_POST['capacity'] ?? 2)));
        $fee = max(0, (float)($_POST['fee_per_semester'] ?? 25000));
        $status = trim($_POST['status'] ?? 'Available');

        if ($roomId > 0 && $blockId > 0 && !empty($roomNum)) {
            try {
                // Check if new capacity is smaller than current occupancy
                $currStmt = $pdo->prepare("SELECT Occupied FROM rooms WHERE RoomID = :id");
                $currStmt->execute([':id' => $roomId]);
                $currentOcc = (int)$currStmt->fetchColumn();

                if ($capacity < $currentOcc) {
                    set_flash('danger', "Capacity cannot be less than current occupied beds ({$currentOcc}).");
                } else {
                    // Auto-adjust status if full
                    if ($status !== 'Maintenance') {
                        $status = ($currentOcc >= $capacity) ? 'Full' : 'Available';
                    }

                    $stmt = $pdo->prepare("
                        UPDATE rooms 
                        SET BlockID = :block, RoomNumber = :num, Type = :type, Capacity = :cap, FeePerSemester = :fee, Status = :status
                        WHERE RoomID = :id
                    ");
                    $stmt->execute([
                        ':block'  => $blockId,
                        ':num'    => $roomNum,
                        ':type'   => $type,
                        ':cap'    => $capacity,
                        ':fee'    => $fee,
                        ':status' => $status,
                        ':id'     => $roomId
                    ]);
                    set_flash('success', "Room {$roomNum} updated successfully!");
                }
            } catch (PDOException $e) {
                set_flash('danger', 'Error updating room: ' . $e->getMessage());
            }
        }
        redirect('admin/rooms.php');
    }

    // Delete Room
    if ($action === 'delete') {
        $roomId = (int)($_POST['room_id'] ?? 0);
        if ($roomId > 0) {
            // Check if occupants exist
            $occStmt = $pdo->prepare("SELECT Occupied FROM rooms WHERE RoomID = :id");
            $occStmt->execute([':id' => $roomId]);
            $occ = (int)$occStmt->fetchColumn();

            if ($occ > 0) {
                set_flash('danger', 'Cannot delete room with active student occupants. Please reallocate students first.');
            } else {
                try {
                    $stmt = $pdo->prepare("DELETE FROM rooms WHERE RoomID = :id");
                    $stmt->execute([':id' => $roomId]);
                    set_flash('success', 'Room deleted successfully.');
                } catch (PDOException $e) {
                    set_flash('danger', 'Error deleting room: ' . $e->getMessage());
                }
            }
        }
        redirect('admin/rooms.php');
    }
}

// Fetch all blocks for dropdown
$blocks = $pdo->query("SELECT * FROM blocks ORDER BY BlockName ASC")->fetchAll();

// Filter
$filterBlock = isset($_GET['block_id']) ? (int)$_GET['block_id'] : 0;
$filterStatus = isset($_GET['status']) ? trim($_GET['status']) : '';

$query = "
    SELECT r.*, b.BlockName, b.Gender as BlockGender,
           (SELECT COUNT(*) FROM students s WHERE s.RoomID = r.RoomID) as actual_occupants
    FROM rooms r
    JOIN blocks b ON r.BlockID = b.BlockID
    WHERE 1=1
";
$params = [];

if ($filterBlock > 0) {
    $query .= " AND r.BlockID = :bid";
    $params[':bid'] = $filterBlock;
}

if (!empty($filterStatus)) {
    $query .= " AND r.Status = :stat";
    $params[':stat'] = $filterStatus;
}

$query .= " ORDER BY b.BlockName ASC, r.RoomNumber ASC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$rooms = $stmt->fetchAll();

$pageTitle = 'Rooms Management - ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-container">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
            <div>
                <h3 class="fw-bold text-dark mb-1">Hostel Room Directory</h3>
                <p class="text-muted small mb-0">Track room capacity, occupancy status, semester tariffs, and maintenance.</p>
            </div>
            <button class="btn btn-teal btn-sm" data-bs-toggle="modal" data-bs-target="#addRoomModal">
                <i class="bi bi-plus-lg me-1"></i> Add New Room
            </button>
        </div>

        <?php display_flash(); ?>

        <!-- Filter Card -->
        <div class="content-card mb-4">
            <div class="p-3">
                <form method="GET" action="rooms.php" class="row g-2 align-items-center">
                    <div class="col-md-4">
                        <select name="block_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">All Hostel Blocks</option>
                            <?php foreach ($blocks as $b): ?>
                                <option value="<?= $b['BlockID'] ?>" <?= $filterBlock == $b['BlockID'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($b['BlockName']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">All Statuses</option>
                            <option value="Available" <?= $filterStatus === 'Available' ? 'selected' : '' ?>>Available</option>
                            <option value="Full" <?= $filterStatus === 'Full' ? 'selected' : '' ?>>Full</option>
                            <option value="Maintenance" <?= $filterStatus === 'Maintenance' ? 'selected' : '' ?>>Maintenance</option>
                        </select>
                    </div>
                    <div class="col-md-5 d-flex gap-2">
                        <input type="text" class="form-control form-control-sm" placeholder="Search room number..." data-table-search="#roomsTable">
                        <?php if ($filterBlock > 0 || !empty($filterStatus)): ?>
                            <a href="<?= BASE_URL ?>/admin/rooms.php" class="btn btn-sm btn-outline-secondary">Reset</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <div class="content-card">
            <div class="content-card-header">
                <h6 class="content-card-title"><i class="bi bi-door-closed me-2 text-muted"></i>Configured Rooms (<?= count($rooms) ?>)</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-custom align-middle" id="roomsTable">
                    <thead>
                        <tr>
                            <th>Room No.</th>
                            <th>Hostel Block</th>
                            <th>Room Type</th>
                            <th>Tariff (Per Sem)</th>
                            <th>Capacity & Occupancy</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rooms)): ?>
                            <tr><td colspan="7" class="text-center py-4 text-muted">No rooms match the selected criteria.</td></tr>
                        <?php else: ?>
                            <?php foreach ($rooms as $r): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($r['RoomNumber']) ?></div>
                                    </td>
                                    <td>
                                        <span class="fw-semibold text-secondary"><?= htmlspecialchars($r['BlockName']) ?></span>
                                        <span class="badge bg-light text-muted border ms-1"><?= htmlspecialchars($r['BlockGender']) ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border"><?= htmlspecialchars($r['Type']) ?></span>
                                    </td>
                                    <td>
                                        <?= format_currency($r['FeePerSemester']) ?>
                                    </td>
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
                                    <td><?= get_status_badge($r['Status']) ?></td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-outline-secondary me-1" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editRoomModal"
                                                data-id="<?= $r['RoomID'] ?>"
                                                data-block="<?= $r['BlockID'] ?>"
                                                data-num="<?= htmlspecialchars($r['RoomNumber']) ?>"
                                                data-type="<?= htmlspecialchars($r['Type']) ?>"
                                                data-cap="<?= $r['Capacity'] ?>"
                                                data-fee="<?= $r['FeePerSemester'] ?>"
                                                data-status="<?= $r['Status'] ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form method="POST" action="rooms.php" class="d-inline" onsubmit="return confirm('Are you sure you want to delete room <?= htmlspecialchars($r['RoomNumber']) ?>?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="room_id" value="<?= $r['RoomID'] ?>">
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

<!-- Modal: Add Room -->
<div class="modal fade" id="addRoomModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="rooms.php" class="modal-content needs-validation" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Add New Room</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Hostel Block *</label>
                    <select name="block_id" class="form-select" required>
                        <option value="">Select Block</option>
                        <?php foreach ($blocks as $b): ?>
                            <option value="<?= $b['BlockID'] ?>"><?= htmlspecialchars($b['BlockName']) ?> (<?= $b['Gender'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Room Number *</label>
                        <input type="text" name="room_number" class="form-control" required placeholder="e.g. A-104">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Room Type *</label>
                        <select name="room_type" class="form-select" required>
                            <option value="Single">Single</option>
                            <option value="Double" selected>Double</option>
                            <option value="Triple">Triple</option>
                            <option value="Four-Bed">Four-Bed</option>
                        </select>
                    </div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Total Bed Capacity *</label>
                        <input type="number" name="capacity" class="form-control" value="2" min="1" max="6" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Tariff / Semester (₹) *</label>
                        <input type="number" name="fee_per_semester" class="form-control" value="28000" min="0" step="500" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Status *</label>
                    <select name="status" class="form-select" required>
                        <option value="Available" selected>Available</option>
                        <option value="Maintenance">Maintenance</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-teal">Add Room</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Room -->
<div class="modal fade" id="editRoomModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="rooms.php" class="modal-content needs-validation" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="room_id" id="edit_room_id">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Edit Room Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Hostel Block *</label>
                    <select name="block_id" id="edit_block_id" class="form-select" required>
                        <?php foreach ($blocks as $b): ?>
                            <option value="<?= $b['BlockID'] ?>"><?= htmlspecialchars($b['BlockName']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Room Number *</label>
                        <input type="text" name="room_number" id="edit_room_number" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Room Type *</label>
                        <select name="room_type" id="edit_room_type" class="form-select" required>
                            <option value="Single">Single</option>
                            <option value="Double">Double</option>
                            <option value="Triple">Triple</option>
                            <option value="Four-Bed">Four-Bed</option>
                        </select>
                    </div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Bed Capacity *</label>
                        <input type="number" name="capacity" id="edit_capacity" class="form-control" min="1" max="6" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Tariff / Sem (₹) *</label>
                        <input type="number" name="fee_per_semester" id="edit_fee" class="form-control" min="0" step="500" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Status *</label>
                    <select name="status" id="edit_status" class="form-select" required>
                        <option value="Available">Available</option>
                        <option value="Full">Full</option>
                        <option value="Maintenance">Maintenance</option>
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
    const editModal = document.getElementById('editRoomModal');
    editModal.addEventListener('show.bs.modal', function (event) {
        const btn = event.relatedTarget;
        document.getElementById('edit_room_id').value = btn.getAttribute('data-id');
        document.getElementById('edit_block_id').value = btn.getAttribute('data-block');
        document.getElementById('edit_room_number').value = btn.getAttribute('data-num');
        document.getElementById('edit_room_type').value = btn.getAttribute('data-type');
        document.getElementById('edit_capacity').value = btn.getAttribute('data-cap');
        document.getElementById('edit_fee').value = btn.getAttribute('data-fee');
        document.getElementById('edit_status').value = btn.getAttribute('data-status');
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
