<?php
$currentPage = basename($_SERVER['PHP_SELF']);
require('scripts/reportsW7.php');
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Week 7 - Advanced Borrowing Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .table-container {
            max-height: 350px;
            overflow-y: auto;
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

    <!-- Navbar -->
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
                            href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($currentPage === 'week7.php') ? 'active' : '' ?>" href="week7.php">Week
                            7 Reports</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($currentPage === 'audit.php') ? 'active' : '' ?>" href="audit.php">Audit Logs</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4 px-4">
        <div class="mb-4">
            <h2 class="text-primary mb-0">Week 7 Reports</h2>
        </div>

        <div class="mb-4">
            <ul class="nav nav-tabs" id="reportTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="report1-tab" data-bs-toggle="tab" data-bs-target="#report1-tab-pane" type="button" role="tab" aria-controls="report1-tab-pane" aria-selected="true">Complete Borrowing</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="report2-tab" data-bs-toggle="tab" data-bs-target="#report2-tab-pane" type="button" role="tab" aria-controls="report2-tab-pane" aria-selected="false">Equipment Summary</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="report3-tab" data-bs-toggle="tab" data-bs-target="#report3-tab-pane" type="button" role="tab" aria-controls="report3-tab-pane" aria-selected="false">Borrowers Without Tx</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="report4-tab" data-bs-toggle="tab" data-bs-target="#report4-tab-pane" type="button" role="tab" aria-controls="report4-tab-pane" aria-selected="false">Unborrowed Equipment</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="report6-tab" data-bs-toggle="tab" data-bs-target="#report6-tab-pane" type="button" role="tab" aria-controls="report6-tab-pane" aria-selected="false">Monthly Borrowing</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="report5-tab" data-bs-toggle="tab" data-bs-target="#report5-tab-pane" type="button" role="tab" aria-controls="report5-tab-pane" aria-selected="false">Combined Activity</button>
                </li>
            </ul>
        </div>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="report1-tab-pane" role="tabpanel" aria-labelledby="report1-tab">
                <div class="row g-4">
                    <div class="col-12">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Complete Borrowing Report</h5>
                            </div>
                            <div class="card-body">
                                <form method="GET" class="row g-2 mb-3 align-items-center">
                                    <input type="hidden" name="report1_page" value="1">
                                    <div class="col-md-4">
                                        <input type="text" name="report1_search" id="searchReport1" class="form-control form-control-sm" placeholder="Search report..." value="<?= htmlspecialchars($report1Search) ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <select name="report1_status" id="filterStatus1" class="form-select form-select-sm">
                                            <option value="">All Statuses</option>
                                            <option value="Active" <?= ($report1Status === 'Active') ? 'selected' : '' ?>>Active</option>
                                            <option value="Returned" <?= ($report1Status === 'Returned') ? 'selected' : '' ?>>Returned</option>
                                            <option value="Overdue" <?= ($report1Status === 'Overdue') ? 'selected' : '' ?>>Overdue</option>
                                            <option value="Cancelled" <?= ($report1Status === 'Cancelled') ? 'selected' : '' ?>>Cancelled</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <select name="report1_sort" id="sortColumn1" class="form-select form-select-sm">
                                            <option value="borrower_no" <?= ($report1Sort === 'borrower_no') ? 'selected' : '' ?>>ID #</option>
                                            <option value="full_name" <?= ($report1Sort === 'full_name') ? 'selected' : '' ?>>Borrower Name</option>
                                            <option value="equipment_name" <?= ($report1Sort === 'equipment_name') ? 'selected' : '' ?>>Equipment</option>
                                            <option value="quantity" <?= ($report1Sort === 'quantity') ? 'selected' : '' ?>>Qty</option>
                                            <option value="borrow_date" <?= ($report1Sort === 'borrow_date') ? 'selected' : '' ?>>Borrow Date</option>
                                            <option value="due_date" <?= ($report1Sort === 'due_date') ? 'selected' : '' ?>>Due Date</option>
                                            <option value="status" <?= ($report1Sort === 'status') ? 'selected' : '' ?>>Status</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <select name="report1_order" id="sortDirection1" class="form-select form-select-sm">
                                            <option value="ASC" <?= ($report1Order === 'ASC') ? 'selected' : '' ?>>ASC</option>
                                            <option value="DESC" <?= ($report1Order === 'DESC') ? 'selected' : '' ?>>DESC</option>
                                        </select>
                                    </div>
                                    <div class="col-md-1 d-flex gap-1">
                                        <button type="submit" class="btn btn-sm btn-primary w-100">Apply</button>
                                    </div>
                                    <div class="col-12 d-flex justify-content-end">
                                        <a href="week7.php#report1" class="btn btn-sm btn-outline-secondary">Reset</a>
                                    </div>
                                </form>

                                <div class="table-responsive table-container border rounded">
                                    <table class="table table-sm table-hover align-middle mb-0" id="tableReport1">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>ID #</th>
                                                <th>Borrower Name</th>
                                                <th>Equipment</th>
                                                <th>Qty</th>
                                                <th>Borrow Date</th>
                                                <th>Due Date</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($completeBorrowing as $r): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($r['borrower_no']) ?></td>
                                                    <td><?= htmlspecialchars($r['full_name']) ?></td>
                                                    <td><?= htmlspecialchars($r['equipment_name']) ?></td>
                                                    <td class="fw-bold"><?= (int) $r['quantity'] ?></td>
                                                    <td><?= htmlspecialchars($r['borrow_date']) ?></td>
                                                    <td><?= htmlspecialchars($r['due_date']) ?></td>
                                                    <td><span class="badge bg-secondary status-badge"><?= htmlspecialchars($r['status']) ?></span></td>
                                                </tr>
                                            <?php endforeach; ?>
                                            <?php if (empty($completeBorrowing)): ?>
                                                <tr>
                                                    <td colspan="7" class="text-center py-3 text-muted">No borrowing history found.</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?= renderPagination($report1Page, $report1TotalPages, 'report1_page', $_GET) ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="report2-tab-pane" role="tabpanel" aria-labelledby="report2-tab">
                <div class="row g-4">
                    <div class="col-12">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0">Equipment Borrowing Summary</h5>
                            </div>
                            <div class="card-body">
                                <form method="GET" class="row g-2 mb-3 align-items-end">
                                    <input type="hidden" name="report2_page" value="1">
                                    <div class="col-md-10">
                                        <label class="form-label small mb-1">Search Equipment or Property Number</label>
                                        <input type="text" name="report2_search" class="form-control form-control-sm" placeholder="Search equipment or property number..." value="<?= htmlspecialchars($report2Search) ?>">
                                    </div>
                                    <div class="col-md-2 d-flex gap-2">
                                        <button type="submit" class="btn btn-sm btn-success w-100">Apply</button>
                                        <a href="week7.php#report2" class="btn btn-sm btn-outline-secondary">Reset</a>
                                    </div>
                                </form>
                                <div class="table-responsive table-container border rounded">
                                    <table class="table table-sm table-hover align-middle mb-0" id="tableReport2">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Property #</th>
                                                <th>Equipment</th>
                                                <th>Total Qty</th>
                                                <th>Tx Count</th>
                                                <th>Avg</th>
                                                <th>Min</th>
                                                <th>Max</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($equipmentSummary as $r): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($r['property_no']) ?></td>
                                                    <td><?= htmlspecialchars($r['equipment_name']) ?></td>
                                                    <td class="fw-bold text-success"><?= (int) $r['total_qty_borrowed'] ?></td>
                                                    <td><?= (int) $r['transaction_count'] ?></td>
                                                    <td><?= $r['avg_qty'] ?></td>
                                                    <td><?= (int) $r['min_qty'] ?></td>
                                                    <td><?= (int) $r['max_qty'] ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                            <?php if (empty($equipmentSummary)): ?>
                                                <tr>
                                                    <td colspan="7" class="text-center py-3 text-muted">No equipment summary available.</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?= renderPagination($report2Page, $report2TotalPages, 'report2_page', $_GET) ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="report3-tab-pane" role="tabpanel" aria-labelledby="report3-tab">
                <div class="row g-4">
                    <div class="col-12">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-warning text-dark">
                                <h5 class="mb-0">Borrowers Without Transactions</h5>
                            </div>
                            <div class="card-body">
                                <form method="GET" class="row g-2 mb-3">
                                    <input type="hidden" name="txless_page" value="1">
                                    <div class="col-md-8">
                                        <input type="text" name="txless_search" id="searchReport3" class="form-control form-control-sm" placeholder="Search borrower..." value="<?= htmlspecialchars($txlessSearch) ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <select name="txless_type" id="filterType3" class="form-select form-select-sm">
                                            <option value="">All Types</option>
                                            <option value="Student" <?= ($txlessType === 'Student') ? 'selected' : '' ?>>Student</option>
                                            <option value="Faculty" <?= ($txlessType === 'Faculty') ? 'selected' : '' ?>>Faculty</option>
                                            <option value="Staff" <?= ($txlessType === 'Staff') ? 'selected' : '' ?>>Staff</option>
                                        </select>
                                    </div>
                                    <div class="col-12 d-flex justify-content-end gap-2">
                                        <button type="submit" class="btn btn-sm btn-warning text-dark">Apply</button>
                                        <a href="week7.php#report3" class="btn btn-sm btn-outline-secondary">Reset</a>
                                    </div>
                                </form>
                                <div class="table-responsive table-container border rounded">
                                    <table class="table table-sm table-hover align-middle mb-0" id="tableReport3">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Borrower ID</th>
                                                <th>Full Name</th>
                                                <th>Type</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($borrowersWithoutTx as $r): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($r['borrower_no']) ?></td>
                                                    <td><?= htmlspecialchars($r['full_name']) ?></td>
                                                    <td><span class="badge bg-secondary type-badge"><?= htmlspecialchars($r['borrower_type']) ?></span></td>
                                                </tr>
                                            <?php endforeach; ?>
                                            <?php if (empty($borrowersWithoutTx)): ?>
                                                <tr>
                                                    <td colspan="3" class="text-center py-3 text-muted">All registered borrowers have borrowing history.</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?= renderPagination($txlessPage, $txlessTotalPages, 'txless_page', $_GET) ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="report4-tab-pane" role="tabpanel" aria-labelledby="report4-tab">
                <div class="row g-4">
                    <div class="col-12">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-danger text-white">
                                <h5 class="mb-0">Equipment Without Borrowing History</h5>
                            </div>
                            <div class="card-body">
                                <form method="GET" class="row g-2 mb-3 align-items-end">
                                    <input type="hidden" name="report4_page" value="1">
                                    <div class="col-md-10">
                                        <label class="form-label small mb-1">Search Unborrowed Equipment</label>
                                        <input type="text" name="report4_search" class="form-control form-control-sm" placeholder="Search unborrowed equipment..." value="<?= htmlspecialchars($report4Search) ?>">
                                    </div>
                                    <div class="col-md-2 d-flex gap-2">
                                        <button type="submit" class="btn btn-sm btn-danger w-100">Apply</button>
                                        <a href="week7.php#report4" class="btn btn-sm btn-outline-secondary">Reset</a>
                                    </div>
                                </form>
                                <div class="table-responsive table-container border rounded">
                                    <table class="table table-sm table-hover align-middle mb-0" id="tableReport4">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Property #</th>
                                                <th>Equipment Name</th>
                                                <th>Stock Available</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($equipmentWithoutHistory as $r): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($r['property_no']) ?></td>
                                                    <td><?= htmlspecialchars($r['equipment_name']) ?></td>
                                                    <td class="fw-bold"><?= (int) $r['quantity_available'] ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                            <?php if (empty($equipmentWithoutHistory)): ?>
                                                <tr>
                                                    <td colspan="3" class="text-center py-3 text-muted">All equipment assets have been borrowed at least once.</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?= renderPagination($report4Page, $report4TotalPages, 'report4_page', $_GET) ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="report6-tab-pane" role="tabpanel" aria-labelledby="report6-tab">
                <div class="row g-4">
                    <div class="col-12">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-secondary text-white">
                                <h5 class="mb-0">Monthly Borrowing Report</h5>
                            </div>
                            <div class="card-body">
                                <form method="GET" class="row g-2 mb-3 align-items-end">
                                    <input type="hidden" name="monthly_page" value="1">
                                    <div class="col-md-10">
                                        <label class="form-label small mb-1">Search Month or Year</label>
                                        <input type="text" name="monthly_search" class="form-control form-control-sm" placeholder="Search month or year..." value="<?= htmlspecialchars($monthlySearch) ?>">
                                    </div>
                                    <div class="col-md-2 d-flex gap-2">
                                        <button type="submit" class="btn btn-sm btn-secondary w-100">Apply</button>
                                        <a href="week7.php#report6" class="btn btn-sm btn-outline-secondary">Reset</a>
                                    </div>
                                </form>
                                <div class="table-responsive table-container border rounded">
                                    <table class="table table-sm table-hover align-middle mb-0" id="tableReport6">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Month & Year</th>
                                                <th class="text-center">Total Transactions</th>
                                                <th class="text-end">Total Items Borrowed</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($monthlyReport as $m): ?>
                                                <tr>
                                                    <td class="fw-bold"><?= htmlspecialchars($m['readable_month']) ?></td>
                                                    <td class="text-center"><?= (int) $m['total_transactions'] ?></td>
                                                    <td class="text-end fw-bold text-info"><?= (int) $m['total_items_borrowed'] ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                            <?php if (empty($monthlyReport)): ?>
                                                <tr>
                                                    <td colspan="3" class="text-center py-3 text-muted">No monthly transaction records available.</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?= renderPagination($monthlyPage, $monthlyTotalPages, 'monthly_page', $_GET) ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="report5-tab-pane" role="tabpanel" aria-labelledby="report5-tab">
                <div class="row g-4">
                    <div class="col-12">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-info text-dark">
                                <h5 class="mb-0">Combined Borrower Activity</h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3 mb-3">
                                    <div class="col-12">
                                        <form method="GET" class="row g-2 align-items-center">
                                            <input type="hidden" name="union_page" value="1">
                                            <div class="col-12">
                                                <label class="form-label small mb-1">UNION Search</label>
                                                <input type="text" name="union_search" id="searchReport5Union" class="form-control form-control-sm" placeholder="Search union logs..." value="<?= htmlspecialchars($unionSearch) ?>">
                                            </div>
                                            <div class="col-4">
                                                <label class="form-label small mb-1">Filter</label>
                                                <select name="union_filter" class="form-select form-select-sm">
                                                    <option value="" <?= ($unionFilter === '') ? 'selected' : '' ?>>All</option>
                                                    <option value="Active Borrower" <?= ($unionFilter === 'Active Borrower') ? 'selected' : '' ?>>Active Borrower</option>
                                                    <option value="Registered Member" <?= ($unionFilter === 'Registered Member') ? 'selected' : '' ?>>Registered Member</option>
                                                </select>
                                            </div>
                                            <div class="col-4">
                                                <label class="form-label small mb-1">Sort by</label>
                                                <select name="union_sort" class="form-select form-select-sm">
                                                    <option value="classification" <?= ($unionSort === 'classification') ? 'selected' : '' ?>>Classification</option>
                                                    <option value="full_name" <?= ($unionSort === 'full_name') ? 'selected' : '' ?>>Member Name</option>
                                                </select>
                                            </div>
                                            <div class="col-4">
                                                <label class="form-label small mb-1">Order</label>
                                                <select name="union_order" class="form-select form-select-sm">
                                                    <option value="ASC" <?= ($unionOrder === 'ASC') ? 'selected' : '' ?>>Ascending</option>
                                                    <option value="DESC" <?= ($unionOrder === 'DESC') ? 'selected' : '' ?>>Descending</option>
                                                </select>
                                            </div>
                                            <div class="col-12 d-flex justify-content-end gap-2">
                                                <button type="submit" class="btn btn-sm btn-info text-dark">Apply</button>
                                                <a href="week7.php#report5" class="btn btn-sm btn-outline-secondary">Reset</a>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                <p class="text-muted small mb-2">UNION Report:</p>
                                <div class="table-responsive border rounded mb-3" style="max-height: 220px;">
                                    <table class="table table-sm mb-0" id="tableReport5Union">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Classification</th>
                                                <th>Member Name</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($unionReport as $u): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($u['classification']) ?></td>
                                                    <td><?= htmlspecialchars($u['full_name']) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                            <?php if (empty($unionReport)): ?>
                                                <tr>
                                                    <td colspan="2" class="text-center py-3 text-muted">No union results found.</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?= renderPagination($unionPage, $unionTotalPages, 'union_page', $_GET) ?>

                                <div class="col-12 mt-4">
                                    <form method="GET" class="row g-2 align-items-center">
                                        <input type="hidden" name="union_all_page" value="1">
                                        <div class="col-12">
                                            <label class="form-label small mb-1">UNION ALL Search</label>
                                            <input type="text" name="union_all_search" id="searchReport5UnionAll" class="form-control form-control-sm" placeholder="Search union all logs..." value="<?= htmlspecialchars($unionAllSearch) ?>">
                                        </div>
                                        <div class="col-4">
                                            <label class="form-label small mb-1">Filter</label>
                                            <select name="union_all_filter" class="form-select form-select-sm">
                                                <option value="" <?= ($unionAllFilter === '') ? 'selected' : '' ?>>All</option>
                                                <option value="Student Account" <?= ($unionAllFilter === 'Student Account') ? 'selected' : '' ?>>Student Account</option>
                                                <option value="Faculty / Staff Account" <?= ($unionAllFilter === 'Faculty / Staff Account') ? 'selected' : '' ?>>Faculty / Staff Account</option>
                                            </select>
                                        </div>
                                        <div class="col-4">
                                            <label class="form-label small mb-1">Sort by</label>
                                            <select name="union_all_sort" class="form-select form-select-sm">
                                                <option value="user_tier" <?= ($unionAllSort === 'user_tier') ? 'selected' : '' ?>>User Tier</option>
                                                <option value="full_name" <?= ($unionAllSort === 'full_name') ? 'selected' : '' ?>>Member Name</option>
                                            </select>
                                        </div>
                                        <div class="col-4">
                                            <label class="form-label small mb-1">Order</label>
                                            <select name="union_all_order" class="form-select form-select-sm">
                                                <option value="ASC" <?= ($unionAllOrder === 'ASC') ? 'selected' : '' ?>>Ascending</option>
                                                <option value="DESC" <?= ($unionAllOrder === 'DESC') ? 'selected' : '' ?>>Descending</option>
                                            </select>
                                        </div>
                                        <div class="col-12 d-flex justify-content-end gap-2">
                                            <button type="submit" class="btn btn-sm btn-info text-dark">Apply</button>
                                            <a href="week7.php#report5" class="btn btn-sm btn-outline-secondary">Reset</a>
                                        </div>
                                    </form>
                                </div>
                                <p class="text-muted small mb-2 mt-4">UNION ALL Report:</p>
                                <div class="table-responsive border rounded" style="max-height: 220px;">
                                    <table class="table table-sm mb-0" id="tableReport5UnionAll">
                                        <thead class="table-light">
                                            <tr>
                                                <th>User Tier</th>
                                                <th>Member Name</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($unionAllReport as $ua): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($ua['user_tier']) ?></td>
                                                    <td><?= htmlspecialchars($ua['full_name']) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                            <?php if (empty($unionAllReport)): ?>
                                                <tr>
                                                    <td colspan="2" class="text-center py-3 text-muted">No union-all results found.</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?= renderPagination($unionAllPage, $unionAllTotalPages, 'union_all_page', $_GET) ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const storageKey = "week7ActiveTab";
            const currentTab = localStorage.getItem(storageKey);
            if (currentTab) {
                const trigger = document.querySelector(`button[data-bs-toggle="tab"][data-bs-target="${currentTab}"]`);
                if (trigger) {
                    const tab = new bootstrap.Tab(trigger);
                    tab.show();
                }
            }

            document.querySelectorAll('button[data-bs-toggle="tab"]').forEach(function (tabEl) {
                tabEl.addEventListener("shown.bs.tab", function (event) {
                    localStorage.setItem(storageKey, event.target.getAttribute("data-bs-target"));
                });
            });

            function setupSimpleSearch(inputId, tableId) {
                const searchInput = document.getElementById(inputId);
                const table = document.getElementById(tableId);
                if (!searchInput || !table) return;

                searchInput.addEventListener("input", function () {
                    const query = this.value.toLowerCase().trim();
                    table.querySelectorAll("tbody tr").forEach(row => {
                        row.style.display = row.innerText.toLowerCase().includes(query) ? "" : "none";
                    });
                });
            }

            
        });
    </script>
</body>

</html>