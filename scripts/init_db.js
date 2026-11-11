const { getPool } = require('../lib/db');

async function initDb() {
  const pool = getPool();

  await pool.query(`
    CREATE TABLE IF NOT EXISTS users (
      id SERIAL PRIMARY KEY,
      name VARCHAR(100) NOT NULL,
      email VARCHAR(150) NOT NULL UNIQUE,
      password VARCHAR(255) NOT NULL,
      phone VARCHAR(20),
      role VARCHAR(10) NOT NULL DEFAULT 'user' CHECK (role IN ('user','staff')),
      created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
      updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
    );

    CREATE TABLE IF NOT EXISTS parking_slots (
      id SERIAL PRIMARY KEY,
      slot_number VARCHAR(10) NOT NULL UNIQUE,
      slot_type VARCHAR(2) NOT NULL DEFAULT '4W' CHECK (slot_type IN ('2W','4W')),
      status VARCHAR(20) NOT NULL DEFAULT 'available' CHECK (status IN ('available','occupied','reserved')),
      created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
      updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
    );

    CREATE TABLE IF NOT EXISTS vehicles (
      id SERIAL PRIMARY KEY,
      user_id INT REFERENCES users(id) ON DELETE SET NULL,
      vehicle_number VARCHAR(20) NOT NULL,
      vehicle_type VARCHAR(2) NOT NULL DEFAULT '4W' CHECK (vehicle_type IN ('2W','4W')),
      owner_name VARCHAR(100),
      slot_id INT REFERENCES parking_slots(id) ON DELETE SET NULL,
      entry_time TIMESTAMPTZ,
      exit_time TIMESTAMPTZ,
      status VARCHAR(20) NOT NULL DEFAULT 'parked' CHECK (status IN ('parked','exited')),
      created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
      updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
    );

    CREATE TABLE IF NOT EXISTS bookings (
      id SERIAL PRIMARY KEY,
      user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
      slot_id INT NOT NULL REFERENCES parking_slots(id) ON DELETE CASCADE,
      vehicle_number VARCHAR(20) NOT NULL,
      booking_date DATE NOT NULL,
      start_time TIME NOT NULL,
      end_time TIME NOT NULL,
      status VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('active','completed','cancelled')),
      created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
      updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
    );

    CREATE TABLE IF NOT EXISTS billing (
      id SERIAL PRIMARY KEY,
      vehicle_id INT NOT NULL REFERENCES vehicles(id) ON DELETE CASCADE,
      user_id INT,
      vehicle_number VARCHAR(20),
      slot_number VARCHAR(10),
      entry_time TIMESTAMPTZ,
      exit_time TIMESTAMPTZ,
      duration_minutes INT DEFAULT 0,
      amount NUMERIC(10,2) DEFAULT 0.00,
      payment_status VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (payment_status IN ('paid','pending')),
      payment_time TIMESTAMPTZ,
      created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
      updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
    );
  `);

  const existingUsers = await pool.query('SELECT COUNT(*)::int AS cnt FROM users');
  if (existingUsers.rows[0].cnt === 0) {
    // Passwords are "password" (bcrypt hashed, same hash as database.sql)
    await pool.query(`
      INSERT INTO users (name, email, password, phone, role) VALUES
      ('Admin Staff', 'staff@parking.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '9876543210', 'staff'),
      ('Raj Kumar',   'user@parking.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '9123456789', 'user'),
      ('Priya Sharma','priya@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '9988776655', 'user');

      INSERT INTO parking_slots (slot_number, slot_type, status) VALUES
      ('A01','4W','occupied'),('A02','4W','available'),('A03','4W','available'),
      ('A04','4W','available'),('A05','4W','reserved'), ('A06','4W','available'),
      ('A07','4W','available'),('A08','4W','available'),('A09','4W','available'),
      ('A10','4W','available'),
      ('B01','2W','available'),('B02','2W','available'),('B03','2W','available'),
      ('B04','2W','available'),('B05','2W','available');

      INSERT INTO vehicles (user_id, vehicle_number, vehicle_type, owner_name, slot_id, entry_time, status)
      VALUES (2, 'TN38AB1234', '4W', 'Raj Kumar', 1, NOW() - INTERVAL '2 hours', 'parked');

      INSERT INTO vehicles (user_id, vehicle_number, vehicle_type, owner_name, slot_id, entry_time, exit_time, status)
      VALUES (2, 'TN38CD5678', '4W', 'Raj Kumar', NULL, NOW() - INTERVAL '5 hours', NOW() - INTERVAL '1 hour', 'exited');

      INSERT INTO billing (vehicle_id, user_id, vehicle_number, slot_number, entry_time, exit_time, duration_minutes, amount, payment_status, payment_time)
      VALUES (2, 2, 'TN38CD5678', 'A02', NOW() - INTERVAL '5 hours', NOW() - INTERVAL '1 hour', 240, 80.00, 'paid', NOW() - INTERVAL '1 hour');

      INSERT INTO vehicles (user_id, vehicle_number, vehicle_type, owner_name, slot_id, entry_time, exit_time, status)
      VALUES (3, 'TN38EF9012', '4W', 'Priya Sharma', NULL, NOW() - INTERVAL '3 hours', NOW() - INTERVAL '30 minutes', 'exited');

      INSERT INTO billing (vehicle_id, user_id, vehicle_number, slot_number, entry_time, exit_time, duration_minutes, amount, payment_status)
      VALUES (3, 3, 'TN38EF9012', 'A03', NOW() - INTERVAL '3 hours', NOW() - INTERVAL '30 minutes', 150, 60.00, 'pending');

      INSERT INTO bookings (user_id, slot_id, vehicle_number, booking_date, start_time, end_time, status)
      VALUES (2, 5, 'TN38AB1234', (NOW() AT TIME ZONE 'Asia/Kolkata')::date + 1, '10:00:00', '14:00:00', 'active');
    `);
    console.log('Seeded database with sample data.');
  } else {
    console.log('Database already initialized; skipping seed.');
  }
}

initDb()
  .then(() => {
    console.log('Database init complete.');
    process.exit(0);
  })
  .catch((err) => {
    console.error('Database init failed:', err);
    process.exit(1);
  });
