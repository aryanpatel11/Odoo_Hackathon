<?php
require_once '../includes/config.php';
requireLogin();

// Fuel efficiency per vehicle (km/L)
$fuel_eff = $conn->query("SELECT v.name, v.license_plate, v.odometer, v.acquisition_cost,
    COALESCE(SUM(fl.liters),0) as total_liters,
    COALESCE(SUM(fl.total_cost),0) as total_fuel_cost,
    COALESCE((SELECT SUM(ml.cost) FROM maintenance_logs ml WHERE ml.vehicle_id=v.id AND ml.status='Completed'),0) as maint_cost,
    COALESCE((SELECT SUM(t.revenue) FROM trips t WHERE t.vehicle_id=v.id AND t.status='Completed'),0) as total_revenue,
    COALESCE((SELECT COUNT(*) FROM trips t WHERE t.vehicle_id=v.id AND t.status='Completed'),0) as trips_done
    FROM vehicles v
    LEFT JOIN fuel_logs fl ON fl.vehicle_id=v.id
    GROUP BY v.id ORDER BY total_fuel_cost DESC");

// Overall stats
$total_revenue = $conn->query("SELECT COALESCE(SUM(revenue),0) as r FROM trips WHERE status='Completed'")->fetch_assoc()['r'];
$total_fuel = $conn->query("SELECT COALESCE(SUM(total_cost),0) as c FROM fuel_logs")->fetch_assoc()['c'];
$total_maint = $conn->query("SELECT COALESCE(SUM(cost),0) as c FROM maintenance_logs")->fetch_assoc()['c'];
$total_trips = $conn->query("SELECT COUNT(*) as c FROM trips WHERE status='Completed'")->fetch_assoc()['c'];
$avg_safety = $conn->query("SELECT ROUND(AVG(safety_score),1) as s FROM drivers")->fetch_assoc()['s'];

// Monthly trips
$monthly = $conn->query("SELECT DATE_FORMAT(start_date,'%b %Y') as month, COUNT(*) as trips, SUM(revenue) as rev
    FROM trips WHERE status='Completed' AND start_date IS NOT NULL
    GROUP BY DATE_FORMAT(start_date,'%Y-%m') ORDER BY DATE_FORMAT(start_date,'%Y-%m') DESC LIMIT 6");

// Driver performance
$driver_perf = $conn->query("SELECT d.name, d.safety_score, d.trips_completed, d.trips_total,
    COALESCE(SUM(t.revenue),0) as revenue
    FROM drivers d
    LEFT JOIN trips t ON t.driver_id=d.id AND t.status='Completed'
    GROUP BY d.id ORDER BY d.safety_score DESC");

include '../includes/header.php';
?>
<div class="page-header">
    <div>
        <div class="page-title"><i class="fas fa-chart-bar" style="color:var(--primary)"></i> Analytics & Financial Reports</div>
        <div class="page-subtitle">Data-driven fleet performance insights</div>
    </div>
    <div style="display:flex;gap:8px">
        <a href="export.php?type=csv" class="btn btn-outline btn-sm"><i class="fas fa-download"></i> Export CSV</a>
        <a href="export.php?type=pdf" class="btn btn-primary btn-sm"><i class="fas fa-file-pdf"></i> Export PDF</a>
    </div>
</div>

<!-- Overall KPIs -->
<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-icon green"><i class="fas fa-rupee-sign"></i></div>
        <div>
            <div class="kpi-value">₹<?= number_format($total_revenue/1000, 1) ?>K</div>
            <div class="kpi-label">Total Revenue</div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon red"><i class="fas fa-gas-pump"></i></div>
        <div>
            <div class="kpi-value">₹<?= number_format($total_fuel/1000, 1) ?>K</div>
            <div class="kpi-label">Total Fuel Cost</div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon yellow"><i class="fas fa-tools"></i></div>
        <div>
            <div class="kpi-value">₹<?= number_format($total_maint/1000, 1) ?>K</div>
            <div class="kpi-label">Total Maintenance</div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon blue"><i class="fas fa-check-circle"></i></div>
        <div>
            <div class="kpi-value"><?= $total_trips ?></div>
            <div class="kpi-label">Completed Trips</div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon purple"><i class="fas fa-shield-alt"></i></div>
        <div>
            <div class="kpi-value"><?= $avg_safety ?></div>
            <div class="kpi-label">Avg Safety Score</div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon cyan"><i class="fas fa-calculator"></i></div>
        <div>
            <div class="kpi-value">₹<?= number_format($total_revenue - $total_fuel - $total_maint, 0) ?></div>
            <div class="kpi-label">Net Profit</div>
        </div>
    </div>
</div>

<div class="analytics-grid">
    <!-- Vehicle ROI Table -->
    <div class="card" style="grid-column:1/-1">
        <div class="card-header">
            <div class="card-title"><i class="fas fa-chart-line"></i> Vehicle Performance & ROI</div>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Vehicle</th><th>Trips</th><th>Odometer</th>
                        <th>Fuel Used (L)</th><th>Fuel Efficiency</th>
                        <th>Revenue</th><th>Op. Cost</th><th>ROI</th>
                    </tr>
                </thead>
                <tbody>
                <?php $fuel_eff->data_seek(0); while ($r = $fuel_eff->fetch_assoc()):
                    $eff = $r['total_liters'] > 0 ? round($r['odometer'] / $r['total_liters'], 2) : 0;
                    $op_cost = $r['total_fuel_cost'] + $r['maint_cost'];
                    $roi = $r['acquisition_cost'] > 0 
                        ? round((($r['total_revenue'] - $op_cost) / $r['acquisition_cost']) * 100, 2)
                        : 0;
                    $roi_color = $roi > 0 ? '#4ade80' : '#f87171';
                ?>
                <tr>
                    <td>
                        <div style="font-weight:600"><?= htmlspecialchars($r['name']) ?></div>
                        <div style="font-size:11px;color:var(--text-muted)"><?= $r['license_plate'] ?></div>
                    </td>
                    <td><?= $r['trips_done'] ?></td>
                    <td><?= number_format($r['odometer']) ?> km</td>
                    <td><?= number_format($r['total_liters'], 1) ?> L</td>
                    <td>
                        <?php if ($eff > 0): ?>
                            <span style="font-weight:600"><?= $eff ?> km/L</span>
                        <?php else: ?>
                            <span style="color:var(--text-muted)">—</span>
                        <?php endif; ?>
                    </td>
                    <td>₹<?= number_format($r['total_revenue'], 2) ?></td>
                    <td>₹<?= number_format($op_cost, 2) ?></td>
                    <td style="font-weight:700;color:<?= $roi_color ?>"><?= $roi ?>%</td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Monthly Trips -->
    <div class="card">
        <div class="card-header">
            <div class="card-title"><i class="fas fa-calendar"></i> Monthly Performance</div>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Month</th><th>Trips</th><th>Revenue</th></tr></thead>
                <tbody>
                <?php $monthly->data_seek(0); while ($m = $monthly->fetch_assoc()): ?>
                <tr>
                    <td><?= $m['month'] ?></td>
                    <td><?= $m['trips'] ?></td>
                    <td>₹<?= number_format($m['rev'], 2) ?></td>
                </tr>
                <?php endwhile; ?>
                <?php if ($monthly->num_rows === 0): ?>
                <tr><td colspan="3" style="text-align:center;color:var(--text-muted);padding:20px">No data yet</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Driver Performance -->
    <div class="card">
        <div class="card-header">
            <div class="card-title"><i class="fas fa-user-shield"></i> Driver Safety Rankings</div>
        </div>
        <?php $driver_perf->data_seek(0); while ($dp = $driver_perf->fetch_assoc()):
            $sc = $dp['safety_score'] >= 90 ? 'high' : ($dp['safety_score'] >= 75 ? 'mid' : 'low');
            $rate = $dp['trips_total'] > 0 ? round(($dp['trips_completed']/$dp['trips_total'])*100) : 100;
        ?>
        <div class="metric-row">
            <div>
                <div style="font-weight:600;font-size:13px"><?= htmlspecialchars($dp['name']) ?></div>
                <div style="font-size:11px;color:var(--text-muted)"><?= $dp['trips_completed'] ?> trips — <?= $rate ?>% completion</div>
            </div>
            <div style="text-align:right">
                <div class="score score-<?= $sc ?>"><?= number_format($dp['safety_score'], 1) ?></div>
                <div style="font-size:11px;color:var(--text-muted)">Safety Score</div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</div>

<!-- ROI Formula Card -->
<div class="card" style="margin-top:20px">
    <div class="card-title" style="margin-bottom:12px"><i class="fas fa-info-circle"></i> Calculation Methodology</div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:16px;font-size:13px;color:var(--text-muted)">
        <div>
            <div style="color:#60a5fa;font-weight:600;margin-bottom:4px">Fuel Efficiency</div>
            Total Odometer ÷ Total Liters Consumed = <strong style="color:#fff">km/L</strong>
        </div>
        <div>
            <div style="color:#60a5fa;font-weight:600;margin-bottom:4px">Vehicle ROI</div>
            (Revenue − (Maintenance + Fuel)) ÷ Acquisition Cost × 100 = <strong style="color:#fff">ROI %</strong>
        </div>
        <div>
            <div style="color:#60a5fa;font-weight:600;margin-bottom:4px">Total Operational Cost</div>
            Fuel Cost + Maintenance Cost = <strong style="color:#fff">Total Op. Cost</strong>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
