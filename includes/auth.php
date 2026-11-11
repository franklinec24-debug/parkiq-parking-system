<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isStaff() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'staff';
}

function isUser() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'user';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ../index.php');
        exit;
    }
}

function requireStaff() {
    requireLogin();
    if (!isStaff()) {
        header('Location: ../user/dashboard.php');
        exit;
    }
}

function requireUser() {
    requireLogin();
    if (!isUser()) {
        header('Location: ../staff/dashboard.php');
        exit;
    }
}
