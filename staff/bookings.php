<?php
require_once '../includes/auth.php';
require_once '../includes/config.php';
requireStaff();

$bookings = $conn->query("
  SELECT b.*, u.name AS user_name, u.email, u.phone, ps.slot_number
  FROM bookings b
  LEFT JOIN users u ON u.id = b.user_id
  LEFT JOIN parking_slots ps ON ps.id = b.slot_id
  ORDER BY b.booking_date DESC, b.start_time DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bookings — ParkIQ</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="dashboard-wrapper">
  <?php include '../includes/staff_sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <div class="flex items-center gap-3">
        <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
        <span class="topbar-title">All Bookings</span>
      </div>
    </div>
    <div class="page-content">
      <div class="card fade-in">
        <div class="card-header">
          <span class="card-title">📅 Pre-Bookings</span>
          <div class="search-bar" style="margin:0;width:250px">
            <input type="text" id="searchInput" placeholder="Search booking...">
          </div>
        </div>
        <div class="table-wrap">
          <table id="bookingsTable">
            <thead>
              <tr><th>ID</th><th>User</th><th>Vehicle No.</th><th>Slot</th><th>Date</th><th>Time</th><th>Status</th><th>Action</th></tr>
            </thead>
            <tbody>
              <?php if ($bookings->num_rows === 0): ?>
                <tr><td colspan="8" class="empty-state">No bookings found.</td></tr>
              <?php else: while ($b = $bookings->fetch_assoc()): ?>
                <tr>
                  <td>#<?= $b['id'] ?></td>
                  <td>
                    <strong><?= htmlspecialchars($b['user_name']) ?></strong>
                    <div class="text-muted"><?= htmlspecialchars($b['phone'] ?? '') ?></div>
                  </td>
                  <td><strong><?= htmlspecialchars($b['vehicle_number']) ?></strong></td>
                  <td><strong class="text-amber"><?= $b['slot_number'] ?></strong></td>
                  <td><?= date('d M Y', strtotime($b['booking_date'])) ?></td>
                  <td><?= date('h:i A', strtotime($b['start_time'])) ?> – <?= date('h:i A', strtotime($b['end_time'])) ?></td>
                  <td>
                    <?php
                      $cls = ['active'=>'badge-blue','completed'=>'badge-green','cancelled'=>'badge-red'];
                      echo '<span class="badge '.($cls[$b['status']]??'badge-muted').'">'.$b['status'].'</span>';
                    ?>
                  </td>
                  <td>
                    <?php if ($b['status'] === 'active'): ?>
                    <button onclick="cancelBooking(<?= $b['id'] ?>)" class="btn btn-danger btn-sm">Cancel</button>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endwhile; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<script src="../js/main.js"></script>
<script>
filterTable('searchInput', 'bookingsTable');

async function cancelBooking(id) {
  if (!confirm('Cancel this booking?')) return;
  const res = await apiCall('../php/booking_handler.php', { action: 'cancel', booking_id: id });
  if (res.success) {
    showNotification('Cancelled', 'Booking cancelled and slot freed.', 'success');
    setTimeout(() => location.reload(), 1000);
  } else {
    showNotification('Error', res.message, 'error');
  }
}
</script>
</body>
</html>
