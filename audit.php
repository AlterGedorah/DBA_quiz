<?php
require('scripts/dbconn.php');
require('scripts/auditLogs.php');
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrow Items Audit Logs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .table-container {
            max-height: 420px;
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
                        <a class="nav-link <?= ($currentPage === 'week7.php') ? 'active' : '' ?>" href="week7.php">Reports</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($currentPage === 'audit.php') ? 'active' : '' ?>" href="audit.php">Audit Logs</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4 pb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">Borrow Items Audit Trail</h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-2 mb-3 align-items-end">
                    <input type="hidden" name="audit_page" value="1">
                    <div class="col-md-5">
                        <label class="form-label small">Search Borrow ID, Equipment ID, Borrower, Equipment</label>
                        <input type="text" name="audit_search" class="form-control form-control-sm"
                            value="<?= htmlspecialchars($auditSearch) ?>" placeholder="Search logs...">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Operation</label>
                        <select name="audit_operation" class="form-select form-select-sm">
                            <option value="" <?= ($auditOperation === '') ? 'selected' : '' ?>>All</option>
                            <option value="INSERT" <?= ($auditOperation === 'INSERT') ? 'selected' : '' ?>>INSERT</option>
                            <option value="UPDATE" <?= ($auditOperation === 'UPDATE') ? 'selected' : '' ?>>UPDATE</option>
                            <option value="DELETE" <?= ($auditOperation === 'DELETE') ? 'selected' : '' ?>>DELETE</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Sort By</label>
                        <select name="audit_sort" class="form-select form-select-sm">
                            <option value="action_timestamp" <?= ($auditSort === 'action_timestamp') ? 'selected' : '' ?>>Timestamp</option>
                            <option value="audit_id" <?= ($auditSort === 'audit_id') ? 'selected' : '' ?>>Audit ID</option>
                            <option value="borrow_id" <?= ($auditSort === 'borrow_id') ? 'selected' : '' ?>>Borrow ID</option>
                            <option value="equipment_id" <?= ($auditSort === 'equipment_id') ? 'selected' : '' ?>>Equipment ID</option>
                            <option value="operation_type" <?= ($auditSort === 'operation_type') ? 'selected' : '' ?>>Operation</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <label class="form-label small">Order</label>
                        <select name="audit_order" class="form-select form-select-sm">
                            <option value="ASC" <?= ($auditOrder === 'ASC') ? 'selected' : '' ?>>ASC</option>
                            <option value="DESC" <?= ($auditOrder === 'DESC') ? 'selected' : '' ?>>DESC</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-primary w-100">Apply</button>
                        <a href="audit.php" class="btn btn-sm btn-outline-secondary">Reset</a>
                    </div>
                </form>

                <div class="table-responsive table-container border rounded">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Audit ID</th>
                                <th>Borrow ID</th>
                                <th>Equipment ID</th>
                                <th>Borrower</th>
                                <th>Equipment</th>
                                <th>Operation</th>
                                <th>Old Qty</th>
                                <th>New Qty</th>
                                <th>Timestamp</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($auditLogs as $log): ?>
                                <tr>
                                    <td><?= (int) $log['audit_id'] ?></td>
                                    <td><?= (int) $log['borrow_id'] ?></td>
                                    <td><?= (int) $log['equipment_id'] ?></td>
                                    <td><?= htmlspecialchars($log['borrower_name']) ?></td>
                                    <td><?= htmlspecialchars($log['equipment_name']) ?></td>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($log['operation_type']) ?></span></td>
                                    <td><?= $log['old_quantity'] !== null ? (int) $log['old_quantity'] : '-' ?></td>
                                    <td><?= $log['new_quantity'] !== null ? (int) $log['new_quantity'] : '-' ?></td>
                                    <td><?= htmlspecialchars($log['action_timestamp']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($auditLogs)): ?>
                                <tr>
                                    <td colspan="9" class="text-center py-3 text-muted">No audit records found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?= renderPagination($auditPage, $auditTotalPages, 'audit_page', $_GET) ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
