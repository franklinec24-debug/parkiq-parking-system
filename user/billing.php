<?php
require_once '../includes/auth.php';
require_once '../includes/config.php';
requireUser();
$uid = $_SESSION['user_id'];
$bills = $conn->query("SELECT * FROM billing WHERE user_id=$uid ORDER BY created_at DESC");
$paid_total    = $conn->query("SELECT COALESCE(SUM(amount),0) t FROM billing WHERE user_id=$uid AND payment_status='paid'")->fetch_assoc()['t'];
$pending_total = $conn->query("SELECT COALESCE(SUM(amount),0) t FROM billing WHERE user_id=$uid AND payment_status='pending'")->fetch_assoc()['t'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Billing — ParkIQ</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="dashboard-wrapper">
  <?php include '../includes/user_sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <div class="flex items-center gap-3">
        <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
        <span class="topbar-title">Billing & Payments</span>
      </div>
    </div>
    <div class="page-content">
      <div class="stats-grid fade-in">
        <div class="stat-card green">
          <div class="stat-label">Total Paid</div>
          <div class="stat-value" style="font-size:28px">₹<?= number_format($paid_total,0) ?></div>
          <div class="stat-sub">All time payments</div>
        </div>
        <div class="stat-card <?= $pending_total>0?'amber':'green' ?>">
          <div class="stat-label">Pending Amount</div>
          <div class="stat-value" style="font-size:28px">₹<?= number_format($pending_total,0) ?></div>
          <div class="stat-sub"><?= $pending_total>0?'Please pay at counter':'All dues cleared!' ?></div>
        </div>
      </div>

      <?php if ($pending_total > 0): ?>
      <div class="card fade-in" style="border-color:var(--amber);border-left:3px solid var(--amber);margin-bottom:20px">
        <div class="card-body" style="padding:16px">
          ⚠️ You have <strong>₹<?= number_format($pending_total,2) ?></strong> in pending payments. Please pay at the parking counter.
        </div>
      </div>
      <?php endif; ?>

      <div class="card fade-in">
        <div class="card-header">
          <span class="card-title">💳 Billing History</span>
        </div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr><th>Bill #</th><th>Vehicle</th><th>Slot</th><th>Entry</th><th>Exit</th><th>Duration</th><th>Amount</th><th>Status</th></tr>
            </thead>
            <tbody>
              <?php if ($bills->num_rows === 0): ?>
                <tr><td colspan="8" class="empty-state">No billing records found.</td></tr>
              <?php else: while ($b = $bills->fetch_assoc()):
                $h=floor($b['duration_minutes']/60); $m=$b['duration_minutes']%60;
              ?>
              <tr>
                <td>#<?= $b['id'] ?></td>
                <td><strong><?= htmlspecialchars($b['vehicle_number']) ?></strong></td>
                <td><?= $b['slot_number'] ?></td>
                <td><?= date('d M, h:i A', strtotime($b['entry_time'])) ?></td>
                <td><?= $b['exit_time'] ? date('d M, h:i A', strtotime($b['exit_time'])) : '—' ?></td>
                <td><?= $h>0?"{$h}h {$m}m":"{$m}m" ?></td>
                <td><strong class="text-amber">₹<?= number_format($b['amount'],2) ?></strong></td>
                <td><span class="badge <?= $b['payment_status']==='paid'?'badge-green':'badge-yellow' ?>"><?= $b['payment_status'] ?></span></td>
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
</body>
</html>
