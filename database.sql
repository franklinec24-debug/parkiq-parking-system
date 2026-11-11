-- ============================================================
-- Vehicle Parking Management System - Database Schema
-- Import this file in phpMyAdmin
-- ============================================================

CREATE DATABASE IF NOT EXISTS parking_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE parking_system;

-- USERS TABLE
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role ENUM('user','staff') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- PARKING SLOTS TABLE
CREATE TABLE IF NOT EXISTS parking_slots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slot_number VARCHAR(10) NOT NULL UNIQUE,
    slot_type ENUM('2W','4W') DEFAULT '4W',
    status ENUM('available','occupied','reserved') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- VEHICLES TABLE
CREATE TABLE IF NOT EXISTS vehicles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    vehicle_number VARCHAR(20) NOT NULL,
    vehicle_type ENUM('2W','4W') DEFAULT '4W',
    owner_name VARCHAR(100),
    slot_id INT,
    entry_time DATETIME,
    exit_time DATETIME,
    status ENUM('parked','exited') DEFAULT 'parked',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (slot_id) REFERENCES parking_slots(id) ON DELETE SET NULL
);

-- BOOKINGS TABLE
CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    slot_id INT NOT NULL,
    vehicle_number VARCHAR(20) NOT NULL,
    booking_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    status ENUM('active','completed','cancelled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (slot_id) REFERENCES parking_slots(id) ON DELETE CASCADE
);

-- BILLING TABLE
CREATE TABLE IF NOT EXISTS billing (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT NOT NULL,
    user_id INT,
    vehicle_number VARCHAR(20),
    slot_number VARCHAR(10),
    entry_time DATETIME,
    exit_time DATETIME,
    duration_minutes INT DEFAULT 0,
    amount DECIMAL(10,2) DEFAULT 0.00,
    payment_status ENUM('paid','pending') DEFAULT 'pending',
    payment_time DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE
);

-- ============================================================
-- SAMPLE DATA
-- Passwords are "password" (bcrypt hashed)
-- staff@parking.com / password  (Staff)
-- user@parking.com  / password  (User)
-- ============================================================
INSERT INTO users (name, email, password, phone, role) VALUES
('Admin Staff', 'staff@parking.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '9876543210', 'staff'),
('Raj Kumar',   'user@parking.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '9123456789', 'user'),
('Priya Sharma','priya@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '9988776655', 'user');

-- Parking Slots
INSERT INTO parking_slots (slot_number, slot_type, status) VALUES
('A01','4W','occupied'),('A02','4W','available'),('A03','4W','available'),
('A04','4W','available'),('A05','4W','reserved'), ('A06','4W','available'),
('A07','4W','available'),('A08','4W','available'),('A09','4W','available'),
('A10','4W','available'),
('B01','2W','available'),('B02','2W','available'),('B03','2W','available'),
('B04','2W','available'),('B05','2W','available');

-- Currently parked vehicle
INSERT INTO vehicles (user_id, vehicle_number, vehicle_type, owner_name, slot_id, entry_time, status)
VALUES (2, 'TN38AB1234', '4W', 'Raj Kumar', 1, DATE_SUB(NOW(), INTERVAL 2 HOUR), 'parked');

-- Exited vehicle (paid)
INSERT INTO vehicles (user_id, vehicle_number, vehicle_type, owner_name, slot_id, entry_time, exit_time, status)
VALUES (2, 'TN38CD5678', '4W', 'Raj Kumar', NULL, DATE_SUB(NOW(), INTERVAL 5 HOUR), DATE_SUB(NOW(), INTERVAL 1 HOUR), 'exited');

INSERT INTO billing (vehicle_id, user_id, vehicle_number, slot_number, entry_time, exit_time, duration_minutes, amount, payment_status, payment_time)
VALUES (2, 2, 'TN38CD5678', 'A02', DATE_SUB(NOW(), INTERVAL 5 HOUR), DATE_SUB(NOW(), INTERVAL 1 HOUR), 240, 80.00, 'paid', DATE_SUB(NOW(), INTERVAL 1 HOUR));

-- Exited vehicle (pending payment)
INSERT INTO vehicles (user_id, vehicle_number, vehicle_type, owner_name, slot_id, entry_time, exit_time, status)
VALUES (3, 'TN38EF9012', '4W', 'Priya Sharma', NULL, DATE_SUB(NOW(), INTERVAL 3 HOUR), DATE_SUB(NOW(), INTERVAL 30 MINUTE), 'exited');

INSERT INTO billing (vehicle_id, user_id, vehicle_number, slot_number, entry_time, exit_time, duration_minutes, amount, payment_status)
VALUES (3, 3, 'TN38EF9012', 'A03', DATE_SUB(NOW(), INTERVAL 3 HOUR), DATE_SUB(NOW(), INTERVAL 30 MINUTE), 150, 60.00, 'pending');

-- Pre-booking
INSERT INTO bookings (user_id, slot_id, vehicle_number, booking_date, start_time, end_time, status)
VALUES (2, 5, 'TN38AB1234', DATE_ADD(CURDATE(), INTERVAL 1 DAY), '10:00:00', '14:00:00', 'active');
