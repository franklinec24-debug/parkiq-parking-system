const path = require('node:path');
const express = require('express');
const bcrypt = require('bcryptjs');
const {
  getPool,
  query,
  nowISTDate,
  nowISTTime,
  formatDuration,
  calculateFare,
} = require('../lib/db');
const {
  getSession,
  setSession,
  clearSession,
  setFlash,
  consumeFlash,
  requireStaffPage,
  requireUserPage,
  requireAuthJson,
  requireStaffJson,
} = require('../lib/auth');
const views = require('../lib/views');

const app = express();

app.use(express.static(path.join(__dirname, '..', 'public')));
app.use(express.json());
app.use(express.urlencoded({ extended: false }));

const asyncHandler = (fn) => (req, res, next) => Promise.resolve(fn(req, res, next)).catch(next);

// ---- LANDING / AUTH ----
app.get(['/', '/index.php'], (req, res) => {
  const session = getSession(req);
  if (session) {
    return res.redirect(session.role === 'staff' ? '/staff/dashboard.php' : '/user/dashboard.php');
  }
  const flash = consumeFlash(req, res);
  const error = flash && flash.type === 'error' ? flash.message : '';
  const success = flash && flash.type === 'success' ? flash.message : '';
  const activeTab = req.query.tab === 'admin-login' ? 'admin-login' : 'user-login';
  res.send(views.renderLoginPage({ error, success, activeTab }));
});

app.get(['/register', '/register.php'], (req, res) => {
  const session = getSession(req);
  if (session) {
    return res.redirect(session.role === 'staff' ? '/staff/dashboard.php' : '/user/dashboard.php');
  }
  const flash = consumeFlash(req, res);
  const error = flash && flash.type === 'error' ? flash.message : '';
  const success = flash && flash.type === 'success' ? flash.message : '';
  res.send(views.renderRegisterPage({ error, success }));
});

app.all('/php/auth_handler.php', asyncHandler(async (req, res) => {
  const action = req.body.action || req.query.action || '';

  if (action === 'login' && req.method === 'POST') {
    const email = String(req.body.email || '').trim().toLowerCase();
    const password = String(req.body.password || '');
    const loginRole = ['user', 'staff'].includes(req.body.login_role) ? req.body.login_role : '';
    const tabParam = loginRole === 'staff' ? '?tab=admin-login' : '';
    if (!email || !password) {
      setFlash(req, res, 'error', 'Please fill in all fields.');
      return res.redirect(`/index.php${tabParam}`);
    }
    const { rows } = await query(
      'SELECT id, name, email, password, phone, role FROM users WHERE LOWER(email) = $1 LIMIT 1',
      [email]
    );
    const user = rows[0];
    if (user && (await bcrypt.compare(password, user.password))) {
      if (loginRole && user.role !== loginRole) {
        const msg = loginRole === 'staff'
          ? 'This is a Normal User account. Please use User Login.'
          : 'This is an Admin account. Please use Admin Login.';
        setFlash(req, res, 'error', msg);
        return res.redirect(`/index.php${tabParam}`);
      }
      setSession(req, res, user);
      return res.redirect(user.role === 'staff' ? '/staff/dashboard.php' : '/user/dashboard.php');
    }
    setFlash(req, res, 'error', 'Invalid email or password.');
    return res.redirect(`/index.php${tabParam}`);
  }

  if (action === 'register' && req.method === 'POST') {
    const name = String(req.body.name || '').trim();
    const email = String(req.body.email || '').trim().toLowerCase();
    const phone = String(req.body.phone || '').trim();
    const password = String(req.body.password || '');
    const role = ['user', 'staff'].includes(req.body.role) ? req.body.role : 'user';

    if (!name || !email || !password) {
      setFlash(req, res, 'error', 'Please fill in all required fields.');
      return res.redirect('/register.php');
    }
    if (password.length < 6) {
      setFlash(req, res, 'error', 'Password must be at least 6 characters.');
      return res.redirect('/register.php');
    }
    const dup = await query('SELECT id FROM users WHERE LOWER(email) = $1 LIMIT 1', [email]);
    if (dup.rows.length > 0) {
      setFlash(req, res, 'error', 'Email already registered. Please login.');
      return res.redirect('/register.php');
    }
    const hash = await bcrypt.hash(password, 10);
    await query(
      'INSERT INTO users (name, email, password, phone, role) VALUES ($1, $2, $3, $4, $5)',
      [name, email, hash, phone, role]
    );
    setFlash(req, res, 'success', 'Account created! You can now sign in.');
    return res.redirect('/register.php');
  }

  if (action === 'logout') {
    clearSession(req, res);
    return res.redirect('/index.php');
  }

  return res.redirect('/index.php');
}));

// ---- STAFF PAGES ----
app.get('/staff/dashboard.php', requireStaffPage, asyncHandler(async (req, res) => {
  const [slotCounts, pendingCount, revRow, parkedRes, pendingRes] = await Promise.all([
    query(`
      SELECT
        COUNT(*)::int AS total,
        COUNT(*) FILTER (WHERE status = 'available')::int AS available,
        COUNT(*) FILTER (WHERE status = 'occupied')::int AS occupied,
        COUNT(*) FILTER (WHERE status = 'reserved')::int AS reserved
      FROM parking_slots
    `),
    query(`SELECT COUNT(*)::int AS cnt FROM billing WHERE payment_status = 'pending'`),
    query(`
      SELECT COALESCE(SUM(amount), 0)::float AS total
      FROM billing
      WHERE payment_status = 'paid'
        AND (payment_time AT TIME ZONE 'Asia/Kolkata')::date = (NOW() AT TIME ZONE 'Asia/Kolkata')::date
    `),
    query(`
      SELECT v.id, v.vehicle_number, v.vehicle_type, v.owner_name, v.entry_time,
             ps.slot_number, u.name AS user_name
      FROM vehicles v
      LEFT JOIN parking_slots ps ON ps.id = v.slot_id
      LEFT JOIN users u ON u.id = v.user_id
      WHERE v.status = 'parked'
      ORDER BY v.entry_time DESC LIMIT 10
    `),
    query(`
      SELECT b.id, b.vehicle_number, b.slot_number, b.amount, b.entry_time, b.exit_time, b.duration_minutes
      FROM billing b
      WHERE b.payment_status = 'pending'
      ORDER BY b.created_at DESC LIMIT 10
    `),
  ]);

  const sc = slotCounts.rows[0];
  res.send(views.renderStaffDashboard({
    user: req.user,
    stats: {
      ...sc,
      pending: pendingCount.rows[0].cnt,
      today_revenue: revRow.rows[0].total,
    },
    parked: parkedRes.rows,
    pendingBills: pendingRes.rows,
  }));
}));

app.get('/staff/vehicle_entry.php', requireStaffPage, asyncHandler(async (req, res) => {
  const { rows } = await query(`
    SELECT
      COUNT(*) FILTER (WHERE slot_type = '4W')::int AS total_4w,
      COUNT(*) FILTER (WHERE slot_type = '4W' AND status = 'available')::int AS avail_4w,
      COUNT(*) FILTER (WHERE slot_type = '2W')::int AS total_2w,
      COUNT(*) FILTER (WHERE slot_type = '2W' AND status = 'available')::int AS avail_2w
    FROM parking_slots
  `);
  const c = rows[0];
  res.send(views.renderStaffVehicleEntry({
    user: req.user,
    avail4w: c.avail_4w,
    total4w: c.total_4w,
    avail2w: c.avail_2w,
    total2w: c.total_2w,
  }));
}));

app.get('/staff/vehicle_exit.php', requireStaffPage, asyncHandler(async (req, res) => {
  const preselect = Number.parseInt(req.query.vehicle_id || '0', 10) || 0;
  const { rows } = await query(`
    SELECT v.id, v.vehicle_number, v.vehicle_type, v.owner_name, v.entry_time, ps.slot_number
    FROM vehicles v
    LEFT JOIN parking_slots ps ON ps.id = v.slot_id
    WHERE v.status = 'parked'
    ORDER BY v.entry_time ASC
  `);
  res.send(views.renderStaffVehicleExit({
    user: req.user,
    parkedList: rows,
    preselect,
  }));
}));

app.get('/staff/parked.php', requireStaffPage, asyncHandler(async (req, res) => {
  const { rows } = await query(`
    SELECT v.id, v.vehicle_number, v.vehicle_type, v.owner_name, v.entry_time,
           ps.slot_number, u.name AS user_name, u.phone
    FROM vehicles v
    LEFT JOIN parking_slots ps ON ps.id = v.slot_id
    LEFT JOIN users u ON u.id = v.user_id
    WHERE v.status = 'parked'
    ORDER BY v.entry_time ASC
  `);
  res.send(views.renderStaffParked({ user: req.user, parked: rows }));
}));

app.get('/staff/history.php', requireStaffPage, asyncHandler(async (req, res) => {
  const { rows } = await query(`
    SELECT v.id, v.vehicle_number, v.vehicle_type, v.owner_name, v.entry_time, v.exit_time, v.status,
           COALESCE(ps.slot_number, b.slot_number) AS slot_number,
           b.duration_minutes, b.amount, b.payment_status,
           u.name AS user_name
    FROM vehicles v
    LEFT JOIN parking_slots ps ON ps.id = v.slot_id
    LEFT JOIN billing b ON b.vehicle_id = v.id
    LEFT JOIN users u ON u.id = v.user_id
    ORDER BY COALESCE(v.exit_time, v.entry_time, v.created_at) DESC
  `);
  res.send(views.renderStaffHistory({ user: req.user, history: rows }));
}));

app.get('/staff/payments.php', requireStaffPage, asyncHandler(async (req, res) => {
  const filter = ['pending', 'paid'].includes(req.query.filter) ? req.query.filter : 'all';
  const where = filter === 'all' ? '' : 'WHERE b.payment_status = $1';
  const params = filter === 'all' ? [] : [filter];

  const [billsRes, totalsRes] = await Promise.all([
    query(`
      SELECT b.*, u.name AS user_name
      FROM billing b
      LEFT JOIN users u ON u.id = b.user_id
      ${where}
      ORDER BY b.created_at DESC
    `, params),
    query(`
      SELECT
        COALESCE(SUM(amount) FILTER (WHERE payment_status = 'paid'), 0)::float AS total_paid,
        COALESCE(SUM(amount) FILTER (WHERE payment_status = 'pending'), 0)::float AS total_pending,
        COALESCE(SUM(amount) FILTER (
          WHERE payment_status = 'paid'
            AND (payment_time AT TIME ZONE 'Asia/Kolkata')::date = (NOW() AT TIME ZONE 'Asia/Kolkata')::date
        ), 0)::float AS today_revenue
      FROM billing
    `),
  ]);

  const t = totalsRes.rows[0];
  res.send(views.renderStaffPayments({
    user: req.user,
    bills: billsRes.rows,
    totalPaid: t.total_paid,
    totalPending: t.total_pending,
    todayRevenue: t.today_revenue,
    filter,
  }));
}));

app.get('/staff/bookings.php', requireStaffPage, asyncHandler(async (req, res) => {
  const { rows } = await query(`
    SELECT b.id, b.user_id, b.slot_id, b.vehicle_number, b.booking_date::text AS booking_date,
           b.start_time::text AS start_time, b.end_time::text AS end_time, b.status,
           u.name AS user_name, u.email, u.phone, ps.slot_number
    FROM bookings b
    LEFT JOIN users u ON u.id = b.user_id
    LEFT JOIN parking_slots ps ON ps.id = b.slot_id
    ORDER BY b.booking_date DESC, b.start_time DESC
  `);
  res.send(views.renderStaffBookings({ user: req.user, bookings: rows }));
}));

app.get('/staff/slots.php', requireStaffPage, asyncHandler(async (req, res) => {
  const { rows } = await query(`
    SELECT ps.*, v.vehicle_number, v.entry_time
    FROM parking_slots ps
    LEFT JOIN vehicles v ON v.slot_id = ps.id AND v.status = 'parked'
    ORDER BY ps.slot_type, ps.slot_number
  `);
  res.send(views.renderStaffSlots({ user: req.user, slots: rows }));
}));

// ---- USER PAGES ----
app.get('/user/dashboard.php', requireUserPage, asyncHandler(async (req, res) => {
  const uid = req.user.user_id;
  const [slotCounts, activeRes, pendingRes, bookingsRes, historyRes] = await Promise.all([
    query(`
      SELECT
        COUNT(*)::int AS total,
        COUNT(*) FILTER (WHERE status = 'available')::int AS avail,
        COUNT(*) FILTER (WHERE status = 'occupied')::int AS occup
      FROM parking_slots
    `),
    query(`
      SELECT v.*, ps.slot_number
      FROM vehicles v
      LEFT JOIN parking_slots ps ON ps.id = v.slot_id
      WHERE v.user_id = $1 AND v.status = 'parked'
      LIMIT 1
    `, [uid]),
    query(`SELECT COALESCE(SUM(amount), 0)::float AS t FROM billing WHERE user_id = $1 AND payment_status = 'pending'`, [uid]),
    query(`
      SELECT b.id, b.vehicle_number, b.booking_date::text AS booking_date,
             b.start_time::text AS start_time, b.end_time::text AS end_time, b.status, ps.slot_number
      FROM bookings b
      LEFT JOIN parking_slots ps ON ps.id = b.slot_id
      WHERE b.user_id = $1 AND b.status = 'active'
      ORDER BY b.booking_date ASC LIMIT 5
    `, [uid]),
    query(`
      SELECT v.*, COALESCE(ps.slot_number, b.slot_number) AS slot_number
      FROM vehicles v
      LEFT JOIN parking_slots ps ON ps.id = v.slot_id
      LEFT JOIN billing b ON b.vehicle_id = v.id
      WHERE v.user_id = $1
      ORDER BY COALESCE(v.entry_time, v.created_at) DESC LIMIT 5
    `, [uid]),
  ]);

  const sc = slotCounts.rows[0];
  res.send(views.renderUserDashboard({
    user: req.user,
    total: sc.total,
    avail: sc.avail,
    occup: sc.occup,
    active: activeRes.rows[0] || null,
    pendingAmt: pendingRes.rows[0].t,
    bookings: bookingsRes.rows,
    history: historyRes.rows,
  }));
}));

app.get('/user/history.php', requireUserPage, asyncHandler(async (req, res) => {
  const { rows } = await query(`
    SELECT v.*, COALESCE(ps.slot_number, b.slot_number) AS slot_number
    FROM vehicles v
    LEFT JOIN parking_slots ps ON ps.id = v.slot_id
    LEFT JOIN billing b ON b.vehicle_id = v.id
    WHERE v.user_id = $1
    ORDER BY COALESCE(v.entry_time, v.created_at) DESC
  `, [req.user.user_id]);
  res.send(views.renderUserHistory({ user: req.user, history: rows }));
}));

app.get('/user/billing.php', requireUserPage, asyncHandler(async (req, res) => {
  const uid = req.user.user_id;
  const [billsRes, totalsRes] = await Promise.all([
    query('SELECT * FROM billing WHERE user_id = $1 ORDER BY created_at DESC', [uid]),
    query(`
      SELECT
        COALESCE(SUM(amount) FILTER (WHERE payment_status = 'paid'), 0)::float AS paid_total,
        COALESCE(SUM(amount) FILTER (WHERE payment_status = 'pending'), 0)::float AS pending_total
      FROM billing WHERE user_id = $1
    `, [uid]),
  ]);
  const t = totalsRes.rows[0];
  res.send(views.renderUserBilling({
    user: req.user,
    bills: billsRes.rows,
    paidTotal: t.paid_total,
    pendingTotal: t.pending_total,
  }));
}));

app.get('/user/booking.php', requireUserPage, (req, res) => {
  res.send(views.renderUserBooking({ user: req.user }));
});

app.get('/user/my_bookings.php', requireUserPage, asyncHandler(async (req, res) => {
  const { rows } = await query(`
    SELECT b.id, b.vehicle_number, b.booking_date::text AS booking_date,
           b.start_time::text AS start_time, b.end_time::text AS end_time,
           b.status, b.created_at, ps.slot_number
    FROM bookings b
    LEFT JOIN parking_slots ps ON ps.id = b.slot_id
    WHERE b.user_id = $1
    ORDER BY b.booking_date DESC, b.start_time DESC
  `, [req.user.user_id]);
  res.send(views.renderUserMyBookings({ user: req.user, bookings: rows }));
}));

app.get('/user/slots.php', requireUserPage, asyncHandler(async (req, res) => {
  const { rows } = await query(`
    SELECT ps.*, v.vehicle_number
    FROM parking_slots ps
    LEFT JOIN vehicles v ON v.slot_id = ps.id AND v.status = 'parked'
    ORDER BY ps.slot_type, ps.slot_number
  `);
  res.send(views.renderUserSlots({ user: req.user, slots: rows }));
}));

// ---- JSON API HANDLERS ----
app.post('/php/vehicle_handler.php', requireStaffJson, asyncHandler(async (req, res) => {
  const action = req.body.action || '';

  if (action === 'entry') {
    const vehicleNumber = String(req.body.vehicle_number || '').trim().toUpperCase();
    const vehicleType = ['4W', '2W'].includes(req.body.vehicle_type) ? req.body.vehicle_type : '4W';
    const ownerName = String(req.body.owner_name || '').trim();

    if (!vehicleNumber) {
      return res.json({ success: false, message: 'Vehicle number is required.' });
    }

    const client = await getPool().connect();
    try {
      await client.query('BEGIN');
      const dup = await client.query(
        "SELECT id FROM vehicles WHERE vehicle_number = $1 AND status = 'parked' LIMIT 1",
        [vehicleNumber]
      );
      if (dup.rows.length > 0) {
        await client.query('ROLLBACK');
        return res.json({ success: false, message: 'This vehicle is already parked in the system.' });
      }

      const slotRes = await client.query(
        "SELECT id, slot_number FROM parking_slots WHERE status = 'available' AND slot_type = $1 ORDER BY slot_number LIMIT 1 FOR UPDATE SKIP LOCKED",
        [vehicleType]
      );
      if (slotRes.rows.length === 0) {
        await client.query('ROLLBACK');
        return res.json({ success: false, message: `No available slots for ${vehicleType}. Parking may be full!` });
      }
      const slot = slotRes.rows[0];

      // Link vehicle to matching customer account from recent booking or prior entry if available
      const ownerMatch = await client.query(
        `SELECT user_id FROM (
           SELECT user_id, created_at FROM bookings WHERE UPPER(vehicle_number) = $1
           UNION ALL
           SELECT v.user_id, v.created_at FROM vehicles v
           JOIN users u ON u.id = v.user_id
           WHERE UPPER(v.vehicle_number) = $1 AND u.role = 'user'
         ) m ORDER BY created_at DESC LIMIT 1`,
        [vehicleNumber]
      );
      const linkedUserId = ownerMatch.rows[0]?.user_id || req.user.user_id;

      await client.query(
        "INSERT INTO vehicles (user_id, vehicle_number, vehicle_type, owner_name, slot_id, entry_time, status) VALUES ($1, $2, $3, $4, $5, NOW(), 'parked')",
        [linkedUserId, vehicleNumber, vehicleType, ownerName, slot.id]
      );
      await client.query("UPDATE parking_slots SET status = 'occupied', updated_at = NOW() WHERE id = $1", [slot.id]);
      await client.query('COMMIT');
      return res.json({ success: true, slot: slot.slot_number, message: 'Entry registered successfully.' });
    } catch (err) {
      await client.query('ROLLBACK');
      throw err;
    } finally {
      client.release();
    }
  }

  if (action === 'exit') {
    const vehicleId = Number.parseInt(req.body.vehicle_id || '0', 10);
    const paymentStatus = ['paid', 'pending'].includes(req.body.payment_status) ? req.body.payment_status : 'pending';
    if (!vehicleId) {
      return res.json({ success: false, message: 'Invalid vehicle selection.' });
    }

    const client = await getPool().connect();
    try {
      await client.query('BEGIN');
      const vRes = await client.query(
        `SELECT v.*, ps.slot_number, ps.id AS slot_db_id
         FROM vehicles v
         LEFT JOIN parking_slots ps ON ps.id = v.slot_id
         WHERE v.id = $1 AND v.status = 'parked'
         FOR UPDATE OF v`,
        [vehicleId]
      );
      const vehicle = vRes.rows[0];
      if (!vehicle) {
        await client.query('ROLLBACK');
        return res.json({ success: false, message: 'Vehicle not found or already exited.' });
      }

      const exitTime = new Date();
      const { durationMinutes, amount } = calculateFare(vehicle.entry_time, vehicle.vehicle_type, exitTime);
      const paymentTime = paymentStatus === 'paid' ? exitTime : null;

      await client.query(
        "UPDATE vehicles SET status = 'exited', exit_time = $1, slot_id = NULL, updated_at = NOW() WHERE id = $2",
        [exitTime, vehicleId]
      );
      if (vehicle.slot_db_id) {
        await client.query("UPDATE parking_slots SET status = 'available', updated_at = NOW() WHERE id = $1", [vehicle.slot_db_id]);
      }
      await client.query(
        `INSERT INTO billing (vehicle_id, user_id, vehicle_number, slot_number, entry_time, exit_time, duration_minutes, amount, payment_status, payment_time)
         VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10)`,
        [
          vehicleId,
          vehicle.user_id,
          vehicle.vehicle_number,
          vehicle.slot_number,
          vehicle.entry_time,
          exitTime,
          durationMinutes,
          amount,
          paymentStatus,
          paymentTime,
        ]
      );
      await client.query('COMMIT');

      return res.json({
        success: true,
        vehicle_number: vehicle.vehicle_number,
        slot: vehicle.slot_number,
        entry_time: new Date(vehicle.entry_time).toISOString(),
        exit_time: exitTime.toISOString(),
        duration: formatDuration(durationMinutes),
        amount: amount.toFixed(2),
        payment_status: paymentStatus,
        message: 'Exit processed successfully.',
      });
    } catch (err) {
      await client.query('ROLLBACK');
      throw err;
    } finally {
      client.release();
    }
  }

  return res.json({ success: false, message: 'Invalid action.' });
}));

app.get('/php/vehicle_api.php', requireAuthJson, asyncHandler(async (req, res) => {
  const action = req.query.action || '';

  if (action === 'recent_entries') {
    if (req.user.role !== 'staff') return res.status(403).json({ success: false, message: 'Forbidden.' });
    const { rows } = await query(`
      SELECT v.vehicle_number, v.vehicle_type, v.owner_name, v.entry_time, ps.slot_number
      FROM vehicles v
      LEFT JOIN parking_slots ps ON ps.id = v.slot_id
      WHERE v.status = 'parked'
        AND (v.entry_time AT TIME ZONE 'Asia/Kolkata')::date = (NOW() AT TIME ZONE 'Asia/Kolkata')::date
      ORDER BY v.entry_time DESC LIMIT 15
    `);
    return res.json({ success: true, vehicles: rows });
  }

  if (action === 'exit_history') {
    if (req.user.role !== 'staff') return res.status(403).json({ success: false, message: 'Forbidden.' });
    const { rows } = await query(`
      SELECT b.vehicle_number, b.slot_number, b.duration_minutes, b.amount, b.payment_status, b.exit_time
      FROM billing b
      ORDER BY b.exit_time DESC LIMIT 20
    `);
    return res.json({ success: true, vehicles: rows });
  }

  if (action === 'search') {
    const vehicleNumber = String(req.query.vehicle_number || '').trim().toUpperCase();
    if (!vehicleNumber) {
      return res.json({ success: false, message: 'Vehicle number required.', vehicles: [] });
    }
    const params = [`%${vehicleNumber}%`];
    let ownerFilter = '';
    if (req.user.role === 'user') {
      params.push(req.user.user_id);
      ownerFilter = 'AND v.user_id = $2';
    }
    const { rows } = await query(`
      SELECT v.vehicle_number, v.vehicle_type, v.entry_time, v.exit_time, v.status,
             COALESCE(ps.slot_number, b.slot_number) AS slot_number
      FROM vehicles v
      LEFT JOIN parking_slots ps ON ps.id = v.slot_id
      LEFT JOIN billing b ON b.vehicle_id = v.id
      WHERE v.vehicle_number ILIKE $1 ${ownerFilter}
      ORDER BY COALESCE(v.entry_time, v.created_at) DESC LIMIT 20
    `, params);
    return res.json({ success: true, vehicles: rows });
  }

  return res.json({ success: false, message: 'Invalid action.' });
}));

app.get('/php/slots_api.php', requireAuthJson, asyncHandler(async (req, res) => {
  if (!req.query.date) {
    const { rows } = await query('SELECT id, slot_number, slot_type, status FROM parking_slots ORDER BY slot_type, slot_number');
    return res.json({ success: true, slots: rows });
  }

  const date = String(req.query.date || '');
  const start = String(req.query.start || '');
  const end = String(req.query.end || '');
  const type = ['4W', '2W'].includes(req.query.type) ? req.query.type : '4W';

  if (!date || !start || !end) {
    return res.json({ success: false, message: 'Date and time required.', slots: [] });
  }
  if (start >= end) {
    return res.json({ success: false, message: 'End time must be after start time.', slots: [] });
  }

  const { rows } = await query(`
    SELECT ps.id, ps.slot_number, ps.slot_type, ps.status,
           (ps.status <> 'occupied' AND NOT EXISTS (
             SELECT 1 FROM bookings b
             WHERE b.slot_id = ps.id
               AND b.booking_date = $1::date
               AND b.status = 'active'
               AND b.start_time < $2::time
               AND b.end_time > $3::time
           )) AS booking_available
    FROM parking_slots ps
    WHERE ps.slot_type = $4
    ORDER BY ps.slot_number
  `, [date, end, start, type]);

  return res.json({ success: true, slots: rows });
}));

app.post('/php/booking_handler.php', requireAuthJson, asyncHandler(async (req, res) => {
  const action = req.body.action || '';

  if (action === 'create') {
    const userId = req.user.user_id;
    const vehicleNumber = String(req.body.vehicle_number || '').trim().toUpperCase();
    const slotId = Number.parseInt(req.body.slot_id || '0', 10);
    const bookingDate = String(req.body.booking_date || '');
    const startTime = String(req.body.start_time || '');
    const endTime = String(req.body.end_time || '');

    if (!userId || !vehicleNumber || !slotId || !bookingDate || !startTime || !endTime) {
      return res.json({ success: false, message: 'All fields are required.' });
    }
    if (startTime >= endTime) {
      return res.json({ success: false, message: 'End time must be after start time.' });
    }
    const todayIST = nowISTDate();
    if (bookingDate < todayIST) {
      return res.json({ success: false, message: 'Cannot book for a past date.' });
    }

    const client = await getPool().connect();
    try {
      await client.query('BEGIN');
      const slotRes = await client.query('SELECT slot_number, status FROM parking_slots WHERE id = $1 FOR UPDATE', [slotId]);
      const slotRow = slotRes.rows[0];
      if (!slotRow) {
        await client.query('ROLLBACK');
        return res.json({ success: false, message: 'Invalid slot selected.' });
      }
      if (slotRow.status === 'occupied') {
        await client.query('ROLLBACK');
        return res.json({ success: false, message: 'This slot is currently occupied.' });
      }

      const conflict = await client.query(`
        SELECT id FROM bookings
        WHERE slot_id = $1 AND booking_date = $2::date AND status = 'active'
          AND start_time < $3::time AND end_time > $4::time
        LIMIT 1
      `, [slotId, bookingDate, endTime, startTime]);
      if (conflict.rows.length > 0) {
        await client.query('ROLLBACK');
        return res.json({ success: false, message: 'This slot is already booked for the selected time.' });
      }

      const ins = await client.query(
        "INSERT INTO bookings (user_id, slot_id, vehicle_number, booking_date, start_time, end_time, status) VALUES ($1, $2, $3, $4::date, $5::time, $6::time, 'active') RETURNING id",
        [userId, slotId, vehicleNumber, bookingDate, startTime, endTime]
      );
      const nowTime = nowISTTime();
      if (bookingDate === todayIST && startTime <= nowTime && endTime >= nowTime) {
        await client.query("UPDATE parking_slots SET status = 'reserved', updated_at = NOW() WHERE id = $1 AND status = 'available'", [slotId]);
      }
      await client.query('COMMIT');
      return res.json({
        success: true,
        booking_id: ins.rows[0].id,
        slot: slotRow.slot_number,
        message: 'Booking confirmed!',
      });
    } catch (err) {
      await client.query('ROLLBACK');
      throw err;
    } finally {
      client.release();
    }
  }

  if (action === 'cancel') {
    const bookingId = Number.parseInt(req.body.booking_id || '0', 10);
    if (!bookingId) {
      return res.json({ success: false, message: 'Invalid booking.' });
    }

    const client = await getPool().connect();
    try {
      await client.query('BEGIN');
      const params = req.user.role === 'staff' ? [bookingId] : [bookingId, req.user.user_id];
      const ownerSql = req.user.role === 'staff' ? '' : 'AND user_id = $2';
      const bRes = await client.query(
        `SELECT * FROM bookings WHERE id = $1 ${ownerSql} AND status = 'active' FOR UPDATE`,
        params
      );
      const booking = bRes.rows[0];
      if (!booking) {
        await client.query('ROLLBACK');
        return res.json({ success: false, message: 'Booking not found or already cancelled.' });
      }

      await client.query("UPDATE bookings SET status = 'cancelled', updated_at = NOW() WHERE id = $1", [bookingId]);
      const other = await client.query(`
        SELECT id FROM bookings
        WHERE slot_id = $1 AND status = 'active'
          AND booking_date = (NOW() AT TIME ZONE 'Asia/Kolkata')::date
          AND end_time >= (NOW() AT TIME ZONE 'Asia/Kolkata')::time
        LIMIT 1
      `, [booking.slot_id]);
      if (other.rows.length === 0) {
        await client.query("UPDATE parking_slots SET status = 'available', updated_at = NOW() WHERE id = $1 AND status = 'reserved'", [booking.slot_id]);
      }
      await client.query('COMMIT');
      return res.json({ success: true, message: 'Booking cancelled successfully.' });
    } catch (err) {
      await client.query('ROLLBACK');
      throw err;
    } finally {
      client.release();
    }
  }

  return res.json({ success: false, message: 'Invalid action.' });
}));

app.post('/php/payment_handler.php', requireStaffJson, asyncHandler(async (req, res) => {
  const action = req.body.action || '';
  if (action !== 'mark_paid') {
    return res.json({ success: false, message: 'Invalid action.' });
  }
  const billId = Number.parseInt(req.body.bill_id || '0', 10);
  if (!billId) {
    return res.json({ success: false, message: 'Invalid bill ID.' });
  }

  const { rows } = await query(
    "UPDATE billing SET payment_status = 'paid', payment_time = NOW(), updated_at = NOW() WHERE id = $1 AND payment_status = 'pending' RETURNING amount",
    [billId]
  );
  if (rows.length === 0) {
    return res.json({ success: false, message: 'Bill not found or already paid.' });
  }
  const amt = Number(rows[0].amount).toFixed(2);
  return res.json({
    success: true,
    amount: amt,
    message: `Payment of ₹${amt} collected successfully.`,
  });
}));

// Error handler
app.use((err, req, res, _next) => {
  console.error(err);
  if (req.path.startsWith('/php/')) {
    return res.status(500).json({ success: false, message: 'Internal server error.' });
  }
  res.status(500).send('Internal server error.');
});

if (require.main === module) {
  const port = process.env.PORT || 3000;
  app.listen(port, () => console.log(`ParkIQ running at http://localhost:${port}`));
}

module.exports = app;
