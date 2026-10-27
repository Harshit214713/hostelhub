<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('Admin');

// 1. Occupancy Report Data
$occupancyReport = $pdo->query("
    SELECT 
        b.BlockName, b.Gender,
        COUNT(DISTINCT r.RoomID) as total_rooms,
        COALESCE(SUM(r.Capacity), 0) as total_capacity,
        COALESCE(SUM(r.Occupied), 0) as total_occupied,
        (COALESCE(SUM(r.Capacity), 0) - COALESCE(SUM(r.Occupied), 0)) as vacant_beds
    FROM blocks b
    LEFT JOIN rooms r ON b.BlockID = r.BlockID
    GROUP BY b.BlockID
")->fetchAll();

$totalHostelCapacity = 0;
$totalHostelOccupied = 0;
foreach ($occupancyReport as $row) {
    $totalHostelCapacity += (int)$row['total_capacity'];
    $totalHostelOccupied += (int)$row['total_occupied'];
}
$overallRate = ($totalHostelCapacity > 0) ? round(($totalHostelOccupied / $totalHostelCapacity) * 100, 1) : 0;

// 2. Pending Fees Report Data
$pendingFeesReport = $pdo->query("
    SELECT 
        f.*, s.Name as StudentName, s.Phone as StudentPhone, s.Email,
        r.RoomNumber, b.BlockName
    FROM fees f
    JOIN students s ON f.StudentID = s.StudentID
    LEFT JOIN rooms r ON s.RoomID = r.RoomID
    LEFT JOIN blocks b ON r.BlockID = b.BlockID
    WHERE f.Status IN ('Pending', 'Overdue')
    ORDER BY f.DueDate ASC
")->fetchAll();

$totalPendingSum = 0;
foreach ($pendingFeesReport as $pf) {
    $totalPendingSum += (float)$pf['Amount'];
}

// 3. Complaints & Maintenance Report Data
$complaintsReport = $pdo->query("
    SELECT 
        c.*, s.Name as StudentName,
        r.RoomNumber, b.BlockName
    FROM complaints c
    JOIN students s ON c.StudentID = s.StudentID
    LEFT JOIN rooms r ON s.RoomID = r.RoomID
    LEFT JOIN blocks b ON r.BlockID = b.BlockID
    ORDER BY c.Date DESC
")->fetchAll();

$allowedTabs = ['occupancy', 'fees', 'complaints'];
$activeTab = in_array($_GET['tab'] ?? '', $allowedTabs, true) ? $_GET['tab'] : 'occupancy';

$pageTitle = 'Audit Reports - ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-container">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3 no-print">
            <div>
                <h3 class="fw-bold text-dark mb-1">Hostel System Reports & Audits</h3>
                <p class="text-muted small mb-0">Generate, review, and print official reports for college administration and records.</p>
            </div>
            <button class="btn btn-teal btn-sm" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> Print / Save as PDF
            </button>
        </div>

        <!-- Official Print Header (Only visible when printing) -->
        <div class="d-none d-print-block text-center mb-4 pb-3 border-bottom">
            <h3 class="fw-bold text-dark mb-0"><?= APP_INSTITUTION ?></h3>
            <h5 class="fw-semibold text-secondary mb-1"><?= APP_TITLE ?> (HostelHub)</h5>
            <div class="small text-muted">Audit Date: <?= date('d M Y, h:i A') ?></div>
        </div>

        <!-- Report Tabs -->
        <ul class="nav nav-pills mb-4 no-print">
            <li class="nav-item">
                <a class="nav-link <?= $activeTab === 'occupancy' ? 'active bg-teal' : 'text-dark' ?>" href="reports.php?tab=occupancy">
                    <i class="bi bi-door-open me-1"></i> Room Occupancy Report
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $activeTab === 'fees' ? 'active bg-teal' : 'text-dark' ?>" href="reports.php?tab=fees">
                    <i class="bi bi-cash-stack me-1"></i> Pending Fees Audit
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $activeTab === 'complaints' ? 'active bg-teal' : 'text-dark' ?>" href="reports.php?tab=complaints">
                    <i class="bi bi-tools me-1"></i> Maintenance & Complaints Audit
                </a>
            </li>
        </ul>

        <!-- TAB 1: Occupancy Report -->
        <?php if ($activeTab === 'occupancy'): ?>
            <div class="content-card">
                <div class="content-card-header">
                    <div>
                        <h6 class="content-card-title">Block-Wise Bed Capacity & Occupancy Status</h6>
                        <span class="small text-muted">Overall Hostel Occupancy: <strong><?= $overallRate ?>%</strong> (<?= $totalHostelOccupied ?> / <?= $totalHostelCapacity ?> Beds)</span>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-custom align-middle">
                        <thead>
                            <tr>
                                <th>Hostel Block</th>
                                <th>Gender</th>
                                <th>Total Rooms</th>
                                <th>Total Capacity</th>
                                <th>Occupied Beds</th>
                                <th>Vacant Beds</th>
                                <th>Occupancy %</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($occupancyReport as $row): ?>
                                <?php 
                                    $c = (int)$row['total_capacity'];
                                    $o = (int)$row['total_occupied'];
                                    $rate = ($c > 0) ? round(($o / $c) * 100, 1) : 0;
                                ?>
                                <tr>
                                    <td><strong class="text-dark"><?= htmlspecialchars($row['BlockName']) ?></strong></td>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($row['Gender']) ?></span></td>
                                    <td><?= $row['total_rooms'] ?></td>
                                    <td><?= $c ?></td>
                                    <td><span class="fw-semibold text-teal"><?= $o ?></span></td>
                                    <td><span class="fw-semibold text-muted"><?= (int)$row['vacant_beds'] ?></span></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-grow-1" style="height: 6px; width: 60px;">
                                                <div class="progress-bar bg-teal" style="background-color: var(--primary-teal); width: <?= $rate ?>%;"></div>
                                            </div>
                                            <span class="small fw-bold"><?= $rate ?>%</span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold bg-light">
                                <td colspan="3">Hostel Total Summary</td>
                                <td><?= $totalHostelCapacity ?></td>
                                <td class="text-teal"><?= $totalHostelOccupied ?></td>
                                <td><?= $totalHostelCapacity - $totalHostelOccupied ?></td>
                                <td><?= $overallRate ?>%</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

        <!-- TAB 2: Pending Fees Report -->
        <?php elseif ($activeTab === 'fees'): ?>
            <div class="content-card">
                <div class="content-card-header">
                    <div>
                        <h6 class="content-card-title">Pending & Overdue Fee Records</h6>
                        <span class="small text-danger fw-semibold">Total Outstanding Dues: <?= format_currency($totalPendingSum) ?> (<?= count($pendingFeesReport) ?> Invoices)</span>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-custom align-middle">
                        <thead>
                            <tr>
                                <th>Invoice</th>
                                <th>Student Resident</th>
                                <th>Room & Block</th>
                                <th>Amount Due</th>
                                <th>Due Date</th>
                                <th>Contact Phone</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($pendingFeesReport)): ?>
                                <tr><td colspan="7" class="text-center py-4 text-muted">No pending or overdue fees! All accounts clear.</td></tr>
                            <?php else: ?>
                                <?php foreach ($pendingFeesReport as $pf): ?>
                                    <tr>
                                        <td>#FEE-<?= str_pad($pf['FeeID'], 4, '0', STR_PAD_LEFT) ?></td>
                                        <td>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($pf['StudentName']) ?></div>
                                            <div class="small text-muted"><?= htmlspecialchars($pf['Email']) ?></div>
                                        </td>
                                        <td><?= htmlspecialchars($pf['RoomNumber'] ?? 'Unassigned') ?> (<?= htmlspecialchars($pf['BlockName'] ?? '-') ?>)</td>
                                        <td><div class="fw-bold text-dark"><?= format_currency($pf['Amount']) ?></div></td>
                                        <td><?= format_date($pf['DueDate']) ?></td>
                                        <td><?= htmlspecialchars($pf['StudentPhone']) ?></td>
                                        <td><?= get_status_badge($pf['Status']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold bg-light">
                                <td colspan="3">Total Uncollected Receivables</td>
                                <td colspan="4" class="text-danger fs-6"><?= format_currency($totalPendingSum) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

        <!-- TAB 3: Maintenance & Complaints Report -->
        <?php elseif ($activeTab === 'complaints'): ?>
            <div class="content-card">
                <div class="content-card-header">
                    <div>
                        <h6 class="content-card-title">Hostel Maintenance & Grievance Report</h6>
                        <span class="small text-muted">Complete log of student complaints and resolution notes</span>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-custom align-middle">
                        <thead>
                            <tr>
                                <th>Ticket</th>
                                <th>Student & Room</th>
                                <th>Category</th>
                                <th>Title</th>
                                <th>Priority</th>
                                <th>Date Lodged</th>
                                <th>Status</th>
                                <th>Resolution Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($complaintsReport)): ?>
                                <tr><td colspan="8" class="text-center py-4 text-muted">No complaints logged.</td></tr>
                            <?php else: ?>
                                <?php foreach ($complaintsReport as $cr): ?>
                                    <tr>
                                        <td>#CMP-<?= str_pad($cr['ComplaintID'], 4, '0', STR_PAD_LEFT) ?></td>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= htmlspecialchars($cr['StudentName']) ?></div>
                                            <div class="small text-muted"><?= htmlspecialchars($cr['RoomNumber'] ?? '-') ?></div>
                                        </td>
                                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($cr['Category']) ?></span></td>
                                        <td><?= htmlspecialchars($cr['Title']) ?></td>
                                        <td><?= htmlspecialchars($cr['Priority']) ?></td>
                                        <td><?= format_date($cr['Date']) ?></td>
                                        <td><?= get_status_badge($cr['Status']) ?></td>
                                        <td>
                                            <div class="small text-muted" style="max-width: 260px;">
                                                <?= !empty($cr['AdminRemarks']) ? htmlspecialchars($cr['AdminRemarks']) : '<em>Pending action</em>' ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
