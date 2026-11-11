<?php
require_once '../includes/auth.php';
require_once '../includes/config.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

// ---- CREATE BOOKING ----
if ($action === 'create') {
    $user_id        = intval($_SESSION['user_id'] ?? 0);
    $vehicle_number = strtoupper(trim($input['vehicle_number'] ?? ''));
    $slot_id        = intval($input['slot_id'] ?? 0);
    $booking_date   = $input['booking_date'] ?? '';
    $start_time     = $input['start_time'] ?? '';
    $end_time       = $input['end_time'] ?? '';

    if (!$user_id || !$vehicle_number || !$slot_id || !$booking_date || !$start_time || !$end_time) {
        echo json_encode(['success'=>false,'message'=>'All fields are required.']);
        exit;
    }

    if ($start_time >= $end_time) {
        echo json_encode(['success'=>false,'message'=>'End time must be after start time.']);
        exit;
    }

    // Check past date
    if ($booking_date < date('Y-m-d')) {
        echo json_encode(['success'=>false,'message'=>'Cannot book for a past date.']);
        exit;
    }

    // Check conflict
    $stmt = $conn->prepare("
        SELECT id FROM bookings
        WHERE slot_id=? AND booking_date=? AND status='active'
        AND start_time < ? AND end_time > ?
    ");
    $stmt->bind_param("isss", $slot_id, $booking_date, $end_time, $start_time);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode(['success'=>false,'message'=>'This slot is already booked for the selected time.']);
        exit;
    }

    // Check slot occupied physically
    $slot_row = $conn->query("SELECT slot_number, status FROM parking_slots WHERE id=$slot_id")->fetch_assoc();
    if (!$slot_row) {
        echo json_encode(['success'=>false,'message'=>'Invalid slot selected.']);
        exit;
    }
    if ($slot_row['status'] === 'occupied') {
        echo json_encode(['success'=>false,'message'=>'This slot is currently occupied.']);
        exit;
    }

    // Insert booking
    $stmt = $conn->prepare("INSERT INTO bookings (user_id, slot_id, vehicle_number, booking_date, start_time, end_time, status) VALUES (?,?,?,?,?,?,'active')");
    $stmt->bind_param("iissss", $user_id, $slot_id, $vehicle_number, $booking_date, $start_time, $end_time);

    if ($stmt->execute()) {
        $booking_id = $conn->insert_id;

        // Mark slot as reserved if booking is for today and current time overlaps
        $now = date('H:i:s');
        if ($booking_date === date('Y-m-d') && $start_time <= $now && $end_time >= $now) {
            $conn->query("UPDATE parking_slots SET status='reserved' WHERE id=$slot_id AND status='available'");
        }

        echo json_encode([
            'success'    => true,
            'booking_id' => $booking_id,
            'slot'       => $slot_row['slot_number'],
            'message'    => 'Booking confirmed!'
        ]);
    } else {
        echo json_encode(['success'=>false,'message'=>'Failed to create booking.']);
    }
    exit;
}

// ---- CANCEL BOOKING ----
if ($action === 'cancel') {
    $booking_id = intval($input['booking_id'] ?? 0);
    $user_id    = intval($_SESSION['user_id'] ?? 0);

    if (!$booking_id) {
        echo json_encode(['success'=>false,'message'=>'Invalid booking.']);
        exit;
    }

    // Get booking
    $role = $_SESSION['role'] ?? 'user';
    $where = ($role === 'staff') ? "id=$booking_id" : "id=$booking_id AND user_id=$user_id";
    $booking = $conn->query("SELECT * FROM bookings WHERE $where AND status='active'")->fetch_assoc();

    if (!$booking) {
        echo json_encode(['success'=>false,'message'=>'Booking not found or already cancelled.']);
        exit;
    }

    // Cancel booking
    $conn->query("UPDATE bookings SET status='cancelled' WHERE id=$booking_id");

    // Free slot if it was reserved for this booking
    // Check if any other active booking still needs this slot reserved
    $slot_id = $booking['slot_id'];
    $now = date('Y-m-d H:i:s');
    $other = $conn->query("
        SELECT id FROM bookings
        WHERE slot_id=$slot_id AND status='active'
        AND booking_date=CURDATE()
        AND CONCAT(booking_date,' ',end_time) >= NOW()
    ");
    if ($other->num_rows === 0) {
        $conn->query("UPDATE parking_slots SET status='available' WHERE id=$slot_id AND status='reserved'");
    }

    echo json_encode(['success'=>true,'message'=>'Booking cancelled successfully.']);
    exit;
}

echo json_encode(['success'=>false,'message'=>'Invalid action.']);
