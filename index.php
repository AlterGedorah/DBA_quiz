<?php
// $host = '192.168.0.105';
// $port = '5432';
// $dbname = "edward the db master123";
// $user = 'postgres';
// $password = 'Postgres123!';

require('scripts/main.php');
require('scripts/reportsW6.php');
$currentPage = basename($_SERVER['PHP_SELF']);
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
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark border-bottom border-body sticky-top mb-4 shadow-sm">
  <div class="container-fluid px-4">
    <a class="navbar-brand fw-bold text-primary" href="index.php">Equipment System</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item">
          <a class="nav-link <?= ($currentPage === 'index.php') ? 'active' : '' ?>" 
             <?= ($currentPage === 'index.php') ? 'aria-current="page"' : '' ?> 
             href="index.php">Home</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= ($currentPage === 'week7.php') ? 'active' : '' ?>" 
             <?= ($currentPage === 'week7.php') ? 'aria-current="page"' : '' ?> 
             href="week7.php">Week 7</a>
        </li>
      </ul>
    </div>
  </div>
</nav>
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
         SECTION 4: MANAGE ACTIVE LOANS & FILTERED RECORDS
         ========================================== -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-info text-dark d-flex justify-content-between align-items-center">
                <h5 class="mb-0">4. Filtered Borrowing Records & Management</h5>
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
                                <option value="Active">Active</option>
                                <option value="Overdue">Overdue</option>
                                <option value="Returned">Returned (Restores Stock)</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-info text-dark w-100 fw-bold" id="btnSubmitMl"
                            disabled>Apply Changes</button>
                    </form>
                </div>

                <div class="col-lg-9">
                    <!-- Filter Form -->
                    <form method="GET" class="row g-2 mb-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label small">Status Filter</label>
                            <select name="tx_status" class="form-select form-select-sm">
                                <option value="">All Statuses</option>
                                <option value="Pending" <?= $txStatusFilter === 'Pending' ? 'selected' : '' ?>>Pending
                                </option>
                                <option value="Active" <?= $txStatusFilter === 'Active' ? 'selected' : '' ?>>Active
                                </option>
                                <option value="Overdue" <?= $txStatusFilter === 'Overdue' ? 'selected' : '' ?>>Overdue
                                </option>
                                <option value="Returned" <?= $txStatusFilter === 'Returned' ? 'selected' : '' ?>>Returned   
                                </option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">From Due Date</label>
                            <input type="date" name="start_date" class="form-control form-control-sm"
                                value="<?= htmlspecialchars($txStartDate) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">To Due Date</label>
                            <input type="date" name="end_date" class="form-control form-control-sm"
                                value="<?= htmlspecialchars($txEndDate) ?>">
                        </div>
                        <div class="col-md-3 d-flex gap-1">
                            <button type="submit" class="btn btn-sm btn-info text-dark w-100 fw-bold">Filter</button>
                            <a href="<?= strtok($_SERVER["REQUEST_URI"], '?') ?>"
                                class="btn btn-sm btn-outline-secondary">Reset</a>
                        </div>
                    </form>

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
                                        <td colspan="7" class="text-center py-3 text-muted">No transactions found matching
                                            the
                                            selected filters.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <!-- ==========================================
         SECTION 5: SYSTEM ANALYTICS & REPORTS
         ========================================== -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">5. System Analytics & Reports</h5>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    
                    <!-- Card 1: Borrower Type Summary -->
                    <div class="col-md-4">
                        <div class="card border-primary h-100 shadow-sm">
                            <div class="card-header bg-primary text-white fw-bold">Borrower Type Summary</div>
                            <div class="card-body p-0">
                                <table class="table table-sm table-striped mb-0 align-middle">
                                    <thead class="table-light">
                                        <tr><th>Category</th><th class="text-end">Total Borrowers</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($borrowerTypeSummary as $bt): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($bt['borrower_type']) ?></td>
                                                <td class="text-end fw-bold"><?= (int)$bt['total_count'] ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($borrowerTypeSummary)): ?>
                                            <tr>
                                                <td colspan="2" class="text-center text-muted py-3">No data available.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Card 2: Transaction Status Summary -->
                    <div class="col-md-4">
                        <div class="card border-info h-100 shadow-sm">
                            <div class="card-header bg-info text-dark fw-bold">Transaction Status Summary</div>
                            <div class="card-body p-0">
                                <table class="table table-sm table-striped mb-0 align-middle">
                                    <thead class="table-light">
                                        <tr><th>Status</th><th class="text-end">Total Transactions</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($transactionStatusSummary as $ts): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($ts['status']) ?></td>
                                                <td class="text-end fw-bold"><?= (int)$ts['total_count'] ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($transactionStatusSummary)): ?>
                                            <tr><td colspan="2" class="text-center text-muted py-3">No data available.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Card 3: Grouped Borrowing Analysis (GROUP BY & HAVING >= 2) -->
                    <div class="col-md-4">
                        <div class="card border-success h-100 shadow-sm">
                            <div class="card-header bg-success text-white fw-bold">Grouped Borrowing Analysis (2+ Loans)</div>
                            <div class="card-body p-0">
                                <div class="table-responsive" style="max-height: 200px; overflow-y: auto;">
                                    <table class="table table-sm table-striped mb-0 align-middle">
                                        <thead class="table-light sticky-top">
                                            <tr><th>Borrower</th><th>Type</th><th class="text-end">Loans</th></tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($groupedBorrowingAnalysis as $gb): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($gb['full_name']) ?></td>
                                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($gb['borrower_type']) ?></span></td>
                                                    <td class="text-end fw-bold"><?= (int)$gb['transaction_count'] ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                            <?php if (empty($groupedBorrowingAnalysis)): ?>
                                                <tr><td colspan="3" class="text-center text-muted py-3">No borrowers with 2+ transactions found.</td></tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>
</body>

</html>