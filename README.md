# 🅿️ ParkIQ — Vehicle Parking Management System

A full-stack web application for managing vehicle parking with pre-booking, real-time slot tracking, and billing.

---

## 🚀 Tech Stack

| Layer    | Technology          |
|----------|---------------------|
| Frontend | HTML, CSS, JavaScript |
| Backend  | PHP 8.x             |
| Database | MySQL via phpMyAdmin (XAMPP) |
| Server   | Apache (XAMPP)      |

---

## 📁 Folder Structure

```
parking_system/
├── index.php                  ← Landing / Login / Register page
├── database.sql               ← Import this in phpMyAdmin
├── css/
│   └── style.css              ← Global stylesheet
├── js/
│   └── main.js                ← Notifications, helpers, AJAX utilities
├── includes/
│   ├── config.php             ← DB connection + pricing config
│   ├── auth.php               ← Session helpers
│   ├── staff_sidebar.php      ← Staff navigation sidebar
│   └── user_sidebar.php       ← User navigation sidebar
├── php/
│   ├── auth_handler.php       ← Login / Register / Logout
│   ├── vehicle_handler.php    ← Entry & Exit logic
│   ├── vehicle_api.php        ← AJAX: recent entries, exit history, search
│   ├── slots_api.php          ← AJAX: live slot grid & booking availability
│   ├── booking_handler.php    ← Create & cancel bookings
│   └── payment_handler.php    ← Mark payments as paid
├── staff/
│   ├── dashboard.php          ← Staff overview & quick actions
│   ├── vehicle_entry.php      ← Register vehicle entry
│   ├── vehicle_exit.php       ← Process vehicle exit & generate bill
│   ├── parked.php             ← All currently parked vehicles
│   ├── bookings.php           ← All pre-bookings
│   ├── payments.php           ← Billing & payment management
│   └── slots.php              ← Live slot grid
└── user/
    ├── dashboard.php          ← User overview
    ├── history.php            ← Parking history
    ├── billing.php            ← Bills & payment status
    ├── booking.php            ← Pre-book a slot
    ├── my_bookings.php        ← My booking list
    └── slots.php              ← Live slot status view
```

---

## ⚙️ Setup Instructions

### Step 1 — Install XAMPP
Download XAMPP from https://www.apachefriends.org and install it.  
Start **Apache** and **MySQL** from the XAMPP Control Panel.

### Step 2 — Copy Project Files
Copy the entire `parking_system` folder to:
```
C:\xampp\htdocs\parking_system\     (Windows)
/opt/lampp/htdocs/parking_system/   (Linux/Mac)
```

### Step 3 — Create Database
1. Open your browser and go to: `http://localhost/phpmyadmin`
2. Click **"New"** to create a new database — name it `parking_system`
3. Click the **Import** tab
4. Click **"Choose File"** → select `parking_system/database.sql`
5. Click **"Go"** to import

> This will create all tables and insert sample data automatically.

### Step 4 — Configure Database (if needed)
Edit `includes/config.php` if your MySQL credentials differ:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // change if different
define('DB_PASS', '');           // add password if set
define('DB_NAME', 'parking_system');
```

### Step 5 — Run the Application
Open your browser and go to:
```
http://localhost/parking_system/
```

---

## 🔑 Demo Login Credentials

| Role  | Email                | Password   |
|-------|----------------------|------------|
| Staff | staff@parking.com    | password   |
| User  | user@parking.com     | password   |
| User  | priya@example.com    | password   |

---

## 💰 Pricing Configuration

Pricing can be changed in `includes/config.php`:

```php
define('RATE_PER_HOUR_4W', 20);   // ₹20/hour for cars
define('RATE_PER_HOUR_2W', 10);   // ₹10/hour for bikes
define('MIN_CHARGE', 20);          // Minimum charge
```

---

## 📋 Features

### Staff Panel
- ✅ Summary cards: total, available, occupied, pending payments
- ✅ Vehicle Entry: enter vehicle number, auto-assign slot
- ✅ Vehicle Exit: select vehicle, auto-calculate fare, generate receipt
- ✅ Mark payments as Paid / Pending
- ✅ View all parked vehicles with live duration & estimated bill
- ✅ View all pre-bookings with cancel option
- ✅ Live slot grid (A-block 4W, B-block 2W)

### User Panel
- ✅ Profile card with role display
- ✅ Live slot availability stats
- ✅ Currently parked vehicle with live duration & estimated bill
- ✅ Parking history table
- ✅ Billing history with payment status
- ✅ Pre-book a slot: date, time, vehicle type, slot picker
- ✅ My bookings with cancel option
- ✅ Live slot status grid (colour-coded)
- ✅ Vehicle number search

### System Features
- ✅ Role-based authentication (Staff / User)
- ✅ Auto slot assignment on entry
- ✅ Time-based billing (rounded up to nearest hour)
- ✅ Booking conflict detection
- ✅ Reserved slots not assigned to walk-ins
- ✅ Real-time slot refresh (30s auto-reload)
- ✅ Toast notifications (success/error/warning)
- ✅ "Parking Full" alert when 90%+ occupied
- ✅ Fully responsive (mobile + desktop)

---

## 🗄️ Database Tables

| Table          | Description                              |
|----------------|------------------------------------------|
| `users`        | Registered users (staff & vehicle owners)|
| `parking_slots`| All parking slots with status            |
| `vehicles`     | Vehicle entry/exit records               |
| `bookings`     | Pre-booking records                      |
| `billing`      | Bills generated on exit                  |

---

## 🛠️ Troubleshooting

**"Database connection failed"**  
→ Make sure MySQL is running in XAMPP Control Panel.  
→ Check credentials in `includes/config.php`.

**"No available slots"**  
→ All slots may be occupied. Use staff panel to process some exits.  
→ Or re-import `database.sql` to reset.

**Login not working**  
→ Make sure `database.sql` was imported successfully.  
→ Use the demo credentials above (password: `password`).

**Blank page / PHP errors**  
→ Enable error display: add `ini_set('display_errors', 1);` at top of `index.php` temporarily.  
→ Ensure PHP 7.4+ is being used.
