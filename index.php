<?php
// $host = '192.168.0.105';
// $port = '5432';
// $dbname = "edward the db master123";
// $user = 'postgres';
// $password = 'Postgres123!';

$host = '127.0.0.1';
$port = '5432';
$dbname = 'borrow_db';
$user = 'postgres';
$password = '3205';

$dsn = "pgsql:host=$host;port=$port;dbname='$dbname'";

try {
    $pdo = new PDO($dsn, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage();
}

$error = '';
$success = '';

// 2. CRUD Router & Business Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        // --- SECTION 1: BORROWER ACTIONS ---
        if ($action === 'create_borrower') {
            $stmt = $pdo->prepare("INSERT INTO borrowers (borrower_no, full_name, borrower_type) VALUES (:bno, :fname, :btype)");
            $stmt->execute([':bno' => trim($_POST['borrower_no']), ':fname' => trim($_POST['full_name']), ':btype' => $_POST['borrower_type']]);
            $success = "Borrower added.";
        } elseif ($action === 'update_borrower') {
            $stmt = $pdo->prepare("UPDATE borrowers SET borrower_no = :bno, full_name = :fname, borrower_type = :btype WHERE borrower_id = :id");
            $stmt->execute([':bno' => trim($_POST['borrower_no']), ':fname' => trim($_POST['full_name']), ':btype' => $_POST['borrower_type'], ':id' => (int) $_POST['borrower_id']]);
            $success = "Borrower updated.";
        } elseif ($action === 'delete_borrower') {
            $stmt = $pdo->prepare("DELETE FROM borrowers WHERE borrower_id = :id");
            $stmt->execute([':id' => (int) $_POST['borrower_id']]);
            $success = "Borrower deleted.";
        }

        // --- SECTION 2: EQUIPMENT ACTIONS ---
        elseif ($action === 'create_eq') {
            $stmt = $pdo->prepare("INSERT INTO equipment (property_no, equipment_name, quantity_available) VALUES (:prop, :name, :qty)");
            $stmt->execute([':prop' => trim($_POST['property_no']), ':name' => trim($_POST['equipment_name']), ':qty' => (int) $_POST['quantity_available']]);
            $success = "Equipment added.";
        } elseif ($action === 'update_eq') {
            $stmt = $pdo->prepare("UPDATE equipment SET property_no = :prop, equipment_name = :name, quantity_available = :qty WHERE equipment_id = :id");
            $stmt->execute([':prop' => trim($_POST['property_no']), ':name' => trim($_POST['equipment_name']), ':qty' => (int) $_POST['quantity_available'], ':id' => (int) $_POST['equipment_id']]);
            $success = "Equipment updated.";
        } elseif ($action === 'delete_eq') {
            $stmt = $pdo->prepare("DELETE FROM equipment WHERE equipment_id = :id");
            $stmt->execute([':id' => (int) $_POST['equipment_id']]);
            $success = "Equipment deleted.";
        } elseif ($action === 'adjust_stock') {
            $stmt = $pdo->prepare("UPDATE equipment SET quantity_available = quantity_available + :amount WHERE equipment_id = :id");
            $stmt->execute([':amount' => (int) $_POST['amount'], ':id' => (int) $_POST['equipment_id']]);
            $success = "Stock level adjusted.";
        }

        // --- SECTION 3: CHECKOUT (BORROW EQUIPMENT) ---
        elseif ($action === 'checkout_equipment') {
            $pdo->beginTransaction();
            $borrowerId = (int) $_POST['borrower_id'];
            $eqId = (int) $_POST['equipment_id'];
            $qty = (int) $_POST['quantity'];
            $dueDate = $_POST['due_date'];

            // 1. Create Transaction header (Default status is now 'Pending' per schema)
            $stmtTx = $pdo->prepare("INSERT INTO borrow_transactions (borrower_id, due_date, status) VALUES (:bid, :due, 'Pending') RETURNING borrow_id");
            $stmtTx->execute([':bid' => $borrowerId, ':due' => $dueDate]);
            $borrowId = $stmtTx->fetchColumn();

            // 2. Create Line Item
            $stmtItem = $pdo->prepare("INSERT INTO borrow_items (borrow_id, equipment_id, quantity) VALUES (:bid, :eqid, :qty)");
            $stmtItem->execute([':bid' => $borrowId, ':eqid' => $eqId, ':qty' => $qty]);

            // 3. Deduct Stock
            $stmtEq = $pdo->prepare("UPDATE equipment SET quantity_available = quantity_available - :qty WHERE equipment_id = :id");
            $stmtEq->execute([':qty' => $qty, ':id' => $eqId]);

            $pdo->commit();
            $success = "Equipment checked out successfully (Pending Approval).";
        }

        // --- SECTION 4: MANAGE LOAN (RETURN / EXTEND / STATUS CHANGE) ---
        elseif ($action === 'manage_loan') {
            $pdo->beginTransaction();
            $txId = (int) $_POST['borrow_id'];
            $newStatus = $_POST['status'];
            $newDueDate = $_POST['due_date'];

            $stmtCurrent = $pdo->prepare("SELECT status FROM borrow_transactions WHERE borrow_id = :id");
            $stmtCurrent->execute([':id' => $txId]);
            $currentStatus = $stmtCurrent->fetchColumn();

            $stmtUp = $pdo->prepare("UPDATE borrow_transactions SET status = :status, due_date = :due WHERE borrow_id = :id");
            $stmtUp->execute([':status' => $newStatus, ':due' => $newDueDate, ':id' => $txId]);

            // Stock Restoration Logic (Only 'Returned' restores stock now)
            $restoringStatuses = ['Returned'];
            $wasRestored = in_array($currentStatus, $restoringStatuses);
            $isRestored = in_array($newStatus, $restoringStatuses);

            if (!$wasRestored && $isRestored) {
                // Moving to Returned: Restore stock
                $items = $pdo->query("SELECT equipment_id, quantity FROM borrow_items WHERE borrow_id = $txId")->fetchAll();
                $stmtRestore = $pdo->prepare("UPDATE equipment SET quantity_available = quantity_available + :qty WHERE equipment_id = :id");
                foreach ($items as $i) {
                    $stmtRestore->execute([':qty' => $i['quantity'], ':id' => $i['equipment_id']]);
                }
            } elseif ($wasRestored && !$isRestored) {
                // Moving from Returned to Pending/Approved (Undo Return): Deduct stock
                $items = $pdo->query("SELECT equipment_id, quantity FROM borrow_items WHERE borrow_id = $txId")->fetchAll();
                $stmtDeduct = $pdo->prepare("UPDATE equipment SET quantity_available = quantity_available - :qty WHERE equipment_id = :id");
                foreach ($items as $i) {
                    $stmtDeduct->execute([':qty' => $i['quantity'], ':id' => $i['equipment_id']]);
                }
            }

            $pdo->commit();
            $success = "Transaction updated and inventory synchronized.";
        }

        header("Location: " . $_SERVER['PHP_SELF'] . "?msg=" . urlencode($success));
        exit;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        if ($e->getCode() === '23503') {
            $error = "Foreign Key Error: Cannot delete record linked to active transactions.";
        } elseif ($e->getCode() === '23505') {
            $error = "Duplicate Entry: Record or unique property already exists.";
        } elseif ($e->getCode() === '23514') {
            if (strpos($e->getMessage(), 'borrow_transactions_check') !== false) {
                $error = "Date Error: Due date cannot be earlier than the borrow date.";
            } else {
                $error = "Inventory Error: Not enough quantity available in stock to fulfill request.";
            }
        } else {
            $error = "Database Error: " . $e->getMessage();
        }
    }
}

if (isset($_GET['msg'])) {
    $success = $_GET['msg'];
}

// 3. READ DATA FOR UI
$borrowers = $pdo->query("SELECT * FROM borrowers ORDER BY borrower_id DESC")->fetchAll();
$equipments = $pdo->query("SELECT * FROM equipment ORDER BY equipment_id DESC")->fetchAll();

// week 
$activeLoans = $pdo->query("
    SELECT t.borrow_id, t.borrow_date, t.due_date, t.status, 
           b.full_name, b.borrower_type, 
           e.equipment_name, e.property_no, 
           bi.quantity
    FROM borrow_transactions t
    JOIN borrowers b ON t.borrower_id = b.borrower_id
    JOIN borrow_items bi ON t.borrow_id = bi.borrow_id
    JOIN equipment e ON bi.equipment_id = e.equipment_id
    ORDER BY t.status ASC, t.due_date ASC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equipment Borrowing System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .table-container {
            max-height: 350px;
            overflow-y: auto;
        }

        table {
            max-height: 100px;
        }

        thead th {
            position: sticky;
            top: 0;
            background-color: var(--bs-body-bg);
            z-index: 1;
        }
    </style>
</head>

<body class="bg-body-tertiary">

    <div class="container-fluid py-4 px-4">
        <div class="mb-4">
            <h2 class="text-primary mb-0">Equipment Management System</h2>
        </div>

        <!-- Alerts -->
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- ==========================================
         SECTION 1: BORROWERS 
         ========================================== -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">1. Manage Borrowers</h5>
            </div>
            <div class="card-body row g-4">
                <div class="col-lg-3 border-end">
                    <form method="POST" id="formBorrower">
                        <input type="hidden" name="action" id="actBorrower" value="create_borrower">
                        <input type="hidden" name="borrower_id" id="idBorrower" value="">

                        <div class="mb-3">
                            <label class="form-label small">ID Number</label>
                            <input type="text" name="borrower_no" id="noBorrower" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Full Name</label>
                            <input type="text" name="full_name" id="nameBorrower" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Type</label>
                            <select name="borrower_type" id="typeBorrower" class="form-select" required>
                                <option value="Student">Student</option>
                                <option value="Faculty">Faculty</option>
                                <option value="Staff">Staff</option>
                            </select>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary w-100" id="btnSubmitBorrower">Save</button>
                            <button type="button" class="btn btn-secondary d-none" id="btnCancelBorrower"
                                onclick="resetBorrowerForm()">Cancel</button>
                        </div>
                    </form>
                </div>
                <div class="col-lg-9">
                    <div class="row g-2 mb-3">
                        <div class="col-md-7">
                            <input type="text" id="borrowerSearch" class="form-control form-control-sm"
                                placeholder="Type to search ID or Name...">
                        </div>
                        <div class="col-md-5">
                            <select id="borrowerTypeFilter" class="form-select form-select-sm">
                                <option value="">All Types</option>
                                <option value="Student">Student</option>
                                <option value="Faculty">Faculty</option>
                                <option value="Staff">Staff</option>
                            </select>
                        </div>
                    </div>

                    <div class="table-responsive table-container border rounded">
                        <!-- Added id="borrowersTable" here -->
                        <table class="table table-sm table-hover align-middle mb-0" id="borrowersTable">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($borrowers as $b): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($b['borrower_no']) ?></td>
                                        <td><?= htmlspecialchars($b['full_name']) ?></td>
                                        <td><span
                                                class="badge bg-secondary"><?= htmlspecialchars($b['borrower_type']) ?></span>
                                        </td>
                                        <td class="text-end text-nowrap">
                                            <button type="button" class="btn btn-sm btn-outline-info"
                                                onclick="editBorrower(<?= $b['borrower_id'] ?>, '<?= htmlspecialchars(addslashes($b['borrower_no'])) ?>', '<?= htmlspecialchars(addslashes($b['full_name'])) ?>', '<?= htmlspecialchars(addslashes($b['borrower_type'])) ?>')">Edit</button>
                                            <form method="POST" class="d-inline"
                                                onsubmit="return confirm('Delete this borrower?');">
                                                <input type="hidden" name="action" value="delete_borrower">
                                                <input type="hidden" name="borrower_id" value="<?= $b['borrower_id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Del</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==========================================
         SECTION 2: EQUIPMENT INVENTORY
         ========================================== -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">Equipment Inventory
                    Listing</h5>
            </div>
            <div class="card-body row g-4">
                <div class="col-lg-3 border-end">
                    <form method="POST" id="formEq">
                        <input type="hidden" name="action" id="actEq" value="create_eq">
                        <input type="hidden" name="equipment_id" id="idEq" value="">

                        <div class="mb-3">
                            <label class="form-label small">Property / Asset Tag No.</label>
                            <input type="text" name="property_no" id="propEq" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Equipment Name</label>
                            <input type="text" name="equipment_name" id="nameEq" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Quantity Available</label>
                            <input type="number" name="quantity_available" id="qtyEq" class="form-control" min="0"
                                required>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success w-100" id="btnSubmitEq">Save</button>
                            <button type="button" class="btn btn-secondary d-none" id="btnCancelEq"
                                onclick="resetEqForm()">Cancel</button>
                        </div>
                    </form>
                </div>
                <div class="col-lg-9">
                    <div class="table-responsive table-container border rounded">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>Prop #</th>
                                    <th>Name</th>
                                    <th>Qty Available</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($equipments as $e): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($e['property_no']) ?></td>
                                        <td><?= htmlspecialchars($e['equipment_name']) ?></td>
                                        <td
                                            class="fw-bold <?= $e['quantity_available'] == 0 ? 'text-danger' : 'text-success' ?>">
                                            <?= (int) $e['quantity_available'] ?>

                                            <form method="POST" class="d-inline ms-2">
                                                <input type="hidden" name="action" value="adjust_stock">
                                                <input type="hidden" name="equipment_id" value="<?= $e['equipment_id'] ?>">
                                                <input type="hidden" name="amount" value="-1">
                                                <button type="submit" class="btn btn-sm btn-secondary p-0 px-1 border-0"
                                                    <?= $e['quantity_available'] == 0 ? 'disabled' : '' ?>>-</button>
                                            </form>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="adjust_stock">
                                                <input type="hidden" name="equipment_id" value="<?= $e['equipment_id'] ?>">
                                                <input type="hidden" name="amount" value="1">
                                                <button type="submit"
                                                    class="btn btn-sm btn-secondary p-0 px-1 border-0">+</button>
                                            </form>
                                        </td>
                                        <td class="text-end text-nowrap">
                                            <button type="button" class="btn btn-sm btn-outline-info"
                                                onclick="editEq(<?= $e['equipment_id'] ?>, '<?= htmlspecialchars(addslashes($e['property_no'])) ?>', '<?= htmlspecialchars(addslashes($e['equipment_name'])) ?>', <?= $e['quantity_available'] ?>)">Edit</button>
                                            <form method="POST" class="d-inline"
                                                onsubmit="return confirm('Delete this equipment?');">
                                                <input type="hidden" name="action" value="delete_eq">
                                                <input type="hidden" name="equipment_id" value="<?= $e['equipment_id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Del</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==========================================
         SECTION 3: CHECKOUT (BORROW)
         ========================================== -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0">3. Checkout Equipment</h5>
            </div>
            <div class="card-body">
                <form method="POST" class="row g-3 align-items-end">
                    <input type="hidden" name="action" value="checkout_equipment">

                    <div class="col-md-3">
                        <label class="form-label small fw-bold">1. Select Borrower</label>
                        <select name="borrower_id" class="form-select" required>
                            <option value="" disabled selected>Choose...</option>
                            <?php foreach ($borrowers as $b): ?>
                                <option value="<?= $b['borrower_id'] ?>"><?= htmlspecialchars($b['full_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">2. Select Equipment</label>
                        <select name="equipment_id" id="eqSelect" class="form-select" required>
                            <option value="" data-stock="0" disabled selected>Choose Item (In Stock)...</option>
                            <?php foreach ($equipments as $e): ?>
                                <?php if ($e['quantity_available'] > 0): ?>
                                    <option value="<?= $e['equipment_id'] ?>" data-stock="<?= $e['quantity_available'] ?>">
                                        <?= htmlspecialchars($e['equipment_name']) ?> (<?= $e['quantity_available'] ?> left)
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <label class="form-label small fw-bold">3. Qty</label>
                        <input type="number" name="quantity" id="checkoutQty" class="form-control" min="1" value="1"
                            required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold">4. Due Date</label>
                        <input type="date" name="due_date" id="newTxDate" class="form-control" required>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-warning w-100 fw-bold">Checkout</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ==========================================
         SECTION 4: MANAGE ACTIVE LOANS
         ========================================== -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-info text-dark">
                <h5 class="mb-0">4. Manage Loans (Approve, Return, Extend)</h5>
            </div>
            <div class="card-body row g-4">
                <div class="col-lg-3 border-end">
                    <form method="POST" id="formManageLoan">
                        <input type="hidden" name="action" value="manage_loan">
                        <input type="hidden" name="borrow_id" id="mlTxId" required>

                        <h6 class="text-info mb-3" id="mlTitle">Select a Loan to Manage</h6>

                        <div class="mb-3">
                            <label class="form-label small">Change Due Date (Extend)</label>
                            <input type="date" name="due_date" id="mlDueDate" class="form-control" required disabled>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Change Status</label>
                            <select name="status" id="mlStatus" class="form-select" required disabled>
                                <option value="Pending">Pending</option>
                                <option value="Approved">Approved</option>
                                <option value="Overdue">Overdue</option>
                                <option value="Returned">Returned (Restores Stock)</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-info text-dark w-100 fw-bold" id="btnSubmitMl"
                            disabled>Apply Changes</button>
                    </form>
                </div>

                <div class="col-lg-9">
                    <div class="table-responsive table-container border rounded">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>Tx #</th>
                                    <th>Borrower</th>
                                    <th>Equipment</th>
                                    <th>Qty</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($activeLoans as $loan):
                                    $bColor = match ($loan['status']) {
                                        'Pending' => 'text-warning',
                                        'Approved' => 'text-primary',
                                        'Returned' => 'text-success',
                                        'Overdue' => 'text-danger',
                                        default => 'text-secondary'
                                    };
                                    ?>
                                    <tr>
                                        <td class="fw-bold"><?= $loan['borrow_id'] ?></td>
                                        <td><?= htmlspecialchars($loan['full_name']) ?></td>
                                        <td><?= htmlspecialchars($loan['equipment_name']) ?></td>
                                        <td class="fw-bold"><?= (int) $loan['quantity'] ?></td>
                                        <td><?= htmlspecialchars($loan['due_date']) ?></td>
                                        <td class="fw-bold <?= $bColor ?>"><?= htmlspecialchars($loan['status']) ?></td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-sm btn-outline-info"
                                                onclick="manageLoan(<?= $loan['borrow_id'] ?>, '<?= $loan['due_date'] ?>', '<?= $loan['status'] ?>')">
                                                Manage
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($activeLoans)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-3">No loan history available.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>
</body>

</html>