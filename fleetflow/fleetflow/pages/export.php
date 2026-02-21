<?php
require_once '../includes/config.php';
requireLogin();

$type = $_GET['type'] ?? 'csv';

if ($type === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="fleetflow_report_' . date('Ymd') . '.csv"');
    
    $out = fopen('php://output', 'w');
    
    // Trips
    fputcsv($out, ['=== FLEETFLOW EXPORT - ' . date('d M Y') . ' ===']);
    fputcsv($out, []);
    fputcsv($out, ['--- COMPLETED TRIPS ---']);
    fputcsv($out, ['Trip ID', 'Vehicle', 'Driver', 'Origin', 'Destination', 'Cargo (kg)', 'Revenue (₹)', 'Distance (km)', 'Start Date', 'Status']);
    $trips = $conn->query("SELECT t.*, v.name as vname, d.name as dname FROM trips t JOIN vehicles v ON t.vehicle_id=v.id JOIN drivers d ON t.driver_id=d.id ORDER BY t.id DESC");
    while ($t = $trips->fetch_assoc()) {
        fputcsv($out, [$t['id'], $t['vname'], $t['dname'], $t['origin'], $t['destination'], $t['cargo_weight'], $t['revenue'], $t['distance_km'], $t['start_date'], $t['status']]);
    }
    
    fputcsv($out, []);
    fputcsv($out, ['--- FUEL LOGS ---']);
    fputcsv($out, ['ID', 'Vehicle', 'Date', 'Liters', 'Cost/L', 'Total Cost', 'Odometer', 'Station']);
    $fuels = $conn->query("SELECT fl.*, v.name as vname FROM fuel_logs fl JOIN vehicles v ON fl.vehicle_id=v.id ORDER BY fl.id DESC");
    while ($f = $fuels->fetch_assoc()) {
        fputcsv($out, [$f['id'], $f['vname'], $f['fuel_date'], $f['liters'], $f['cost_per_liter'], $f['total_cost'], $f['odometer_at_fill'], $f['station']]);
    }
    
    fputcsv($out, []);
    fputcsv($out, ['--- DRIVER PERFORMANCE ---']);
    fputcsv($out, ['Name', 'License', 'Category', 'Expiry', 'Safety Score', 'Completed', 'Total', 'Status']);
    $drivers = $conn->query("SELECT * FROM drivers ORDER BY safety_score DESC");
    while ($d = $drivers->fetch_assoc()) {
        fputcsv($out, [$d['name'], $d['license_number'], $d['license_category'], $d['license_expiry'], $d['safety_score'], $d['trips_completed'], $d['trips_total'], $d['status']]);
    }
    
    fclose($out);
    exit();
}

if ($type === 'pdf') {
    // Simple HTML-based printable report
    header('Content-Type: text/html');
    $trips = $conn->query("SELECT t.*, v.name as vname, d.name as dname FROM trips t JOIN vehicles v ON t.vehicle_id=v.id JOIN drivers d ON t.driver_id=d.id ORDER BY t.id DESC LIMIT 20");
    $total_rev = $conn->query("SELECT COALESCE(SUM(revenue),0) as r FROM trips WHERE status='Completed'")->fetch_assoc()['r'];
    $total_fuel = $conn->query("SELECT COALESCE(SUM(total_cost),0) as c FROM fuel_logs")->fetch_assoc()['c'];
    $total_maint = $conn->query("SELECT COALESCE(SUM(cost),0) as c FROM maintenance_logs")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html>
<head>
<title>FleetFlow Report - <?= date('d M Y') ?></title>
<style>
body { font-family: Arial, sans-serif; margin: 30px; color: #333; }
h1 { color: #2563eb; }
h2 { color: #334155; border-bottom: 2px solid #2563eb; padding-bottom:6px; margin-top:30px; }
table { width:100%; border-collapse:collapse; margin-top:10px; font-size:13px; }
th { background:#2563eb; color:#fff; padding:8px; text-align:left; }
td { padding:7px 8px; border-bottom:1px solid #e2e8f0; }
tr:nth-child(even) td { background:#f8fafc; }
.kpi-row { display:flex; gap:20px; margin:20px 0; }
.kpi { background:#f1f5f9; border-radius:8px; padding:16px 24px; flex:1; }
.kpi-val { font-size:24px; font-weight:800; color:#2563eb; }
.kpi-lbl { font-size:12px; color:#64748b; }
.footer { margin-top:40px; font-size:11px; color:#94a3b8; text-align:center; }
@media print { .no-print { display:none } }
</style>
</head>
<body>
<div class="no-print" style="margin-bottom:20px">
    <button onclick="window.print()" style="background:#2563eb;color:#fff;border:none;padding:10px 20px;border-radius:6px;cursor:pointer;font-size:14px">🖨️ Print / Save as PDF</button>
    <a href="analytics.php" style="margin-left:10px;color:#2563eb">← Back</a>
</div>

<h1>🚚 FleetFlow — Operational Report</h1>
<p style="color:#64748b">Generated: <?= date('d F Y, H:i') ?> | System: FleetFlow v1.0</p>

<div class="kpi-row">
    <div class="kpi"><div class="kpi-val">₹<?= number_format($total_rev) ?></div><div class="kpi-lbl">Total Revenue</div></div>
    <div class="kpi"><div class="kpi-val">₹<?= number_format($total_fuel) ?></div><div class="kpi-lbl">Fuel Costs</div></div>
    <div class="kpi"><div class="kpi-val">₹<?= number_format($total_maint) ?></div><div class="kpi-lbl">Maintenance Costs</div></div>
    <div class="kpi"><div class="kpi-val">₹<?= number_format($total_rev - $total_fuel - $total_maint) ?></div><div class="kpi-lbl">Net Profit</div></div>
</div>

<h2>Recent Trips</h2>
<table>
    <tr><th>ID</th><th>Vehicle</th><th>Driver</th><th>Route</th><th>Cargo (kg)</th><th>Revenue</th><th>Status</th><th>Date</th></tr>
    <?php while ($t = $trips->fetch_assoc()): ?>
    <tr>
        <td>#<?= $t['id'] ?></td>
        <td><?= htmlspecialchars($t['vname']) ?></td>
        <td><?= htmlspecialchars($t['dname']) ?></td>
        <td><?= htmlspecialchars($t['origin']) ?> → <?= htmlspecialchars($t['destination']) ?></td>
        <td><?= number_format($t['cargo_weight']) ?></td>
        <td>₹<?= number_format($t['revenue']) ?></td>
        <td><?= $t['status'] ?></td>
        <td><?= $t['start_date'] ? date('d M Y', strtotime($t['start_date'])) : '—' ?></td>
    </tr>
    <?php endwhile; ?>
</table>

<div class="footer">FleetFlow Modular Fleet & Logistics Management System — Confidential Report</div>
</body>
</html>
<?php
    exit();
}
?>
