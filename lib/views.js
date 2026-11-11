const {
  RATE_PER_HOUR_4W,
  RATE_PER_HOUR_2W,
  MIN_CHARGE,
  formatDuration,
  calculateFare,
  formatDateTimeShort,
  formatDateTimeFull,
  formatDateOnly,
  formatDayMonth,
  formatTime12,
} = require('./db');

function esc(val) {
  if (val === null || val === undefined) return '';
  return String(val)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function money(val) {
  return Number(val || 0).toFixed(2);
}

function moneyInt(val) {
  return Math.round(Number(val || 0)).toLocaleString('en-IN');
}

function renderLoginPage({ error = '', success = '', activeTab = 'user-login' } = {}) {
  const isAdmin = activeTab === 'admin-login';
  return `<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ParkIQ — Vehicle Parking Management</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body class="auth-body">

<div class="auth-box fade-in">
  <div class="auth-logo">
    <div class="logo-icon">🅿️</div>
    <div>
      <h1>ParkIQ</h1>
      <span>Parking Management System</span>
    </div>
  </div>

  ${error ? `<div class="role-note" style="background:var(--red-dim);border-color:rgba(239,68,68,0.3);color:var(--red);">⚠️ ${esc(error)}</div>` : ''}
  ${success ? `<div class="role-note" style="background:var(--green-dim);border-color:rgba(16,185,129,0.2);color:var(--green);">✅ ${esc(success)}</div>` : ''}

  <div class="auth-tabs">
    <button class="auth-tab ${isAdmin ? '' : 'active'}" data-tab="user-login">🚗 User Login</button>
    <button class="auth-tab ${isAdmin ? 'active' : ''}" data-tab="admin-login">🛡️ Admin Login</button>
  </div>

  <div class="auth-section ${isAdmin ? '' : 'active'}" id="user-login">
    <div class="role-note">
      🔑 Demo User: <strong>user@parking.com</strong> — password: <strong>password</strong>
    </div>
    <form action="php/auth_handler.php" method="POST">
      <input type="hidden" name="action" value="login">
      <input type="hidden" name="login_role" value="user">
      <div class="form-group">
        <label>User Email Address</label>
        <input type="email" name="email" placeholder="user@parking.com" required>
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" placeholder="••••••••" required>
      </div>
      <button type="submit" class="btn btn-primary">Sign In as User →</button>
    </form>
  </div>

  <div class="auth-section ${isAdmin ? 'active' : ''}" id="admin-login">
    <div class="role-note">
      🛡️ Demo Admin: <strong>staff@parking.com</strong> — password: <strong>password</strong>
    </div>
    <form action="php/auth_handler.php" method="POST">
      <input type="hidden" name="action" value="login">
      <input type="hidden" name="login_role" value="staff">
      <div class="form-group">
        <label>Admin Email Address</label>
        <input type="email" name="email" placeholder="staff@parking.com" required>
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" placeholder="••••••••" required>
      </div>
      <button type="submit" class="btn btn-primary">Sign In as Admin →</button>
    </form>
  </div>

  <p class="auth-footer" style="font-size:12px;">
    New here? <a href="/register.php" target="_blank" rel="noopener noreferrer" style="color:var(--amber);font-weight:500;">Register new account ↗</a>
  </p>
</div>

<script src="js/main.js"></script>
</body>
</html>`;
}

function renderRegisterPage({ error = '', success = '' } = {}) {
  return `<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register — ParkIQ</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body class="auth-body">

<div class="auth-box fade-in">
  <div class="auth-logo">
    <div class="logo-icon">🅿️</div>
    <div>
      <h1>ParkIQ</h1>
      <span>Create New Account</span>
    </div>
  </div>

  ${error ? `<div class="role-note" style="background:var(--red-dim);border-color:rgba(239,68,68,0.3);color:var(--red);">⚠️ ${esc(error)}</div>` : ''}
  ${success ? `<div class="role-note" style="background:var(--green-dim);border-color:rgba(16,185,129,0.2);color:var(--green);">✅ ${esc(success)} <a href="/index.php" style="color:var(--amber);margin-left:6px;font-weight:600;">Go to Login →</a></div>` : ''}

  <form action="php/auth_handler.php" method="POST">
    <input type="hidden" name="action" value="register">
    <div class="form-group">
      <label>Full Name</label>
      <input type="text" name="name" placeholder="Raj Kumar" required>
    </div>
    <div class="form-group">
      <label>Email Address</label>
      <input type="email" name="email" placeholder="you@example.com" required>
    </div>
    <div class="form-group">
      <label>Phone Number</label>
      <input type="tel" name="phone" placeholder="9876543210">
    </div>
    <div class="form-group">
      <label>Password</label>
      <input type="password" name="password" placeholder="Min. 6 characters" required minlength="6">
    </div>
    <div class="form-group">
      <label>Account Type</label>
      <select name="role">
        <option value="user">Normal User (Vehicle Owner)</option>
        <option value="staff">Admin / Staff (Parking Attendant)</option>
      </select>
    </div>
    <button type="submit" class="btn btn-primary">Create Account →</button>
  </form>

  <p class="auth-footer" style="font-size:12px;">
    Already have an account? <a href="/index.php" style="color:var(--amber);font-weight:500;">Back to Login</a>
  </p>
</div>

<script src="js/main.js"></script>
</body>
</html>`;
}

function renderStaffSidebar(user) {
  const name = user.name || 'Staff';
  const initial = esc(name.charAt(0).toUpperCase());
  return `<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <div class="logo-icon">🅿️</div>
    <div>
      <h2>ParkIQ</h2>
      <span>Staff Panel</span>
    </div>
  </div>
  <nav class="sidebar-nav">
    <span class="nav-section-label">Overview</span>
    <a href="dashboard.php" class="nav-item"><span class="nav-icon">📊</span> Dashboard</a>
    <span class="nav-section-label">Operations</span>
    <a href="vehicle_entry.php" class="nav-item"><span class="nav-icon">🚗</span> Vehicle Entry</a>
    <a href="vehicle_exit.php" class="nav-item"><span class="nav-icon">🚪</span> Vehicle Exit</a>
    <a href="parked.php" class="nav-item"><span class="nav-icon">🅿️</span> Parked Vehicles</a>
    <a href="history.php" class="nav-item"><span class="nav-icon">🕐</span> Parking History</a>
    <span class="nav-section-label">Finance</span>
    <a href="payments.php" class="nav-item"><span class="nav-icon">💰</span> Payments</a>
    <span class="nav-section-label">Bookings</span>
    <a href="bookings.php" class="nav-item"><span class="nav-icon">📅</span> All Bookings</a>
    <span class="nav-section-label">Slots</span>
    <a href="slots.php" class="nav-item"><span class="nav-icon">🔲</span> Slot Overview</a>
  </nav>
  <div class="sidebar-footer">
    <div class="user-card">
      <div class="user-avatar">${initial}</div>
      <div class="user-info">
        <div class="user-name">${esc(name)}</div>
        <div class="user-role">Staff</div>
      </div>
    </div>
    <a href="../php/auth_handler.php?action=logout" class="btn-logout" onclick="event.preventDefault(); document.getElementById('logoutForm').submit();">🚪 Logout</a>
    <form id="logoutForm" action="../php/auth_handler.php" method="POST" style="display:none">
      <input type="hidden" name="action" value="logout">
    </form>
  </div>
</aside>`;
}

function renderUserSidebar(user) {
  const name = user.name || 'User';
  const initial = esc(name.charAt(0).toUpperCase());
  return `<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <div class="logo-icon">🅿️</div>
    <div>
      <h2>ParkIQ</h2>
      <span>User Panel</span>
    </div>
  </div>
  <nav class="sidebar-nav">
    <span class="nav-section-label">Overview</span>
    <a href="dashboard.php" class="nav-item"><span class="nav-icon">🏠</span> Dashboard</a>
    <span class="nav-section-label">Parking</span>
    <a href="history.php" class="nav-item"><span class="nav-icon">🕐</span> Parking History</a>
    <a href="billing.php" class="nav-item"><span class="nav-icon">💳</span> Billing &amp; Payments</a>
    <span class="nav-section-label">Bookings</span>
    <a href="booking.php" class="nav-item"><span class="nav-icon">📅</span> Pre-Book Slot</a>
    <a href="my_bookings.php" class="nav-item"><span class="nav-icon">📋</span> My Bookings</a>
    <span class="nav-section-label">Slots</span>
    <a href="slots.php" class="nav-item"><span class="nav-icon">🔲</span> Slot Status</a>
  </nav>
  <div class="sidebar-footer">
    <div class="user-card">
      <div class="user-avatar">${initial}</div>
      <div class="user-info">
        <div class="user-name">${esc(name)}</div>
        <div class="user-role">User</div>
      </div>
    </div>
    <a href="#" class="btn-logout" onclick="event.preventDefault(); document.getElementById('logoutForm').submit();">🚪 Logout</a>
    <form id="logoutForm" action="../php/auth_handler.php" method="POST" style="display:none">
      <input type="hidden" name="action" value="logout">
    </form>
  </div>
</aside>`;
}

function layoutShell({ title, sidebar, topbarTitle, topbarRight = '', bodyContent, scripts = '' }) {
  return `<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>${esc(title)} — ParkIQ</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="dashboard-wrapper">
  ${sidebar}
  <div class="main-content">
    <div class="topbar">
      <div class="flex items-center gap-3">
        <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
        <span class="topbar-title">${esc(topbarTitle)}</span>
      </div>
      ${topbarRight ? `<div class="topbar-right">${topbarRight}</div>` : ''}
    </div>
    <div class="page-content">
      ${bodyContent}
    </div>
  </div>
</div>
<script src="../js/main.js"></script>
${scripts}
</body>
</html>`;
}

function renderStaffDashboard({ user, stats, parked, pendingBills }) {
  const pct = stats.total > 0 ? Math.round((stats.occupied / stats.total) * 100) : 0;
  const color = pct >= 90 ? '#ef4444' : pct >= 60 ? '#f59e0b' : '#10b981';

  const parkedRows = parked.length === 0
    ? '<tr><td colspan="7" class="empty-state">No vehicles currently parked.</td></tr>'
    : parked.map((row) => {
        const mins = Math.max(0, Math.round((Date.now() - new Date(row.entry_time).getTime()) / 60000));
        return `<tr>
          <td><strong>${esc(row.vehicle_number)}</strong></td>
          <td><span class="badge badge-blue">${esc(row.vehicle_type)}</span></td>
          <td>${esc(row.owner_name || row.user_name || '—')}</td>
          <td><strong class="text-amber">${esc(row.slot_number)}</strong></td>
          <td>${esc(formatDateTimeShort(row.entry_time))}</td>
          <td>${esc(formatDuration(mins))}</td>
          <td><a href="vehicle_exit.php?vehicle_id=${row.id}" class="btn btn-danger btn-sm">Exit</a></td>
        </tr>`;
      }).join('');

  const pendingRows = pendingBills.length === 0
    ? '<tr><td colspan="6" class="empty-state">No pending payments. 🎉</td></tr>'
    : pendingBills.map((row) => `<tr>
        <td><strong>${esc(row.vehicle_number)}</strong></td>
        <td>${esc(row.slot_number)}</td>
        <td>${esc(formatDuration(row.duration_minutes))}</td>
        <td><strong class="text-amber">₹${money(row.amount)}</strong></td>
        <td>${esc(formatDateTimeShort(row.exit_time))}</td>
        <td><button onclick="markPaid(${row.id})" class="btn btn-success btn-sm">Mark Paid</button></td>
      </tr>`).join('');

  return layoutShell({
    title: 'Staff Dashboard',
    sidebar: renderStaffSidebar(user),
    topbarTitle: 'Dashboard',
    topbarRight: '<div class="live-badge"><div class="live-dot"></div> Live</div><span class="text-muted" id="clock"></span>',
    bodyContent: `
      <div class="stats-grid fade-in">
        <div class="stat-card amber"><div class="stat-label">Total Slots</div><div class="stat-value">${stats.total}</div><div class="stat-sub">All parking spaces</div></div>
        <div class="stat-card green"><div class="stat-label">Available</div><div class="stat-value">${stats.available}</div><div class="stat-sub">Ready for parking</div></div>
        <div class="stat-card red"><div class="stat-label">Occupied</div><div class="stat-value">${stats.occupied}</div><div class="stat-sub">Currently parked</div></div>
        <div class="stat-card blue"><div class="stat-label">Reserved</div><div class="stat-value">${stats.reserved}</div><div class="stat-sub">Pre-booked slots</div></div>
        <div class="stat-card amber"><div class="stat-label">Pending Payment</div><div class="stat-value">${stats.pending}</div><div class="stat-sub">Awaiting collection</div></div>
        <div class="stat-card green"><div class="stat-label">Today's Revenue</div><div class="stat-value" style="font-size:28px">₹${moneyInt(stats.today_revenue)}</div><div class="stat-sub">Collected today</div></div>
      </div>
      <div class="grid-2">
        <div class="card fade-in">
          <div class="card-header"><span class="card-title">🚀 Quick Actions</span></div>
          <div class="card-body" style="display:flex;flex-direction:column;gap:10px">
            <a href="vehicle_entry.php" class="btn btn-primary">🚗 New Vehicle Entry</a>
            <a href="vehicle_exit.php" class="btn btn-secondary">🚪 Process Vehicle Exit</a>
            <a href="payments.php" class="btn btn-secondary">💰 Manage Payments</a>
            <a href="bookings.php" class="btn btn-secondary">📅 View All Bookings</a>
          </div>
        </div>
        <div class="card fade-in">
          <div class="card-header"><span class="card-title">📊 Occupancy Rate</span></div>
          <div class="card-body">
            <div style="text-align:center;padding:10px 0">
              <div style="font-size:64px;font-family:'Rajdhani',sans-serif;font-weight:700;color:${color}">${pct}%</div>
              <div class="text-muted">${stats.occupied} of ${stats.total} slots occupied</div>
              <div style="margin-top:16px;background:var(--bg-surface);border-radius:20px;height:12px;overflow:hidden">
                <div style="height:100%;width:${pct}%;background:${color};border-radius:20px;transition:width 0.5s"></div>
              </div>
              ${pct >= 90 ? '<div class="badge badge-red" style="margin-top:10px">⚠️ Parking Almost Full!</div>' : ''}
            </div>
          </div>
        </div>
      </div>
      <div class="card fade-in">
        <div class="card-header">
          <span class="card-title">🅿️ Currently Parked Vehicles</span>
          <a href="parked.php" class="btn btn-secondary btn-sm">View All</a>
        </div>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Vehicle No.</th><th>Type</th><th>Owner</th><th>Slot</th><th>Entry Time</th><th>Duration</th><th>Action</th></tr></thead>
            <tbody>${parkedRows}</tbody>
          </table>
        </div>
      </div>
      <div class="card fade-in">
        <div class="card-header">
          <span class="card-title">💰 Pending Payments</span>
          <a href="payments.php" class="btn btn-secondary btn-sm">View All</a>
        </div>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Vehicle No.</th><th>Slot</th><th>Duration</th><th>Amount</th><th>Exit Time</th><th>Action</th></tr></thead>
            <tbody>${pendingRows}</tbody>
          </table>
        </div>
      </div>`,
    scripts: `<script>
function updateClock() {
  document.getElementById('clock').textContent = new Date().toLocaleTimeString('en-IN', {hour:'2-digit',minute:'2-digit',second:'2-digit'});
}
setInterval(updateClock, 1000);
updateClock();

async function markPaid(billId) {
  if (!confirm('Mark this payment as paid?')) return;
  const res = await apiCall('../php/payment_handler.php', { action: 'mark_paid', bill_id: billId });
  if (res.success) {
    showNotification('Payment Collected', res.message, 'success');
    setTimeout(() => location.reload(), 1200);
  } else {
    showNotification('Error', res.message, 'error');
  }
}
setTimeout(() => location.reload(), 30000);
</script>`,
  });
}

function renderStaffVehicleEntry({ user, avail4w, total4w, avail2w, total2w }) {
  return layoutShell({
    title: 'Vehicle Entry',
    sidebar: renderStaffSidebar(user),
    topbarTitle: 'Vehicle Entry',
    bodyContent: `
      <div class="grid-2">
        <div class="card fade-in">
          <div class="card-header"><span class="card-title">🚗 Register Vehicle Entry</span></div>
          <div class="card-body">
            <div class="stats-grid" style="grid-template-columns:1fr 1fr;gap:10px;margin-bottom:20px">
              <div class="stat-card green" style="padding:14px">
                <div class="stat-label">4-Wheeler Available</div>
                <div class="stat-value" style="font-size:28px">${avail4w}</div>
                <div class="stat-sub">of ${total4w}</div>
              </div>
              <div class="stat-card blue" style="padding:14px">
                <div class="stat-label">2-Wheeler Available</div>
                <div class="stat-value" style="font-size:28px">${avail2w}</div>
                <div class="stat-sub">of ${total2w}</div>
              </div>
            </div>
            <form class="dash-form" id="entryForm">
              <div class="form-row">
                <div class="form-group">
                  <label>Vehicle Number *</label>
                  <input type="text" id="vehicle_number" placeholder="TN38AB1234" required style="text-transform:uppercase" oninput="this.value=this.value.toUpperCase()">
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
              <button type="submit" class="btn btn-primary" style="margin-top:8px">🚗 Register Entry &amp; Assign Slot</button>
            </form>
            <div id="entryResult" style="display:none;margin-top:20px"></div>
          </div>
        </div>
        <div class="card fade-in">
          <div class="card-header"><span class="card-title">🔲 Slot Availability</span></div>
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
      <div class="card fade-in">
        <div class="card-header"><span class="card-title">🕐 Recent Entries Today</span></div>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Vehicle No.</th><th>Type</th><th>Owner</th><th>Slot</th><th>Entry Time</th></tr></thead>
            <tbody id="recentEntries"><tr><td colspan="5" class="empty-state">Loading...</td></tr></tbody>
          </table>
        </div>
      </div>`,
    scripts: `<script>
async function loadSlots() {
  const res = await fetch('../php/slots_api.php');
  const data = await res.json();
  if (!data.success) return;
  document.getElementById('slotsGrid').innerHTML = '<div class="slots-grid">' + data.slots.map(s => \`
    <div class="slot-box slot-\${s.status}">
      \${s.slot_number}
      <span class="slot-type">\${s.slot_type}</span>
    </div>\`).join('') + '</div>';
}

async function loadRecent() {
  const res = await fetch('../php/vehicle_api.php?action=recent_entries');
  const data = await res.json();
  const tbody = document.getElementById('recentEntries');
  if (!data.success || !data.vehicles.length) {
    tbody.innerHTML = '<tr><td colspan="5" class="empty-state">No entries today.</td></tr>';
    return;
  }
  tbody.innerHTML = data.vehicles.map(v => \`
    <tr>
      <td><strong>\${v.vehicle_number}</strong></td>
      <td><span class="badge badge-blue">\${v.vehicle_type}</span></td>
      <td>\${v.owner_name || '—'}</td>
      <td><strong class="text-amber">\${v.slot_number || '—'}</strong></td>
      <td>\${new Date(v.entry_time).toLocaleTimeString('en-IN', {hour:'2-digit',minute:'2-digit',hour12:true})}</td>
    </tr>\`).join('');
}

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
    showNotification('Entry Registered', \`Slot \${res.slot} assigned to \${data.vehicle_number}\`, 'success');
    resultDiv.innerHTML = \`
      <div class="receipt">
        <div style="text-align:center;margin-bottom:14px">
          <div style="font-size:22px;font-family:'Rajdhani',sans-serif;font-weight:700;color:var(--green)">✅ Entry Registered</div>
        </div>
        <div class="receipt-row"><span>Vehicle No.</span><strong>\${data.vehicle_number}</strong></div>
        <div class="receipt-row"><span>Type</span><strong>\${data.vehicle_type === '4W' ? '4-Wheeler' : '2-Wheeler'}</strong></div>
        <div class="receipt-row"><span>Slot Assigned</span><strong class="text-amber">\${res.slot}</strong></div>
        <div class="receipt-row"><span>Entry Time</span><strong>\${new Date().toLocaleString('en-IN')}</strong></div>
        <div class="receipt-row"><span>Rate</span><strong>₹\${data.vehicle_type === '4W' ? '${RATE_PER_HOUR_4W}' : '${RATE_PER_HOUR_2W}'}/hour</strong></div>
      </div>\`;
    document.getElementById('entryForm').reset();
    loadSlots();
    loadRecent();
  } else {
    showNotification('Error', res.message, 'error');
    resultDiv.innerHTML = \`<div class="role-note" style="background:var(--red-dim);border-color:rgba(239,68,68,0.3);color:var(--red);">❌ \${res.message}</div>\`;
  }
});

loadRecent();
startSlotRefresh(loadSlots, 20000);
</script>`,
  });
}

function renderStaffVehicleExit({ user, parkedList, preselect }) {
  const optionsHtml = parkedList.map((v) => `
    <option value="${v.id}"
      data-number="${esc(v.vehicle_number)}"
      data-type="${esc(v.vehicle_type)}"
      data-owner="${esc(v.owner_name || '')}"
      data-slot="${esc(v.slot_number || '')}"
      data-entry="${new Date(v.entry_time).toISOString()}"
      ${Number(preselect) === Number(v.id) ? 'selected' : ''}>
      ${esc(v.vehicle_number)} — Slot ${esc(v.slot_number || '—')}
    </option>`).join('');

  return layoutShell({
    title: 'Vehicle Exit',
    sidebar: renderStaffSidebar(user),
    topbarTitle: 'Vehicle Exit',
    bodyContent: `
      <div class="grid-2">
        <div class="card fade-in">
          <div class="card-header"><span class="card-title">🚪 Process Vehicle Exit</span></div>
          <div class="card-body">
            ${parkedList.length === 0 ? `
              <div class="empty-state"><div class="icon">🅿️</div><p>No vehicles currently parked.</p></div>
            ` : `
              <div class="dash-form">
                <div class="form-group">
                  <label>Select Vehicle *</label>
                  <select id="vehicleSelect" onchange="loadVehicleInfo()">
                    <option value="">— Select a vehicle —</option>
                    ${optionsHtml}
                  </select>
                </div>
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
                  <button onclick="processExit()" class="btn btn-danger w-full">🚪 Process Exit &amp; Free Slot</button>
                </div>
              </div>`}
          </div>
        </div>
        <div class="card fade-in">
          <div class="card-header"><span class="card-title">🧾 Generated Bill</span></div>
          <div class="card-body">
            <div id="billOutput">
              <div class="empty-state"><div class="icon">🧾</div><p>Select a vehicle to generate bill.</p></div>
            </div>
          </div>
        </div>
      </div>
      <div class="card fade-in">
        <div class="card-header"><span class="card-title">🕐 Exit History Today</span></div>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Vehicle No.</th><th>Slot</th><th>Duration</th><th>Amount</th><th>Payment</th><th>Exit Time</th></tr></thead>
            <tbody id="exitHistory"><tr><td colspan="6" class="empty-state">Loading...</td></tr></tbody>
          </table>
        </div>
      </div>`,
    scripts: `<script>
const RATE_4W = ${RATE_PER_HOUR_4W}, RATE_2W = ${RATE_PER_HOUR_2W}, MIN_CHARGE = ${MIN_CHARGE};
let durationTimer = null;

function loadVehicleInfo() {
  const sel = document.getElementById('vehicleSelect');
  if (!sel) return;
  const opt = sel.options[sel.selectedIndex];
  if (!opt || !opt.value) {
    document.getElementById('vehicleDetails').style.display = 'none';
    return;
  }
  const entry = new Date(opt.dataset.entry);
  const rate = opt.dataset.type === '4W' ? RATE_4W : RATE_2W;
  const updateNumbers = () => {
    const mins = Math.max(1, Math.ceil((Date.now() - entry.getTime()) / 60000));
    const h = Math.floor(mins / 60), m = mins % 60;
    const amount = Math.max(MIN_CHARGE, Math.ceil(mins / 60) * rate);
    document.getElementById('d_dur').textContent = h > 0 ? \`\${h}h \${m}m\` : \`\${m}m\`;
    document.getElementById('d_amount').textContent = \`₹\${amount.toFixed(2)}\`;
  };
  document.getElementById('d_number').textContent = opt.dataset.number;
  document.getElementById('d_type').textContent = opt.dataset.type === '4W' ? '4-Wheeler' : '2-Wheeler';
  document.getElementById('d_owner').textContent = opt.dataset.owner || '—';
  document.getElementById('d_slot').textContent = opt.dataset.slot;
  document.getElementById('d_entry').textContent = entry.toLocaleString('en-IN');
  updateNumbers();
  document.getElementById('vehicleDetails').style.display = 'block';
  clearInterval(durationTimer);
  durationTimer = setInterval(updateNumbers, 30000);
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
    showNotification('Exit Processed', \`\${res.vehicle_number} has exited. Bill: ₹\${res.amount}\`, 'success');
    document.getElementById('billOutput').innerHTML = \`
      <div class="receipt">
        <div style="text-align:center;margin-bottom:14px;font-family:'Rajdhani',sans-serif;font-size:20px;font-weight:700;color:var(--amber)">🧾 PARKING RECEIPT</div>
        <div class="receipt-row"><span>Vehicle No.</span><strong>\${res.vehicle_number}</strong></div>
        <div class="receipt-row"><span>Slot</span><strong>\${res.slot}</strong></div>
        <div class="receipt-row"><span>Entry</span><strong>\${new Date(res.entry_time).toLocaleString('en-IN')}</strong></div>
        <div class="receipt-row"><span>Exit</span><strong>\${new Date(res.exit_time).toLocaleString('en-IN')}</strong></div>
        <div class="receipt-row"><span>Duration</span><strong>\${res.duration}</strong></div>
        <div class="receipt-row total"><span>Total</span><strong>₹\${res.amount}</strong></div>
        <div style="text-align:center;margin-top:12px">
          <span class="badge \${res.payment_status === 'paid' ? 'badge-green' : 'badge-yellow'}">
            \${res.payment_status === 'paid' ? '✅ PAID' : '⏳ PENDING'}
          </span>
        </div>
      </div>\`;
    document.getElementById('vehicleDetails').style.display = 'none';
    document.getElementById('vehicleSelect').value = '';
    const opt = document.querySelector(\`#vehicleSelect option[value="\${vehicleId}"]\`);
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
    const h = Math.floor(v.duration_minutes / 60), m = v.duration_minutes % 60;
    return \`<tr>
      <td><strong>\${v.vehicle_number}</strong></td>
      <td>\${v.slot_number || '—'}</td>
      <td>\${h > 0 ? h + 'h ' + m + 'm' : m + 'm'}</td>
      <td><strong class="text-amber">₹\${parseFloat(v.amount).toFixed(2)}</strong></td>
      <td><span class="badge \${v.payment_status === 'paid' ? 'badge-green' : 'badge-yellow'}">\${v.payment_status}</span></td>
      <td>\${new Date(v.exit_time).toLocaleString('en-IN',{hour:'2-digit',minute:'2-digit',hour12:true,day:'2-digit',month:'short'})}</td>
    </tr>\`;
  }).join('');
}

const selEl = document.getElementById('vehicleSelect');
if (selEl && selEl.value) loadVehicleInfo();
loadExitHistory();
</script>`,
  });
}

function renderStaffParked({ user, parked }) {
  const rows = parked.length === 0
    ? '<tr><td colspan="9" class="empty-state"><div class="icon">🅿️</div>No vehicles parked right now.</td></tr>'
    : parked.map((v) => {
        const { durationMinutes, amount } = calculateFare(v.entry_time, v.vehicle_type);
        return `<tr>
          <td><strong>${esc(v.vehicle_number)}</strong></td>
          <td><span class="badge badge-blue">${esc(v.vehicle_type)}</span></td>
          <td>${esc(v.owner_name || v.user_name || '—')}</td>
          <td>${esc(v.phone || '—')}</td>
          <td><strong class="text-amber">${esc(v.slot_number)}</strong></td>
          <td>${esc(formatDateTimeShort(v.entry_time))}</td>
          <td>${esc(formatDuration(durationMinutes))}</td>
          <td><strong>₹${amount}</strong></td>
          <td><a href="vehicle_exit.php?vehicle_id=${v.id}" class="btn btn-danger btn-sm">Exit</a></td>
        </tr>`;
      }).join('');

  return layoutShell({
    title: 'Parked Vehicles',
    sidebar: renderStaffSidebar(user),
    topbarTitle: 'Parked Vehicles',
    topbarRight: '<div class="live-badge"><div class="live-dot"></div> Live</div>',
    bodyContent: `
      <div class="card fade-in">
        <div class="card-header">
          <span class="card-title">🅿️ Currently Parked</span>
          <div class="search-bar" style="margin:0;width:240px">
            <input type="text" id="searchInput" placeholder="Search vehicle...">
          </div>
        </div>
        <div class="table-wrap">
          <table id="parkedTable">
            <thead><tr><th>Vehicle No.</th><th>Type</th><th>Owner</th><th>Phone</th><th>Slot</th><th>Entry Time</th><th>Duration</th><th>Est. Bill</th><th>Action</th></tr></thead>
            <tbody>${rows}</tbody>
          </table>
        </div>
      </div>`,
    scripts: `<script>
filterTable('searchInput', 'parkedTable');
setTimeout(() => location.reload(), 60000);
</script>`,
  });
}

function renderStaffPayments({ user, bills, totalPaid, totalPending, todayRevenue, filter }) {
  const rows = bills.length === 0
    ? '<tr><td colspan="8" class="empty-state">No records found.</td></tr>'
    : bills.map((b) => `<tr>
        <td>#${b.id}</td>
        <td><strong>${esc(b.vehicle_number)}</strong><div class="text-muted">${esc(b.user_name || '—')}</div></td>
        <td>${esc(b.slot_number || '—')}</td>
        <td>${esc(formatDuration(b.duration_minutes))}</td>
        <td><strong class="text-amber">₹${money(b.amount)}</strong></td>
        <td><span class="badge ${b.payment_status === 'paid' ? 'badge-green' : 'badge-yellow'}">${esc(b.payment_status)}</span></td>
        <td>${esc(formatDateTimeShort(b.exit_time))}</td>
        <td>${b.payment_status === 'pending'
          ? `<button onclick="markPaid(${b.id})" class="btn btn-success btn-sm">Mark Paid</button>`
          : '<span class="text-muted">—</span>'}</td>
      </tr>`).join('');

  return layoutShell({
    title: 'Payments',
    sidebar: renderStaffSidebar(user),
    topbarTitle: 'Payments',
    bodyContent: `
      <div class="stats-grid fade-in">
        <div class="stat-card green"><div class="stat-label">Total Collected</div><div class="stat-value" style="font-size:28px">₹${moneyInt(totalPaid)}</div><div class="stat-sub">All time paid</div></div>
        <div class="stat-card amber"><div class="stat-label">Pending Amount</div><div class="stat-value" style="font-size:28px">₹${moneyInt(totalPending)}</div><div class="stat-sub">Awaiting collection</div></div>
        <div class="stat-card blue"><div class="stat-label">Today's Revenue</div><div class="stat-value" style="font-size:28px">₹${moneyInt(todayRevenue)}</div><div class="stat-sub">Collected today</div></div>
      </div>
      <div class="card fade-in">
        <div class="card-header">
          <span class="card-title">💰 Billing Records</span>
          <div class="flex gap-2">
            <a href="?filter=all" class="btn btn-sm ${filter === 'all' ? 'btn-primary' : 'btn-secondary'}">All</a>
            <a href="?filter=pending" class="btn btn-sm ${filter === 'pending' ? 'btn-primary' : 'btn-secondary'}">Pending</a>
            <a href="?filter=paid" class="btn btn-sm ${filter === 'paid' ? 'btn-primary' : 'btn-secondary'}">Paid</a>
          </div>
        </div>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Bill #</th><th>Vehicle</th><th>Slot</th><th>Duration</th><th>Amount</th><th>Status</th><th>Exit Time</th><th>Action</th></tr></thead>
            <tbody>${rows}</tbody>
          </table>
        </div>
      </div>`,
    scripts: `<script>
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
</script>`,
  });
}

function renderStaffBookings({ user, bookings }) {
  const cls = { active: 'badge-blue', completed: 'badge-green', cancelled: 'badge-red' };
  const rows = bookings.length === 0
    ? '<tr><td colspan="8" class="empty-state">No bookings found.</td></tr>'
    : bookings.map((b) => `<tr>
        <td>#${b.id}</td>
        <td><strong>${esc(b.user_name)}</strong><div class="text-muted">${esc(b.phone || '')}</div></td>
        <td><strong>${esc(b.vehicle_number)}</strong></td>
        <td><strong class="text-amber">${esc(b.slot_number)}</strong></td>
        <td>${esc(formatDateOnly(b.booking_date))}</td>
        <td>${esc(formatTime12(b.start_time))} – ${esc(formatTime12(b.end_time))}</td>
        <td><span class="badge ${cls[b.status] || 'badge-muted'}">${esc(b.status)}</span></td>
        <td>${b.status === 'active' ? `<button onclick="cancelBooking(${b.id})" class="btn btn-danger btn-sm">Cancel</button>` : ''}</td>
      </tr>`).join('');

  return layoutShell({
    title: 'Bookings',
    sidebar: renderStaffSidebar(user),
    topbarTitle: 'All Bookings',
    bodyContent: `
      <div class="card fade-in">
        <div class="card-header">
          <span class="card-title">📅 Pre-Bookings</span>
          <div class="search-bar" style="margin:0;width:250px">
            <input type="text" id="searchInput" placeholder="Search booking...">
          </div>
        </div>
        <div class="table-wrap">
          <table id="bookingsTable">
            <thead><tr><th>ID</th><th>User</th><th>Vehicle No.</th><th>Slot</th><th>Date</th><th>Time</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>${rows}</tbody>
          </table>
        </div>
      </div>`,
    scripts: `<script>
filterTable('searchInput', 'bookingsTable');
async function cancelBooking(id) {
  if (!confirm('Cancel this booking?')) return;
  const res = await apiCall('../php/booking_handler.php', { action: 'cancel', booking_id: id });
  if (res.success) {
    showNotification('Cancelled', 'Booking cancelled and slot freed.', 'success');
    setTimeout(() => location.reload(), 1000);
  } else {
    showNotification('Error', res.message, 'error');
  }
}
</script>`,
  });
}

function renderStaffSlots({ user, slots }) {
  const total = slots.length;
  const avail = slots.filter((s) => s.status === 'available').length;
  const occup = slots.filter((s) => s.status === 'occupied').length;
  const resvd = slots.filter((s) => s.status === 'reserved').length;

  const renderGrid = (type) => slots
    .filter((s) => s.slot_type === type)
    .map((s) => `<div class="slot-box slot-${esc(s.status)}" title="${esc(s.vehicle_number || s.status)}">
      ${esc(s.slot_number)}
      <span class="slot-type">${esc(s.vehicle_number || s.status.toUpperCase())}</span>
    </div>`).join('');

  return layoutShell({
    title: 'Slot Overview',
    sidebar: renderStaffSidebar(user),
    topbarTitle: 'Slot Overview',
    topbarRight: '<div class="live-badge"><div class="live-dot"></div>Live</div>',
    bodyContent: `
      <div class="stats-grid fade-in">
        <div class="stat-card amber"><div class="stat-label">Total Slots</div><div class="stat-value">${total}</div></div>
        <div class="stat-card green"><div class="stat-label">Available</div><div class="stat-value">${avail}</div></div>
        <div class="stat-card red"><div class="stat-label">Occupied</div><div class="stat-value">${occup}</div></div>
        <div class="stat-card amber"><div class="stat-label">Reserved</div><div class="stat-value">${resvd}</div></div>
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
        <div class="card-body"><div class="slots-grid">${renderGrid('4W')}</div></div>
      </div>
      <div class="card fade-in">
        <div class="card-header"><span class="card-title">🔲 2-Wheeler Slots (B-Block)</span></div>
        <div class="card-body"><div class="slots-grid">${renderGrid('2W')}</div></div>
      </div>`,
    scripts: `<script>setTimeout(() => location.reload(), 30000);</script>`,
  });
}

function renderUserDashboard({ user, total, avail, occup, active, pendingAmt, bookings, history }) {
  let activeCard = '';
  if (active) {
    const { durationMinutes, amount } = calculateFare(active.entry_time, active.vehicle_type);
    activeCard = `
      <div class="card fade-in" style="border-color:var(--amber);border-left:3px solid var(--amber)">
        <div class="card-header">
          <span class="card-title text-amber">🚗 Vehicle Currently Parked</span>
          <span class="badge badge-green">Active</span>
        </div>
        <div class="card-body">
          <div class="receipt">
            <div class="receipt-row"><span>Vehicle No.</span><strong>${esc(active.vehicle_number)}</strong></div>
            <div class="receipt-row"><span>Slot</span><strong class="text-amber">${esc(active.slot_number)}</strong></div>
            <div class="receipt-row"><span>Entry Time</span><strong>${esc(formatDateTimeFull(active.entry_time))}</strong></div>
            <div class="receipt-row"><span>Duration</span><strong>${esc(formatDuration(durationMinutes))}</strong></div>
            <div class="receipt-row total"><span>Estimated Bill</span><strong>₹${amount}</strong></div>
          </div>
        </div>
      </div>`;
  }

  const bookingCards = bookings.length === 0
    ? '<div class="empty-state"><div class="icon">📅</div><p>No upcoming bookings.</p><a href="booking.php" class="btn btn-primary btn-sm" style="margin-top:10px">Book a Slot</a></div>'
    : bookings.map((b) => `
        <div style="background:var(--bg-surface);border-radius:10px;padding:14px;margin-bottom:10px">
          <div class="flex items-center" style="justify-content:space-between;margin-bottom:6px">
            <strong class="text-amber">${esc(b.vehicle_number)}</strong>
            <span class="badge badge-blue">Slot ${esc(b.slot_number)}</span>
          </div>
          <div style="font-size:13px;color:var(--text-secondary)">
            📅 ${esc(formatDateOnly(b.booking_date))} &nbsp;
            🕐 ${esc(formatTime12(b.start_time))} – ${esc(formatTime12(b.end_time))}
          </div>
          <button onclick="cancelBooking(${b.id})" class="btn btn-danger btn-sm" style="margin-top:8px">Cancel</button>
        </div>`).join('');

  const historyCards = history.length === 0
    ? '<div class="empty-state"><div class="icon">🚗</div><p>No parking history.</p></div>'
    : history.map((v) => `
        <div style="background:var(--bg-surface);border-radius:10px;padding:12px;margin-bottom:8px;display:flex;justify-content:space-between;align-items:center">
          <div>
            <strong>${esc(v.vehicle_number)}</strong>
            <div class="text-muted">${v.slot_number ? `Slot ${esc(v.slot_number)}` : 'Exited'} · ${esc(formatDayMonth(v.created_at))}</div>
          </div>
          <span class="badge ${v.status === 'parked' ? 'badge-green' : 'badge-muted'}">${esc(v.status)}</span>
        </div>`).join('');

  return layoutShell({
    title: 'Dashboard',
    sidebar: renderUserSidebar(user),
    topbarTitle: 'My Dashboard',
    topbarRight: '<div class="live-badge"><div class="live-dot"></div>Live</div><span class="text-muted" id="clock"></span>',
    bodyContent: `
      <div class="profile-card fade-in">
        <div class="profile-avatar">${esc((user.name || 'U').charAt(0).toUpperCase())}</div>
        <div class="profile-meta">
          <h2>${esc(user.name)}</h2>
          <p>${esc(user.email)}</p>
          <p>${esc(user.phone || '')}</p>
          <span class="role-chip">Vehicle Owner</span>
        </div>
      </div>
      <div class="stats-grid fade-in">
        <div class="stat-card amber"><div class="stat-label">Total Slots</div><div class="stat-value">${total}</div><div class="stat-sub">Parking capacity</div></div>
        <div class="stat-card green"><div class="stat-label">Available Now</div><div class="stat-value">${avail}</div><div class="stat-sub">Ready for parking</div></div>
        <div class="stat-card red"><div class="stat-label">Occupied</div><div class="stat-value">${occup}</div><div class="stat-sub">Currently in use</div></div>
        <div class="stat-card ${pendingAmt > 0 ? 'amber' : 'green'}">
          <div class="stat-label">Pending Payment</div>
          <div class="stat-value" style="font-size:26px">₹${moneyInt(pendingAmt)}</div>
          <div class="stat-sub">${pendingAmt > 0 ? 'Dues remaining' : 'All clear!'}</div>
        </div>
      </div>
      ${activeCard}
      <div class="grid-2">
        <div class="card fade-in">
          <div class="card-header"><span class="card-title">📅 Upcoming Bookings</span><a href="booking.php" class="btn btn-primary btn-sm">+ New Booking</a></div>
          <div class="card-body">${bookingCards}</div>
        </div>
        <div class="card fade-in">
          <div class="card-header"><span class="card-title">🕐 Recent Parking</span><a href="history.php" class="btn btn-secondary btn-sm">View All</a></div>
          <div class="card-body">${historyCards}</div>
        </div>
      </div>
      <div class="card fade-in">
        <div class="card-header"><span class="card-title">🔍 Search Vehicle History</span></div>
        <div class="card-body">
          <div class="search-bar">
            <input type="text" id="vehicleSearch" placeholder="Enter vehicle number (e.g. TN38AB1234)">
            <button onclick="searchVehicle()" class="btn btn-primary">Search</button>
          </div>
          <div id="searchResults"></div>
        </div>
      </div>`,
    scripts: `<script>
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
  const res = await fetch(\`../php/vehicle_api.php?action=search&vehicle_number=\${encodeURIComponent(q)}\`);
  const data = await res.json();
  const div = document.getElementById('searchResults');
  if (!data.success || !data.vehicles.length) {
    div.innerHTML = '<div class="empty-state" style="padding:20px"><p>No records found</p></div>';
    return;
  }
  div.innerHTML = \`<div class="table-wrap" style="margin-top:12px"><table>
    <thead><tr><th>Vehicle No.</th><th>Slot</th><th>Entry</th><th>Exit</th><th>Status</th></tr></thead>
    <tbody>\${data.vehicles.map(v=>\`<tr>
      <td><strong>\${v.vehicle_number}</strong></td>
      <td>\${v.slot_number||'—'}</td>
      <td>\${v.entry_time ? new Date(v.entry_time).toLocaleString('en-IN') : '—'}</td>
      <td>\${v.exit_time ? new Date(v.exit_time).toLocaleString('en-IN') : '—'}</td>
      <td><span class="badge \${v.status==='parked'?'badge-green':'badge-muted'}">\${v.status}</span></td>
    </tr>\`).join('')}</tbody></table></div>\`;
}
</script>`,
  });
}

function renderUserHistory({ user, history }) {
  const rows = history.length === 0
    ? '<tr><td colspan="8" class="empty-state">No parking history found.</td></tr>'
    : history.map((v, idx) => {
        let mins = 0;
        if (v.entry_time && v.exit_time) {
          mins = Math.round((new Date(v.exit_time).getTime() - new Date(v.entry_time).getTime()) / 60000);
        } else if (v.entry_time && v.status === 'parked') {
          mins = Math.round((Date.now() - new Date(v.entry_time).getTime()) / 60000);
        }
        return `<tr>
          <td>${idx + 1}</td>
          <td><strong>${esc(v.vehicle_number)}</strong></td>
          <td><span class="badge badge-blue">${esc(v.vehicle_type)}</span></td>
          <td>${esc(v.slot_number || '—')}</td>
          <td>${esc(formatDateTimeFull(v.entry_time))}</td>
          <td>${v.exit_time ? esc(formatDateTimeFull(v.exit_time)) : '<span class="badge badge-green">Still Parked</span>'}</td>
          <td>${mins > 0 ? esc(formatDuration(mins)) : '—'}</td>
          <td><span class="badge ${v.status === 'parked' ? 'badge-green' : 'badge-muted'}">${esc(v.status)}</span></td>
        </tr>`;
      }).join('');

  return layoutShell({
    title: 'Parking History',
    sidebar: renderUserSidebar(user),
    topbarTitle: 'Parking History',
    bodyContent: `
      <div class="card fade-in">
        <div class="card-header">
          <span class="card-title">🕐 My Parking History</span>
          <div class="search-bar" style="margin:0;width:240px">
            <input type="text" id="searchInput" placeholder="Search...">
          </div>
        </div>
        <div class="table-wrap">
          <table id="histTable">
            <thead><tr><th>#</th><th>Vehicle No.</th><th>Type</th><th>Slot</th><th>Entry</th><th>Exit</th><th>Duration</th><th>Status</th></tr></thead>
            <tbody>${rows}</tbody>
          </table>
        </div>
      </div>`,
    scripts: `<script>filterTable('searchInput','histTable');</script>`,
  });
}

function renderUserBilling({ user, bills, paidTotal, pendingTotal }) {
  const rows = bills.length === 0
    ? '<tr><td colspan="8" class="empty-state">No billing records found.</td></tr>'
    : bills.map((b) => `<tr>
        <td>#${b.id}</td>
        <td><strong>${esc(b.vehicle_number)}</strong></td>
        <td>${esc(b.slot_number || '—')}</td>
        <td>${esc(formatDateTimeShort(b.entry_time))}</td>
        <td>${esc(formatDateTimeShort(b.exit_time))}</td>
        <td>${esc(formatDuration(b.duration_minutes))}</td>
        <td><strong class="text-amber">₹${money(b.amount)}</strong></td>
        <td><span class="badge ${b.payment_status === 'paid' ? 'badge-green' : 'badge-yellow'}">${esc(b.payment_status)}</span></td>
      </tr>`).join('');

  return layoutShell({
    title: 'Billing',
    sidebar: renderUserSidebar(user),
    topbarTitle: 'Billing & Payments',
    bodyContent: `
      <div class="stats-grid fade-in">
        <div class="stat-card green"><div class="stat-label">Total Paid</div><div class="stat-value" style="font-size:28px">₹${moneyInt(paidTotal)}</div><div class="stat-sub">All time payments</div></div>
        <div class="stat-card ${pendingTotal > 0 ? 'amber' : 'green'}">
          <div class="stat-label">Pending Amount</div>
          <div class="stat-value" style="font-size:28px">₹${moneyInt(pendingTotal)}</div>
          <div class="stat-sub">${pendingTotal > 0 ? 'Please pay at counter' : 'All dues cleared!'}</div>
        </div>
      </div>
      ${pendingTotal > 0 ? `
        <div class="card fade-in" style="border-color:var(--amber);border-left:3px solid var(--amber);margin-bottom:20px">
          <div class="card-body" style="padding:16px">
            ⚠️ You have <strong>₹${money(pendingTotal)}</strong> in pending payments. Please pay at the parking counter.
          </div>
        </div>` : ''}
      <div class="card fade-in">
        <div class="card-header"><span class="card-title">💳 Billing History</span></div>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Bill #</th><th>Vehicle</th><th>Slot</th><th>Entry</th><th>Exit</th><th>Duration</th><th>Amount</th><th>Status</th></tr></thead>
            <tbody>${rows}</tbody>
          </table>
        </div>
      </div>`,
  });
}

function renderUserBooking({ user }) {
  return layoutShell({
    title: 'Book a Slot',
    sidebar: renderUserSidebar(user),
    topbarTitle: 'Pre-Book a Slot',
    bodyContent: `
      <div class="grid-2">
        <div class="card fade-in">
          <div class="card-header"><span class="card-title">📅 Reserve a Parking Slot</span></div>
          <div class="card-body">
            <div class="role-note">💡 Book a slot in advance. Reserved slots won't be assigned to others during your booking time.</div>
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
                <select id="b_slot" required><option value="">— Select date &amp; time first —</option></select>
              </div>
              <button type="submit" class="btn btn-primary" style="margin-top:8px">📅 Confirm Booking</button>
            </form>
            <div id="bookingResult" style="margin-top:16px;display:none"></div>
          </div>
        </div>
        <div class="card fade-in">
          <div class="card-header"><span class="card-title">🔲 Slot Availability</span></div>
          <div class="card-body">
            <div class="mb-2" style="font-size:12px;color:var(--text-muted);margin-bottom:12px">
              <span class="badge badge-green">Available</span>&nbsp;
              <span class="badge badge-red">Occupied</span>&nbsp;
              <span class="badge badge-yellow">Reserved</span>
            </div>
            <div id="slotGrid"><div class="empty-state"><div class="icon">📅</div><p>Select date &amp; time to see available slots.</p></div></div>
          </div>
        </div>
      </div>`,
    scripts: `<script>
document.getElementById('b_date').min = new Date().toLocaleDateString('en-CA');

async function loadAvailableSlots() {
  const date  = document.getElementById('b_date').value;
  const start = document.getElementById('b_start').value;
  const end   = document.getElementById('b_end').value;
  const type  = document.getElementById('b_type').value;
  if (!date || !start || !end) return;
  const res = await fetch(\`../php/slots_api.php?date=\${encodeURIComponent(date)}&start=\${encodeURIComponent(start)}&end=\${encodeURIComponent(end)}&type=\${encodeURIComponent(type)}\`);
  const data = await res.json();
  const sel = document.getElementById('b_slot');
  sel.innerHTML = '<option value="">— Choose a slot —</option>';
  const grid = document.getElementById('slotGrid');
  if (!data.success) {
    grid.innerHTML = '<div class="empty-state"><p>' + data.message + '</p></div>';
    return;
  }
  let gridHTML = '<div class="slots-grid">';
  data.slots.forEach(s => {
    const cls = s.booking_available ? 'slot-available' : 'slot-occupied';
    gridHTML += \`<div class="slot-box \${cls}">\${s.slot_number}<span class="slot-type">\${s.slot_type}</span></div>\`;
    if (s.booking_available) sel.innerHTML += \`<option value="\${s.id}">\${s.slot_number} (\${s.slot_type})</option>\`;
  });
  gridHTML += '</div>';
  grid.innerHTML = gridHTML;
}

document.getElementById('bookingForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const data = {
    action: 'create',
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
    showNotification('Booking Confirmed!', \`Slot \${res.slot} reserved for \${data.vehicle_number}\`, 'success');
    div.innerHTML = \`
      <div class="receipt">
        <div style="text-align:center;font-family:'Rajdhani',sans-serif;font-size:20px;font-weight:700;color:var(--green);margin-bottom:12px">✅ Booking Confirmed!</div>
        <div class="receipt-row"><span>Booking ID</span><strong>#\${res.booking_id}</strong></div>
        <div class="receipt-row"><span>Vehicle No.</span><strong>\${data.vehicle_number}</strong></div>
        <div class="receipt-row"><span>Slot</span><strong class="text-amber">\${res.slot}</strong></div>
        <div class="receipt-row"><span>Date</span><strong>\${data.booking_date}</strong></div>
        <div class="receipt-row"><span>Time</span><strong>\${data.start_time} – \${data.end_time}</strong></div>
      </div>\`;
    document.getElementById('bookingForm').reset();
    document.getElementById('slotGrid').innerHTML = '<div class="empty-state"><p>Booking confirmed! Select another slot to book more.</p></div>';
  } else {
    showNotification('Error', res.message, 'error');
    div.innerHTML = \`<div class="role-note" style="background:var(--red-dim);border-color:rgba(239,68,68,0.3);color:var(--red)">❌ \${res.message}</div>\`;
  }
});
</script>`,
  });
}

function renderUserMyBookings({ user, bookings }) {
  const cls = { active: 'badge-blue', completed: 'badge-green', cancelled: 'badge-red' };
  const rows = bookings.length === 0
    ? `<tr><td colspan="8" class="empty-state"><div class="icon">📅</div><p>No bookings yet. <a href="booking.php" style="color:var(--amber)">Book a slot now!</a></p></td></tr>`
    : bookings.map((b) => `<tr>
        <td>#${b.id}</td>
        <td><strong>${esc(b.vehicle_number)}</strong></td>
        <td><strong class="text-amber">${esc(b.slot_number)}</strong></td>
        <td>${esc(formatDateOnly(b.booking_date))}</td>
        <td>${esc(formatTime12(b.start_time))} – ${esc(formatTime12(b.end_time))}</td>
        <td><span class="badge ${cls[b.status] || 'badge-muted'}">${esc(b.status)}</span></td>
        <td>${esc(formatDateOnly(b.created_at))}</td>
        <td>${b.status === 'active' ? `<button onclick="cancelBooking(${b.id})" class="btn btn-danger btn-sm">Cancel</button>` : '—'}</td>
      </tr>`).join('');

  return layoutShell({
    title: 'My Bookings',
    sidebar: renderUserSidebar(user),
    topbarTitle: 'My Bookings',
    topbarRight: '<a href="booking.php" class="btn btn-primary btn-sm">+ New Booking</a>',
    bodyContent: `
      <div class="card fade-in">
        <div class="card-header"><span class="card-title">📋 Booking History</span></div>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Booking #</th><th>Vehicle No.</th><th>Slot</th><th>Date</th><th>Time Slot</th><th>Status</th><th>Booked On</th><th>Action</th></tr></thead>
            <tbody>${rows}</tbody>
          </table>
        </div>
      </div>`,
    scripts: `<script>
async function cancelBooking(id) {
  if (!confirm('Are you sure you want to cancel this booking?')) return;
  const res = await apiCall('../php/booking_handler.php', {action:'cancel', booking_id:id});
  if (res.success) { showNotification('Cancelled','Booking cancelled successfully.','success'); setTimeout(()=>location.reload(),1000); }
  else showNotification('Error', res.message, 'error');
}
</script>`,
  });
}

function renderUserSlots({ user, slots }) {
  const avail = slots.filter((s) => s.status === 'available').length;
  const occup = slots.filter((s) => s.status === 'occupied').length;
  const resvd = slots.filter((s) => s.status === 'reserved').length;

  const renderGrid = (type) => slots
    .filter((s) => s.slot_type === type)
    .map((s) => `<div class="slot-box slot-${esc(s.status)}">
      ${esc(s.slot_number)}
      <span class="slot-type">${s.status === 'occupied' ? 'IN USE' : esc(s.status.toUpperCase())}</span>
    </div>`).join('');

  return layoutShell({
    title: 'Slot Status',
    sidebar: renderUserSidebar(user),
    topbarTitle: 'Slot Status',
    topbarRight: '<div class="live-badge"><div class="live-dot"></div>Live</div>',
    bodyContent: `
      <div class="stats-grid fade-in">
        <div class="stat-card amber"><div class="stat-label">Total</div><div class="stat-value">${slots.length}</div></div>
        <div class="stat-card green"><div class="stat-label">Available</div><div class="stat-value">${avail}</div></div>
        <div class="stat-card red"><div class="stat-label">Occupied</div><div class="stat-value">${occup}</div></div>
        <div class="stat-card amber"><div class="stat-label">Reserved</div><div class="stat-value">${resvd}</div></div>
      </div>
      ${avail === 0 ? `<div class="card" style="border-color:var(--red);margin-bottom:20px"><div class="card-body" style="padding:14px;color:var(--red);font-weight:600">⚠️ Parking Full! No slots currently available.</div></div>` : ''}
      <div class="card fade-in">
        <div class="card-header">
          <span class="card-title">🔲 4-Wheeler (A-Block)</span>
          <div class="flex gap-2" style="font-size:12px">
            <span class="badge badge-green">Available</span>
            <span class="badge badge-red">Occupied</span>
            <span class="badge badge-yellow">Reserved</span>
          </div>
        </div>
        <div class="card-body"><div class="slots-grid">${renderGrid('4W')}</div></div>
      </div>
      <div class="card fade-in">
        <div class="card-header"><span class="card-title">🔲 2-Wheeler (B-Block)</span></div>
        <div class="card-body"><div class="slots-grid">${renderGrid('2W')}</div></div>
      </div>
      <p class="text-muted text-center" style="margin-top:10px">Auto-refreshes every 30 seconds</p>`,
    scripts: `<script>setTimeout(()=>location.reload(),30000);</script>`,
  });
}

function renderStaffHistory({ user, history }) {
  const rows = history.length === 0
    ? '<tr><td colspan="10" class="empty-state">No parking history found.</td></tr>'
    : history.map((v, idx) => {
        let mins = Number(v.duration_minutes) || 0;
        if (!mins && v.entry_time && v.exit_time) {
          mins = Math.round((new Date(v.exit_time).getTime() - new Date(v.entry_time).getTime()) / 60000);
        } else if (!mins && v.entry_time && v.status === 'parked') {
          mins = Math.round((Date.now() - new Date(v.entry_time).getTime()) / 60000);
        }
        const payBadge = v.payment_status
          ? `<span class="badge ${v.payment_status === 'paid' ? 'badge-green' : 'badge-yellow'}">${esc(v.payment_status)}</span>`
          : '<span class="text-muted">—</span>';
        return `<tr>
          <td>${idx + 1}</td>
          <td><strong>${esc(v.vehicle_number)}</strong></td>
          <td><span class="badge badge-blue">${esc(v.vehicle_type)}</span></td>
          <td>${esc(v.owner_name || v.user_name || '—')}</td>
          <td><strong class="text-amber">${esc(v.slot_number || '—')}</strong></td>
          <td>${esc(formatDateTimeShort(v.entry_time))}</td>
          <td>${v.exit_time ? esc(formatDateTimeShort(v.exit_time)) : '<span class="badge badge-green">Still Parked</span>'}</td>
          <td>${mins > 0 ? esc(formatDuration(mins)) : '—'}</td>
          <td>${v.amount !== null && v.amount !== undefined ? `<strong class="text-amber">₹${money(v.amount)}</strong>` : '<span class="text-muted">—</span>'}</td>
          <td>${payBadge}</td>
        </tr>`;
      }).join('');

  return layoutShell({
    title: 'Parking History',
    sidebar: renderStaffSidebar(user),
    topbarTitle: 'Parking History',
    bodyContent: `
      <div class="card fade-in">
        <div class="card-header">
          <span class="card-title">🕐 All Parking History</span>
          <div class="search-bar" style="margin:0;width:240px">
            <input type="text" id="searchInput" placeholder="Search vehicle or owner...">
          </div>
        </div>
        <div class="table-wrap">
          <table id="staffHistTable">
            <thead><tr><th>#</th><th>Vehicle No.</th><th>Type</th><th>Owner</th><th>Slot</th><th>Entry</th><th>Exit</th><th>Duration</th><th>Amount</th><th>Payment</th></tr></thead>
            <tbody>${rows}</tbody>
          </table>
        </div>
      </div>`,
    scripts: `<script>filterTable('searchInput', 'staffHistTable');</script>`,
  });
}

module.exports = {
  renderLoginPage,
  renderRegisterPage,
  renderStaffDashboard,
  renderStaffVehicleEntry,
  renderStaffVehicleExit,
  renderStaffParked,
  renderStaffHistory,
  renderStaffPayments,
  renderStaffBookings,
  renderStaffSlots,
  renderUserDashboard,
  renderUserHistory,
  renderUserBilling,
  renderUserBooking,
  renderUserMyBookings,
  renderUserSlots,
};
