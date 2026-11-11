<?php
require_once '../includes/config.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'login':
        handleLogin();
        break;
    case 'signup':
        handleSignup();
        break;
    case 'logout':
        handleLogout();
        break;
    default:
        jsonResponse(['success' => false, 'message' => 'Invalid action']);
}

function handleLogin() {
    global $conn;
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'user';

    if (!$email || !$password) {
        jsonResponse(['success' => false, 'message' => 'Email and password are required']);
    }

    $stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email = ? AND role = ?");
    $stmt->bind_param("ss", $email, $role);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user || !password_verify($password, $user['password'])) {
        jsonResponse(['success' => false, 'message' => 'Invalid email or password']);
    }

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];

    $redirect = $user['role'] === 'staff' ? 'staff/dashboard.php' : 'user/dashboard.php';
    jsonResponse(['success' => true, 'redirect' => $redirect, 'role' => $user['role']]);
}

function handleSignup() {
    global $conn;
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $role = $_POST['role'] ?? 'user';

    if (!$name || !$email || !$password) {
        jsonResponse(['success' => false, 'message' => 'All fields are required']);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(['success' => false, 'message' => 'Invalid email format']);
    }

    // Check duplicate
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        jsonResponse(['success' => false, 'message' => 'Email already registered']);
    }

    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, phone, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $name, $email, $hashed, $phone, $role);

    if ($stmt->execute()) {
        jsonResponse(['success' => true, 'message' => 'Account created! Please login.']);
    } else {
        jsonResponse(['success' => false, 'message' => 'Registration failed. Try again.']);
    }
}

function handleLogout() {
    session_destroy();
    jsonResponse(['success' => true, 'redirect' => '../index.php']);
}
?>
