<?php
require_once '../includes/auth.php';
require_once '../includes/config.php';
requireUser();
$uid = $_SESSION['user_id'];
$history = $conn->query("
  SELECT v.*, ps.slot_number
  FROM vehicles v
  LEFT JOIN parking_slots ps ON ps.id=v.slot_id
  WHERE v.user_id=$uid
  ORDER BY v.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Parking History — ParkIQ</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="dashboard-wrapper">
  <?php include '../includes/user_sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <div class="flex items-center gap-3">
        <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
        <span class="topbar-title">Parking History</span>
      </div>
    </div>
    <div class="page-content">
      <div class="card fade-in">
        <div class="card-header">
          <span class="card-title">🕐 My Parking History</span>
          <div class="search-bar" style="margin:0;width:240px">
            <input type="text" id="searchInput" placeholder="Search...">
          </div>
        </div>
        <div class="table-wrap">
          <table id="histTable">
            <thead>
              <tr><th>#</th><th>Vehicle No.</th><th>Type</th><th>Slot</th><th>Entry</th><th>Exit</th><th>Duration</th><th>Status</th></tr>
            </thead>
            <tbody>
              <?php if ($history->num_rows === 0): ?>
                <tr><td colspan="8" class="empty-state">No parking history found.</td></tr>
              <?php else: $i=1; while ($v = $history->fetch_assoc()):
                $mins = 0;
                if ($v['entry_time'] && $v['exit_time']) {
                  $mins = round((strtotime($v['exit_time'])-strtotime($v['entry_time']))/60);
                } elseif ($v['entry_time'] && $v['status']==='parked') {
                  $mins = round((time()-strtotime($v['entry_time']))/60);
                }
                $h=floor($mins/60); $m=$mins%60;
              ?>
              <tr>
                <td><?= $i++ ?></td>
                <td><strong><?= htmlspecialchars($v['vehicle_number']) ?></strong></td>
                <td><span class="badge badge-blue"><?= $v['vehicle_type'] ?></span></td>
                <td><?= $v['slot_number'] ?: '—' ?></td>
                <td><?= $v['entry_time'] ? date('d M Y, h:i A', strtotime($v['entry_time'])) : '—' ?></td>
                <td><?= $v['exit_time'] ? date('d M Y, h:i A', strtotime($v['exit_time'])) : '<span class="badge badge-green">Still Parked</span>' ?></td>
                <td><?= $mins > 0 ? ($h>0?"{$h}h {$m}m":"{$m}m") : '—' ?></td>
                <td><span class="badge <?= $v['status']==='parked'?'badge-green':'badge-muted' ?>"><?= $v['status'] ?></span></td>
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
<script>filterTable('searchInput','histTable');</script>
</body>
</html>
