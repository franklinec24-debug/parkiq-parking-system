const { Pool } = require('pg');

const RATE_PER_HOUR_4W = 20;
const RATE_PER_HOUR_2W = 10;
const MIN_CHARGE = 20;

let pool;

function getPool() {
  if (!pool) {
    const connectionString = process.env.DATABASE_URL || process.env.POSTGRES_URL;
    if (!connectionString) {
      throw new Error('DATABASE_URL environment variable is not configured');
    }
    pool = new Pool({
      connectionString,
      max: 5,
      idleTimeoutMillis: 30000,
    });
  }
  return pool;
}

async function query(text, params = []) {
  return getPool().query(text, params);
}

function nowISTDate() {
  return new Date().toLocaleDateString('en-CA', { timeZone: 'Asia/Kolkata' });
}

function nowISTTime() {
  return new Date().toLocaleTimeString('en-GB', { timeZone: 'Asia/Kolkata', hour12: false });
}

function formatDuration(minutes) {
  const mTotal = Math.max(0, Math.round(Number(minutes) || 0));
  const h = Math.floor(mTotal / 60);
  const m = mTotal % 60;
  return h > 0 ? `${h}h ${m}m` : `${m}m`;
}

function calculateFare(entryTime, vehicleType, exitTime = new Date()) {
  const entryMs = new Date(entryTime).getTime();
  const exitMs = new Date(exitTime).getTime();
  const durationMinutes = Math.max(1, Math.round((exitMs - entryMs) / 60000));
  const hoursBilled = Math.ceil(durationMinutes / 60);
  const rate = vehicleType === '4W' ? RATE_PER_HOUR_4W : RATE_PER_HOUR_2W;
  const amount = Math.max(MIN_CHARGE, hoursBilled * rate);
  return { durationMinutes, amount };
}

function formatDateTimeShort(dt) {
  if (!dt) return '—';
  const d = new Date(dt);
  const datePart = d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', timeZone: 'Asia/Kolkata' });
  const timePart = d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true, timeZone: 'Asia/Kolkata' });
  return `${datePart}, ${timePart}`;
}

function formatDateTimeFull(dt) {
  if (!dt) return '—';
  const d = new Date(dt);
  const datePart = d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', timeZone: 'Asia/Kolkata' });
  const timePart = d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true, timeZone: 'Asia/Kolkata' });
  return `${datePart}, ${timePart}`;
}

function formatDateOnly(dateStr) {
  if (!dateStr) return '—';
  const d = new Date(`${String(dateStr).slice(0, 10)}T00:00:00+05:30`);
  return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', timeZone: 'Asia/Kolkata' });
}

function formatDayMonth(dt) {
  if (!dt) return '—';
  return new Date(dt).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', timeZone: 'Asia/Kolkata' });
}

function formatTime12(timeStr) {
  if (!timeStr) return '—';
  const [hh, mm] = String(timeStr).split(':').map(Number);
  const period = hh >= 12 ? 'PM' : 'AM';
  const h12 = ((hh + 11) % 12) + 1;
  return `${String(h12).padStart(2, '0')}:${String(mm || 0).padStart(2, '0')} ${period}`;
}

module.exports = {
  RATE_PER_HOUR_4W,
  RATE_PER_HOUR_2W,
  MIN_CHARGE,
  getPool,
  query,
  nowISTDate,
  nowISTTime,
  formatDuration,
  calculateFare,
  formatDateTimeShort,
  formatDateTimeFull,
  formatDateOnly,
  formatDayMonth,
  formatTime12,
};
