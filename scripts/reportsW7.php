<?php
// Database Configuration
$host = '127.0.0.1';
$port = '5432';
$dbname = 'borrow_db';
$user = 'postgres';
$password = '3205';

$dsn = "pgsql:host=$host;port=$port;dbname='$dbname'";

try {
    $pdo = new PDO($dsn, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

function normalizeSortOrder($value, $default)
{
    $value = strtoupper((string) $value);
    return in_array($value, ['ASC', 'DESC'], true) ? $value : $default;
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

// Complete Borrowing Report
$report1Search = trim($_GET['report1_search'] ?? '');
$report1Status = $_GET['report1_status'] ?? '';
$report1Sort = $_GET['report1_sort'] ?? 'borrow_date';
$report1Order = normalizeSortOrder($_GET['report1_order'] ?? 'DESC', 'DESC');
$report1Page = max(1, (int) ($_GET['report1_page'] ?? 1));

$allowedReport1Sort = ['borrower_no', 'full_name', 'equipment_name', 'quantity', 'borrow_date', 'due_date', 'status'];
if (!in_array($report1Sort, $allowedReport1Sort, true)) {
    $report1Sort = 'borrow_date';
}

$report1Sql = "
    SELECT 
        b.borrower_no,
        INITCAP(b.full_name) AS full_name,
        INITCAP(e.equipment_name) AS equipment_name,
        bi.quantity,
        TO_CHAR(t.borrow_date, 'YYYY-MM-DD') AS borrow_date,
        TO_CHAR(t.due_date, 'YYYY-MM-DD') AS due_date,
        t.status
    FROM borrow_transactions t
    JOIN borrowers b ON t.borrower_id = b.borrower_id
    JOIN borrow_items bi ON t.borrow_id = bi.borrow_id
    JOIN equipment e ON bi.equipment_id = e.equipment_id
    WHERE 1=1";
$report1CountSql = "SELECT COUNT(*) FROM borrow_transactions t JOIN borrowers b ON t.borrower_id = b.borrower_id JOIN borrow_items bi ON t.borrow_id = bi.borrow_id JOIN equipment e ON bi.equipment_id = e.equipment_id WHERE 1=1";
$report1Params = [];

if ($report1Search !== '') {
    $report1Sql .= ' AND (LOWER(b.borrower_no) LIKE :report1_search OR LOWER(b.full_name) LIKE :report1_search OR LOWER(e.equipment_name) LIKE :report1_search OR LOWER(t.status) LIKE :report1_search)';
    $report1CountSql .= ' AND (LOWER(b.borrower_no) LIKE :report1_search OR LOWER(b.full_name) LIKE :report1_search OR LOWER(e.equipment_name) LIKE :report1_search OR LOWER(t.status) LIKE :report1_search)';
    $report1Params[':report1_search'] = '%' . strtolower($report1Search) . '%';
}

if ($report1Status !== '') {
    $report1Sql .= ' AND t.status = :report1_status';
    $report1CountSql .= ' AND t.status = :report1_status';
    $report1Params[':report1_status'] = $report1Status;
}

$report1CountStmt = $pdo->prepare($report1CountSql);
$report1CountStmt->execute($report1Params);
$report1Total = (int) $report1CountStmt->fetchColumn();
$report1TotalPages = max(1, (int) ceil($report1Total / $perPage));
$report1Page = min($report1Page, $report1TotalPages);

$report1Sql .= ' ORDER BY ' . $report1Sort . ' ' . $report1Order . ' LIMIT :limit OFFSET :offset';
$report1Stmt = $pdo->prepare($report1Sql);
foreach ($report1Params as $key => $value) {
    $report1Stmt->bindValue($key, $value);
}
$report1Stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$report1Stmt->bindValue(':offset', ($report1Page - 1) * $perPage, PDO::PARAM_INT);
$report1Stmt->execute();
$completeBorrowing = $report1Stmt->fetchAll();

// Borrowers Without Transactions
$txlessSearch = trim($_GET['txless_search'] ?? '');
$txlessType = $_GET['txless_type'] ?? '';
$txlessSort = $_GET['txless_sort'] ?? 'borrower_id';
$txlessOrder = normalizeSortOrder($_GET['txless_order'] ?? 'DESC', 'DESC');
$txlessPage = max(1, (int) ($_GET['txless_page'] ?? 1));

$allowedTxlessSort = ['borrower_id', 'borrower_no', 'full_name', 'borrower_type'];
if (!in_array($txlessSort, $allowedTxlessSort, true)) {
    $txlessSort = 'borrower_id';
}

$txlessSql = "
    SELECT b.borrower_id, LOWER(b.borrower_no) AS borrower_no, INITCAP(b.full_name) AS full_name, b.borrower_type
    FROM borrowers b
    LEFT JOIN borrow_transactions t ON b.borrower_id = t.borrower_id
    WHERE t.borrow_id IS NULL";
$txlessCountSql = "SELECT COUNT(*) FROM borrowers b LEFT JOIN borrow_transactions t ON b.borrower_id = t.borrower_id WHERE t.borrow_id IS NULL";
$txlessParams = [];

if ($txlessSearch !== '') {
    $txlessSql .= ' AND (LOWER(b.borrower_no) LIKE :txless_search OR LOWER(b.full_name) LIKE :txless_search OR LOWER(b.borrower_type) LIKE :txless_search)';
    $txlessCountSql .= ' AND (LOWER(b.borrower_no) LIKE :txless_search OR LOWER(b.full_name) LIKE :txless_search OR LOWER(b.borrower_type) LIKE :txless_search)';
    $txlessParams[':txless_search'] = '%' . strtolower($txlessSearch) . '%';
}

if ($txlessType !== '') {
    $txlessSql .= ' AND b.borrower_type = :txless_type';
    $txlessCountSql .= ' AND b.borrower_type = :txless_type';
    $txlessParams[':txless_type'] = $txlessType;
}

$txlessCountStmt = $pdo->prepare($txlessCountSql);
$txlessCountStmt->execute($txlessParams);
$txlessTotal = (int) $txlessCountStmt->fetchColumn();
$txlessTotalPages = max(1, (int) ceil($txlessTotal / $perPage));
$txlessPage = min($txlessPage, $txlessTotalPages);

$txlessSql .= ' ORDER BY ' . $txlessSort . ' ' . $txlessOrder . ' LIMIT :limit OFFSET :offset';
$txlessStmt = $pdo->prepare($txlessSql);
foreach ($txlessParams as $key => $value) {
    $txlessStmt->bindValue($key, $value);
}
$txlessStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$txlessStmt->bindValue(':offset', ($txlessPage - 1) * $perPage, PDO::PARAM_INT);
$txlessStmt->execute();
$borrowersWithoutTx = $txlessStmt->fetchAll();

// Combined Borrower Activity
$unionSearch = trim($_GET['union_search'] ?? '');
$unionFilter = $_GET['union_filter'] ?? '';
$unionSort = $_GET['union_sort'] ?? 'classification';
$unionOrder = normalizeSortOrder($_GET['union_order'] ?? 'ASC', 'ASC');
$unionPage = max(1, (int) ($_GET['union_page'] ?? 1));

$allowedUnionFilter = ['', 'Active Borrower', 'Registered Member'];
if (!in_array($unionFilter, $allowedUnionFilter, true)) {
    $unionFilter = '';
}

$allowedUnionSort = ['classification', 'full_name'];
if (!in_array($unionSort, $allowedUnionSort, true)) {
    $unionSort = 'classification';
}

$unionSql = "SELECT classification, full_name FROM (
    SELECT 'Active Borrower' AS classification, full_name FROM borrowers WHERE borrower_id IN (SELECT borrower_id FROM borrow_transactions)
    UNION
    SELECT 'Registered Member' AS classification, full_name FROM borrowers
) AS combined WHERE 1=1";
$unionCountSql = "SELECT COUNT(*) FROM (SELECT 'Active Borrower' AS classification, full_name FROM borrowers WHERE borrower_id IN (SELECT borrower_id FROM borrow_transactions) UNION SELECT 'Registered Member' AS classification, full_name FROM borrowers) AS combined WHERE 1=1";
$unionParams = [];

if ($unionSearch !== '') {
    $unionSql .= ' AND (LOWER(classification) LIKE :union_search OR LOWER(full_name) LIKE :union_search)';
    $unionCountSql .= ' AND (LOWER(classification) LIKE :union_search OR LOWER(full_name) LIKE :union_search)';
    $unionParams[':union_search'] = '%' . strtolower($unionSearch) . '%';
}

if ($unionFilter !== '') {
    $unionSql .= ' AND classification = :union_filter';
    $unionCountSql .= ' AND classification = :union_filter';
    $unionParams[':union_filter'] = $unionFilter;
}

$unionCountStmt = $pdo->prepare($unionCountSql);
$unionCountStmt->execute($unionParams);
$unionTotal = (int) $unionCountStmt->fetchColumn();
$unionTotalPages = max(1, (int) ceil($unionTotal / $perPage));
$unionPage = min($unionPage, $unionTotalPages);

$unionSql .= ' ORDER BY ' . $unionSort . ' ' . $unionOrder . ' LIMIT :limit OFFSET :offset';
$unionStmt = $pdo->prepare($unionSql);
foreach ($unionParams as $key => $value) {
    $unionStmt->bindValue($key, $value);
}
$unionStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$unionStmt->bindValue(':offset', ($unionPage - 1) * $perPage, PDO::PARAM_INT);
$unionStmt->execute();
$unionReport = $unionStmt->fetchAll();

$unionAllSearch = trim($_GET['union_all_search'] ?? '');
$unionAllFilter = $_GET['union_all_filter'] ?? '';
$unionAllSort = $_GET['union_all_sort'] ?? 'user_tier';
$unionAllOrder = normalizeSortOrder($_GET['union_all_order'] ?? 'ASC', 'ASC');
$unionAllPage = max(1, (int) ($_GET['union_all_page'] ?? 1));

$allowedUnionAllFilter = ['', 'Student Account', 'Faculty / Staff Account'];
if (!in_array($unionAllFilter, $allowedUnionAllFilter, true)) {
    $unionAllFilter = '';
}

$allowedUnionAllSort = ['user_tier', 'full_name'];
if (!in_array($unionAllSort, $allowedUnionAllSort, true)) {
    $unionAllSort = 'user_tier';
}

$unionAllSql = "SELECT user_tier, full_name FROM (
    SELECT 'Student Account' AS user_tier, full_name FROM borrowers WHERE borrower_type = 'Student'
    UNION ALL
    SELECT 'Faculty / Staff Account' AS user_tier, full_name FROM borrowers WHERE borrower_type IN ('Faculty', 'Staff')
) AS combined WHERE 1=1";
$unionAllCountSql = "SELECT COUNT(*) FROM (SELECT 'Student Account' AS user_tier, full_name FROM borrowers WHERE borrower_type = 'Student' UNION ALL SELECT 'Faculty / Staff Account' AS user_tier, full_name FROM borrowers WHERE borrower_type IN ('Faculty', 'Staff')) AS combined WHERE 1=1";
$unionAllParams = [];

if ($unionAllSearch !== '') {
    $unionAllSql .= ' AND (LOWER(user_tier) LIKE :union_all_search OR LOWER(full_name) LIKE :union_all_search)';
    $unionAllCountSql .= ' AND (LOWER(user_tier) LIKE :union_all_search OR LOWER(full_name) LIKE :union_all_search)';
    $unionAllParams[':union_all_search'] = '%' . strtolower($unionAllSearch) . '%';
}

if ($unionAllFilter !== '') {
    $unionAllSql .= ' AND user_tier = :union_all_filter';
    $unionAllCountSql .= ' AND user_tier = :union_all_filter';
    $unionAllParams[':union_all_filter'] = $unionAllFilter;
}

$unionAllCountStmt = $pdo->prepare($unionAllCountSql);
$unionAllCountStmt->execute($unionAllParams);
$unionAllTotal = (int) $unionAllCountStmt->fetchColumn();
$unionAllTotalPages = max(1, (int) ceil($unionAllTotal / $perPage));
$unionAllPage = min($unionAllPage, $unionAllTotalPages);

$unionAllSql .= ' ORDER BY ' . $unionAllSort . ' ' . $unionAllOrder . ' LIMIT :limit OFFSET :offset';
$unionAllStmt = $pdo->prepare($unionAllSql);
foreach ($unionAllParams as $key => $value) {
    $unionAllStmt->bindValue($key, $value);
}
$unionAllStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$unionAllStmt->bindValue(':offset', ($unionAllPage - 1) * $perPage, PDO::PARAM_INT);
$unionAllStmt->execute();
$unionAllReport = $unionAllStmt->fetchAll();

// Equipment Borrowing Summary
$equipmentSummary = $pdo->query("
    SELECT 
        UPPER(e.property_no) AS property_no, 
        INITCAP(e.equipment_name) AS equipment_name, 
        COALESCE(SUM(bi.quantity), 0) AS total_qty_borrowed, 
        COUNT(DISTINCT t.borrow_id) AS transaction_count,
        ROUND(COALESCE(AVG(bi.quantity), 0), 2) AS avg_qty,
        COALESCE(MIN(bi.quantity), 0) AS min_qty,
        COALESCE(MAX(bi.quantity), 0) AS max_qty
    FROM equipment e
    LEFT JOIN borrow_items bi ON e.equipment_id = bi.equipment_id
    LEFT JOIN borrow_transactions t ON bi.borrow_id = t.borrow_id
    GROUP BY e.equipment_id, e.property_no, e.equipment_name
    ORDER BY total_qty_borrowed DESC
")->fetchAll();

// Equipment Without Borrowing History
$equipmentWithoutHistory = $pdo->query("
    SELECT TRIM(e.property_no) AS property_no, INITCAP(e.equipment_name) AS equipment_name, e.quantity_available
    FROM equipment e
    LEFT JOIN borrow_items bi ON e.equipment_id = bi.equipment_id
    WHERE bi.borrow_id IS NULL
    ORDER BY e.equipment_id DESC
")->fetchAll();

// Monthly Borrowing Report 
$monthlyReport = $pdo->query("
    SELECT 
        TO_CHAR(t.borrow_date, 'Month YYYY') AS readable_month,
        COUNT(t.borrow_id) AS total_transactions,
        SUM(bi.quantity) AS total_items_borrowed
    FROM borrow_transactions t
    JOIN borrow_items bi ON t.borrow_id = bi.borrow_id
    GROUP BY TO_CHAR(t.borrow_date, 'Month YYYY'), DATE_TRUNC('month', t.borrow_date)
    ORDER BY DATE_TRUNC('month', t.borrow_date) DESC
")->fetchAll();
?>