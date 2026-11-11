<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'parking_system');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]));
}

$conn->set_charset('utf8mb4');

// Pricing (Indian Rupees)
define('RATE_PER_HOUR_4W', 20);  // ₹20 per hour for 4-wheelers
define('RATE_PER_HOUR_2W', 10);  // ₹10 per hour for 2-wheelers
define('MIN_CHARGE', 20);         // Minimum charge
