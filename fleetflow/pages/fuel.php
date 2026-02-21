<?php
require_once '../includes/config.php';
requireLogin();
requireRole(['manager', 'financial_analyst', 'admin']);

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $vid = intval($_POST['vehicle_id']);
        $liters = floatval($_POST['liters']);
        $cpl = floatval($_POST['cost_per_liter']);
        $total = round($liters * $cpl, 2);
        $odo = floatval($_POST['odometer_at_fill'] ?? 0);
        $fd = $_POST['fuel_date'];
        $station = trim($_POST['station'] ?? '');

        $stmt = $conn->prepare("INSERT INTO fuel_logs (vehicle_id, liters, cost_per_liter, total_cost, odometer_at_fill, fuel_date, station) VALUES (?,?,?,?,?,?,?)");
        $stmt->bind_param("iddddss", $vid, $liters, $cpl, $total, $odo, $fd, $station);
        if ($stmt->execute()) $msg = ['success', "Fuel log added. Total Cost: Rs. " . number_format($total, 2)];
        else $msg = ['danger', 'Error: ' . $stmt->error];
    }
    elseif ($action === 'delete') {
        $conn->query("DELETE FROM fuel_logs WHERE id=" . intval($_POST['id']));
        $msg = ['success', 'Record deleted.'];
    }
}

$logs = $conn->query("SELECT fl.*, v.name as vname FROM fuel_logs fl JOIN vehicles v ON fl.vehicle_id=v.id ORDER BY fl.id DESC");

$summary = $conn->query("SELECT v.id, v.name, v.license_plate,
    COALESCE(SUM(fl.total_cost),0) as fuel_cost,
    COALESCE((SELECT SUM(ml.cost) FROM maintenance_logs ml WHERE ml.vehicle_id=v.id),0) as maint_cost
    FROM vehicles v
    LEFT JOIN fuel_logs fl ON fl.vehicle_id=v.id
    GROUP BY v.id ORDER BY fuel_cost DESC LIMIT 10");

$vehicles = $conn->query("SELECT id, name, license_plate FROM vehicles WHERE status!='Retired' ORDER BY name");

include '../includes/header.php';
?>
<div class="page-header">
    <div>
        <div class="page-title"><i class="fas fa-gas-pump"></i> Fuel &amp; Expense Logs</div>
        <div class="page-subtitle">Track operational costs per vehicle</div>
    </div>
    <button class="btn btn-primary" onclick="openModal('addFuelModal')">
        <i class="fas fa-plus"></i> Log Fuel
    </button>
</div>

<?php if ($msg): ?>
<div class="alert alert-<?= $msg[0] ?>"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($msg[1]) ?></div>
<?php endif; ?>

<!-- Cost Summary -->
<div class="card" style="margin-bottom:20px">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-calculator"></i> Total Operational Cost per Vehicle</div>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Vehicle</th><th>Plate</th><th>Fuel Cost (Rs.)</th><th>Maintenance Cost (Rs.)</th><th>Total Operational Cost (Rs.)</th></tr></thead>
            <tbody>
            <?php $summary->data_seek(0); while ($s = $summary->fetch_assoc()): ?>
            <tr>
                <td style="font-weight:600"><?= htmlspecialchars($s['name']) ?></td>
                <td><code style="background:var(--dark);padding:2px 6px;border-radius:4px;font-size:12px"><?= htmlspecialchars($s['license_plate']) ?></code></td>
                <td>Rs. <?= number_format($s['fuel_cost'], 2) ?></td>
                <td>Rs. <?= number_format($s['maint_cost'], 2) ?></td>
                <td style="font-weight:700;color:#4ade80">Rs. <?= number_format($s['fuel_cost'] + $s['maint_cost'], 2) ?></td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Fuel Logs -->
<div class="card">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-list"></i> Fuel Log History</div>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>#</th><th>Vehicle</th><th>Date</th><th>Liters</th><th>Rs./Liter</th><th>Total Cost</th><th>Odometer</th><th>Station</th><th>Action</th></tr></thead>
            <tbody>
            <?php if ($logs->num_rows === 0): ?>
                <tr><td colspan="9"><div class="empty-state"><div class="icon">&#9981;</div><p>No fuel logs yet.</p></div></td></tr>
            <?php endif; ?>
            <?php while ($f = $logs->fetch_assoc()): ?>
            <tr>
                <td style="color:var(--text-muted)">#<?= $f['id'] ?></td>
                <td><?= htmlspecialchars($f['vname']) ?></td>
                <td><?= date('d M Y', strtotime($f['fuel_date'])) ?></td>
                <td><?= number_format($f['liters'], 2) ?> L</td>
                <td>Rs. <?= number_format($f['cost_per_liter'], 2) ?></td>
                <td style="font-weight:600">Rs. <?= number_format($f['total_cost'], 2) ?></td>
                <td><?= number_format($f['odometer_at_fill']) ?> km</td>
                <td><?= htmlspecialchars($f['station'] ?: '—') ?></td>
                <td>
                    <form method="POST" style="display:inline" onsubmit="return confirmDelete()">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $f['id'] ?>">
                        <button type="submit" class="action-btn delete"><i class="fas fa-trash"></i></button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Modal -->
<div class="modal-overlay" id="addFuelModal">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title">Log Fuel Entry</div>
            <button class="modal-close" onclick="closeModal('addFuelModal')">x</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="form-grid">
                <div class="form-group">
                    <label>Vehicle</label>
                    <select name="vehicle_id" required>
                        <option value="">-- Select Vehicle --</option>
                        <?php $vehicles->data_seek(0); while ($v = $vehicles->fetch_assoc()): ?>
                        <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['name']) ?> (<?= $v['license_plate'] ?>)</option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Fuel Date</label>
                    <input type="date" name="fuel_date" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label>Liters Filled</label>
                    <input type="number" name="liters" placeholder="e.g. 40" step="0.01" min="0.01" required>
                </div>
                <div class="form-group">
                    <label>Cost per Liter (Rs.)</label>
                    <input type="number" name="cost_per_liter" placeholder="e.g. 96.50" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                    <label>Odometer at Fill (km)</label>
                    <input type="number" name="odometer_at_fill" placeholder="0" step="0.01" min="0" value="0">
                </div>
                <div class="form-group">
                    <label>Station Name</label>
                    <input type="text" name="station" placeholder="e.g. HP Petrol Pump">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('addFuelModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Fuel Log</button>
            </div>
        </form>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
