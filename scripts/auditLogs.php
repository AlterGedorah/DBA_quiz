<?php
$host = '127.0.0.1';
$port = '5432';
$dbname = 'borrow_db';
$user = 'postgres';
$password = '3205';

$dsn = "pgsql:host=$host;port=$port;dbname='$dbname'";

try {
    $pdo = new PDO($dsn, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}

function renderPagination($currentPage, $totalPages, $pageParam, $queryParams = [])
{
    if ($totalPages <= 1) {
        return '';
    }

    $queryParams = is_array($queryParams) ? $queryParams : [];
    $currentPage = max(1, (int) $currentPage);
    $totalPages = max(1, (int) $totalPages);
    $currentPage = min($currentPage, $totalPages);

    $html = '<nav aria-label="Pagination"><ul class="pagination pagination-sm justify-content-center mb-0 mt-3">';

    $prevParams = $queryParams;
    $prevParams[$pageParam] = max(1, $currentPage - 1);
    $html .= '<li class="page-item ' . ($currentPage <= 1 ? 'disabled' : '') . '"><a class="page-link" href="?' . http_build_query($prevParams) . '">Previous</a></li>';

    for ($i = 1; $i <= $totalPages; $i++) {
        $pageParams = $queryParams;
        $pageParams[$pageParam] = $i;
        $activeClass = ($i === $currentPage) ? ' active' : '';
        $html .= '<li class="page-item' . $activeClass . '"><a class="page-link" href="?' . http_build_query($pageParams) . '">' . $i . '</a></li>';
    }

    $nextParams = $queryParams;
    $nextParams[$pageParam] = min($totalPages, $currentPage + 1);
    $html .= '<li class="page-item ' . ($currentPage >= $totalPages ? 'disabled' : '') . '"><a class="page-link" href="?' . http_build_query($nextParams) . '">Next</a></li>';
    $html .= '</ul></nav>';

    return $html;
}

$perPage = 10;
$auditPage = max(1, (int) ($_GET['audit_page'] ?? 1));
$auditSearch = trim($_GET['audit_search'] ?? '');
$auditOperation = $_GET['audit_operation'] ?? '';
$auditSort = $_GET['audit_sort'] ?? 'action_timestamp';
$auditOrder = strtoupper($_GET['audit_order'] ?? 'DESC');

$allowedSort = ['audit_id', 'borrow_id', 'equipment_id', 'operation_type', 'action_timestamp'];
if (!in_array($auditSort, $allowedSort, true)) {
    $auditSort = 'action_timestamp';
}
if (!in_array($auditOrder, ['ASC', 'DESC'], true)) {
    $auditOrder = 'DESC';
}

$allowedOperations = ['', 'INSERT', 'UPDATE', 'DELETE'];
if (!in_array($auditOperation, $allowedOperations, true)) {
    $auditOperation = '';
}

$baseFrom = "
    FROM borrow_items_audit a
    LEFT JOIN equipment e ON a.equipment_id = e.equipment_id
    LEFT JOIN borrow_transactions t ON a.borrow_id = t.borrow_id
    LEFT JOIN borrowers b ON t.borrower_id = b.borrower_id
    WHERE 1=1";

$countSql = 'SELECT COUNT(*) ' . $baseFrom;
$dataSql = "
    SELECT
        a.audit_id,
        a.borrow_id,
        a.equipment_id,
        COALESCE(b.full_name, 'Unknown') AS borrower_name,
        COALESCE(e.equipment_name, 'Unknown') AS equipment_name,
        a.operation_type,
        a.old_quantity,
        a.new_quantity,
        a.action_timestamp
" . $baseFrom;

$params = [];

if ($auditSearch !== '') {
    $countSql .= ' AND (CAST(a.borrow_id AS TEXT) ILIKE :audit_search OR CAST(a.equipment_id AS TEXT) ILIKE :audit_search OR b.full_name ILIKE :audit_search OR e.equipment_name ILIKE :audit_search)';
    $dataSql .= ' AND (CAST(a.borrow_id AS TEXT) ILIKE :audit_search OR CAST(a.equipment_id AS TEXT) ILIKE :audit_search OR b.full_name ILIKE :audit_search OR e.equipment_name ILIKE :audit_search)';
    $params[':audit_search'] = '%' . $auditSearch . '%';
}

if ($auditOperation !== '') {
    $countSql .= ' AND a.operation_type = :audit_operation';
    $dataSql .= ' AND a.operation_type = :audit_operation';
    $params[':audit_operation'] = $auditOperation;
}

$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$auditTotal = (int) $countStmt->fetchColumn();
$auditTotalPages = max(1, (int) ceil($auditTotal / $perPage));
$auditPage = min($auditPage, $auditTotalPages);

$dataSql .= ' ORDER BY a.' . $auditSort . ' ' . $auditOrder . ' LIMIT :limit OFFSET :offset';
$dataStmt = $pdo->prepare($dataSql);
foreach ($params as $key => $value) {
    $dataStmt->bindValue($key, $value);
}
$dataStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$dataStmt->bindValue(':offset', ($auditPage - 1) * $perPage, PDO::PARAM_INT);
$dataStmt->execute();
$auditLogs = $dataStmt->fetchAll(PDO::FETCH_ASSOC);
