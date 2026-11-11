<?php
require_once '../includes/auth.php';
require_once '../includes/config.php';
header('Content-Type: application/json');

// Basic slot list (for staff/user live grid)
if (!isset($_GET['date'])) {
    $result = $conn->query("SELECT id, slot_number, slot_type, status FROM parking_slots ORDER BY slot_type, slot_number");
    $slots = [];
    while ($r = $result->fetch_assoc()) $slots[] = $r;
    echo json_encode(['success'=>true,'slots'=>$slots]);
    exit;
}

// Booking availability check
$date  = $_GET['date']  ?? '';
$start = $_GET['start'] ?? '';
$end   = $_GET['end']   ?? '';
$type  = in_array($_GET['type'] ?? '', ['4W','2W']) ? $_GET['type'] : '4W';

if (!$date || !$start || !$end) {
    echo json_encode(['success'=>false,'message'=>'Date and time required.','slots'=>[]]);
    exit;
}

if ($start >= $end) {
    echo json_encode(['success'=>false,'message'=>'End time must be after start time.','slots'=>[]]);
    exit;
}

// Get all slots of given type
$result = $conn->query("SELECT id, slot_number, slot_type, status FROM parking_slots WHERE slot_type='$type' ORDER BY slot_number");
$slots = [];
while ($r = $result->fetch_assoc()) {
    // Check if this slot has a conflicting booking
    $slot_id = $r['id'];
    $stmt = $conn->prepare("
        SELECT id FROM bookings
        WHERE slot_id=? AND booking_date=? AND status='active'
        AND start_time < ? AND end_time > ?
    ");
    $stmt->bind_param("isss", $slot_id, $date, $end, $start);
    $stmt->execute();
    $conflict = $stmt->get_result()->num_rows > 0;

    // Also check if currently occupied
    $currently_occupied = ($r['status'] === 'occupied');

    $r['booking_available'] = !$conflict && !$currently_occupied;
    $slots[] = $r;
}

echo json_encode(['success'=>true,'slots'=>$slots]);
