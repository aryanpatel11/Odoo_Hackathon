# 🚚 FleetFlow - Modular Fleet & Logistics Management System

## Quick Setup Guide (XAMPP)

### Requirements
- XAMPP (Apache + MySQL + PHP 7.4+)
- Web Browser

---

## Step 1: Copy Project Files
1. Copy the `fleetflow` folder to your XAMPP `htdocs` directory:
   - **Windows:** `C:\xampp\htdocs\fleetflow`
   - **Mac/Linux:** `/Applications/XAMPP/htdocs/fleetflow`

## Step 2: Start XAMPP
1. Open XAMPP Control Panel
2. Start **Apache** (green)
3. Start **MySQL** (green)

## Step 3: Import Database
1. Open browser → go to: `http://localhost/phpmyadmin`
2. Click **"New"** on the left panel → Create database named `fleetflow`
3. Click on `fleetflow` database → click **"Import"** tab
4. Click **"Choose File"** → Select `fleetflow/fleetflow.sql`
5. Click **"Go"** (blue button at bottom)

## Step 4: Launch Application
Open browser → go to: **`http://localhost/fleetflow`**

---

## 🔑 Default Login Credentials

| Role | Email | Password |
|------|-------|----------|
| Fleet Manager | manager@fleetflow.com | password |
| Dispatcher | dispatcher@fleetflow.com | password |
| Safety Officer | safety@fleetflow.com | password |
| Finance Analyst | finance@fleetflow.com | password |

---

## 📱 System Pages

| Page | URL | Purpose |
|------|-----|---------|
| Login | `/fleetflow/` | Authentication & RBAC |
| Dashboard | `/fleetflow/pages/dashboard.php` | KPI Command Center |
| Vehicles | `/fleetflow/pages/vehicles.php` | Asset Management (CRUD) |
| Trips | `/fleetflow/pages/trips.php` | Trip Dispatcher & Lifecycle |
| Maintenance | `/fleetflow/pages/maintenance.php` | Service Logs (auto-sets In Shop) |
| Fuel & Expenses | `/fleetflow/pages/fuel.php` | Financial Tracking |
| Drivers | `/fleetflow/pages/drivers.php` | Safety Profiles & Compliance |
| Analytics | `/fleetflow/pages/analytics.php` | ROI, Efficiency, Reports |

---

## ✨ Key Features

### Automatic Business Logic
- **Cargo Validation:** Trip blocked if cargo weight > vehicle max capacity
- **License Compliance:** Drivers with expired licenses cannot be assigned
- **In Shop Auto-Status:** Adding maintenance log → vehicle removed from dispatch pool
- **Status Lifecycle:** Draft → Dispatched → Completed → Cancelled

### Analytics & Reports
- Fuel efficiency (km/L) per vehicle
- Vehicle ROI: `(Revenue - Op. Cost) / Acquisition Cost × 100`
- CSV export for data analysis
- Printable PDF report

---

## 🗄️ Database Structure
```
users          → Login accounts with RBAC roles
vehicles       → Fleet asset registry
drivers        → Driver profiles & compliance
trips          → Trip lifecycle management
maintenance_logs → Service records (links to vehicle status)
fuel_logs      → Fuel & expense tracking
```

---

## Troubleshooting

**"Database connection failed":**
→ Make sure MySQL is running in XAMPP and you've imported the SQL file.

**"Page not found":**
→ Make sure the folder is named exactly `fleetflow` in htdocs.

**Blank page:**
→ Enable PHP error reporting by adding `<?php ini_set('display_errors',1); ?>` at top of the file temporarily.
