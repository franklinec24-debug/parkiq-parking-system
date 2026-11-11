<?php
require_once '../includes/auth.php';
require_once '../includes/config.php';
requireUser();
$slots = $conn->query("SELECT ps.*, v.vehicle_number FROM parking_slots ps LEFT JOIN vehicles v ON v.slot_id=ps.id AND v.status='parked' ORDER BY ps.slot_type, ps.slot_number");
$all=[];
while($r=$slots->fetch_assoc()) $all[]=$r;
$avail=count(array_filter($all,fn($s)=>$s['status']==='available'));
$occup=count(array_filter($all,fn($s)=>$s['status']==='occupied'));
$resvd=count(array_filter($all,fn($s)=>$s['status']==='reserved'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Slot Status — ParkIQ</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="dashboard-wrapper">
  <?php include '../includes/user_sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <div class="flex items-center gap-3">
        <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
        <span class="topbar-title">Slot Status</span>
      </div>
      <div class="topbar-right"><div class="live-badge"><div class="live-dot"></div>Live</div></div>
    </div>
    <div class="page-content">
      <div class="stats-grid fade-in">
        <div class="stat-card amber"><div class="stat-label">Total</div><div class="stat-value"><?=count($all)?></div></div>
        <div class="stat-card green"><div class="stat-label">Available</div><div class="stat-value"><?=$avail?></div></div>
        <div class="stat-card red"><div class="stat-label">Occupied</div><div class="stat-value"><?=$occup?></div></div>
        <div class="stat-card amber"><div class="stat-label">Reserved</div><div class="stat-value"><?=$resvd?></div></div>
      </div>
      <?php if ($avail === 0): ?>
      <div class="card" style="border-color:var(--red);margin-bottom:20px">
        <div class="card-body" style="padding:14px;color:var(--red);font-weight:600">⚠️ Parking Full! No slots currently available.</div>
      </div>
      <?php endif; ?>
      <div class="card fade-in">
        <div class="card-header">
          <span class="card-title">🔲 4-Wheeler (A-Block)</span>
          <div class="flex gap-2" style="font-size:12px">
            <span class="badge badge-green">Available</span>
            <span class="badge badge-red">Occupied</span>
            <span class="badge badge-yellow">Reserved</span>
          </div>
        </div>
        <div class="card-body">
          <div class="slots-grid">
            <?php foreach ($all as $s): if ($s['slot_type']!=='4W') continue; ?>
            <div class="slot-box slot-<?=$s['status']?>">
              <?=$s['slot_number']?>
              <span class="slot-type"><?=$s['status']==='occupied'?'IN USE':strtoupper($s['status'])?></span>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <div class="card fade-in">
        <div class="card-header"><span class="card-title">🔲 2-Wheeler (B-Block)</span></div>
        <div class="card-body">
          <div class="slots-grid">
            <?php foreach ($all as $s): if ($s['slot_type']!=='2W') continue; ?>
            <div class="slot-box slot-<?=$s['status']?>">
              <?=$s['slot_number']?>
              <span class="slot-type"><?=$s['status']==='occupied'?'IN USE':strtoupper($s['status'])?></span>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <p class="text-muted text-center" style="margin-top:10px">Auto-refreshes every 30 seconds</p>
    </div>
  </div>
</div>
<script src="../js/main.js"></script>
<script>setTimeout(()=>location.reload(),30000);</script>
</body>
</html>
