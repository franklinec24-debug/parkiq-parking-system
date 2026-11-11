<?php
require_once '../includes/auth.php';
require_once '../includes/config.php';

$action = $_POST['action'] ?? '';

if ($action === 'login') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $_SESSION['error'] = 'Please fill in all fields.';
        header('Location: ../index.php');
        exit;
    }

    $stmt = $conn->prepare("SELECT id, name, email, password, phone, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name']    = $user['name'];
        $_SESSION['email']   = $user['email'];
        $_SESSION['phone']   = $user['phone'];
        $_SESSION['role']    = $user['role'];

        if ($user['role'] === 'staff') {
            header('Location: ../staff/dashboard.php');
        } else {
            header('Location: ../user/dashboard.php');
        }
        exit;
    } else {
        $_SESSION['error'] = 'Invalid email or password.';
        header('Location: ../index.php');
        exit;
    }
}

if ($action === 'register') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = in_array($_POST['role'] ?? '', ['user','staff']) ? $_POST['role'] : 'user';

    if (empty($name) || empty($email) || empty($password)) {
        $_SESSION['error'] = 'Please fill in all required fields.';
        header('Location: ../index.php');
        exit;
    }

    if (strlen($password) < 6) {
        $_SESSION['error'] = 'Password must be at least 6 characters.';
        header('Location: ../index.php');
        exit;
    }

    // Check existing email
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $_SESSION['error'] = 'Email already registered. Please login.';
        header('Location: ../index.php');
        exit;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, phone, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $name, $email, $hash, $phone, $role);

    if ($stmt->execute()) {
        $_SESSION['success'] = 'Account created! Please login.';
    } else {
        $_SESSION['error'] = 'Registration failed. Please try again.';
    }
    header('Location: ../index.php');
    exit;
}

if ($action === 'logout') {
    session_destroy();
    header('Location: ../index.php');
    exit;
}

header('Location: ../index.php');
exit;
