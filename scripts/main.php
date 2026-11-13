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
    echo "Database connection failed: " . $e->getMessage();
}

$error = '';
$success = '';

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

            // 0. Explicitly verify stock availability before proceeding
            $stmtStock = $pdo->prepare("SELECT quantity_available, equipment_name FROM equipment WHERE equipment_id = :id FOR UPDATE");
            $stmtStock->execute([':id' => $eqId]);
            $equipment = $stmtStock->fetch(PDO::FETCH_ASSOC);

            if (!$equipment) {
                throw new Exception("Selected equipment item does not exist in the database.");
            }

            if ((int) $equipment['quantity_available'] < $qty) {
                throw new Exception("Inventory Error: Only " . $equipment['quantity_available'] . " unit(s) of \"" . $equipment['equipment_name'] . "\" available in stock, but you requested " . $qty . ".");
            }

            // 1. Create Transaction header with 'Active' status (matching database check constraint)
            $stmtTx = $pdo->prepare("INSERT INTO borrow_transactions (borrower_id, due_date, status) VALUES (:bid, :due, 'Active') RETURNING borrow_id");
            $stmtTx->execute([':bid' => $borrowerId, ':due' => $dueDate]);
            $borrowId = $stmtTx->fetchColumn();

            // 2. Create Line Item
            $stmtItem = $pdo->prepare("INSERT INTO borrow_items (borrow_id, equipment_id, quantity) VALUES (:bid, :eqid, :qty)");
            $stmtItem->execute([':bid' => $borrowId, ':eqid' => $eqId, ':qty' => $qty]);

            // 3. Deduct Stock safely
            $stmtEq = $pdo->prepare("UPDATE equipment SET quantity_available = quantity_available - :qty WHERE equipment_id = :id");
            $stmtEq->execute([':qty' => $qty, ':id' => $eqId]);

            $pdo->commit();
            $success = "Equipment checked out successfully (Status: Active).";
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
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if (strpos($e->getMessage(), 'Inventory Error:') !== false || strpos($e->getMessage(), 'Selected equipment') !== false) {
            $error = $e->getMessage();
        } elseif ($e instanceof PDOException) {
            if ($e->getCode() === '23503') {
                $error = "Foreign Key Error: Cannot delete record linked to active transactions.";
            } elseif ($e->getCode() === '23505') {
                $error = "Duplicate Entry: Record or unique property already exists.";
            } elseif ($e->getCode() === '23514') {
                $error = "Database Constraint Error: Stock level cannot fall below zero.";
            } else {
                $error = "Database Error: " . $e->getMessage();
            }
        } else {
            $error = "Error: " . $e->getMessage();
        }
    }
}

if (isset($_GET['msg'])) {
    $success = $_GET['msg'];
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

$borrowerSearch = trim($_GET['borrower_search'] ?? '');
$borrowerTypeFilter = $_GET['borrower_type'] ?? '';
$borrowerSort = $_GET['borrower_sort'] ?? 'borrower_id';
$borrowerOrder = strtoupper($_GET['borrower_order'] ?? 'DESC');
$borrowerPage = max(1, (int) ($_GET['borrower_page'] ?? 1));

$allowedBorrowerSort = ['borrower_id', 'borrower_no', 'full_name', 'borrower_type'];
if (!in_array($borrowerSort, $allowedBorrowerSort, true)) {
    $borrowerSort = 'borrower_id';
}
if (!in_array($borrowerOrder, ['ASC', 'DESC'], true)) {
    $borrowerOrder = 'DESC';
}

$borrowerSql = 'SELECT * FROM borrowers WHERE 1=1';
$borrowerCountSql = 'SELECT COUNT(*) FROM borrowers WHERE 1=1';
$borrowerParams = [];

if ($borrowerSearch !== '') {
    $borrowerSql .= ' AND (borrower_no ILIKE :borrower_search OR full_name ILIKE :borrower_search)';
    $borrowerCountSql .= ' AND (borrower_no ILIKE :borrower_search OR full_name ILIKE :borrower_search)';
    $borrowerParams[':borrower_search'] = '%' . $borrowerSearch . '%';
}

if ($borrowerTypeFilter !== '') {
    $borrowerSql .= ' AND borrower_type = :borrower_type';
    $borrowerCountSql .= ' AND borrower_type = :borrower_type';
    $borrowerParams[':borrower_type'] = $borrowerTypeFilter;
}

$borrowerCountStmt = $pdo->prepare($borrowerCountSql);
$borrowerCountStmt->execute($borrowerParams);
$borrowerTotal = (int) $borrowerCountStmt->fetchColumn();
$borrowerTotalPages = max(1, (int) ceil($borrowerTotal / $perPage));
$borrowerPage = min($borrowerPage, $borrowerTotalPages);

$allBorrowers = $pdo->query("SELECT * FROM borrowers ORDER BY borrower_id DESC")->fetchAll();

$borrowerSql .= ' ORDER BY ' . $borrowerSort . ' ' . $borrowerOrder . ' LIMIT :limit OFFSET :offset';
$borrowerStmt = $pdo->prepare($borrowerSql);
foreach ($borrowerParams as $key => $value) {
    $borrowerStmt->bindValue($key, $value);
}
$borrowerStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$borrowerStmt->bindValue(':offset', ($borrowerPage - 1) * $perPage, PDO::PARAM_INT);
$borrowerStmt->execute();
$borrowers = $borrowerStmt->fetchAll();

$equipmentSearch = trim($_GET['eq_search'] ?? '');
$equipmentSort = $_GET['eq_sort'] ?? 'equipment_id';
$equipmentOrder = strtoupper($_GET['eq_order'] ?? 'DESC');
$equipmentPage = max(1, (int) ($_GET['eq_page'] ?? 1));

$allowedEquipmentSort = ['equipment_id', 'property_no', 'equipment_name', 'quantity_available'];
if (!in_array($equipmentSort, $allowedEquipmentSort, true)) {
    $equipmentSort = 'equipment_id';
}
if (!in_array($equipmentOrder, ['ASC', 'DESC'], true)) {
    $equipmentOrder = 'DESC';
}

$equipmentSql = 'SELECT * FROM equipment WHERE 1=1';
$equipmentCountSql = 'SELECT COUNT(*) FROM equipment WHERE 1=1';
$equipmentParams = [];

if ($equipmentSearch !== '') {
    $equipmentSql .= ' AND (property_no ILIKE :eq_search OR equipment_name ILIKE :eq_search)';
    $equipmentCountSql .= ' AND (property_no ILIKE :eq_search OR equipment_name ILIKE :eq_search)';
    $equipmentParams[':eq_search'] = '%' . $equipmentSearch . '%';
}

$equipmentCountStmt = $pdo->prepare($equipmentCountSql);
$equipmentCountStmt->execute($equipmentParams);
$equipmentTotal = (int) $equipmentCountStmt->fetchColumn();
$equipmentTotalPages = max(1, (int) ceil($equipmentTotal / $perPage));
$equipmentPage = min($equipmentPage, $equipmentTotalPages);

$allEquipments = $pdo->query("SELECT * FROM equipment ORDER BY equipment_id DESC")->fetchAll();

$equipmentSql .= ' ORDER BY ' . $equipmentSort . ' ' . $equipmentOrder . ' LIMIT :limit OFFSET :offset';
$equipmentStmt = $pdo->prepare($equipmentSql);
foreach ($equipmentParams as $key => $value) {
    $equipmentStmt->bindValue($key, $value);
}
$equipmentStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$equipmentStmt->bindValue(':offset', ($equipmentPage - 1) * $perPage, PDO::PARAM_INT);
$equipmentStmt->execute();
$equipments = $equipmentStmt->fetchAll();

$txStatusFilter = $_GET['tx_status'] ?? '';
$txStartDate = $_GET['start_date'] ?? '';
$txEndDate = $_GET['end_date'] ?? '';
$txSearch = trim($_GET['tx_search'] ?? '');
$txPage = max(1, (int) ($_GET['tx_page'] ?? 1));

$txSort = $_GET['tx_sort'] ?? 'due_date';
$txOrder = strtoupper($_GET['tx_order'] ?? 'ASC');
$allowedTxSort = ['borrow_id', 'due_date', 'status', 'full_name', 'equipment_name'];
if (!in_array($txSort, $allowedTxSort, true)) {
    $txSort = 'due_date';
}
if (!in_array($txOrder, ['ASC', 'DESC'], true)) {
    $txOrder = 'ASC';
}

$sqlTx = "SELECT t.borrow_id, t.borrow_date, t.due_date, t.status,
                 b.full_name, b.borrower_type,
                 e.equipment_name, e.property_no,
                 bi.quantity
          FROM borrow_transactions t
          JOIN borrowers b ON t.borrower_id = b.borrower_id
          JOIN borrow_items bi ON t.borrow_id = bi.borrow_id
          JOIN equipment e ON bi.equipment_id = e.equipment_id
          WHERE 1=1";
$sqlTxCount = "SELECT COUNT(*)
               FROM borrow_transactions t
               JOIN borrowers b ON t.borrower_id = b.borrower_id
               JOIN borrow_items bi ON t.borrow_id = bi.borrow_id
               JOIN equipment e ON bi.equipment_id = e.equipment_id
               WHERE 1=1";
$txParams = [];

if (!empty($txStatusFilter)) {
    $sqlTx .= ' AND t.status = :status';
    $sqlTxCount .= ' AND t.status = :status';
    $txParams[':status'] = $txStatusFilter;
}

if (!empty($txStartDate) && !empty($txEndDate)) {
    $sqlTx .= ' AND t.due_date BETWEEN :start_date AND :end_date';
    $sqlTxCount .= ' AND t.due_date BETWEEN :start_date AND :end_date';
    $txParams[':start_date'] = $txStartDate;
    $txParams[':end_date'] = $txEndDate;
}

if ($txSearch !== '') {
    $sqlTx .= ' AND (CAST(t.borrow_id AS TEXT) ILIKE :tx_search OR b.full_name ILIKE :tx_search OR e.equipment_name ILIKE :tx_search)';
    $sqlTxCount .= ' AND (CAST(t.borrow_id AS TEXT) ILIKE :tx_search OR b.full_name ILIKE :tx_search OR e.equipment_name ILIKE :tx_search)';
    $txParams[':tx_search'] = '%' . $txSearch . '%';
}

$stmtTxCount = $pdo->prepare($sqlTxCount);
$stmtTxCount->execute($txParams);
$txTotal = (int) $stmtTxCount->fetchColumn();
$txTotalPages = max(1, (int) ceil($txTotal / $perPage));
$txPage = min($txPage, $txTotalPages);

if ($txSort === 'full_name') {
    $txSortColumn = 'b.full_name';
} elseif ($txSort === 'equipment_name') {
    $txSortColumn = 'e.equipment_name';
} else {
    $txSortColumn = 't.' . $txSort;
}

$sqlTx .= ' ORDER BY ' . $txSortColumn . ' ' . $txOrder . ' LIMIT :limit OFFSET :offset';
$stmtTx = $pdo->prepare($sqlTx);
foreach ($txParams as $key => $value) {
    $stmtTx->bindValue($key, $value);
}
$stmtTx->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmtTx->bindValue(':offset', ($txPage - 1) * $perPage, PDO::PARAM_INT);
$stmtTx->execute();
$activeLoans = $stmtTx->fetchAll();

// Backwards compatibility for existing sorting helpers in the rest of the app.
function getSortParams($defaultCol, $allowedCols)
{
    $col = $_GET['sort'] ?? $defaultCol;
    if (!in_array($col, $allowedCols, true)) {
        $col = $defaultCol;
    }
    $order = strtoupper($_GET['order'] ?? 'ASC');
    $order = ($order === 'DESC') ? 'DESC' : 'ASC';

    $nextOrder = ($order === 'ASC') ? 'DESC' : 'ASC';

    return ['col' => $col, 'order' => $order, 'nextOrder' => $nextOrder];
}

?>