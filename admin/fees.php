<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('Admin');

// Auto-mark overdue fees
$pdo->query("UPDATE fees SET Status = 'Overdue' WHERE Status = 'Pending' AND DueDate < CURDATE()");

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf()) {
        set_flash('danger', 'Security token invalid.');
        redirect('admin/fees.php');
    }

    $action = $_POST['action'] ?? '';

    // Create Fee Invoice
    if ($action === 'create') {
        $studentId = (int)($_POST['student_id'] ?? 0);
        $title = trim($_POST['title'] ?? 'Hostel & Mess Fee');
        $amount = (float)($_POST['amount'] ?? 0);
        $dueDate = trim($_POST['due_date'] ?? date('Y-m-d', strtotime('+15 days')));

        if ($studentId > 0 && $amount > 0 && !empty($dueDate)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO fees (StudentID, Title, Amount, DueDate, Status) VALUES (:sid, :t, :a, :d, 'Pending')");
                $stmt->execute([':sid' => $studentId, ':t' => $title, ':a' => $amount, ':d' => $dueDate]);
                set_flash('success', 'Fee invoice generated successfully.');
            } catch (PDOException $e) {
                set_flash('danger', 'Error creating fee record: ' . $e->getMessage());
            }
        }
        redirect('admin/fees.php');
    }

    // Record Payment
    if ($action === 'record_payment') {
        $feeId = (int)($_POST['fee_id'] ?? 0);
        $method = trim($_POST['payment_method'] ?? 'Cash');
        $txnRef = trim($_POST['transaction_ref'] ?? '');
        $paidDate = trim($_POST['paid_date'] ?? date('Y-m-d'));

        if ($feeId > 0) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE fees 
                    SET Status = 'Paid', PaidDate = :pd, PaymentMethod = :pm, TransactionRef = :ref
                    WHERE FeeID = :id
                ");
                $stmt->execute([':pd' => $paidDate, ':pm' => $method, ':ref' => $txnRef, ':id' => $feeId]);
                set_flash('success', 'Payment recorded successfully! Status updated to Paid.');
            } catch (PDOException $e) {
                set_flash('danger', 'Error updating fee: ' . $e->getMessage());
            }
        }
        redirect('admin/fees.php');
    }

    // Delete Fee
    if ($action === 'delete') {
        $feeId = (int)($_POST['fee_id'] ?? 0);
        if ($feeId > 0) {
            $pdo->prepare("DELETE FROM fees WHERE FeeID = :id")->execute([':id' => $feeId]);
            set_flash('success', 'Fee invoice deleted.');
        }
        redirect('admin/fees.php');
    }
}

// Filter Status
$filterStatus = $_GET['status'] ?? '';
$where = "1=1";
$params = [];
if (!empty($filterStatus)) {
    $where .= " AND f.Status = :stat";
    $params[':stat'] = $filterStatus;
}

$fees = $pdo->prepare("
    SELECT 
        f.*,
        s.Name as StudentName, s.Email, s.Phone,
        r.RoomNumber, b.BlockName
    FROM fees f
    JOIN students s ON f.StudentID = s.StudentID
    LEFT JOIN rooms r ON s.RoomID = r.RoomID
    LEFT JOIN blocks b ON r.BlockID = b.BlockID
    WHERE {$where}
    ORDER BY f.FeeID DESC
");
$fees->execute($params);
$feeList = $fees->fetchAll();

// Fetch all students for invoice creation
$allStudents = $pdo->query("SELECT StudentID, Name, Course, Year FROM students ORDER BY Name ASC")->fetchAll();

$pageTitle = 'Fee Records - ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-container">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
            <div>
                <h3 class="fw-bold text-dark mb-1">Hostel Fee Management</h3>
                <p class="text-muted small mb-0">Issue fee invoices, collect offline/online payments, and monitor pending dues.</p>
            </div>
            <button class="btn btn-teal btn-sm" data-bs-toggle="modal" data-bs-target="#createFeeModal">
                <i class="bi bi-receipt me-1"></i> Issue Fee Invoice
            </button>
        </div>

        <?php display_flash(); ?>

        <!-- Filter bar -->
        <div class="content-card mb-4">
            <div class="p-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex gap-2">
                    <a href="fees.php" class="btn btn-sm <?= empty($filterStatus) ? 'btn-teal' : 'btn-outline-secondary' ?>">All Records</a>
                    <a href="fees.php?status=Pending" class="btn btn-sm <?= $filterStatus === 'Pending' ? 'btn-warning text-dark' : 'btn-outline-secondary' ?>">Pending</a>
                    <a href="fees.php?status=Paid" class="btn btn-sm <?= $filterStatus === 'Paid' ? 'btn-success' : 'btn-outline-secondary' ?>">Paid</a>
                    <a href="fees.php?status=Overdue" class="btn btn-sm <?= $filterStatus === 'Overdue' ? 'btn-danger' : 'btn-outline-secondary' ?>">Overdue</a>
                </div>
                <div style="max-width: 250px;">
                    <input type="text" class="form-control form-control-sm" placeholder="Search fee records..." data-table-search="#feesTable">
                </div>
            </div>
        </div>

        <div class="content-card">
            <div class="content-card-header">
                <h6 class="content-card-title"><i class="bi bi-cash-stack me-2 text-muted"></i>Fee Records (<?= count($feeList) ?>)</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-custom align-middle" id="feesTable">
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Student & Room</th>
                            <th>Fee Description</th>
                            <th>Amount</th>
                            <th>Due Date</th>
                            <th>Payment Status</th>
                            <th>Paid On / Txn</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($feeList)): ?>
                            <tr><td colspan="8" class="text-center py-4 text-muted">No fee records found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($feeList as $f): ?>
                                <tr>
                                    <td>
                                        <span class="fw-bold text-dark">#FEE-<?= str_pad($f['FeeID'], 4, '0', STR_PAD_LEFT) ?></span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?= htmlspecialchars($f['StudentName']) ?></div>
                                        <div class="small text-muted"><?= htmlspecialchars($f['RoomNumber'] ?? 'No room') ?> (<?= htmlspecialchars($f['BlockName'] ?? '-') ?>)</div>
                                    </td>
                                    <td>
                                        <div class="text-dark small fw-semibold"><?= htmlspecialchars($f['Title']) ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark fs-6"><?= format_currency($f['Amount']) ?></div>
                                    </td>
                                    <td>
                                        <span class="small <?= (strtotime($f['DueDate']) < time() && $f['Status'] !== 'Paid') ? 'text-danger fw-bold' : 'text-muted' ?>">
                                            <?= format_date($f['DueDate']) ?>
                                        </span>
                                    </td>
                                    <td><?= get_status_badge($f['Status']) ?></td>
                                    <td>
                                        <?php if ($f['Status'] === 'Paid'): ?>
                                            <div class="small text-dark fw-semibold"><?= format_date($f['PaidDate']) ?></div>
                                            <div class="small text-muted"><?= htmlspecialchars($f['PaymentMethod'] ?? '') ?>: <?= htmlspecialchars($f['TransactionRef'] ?? 'N/A') ?></div>
                                        <?php else: ?>
                                            <span class="text-muted small fst-italic">Not paid</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($f['Status'] !== 'Paid'): ?>
                                            <button class="btn btn-sm btn-teal py-1 px-2"
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#recordPaymentModal"
                                                    data-id="<?= $f['FeeID'] ?>"
                                                    data-student="<?= htmlspecialchars($f['StudentName']) ?>"
                                                    data-title="<?= htmlspecialchars($f['Title']) ?>"
                                                    data-amount="<?= $f['Amount'] ?>">
                                                <i class="bi bi-check2 me-1"></i>Collect
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-outline-secondary py-1 px-2"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#receiptModal"
                                                    data-id="<?= str_pad($f['FeeID'], 4, '0', STR_PAD_LEFT) ?>"
                                                    data-student="<?= htmlspecialchars($f['StudentName']) ?>"
                                                    data-title="<?= htmlspecialchars($f['Title']) ?>"
                                                    data-amount="<?= format_currency($f['Amount']) ?>"
                                                    data-date="<?= format_date($f['PaidDate']) ?>"
                                                    data-method="<?= htmlspecialchars($f['PaymentMethod'] ?? 'N/A') ?>"
                                                    data-ref="<?= htmlspecialchars($f['TransactionRef'] ?? 'N/A') ?>">
                                                <i class="bi bi-printer me-1"></i>Receipt
                                            </button>
                                        <?php endif; ?>

                                        <form method="POST" action="fees.php" class="d-inline" onsubmit="return confirm('Delete this fee entry?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="fee_id" value="<?= $f['FeeID'] ?>">
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

<!-- Modal: Issue Fee -->
<div class="modal fade" id="createFeeModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="fees.php" class="modal-content needs-validation" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Issue Student Fee Invoice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Select Student *</label>
                    <select name="student_id" class="form-select" required>
                        <option value="">-- Select Student --</option>
                        <?php foreach ($allStudents as $as): ?>
                            <option value="<?= $as['StudentID'] ?>">
                                <?= htmlspecialchars($as['Name']) ?> (<?= htmlspecialchars($as['Course']) ?> - Yr <?= $as['Year'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Fee Title *</label>
                    <input type="text" name="title" class="form-control" value="Hostel & Mess Fee - Fall Semester" required>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Amount (₹) *</label>
                        <input type="number" name="amount" class="form-control" value="28000" min="100" step="100" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Due Date *</label>
                        <input type="date" name="due_date" class="form-control" value="<?= date('Y-m-d', strtotime('+15 days')) ?>" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-teal">Create Invoice</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Record Payment -->
<div class="modal fade" id="recordPaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="fees.php" class="modal-content needs-validation" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="record_payment">
            <input type="hidden" name="fee_id" id="pay_fee_id">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Record Fee Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="p-3 bg-light rounded-3 mb-3">
                    <div class="small text-muted">Student:</div>
                    <div class="fw-bold text-dark" id="pay_student_name"></div>
                    <div class="small text-muted mt-1">Invoice: <span id="pay_fee_title"></span></div>
                    <div class="fw-bold fs-5 text-teal mt-1" id="pay_fee_amount"></div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Payment Mode *</label>
                    <select name="payment_method" class="form-select" required>
                        <option value="UPI">UPI / QR Code</option>
                        <option value="Cash">Cash (Hostel Office)</option>
                        <option value="Net Banking">Net Banking / NEFT</option>
                        <option value="Card">Debit / Credit Card</option>
                    </select>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Payment Date *</label>
                        <input type="date" name="paid_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Transaction Reference / Receipt No.</label>
                        <input type="text" name="transaction_ref" class="form-control" placeholder="e.g. UPI/2026/98213">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-teal">Confirm Payment Received</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Receipt View -->
<div class="modal fade" id="receiptModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Payment Receipt</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" id="printableReceipt">
                <div class="text-center mb-4">
                    <div class="fw-bold fs-4 text-dark"><?= APP_NAME ?></div>
                    <div class="small text-muted"><?= APP_TITLE ?></div>
                    <div class="badge bg-success mt-2">PAYMENT CONFIRMED</div>
                </div>
                <hr>
                <div class="row g-2 mb-2 small">
                    <div class="col-6 text-muted">Receipt Number:</div>
                    <div class="col-6 fw-bold text-end" id="rcpt_id"></div>
                    <div class="col-6 text-muted">Student Name:</div>
                    <div class="col-6 fw-bold text-end" id="rcpt_student"></div>
                    <div class="col-6 text-muted">Particulars:</div>
                    <div class="col-6 fw-bold text-end" id="rcpt_title"></div>
                    <div class="col-6 text-muted">Amount Paid:</div>
                    <div class="col-6 fw-bold text-end text-success fs-6" id="rcpt_amount"></div>
                    <div class="col-6 text-muted">Payment Date:</div>
                    <div class="col-6 text-end" id="rcpt_date"></div>
                    <div class="col-6 text-muted">Payment Mode:</div>
                    <div class="col-6 text-end" id="rcpt_method"></div>
                    <div class="col-6 text-muted">Transaction ID:</div>
                    <div class="col-6 text-end font-monospace" id="rcpt_ref"></div>
                </div>
                <div class="alert alert-light border small text-center text-muted mt-3 mb-0">
                    This is an electronically generated receipt from <?= APP_NAME ?>.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-teal" onclick="window.print()"><i class="bi bi-printer me-1"></i> Print Receipt</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const payModal = document.getElementById('recordPaymentModal');
    payModal.addEventListener('show.bs.modal', function (event) {
        const btn = event.relatedTarget;
        document.getElementById('pay_fee_id').value = btn.getAttribute('data-id');
        document.getElementById('pay_student_name').textContent = btn.getAttribute('data-student');
        document.getElementById('pay_fee_title').textContent = btn.getAttribute('data-title');
        document.getElementById('pay_fee_amount').textContent = '₹ ' + parseFloat(btn.getAttribute('data-amount')).toFixed(2);
    });

    const rcptModal = document.getElementById('receiptModal');
    rcptModal.addEventListener('show.bs.modal', function (event) {
        const btn = event.relatedTarget;
        document.getElementById('rcpt_id').textContent = '#REC-' + btn.getAttribute('data-id');
        document.getElementById('rcpt_student').textContent = btn.getAttribute('data-student');
        document.getElementById('rcpt_title').textContent = btn.getAttribute('data-title');
        document.getElementById('rcpt_amount').textContent = btn.getAttribute('data-amount');
        document.getElementById('rcpt_date').textContent = btn.getAttribute('data-date');
        document.getElementById('rcpt_method').textContent = btn.getAttribute('data-method');
        document.getElementById('rcpt_ref').textContent = btn.getAttribute('data-ref');
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
