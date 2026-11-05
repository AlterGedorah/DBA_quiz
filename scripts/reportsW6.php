<?php
$borrowers = $pdo->query("SELECT * FROM borrowers ORDER BY borrower_id DESC")->fetchAll();
$equipments = $pdo->query("SELECT * FROM equipment ORDER BY equipment_id DESC")->fetchAll();

// Borrower Type Summary
$borrowerTypeSummary = $pdo->query("
    SELECT borrower_type, COUNT(*) as total_count 
    FROM borrowers 
    GROUP BY borrower_type 
    ORDER BY total_count DESC
")->fetchAll();

// Transaction Status Summary
$transactionStatusSummary = $pdo->query("
    SELECT status, COUNT(*) as total_count 
    FROM borrow_transactions 
    GROUP BY status 
    ORDER BY total_count DESC
")->fetchAll();

// Grouped Borrowing Analysis 
$groupedBorrowingAnalysis = $pdo->query("
    SELECT b.full_name, b.borrower_type, COUNT(t.borrow_id) as transaction_count 
    FROM borrowers b
    JOIN borrow_transactions t ON b.borrower_id = t.borrower_id
    GROUP BY b.borrower_id, b.full_name, b.borrower_type
    HAVING COUNT(t.borrow_id) >= 2
    ORDER BY transaction_count DESC
")->fetchAll();
?>