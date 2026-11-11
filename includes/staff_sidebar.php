<?php
if (!isset($_SESSION['user_id'])) return;
$name = $_SESSION['name'] ?? 'Staff';
$initial = strtoupper(substr($name, 0, 1));
?>
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <div class="logo-icon">🅿️</div>
    <div>
      <h2>ParkIQ</h2>
      <span>Staff Panel</span>
    </div>
  </div>

  <nav class="sidebar-nav">
    <span class="nav-section-label">Overview</span>
    <a href="dashboard.php" class="nav-item">
      <span class="nav-icon">📊</span> Dashboard
    </a>

    <span class="nav-section-label">Operations</span>
    <a href="vehicle_entry.php" class="nav-item">
      <span class="nav-icon">🚗</span> Vehicle Entry
    </a>
    <a href="vehicle_exit.php" class="nav-item">
      <span class="nav-icon">🚪</span> Vehicle Exit
    </a>
    <a href="parked.php" class="nav-item">
      <span class="nav-icon">🅿️</span> Parked Vehicles
    </a>

    <span class="nav-section-label">Finance</span>
    <a href="payments.php" class="nav-item">
      <span class="nav-icon">💰</span> Payments
    </a>

    <span class="nav-section-label">Bookings</span>
    <a href="bookings.php" class="nav-item">
      <span class="nav-icon">📅</span> All Bookings
    </a>

    <span class="nav-section-label">Slots</span>
    <a href="slots.php" class="nav-item">
      <span class="nav-icon">🔲</span> Slot Overview
    </a>
  </nav>

  <div class="sidebar-footer">
    <div class="user-card">
      <div class="user-avatar"><?= $initial ?></div>
      <div class="user-info">
        <div class="user-name"><?= htmlspecialchars($name) ?></div>
        <div class="user-role">Staff</div>
      </div>
    </div>
    <a href="../php/auth_handler.php?action=logout" class="btn-logout"
       onclick="event.preventDefault(); document.getElementById('logoutForm').submit();">
      🚪 Logout
    </a>
    <form id="logoutForm" action="../php/auth_handler.php" method="POST" style="display:none">
      <input type="hidden" name="action" value="logout">
    </form>
  </div>
</aside>
