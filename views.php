<?php
require('scripts/dbconn.php');
$currentPage = basename($_SERVER['PHP_SELF']);

function normalizeViewSortOrder($value, $default) {
    $value = strtoupper((string) $value);
    return in_array($value, ['ASC', 'DESC'], true) ? $value : $default;
}

function renderViewPagination($currentPage, $totalPages, $queryParams = []) {
    if ($totalPages <= 1) {
        return '';
    }

    $queryParams = is_array($queryParams) ? $queryParams : [];
    $currentPage = max(1, (int) $currentPage);
    $totalPages = max(1, (int) $totalPages);
    $currentPage = min($currentPage, $totalPages);

    $queryParams = array_filter($queryParams, static function ($key) {
        return $key !== 'view_page';
    }, ARRAY_FILTER_USE_KEY);

    $html = '<nav aria-label="Views pagination"><ul class="pagination pagination-sm justify-content-center mb-0 mt-3 align-items-center">';

    $prevParams = $queryParams;
    $prevParams['view_page'] = max(1, $currentPage - 1);
    $html .= '<li class="page-item ' . ($currentPage <= 1 ? 'disabled' : '') . '"><a class="page-link" href="?' . http_build_query($prevParams) . '">Previous</a></li>';

    $html .= '<li class="page-item"><form method="GET" class="d-flex align-items-center gap-2 px-2 mb-0" style="margin: 0;">';
    foreach ($queryParams as $key => $value) {
        $html .= '<input type="hidden" name="' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . '" value="' . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . '">';
    }
    $html .= '<input type="number" name="view_page" value="' . htmlspecialchars((string) $currentPage, ENT_QUOTES, 'UTF-8') . '" min="1" max="' . (int) $totalPages . '" class="form-control form-control-sm" style="width: 72px; text-align: center;">';
    $html .= '<span class="text-muted small">/ ' . (int) $totalPages . '</span>';
    $html .= '<button type="submit" class="btn btn-sm btn-outline-secondary">Go</button>';
    $html .= '</form></li>';

    $nextParams = $queryParams;
    $nextParams['view_page'] = min($totalPages, $currentPage + 1);
    $html .= '<li class="page-item ' . ($currentPage >= $totalPages ? 'disabled' : '') . '"><a class="page-link" href="?' . http_build_query($nextParams) . '">Next</a></li>';
    $html .= '</ul></nav>';

    return $html;
}

$perPage = 5;
$viewSearch = trim($_GET['view_search'] ?? '');
$viewSort = $_GET['view_sort'] ?? 'view_name';
$viewOrder = normalizeViewSortOrder($_GET['view_order'] ?? 'ASC', 'ASC');
$viewPage = max(1, (int) ($_GET['view_page'] ?? 1));

$allowedViewSort = ['view_name', 'view_sql'];
if (!in_array($viewSort, $allowedViewSort, true)) {
    $viewSort = 'view_name';
}

$viewDetails = [];
$viewError = null;

try {
    $countSql = "
        SELECT COUNT(*)
        FROM information_schema.views
        WHERE table_schema = 'public'
    ";
    $dataSql = "
        SELECT
            table_name AS view_name,
            pg_get_viewdef('public.' || table_name, true) AS view_sql
        FROM information_schema.views
        WHERE table_schema = 'public'
    ";
    $params = [];

    if ($viewSearch !== '') {
        $searchTerm = '%' . strtolower($viewSearch) . '%';
        $countSql .= ' AND (LOWER(table_name) LIKE :view_search OR LOWER(pg_get_viewdef(\'public.\' || table_name, true)) LIKE :view_search)';
        $dataSql .= ' AND (LOWER(table_name) LIKE :view_search OR LOWER(pg_get_viewdef(\'public.\' || table_name, true)) LIKE :view_search)';
        $params[':view_search'] = $searchTerm;
    }

    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalViews = (int) $countStmt->fetchColumn();
    $totalPages = max(1, (int) ceil($totalViews / $perPage));
    $viewPage = min($viewPage, $totalPages);

    $dataSql .= ' ORDER BY ' . $viewSort . ' ' . $viewOrder . ' LIMIT :limit OFFSET :offset';
    $dataStmt = $pdo->prepare($dataSql);
    foreach ($params as $key => $value) {
        $dataStmt->bindValue($key, $value);
    }
    $dataStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $dataStmt->bindValue(':offset', ($viewPage - 1) * $perPage, PDO::PARAM_INT);
    $dataStmt->execute();
    $viewRows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($viewRows as $view) {
        $name = $view['view_name'];
        $sql = $view['view_sql'];
        $dataRows = [];
        $dataError = null;

        try {
            $dataStmt = $pdo->query('SELECT * FROM "' . str_replace('"', '""', $name) . '" LIMIT 10');
            $dataRows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            $dataError = $e->getMessage();
        }

        $viewDetails[] = [
            'name' => $name,
            'sql' => $sql,
            'rows' => $dataRows,
            'data_error' => $dataError,
        ];
    }
} catch (Throwable $e) {
    $viewError = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Views</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        pre {
            white-space: pre-wrap;
            word-break: break-word;
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
                    <li class="nav-item">
                        <a class="nav-link <?= ($currentPage === 'views.php') ? 'active' : '' ?>" href="views.php">Views</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4 pb-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="text-primary mb-1">Database Views</h2>
                <p class="text-muted mb-0">Definitions from the current database schema.</p>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="row g-2 align-items-end">
                    <input type="hidden" name="view_page" value="1">
                    <div class="col-md-6">
                        <label class="form-label small mb-1">Search view name or SQL</label>
                        <input type="text" name="view_search" class="form-control form-control-sm" value="<?= htmlspecialchars($viewSearch) ?>" placeholder="Search views...">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-1">Sort by</label>
                        <select name="view_sort" class="form-select form-select-sm">
                            <option value="view_name" <?= ($viewSort === 'view_name') ? 'selected' : '' ?>>Name</option>
                            <option value="view_sql" <?= ($viewSort === 'view_sql') ? 'selected' : '' ?>>SQL</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-1">Order</label>
                        <select name="view_order" class="form-select form-select-sm">
                            <option value="ASC" <?= ($viewOrder === 'ASC') ? 'selected' : '' ?>>Ascending</option>
                            <option value="DESC" <?= ($viewOrder === 'DESC') ? 'selected' : '' ?>>Descending</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-primary w-100">Apply</button>
                        <a href="views.php" class="btn btn-sm btn-outline-secondary">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($viewError): ?>
            <div class="alert alert-warning">
                <?= htmlspecialchars($viewError) ?>
            </div>
        <?php endif; ?>

        <?php if (empty($viewDetails)): ?>
            <div class="alert alert-info">No public views matched your filters.</div>
        <?php else: ?>
            <?php foreach ($viewDetails as $view): ?>
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-info text-dark">
                        <h5 class="mb-0"><?= htmlspecialchars($view['name']) ?></h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($view['data_error'])): ?>
                            <div class="alert alert-warning mb-0"><?= htmlspecialchars($view['data_error']) ?></div>
                        <?php elseif (empty($view['rows'])): ?>
                            <div class="alert alert-secondary mb-0">This view has no rows to display.</div>
                        <?php else: ?>
                            <div class="table-responsive border rounded">
                                <table class="table table-sm table-hover align-middle mb-0">
                                    <thead class="table-dark">
                                        <tr>
                                            <?php foreach (array_keys($view['rows'][0]) as $column): ?>
                                                <th><?= htmlspecialchars($column) ?></th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($view['rows'] as $row): ?>
                                            <tr>
                                                <?php foreach ($row as $value): ?>
                                                    <td><?= htmlspecialchars((string) $value) ?></td>
                                                <?php endforeach; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <?= renderViewPagination($viewPage, $totalPages, $_GET) ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
