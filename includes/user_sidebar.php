<?php
if (!isset($_SESSION['user_id'])) return;
$name = $_SESSION['name'] ?? 'User';
$initial = strtoupper(substr($name, 0, 1));
?>
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <div class="logo-icon">🅿️</div>
    <div>
      <h2>ParkIQ</h2>
      <span>User Panel</span>
    </div>
  </div>

  <nav class="sidebar-nav">
    <span class="nav-section-label">Overview</span>
    <a href="dashboard.php" class="nav-item">
      <span class="nav-icon">🏠</span> Dashboard
    </a>

    <span class="nav-section-label">Parking</span>
    <a href="history.php" class="nav-item">
      <span class="nav-icon">🕐</span> Parking History
    </a>
    <a href="billing.php" class="nav-item">
      <span class="nav-icon">💳</span> Billing & Payments
    </a>

    <span class="nav-section-label">Bookings</span>
    <a href="booking.php" class="nav-item">
      <span class="nav-icon">📅</span> Pre-Book Slot
    </a>
    <a href="my_bookings.php" class="nav-item">
      <span class="nav-icon">📋</span> My Bookings
    </a>

    <span class="nav-section-label">Slots</span>
    <a href="slots.php" class="nav-item">
      <span class="nav-icon">🔲</span> Slot Status
    </a>
  </nav>

  <div class="sidebar-footer">
    <div class="user-card">
      <div class="user-avatar"><?= $initial ?></div>
      <div class="user-info">
        <div class="user-name"><?= htmlspecialchars($name) ?></div>
        <div class="user-role">User</div>
      </div>
    </div>
    <a href="#" class="btn-logout"
       onclick="event.preventDefault(); document.getElementById('logoutForm').submit();">
      🚪 Logout
    </a>
    <form id="logoutForm" action="../php/auth_handler.php" method="POST" style="display:none">
      <input type="hidden" name="action" value="logout">
    </form>
  </div>
</aside>
