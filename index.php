<?php
require_once 'includes/auth.php';
if (isLoggedIn()) {
    header('Location: ' . (isStaff() ? 'staff/dashboard.php' : 'user/dashboard.php'));
    exit;
}
$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);
$success = $_SESSION['success'] ?? '';
unset($_SESSION['success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ParkIQ — Vehicle Parking Management</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body class="auth-body">

<div class="auth-box fade-in">
  <div class="auth-logo">
    <div class="logo-icon">🅿️</div>
    <div>
      <h1>ParkIQ</h1>
      <span>Parking Management System</span>
    </div>
  </div>

  <?php if ($error): ?>
    <div class="role-note" style="background:var(--red-dim);border-color:rgba(239,68,68,0.3);color:var(--red);">
      ⚠️ <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>
  <?php if ($success): ?>
    <div class="role-note" style="background:var(--green-dim);border-color:rgba(16,185,129,0.2);color:var(--green);">
      ✅ <?= htmlspecialchars($success) ?>
    </div>
  <?php endif; ?>

  <div class="auth-tabs">
    <button class="auth-tab active" data-tab="login">Login</button>
    <button class="auth-tab" data-tab="register">Register</button>
  </div>

  <!-- LOGIN -->
  <div class="auth-section active" id="login">
    <div class="role-note">
      🔑 Demo: <strong>staff@parking.com</strong> or <strong>user@parking.com</strong> — password: <strong>password</strong>
    </div>
    <form action="php/auth_handler.php" method="POST">
      <input type="hidden" name="action" value="login">
      <div class="form-group">
        <label>Email Address</label>
        <input type="email" name="email" placeholder="you@example.com" required>
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" placeholder="••••••••" required>
      </div>
      <button type="submit" class="btn btn-primary">Sign In →</button>
    </form>
    <p class="auth-footer">Don't have an account? <a href="#" onclick="document.querySelector('[data-tab=register]').click()" style="color:var(--amber)">Register</a></p>
  </div>

  <!-- REGISTER -->
  <div class="auth-section" id="register">
    <form action="php/auth_handler.php" method="POST">
      <input type="hidden" name="action" value="register">
      <div class="form-group">
        <label>Full Name</label>
        <input type="text" name="name" placeholder="Raj Kumar" required>
      </div>
      <div class="form-group">
        <label>Email Address</label>
        <input type="email" name="email" placeholder="you@example.com" required>
      </div>
      <div class="form-group">
        <label>Phone Number</label>
        <input type="tel" name="phone" placeholder="9876543210">
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" placeholder="Min. 6 characters" required minlength="6">
      </div>
      <div class="form-group">
        <label>Account Type</label>
        <select name="role">
          <option value="user">User (Vehicle Owner)</option>
          <option value="staff">Staff (Parking Attendant)</option>
        </select>
      </div>
      <button type="submit" class="btn btn-primary">Create Account →</button>
    </form>
    <p class="auth-footer">Already have an account? <a href="#" onclick="document.querySelector('[data-tab=login]').click()" style="color:var(--amber)">Login</a></p>
  </div>
</div>

<script src="js/main.js"></script>
</body>
</html>
