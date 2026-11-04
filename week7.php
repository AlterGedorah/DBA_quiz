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
    <?php $currentPage = basename($_SERVER['PHP_SELF']); ?>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark border-bottom border-body sticky-top mb-4 shadow-sm">
      <div class="container-fluid px-4">
        <a class="navbar-brand fw-bold text-primary" href="index.php">Equipment System</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
          <ul class="navbar-nav me-auto">
            <li class="nav-item">
              <a class="nav-link <?= ($currentPage === 'index.php') ? 'active' : '' ?>" href="index.php">Home</a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?= ($currentPage === 'reportsW7.php') ? 'active' : '' ?>" href="reportsW7.php">Week 7 Reports</a>
            </li>
          </ul>
        </div>
      </div>
    </nav>

    <div class="container-fluid py-4 px-4">
        <div class="mb-4">
            <h2 class="text-primary mb-0">Week 7</h2>
        </div>

        <div class="row g-4">
            
            <!-- 1. COMPLETE BORROWING REPORT -->
            <div class="col-12">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">1. Complete Borrowing Report (Multiple Joins)</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive table-container border rounded m-3">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="table-dark">
                                    <tr><th>ID #</th><th>Borrower Name</th><th>Equipment</th><th>Qty</th><th>Borrow Date</th><th>Due Date</th><th>Status</th></tr>
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
                                            <td><span class="badge bg-secondary"><?= htmlspecialchars($r['status']) ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($completeBorrowing)): ?>
                                            <tr><td colspan="7" class="text-center py-3 text-muted">No borrowing history found.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. EQUIPMENT BORROWING SUMMARY -->
            <div class="col-12">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">2. Equipment Borrowing Summary (Aggregates)</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive table-container border rounded m-3">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="table-dark">
                                    <tr><th>Property #</th><th>Equipment</th><th>Total Qty</th><th>Tx Count</th><th>Avg</th><th>Min</th><th>Max</th></tr>
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
                                            <tr><td colspan="7" class="text-center py-3 text-muted">No equipment summary available.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. BORROWERS WITHOUT TRANSACTIONS -->
            <div class="col-12">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">3. Borrowers Without Transactions (LEFT JOIN)</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive table-container border rounded m-3">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="table-dark">
                                    <tr><th>Borrower ID</th><th>Full Name</th><th>Type</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($borrowersWithoutTx as $r): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($r['borrower_no']) ?></td>
                                            <td><?= htmlspecialchars($r['full_name']) ?></td>
                                            <td><span class="badge bg-secondary"><?= htmlspecialchars($r['borrower_type']) ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($borrowersWithoutTx)): ?>
                                            <tr><td colspan="3" class="text-center py-3 text-muted">All registered borrowers have borrowing history.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. EQUIPMENT WITHOUT BORROWING HISTORY -->
            <div class="col-12">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0">4. Equipment Without Borrowing History</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive table-container border rounded m-3">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="table-dark">
                                    <tr><th>Property #</th><th>Equipment Name</th><th>Stock Available</th></tr>
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
                                            <tr><td colspan="3" class="text-center py-3 text-muted">All equipment assets have been borrowed at least once.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 5. COMBINED BORROWER ACTIVITY (UNION & UNION ALL) -->
            <div class="col-12">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-info text-dark">
                        <h5 class="mb-0">5. Combined Borrower Activity (UNION & UNION ALL)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive border rounded mb-3" style="max-height: 220px;">
                            <table class="table table-sm mb-0">
                                <thead class="table-light"><tr><th>Classification</th><th>Member Name</th></tr></thead>
                                <tbody>
                                    <?php foreach ($unionReport as $u): ?>
                                        <tr><td><?= $u['classification'] ?></td><td><?= htmlspecialchars($u['full_name']) ?></td></tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="table-responsive border rounded" style="max-height: 220px;">
                            <table class="table table-sm mb-0">
                                <thead class="table-light"><tr><th>User Tier</th><th>Member Name</th></tr></thead>
                                <tbody>
                                    <?php foreach ($unionAllReport as $ua): ?>
                                        <tr><td><?= $ua['user_tier'] ?></td><td><?= htmlspecialchars($ua['full_name']) ?></td></tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 6. MONTHLY BORROWING REPORT -->
            <div class="col-12">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0">6. Monthly Borrowing Report</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive table-container border rounded m-3">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="table-dark">
                                    <tr><th>Month & Year</th><th class="text-center">Total Transactions</th><th class="text-end">Total Items Borrowed</th></tr>
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
                                            <tr><td colspan="3" class="text-center py-3 text-muted">No monthly transaction records available.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>