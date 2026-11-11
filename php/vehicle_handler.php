<?php
require_once '../includes/auth.php';
require_once '../includes/config.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

// ---- VEHICLE ENTRY ----
if ($action === 'entry') {
    $vehicle_number = strtoupper(trim($input['vehicle_number'] ?? ''));
    $vehicle_type   = in_array($input['vehicle_type'] ?? '', ['4W','2W']) ? $input['vehicle_type'] : '4W';
    $owner_name     = trim($input['owner_name'] ?? '');
    $user_id        = $_SESSION['user_id'] ?? null;

    if (empty($vehicle_number)) {
        echo json_encode(['success'=>false,'message'=>'Vehicle number is required.']);
        exit;
    }

    // Check if already parked
    $stmt = $conn->prepare("SELECT id FROM vehicles WHERE vehicle_number=? AND status='parked'");
    $stmt->bind_param("s", $vehicle_number);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode(['success'=>false,'message'=>'This vehicle is already parked in the system.']);
        exit;
    }

    // Find available slot (prefer non-reserved)
    $slot_result = $conn->query("SELECT id, slot_number FROM parking_slots WHERE status='available' AND slot_type='$vehicle_type' ORDER BY slot_number LIMIT 1");
    if ($slot_result->num_rows === 0) {
        echo json_encode(['success'=>false,'message'=>'No available slots for '.$vehicle_type.'. Parking may be full!']);
        exit;
    }
    $slot = $slot_result->fetch_assoc();

    // Insert vehicle record
    $entry_time = date('Y-m-d H:i:s');
    $stmt = $conn->prepare("INSERT INTO vehicles (user_id, vehicle_number, vehicle_type, owner_name, slot_id, entry_time, status) VALUES (?,?,?,?,?,?,'parked')");
    $stmt->bind_param("isssis", $user_id, $vehicle_number, $vehicle_type, $owner_name, $slot['id'], $entry_time);
    if (!$stmt->execute()) {
        echo json_encode(['success'=>false,'message'=>'Failed to register entry.']);
        exit;
    }

    // Mark slot occupied
    $conn->query("UPDATE parking_slots SET status='occupied' WHERE id=".$slot['id']);

    echo json_encode(['success'=>true,'slot'=>$slot['slot_number'],'message'=>'Entry registered successfully.']);
    exit;
}

// ---- VEHICLE EXIT ----
if ($action === 'exit') {
    $vehicle_id     = intval($input['vehicle_id'] ?? 0);
    $payment_status = in_array($input['payment_status'] ?? '', ['paid','pending']) ? $input['payment_status'] : 'pending';

    if (!$vehicle_id) {
        echo json_encode(['success'=>false,'message'=>'Invalid vehicle selection.']);
        exit;
    }

    // Get vehicle details
    $stmt = $conn->prepare("SELECT v.*, ps.slot_number, ps.id AS slot_db_id FROM vehicles v LEFT JOIN parking_slots ps ON ps.id=v.slot_id WHERE v.id=? AND v.status='parked'");
    $stmt->bind_param("i", $vehicle_id);
    $stmt->execute();
    $vehicle = $stmt->get_result()->fetch_assoc();

    if (!$vehicle) {
        echo json_encode(['success'=>false,'message'=>'Vehicle not found or already exited.']);
        exit;
    }

    $exit_time = date('Y-m-d H:i:s');
    $entry_ts  = strtotime($vehicle['entry_time']);
    $exit_ts   = time();
    $duration_minutes = max(1, round(($exit_ts - $entry_ts) / 60));
    $hours_billed = ceil($duration_minutes / 60);
    $rate = $vehicle['vehicle_type'] === '4W' ? RATE_PER_HOUR_4W : RATE_PER_HOUR_2W;
    $amount = max(MIN_CHARGE, $hours_billed * $rate);

    $h = floor($duration_minutes/60); $m = $duration_minutes%60;
    $duration_str = $h > 0 ? "{$h}h {$m}m" : "{$m}m";

    // Update vehicle status
    $stmt = $conn->prepare("UPDATE vehicles SET status='exited', exit_time=?, slot_id=NULL WHERE id=?");
    $stmt->bind_param("si", $exit_time, $vehicle_id);
    $stmt->execute();

    // Free slot
    if ($vehicle['slot_db_id']) {
        $conn->query("UPDATE parking_slots SET status='available' WHERE id=".$vehicle['slot_db_id']);
    }

    // Create billing record
    $payment_time = $payment_status === 'paid' ? $exit_time : null;
    $stmt = $conn->prepare("INSERT INTO billing (vehicle_id, user_id, vehicle_number, slot_number, entry_time, exit_time, duration_minutes, amount, payment_status, payment_time) VALUES (?,?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param("iissssidss", $vehicle_id, $vehicle['user_id'], $vehicle['vehicle_number'], $vehicle['slot_number'], $vehicle['entry_time'], $exit_time, $duration_minutes, $amount, $payment_status, $payment_time);
    $stmt->execute();

    echo json_encode([
        'success' => true,
        'vehicle_number' => $vehicle['vehicle_number'],
        'slot'    => $vehicle['slot_number'],
        'entry_time' => $vehicle['entry_time'],
        'exit_time'  => $exit_time,
        'duration'   => $duration_str,
        'amount'     => number_format($amount, 2),
        'payment_status' => $payment_status,
        'message'    => 'Exit processed successfully.'
    ]);
    exit;
}

echo json_encode(['success'=>false,'message'=>'Invalid action.']);
