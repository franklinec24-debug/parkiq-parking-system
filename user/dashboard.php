<?php
require_once '../includes/auth.php';
require_once '../includes/config.php';
requireUser();
$uid = $_SESSION['user_id'];

// Slot stats
$total   = $conn->query("SELECT COUNT(*) c FROM parking_slots")->fetch_assoc()['c'];
$avail   = $conn->query("SELECT COUNT(*) c FROM parking_slots WHERE status='available'")->fetch_assoc()['c'];
$occup   = $conn->query("SELECT COUNT(*) c FROM parking_slots WHERE status='occupied'")->fetch_assoc()['c'];

// My active parking
$active = $conn->query("SELECT v.*, ps.slot_number FROM vehicles v LEFT JOIN parking_slots ps ON ps.id=v.slot_id WHERE v.user_id=$uid AND v.status='parked' LIMIT 1")->fetch_assoc();

// My pending bills
$pending_amt = $conn->query("SELECT COALESCE(SUM(amount),0) t FROM billing WHERE user_id=$uid AND payment_status='pending'")->fetch_assoc()['t'];

// Active bookings
$bookings = $conn->query("SELECT b.*, ps.slot_number FROM bookings b LEFT JOIN parking_slots ps ON ps.id=b.slot_id WHERE b.user_id=$uid AND b.status='active' ORDER BY b.booking_date ASC LIMIT 5");

// Recent history
$history = $conn->query("SELECT v.*, ps.slot_number FROM vehicles v LEFT JOIN parking_slots ps ON ps.id=v.slot_id WHERE v.user_id=$uid ORDER BY v.created_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Dashboard — ParkIQ</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="dashboard-wrapper">
  <?php include '../includes/user_sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <div class="flex items-center gap-3">
        <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
        <span class="topbar-title">My Dashboard</span>
      </div>
      <div class="topbar-right">
        <div class="live-badge"><div class="live-dot"></div>Live</div>
        <span class="text-muted" id="clock"></span>
      </div>
    </div>
    <div class="page-content">

      <!-- Profile -->
      <div class="profile-card fade-in">
        <div class="profile-avatar"><?= strtoupper(substr($_SESSION['name'],0,1)) ?></div>
        <div class="profile-meta">
          <h2><?= htmlspecialchars($_SESSION['name']) ?></h2>
          <p><?= htmlspecialchars($_SESSION['email']) ?></p>
          <p><?= htmlspecialchars($_SESSION['phone'] ?? '') ?></p>
          <span class="role-chip">Vehicle Owner</span>
        </div>
      </div>

      <!-- Stats -->
      <div class="stats-grid fade-in">
        <div class="stat-card amber">
          <div class="stat-label">Total Slots</div>
          <div class="stat-value"><?= $total ?></div>
          <div class="stat-sub">Parking capacity</div>
        </div>
        <div class="stat-card green">
          <div class="stat-label">Available Now</div>
          <div class="stat-value"><?= $avail ?></div>
          <div class="stat-sub">Ready for parking</div>
        </div>
        <div class="stat-card red">
          <div class="stat-label">Occupied</div>
          <div class="stat-value"><?= $occup ?></div>
          <div class="stat-sub">Currently in use</div>
        </div>
        <div class="stat-card <?= $pending_amt > 0 ? 'amber' : 'green' ?>">
          <div class="stat-label">Pending Payment</div>
          <div class="stat-value" style="font-size:26px">₹<?= number_format($pending_amt, 0) ?></div>
          <div class="stat-sub"><?= $pending_amt > 0 ? 'Dues remaining' : 'All clear!' ?></div>
        </div>
      </div>

      <!-- Active Parking Alert -->
      <?php if ($active): ?>
      <?php $mins = round((time()-strtotime($active['entry_time']))/60); $h=floor($mins/60); $m=$mins%60; $est=max(20,ceil($mins/60)*20); ?>
      <div class="card fade-in" style="border-color:var(--amber);border-left:3px solid var(--amber)">
        <div class="card-header">
          <span class="card-title text-amber">🚗 Vehicle Currently Parked</span>
          <span class="badge badge-green">Active</span>
        </div>
        <div class="card-body">
          <div class="receipt">
            <div class="receipt-row"><span>Vehicle No.</span><strong><?= htmlspecialchars($active['vehicle_number']) ?></strong></div>
            <div class="receipt-row"><span>Slot</span><strong class="text-amber"><?= $active['slot_number'] ?></strong></div>
            <div class="receipt-row"><span>Entry Time</span><strong><?= date('d M Y, h:i A', strtotime($active['entry_time'])) ?></strong></div>
            <div class="receipt-row"><span>Duration</span><strong><?= $h>0?"{$h}h {$m}m":"{$m}m" ?></strong></div>
            <div class="receipt-row total"><span>Estimated Bill</span><strong>₹<?= $est ?></strong></div>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <div class="grid-2">
        <!-- Active Bookings -->
        <div class="card fade-in">
          <div class="card-header">
            <span class="card-title">📅 Upcoming Bookings</span>
            <a href="booking.php" class="btn btn-primary btn-sm">+ New Booking</a>
          </div>
          <div class="card-body">
            <?php if ($bookings->num_rows === 0): ?>
              <div class="empty-state"><div class="icon">📅</div><p>No upcoming bookings.</p><a href="booking.php" class="btn btn-primary btn-sm" style="margin-top:10px">Book a Slot</a></div>
            <?php else: while ($b = $bookings->fetch_assoc()): ?>
            <div style="background:var(--bg-surface);border-radius:10px;padding:14px;margin-bottom:10px">
              <div class="flex items-center" style="justify-content:space-between;margin-bottom:6px">
                <strong class="text-amber"><?= htmlspecialchars($b['vehicle_number']) ?></strong>
                <span class="badge badge-blue">Slot <?= $b['slot_number'] ?></span>
              </div>
              <div style="font-size:13px;color:var(--text-secondary)">
                📅 <?= date('d M Y', strtotime($b['booking_date'])) ?> &nbsp;
                🕐 <?= date('h:i A', strtotime($b['start_time'])) ?> – <?= date('h:i A', strtotime($b['end_time'])) ?>
              </div>
              <button onclick="cancelBooking(<?= $b['id'] ?>)" class="btn btn-danger btn-sm" style="margin-top:8px">Cancel</button>
            </div>
            <?php endwhile; endif; ?>
          </div>
        </div>

        <!-- Recent History -->
        <div class="card fade-in">
          <div class="card-header">
            <span class="card-title">🕐 Recent Parking</span>
            <a href="history.php" class="btn btn-secondary btn-sm">View All</a>
          </div>
          <div class="card-body">
            <?php if ($history->num_rows === 0): ?>
              <div class="empty-state"><div class="icon">🚗</div><p>No parking history.</p></div>
            <?php else: while ($v = $history->fetch_assoc()): ?>
            <div style="background:var(--bg-surface);border-radius:10px;padding:12px;margin-bottom:8px;display:flex;justify-content:space-between;align-items:center">
              <div>
                <strong><?= htmlspecialchars($v['vehicle_number']) ?></strong>
                <div class="text-muted"><?= $v['slot_number'] ? 'Slot '.$v['slot_number'] : 'Exited' ?> · <?= date('d M', strtotime($v['created_at'])) ?></div>
              </div>
              <span class="badge <?= $v['status']==='parked'?'badge-green':'badge-muted' ?>"><?= $v['status'] ?></span>
            </div>
            <?php endwhile; endif; ?>
          </div>
        </div>
      </div>

      <!-- Search vehicle -->
      <div class="card fade-in">
        <div class="card-header"><span class="card-title">🔍 Search Vehicle History</span></div>
        <div class="card-body">
          <div class="search-bar">
            <input type="text" id="vehicleSearch" placeholder="Enter vehicle number (e.g. TN38AB1234)">
            <button onclick="searchVehicle()" class="btn btn-primary">Search</button>
          </div>
          <div id="searchResults"></div>
        </div>
      </div>

    </div>
  </div>
</div>
<script src="../js/main.js"></script>
<script>
function updateClock() {
  document.getElementById('clock').textContent = new Date().toLocaleTimeString('en-IN',{hour:'2-digit',minute:'2-digit',second:'2-digit'});
}
setInterval(updateClock, 1000); updateClock();

async function cancelBooking(id) {
  if (!confirm('Cancel this booking?')) return;
  const res = await apiCall('../php/booking_handler.php', {action:'cancel', booking_id:id});
  if (res.success) { showNotification('Cancelled','Booking cancelled.','success'); setTimeout(()=>location.reload(),1000); }
  else showNotification('Error', res.message, 'error');
}

async function searchVehicle() {
  const q = document.getElementById('vehicleSearch').value.trim().toUpperCase();
  if (!q) return;
  const res = await fetch(`../php/vehicle_api.php?action=search&vehicle_number=${encodeURIComponent(q)}&user_id=<?= $uid ?>`);
  const data = await res.json();
  const div = document.getElementById('searchResults');
  if (!data.success || !data.vehicles.length) {
    div.innerHTML = '<div class="empty-state" style="padding:20px"><p>No records found for '+q+'</p></div>';
    return;
  }
  div.innerHTML = `<div class="table-wrap" style="margin-top:12px"><table>
    <thead><tr><th>Vehicle No.</th><th>Slot</th><th>Entry</th><th>Exit</th><th>Status</th></tr></thead>
    <tbody>${data.vehicles.map(v=>`<tr>
      <td><strong>${v.vehicle_number}</strong></td>
      <td>${v.slot_number||'—'}</td>
      <td>${v.entry_time ? new Date(v.entry_time).toLocaleString('en-IN') : '—'}</td>
      <td>${v.exit_time ? new Date(v.exit_time).toLocaleString('en-IN') : '—'}</td>
      <td><span class="badge ${v.status==='parked'?'badge-green':'badge-muted'}">${v.status}</span></td>
    </tr>`).join('')}</tbody></table></div>`;
}
</script>
</body>
</html>
