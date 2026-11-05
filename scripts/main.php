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


// Capture filter inputs from URL
$txStatusFilter = $_GET['tx_status'] ?? '';
$txStartDate = $_GET['start_date'] ?? '';
$txEndDate = $_GET['end_date'] ?? '';

// Base query for transactions
$sqlTx = "SELECT t.borrow_id, t.borrow_date, t.due_date, t.status, 
                 b.full_name, b.borrower_type, 
                 e.equipment_name, e.property_no, 
                 bi.quantity
          FROM borrow_transactions t
          JOIN borrowers b ON t.borrower_id = b.borrower_id
          JOIN borrow_items bi ON t.borrow_id = bi.borrow_id
          JOIN equipment e ON bi.equipment_id = e.equipment_id
          WHERE 1=1";
$txParams = [];

// Apply status filter
if (!empty($txStatusFilter)) {
    $sqlTx .= " AND t.status = :status";
    $txParams[':status'] = $txStatusFilter;
}

// Apply date range filter (based on due date or borrow date)
if (!empty($txStartDate) && !empty($txEndDate)) {
    $sqlTx .= " AND t.due_date BETWEEN :start_date AND :end_date";
    $txParams[':start_date'] = $txStartDate;
    $txParams[':end_date'] = $txEndDate;
}

$sqlTx .= " ORDER BY t.status ASC, t.due_date ASC";

$stmtTx = $pdo->prepare($sqlTx);
$stmtTx->execute($txParams);
$activeLoans = $stmtTx->fetchAll();

function getSortParams($defaultCol, $allowedCols)
{
    $col = $_GET['sort'] ?? $defaultCol;
    if (!in_array($col, $allowedCols)) {
        $col = $defaultCol;
    }
    $order = strtoupper($_GET['order'] ?? 'ASC');
    $order = ($order === 'DESC') ? 'DESC' : 'ASC';

    $nextOrder = ($order === 'ASC') ? 'DESC' : 'ASC';

    return ['col' => $col, 'order' => $order, 'nextOrder' => $nextOrder];
}

// --- 2. APPLY TO BORROWERS ---
$bSort = getSortParams('borrower_id', ['borrower_id', 'borrower_no', 'full_name', 'borrower_type']);
$borrowers = $pdo->query("SELECT * FROM borrowers ORDER BY {$bSort['col']} {$bSort['order']}")->fetchAll();

// --- 3. APPLY TO EQUIPMENT ---
$eSort = getSortParams('equipment_id', ['equipment_id', 'property_no', 'equipment_name', 'quantity_available']);
$equipments = $pdo->query("SELECT * FROM equipment ORDER BY {$eSort['col']} {$eSort['order']}")->fetchAll();

// --- 4. APPLY TO TRANSACTIONS ---
$tSort = getSortParams('borrow_id', ['borrow_id', 'full_name', 'equipment_name', 'quantity', 'due_date', 'status']);


?>