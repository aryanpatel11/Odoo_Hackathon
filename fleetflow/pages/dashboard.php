<?php
require_once '../includes/config.php';
requireLogin();

// Filter criteria
$where_v = "WHERE 1=1";
$where_t = "WHERE 1=1";

if (!empty($_GET['type'])) {
    $type = $conn->real_escape_string($_GET['type']);
    $where_v .= " AND type='$type'";
}
if (!empty($_GET['status'])) {
    $status = $conn->real_escape_string($_GET['status']);
    $where_v .= " AND status='$status'";
}
if (!empty($_GET['region'])) {
    $region = $conn->real_escape_string($_GET['region']);
    $where_v .= " AND region LIKE '%$region%'";
}

// KPI queries
$active = $conn->query("SELECT COUNT(*) as c FROM vehicles $where_v AND status='On Trip'")->fetch_assoc()['c'];
$inshop = $conn->query("SELECT COUNT(*) as c FROM vehicles $where_v AND status='In Shop'")->fetch_assoc()['c'];
$total_v = $conn->query("SELECT COUNT(*) as c FROM vehicles $where_v AND status!='Retired'")->fetch_assoc()['c'];
$assigned = $conn->query("SELECT COUNT(*) as c FROM vehicles $where_v AND status IN ('On Trip')")->fetch_assoc()['c'];
$utilization = $total_v > 0 ? round(($assigned / $total_v) * 100) : 0;
$pending_cargo = $conn->query("SELECT COUNT(*) as c FROM trips WHERE status IN ('Draft','Dispatched')")->fetch_assoc()['c'];
$total_drivers = $conn->query("SELECT COUNT(*) as c FROM drivers")->fetch_assoc()['c'];
$expired_licenses = $conn->query("SELECT COUNT(*) as c FROM drivers WHERE license_expiry < CURDATE()")->fetch_assoc()['c'];

// Recent trips
$recent_trips = $conn->query("SELECT t.*, v.name as vehicle_name, d.name as driver_name 
    FROM trips t 
    JOIN vehicles v ON t.vehicle_id = v.id 
    JOIN drivers d ON t.driver_id = d.id 
    ORDER BY t.created_at DESC LIMIT 5");

// Vehicle status breakdown (Filtered)
$v_status = $conn->query("SELECT status, COUNT(*) as c FROM vehicles $where_v AND status!='Retired' GROUP BY status");
$status_data = [];
while ($r = $v_status->fetch_assoc()) $status_data[$r['status']] = $r['c'];

include '../includes/header.php';
?>
<div class="page-header">
    <div>
        <div class="page-title"><i class="fas fa-tachometer-alt" style="color:var(--primary)"></i> Command Center</div>
        <div class="page-subtitle">Real-time fleet overview — <?= date('l, d M Y') ?></div>
    </div>
</div>

<!-- Filters -->
<div class="filters-bar" style="margin-bottom: 20px;">
    <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;width:100%">
        <input type="text" name="region" placeholder="🔍 Filter by region..." value="<?= htmlspecialchars($_GET['region'] ?? '') ?>">
        <select name="type">
            <option value="">All Vehicle Types</option>
            <option value="Truck" <?= ($_GET['type']??'')==='Truck'?'selected':'' ?>>Truck</option>
            <option value="Van" <?= ($_GET['type']??'')==='Van'?'selected':'' ?>>Van</option>
            <option value="Bike" <?= ($_GET['type']??'')==='Bike'?'selected':'' ?>>Bike</option>
        </select>
        <select name="status">
            <option value="">All Statuses</option>
            <option value="Available" <?= ($_GET['status']??'')==='Available'?'selected':'' ?>>Available</option>
            <option value="On Trip" <?= ($_GET['status']??'')==='On Trip'?'selected':'' ?>>On Trip</option>
            <option value="In Shop" <?= ($_GET['status']??'')==='In Shop'?'selected':'' ?>>In Shop</option>
        </select>
        <button type="submit" class="btn btn-primary btn-sm">Apply Filters</button>
        <a href="dashboard.php" class="btn btn-outline btn-sm">Clear</a>
    </form>
</div>

<!-- KPIs -->
<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-icon blue"><i class="fas fa-truck"></i></div>
        <div>
            <div class="kpi-value"><?= $active ?></div>
            <div class="kpi-label">Active Fleet (On Trip)</div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon red"><i class="fas fa-wrench"></i></div>
        <div>
            <div class="kpi-value"><?= $inshop ?></div>
            <div class="kpi-label">Maintenance Alerts</div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon green"><i class="fas fa-chart-pie"></i></div>
        <div>
            <div class="kpi-value"><?= $utilization ?>%</div>
            <div class="kpi-label">Fleet Utilization Rate</div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon yellow"><i class="fas fa-boxes"></i></div>
        <div>
            <div class="kpi-value"><?= $pending_cargo ?></div>
            <div class="kpi-label">Pending / Active Trips</div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon purple"><i class="fas fa-id-card"></i></div>
        <div>
            <div class="kpi-value"><?= $total_drivers ?></div>
            <div class="kpi-label">Total Drivers</div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon red"><i class="fas fa-exclamation-triangle"></i></div>
        <div>
            <div class="kpi-value" style="color:<?= $expired_licenses>0?'#f87171':'#4ade80' ?>"><?= $expired_licenses ?></div>
            <div class="kpi-label">Expired Licenses</div>
        </div>
    </div>
</div>

<!-- Alerts -->
<?php if ($expired_licenses > 0): ?>
<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle"></i>
    <strong><?= $expired_licenses ?> driver(s)</strong> have expired licenses and cannot be assigned to trips.
    <a href="drivers.php" style="color:#fbbf24;margin-left:8px;">View Drivers →</a>
</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(400px, 1fr));gap:24px;">
    <!-- Recent Trips -->
    <div class="card">
        <div class="card-header">
            <div class="card-title"><i class="fas fa-route"></i> Recent Trips</div>
            <a href="trips.php" class="btn btn-outline btn-sm">View All</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Vehicle</th><th>Driver</th><th>Route</th><th>Status</th></tr></thead>
                <tbody>
                <?php if ($recent_trips->num_rows === 0): ?>
                    <tr><td colspan="4" style="text-align:center;color:var(--text-muted);padding:30px">No trips yet</td></tr>
                <?php endif; ?>
                <?php while ($t = $recent_trips->fetch_assoc()): 
                    $pclass = ['Draft'=>'gray','Dispatched'=>'blue','Completed'=>'green','Cancelled'=>'red'][$t['status']] ?? 'gray';
                ?>
                <tr>
                    <td><?= htmlspecialchars($t['vehicle_name']) ?></td>
                    <td><?= htmlspecialchars($t['driver_name']) ?></td>
                    <td><?= htmlspecialchars($t['origin']) ?> → <?= htmlspecialchars($t['destination']) ?></td>
                    <td><span class="pill pill-<?= $pclass ?>"><?= $t['status'] ?></span></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Vehicle Status -->
    <div class="card">
        <div class="card-header">
            <div class="card-title"><i class="fas fa-chart-donut"></i> Fleet Status</div>
            <a href="vehicles.php" class="btn btn-outline btn-sm">Manage</a>
        </div>
        <div style="display: flex; flex-direction: column; gap: 16px;">
            <?php
            $statuses = ['Available' => ['green','fa-check-circle'], 'On Trip' => ['blue','fa-road'], 'In Shop' => ['red','fa-wrench']];
            foreach ($statuses as $st => $info): 
                $count = $status_data[$st] ?? 0;
                $pct = $total_v > 0 ? round(($count/$total_v)*100) : 0;
            ?>
            <div>
                <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
                    <span style="font-size:14px; color:var(--text); font-weight:500;"><i class="fas <?= $info[1] ?>" style="color:var(--<?= $info[0]==='green'?'success':($info[0]==='blue'?'info':'danger') ?>);margin-right:8px; width: 16px; text-align:center;"></i><?= $st ?></span>
                    <span style="font-size:14px;font-weight:600; color:var(--text);"><?= $count ?> <span style="font-weight:400; color:var(--text-muted); font-size:13px;">(<?= $pct ?>%)</span></span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width:<?= $pct ?>%;background:var(--<?= $info[0]==='green'?'success':($info[0]==='blue'?'info':'danger') ?>)"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div style="margin-top:24px;padding-top:20px;border-top:1px solid var(--border);text-align:center;color:var(--text-muted);font-size:13px; font-weight:500;">
            Total Active Fleet: <?= $total_v ?> vehicles
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
