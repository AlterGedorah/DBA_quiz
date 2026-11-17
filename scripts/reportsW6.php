<?php
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

$groupedAnalysisSearch = trim($_GET['grouped_analysis_search'] ?? '');
$groupedAnalysisType = $_GET['grouped_analysis_type'] ?? '';
$groupedAnalysisMinLoans = max(2, (int) ($_GET['grouped_analysis_min_loans'] ?? 2));
$groupedAnalysisSort = $_GET['grouped_analysis_sort'] ?? 'transaction_count';
$groupedAnalysisOrder = strtoupper($_GET['grouped_analysis_order'] ?? 'DESC');

$allowedGroupedAnalysisSort = ['full_name', 'borrower_type', 'transaction_count'];
if (!in_array($groupedAnalysisSort, $allowedGroupedAnalysisSort, true)) {
    $groupedAnalysisSort = 'transaction_count';
}
if (!in_array($groupedAnalysisOrder, ['ASC', 'DESC'], true)) {
    $groupedAnalysisOrder = 'DESC';
}

$groupedAnalysisSql = "
    SELECT b.full_name, b.borrower_type, COUNT(t.borrow_id) as transaction_count
    FROM borrowers b
    JOIN borrow_transactions t ON b.borrower_id = t.borrower_id
    WHERE 1=1";
$groupedAnalysisParams = [];

if ($groupedAnalysisSearch !== '') {
    $groupedAnalysisSql .= ' AND (b.full_name ILIKE :grouped_analysis_search OR b.borrower_type ILIKE :grouped_analysis_search OR CAST(t.borrow_id AS TEXT) ILIKE :grouped_analysis_search)';
    $groupedAnalysisParams[':grouped_analysis_search'] = '%' . $groupedAnalysisSearch . '%';
}

if ($groupedAnalysisType !== '') {
    $groupedAnalysisSql .= ' AND b.borrower_type = :grouped_analysis_type';
    $groupedAnalysisParams[':grouped_analysis_type'] = $groupedAnalysisType;
}

$groupedAnalysisSql .= ' GROUP BY b.borrower_id, b.full_name, b.borrower_type';
$groupedAnalysisSql .= ' HAVING COUNT(t.borrow_id) >= :grouped_analysis_min_loans';
$groupedAnalysisParams[':grouped_analysis_min_loans'] = $groupedAnalysisMinLoans;

$groupedAnalysisSql .= ' ORDER BY ' . $groupedAnalysisSort . ' ' . $groupedAnalysisOrder;

$groupedBorrowingAnalysisStmt = $pdo->prepare($groupedAnalysisSql);
foreach ($groupedAnalysisParams as $key => $value) {
    $groupedBorrowingAnalysisStmt->bindValue($key, $value);
}
$groupedBorrowingAnalysisStmt->execute();
$groupedBorrowingAnalysis = $groupedBorrowingAnalysisStmt->fetchAll();
?>