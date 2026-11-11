<?php
require_once '../includes/auth.php';
require_once '../includes/config.php';
requireUser();
$uid = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Book a Slot — ParkIQ</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="dashboard-wrapper">
  <?php include '../includes/user_sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <div class="flex items-center gap-3">
        <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
        <span class="topbar-title">Pre-Book a Slot</span>
      </div>
    </div>
    <div class="page-content">
      <div class="grid-2">
        <!-- BOOKING FORM -->
        <div class="card fade-in">
          <div class="card-header">
            <span class="card-title">📅 Reserve a Parking Slot</span>
          </div>
          <div class="card-body">
            <div class="role-note">
              💡 Book a slot in advance. Reserved slots won't be assigned to others during your booking time.
            </div>
            <form class="dash-form" id="bookingForm" style="margin-top:16px">
              <div class="form-group">
                <label>Vehicle Number *</label>
                <input type="text" id="b_vehicle" placeholder="TN38AB1234" required oninput="this.value=this.value.toUpperCase()">
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label>Vehicle Type</label>
                  <select id="b_type" onchange="loadAvailableSlots()">
                    <option value="4W">4-Wheeler</option>
                    <option value="2W">2-Wheeler</option>
                  </select>
                </div>
                <div class="form-group">
                  <label>Booking Date *</label>
                  <input type="date" id="b_date" required onchange="loadAvailableSlots()">
                </div>
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label>Start Time *</label>
                  <input type="time" id="b_start" required onchange="loadAvailableSlots()">
                </div>
                <div class="form-group">
                  <label>End Time *</label>
                  <input type="time" id="b_end" required onchange="loadAvailableSlots()">
                </div>
              </div>
              <div class="form-group">
                <label>Select Slot *</label>
                <select id="b_slot" required>
                  <option value="">— Select date & time first —</option>
                </select>
              </div>
              <button type="submit" class="btn btn-primary" style="margin-top:8px">📅 Confirm Booking</button>
            </form>
            <div id="bookingResult" style="margin-top:16px;display:none"></div>
          </div>
        </div>

        <!-- SLOT GRID -->
        <div class="card fade-in">
          <div class="card-header">
            <span class="card-title">🔲 Slot Availability</span>
          </div>
          <div class="card-body">
            <div class="mb-2" style="font-size:12px;color:var(--text-muted);margin-bottom:12px">
              <span class="badge badge-green">Available</span>&nbsp;
              <span class="badge badge-red">Occupied</span>&nbsp;
              <span class="badge badge-yellow">Reserved</span>
            </div>
            <div id="slotGrid">
              <div class="empty-state"><div class="icon">📅</div><p>Select date & time to see available slots.</p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<script src="../js/main.js"></script>
<script>
// Set min date to today
document.getElementById('b_date').min = new Date().toISOString().split('T')[0];

async function loadAvailableSlots() {
  const date  = document.getElementById('b_date').value;
  const start = document.getElementById('b_start').value;
  const end   = document.getElementById('b_end').value;
  const type  = document.getElementById('b_type').value;
  if (!date || !start || !end) return;

  const res = await fetch(`../php/slots_api.php?date=${date}&start=${start}&end=${end}&type=${type}`);
  const data = await res.json();

  const sel = document.getElementById('b_slot');
  sel.innerHTML = '<option value="">— Choose a slot —</option>';

  const grid = document.getElementById('slotGrid');

  if (!data.success) {
    grid.innerHTML = '<div class="empty-state"><p>'+data.message+'</p></div>';
    return;
  }

  let gridHTML = '<div class="slots-grid">';
  data.slots.forEach(s => {
    const cls = s.booking_available ? 'slot-available' : 'slot-occupied';
    gridHTML += `<div class="slot-box ${cls}">${s.slot_number}<span class="slot-type">${s.slot_type}</span></div>`;
    if (s.booking_available) {
      sel.innerHTML += `<option value="${s.id}">${s.slot_number} (${s.slot_type})</option>`;
    }
  });
  gridHTML += '</div>';
  grid.innerHTML = gridHTML;
}

document.getElementById('bookingForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const data = {
    action: 'create',
    user_id: <?= $uid ?>,
    vehicle_number: document.getElementById('b_vehicle').value.trim(),
    slot_id: document.getElementById('b_slot').value,
    booking_date: document.getElementById('b_date').value,
    start_time: document.getElementById('b_start').value,
    end_time: document.getElementById('b_end').value,
  };

  if (!data.vehicle_number || !data.slot_id || !data.booking_date || !data.start_time || !data.end_time) {
    showNotification('Error', 'Please fill all fields and select a slot.', 'error');
    return;
  }
  if (data.start_time >= data.end_time) {
    showNotification('Error', 'End time must be after start time.', 'error');
    return;
  }

  const res = await apiCall('../php/booking_handler.php', data);
  const div = document.getElementById('bookingResult');
  div.style.display = 'block';

  if (res.success) {
    showNotification('Booking Confirmed!', `Slot ${res.slot} reserved for ${data.vehicle_number}`, 'success');
    div.innerHTML = `
      <div class="receipt">
        <div style="text-align:center;font-family:'Rajdhani',sans-serif;font-size:20px;font-weight:700;color:var(--green);margin-bottom:12px">✅ Booking Confirmed!</div>
        <div class="receipt-row"><span>Booking ID</span><strong>#${res.booking_id}</strong></div>
        <div class="receipt-row"><span>Vehicle No.</span><strong>${data.vehicle_number}</strong></div>
        <div class="receipt-row"><span>Slot</span><strong class="text-amber">${res.slot}</strong></div>
        <div class="receipt-row"><span>Date</span><strong>${data.booking_date}</strong></div>
        <div class="receipt-row"><span>Time</span><strong>${data.start_time} – ${data.end_time}</strong></div>
      </div>
    `;
    document.getElementById('bookingForm').reset();
    document.getElementById('slotGrid').innerHTML = '<div class="empty-state"><p>Booking confirmed! Select another slot to book more.</p></div>';
  } else {
    showNotification('Error', res.message, 'error');
    div.innerHTML = `<div class="role-note" style="background:var(--red-dim);border-color:rgba(239,68,68,0.3);color:var(--red)">❌ ${res.message}</div>`;
  }
});
</script>
</body>
</html>
