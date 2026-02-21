<?php
require_once '../includes/config.php';
requireLogin();

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $vid = intval($_POST['vehicle_id']);
        $stmt = $conn->prepare("INSERT INTO maintenance_logs (vehicle_id, service_type, description, cost, service_date, technician, status) VALUES (?,?,?,?,?,?,?)");
        $status = 'In Progress';
        $stmt->bind_param("issdsss", $vid, $_POST['service_type'], $_POST['description'], $_POST['cost'], $_POST['service_date'], $_POST['technician'], $status);
        if ($stmt->execute()) {
            // Auto-set vehicle to In Shop
            $conn->query("UPDATE vehicles SET status='In Shop' WHERE id=$vid");
            $msg = ['success', '✓ Maintenance log added. Vehicle status set to "In Shop" and removed from dispatcher pool.'];
        } else {
            $msg = ['danger', 'Error: ' . $conn->error];
        }
    }
    elseif ($action === 'complete') {
        $id = intval($_POST['id']);
        $vid = intval($_POST['vehicle_id']);
        $conn->query("UPDATE maintenance_logs SET status='Completed', completed_date=CURDATE() WHERE id=$id");
        $conn->query("UPDATE vehicles SET status='Available' WHERE id=$vid");
        $msg = ['success', 'Maintenance completed. Vehicle returned to Available.'];
    }
    elseif ($action === 'delete') {
        $id = intval($_POST['id']);
        $conn->query("DELETE FROM maintenance_logs WHERE id=$id");
        $msg = ['success', 'Log deleted.'];
    }
}

$logs = $conn->query("SELECT ml.*, v.name as vname, v.license_plate 
    FROM maintenance_logs ml 
    JOIN vehicles v ON ml.vehicle_id=v.id 
    ORDER BY ml.id DESC");

$vehicles = $conn->query("SELECT id, name, license_plate FROM vehicles WHERE status!='Retired' ORDER BY name");

include '../includes/header.php';
?>
<div class="page-header">
    <div>
        <div class="page-title"><i class="fas fa-wrench" style="color:var(--primary)"></i> Maintenance & Service Logs</div>
        <div class="page-subtitle">Preventative and reactive fleet health tracking</div>
    </div>
    <button class="btn btn-primary" onclick="openModal('addMaintModal')">
        <i class="fas fa-plus"></i> Log Service
    </button>
</div>

<?php if ($msg): ?>
<div class="alert alert-<?= $msg[0] ?>"><i class="fas fa-info-circle"></i> <?= $msg[1] ?></div>
<?php endif; ?>

<div class="alert alert-warning">
    <i class="fas fa-info-circle"></i>
    <strong>Auto-Logic:</strong> Adding a vehicle to a service log automatically sets its status to "In Shop" and hides it from the Dispatcher.
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>#</th><th>Vehicle</th><th>Service Type</th><th>Description</th><th>Cost (₹)</th><th>Technician</th><th>Service Date</th><th>Completed</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php if ($logs->num_rows === 0): ?>
                <tr><td colspan="10"><div class="empty-state"><div class="icon">🔧</div><p>No maintenance logs yet.</p></div></td></tr>
            <?php endif; ?>
            <?php while ($m = $logs->fetch_assoc()):
                $pc = ['Pending'=>'yellow','In Progress'=>'blue','Completed'=>'green'][$m['status']] ?? 'gray';
            ?>
            <tr>
                <td style="color:var(--text-muted)">#<?= $m['id'] ?></td>
                <td>
                    <div style="font-weight:600"><?= htmlspecialchars($m['vname']) ?></div>
                    <div style="font-size:11px;color:var(--text-muted)"><?= htmlspecialchars($m['license_plate']) ?></div>
                </td>
                <td><?= htmlspecialchars($m['service_type']) ?></td>
                <td style="max-width:200px;font-size:12px"><?= htmlspecialchars($m['description'] ?: '—') ?></td>
                <td>₹<?= number_format($m['cost'], 2) ?></td>
                <td><?= htmlspecialchars($m['technician'] ?: '—') ?></td>
                <td><?= date('d M Y', strtotime($m['service_date'])) ?></td>
                <td><?= $m['completed_date'] ? date('d M Y', strtotime($m['completed_date'])) : '—' ?></td>
                <td><span class="pill pill-<?= $pc ?>"><?= $m['status'] ?></span></td>
                <td>
                    <div style="display:flex;gap:4px">
                    <?php if ($m['status'] !== 'Completed'): ?>
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="action" value="complete">
                            <input type="hidden" name="id" value="<?= $m['id'] ?>">
                            <input type="hidden" name="vehicle_id" value="<?= $m['vehicle_id'] ?>">
                            <button type="submit" class="btn btn-success btn-xs">Mark Done</button>
                        </form>
                    <?php endif; ?>
                        <form method="POST" style="display:inline" onsubmit="return confirmDelete()">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $m['id'] ?>">
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

<!-- Add Modal -->
<div class="modal-overlay" id="addMaintModal">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title">Log Maintenance Service</div>
            <button class="modal-close" onclick="closeModal('addMaintModal')">×</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="form-grid">
                <div class="form-group full">
                    <label>Vehicle</label>
                    <select name="vehicle_id" required>
                        <option value="">-- Select Vehicle --</option>
                        <?php $vehicles->data_seek(0); while ($v = $vehicles->fetch_assoc()): ?>
                        <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['name']) ?> (<?= $v['license_plate'] ?>)</option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Service Type</label>
                    <input type="text" name="service_type" placeholder="e.g. Oil Change, Tire Replacement" required>
                </div>
                <div class="form-group">
                    <label>Technician / Workshop</label>
                    <input type="text" name="technician" placeholder="e.g. Ram Auto Works">
                </div>
                <div class="form-group">
                    <label>Cost (₹)</label>
                    <input type="number" name="cost" placeholder="0" step="0.01" min="0">
                </div>
                <div class="form-group">
                    <label>Service Date</label>
                    <input type="date" name="service_date" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="form-group full">
                    <label>Description</label>
                    <textarea name="description" placeholder="Detailed description of service needed..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('addMaintModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Log Service</button>
            </div>
        </form>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
