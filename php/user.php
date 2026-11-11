<?php
require_once '../includes/config.php';

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Unauthorized']);
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'get_dashboard':
        getDashboard();
        break;
    case 'get_slots':
        getSlots();
        break;
    case 'create_booking':
        createBooking();
        break;
    case 'get_my_bookings':
        getMyBookings();
        break;
    case 'cancel_booking':
        cancelBooking();
        break;
    case 'get_parking_history':
        getParkingHistory();
        break;
    case 'get_billing_history':
        getBillingHistory();
        break;
    case 'search_vehicle':
        searchVehicle();
        break;
    default:
        jsonResponse(['success' => false, 'message' => 'Invalid action']);
}

function getDashboard() {
    global $conn;
    $total = $conn->query("SELECT COUNT(*) as c FROM parking_slots")->fetch_assoc()['c'];
    $available = $conn->query("SELECT COUNT(*) as c FROM parking_slots WHERE status='available'")->fetch_assoc()['c'];
    $occupied = $conn->query("SELECT COUNT(*) as c FROM parking_slots WHERE status='occupied'")->fetch_assoc()['c'];
    $reserved = $conn->query("SELECT COUNT(*) as c FROM parking_slots WHERE status='reserved'")->fetch_assoc()['c'];

    jsonResponse([
        'success' => true,
        'total' => $total,
        'available' => $available,
        'occupied' => $occupied,
        'reserved' => $reserved
    ]);
}

function getSlots() {
    global $conn;
    $result = $conn->query("SELECT * FROM parking_slots ORDER BY slot_number");
    $slots = [];
    while ($row = $result->fetch_assoc()) $slots[] = $row;
    jsonResponse(['success' => true, 'slots' => $slots]);
}

function createBooking() {
    global $conn;
    $user_id = $_SESSION['user_id'];
    $vehicle_number = strtoupper(trim($_POST['vehicle_number'] ?? ''));
    $booking_date = $_POST['booking_date'] ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    $slot_id = intval($_POST['slot_id'] ?? 0);

    if (!$vehicle_number || !$booking_date || !$start_time || !$end_time || !$slot_id) {
        jsonResponse(['success' => false, 'message' => 'All fields are required']);
    }

    if ($booking_date < date('Y-m-d')) {
        jsonResponse(['success' => false, 'message' => 'Cannot book for a past date']);
    }

    if ($start_time >= $end_time) {
        jsonResponse(['success' => false, 'message' => 'End time must be after start time']);
    }

    // Check slot availability for that time
    $stmt = $conn->prepare("SELECT id FROM bookings WHERE slot_id=? AND booking_date=? AND status='active' AND NOT (end_time <= ? OR start_time >= ?)");
    $stmt->bind_param("isss", $slot_id, $booking_date, $start_time, $end_time);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        jsonResponse(['success' => false, 'message' => 'Slot is already booked for this time']);
    }

    // Check slot status
    $slot = $conn->query("SELECT * FROM parking_slots WHERE id=$slot_id")->fetch_assoc();
    if (!$slot || $slot['status'] === 'occupied') {
        jsonResponse(['success' => false, 'message' => 'Slot not available']);
    }

    // Create booking
    $stmt = $conn->prepare("INSERT INTO bookings (user_id, slot_id, vehicle_number, booking_date, start_time, end_time) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iissss", $user_id, $slot_id, $vehicle_number, $booking_date, $start_time, $end_time);

    if ($stmt->execute()) {
        // Reserve slot if booking is for today
        if ($booking_date === date('Y-m-d')) {
            $conn->query("UPDATE parking_slots SET status='reserved' WHERE id=$slot_id AND status='available'");
        }
        jsonResponse(['success' => true, 'message' => "Slot {$slot['slot_number']} booked successfully!"]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Booking failed. Try again.']);
    }
}

function getMyBookings() {
    global $conn;
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT b.*, ps.slot_number FROM bookings b JOIN parking_slots ps ON b.slot_id=ps.id WHERE b.user_id=? ORDER BY b.booking_date DESC, b.start_time DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $bookings = [];
    while ($row = $result->fetch_assoc()) $bookings[] = $row;
    jsonResponse(['success' => true, 'bookings' => $bookings]);
}

function cancelBooking() {
    global $conn;
    $booking_id = intval($_POST['booking_id'] ?? 0);
    $user_id = $_SESSION['user_id'];
    $b = $conn->query("SELECT slot_id FROM bookings WHERE id=$booking_id AND user_id=$user_id AND status='active'")->fetch_assoc();
    if ($b) {
        $conn->query("UPDATE parking_slots SET status='available' WHERE id={$b['slot_id']} AND status='reserved'");
        $conn->query("UPDATE bookings SET status='cancelled' WHERE id=$booking_id");
        jsonResponse(['success' => true, 'message' => 'Booking cancelled']);
    } else {
        jsonResponse(['success' => false, 'message' => 'Booking not found']);
    }
}

function getParkingHistory() {
    global $conn;
    $user_id = $_SESSION['user_id'];
    $result = $conn->prepare("SELECT v.*, ps.slot_number FROM vehicles v LEFT JOIN parking_slots ps ON v.slot_id=ps.id WHERE v.user_id=? ORDER BY v.created_at DESC LIMIT 20");
    $result->bind_param("i", $user_id);
    $result->execute();
    $r = $result->get_result();
    $history = [];
    while ($row = $r->fetch_assoc()) $history[] = $row;
    jsonResponse(['success' => true, 'history' => $history]);
}

function getBillingHistory() {
    global $conn;
    $user_id = $_SESSION['user_id'];
    $result = $conn->query("SELECT bi.* FROM billing bi 
        JOIN vehicles v ON bi.vehicle_id=v.id 
        WHERE v.user_id=$user_id ORDER BY bi.created_at DESC LIMIT 20");
    $bills = [];
    while ($row = $result->fetch_assoc()) $bills[] = $row;
    jsonResponse(['success' => true, 'bills' => $bills]);
}

function searchVehicle() {
    global $conn;
    $vehicle_number = strtoupper(trim($_GET['vehicle_number'] ?? ''));
    if (!$vehicle_number) {
        jsonResponse(['success' => false, 'message' => 'Vehicle number required']);
    }

    // Current status
    $current = $conn->query("SELECT v.*, ps.slot_number FROM vehicles v LEFT JOIN parking_slots ps ON v.slot_id=ps.id WHERE v.vehicle_number='$vehicle_number' AND v.status='parked'")->fetch_assoc();

    // History
    $result = $conn->query("SELECT v.*, ps.slot_number FROM vehicles v LEFT JOIN parking_slots ps ON v.slot_id=ps.id WHERE v.vehicle_number='$vehicle_number' ORDER BY v.created_at DESC LIMIT 10");
    $history = [];
    while ($row = $result->fetch_assoc()) $history[] = $row;

    jsonResponse(['success' => true, 'current' => $current, 'history' => $history]);
}
?>
