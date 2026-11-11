<?php
require_once '../includes/auth.php';
require_once '../includes/config.php';
requireStaff();

$filter = $_GET['filter'] ?? 'all';
$where = $filter === 'pending' ? "WHERE b.payment_status='pending'" : ($filter === 'paid' ? "WHERE b.payment_status='paid'" : '');

$bills = $conn->query("
  SELECT b.*, u.name AS user_name
  FROM billing b
  LEFT JOIN users u ON u.id = b.user_id
  $where
  ORDER BY b.created_at DESC
");

$total_paid    = $conn->query("SELECT COALESCE(SUM(amount),0) AS t FROM billing WHERE payment_status='paid'")->fetch_assoc()['t'];
$total_pending = $conn->query("SELECT COALESCE(SUM(amount),0) AS t FROM billing WHERE payment_status='pending'")->fetch_assoc()['t'];
$today_revenue = $conn->query("SELECT COALESCE(SUM(amount),0) AS t FROM billing WHERE payment_status='paid' AND DATE(payment_time)=CURDATE()")->fetch_assoc()['t'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payments — ParkIQ</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="dashboard-wrapper">
  <?php include '../includes/staff_sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <div class="flex items-center gap-3">
        <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
        <span class="topbar-title">Payments</span>
      </div>
    </div>
    <div class="page-content">
      <div class="stats-grid fade-in">
        <div class="stat-card green">
          <div class="stat-label">Total Collected</div>
          <div class="stat-value" style="font-size:28px">₹<?= number_format($total_paid, 0) ?></div>
          <div class="stat-sub">All time paid</div>
        </div>
        <div class="stat-card amber">
          <div class="stat-label">Pending Amount</div>
          <div class="stat-value" style="font-size:28px">₹<?= number_format($total_pending, 0) ?></div>
          <div class="stat-sub">Awaiting collection</div>
        </div>
        <div class="stat-card blue">
          <div class="stat-label">Today's Revenue</div>
          <div class="stat-value" style="font-size:28px">₹<?= number_format($today_revenue, 0) ?></div>
          <div class="stat-sub">Collected today</div>
        </div>
      </div>

      <div class="card fade-in">
        <div class="card-header">
          <span class="card-title">💰 Billing Records</span>
          <div class="flex gap-2">
            <a href="?filter=all" class="btn btn-sm <?= $filter==='all'?'btn-primary':'btn-secondary' ?>">All</a>
            <a href="?filter=pending" class="btn btn-sm <?= $filter==='pending'?'btn-primary':'btn-secondary' ?>">Pending</a>
            <a href="?filter=paid" class="btn btn-sm <?= $filter==='paid'?'btn-primary':'btn-secondary' ?>">Paid</a>
          </div>
        </div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr><th>Bill #</th><th>Vehicle</th><th>Slot</th><th>Duration</th><th>Amount</th><th>Status</th><th>Exit Time</th><th>Action</th></tr>
            </thead>
            <tbody>
              <?php if ($bills->num_rows === 0): ?>
                <tr><td colspan="8" class="empty-state">No records found.</td></tr>
              <?php else: while ($b = $bills->fetch_assoc()):
                $h = floor($b['duration_minutes']/60); $m = $b['duration_minutes']%60;
              ?>
                <tr>
                  <td>#<?= $b['id'] ?></td>
                  <td><strong><?= htmlspecialchars($b['vehicle_number']) ?></strong>
                    <div class="text-muted"><?= htmlspecialchars($b['user_name'] ?? '—') ?></div>
                  </td>
                  <td><?= $b['slot_number'] ?></td>
                  <td><?= $h > 0 ? "{$h}h {$m}m" : "{$m}m" ?></td>
                  <td><strong class="text-amber">₹<?= number_format($b['amount'], 2) ?></strong></td>
                  <td><span class="badge <?= $b['payment_status']==='paid'?'badge-green':'badge-yellow' ?>"><?= $b['payment_status'] ?></span></td>
                  <td><?= $b['exit_time'] ? date('d M, h:i A', strtotime($b['exit_time'])) : '—' ?></td>
                  <td>
                    <?php if ($b['payment_status'] === 'pending'): ?>
                    <button onclick="markPaid(<?= $b['id'] ?>)" class="btn btn-success btn-sm">Mark Paid</button>
                    <?php else: ?>
                    <span class="text-muted">—</span>
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
async function markPaid(id) {
  if (!confirm('Confirm payment received?')) return;
  const res = await apiCall('../php/payment_handler.php', { action: 'mark_paid', bill_id: id });
  if (res.success) {
    showNotification('Payment Collected', '₹' + res.amount + ' marked as paid.', 'success');
    setTimeout(() => location.reload(), 1000);
  } else {
    showNotification('Error', res.message, 'error');
  }
}
</script>
</body>
</html>
