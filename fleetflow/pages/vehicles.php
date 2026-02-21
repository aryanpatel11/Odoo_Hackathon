<?php
require_once '../includes/config.php';
requireLogin();
requireRole(['manager', 'dispatcher', 'safety_officer', 'admin']);

$msg = '';

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $stmt = $conn->prepare("INSERT INTO vehicles (name, model, license_plate, type, max_capacity, odometer, acquisition_cost, region) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->bind_param("ssssddds", $_POST['name'], $_POST['model'], $_POST['license_plate'], $_POST['type'], $_POST['max_capacity'], $_POST['odometer'], $_POST['acquisition_cost'], $_POST['region']);
        if ($stmt->execute()) $msg = ['success', 'Vehicle added successfully!'];
        else $msg = ['danger', 'Error: ' . $conn->error];
    }
    elseif ($action === 'edit') {
        $stmt = $conn->prepare("UPDATE vehicles SET name=?, model=?, license_plate=?, type=?, max_capacity=?, odometer=?, acquisition_cost=?, region=? WHERE id=?");
        $stmt->bind_param("ssssdddsi", $_POST['name'], $_POST['model'], $_POST['license_plate'], $_POST['type'], $_POST['max_capacity'], $_POST['odometer'], $_POST['acquisition_cost'], $_POST['region'], $_POST['id']);
        if ($stmt->execute()) $msg = ['success', 'Vehicle updated!'];
        else $msg = ['danger', 'Error: ' . $conn->error];
    }
    elseif ($action === 'toggle_retired') {
        $id = intval($_POST['id']);
        $v = $conn->query("SELECT status FROM vehicles WHERE id=$id")->fetch_assoc();
        $new = $v['status'] === 'Retired' ? 'Available' : 'Retired';
        $conn->query("UPDATE vehicles SET status='$new' WHERE id=$id");
        $msg = ['success', "Vehicle status set to $new."];
    }
    elseif ($action === 'delete') {
        $id = intval($_POST['id']);
        $conn->query("DELETE FROM vehicles WHERE id=$id");
        $msg = ['success', 'Vehicle deleted.'];
    }
}

// Filters
$where = "WHERE 1=1";
if (!empty($_GET['type'])) $where .= " AND v.type='" . $conn->real_escape_string($_GET['type']) . "'";
if (!empty($_GET['status'])) $where .= " AND v.status='" . $conn->real_escape_string($_GET['status']) . "'";
if (!empty($_GET['region'])) $where .= " AND v.region LIKE '%" . $conn->real_escape_string($_GET['region']) . "%'";

$vehicles = $conn->query("SELECT v.*, 
    (SELECT SUM(fl.total_cost) FROM fuel_logs fl WHERE fl.vehicle_id=v.id) as total_fuel,
    (SELECT SUM(ml.cost) FROM maintenance_logs ml WHERE ml.vehicle_id=v.id) as total_maint,
    (SELECT COUNT(*) FROM trips t WHERE t.vehicle_id=v.id AND t.status='Completed') as trips_done
    FROM vehicles v $where ORDER BY v.id DESC");

include '../includes/header.php';
?>
<div class="page-header">
    <div>
        <div class="page-title"><i class="fas fa-truck"></i> Vehicle Registry</div>
        <div class="page-subtitle">Manage your fleet assets</div>
    </div>
    <button class="btn btn-primary" onclick="openModal('addModal')">
        <i class="fas fa-plus"></i> Add Vehicle
    </button>
</div>

<?php if ($msg): ?>
<div class="alert alert-<?= $msg[0] ?>"><i class="fas fa-check-circle"></i> <?= $msg[1] ?></div>
<?php endif; ?>

<!-- Filters -->
<div class="filters-bar">
    <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;width:100%">
        <input type="text" name="region" placeholder="🔍 Search by region..." value="<?= htmlspecialchars($_GET['region'] ?? '') ?>">
        <select name="type">
            <option value="">All Types</option>
            <option value="Truck" <?= ($_GET['type']??'')==='Truck'?'selected':'' ?>>Truck</option>
            <option value="Van" <?= ($_GET['type']??'')==='Van'?'selected':'' ?>>Van</option>
            <option value="Bike" <?= ($_GET['type']??'')==='Bike'?'selected':'' ?>>Bike</option>
        </select>
        <select name="status">
            <option value="">All Status</option>
            <option value="Available" <?= ($_GET['status']??'')==='Available'?'selected':'' ?>>Available</option>
            <option value="On Trip" <?= ($_GET['status']??'')==='On Trip'?'selected':'' ?>>On Trip</option>
            <option value="In Shop" <?= ($_GET['status']??'')==='In Shop'?'selected':'' ?>>In Shop</option>
            <option value="Retired" <?= ($_GET['status']??'')==='Retired'?'selected':'' ?>>Retired</option>
        </select>
        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
        <a href="vehicles.php" class="btn btn-outline btn-sm">Reset</a>
    </form>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th><th>Vehicle</th><th>Type</th><th>Plate</th>
                    <th>Capacity (kg)</th><th>Odometer</th><th>Region</th>
                    <th>Trips</th><th>Total Cost</th><th>Status</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($vehicles->num_rows === 0): ?>
                <tr><td colspan="11"><div class="empty-state"><div class="icon">🚛</div><p>No vehicles found.</p></div></td></tr>
            <?php endif; ?>
            <?php $i=1; while ($v = $vehicles->fetch_assoc()): 
                $spill = ['Available'=>'green','On Trip'=>'blue','In Shop'=>'red','Retired'=>'gray'][$v['status']] ?? 'gray';
                $total_cost = ($v['total_fuel'] ?? 0) + ($v['total_maint'] ?? 0);
            ?>
            <tr>
                <td style="color:var(--text-muted)"><?= $i++ ?></td>
                <td>
                    <div style="font-weight:600"><?= htmlspecialchars($v['name']) ?></div>
                    <div style="font-size:11px;color:var(--text-muted)"><?= htmlspecialchars($v['model']) ?></div>
                </td>
                <td><span class="pill pill-<?= $v['type']==='Truck'?'blue':($v['type']==='Van'?'green':'yellow') ?>"><?= $v['type'] ?></span></td>
                <td><code style="background:var(--dark);padding:2px 6px;border-radius:4px;font-size:12px"><?= htmlspecialchars($v['license_plate']) ?></code></td>
                <td><?= number_format($v['max_capacity']) ?> kg</td>
                <td><?= number_format($v['odometer']) ?> km</td>
                <td><?= htmlspecialchars($v['region'] ?: '—') ?></td>
                <td><?= $v['trips_done'] ?></td>
                <td>₹<?= number_format($total_cost, 2) ?></td>
                <td><span class="pill pill-<?= $spill ?>"><?= $v['status'] ?></span></td>
                <td>
                    <div style="display:flex;gap:4px;">
                        <button class="action-btn edit" onclick="editVehicle(<?= htmlspecialchars(json_encode($v)) ?>)" title="Edit"><i class="fas fa-edit"></i></button>
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="action" value="toggle_retired">
                            <input type="hidden" name="id" value="<?= $v['id'] ?>">
                            <button type="submit" class="action-btn view" title="Toggle Retired" style="background:rgba(217,119,6,0.2);color:#fbbf24"><i class="fas fa-power-off"></i></button>
                        </form>
                        <form method="POST" style="display:inline" onsubmit="return confirmDelete()">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $v['id'] ?>">
                            <button type="submit" class="action-btn delete" title="Delete"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Modal -->
<div class="modal-overlay" id="addModal">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title">Add New Vehicle</div>
            <button class="modal-close" onclick="closeModal('addModal')">×</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="form-grid">
                <div class="form-group">
                    <label>Vehicle Name</label>
                    <input type="text" name="name" placeholder="e.g. Van-05" required>
                </div>
                <div class="form-group">
                    <label>Model</label>
                    <input type="text" name="model" placeholder="e.g. Toyota HiAce 2022" required>
                </div>
                <div class="form-group">
                    <label>License Plate (Unique)</label>
                    <input type="text" name="license_plate" placeholder="e.g. GJ-01-AA-0001" required>
                </div>
                <div class="form-group">
                    <label>Type</label>
                    <select name="type" required>
                        <option value="Truck">Truck</option>
                        <option value="Van">Van</option>
                        <option value="Bike">Bike</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Max Capacity (kg)</label>
                    <input type="number" name="max_capacity" placeholder="500" required min="1">
                </div>
                <div class="form-group">
                    <label>Current Odometer (km)</label>
                    <input type="number" name="odometer" placeholder="0" value="0" min="0">
                </div>
                <div class="form-group">
                    <label>Acquisition Cost (₹)</label>
                    <input type="number" name="acquisition_cost" placeholder="0" value="0" min="0">
                </div>
                <div class="form-group">
                    <label>Region</label>
                    <input type="text" name="region" placeholder="e.g. North, South">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('addModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Vehicle</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal-overlay" id="editModal">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title">Edit Vehicle</div>
            <button class="modal-close" onclick="closeModal('editModal')">×</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            <div class="form-grid">
                <div class="form-group">
                    <label>Vehicle Name</label>
                    <input type="text" name="name" id="edit_name" required>
                </div>
                <div class="form-group">
                    <label>Model</label>
                    <input type="text" name="model" id="edit_model" required>
                </div>
                <div class="form-group">
                    <label>License Plate</label>
                    <input type="text" name="license_plate" id="edit_plate" required>
                </div>
                <div class="form-group">
                    <label>Type</label>
                    <select name="type" id="edit_type">
                        <option value="Truck">Truck</option>
                        <option value="Van">Van</option>
                        <option value="Bike">Bike</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Max Capacity (kg)</label>
                    <input type="number" name="max_capacity" id="edit_cap" required>
                </div>
                <div class="form-group">
                    <label>Odometer (km)</label>
                    <input type="number" name="odometer" id="edit_odo">
                </div>
                <div class="form-group">
                    <label>Acquisition Cost (₹)</label>
                    <input type="number" name="acquisition_cost" id="edit_acq">
                </div>
                <div class="form-group">
                    <label>Region</label>
                    <input type="text" name="region" id="edit_region">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('editModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Vehicle</button>
            </div>
        </form>
    </div>
</div>

<script>
function editVehicle(v) {
    document.getElementById('edit_id').value = v.id;
    document.getElementById('edit_name').value = v.name;
    document.getElementById('edit_model').value = v.model;
    document.getElementById('edit_plate').value = v.license_plate;
    document.getElementById('edit_type').value = v.type;
    document.getElementById('edit_cap').value = v.max_capacity;
    document.getElementById('edit_odo').value = v.odometer;
    document.getElementById('edit_acq').value = v.acquisition_cost;
    document.getElementById('edit_region').value = v.region;
    openModal('editModal');
}
</script>
<?php include '../includes/footer.php'; ?>
