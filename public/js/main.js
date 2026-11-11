// ============================================================
// Parking Management System - Main JS
// ============================================================

// ---- SIDEBAR TOGGLE ----
function toggleSidebar() {
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sidebarOverlay');
  sidebar.classList.toggle('open');
  overlay.classList.toggle('show');
}

// ---- NOTIFICATIONS ----
let notifTimer = null;

function showNotification(title, message, type = 'info') {
  const existing = document.getElementById('notification');
  if (existing) existing.remove();

  const icons = { success: '✅', error: '❌', warning: '⚠️', info: 'ℹ️' };

  const el = document.createElement('div');
  el.id = 'notification';
  el.className = `notification ${type}`;
  el.innerHTML = `
    <span class="notification-icon">${icons[type]}</span>
    <div>
      <div class="notification-title">${title}</div>
      <div class="notification-msg">${message}</div>
    </div>
  `;
  document.body.appendChild(el);

  setTimeout(() => el.classList.add('show'), 10);

  clearTimeout(notifTimer);
  notifTimer = setTimeout(() => {
    el.classList.remove('show');
    setTimeout(() => el.remove(), 350);
  }, 4000);
}

// ---- MODALS ----
function openModal(id) {
  document.getElementById(id).classList.add('open');
}

function closeModal(id) {
  document.getElementById(id).classList.remove('open');
}

// Close modal on overlay click
document.addEventListener('click', (e) => {
  if (e.target.classList.contains('modal-overlay')) {
    e.target.classList.remove('open');
  }
});

// ---- FETCH HELPER ----
async function apiCall(url, data = {}) {
  try {
    const res = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    });
    return await res.json();
  } catch (err) {
    return { success: false, message: 'Network error. Please try again.' };
  }
}

// ---- FORMAT HELPERS ----
function formatDuration(minutes) {
  if (!minutes) return '—';
  const h = Math.floor(minutes / 60);
  const m = minutes % 60;
  return h > 0 ? `${h}h ${m}m` : `${m}m`;
}

function formatCurrency(amount) {
  return '₹' + parseFloat(amount).toFixed(2);
}

function formatDateTime(dt) {
  if (!dt) return '—';
  return new Date(dt).toLocaleString('en-IN', {
    day: '2-digit', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit', hour12: true
  });
}

function formatDate(dt) {
  if (!dt) return '—';
  return new Date(dt).toLocaleDateString('en-IN', {
    day: '2-digit', month: 'short', year: 'numeric'
  });
}

// ---- STATUS BADGE ----
function badge(status) {
  const map = {
    'available':  ['green',  'Available'],
    'occupied':   ['red',    'Occupied'],
    'reserved':   ['yellow', 'Reserved'],
    'paid':       ['green',  'Paid'],
    'pending':    ['yellow', 'Pending'],
    'parked':     ['blue',   'Parked'],
    'exited':     ['muted',  'Exited'],
    'active':     ['blue',   'Active'],
    'completed':  ['green',  'Completed'],
    'cancelled':  ['red',    'Cancelled'],
  };
  const [cls, label] = map[status] || ['muted', status];
  return `<span class="badge badge-${cls}">${label}</span>`;
}

// ---- REAL-TIME SLOT REFRESH ----
function startSlotRefresh(callback, interval = 30000) {
  callback();
  setInterval(callback, interval);
}

// ---- TABLE FILTER ----
function filterTable(inputId, tableId) {
  const input = document.getElementById(inputId);
  if (!input) return;
  input.addEventListener('input', function () {
    const q = this.value.toLowerCase();
    const rows = document.querySelectorAll(`#${tableId} tbody tr`);
    rows.forEach(row => {
      row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
  });
}

// ---- AUTH TABS ----
function initAuthTabs() {
  document.querySelectorAll('.auth-tab').forEach(tab => {
    tab.addEventListener('click', function () {
      const target = this.dataset.tab;
      document.querySelectorAll('.auth-tab').forEach(t => t.classList.remove('active'));
      document.querySelectorAll('.auth-section').forEach(s => s.classList.remove('active'));
      this.classList.add('active');
      document.getElementById(target).classList.add('active');
    });
  });
}

// ---- ACTIVE NAV ----
function setActiveNav() {
  const path = window.location.pathname;
  document.querySelectorAll('.nav-item').forEach(item => {
    const href = item.getAttribute('href');
    if (href && path.includes(href.split('/').pop().replace('.php', ''))) {
      item.classList.add('active');
    }
  });
}

// Init
document.addEventListener('DOMContentLoaded', () => {
  initAuthTabs();
  setActiveNav();
});
