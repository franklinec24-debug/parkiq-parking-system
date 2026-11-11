<?php
require_once '../includes/auth.php';
require_once '../includes/config.php';
requireUser();
$uid = $_SESSION['user_id'];
$bookings = $conn->query("SELECT b.*, ps.slot_number FROM bookings b LEFT JOIN parking_slots ps ON ps.id=b.slot_id WHERE b.user_id=$uid ORDER BY b.booking_date DESC, b.start_time DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>My Bookings — ParkIQ</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="dashboard-wrapper">
  <?php include '../includes/user_sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <div class="flex items-center gap-3">
        <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
        <span class="topbar-title">My Bookings</span>
      </div>
      <div class="topbar-right">
        <a href="booking.php" class="btn btn-primary btn-sm">+ New Booking</a>
      </div>
    </div>
    <div class="page-content">
      <div class="card fade-in">
        <div class="card-header">
          <span class="card-title">📋 Booking History</span>
        </div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr><th>Booking #</th><th>Vehicle No.</th><th>Slot</th><th>Date</th><th>Time Slot</th><th>Status</th><th>Booked On</th><th>Action</th></tr>
            </thead>
            <tbody>
              <?php if ($bookings->num_rows === 0): ?>
                <tr><td colspan="8" class="empty-state">
                  <div class="icon">📅</div><p>No bookings yet. <a href="booking.php" style="color:var(--amber)">Book a slot now!</a></p>
                </td></tr>
              <?php else: while ($b = $bookings->fetch_assoc()): ?>
              <tr>
                <td>#<?= $b['id'] ?></td>
                <td><strong><?= htmlspecialchars($b['vehicle_number']) ?></strong></td>
                <td><strong class="text-amber"><?= $b['slot_number'] ?></strong></td>
                <td><?= date('d M Y', strtotime($b['booking_date'])) ?></td>
                <td><?= date('h:i A', strtotime($b['start_time'])) ?> – <?= date('h:i A', strtotime($b['end_time'])) ?></td>
                <td>
                  <?php $cls=['active'=>'badge-blue','completed'=>'badge-green','cancelled'=>'badge-red']; ?>
                  <span class="badge <?= $cls[$b['status']]??'badge-muted' ?>"><?= $b['status'] ?></span>
                </td>
                <td><?= date('d M Y', strtotime($b['created_at'])) ?></td>
                <td>
                  <?php if ($b['status']==='active'): ?>
                    <button onclick="cancelBooking(<?= $b['id'] ?>)" class="btn btn-danger btn-sm">Cancel</button>
                  <?php else: ?>—<?php endif; ?>
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
async function cancelBooking(id) {
  if (!confirm('Are you sure you want to cancel this booking?')) return;
  const res = await apiCall('../php/booking_handler.php', {action:'cancel', booking_id:id});
  if (res.success) { showNotification('Cancelled','Booking cancelled successfully.','success'); setTimeout(()=>location.reload(),1000); }
  else showNotification('Error', res.message, 'error');
}
</script>
</body>
</html>
