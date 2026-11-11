<?php
require_once '../includes/auth.php';
require_once '../includes/config.php';
requireStaff();

// Summary stats
$stats = [];
$r = $conn->query("SELECT COUNT(*) AS total FROM parking_slots");
$stats['total'] = $r->fetch_assoc()['total'];

$r = $conn->query("SELECT COUNT(*) AS cnt FROM parking_slots WHERE status='available'");
$stats['available'] = $r->fetch_assoc()['cnt'];

$r = $conn->query("SELECT COUNT(*) AS cnt FROM parking_slots WHERE status='occupied'");
$stats['occupied'] = $r->fetch_assoc()['cnt'];

$r = $conn->query("SELECT COUNT(*) AS cnt FROM parking_slots WHERE status='reserved'");
$stats['reserved'] = $r->fetch_assoc()['cnt'];

$r = $conn->query("SELECT COUNT(*) AS cnt FROM billing WHERE payment_status='pending'");
$stats['pending'] = $r->fetch_assoc()['cnt'];

$r = $conn->query("SELECT COALESCE(SUM(amount),0) AS total FROM billing WHERE payment_status='paid' AND DATE(payment_time)=CURDATE()");
$stats['today_revenue'] = $r->fetch_assoc()['total'];

// Currently parked
$parked = $conn->query("
  SELECT v.id, v.vehicle_number, v.vehicle_type, v.owner_name, v.entry_time,
         ps.slot_number, u.name AS user_name
  FROM vehicles v
  LEFT JOIN parking_slots ps ON ps.id = v.slot_id
  LEFT JOIN users u ON u.id = v.user_id
  WHERE v.status='parked'
  ORDER BY v.entry_time DESC LIMIT 10
");

// Recent exits pending payment
$pending_bills = $conn->query("
  SELECT b.id, b.vehicle_number, b.slot_number, b.amount, b.entry_time, b.exit_time, b.duration_minutes
  FROM billing b
  WHERE b.payment_status='pending'
  ORDER BY b.created_at DESC LIMIT 10
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Staff Dashboard — ParkIQ</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="dashboard-wrapper">
  <?php include '../includes/staff_sidebar.php'; ?>

  <div class="main-content">
    <div class="topbar">
      <div class="flex items-center gap-3">
        <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
        <span class="topbar-title">Dashboard</span>
      </div>
      <div class="topbar-right">
        <div class="live-badge"><div class="live-dot"></div> Live</div>
        <span class="text-muted" id="clock"></span>
      </div>
    </div>

    <div class="page-content">
      <!-- STATS -->
      <div class="stats-grid fade-in">
        <div class="stat-card amber">
          <div class="stat-label">Total Slots</div>
          <div class="stat-value"><?= $stats['total'] ?></div>
          <div class="stat-sub">All parking spaces</div>
        </div>
        <div class="stat-card green">
          <div class="stat-label">Available</div>
          <div class="stat-value"><?= $stats['available'] ?></div>
          <div class="stat-sub">Ready for parking</div>
        </div>
        <div class="stat-card red">
          <div class="stat-label">Occupied</div>
          <div class="stat-value"><?= $stats['occupied'] ?></div>
          <div class="stat-sub">Currently parked</div>
        </div>
        <div class="stat-card blue">
          <div class="stat-label">Reserved</div>
          <div class="stat-value"><?= $stats['reserved'] ?></div>
          <div class="stat-sub">Pre-booked slots</div>
        </div>
        <div class="stat-card amber">
          <div class="stat-label">Pending Payment</div>
          <div class="stat-value"><?= $stats['pending'] ?></div>
          <div class="stat-sub">Awaiting collection</div>
        </div>
        <div class="stat-card green">
          <div class="stat-label">Today's Revenue</div>
          <div class="stat-value" style="font-size:28px">₹<?= number_format($stats['today_revenue'], 0) ?></div>
          <div class="stat-sub">Collected today</div>
        </div>
      </div>

      <div class="grid-2">
        <!-- QUICK ACTIONS -->
        <div class="card fade-in">
          <div class="card-header">
            <span class="card-title">🚀 Quick Actions</span>
          </div>
          <div class="card-body" style="display:flex;flex-direction:column;gap:10px">
            <a href="vehicle_entry.php" class="btn btn-primary">🚗 New Vehicle Entry</a>
            <a href="vehicle_exit.php" class="btn btn-secondary">🚪 Process Vehicle Exit</a>
            <a href="payments.php" class="btn btn-secondary">💰 Manage Payments</a>
            <a href="bookings.php" class="btn btn-secondary">📅 View All Bookings</a>
          </div>
        </div>

        <!-- SLOT FILL RATE -->
        <div class="card fade-in">
          <div class="card-header">
            <span class="card-title">📊 Occupancy Rate</span>
          </div>
          <div class="card-body">
            <?php
            $pct = $stats['total'] > 0 ? round(($stats['occupied'] / $stats['total']) * 100) : 0;
            $color = $pct >= 90 ? '#ef4444' : ($pct >= 60 ? '#f59e0b' : '#10b981');
            ?>
            <div style="text-align:center;padding:10px 0">
              <div style="font-size:64px;font-family:'Rajdhani',sans-serif;font-weight:700;color:<?= $color ?>"><?= $pct ?>%</div>
              <div class="text-muted"><?= $stats['occupied'] ?> of <?= $stats['total'] ?> slots occupied</div>
              <div style="margin-top:16px;background:var(--bg-surface);border-radius:20px;height:12px;overflow:hidden">
                <div style="height:100%;width:<?= $pct ?>%;background:<?= $color ?>;border-radius:20px;transition:width 0.5s"></div>
              </div>
              <?php if ($pct >= 90): ?>
                <div class="badge badge-red" style="margin-top:10px">⚠️ Parking Almost Full!</div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

      <!-- PARKED VEHICLES -->
      <div class="card fade-in">
        <div class="card-header">
          <span class="card-title">🅿️ Currently Parked Vehicles</span>
          <a href="parked.php" class="btn btn-secondary btn-sm">View All</a>
        </div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Vehicle No.</th>
                <th>Type</th>
                <th>Owner</th>
                <th>Slot</th>
                <th>Entry Time</th>
                <th>Duration</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($parked->num_rows === 0): ?>
                <tr><td colspan="7" class="empty-state">No vehicles currently parked.</td></tr>
              <?php else: while ($row = $parked->fetch_assoc()): ?>
                <?php
                  $mins = round((time() - strtotime($row['entry_time'])) / 60);
                  $h = floor($mins / 60); $m = $mins % 60;
                  $dur = $h > 0 ? "{$h}h {$m}m" : "{$m}m";
                ?>
                <tr>
                  <td><strong><?= htmlspecialchars($row['vehicle_number']) ?></strong></td>
                  <td><span class="badge badge-blue"><?= $row['vehicle_type'] ?></span></td>
                  <td><?= htmlspecialchars($row['owner_name'] ?: $row['user_name'] ?: '—') ?></td>
                  <td><strong class="text-amber"><?= $row['slot_number'] ?></strong></td>
                  <td><?= date('d M, h:i A', strtotime($row['entry_time'])) ?></td>
                  <td><?= $dur ?></td>
                  <td><a href="vehicle_exit.php?vehicle_id=<?= $row['id'] ?>" class="btn btn-danger btn-sm">Exit</a></td>
                </tr>
              <?php endwhile; endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- PENDING PAYMENTS -->
      <div class="card fade-in">
        <div class="card-header">
          <span class="card-title">💰 Pending Payments</span>
          <a href="payments.php" class="btn btn-secondary btn-sm">View All</a>
        </div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Vehicle No.</th>
                <th>Slot</th>
                <th>Duration</th>
                <th>Amount</th>
                <th>Exit Time</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($pending_bills->num_rows === 0): ?>
                <tr><td colspan="6" class="empty-state">No pending payments. 🎉</td></tr>
              <?php else: while ($row = $pending_bills->fetch_assoc()): ?>
                <?php $h = floor($row['duration_minutes']/60); $m = $row['duration_minutes']%60; ?>
                <tr>
                  <td><strong><?= htmlspecialchars($row['vehicle_number']) ?></strong></td>
                  <td><?= $row['slot_number'] ?></td>
                  <td><?= $h > 0 ? "{$h}h {$m}m" : "{$m}m" ?></td>
                  <td><strong class="text-amber">₹<?= number_format($row['amount'], 2) ?></strong></td>
                  <td><?= date('d M, h:i A', strtotime($row['exit_time'])) ?></td>
                  <td>
                    <button onclick="markPaid(<?= $row['id'] ?>)" class="btn btn-success btn-sm">Mark Paid</button>
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
// Clock
function updateClock() {
  document.getElementById('clock').textContent = new Date().toLocaleTimeString('en-IN', {hour:'2-digit',minute:'2-digit',second:'2-digit'});
}
setInterval(updateClock, 1000);
updateClock();

async function markPaid(billId) {
  if (!confirm('Mark this payment as paid?')) return;
  const res = await apiCall('../php/payment_handler.php', { action: 'mark_paid', bill_id: billId });
  if (res.success) {
    showNotification('Payment Collected', res.message, 'success');
    setTimeout(() => location.reload(), 1200);
  } else {
    showNotification('Error', res.message, 'error');
  }
}

// Auto refresh every 30s
setTimeout(() => location.reload(), 30000);
</script>
</body>
</html>
