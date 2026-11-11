<?php
require_once '../includes/auth.php';
require_once '../includes/config.php';
requireStaff();

// Available slots
$slots_4w = $conn->query("SELECT * FROM parking_slots WHERE status='available' AND slot_type='4W' ORDER BY slot_number");
$slots_2w = $conn->query("SELECT * FROM parking_slots WHERE status='available' AND slot_type='2W' ORDER BY slot_number");

$total_4w = $conn->query("SELECT COUNT(*) AS c FROM parking_slots WHERE slot_type='4W'")->fetch_assoc()['c'];
$avail_4w = $conn->query("SELECT COUNT(*) AS c FROM parking_slots WHERE slot_type='4W' AND status='available'")->fetch_assoc()['c'];
$total_2w = $conn->query("SELECT COUNT(*) AS c FROM parking_slots WHERE slot_type='2W'")->fetch_assoc()['c'];
$avail_2w = $conn->query("SELECT COUNT(*) AS c FROM parking_slots WHERE slot_type='2W' AND status='available'")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Vehicle Entry — ParkIQ</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="dashboard-wrapper">
  <?php include '../includes/staff_sidebar.php'; ?>

  <div class="main-content">
    <div class="topbar">
      <div class="flex items-center gap-3">
        <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
        <span class="topbar-title">Vehicle Entry</span>
      </div>
    </div>

    <div class="page-content">
      <div class="grid-2">
        <!-- ENTRY FORM -->
        <div class="card fade-in">
          <div class="card-header">
            <span class="card-title">🚗 Register Vehicle Entry</span>
          </div>
          <div class="card-body">
            <div class="stats-grid" style="grid-template-columns:1fr 1fr;gap:10px;margin-bottom:20px">
              <div class="stat-card green" style="padding:14px">
                <div class="stat-label">4-Wheeler Available</div>
                <div class="stat-value" style="font-size:28px"><?= $avail_4w ?></div>
                <div class="stat-sub">of <?= $total_4w ?></div>
              </div>
              <div class="stat-card blue" style="padding:14px">
                <div class="stat-label">2-Wheeler Available</div>
                <div class="stat-value" style="font-size:28px"><?= $avail_2w ?></div>
                <div class="stat-sub">of <?= $total_2w ?></div>
              </div>
            </div>

            <form class="dash-form" id="entryForm">
              <div class="form-row">
                <div class="form-group">
                  <label>Vehicle Number *</label>
                  <input type="text" id="vehicle_number" placeholder="TN38AB1234" required
                         style="text-transform:uppercase" oninput="this.value=this.value.toUpperCase()">
                </div>
                <div class="form-group">
                  <label>Vehicle Type *</label>
                  <select id="vehicle_type">
                    <option value="4W">4-Wheeler (Car/Van)</option>
                    <option value="2W">2-Wheeler (Bike/Scooter)</option>
                  </select>
                </div>
              </div>
              <div class="form-group">
                <label>Owner Name (optional)</label>
                <input type="text" id="owner_name" placeholder="Vehicle owner's name">
              </div>
              <button type="submit" class="btn btn-primary" style="margin-top:8px">
                🚗 Register Entry & Assign Slot
              </button>
            </form>

            <!-- Result -->
            <div id="entryResult" style="display:none;margin-top:20px"></div>
          </div>
        </div>

        <!-- SLOT AVAILABILITY -->
        <div class="card fade-in">
          <div class="card-header">
            <span class="card-title">🔲 Slot Availability</span>
          </div>
          <div class="card-body">
            <div class="mb-2" style="font-size:12px;color:var(--text-muted);margin-bottom:12px">
              <span class="badge badge-green">Green</span> Available &nbsp;
              <span class="badge badge-red">Red</span> Occupied &nbsp;
              <span class="badge badge-yellow">Yellow</span> Reserved
            </div>
            <div id="slotsGrid"></div>
          </div>
        </div>
      </div>

      <!-- RECENT ENTRIES -->
      <div class="card fade-in">
        <div class="card-header">
          <span class="card-title">🕐 Recent Entries Today</span>
        </div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr><th>Vehicle No.</th><th>Type</th><th>Owner</th><th>Slot</th><th>Entry Time</th></tr>
            </thead>
            <tbody id="recentEntries">
              <tr><td colspan="5" class="empty-state">Loading...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="../js/main.js"></script>
<script>
// Load slots
async function loadSlots() {
  const res = await fetch('../php/slots_api.php');
  const data = await res.json();
  if (!data.success) return;
  const grid = document.getElementById('slotsGrid');
  grid.innerHTML = '<div class="slots-grid">' + data.slots.map(s => `
    <div class="slot-box slot-${s.status}">
      ${s.slot_number}
      <span class="slot-type">${s.slot_type}</span>
    </div>
  `).join('') + '</div>';
}

// Load recent entries
async function loadRecent() {
  const res = await fetch('../php/vehicle_api.php?action=recent_entries');
  const data = await res.json();
  const tbody = document.getElementById('recentEntries');
  if (!data.success || !data.vehicles.length) {
    tbody.innerHTML = '<tr><td colspan="5" class="empty-state">No entries today.</td></tr>';
    return;
  }
  tbody.innerHTML = data.vehicles.map(v => `
    <tr>
      <td><strong>${v.vehicle_number}</strong></td>
      <td><span class="badge badge-blue">${v.vehicle_type}</span></td>
      <td>${v.owner_name || '—'}</td>
      <td><strong class="text-amber">${v.slot_number}</strong></td>
      <td>${new Date(v.entry_time).toLocaleTimeString('en-IN', {hour:'2-digit',minute:'2-digit',hour12:true})}</td>
    </tr>
  `).join('');
}

// Entry form
document.getElementById('entryForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const data = {
    action: 'entry',
    vehicle_number: document.getElementById('vehicle_number').value.trim(),
    vehicle_type: document.getElementById('vehicle_type').value,
    owner_name: document.getElementById('owner_name').value.trim()
  };

  if (!data.vehicle_number) {
    showNotification('Error', 'Vehicle number is required.', 'error');
    return;
  }

  const res = await apiCall('../php/vehicle_handler.php', data);
  const resultDiv = document.getElementById('entryResult');
  resultDiv.style.display = 'block';

  if (res.success) {
    showNotification('Entry Registered', `Slot ${res.slot} assigned to ${data.vehicle_number}`, 'success');
    resultDiv.innerHTML = `
      <div class="receipt">
        <div style="text-align:center;margin-bottom:14px">
          <div style="font-size:22px;font-family:'Rajdhani',sans-serif;font-weight:700;color:var(--green)">✅ Entry Registered</div>
        </div>
        <div class="receipt-row"><span>Vehicle No.</span><strong>${data.vehicle_number}</strong></div>
        <div class="receipt-row"><span>Type</span><strong>${data.vehicle_type === '4W' ? '4-Wheeler' : '2-Wheeler'}</strong></div>
        <div class="receipt-row"><span>Slot Assigned</span><strong class="text-amber">${res.slot}</strong></div>
        <div class="receipt-row"><span>Entry Time</span><strong>${new Date().toLocaleString('en-IN')}</strong></div>
        <div class="receipt-row"><span>Rate</span><strong>₹${data.vehicle_type === '4W' ? '20' : '10'}/hour</strong></div>
      </div>
    `;
    document.getElementById('entryForm').reset();
    loadSlots();
    loadRecent();
  } else {
    showNotification('Error', res.message, 'error');
    resultDiv.innerHTML = `<div class="role-note" style="background:var(--red-dim);border-color:rgba(239,68,68,0.3);color:var(--red);">❌ ${res.message}</div>`;
  }
});

loadSlots();
loadRecent();
startSlotRefresh(loadSlots, 20000);
</script>
</body>
</html>
