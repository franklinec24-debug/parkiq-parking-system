<?php
require_once '../includes/config.php';

if (!isLoggedIn() || !isStaff()) {
    jsonResponse(['success' => false, 'message' => 'Unauthorized']);
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'get_dashboard':
        getDashboard();
        break;
    case 'vehicle_entry':
        vehicleEntry();
        break;
    case 'vehicle_exit':
        vehicleExit();
        break;
    case 'get_parked_vehicles':
        getParkedVehicles();
        break;
    case 'get_pending_payments':
        getPendingPayments();
        break;
    case 'get_bookings':
        getAllBookings();
        break;
    case 'update_payment':
        updatePayment();
        break;
    case 'cancel_booking':
        cancelBooking();
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
    $pending = $conn->query("SELECT COUNT(*) as c FROM billing WHERE payment_status='pending'")->fetch_assoc()['c'];
    $today_revenue = $conn->query("SELECT COALESCE(SUM(amount),0) as r FROM billing WHERE payment_status='paid' AND DATE(created_at)=CURDATE()")->fetch_assoc()['r'];

    jsonResponse([
        'success' => true,
        'total' => $total,
        'available' => $available,
        'occupied' => $occupied,
        'reserved' => $reserved,
        'pending_payments' => $pending,
        'today_revenue' => $today_revenue
    ]);
}

function vehicleEntry() {
    global $conn;
    $vehicle_number = strtoupper(trim($_POST['vehicle_number'] ?? ''));

    if (!$vehicle_number) {
        jsonResponse(['success' => false, 'message' => 'Vehicle number required']);
    }

    // Check if already parked
    $check = $conn->prepare("SELECT id FROM vehicles WHERE vehicle_number=? AND status='parked'");
    $check->bind_param("s", $vehicle_number);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        jsonResponse(['success' => false, 'message' => 'Vehicle is already parked']);
    }

    // Check for active booking today
    $booking_slot_id = null;
    $booking_check = $conn->prepare("SELECT id, slot_id FROM bookings WHERE vehicle_number=? AND booking_date=CURDATE() AND status='active' AND start_time <= TIME(NOW()) AND end_time >= TIME(NOW())");
    $booking_check->bind_param("s", $vehicle_number);
    $booking_check->execute();
    $booking_result = $booking_check->get_result()->fetch_assoc();
    if ($booking_result) {
        $booking_slot_id = $booking_result['slot_id'];
        // Update booking status
        $upd = $conn->prepare("UPDATE bookings SET status='completed' WHERE id=?");
        $upd->bind_param("i", $booking_result['id']);
        $upd->execute();
    }

    // Find available slot
    if ($booking_slot_id) {
        $slot = $conn->query("SELECT id, slot_number FROM parking_slots WHERE id=$booking_slot_id AND status='reserved'")->fetch_assoc();
    } else {
        $slot = $conn->query("SELECT id, slot_number FROM parking_slots WHERE status='available' LIMIT 1")->fetch_assoc();
    }

    if (!$slot) {
        jsonResponse(['success' => false, 'message' => 'No available parking slots']);
    }

    $entry_time = date('Y-m-d H:i:s');

    // Insert vehicle record
    $stmt = $conn->prepare("INSERT INTO vehicles (vehicle_number, slot_id, entry_time, status) VALUES (?, ?, ?, 'parked')");
    $stmt->bind_param("sis", $vehicle_number, $slot['id'], $entry_time);
    $stmt->execute();

    // Update slot status
    $conn->query("UPDATE parking_slots SET status='occupied' WHERE id={$slot['id']}");

    jsonResponse([
        'success' => true,
        'message' => "Vehicle {$vehicle_number} parked at slot {$slot['slot_number']}",
        'slot' => $slot['slot_number'],
        'entry_time' => $entry_time
    ]);
}

function vehicleExit() {
    global $conn;
    $vehicle_id = intval($_POST['vehicle_id'] ?? 0);
    $payment_status = $_POST['payment_status'] ?? 'pending';

    if (!$vehicle_id) {
        jsonResponse(['success' => false, 'message' => 'Vehicle ID required']);
    }

    $stmt = $conn->prepare("SELECT v.*, ps.slot_number FROM vehicles v JOIN parking_slots ps ON v.slot_id=ps.id WHERE v.id=? AND v.status='parked'");
    $stmt->bind_param("i", $vehicle_id);
    $stmt->execute();
    $vehicle = $stmt->get_result()->fetch_assoc();

    if (!$vehicle) {
        jsonResponse(['success' => false, 'message' => 'Vehicle not found or already exited']);
    }

    $exit_time = date('Y-m-d H:i:s');
    $duration = (strtotime($exit_time) - strtotime($vehicle['entry_time'])) / 60; // minutes
    $amount = calculateFee($vehicle['entry_time'], $exit_time);

    // Update vehicle
    $stmt = $conn->prepare("UPDATE vehicles SET exit_time=?, status='exited' WHERE id=?");
    $stmt->bind_param("si", $exit_time, $vehicle_id);
    $stmt->execute();

    // Free slot
    $conn->query("UPDATE parking_slots SET status='available' WHERE id={$vehicle['slot_id']}");

    // Create bill
    $stmt = $conn->prepare("INSERT INTO billing (vehicle_id, vehicle_number, slot_number, entry_time, exit_time, duration_minutes, amount, payment_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $dur = intval($duration);
    $stmt->bind_param("issssiis", $vehicle_id, $vehicle['vehicle_number'], $vehicle['slot_number'], $vehicle['entry_time'], $exit_time, $dur, $amount, $payment_status);
    $stmt->execute();

    jsonResponse([
        'success' => true,
        'message' => "Exit processed for {$vehicle['vehicle_number']}",
        'vehicle_number' => $vehicle['vehicle_number'],
        'slot' => $vehicle['slot_number'],
        'entry_time' => $vehicle['entry_time'],
        'exit_time' => $exit_time,
        'duration_minutes' => $dur,
        'amount' => $amount,
        'payment_status' => $payment_status
    ]);
}

function getParkedVehicles() {
    global $conn;
    $search = $conn->real_escape_string($_GET['search'] ?? '');
    $where = $search ? "AND v.vehicle_number LIKE '%$search%'" : '';

    $result = $conn->query("SELECT v.id, v.vehicle_number, v.entry_time, ps.slot_number 
        FROM vehicles v JOIN parking_slots ps ON v.slot_id=ps.id 
        WHERE v.status='parked' $where ORDER BY v.entry_time DESC");

    $vehicles = [];
    while ($row = $result->fetch_assoc()) {
        // Calculate duration
        $mins = (time() - strtotime($row['entry_time'])) / 60;
        $row['duration'] = intval($mins);
        $row['estimated_fee'] = calculateFee($row['entry_time'], date('Y-m-d H:i:s'));
        $vehicles[] = $row;
    }
    jsonResponse(['success' => true, 'vehicles' => $vehicles]);
}

function getPendingPayments() {
    global $conn;
    $result = $conn->query("SELECT * FROM billing WHERE payment_status='pending' ORDER BY created_at DESC");
    $bills = [];
    while ($row = $result->fetch_assoc()) $bills[] = $row;
    jsonResponse(['success' => true, 'bills' => $bills]);
}

function getAllBookings() {
    global $conn;
    $result = $conn->query("SELECT b.*, u.name, u.phone, ps.slot_number 
        FROM bookings b 
        JOIN users u ON b.user_id=u.id 
        JOIN parking_slots ps ON b.slot_id=ps.id 
        ORDER BY b.booking_date DESC, b.start_time DESC LIMIT 50");
    $bookings = [];
    while ($row = $result->fetch_assoc()) $bookings[] = $row;
    jsonResponse(['success' => true, 'bookings' => $bookings]);
}

function updatePayment() {
    global $conn;
    $bill_id = intval($_POST['bill_id'] ?? 0);
    $stmt = $conn->prepare("UPDATE billing SET payment_status='paid' WHERE id=?");
    $stmt->bind_param("i", $bill_id);
    if ($stmt->execute()) {
        jsonResponse(['success' => true, 'message' => 'Payment marked as paid']);
    } else {
        jsonResponse(['success' => false, 'message' => 'Update failed']);
    }
}

function cancelBooking() {
    global $conn;
    $booking_id = intval($_POST['booking_id'] ?? 0);
    // Free the slot
    $b = $conn->query("SELECT slot_id FROM bookings WHERE id=$booking_id")->fetch_assoc();
    if ($b) {
        $conn->query("UPDATE parking_slots SET status='available' WHERE id={$b['slot_id']} AND status='reserved'");
        $conn->query("UPDATE bookings SET status='cancelled' WHERE id=$booking_id");
        jsonResponse(['success' => true, 'message' => 'Booking cancelled']);
    } else {
        jsonResponse(['success' => false, 'message' => 'Booking not found']);
    }
}
?>
