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

// Complete Borrowing Report
$completeBorrowing = $pdo->query("
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
    ORDER BY t.borrow_id DESC
")->fetchAll();

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

// Borrowers Without Transactions
$borrowersWithoutTx = $pdo->query("
    SELECT LOWER(b.borrower_no) AS borrower_no, INITCAP(b.full_name) AS full_name, b.borrower_type
    FROM borrowers b
    LEFT JOIN borrow_transactions t ON b.borrower_id = t.borrower_id
    WHERE t.borrow_id IS NULL
    ORDER BY b.borrower_id DESC
")->fetchAll();

// Equipment Without Borrowing History
$equipmentWithoutHistory = $pdo->query("
    SELECT TRIM(e.property_no) AS property_no, INITCAP(e.equipment_name) AS equipment_name, e.quantity_available
    FROM equipment e
    LEFT JOIN borrow_items bi ON e.equipment_id = bi.equipment_id
    WHERE bi.borrow_id IS NULL
    ORDER BY e.equipment_id DESC
")->fetchAll();

// Combined Borrower Activity 
$unionReport = $pdo->query("
    SELECT 'Active Borrower' AS classification, full_name FROM borrowers WHERE borrower_id IN (SELECT borrower_id FROM borrow_transactions)
    UNION
    SELECT 'Registered Member' AS classification, full_name FROM borrowers
    ORDER BY classification ASC
")->fetchAll();

$unionAllReport = $pdo->query("
    SELECT 'Student Account' AS user_tier, full_name FROM borrowers WHERE borrower_type = 'Student'
    UNION ALL
    SELECT 'Faculty / Staff Account' AS user_tier, full_name FROM borrowers WHERE borrower_type IN ('Faculty', 'Staff')
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