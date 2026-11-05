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
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4 px-4">
        <div class="mb-4">
            <h2 class="text-primary mb-0">Week 7 Reports</h2>
        </div>

        <div class="row g-4">
            <!-- 1. COMPLETE BORROWING REPORT -->
            <div class="col-12">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Complete Borrowing Report</h5>
                    </div>
                    <div class="card-body">
                        <!-- Search, Status Filter, Sort Column, Order, and Reset Controls -->
                        <div class="row g-2 mb-3 align-items-center">
                            <div class="col-md-4">
                                <input type="text" id="searchReport1" class="form-control form-control-sm"
                                    placeholder="Search report...">
                            </div>
                            <div class="col-md-3">
                                <select id="filterStatus1" class="form-select form-select-sm">
                                    <option value="">All Statuses</option>
                                    <option value="Active">Active</option>
                                    <option value="Returned">Returned</option>
                                    <option value="Overdue">Overdue</option>
                                    <option value="Cancelled">Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select id="sortColumn1" class="form-select form-select-sm">
                                    <option value="1" selected>Borrower Name</option>
                                    <option value="2">Equipment</option>
                                    <option value="3">Qty</option>
                                    <option value="4">Borrow Date</option>
                                    <option value="5">Due Date</option>
                                    <option value="6">Status</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select id="sortDirection1" class="form-select form-select-sm">
                                    <option value="asc">ASC</option>
                                    <option value="desc">DESC</option>
                                </select>
                            </div>
                            <div class="col-md-1">
                                <button type="button" id="resetReport1"
                                    class="btn btn-sm btn-outline-secondary w-100">Reset</button>
                            </div>
                        </div>

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
                                            <td><span
                                                    class="badge bg-secondary status-badge"><?= htmlspecialchars($r['status']) ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($completeBorrowing)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-3 text-muted">No borrowing history found.
                                            </td>
                                        </tr>
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
                        <h5 class="mb-0">Equipment Borrowing Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-2 mb-3">
                            <div class="col-md-12">
                                <input type="text" id="searchReport2" class="form-control form-control-sm"
                                    placeholder="Search equipment or property number...">
                            </div>
                        </div>

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
                                            <td colspan="7" class="text-center py-3 text-muted">No equipment summary
                                                available.</td>
                                        </tr>
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
                        <h5 class="mb-0">Borrowers Without Transactions</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-2 mb-3">
                            <div class="col-md-8">
                                <input type="text" id="searchReport3" class="form-control form-control-sm"
                                    placeholder="Search borrower...">
                            </div>
                            <div class="col-md-4">
                                <select id="filterType3" class="form-select form-select-sm">
                                    <option value="">All Types</option>
                                    <option value="Student">Student</option>
                                    <option value="Faculty">Faculty</option>
                                    <option value="Staff">Staff</option>
                                </select>
                            </div>
                        </div>

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
                                            <td><span
                                                    class="badge bg-secondary type-badge"><?= htmlspecialchars($r['borrower_type']) ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($borrowersWithoutTx)): ?>
                                        <tr>
                                            <td colspan="3" class="text-center py-3 text-muted">All registered borrowers
                                                have borrowing history.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. EQUIPMENT WITHOUT BORROWING HISTORY -->
            <div class="col-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0">Equipment Without Borrowing History</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-2 mb-3">
                            <div class="col-md-12">
                                <input type="text" id="searchReport4" class="form-control form-control-sm"
                                    placeholder="Search unborrowed equipment...">
                            </div>
                        </div>

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
                                            <td colspan="3" class="text-center py-3 text-muted">All equipment assets have
                                                been borrowed at least once.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0">Monthly Borrowing Report</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-2 mb-3">
                            <div class="col-md-12">
                                <input type="text" id="searchReport6" class="form-control form-control-sm"
                                    placeholder="Search month or year...">
                            </div>
                        </div>

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
                                            <td class="fw-bold">
                                                <?= htmlspecialchars($m['readable_month']) ?>
                                            </td>
                                            <td class="text-center">
                                                <?= (int) $m['total_transactions'] ?>
                                            </td>
                                            <td class="text-end fw-bold text-info">
                                                <?= (int) $m['total_items_borrowed'] ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($monthlyReport)): ?>
                                        <tr>
                                            <td colspan="3" class="text-center py-3 text-muted">No monthly transaction
                                                records
                                                available.</td>
                                        </tr>
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
                        <h5 class="mb-0">Combined Borrower Activity</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-2 mb-3">
                            <div class="col-md-12">
                                <input type="text" id="searchReport5" class="form-control form-control-sm"
                                    placeholder="Search union logs...">
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
                                            <td><?= $u['classification'] ?></td>
                                            <td><?= htmlspecialchars($u['full_name']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <p class="text-muted small mb-2">UNION ALL Report:</p>
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
                                            <td><?= $ua['user_tier'] ?></td>
                                            <td><?= htmlspecialchars($ua['full_name']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 6. MONTHLY BORROWING REPORT -->


        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // General Search & Filter Handler for Reports 2, 4, 6
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

            setupSimpleSearch("searchReport2", "tableReport2");
            setupSimpleSearch("searchReport4", "tableReport4");
            setupSimpleSearch("searchReport6", "tableReport6");

            // Report 1: Search + Status Dropdown Filter
            const search1 = document.getElementById("searchReport1");
            const filterStatus1 = document.getElementById("filterStatus1");
            const table1 = document.getElementById("tableReport1");

            function filterReport1() {
                if (!table1) return;
                const query = search1 ? search1.value.toLowerCase().trim() : "";
                const statusVal = filterStatus1 ? filterStatus1.value.toLowerCase() : "";

                table1.querySelectorAll("tbody tr").forEach(row => {
                    const text = row.innerText.toLowerCase();
                    const statusBadge = row.querySelector(".status-badge");
                    const statusText = statusBadge ? statusBadge.innerText.toLowerCase() : "";

                    const matchesSearch = text.includes(query);
                    const matchesStatus = !statusVal || statusText === statusVal;

                    row.style.display = matchesSearch && matchesStatus ? "" : "none";
                });
            }
            if (search1) search1.addEventListener("input", filterReport1);
            if (filterStatus1) filterStatus1.addEventListener("change", filterReport1);

            // Report 3: Search + Borrower Type Dropdown Filter
            const search3 = document.getElementById("searchReport3");
            const filterType3 = document.getElementById("filterType3");
            const table3 = document.getElementById("tableReport3");

            function filterReport3() {
                if (!table3) return;
                const query = search3 ? search3.value.toLowerCase().trim() : "";
                const typeVal = filterType3 ? filterType3.value.toLowerCase() : "";

                table3.querySelectorAll("tbody tr").forEach(row => {
                    const text = row.innerText.toLowerCase();
                    const typeBadge = row.querySelector(".type-badge");
                    const typeText = typeBadge ? typeBadge.innerText.toLowerCase() : "";

                    const matchesSearch = text.includes(query);
                    const matchesType = !typeVal || typeText === typeVal;

                    row.style.display = matchesSearch && matchesType ? "" : "none";
                });
            }
            if (search3) search3.addEventListener("input", filterReport3);
            if (filterType3) filterType3.addEventListener("change", filterReport3);

            // Report 5: Combined Union & Union All Search
            const unionSearch = document.getElementById("searchReport5");
            if (unionSearch) {
                unionSearch.addEventListener("input", function () {
                    const query = this.value.toLowerCase().trim();
                    ["tableReport5Union", "tableReport5UnionAll"].forEach(tId => {
                        const tbl = document.getElementById(tId);
                        if (tbl) {
                            tbl.querySelectorAll("tbody tr").forEach(row => {
                                row.style.display = row.innerText.toLowerCase().includes(query) ? "" : "none";
                            });
                        }
                    });
                });
            }
        });
        document.addEventListener("DOMContentLoaded", function () {
            const search1 = document.getElementById("searchReport1");
            const filterStatus1 = document.getElementById("filterStatus1");
            const sortCol1 = document.getElementById("sortColumn1");
            const sortDir1 = document.getElementById("sortDirection1");
            const reset1 = document.getElementById("resetReport1");
            const table1 = document.getElementById("tableReport1");

            function filterAndSortReport1() {
                if (!table1) return;
                const query = search1 ? search1.value.toLowerCase().trim() : "";
                const statusVal = filterStatus1 ? filterStatus1.value.toLowerCase() : "";
                const colIndex = sortCol1 ? parseInt(sortCol1.value) : 1;
                const direction = sortDir1 ? sortDir1.value : "asc";

                const tbody = table1.querySelector("tbody");
                let rows = Array.from(tbody.querySelectorAll("tr"));

                // 1. Filter rows
                rows.forEach(row => {
                    if (row.cells.length <= 1) return; // Skip empty message rows
                    const text = row.innerText.toLowerCase();
                    const statusBadge = row.querySelector(".status-badge");
                    const statusText = statusBadge ? statusBadge.innerText.toLowerCase() : "";

                    const matchesSearch = text.includes(query);
                    const matchesStatus = !statusVal || statusText === statusVal;

                    row.style.display = matchesSearch && matchesStatus ? "" : "none";
                });

                // 2. Sort rows based on dropdown selection
                rows.sort((rowA, rowB) => {
                    if (rowA.cells.length <= colIndex || rowB.cells.length <= colIndex) return 0;
                    const valA = rowA.cells[colIndex].innerText.trim().toLowerCase();
                    const valB = rowB.cells[colIndex].innerText.trim().toLowerCase();

                    const numA = parseFloat(valA);
                    const numB = parseFloat(valB);

                    let comparison = 0;
                    if (!isNaN(numA) && !isNaN(numB)) {
                        comparison = numA - numB;
                    } else {
                        comparison = valA.localeCompare(valB, undefined, { numeric: true, sensitivity: "base" });
                    }

                    return direction === "asc" ? comparison : -comparison;
                });

                // Re-append sorted rows
                rows.forEach(row => tbody.appendChild(row));
            }

            // Attach event listeners
            if (search1) search1.addEventListener("input", filterAndSortReport1);
            if (filterStatus1) filterStatus1.addEventListener("change", filterAndSortReport1);
            if (sortCol1) sortCol1.addEventListener("change", filterAndSortReport1);
            if (sortDir1) sortDir1.addEventListener("change", filterAndSortReport1);

            // Reset button handler
            if (reset1) {
                reset1.addEventListener("click", function () {
                    if (search1) search1.value = "";
                    if (filterStatus1) filterStatus1.value = "";
                    if (sortCol1) sortCol1.value = "1";
                    if (sortDir1) sortDir1.value = "asc";
                    filterAndSortReport1();
                });
            }
        });
    </script>
</body>

</html>