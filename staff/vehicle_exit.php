<?php
require_once '../includes/auth.php';
require_once '../includes/config.php';
requireStaff();

// Pre-select if vehicle_id passed
$preselect = intval($_GET['vehicle_id'] ?? 0);

// Parked vehicles
$parked = $conn->query("
  SELECT v.id, v.vehicle_number, v.vehicle_type, v.owner_name, v.entry_time,
         ps.slot_number
  FROM vehicles v
  LEFT JOIN parking_slots ps ON ps.id = v.slot_id
  WHERE v.status='parked'
  ORDER BY v.entry_time ASC
");
$parked_list = [];
while ($row = $parked->fetch_assoc()) $parked_list[] = $row;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Vehicle Exit — ParkIQ</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="dashboard-wrapper">
  <?php include '../includes/staff_sidebar.php'; ?>

  <div class="main-content">
    <div class="topbar">
      <div class="flex items-center gap-3">
        <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
        <span class="topbar-title">Vehicle Exit</span>
      </div>
    </div>

    <div class="page-content">
      <div class="grid-2">
        <!-- EXIT FORM -->
        <div class="card fade-in">
          <div class="card-header">
            <span class="card-title">🚪 Process Vehicle Exit</span>
          </div>
          <div class="card-body">
            <?php if (empty($parked_list)): ?>
              <div class="empty-state">
                <div class="icon">🅿️</div>
                <p>No vehicles currently parked.</p>
              </div>
            <?php else: ?>
            <div class="dash-form">
              <div class="form-group">
                <label>Select Vehicle *</label>
                <select id="vehicleSelect" onchange="loadVehicleInfo()">
                  <option value="">— Select a vehicle —</option>
                  <?php foreach ($parked_list as $v): ?>
                  <option value="<?= $v['id'] ?>"
                    data-number="<?= htmlspecialchars($v['vehicle_number']) ?>"
                    data-type="<?= $v['vehicle_type'] ?>"
                    data-owner="<?= htmlspecialchars($v['owner_name'] ?? '') ?>"
                    data-slot="<?= $v['slot_number'] ?>"
                    data-entry="<?= $v['entry_time'] ?>"
                    <?= $preselect == $v['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($v['vehicle_number']) ?> — Slot <?= $v['slot_number'] ?>
                  </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <!-- Vehicle Details -->
              <div id="vehicleDetails" style="display:none">
                <div class="receipt" style="margin-bottom:16px">
                  <div class="receipt-row"><span>Vehicle No.</span><strong id="d_number">—</strong></div>
                  <div class="receipt-row"><span>Type</span><strong id="d_type">—</strong></div>
                  <div class="receipt-row"><span>Owner</span><strong id="d_owner">—</strong></div>
                  <div class="receipt-row"><span>Slot</span><strong id="d_slot" class="text-amber">—</strong></div>
                  <div class="receipt-row"><span>Entry Time</span><strong id="d_entry">—</strong></div>
                  <div class="receipt-row"><span>Duration</span><strong id="d_dur" class="text-amber">—</strong></div>
                  <div class="receipt-row total"><span>Amount Due</span><strong id="d_amount">—</strong></div>
                </div>

                <div class="form-group">
                  <label>Payment Status</label>
                  <select id="payment_status">
                    <option value="paid">✅ Paid (Collected Now)</option>
                    <option value="pending">⏳ Pending (Collect Later)</option>
                  </select>
                </div>

                <button onclick="processExit()" class="btn btn-danger w-full">🚪 Process Exit & Free Slot</button>
              </div>
            </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- BILL GENERATED -->
        <div class="card fade-in">
          <div class="card-header">
            <span class="card-title">🧾 Generated Bill</span>
          </div>
          <div class="card-body">
            <div id="billOutput">
              <div class="empty-state">
                <div class="icon">🧾</div>
                <p>Select a vehicle to generate bill.</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- EXIT HISTORY -->
      <div class="card fade-in">
        <div class="card-header">
          <span class="card-title">🕐 Exit History Today</span>
        </div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr><th>Vehicle No.</th><th>Slot</th><th>Duration</th><th>Amount</th><th>Payment</th><th>Exit Time</th></tr>
            </thead>
            <tbody id="exitHistory">
              <tr><td colspan="6" class="empty-state">Loading...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="../js/main.js"></script>
<script>
const RATE_4W = 20, RATE_2W = 10, MIN_CHARGE = 20;
let durationTimer = null;

function loadVehicleInfo() {
  const sel = document.getElementById('vehicleSelect');
  const opt = sel.options[sel.selectedIndex];
  if (!opt.value) {
    document.getElementById('vehicleDetails').style.display = 'none';
    return;
  }

  const entry = new Date(opt.dataset.entry);
  const now = new Date();
  const mins = Math.ceil((now - entry) / 60000);
  const h = Math.floor(mins / 60), m = mins % 60;
  const rate = opt.dataset.type === '4W' ? RATE_4W : RATE_2W;
  const hours = Math.ceil(mins / 60);
  const amount = Math.max(MIN_CHARGE, hours * rate);

  document.getElementById('d_number').textContent = opt.dataset.number;
  document.getElementById('d_type').textContent = opt.dataset.type === '4W' ? '4-Wheeler' : '2-Wheeler';
  document.getElementById('d_owner').textContent = opt.dataset.owner || '—';
  document.getElementById('d_slot').textContent = opt.dataset.slot;
  document.getElementById('d_entry').textContent = entry.toLocaleString('en-IN');
  document.getElementById('d_dur').textContent = h > 0 ? `${h}h ${m}m` : `${m}m`;
  document.getElementById('d_amount').textContent = `₹${amount.toFixed(2)}`;

  document.getElementById('vehicleDetails').style.display = 'block';

  // Live timer
  clearInterval(durationTimer);
  durationTimer = setInterval(() => {
    const now2 = new Date();
    const mins2 = Math.ceil((now2 - entry) / 60000);
    const h2 = Math.floor(mins2/60), m2 = mins2%60;
    const hours2 = Math.ceil(mins2/60);
    const amount2 = Math.max(MIN_CHARGE, hours2 * rate);
    document.getElementById('d_dur').textContent = h2 > 0 ? `${h2}h ${m2}m` : `${m2}m`;
    document.getElementById('d_amount').textContent = `₹${amount2.toFixed(2)}`;
  }, 30000);
}

async function processExit() {
  const vehicleId = document.getElementById('vehicleSelect').value;
  const paymentStatus = document.getElementById('payment_status').value;
  if (!vehicleId) return;

  const res = await apiCall('../php/vehicle_handler.php', {
    action: 'exit',
    vehicle_id: vehicleId,
    payment_status: paymentStatus
  });

  if (res.success) {
    showNotification('Exit Processed', `${res.vehicle_number} has exited. Bill: ₹${res.amount}`, 'success');
    document.getElementById('billOutput').innerHTML = `
      <div class="receipt">
        <div style="text-align:center;margin-bottom:14px;font-family:'Rajdhani',sans-serif;font-size:20px;font-weight:700;color:var(--amber)">
          🧾 PARKING RECEIPT
        </div>
        <div class="receipt-row"><span>Vehicle No.</span><strong>${res.vehicle_number}</strong></div>
        <div class="receipt-row"><span>Slot</span><strong>${res.slot}</strong></div>
        <div class="receipt-row"><span>Entry</span><strong>${new Date(res.entry_time).toLocaleString('en-IN')}</strong></div>
        <div class="receipt-row"><span>Exit</span><strong>${new Date().toLocaleString('en-IN')}</strong></div>
        <div class="receipt-row"><span>Duration</span><strong>${res.duration}</strong></div>
        <div class="receipt-row total"><span>Total</span><strong>₹${res.amount}</strong></div>
        <div style="text-align:center;margin-top:12px">
          <span class="badge ${res.payment_status === 'paid' ? 'badge-green' : 'badge-yellow'}">
            ${res.payment_status === 'paid' ? '✅ PAID' : '⏳ PENDING'}
          </span>
        </div>
      </div>
    `;
    document.getElementById('vehicleDetails').style.display = 'none';
    document.getElementById('vehicleSelect').value = '';
    // Remove from dropdown
    const opt = document.querySelector(`#vehicleSelect option[value="${vehicleId}"]`);
    if (opt) opt.remove();
    loadExitHistory();
  } else {
    showNotification('Error', res.message, 'error');
  }
}

async function loadExitHistory() {
  const res = await fetch('../php/vehicle_api.php?action=exit_history');
  const data = await res.json();
  const tbody = document.getElementById('exitHistory');
  if (!data.success || !data.vehicles.length) {
    tbody.innerHTML = '<tr><td colspan="6" class="empty-state">No exits today.</td></tr>';
    return;
  }
  tbody.innerHTML = data.vehicles.map(v => {
    const h = Math.floor(v.duration_minutes/60), m = v.duration_minutes%60;
    return `<tr>
      <td><strong>${v.vehicle_number}</strong></td>
      <td>${v.slot_number}</td>
      <td>${h > 0 ? h+'h '+m+'m' : m+'m'}</td>
      <td><strong class="text-amber">₹${parseFloat(v.amount).toFixed(2)}</strong></td>
      <td><span class="badge ${v.payment_status === 'paid' ? 'badge-green' : 'badge-yellow'}">${v.payment_status}</span></td>
      <td>${new Date(v.exit_time).toLocaleString('en-IN',{hour:'2-digit',minute:'2-digit',hour12:true,day:'2-digit',month:'short'})}</td>
    </tr>`;
  }).join('');
}

// Auto-load if pre-selected
if (document.getElementById('vehicleSelect').value) {
  loadVehicleInfo();
}
loadExitHistory();
</script>
</body>
</html>
