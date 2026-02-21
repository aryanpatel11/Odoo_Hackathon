<?php
require_once '../includes/config.php';
requireLogin();

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $vid = intval($_POST['vehicle_id']);
        $did = intval($_POST['driver_id']);
        $cargo = floatval($_POST['cargo_weight']);

        $v = $conn->query("SELECT max_capacity FROM vehicles WHERE id=$vid")->fetch_assoc();
        $d = $conn->query("SELECT license_expiry FROM drivers WHERE id=$did")->fetch_assoc();

        if (!$v || !$d) {
            $msg = ['danger', 'Invalid vehicle or driver selection.'];
        } elseif ($cargo > $v['max_capacity']) {
            $msg = ['danger', "Cargo weight ({$cargo}kg) exceeds vehicle max capacity ({$v['max_capacity']}kg). Trip blocked by system!"];
        } elseif ($d['license_expiry'] < date('Y-m-d')) {
            $msg = ['danger', 'Driver license is expired. Cannot assign trip. Please update driver records.'];
        } else {
            $origin = trim($_POST['origin']);
            $dest = trim($_POST['destination']);
            $cdesc = trim($_POST['cargo_desc'] ?? '');
            $dist = floatval($_POST['distance_km'] ?? 0);
            $rev = floatval($_POST['revenue'] ?? 0);
            $sodo = floatval($_POST['start_odometer'] ?? 0);
            $raw_date = $_POST['start_date'] ?? '';
            $sdate = !empty($raw_date) ? str_replace('T', ' ', $raw_date) . ':00' : date('Y-m-d H:i:s');
            $notes = trim($_POST['notes'] ?? '');
            $status = isset($_POST['dispatch_now']) ? 'Dispatched' : 'Draft';

            $stmt = $conn->prepare("INSERT INTO trips (vehicle_id, driver_id, origin, destination, cargo_description, cargo_weight, distance_km, revenue, start_odometer, status, start_date, notes) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("iisssddddsss", $vid, $did, $origin, $dest, $cdesc, $cargo, $dist, $rev, $sodo, $status, $sdate, $notes);

            if ($stmt->execute()) {
                if ($status === 'Dispatched') {
                    $conn->query("UPDATE vehicles SET status='On Trip' WHERE id=$vid");
                    $conn->query("UPDATE drivers SET status='On Duty' WHERE id=$did");
                }
                $msg = ['success', "Trip created successfully! Status: $status"];
            } else {
                $msg = ['danger', 'Database error: ' . $stmt->error];
            }
        }
    }
    elseif ($action === 'update_status') {
        $tid = intval($_POST['trip_id']);
        $new_status = $_POST['new_status'];
        $allowed = ['Draft','Dispatched','Completed','Cancelled'];
        if (!in_array($new_status, $allowed)) exit('Invalid status');
        
        $trip = $conn->query("SELECT * FROM trips WHERE id=$tid")->fetch_assoc();
        $conn->query("UPDATE trips SET status='$new_status' WHERE id=$tid");

        if ($new_status === 'Completed') {
            $eodo = floatval($_POST['end_odometer'] ?? 0);
            if ($eodo > 0) $conn->query("UPDATE trips SET end_odometer=$eodo, end_date=NOW() WHERE id=$tid");
            $conn->query("UPDATE vehicles SET status='Available'" . ($eodo > 0 ? ", odometer=$eodo" : "") . " WHERE id={$trip['vehicle_id']}");
            $conn->query("UPDATE drivers SET status='Off Duty', trips_completed=trips_completed+1, trips_total=trips_total+1 WHERE id={$trip['driver_id']}");
        } elseif ($new_status === 'Cancelled') {
            $conn->query("UPDATE vehicles SET status='Available' WHERE id={$trip['vehicle_id']}");
            $conn->query("UPDATE drivers SET status='Off Duty', trips_total=trips_total+1 WHERE id={$trip['driver_id']}");
        } elseif ($new_status === 'Dispatched') {
            $conn->query("UPDATE vehicles SET status='On Trip' WHERE id={$trip['vehicle_id']}");
            $conn->query("UPDATE drivers SET status='On Duty' WHERE id={$trip['driver_id']}");
        }
        $msg = ['success', "Trip status updated to $new_status."];
    }
    elseif ($action === 'delete') {
        $tid = intval($_POST['trip_id']);
        $conn->query("DELETE FROM trips WHERE id=$tid");
        $msg = ['success', 'Trip record deleted.'];
    }
}

$where = "WHERE 1=1";
if (!empty($_GET['status'])) $where .= " AND t.status='" . $conn->real_escape_string($_GET['status']) . "'";

$trips = $conn->query("SELECT t.*, v.name as vname, v.max_capacity, d.name as dname 
    FROM trips t 
    JOIN vehicles v ON t.vehicle_id=v.id 
    JOIN drivers d ON t.driver_id=d.id 
    $where ORDER BY t.id DESC");

$avail_vehicles = $conn->query("SELECT id, name, license_plate, type, max_capacity FROM vehicles WHERE status='Available' ORDER BY name");
$avail_drivers = $conn->query("SELECT id, name, license_category, license_expiry FROM drivers WHERE status IN ('Off Duty','On Duty') AND license_expiry >= CURDATE() ORDER BY name");

include '../includes/header.php';
?>
<div class="page-header">
    <div>
        <div class="page-title"><i class="fas fa-route" style="color:var(--primary)"></i> Trip Dispatcher</div>
        <div class="page-subtitle">Create and manage delivery trips</div>
    </div>
    <button class="btn btn-primary" onclick="openModal('addTripModal')">
        <i class="fas fa-plus"></i> New Trip
    </button>
</div>

<?php if ($msg): ?>
<div class="alert alert-<?= $msg[0] ?>"><i class="fas fa-<?= $msg[0]==='success'?'check':'exclamation' ?>-circle"></i> <?= htmlspecialchars($msg[1]) ?></div>
<?php endif; ?>

<div class="filters-bar">
    <form method="GET" style="display:flex;gap:10px">
        <select name="status">
            <option value="">All Status</option>
            <?php foreach (['Draft','Dispatched','Completed','Cancelled'] as $s): ?>
            <option value="<?= $s ?>" <?= ($_GET['status']??'')===$s?'selected':'' ?>><?= $s ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
        <a href="trips.php" class="btn btn-outline btn-sm">Reset</a>
    </form>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>#</th><th>Vehicle</th><th>Driver</th><th>Route</th><th>Cargo (kg)</th><th>Revenue (₹)</th><th>Status</th><th>Date</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php if ($trips->num_rows === 0): ?>
                <tr><td colspan="9"><div class="empty-state"><div class="icon">🗺️</div><p>No trips found. Create one!</p></div></td></tr>
            <?php endif; ?>
            <?php while ($t = $trips->fetch_assoc()):
                $pc = ['Draft'=>'gray','Dispatched'=>'blue','Completed'=>'green','Cancelled'=>'red'][$t['status']] ?? 'gray';
            ?>
            <tr>
                <td style="color:var(--text-muted)">#<?= $t['id'] ?></td>
                <td><?= htmlspecialchars($t['vname']) ?></td>
                <td><?= htmlspecialchars($t['dname']) ?></td>
                <td>
                    <div style="font-size:12px"><?= htmlspecialchars($t['origin']) ?></div>
                    <div style="font-size:12px;color:var(--text-muted)">→ <?= htmlspecialchars($t['destination']) ?></div>
                </td>
                <td><?= number_format($t['cargo_weight']) ?> / <?= number_format($t['max_capacity']) ?></td>
                <td>₹<?= number_format($t['revenue'], 2) ?></td>
                <td><span class="pill pill-<?= $pc ?>"><?= $t['status'] ?></span></td>
                <td style="font-size:12px"><?= $t['start_date'] ? date('d M Y', strtotime($t['start_date'])) : '—' ?></td>
                <td>
                    <div style="display:flex;gap:4px;flex-wrap:wrap">
                    <?php if ($t['status'] === 'Draft'): ?>
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="trip_id" value="<?= $t['id'] ?>">
                            <input type="hidden" name="new_status" value="Dispatched">
                            <button type="submit" class="btn btn-primary btn-xs">Dispatch</button>
                        </form>
                    <?php elseif ($t['status'] === 'Dispatched'): ?>
                        <button class="btn btn-success btn-xs" onclick="openComplete(<?= $t['id'] ?>)">Complete</button>
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="trip_id" value="<?= $t['id'] ?>">
                            <input type="hidden" name="new_status" value="Cancelled">
                            <button type="submit" class="btn btn-danger btn-xs" onclick="return confirmDelete('Cancel this trip?')">Cancel</button>
                        </form>
                    <?php endif; ?>
                        <form method="POST" style="display:inline" onsubmit="return confirmDelete('Delete this trip record?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="trip_id" value="<?= $t['id'] ?>">
                            <button type="submit" class="action-btn delete"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Trip Modal -->
<div class="modal-overlay" id="addTripModal">
    <div class="modal" style="max-width:700px">
        <div class="modal-header">
            <div class="modal-title">Create New Trip</div>
            <button class="modal-close" onclick="closeModal('addTripModal')">×</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="alert alert-danger" id="capacity-warning" style="display:none">
                <i class="fas fa-exclamation-triangle"></i> Cargo weight exceeds vehicle max capacity!
            </div>
            <div class="form-grid">
                <div class="form-group">
                    <label>Select Vehicle (Available Only)</label>
                    <select name="vehicle_id" id="vehicle_id" required>
                        <option value="">-- Select Available Vehicle --</option>
                        <?php $avail_vehicles->data_seek(0); while ($v = $avail_vehicles->fetch_assoc()): ?>
                        <option value="<?= $v['id'] ?>" data-capacity="<?= $v['max_capacity'] ?>">
                            <?= htmlspecialchars($v['name']) ?> (<?= $v['type'] ?>) — Max: <?= number_format($v['max_capacity']) ?>kg
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Select Driver (Valid License Only)</label>
                    <select name="driver_id" id="driver_id" required>
                        <option value="">-- Select Available Driver --</option>
                        <?php $avail_drivers->data_seek(0); while ($d = $avail_drivers->fetch_assoc()): ?>
                        <option value="<?= $d['id'] ?>">
                            <?= htmlspecialchars($d['name']) ?> (<?= $d['license_category'] ?>) — Exp: <?= date('d M Y', strtotime($d['license_expiry'])) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Origin / Pickup</label>
                    <input type="text" name="origin" placeholder="Pickup location" required>
                </div>
                <div class="form-group">
                    <label>Destination / Delivery</label>
                    <input type="text" name="destination" placeholder="Delivery location" required>
                </div>
                <div class="form-group">
                    <label>Cargo Weight (kg)</label>
                    <input type="number" name="cargo_weight" id="cargo_weight" placeholder="e.g. 450" step="0.01" min="0.01" required>
                </div>
                <div class="form-group">
                    <label>Cargo Description</label>
                    <input type="text" name="cargo_desc" placeholder="e.g. Electronics, Produce">
                </div>
                <div class="form-group">
                    <label>Distance (km)</label>
                    <input type="number" name="distance_km" placeholder="0" step="0.01" min="0" value="0">
                </div>
                <div class="form-group">
                    <label>Expected Revenue (₹)</label>
                    <input type="number" name="revenue" placeholder="0" step="0.01" min="0" value="0">
                </div>
                <div class="form-group">
                    <label>Start Odometer (km)</label>
                    <input type="number" name="start_odometer" placeholder="0" step="0.01" value="0">
                </div>
                <div class="form-group">
                    <label>Start Date & Time</label>
                    <input type="datetime-local" name="start_date" value="<?= date('Y-m-d\TH:i') ?>">
                </div>
                <div class="form-group full">
                    <label>Notes / Instructions</label>
                    <textarea name="notes" placeholder="Additional instructions or special requirements..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('addTripModal')">Cancel</button>
                <button type="submit" class="btn btn-warning" id="trip-draft"><i class="fas fa-save"></i> Save as Draft</button>
                <button type="submit" name="dispatch_now" value="1" class="btn btn-success" id="trip-dispatch"><i class="fas fa-paper-plane"></i> Create & Dispatch Now</button>
            </div>
        </form>
    </div>
</div>

<!-- Complete Trip Modal -->
<div class="modal-overlay" id="completeModal">
    <div class="modal" style="max-width:420px">
        <div class="modal-header">
            <div class="modal-title"><i class="fas fa-check-circle"></i> Complete Trip</div>
            <button class="modal-close" onclick="closeModal('completeModal')">×</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="new_status" value="Completed">
            <input type="hidden" name="trip_id" id="complete_trip_id">
            <div class="form-group" style="margin-bottom:16px">
                <label>Final Odometer Reading (km)</label>
                <input type="number" name="end_odometer" placeholder="Enter final odometer reading" step="0.01" required>
                <small style="color:var(--text-muted);font-size:11px;margin-top:4px;">This updates the vehicle's odometer and sets both Vehicle & Driver to Available.</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('completeModal')">Cancel</button>
                <button type="submit" class="btn btn-success"><i class="fas fa-check"></i> Mark Trip Completed</button>
            </div>
        </form>
    </div>
</div>

<script>
function openComplete(tripId) {
    document.getElementById('complete_trip_id').value = tripId;
    openModal('completeModal');
}
</script>
<?php include '../includes/footer.php'; ?>
