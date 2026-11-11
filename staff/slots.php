<?php
require_once '../includes/auth.php';
require_once '../includes/config.php';
requireStaff();
$slots = $conn->query("SELECT ps.*, v.vehicle_number, v.entry_time FROM parking_slots ps LEFT JOIN vehicles v ON v.slot_id=ps.id AND v.status='parked' ORDER BY ps.slot_type, ps.slot_number");
$all = [];
while ($r = $slots->fetch_assoc()) $all[] = $r;
$total = count($all);
$avail = count(array_filter($all, fn($s) => $s['status']==='available'));
$occup = count(array_filter($all, fn($s) => $s['status']==='occupied'));
$resvd = count(array_filter($all, fn($s) => $s['status']==='reserved'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Slot Overview — ParkIQ</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="dashboard-wrapper">
  <?php include '../includes/staff_sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <div class="flex items-center gap-3">
        <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
        <span class="topbar-title">Slot Overview</span>
      </div>
      <div class="topbar-right"><div class="live-badge"><div class="live-dot"></div>Live</div></div>
    </div>
    <div class="page-content">
      <div class="stats-grid fade-in">
        <div class="stat-card amber"><div class="stat-label">Total Slots</div><div class="stat-value"><?= $total ?></div></div>
        <div class="stat-card green"><div class="stat-label">Available</div><div class="stat-value"><?= $avail ?></div></div>
        <div class="stat-card red"><div class="stat-label">Occupied</div><div class="stat-value"><?= $occup ?></div></div>
        <div class="stat-card amber"><div class="stat-label">Reserved</div><div class="stat-value"><?= $resvd ?></div></div>
      </div>

      <div class="card fade-in">
        <div class="card-header">
          <span class="card-title">🔲 4-Wheeler Slots (A-Block)</span>
          <div class="flex gap-2" style="font-size:12px">
            <span class="badge badge-green">Available</span>
            <span class="badge badge-red">Occupied</span>
            <span class="badge badge-yellow">Reserved</span>
          </div>
        </div>
        <div class="card-body">
          <div class="slots-grid">
            <?php foreach ($all as $s): if ($s['slot_type'] !== '4W') continue; ?>
            <div class="slot-box slot-<?= $s['status'] ?>" title="<?= $s['vehicle_number'] ? $s['vehicle_number'] : $s['status'] ?>">
              <?= $s['slot_number'] ?>
              <span class="slot-type"><?= $s['vehicle_number'] ?: strtoupper($s['status']) ?></span>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <div class="card fade-in">
        <div class="card-header">
          <span class="card-title">🔲 2-Wheeler Slots (B-Block)</span>
        </div>
        <div class="card-body">
          <div class="slots-grid">
            <?php foreach ($all as $s): if ($s['slot_type'] !== '2W') continue; ?>
            <div class="slot-box slot-<?= $s['status'] ?>" title="<?= $s['vehicle_number'] ?: $s['status'] ?>">
              <?= $s['slot_number'] ?>
              <span class="slot-type"><?= $s['vehicle_number'] ?: strtoupper($s['status']) ?></span>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<script src="../js/main.js"></script>
<script>setTimeout(() => location.reload(), 30000);</script>
</body>
</html>
