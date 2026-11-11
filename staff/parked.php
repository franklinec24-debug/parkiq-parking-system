<?php
require_once '../includes/auth.php';
require_once '../includes/config.php';
requireStaff();
$parked = $conn->query("
  SELECT v.id, v.vehicle_number, v.vehicle_type, v.owner_name, v.entry_time,
         ps.slot_number, u.name AS user_name, u.phone
  FROM vehicles v
  LEFT JOIN parking_slots ps ON ps.id = v.slot_id
  LEFT JOIN users u ON u.id = v.user_id
  WHERE v.status='parked'
  ORDER BY v.entry_time ASC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Parked Vehicles — ParkIQ</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="dashboard-wrapper">
  <?php include '../includes/staff_sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <div class="flex items-center gap-3">
        <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
        <span class="topbar-title">Parked Vehicles</span>
      </div>
      <div class="topbar-right">
        <div class="live-badge"><div class="live-dot"></div> Live</div>
      </div>
    </div>
    <div class="page-content">
      <div class="card fade-in">
        <div class="card-header">
          <span class="card-title">🅿️ Currently Parked</span>
          <div class="search-bar" style="margin:0;width:240px">
            <input type="text" id="searchInput" placeholder="Search vehicle...">
          </div>
        </div>
        <div class="table-wrap">
          <table id="parkedTable">
            <thead>
              <tr><th>Vehicle No.</th><th>Type</th><th>Owner</th><th>Phone</th><th>Slot</th><th>Entry Time</th><th>Duration</th><th>Est. Bill</th><th>Action</th></tr>
            </thead>
            <tbody>
              <?php if ($parked->num_rows === 0): ?>
                <tr><td colspan="9" class="empty-state"><div class="icon">🅿️</div>No vehicles parked right now.</td></tr>
              <?php else: while ($v = $parked->fetch_assoc()):
                $mins = round((time() - strtotime($v['entry_time'])) / 60);
                $h = floor($mins/60); $m = $mins%60;
                $rate = $v['vehicle_type']==='4W' ? 20 : 10;
                $est = max(20, ceil($mins/60) * $rate);
              ?>
              <tr>
                <td><strong><?= htmlspecialchars($v['vehicle_number']) ?></strong></td>
                <td><span class="badge badge-blue"><?= $v['vehicle_type'] ?></span></td>
                <td><?= htmlspecialchars($v['owner_name'] ?: $v['user_name'] ?: '—') ?></td>
                <td><?= htmlspecialchars($v['phone'] ?? '—') ?></td>
                <td><strong class="text-amber"><?= $v['slot_number'] ?></strong></td>
                <td><?= date('d M, h:i A', strtotime($v['entry_time'])) ?></td>
                <td><?= $h>0?"{$h}h {$m}m":"{$m}m" ?></td>
                <td><strong>₹<?= $est ?></strong></td>
                <td><a href="vehicle_exit.php?vehicle_id=<?= $v['id'] ?>" class="btn btn-danger btn-sm">Exit</a></td>
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
filterTable('searchInput', 'parkedTable');
setTimeout(() => location.reload(), 60000); // refresh every min
</script>
</body>
</html>
