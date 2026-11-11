<?php
require_once '../includes/auth.php';
require_once '../includes/config.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

// Recent entries today
if ($action === 'recent_entries') {
    $result = $conn->query("
        SELECT v.vehicle_number, v.vehicle_type, v.owner_name, v.entry_time, ps.slot_number
        FROM vehicles v
        LEFT JOIN parking_slots ps ON ps.id=v.slot_id
        WHERE v.status='parked' AND DATE(v.entry_time)=CURDATE()
        ORDER BY v.entry_time DESC LIMIT 15
    ");
    $vehicles = [];
    while ($r = $result->fetch_assoc()) $vehicles[] = $r;
    echo json_encode(['success'=>true,'vehicles'=>$vehicles]);
    exit;
}

// Exit history today
if ($action === 'exit_history') {
    $result = $conn->query("
        SELECT b.vehicle_number, b.slot_number, b.duration_minutes, b.amount, b.payment_status, b.exit_time
        FROM billing b
        WHERE DATE(b.exit_time)=CURDATE()
        ORDER BY b.exit_time DESC LIMIT 20
    ");
    $vehicles = [];
    while ($r = $result->fetch_assoc()) $vehicles[] = $r;
    echo json_encode(['success'=>true,'vehicles'=>$vehicles]);
    exit;
}

// Search by vehicle number
if ($action === 'search') {
    $vehicle_number = strtoupper(trim($_GET['vehicle_number'] ?? ''));
    $user_id = intval($_GET['user_id'] ?? 0);
    if (empty($vehicle_number)) {
        echo json_encode(['success'=>false,'message'=>'Vehicle number required.','vehicles'=>[]]);
        exit;
    }
    $where = $user_id ? "AND v.user_id=$user_id" : '';
    $stmt = $conn->prepare("
        SELECT v.vehicle_number, v.vehicle_type, v.entry_time, v.exit_time, v.status, ps.slot_number
        FROM vehicles v
        LEFT JOIN parking_slots ps ON ps.id=v.slot_id
        WHERE v.vehicle_number LIKE ? $where
        ORDER BY v.created_at DESC LIMIT 20
    ");
    $like = "%$vehicle_number%";
    $stmt->bind_param("s", $like);
    $stmt->execute();
    $result = $stmt->get_result();
    $vehicles = [];
    while ($r = $result->fetch_assoc()) $vehicles[] = $r;
    echo json_encode(['success'=>true,'vehicles'=>$vehicles]);
    exit;
}

echo json_encode(['success'=>false,'message'=>'Invalid action.']);
