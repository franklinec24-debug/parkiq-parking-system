<?php
require_once '../includes/auth.php';
require_once '../includes/config.php';
header('Content-Type: application/json');
requireStaff();

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

if ($action === 'mark_paid') {
    $bill_id = intval($input['bill_id'] ?? 0);
    if (!$bill_id) {
        echo json_encode(['success'=>false,'message'=>'Invalid bill ID.']);
        exit;
    }

    $bill = $conn->query("SELECT * FROM billing WHERE id=$bill_id AND payment_status='pending'")->fetch_assoc();
    if (!$bill) {
        echo json_encode(['success'=>false,'message'=>'Bill not found or already paid.']);
        exit;
    }

    $payment_time = date('Y-m-d H:i:s');
    $conn->query("UPDATE billing SET payment_status='paid', payment_time='$payment_time' WHERE id=$bill_id");

    echo json_encode([
        'success'  => true,
        'amount'   => number_format($bill['amount'], 2),
        'message'  => 'Payment of ₹'.number_format($bill['amount'],2).' collected successfully.'
    ]);
    exit;
}

echo json_encode(['success'=>false,'message'=>'Invalid action.']);
