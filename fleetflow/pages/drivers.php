<?php
require_once '../includes/config.php';
requireLogin();
requireRole(['manager', 'dispatcher', 'safety_officer', 'admin']);

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $stmt = $conn->prepare("INSERT INTO drivers (name, email, phone, license_number, license_category, license_expiry, status) VALUES (?,?,?,?,?,?,?)");
        $st = 'Off Duty';
        $stmt->bind_param("sssssss", $_POST['name'], $_POST['email'], $_POST['phone'], $_POST['license_number'], $_POST['license_category'], $_POST['license_expiry'], $st);
        if ($stmt->execute()) $msg = ['success', 'Driver profile created!'];
        else $msg = ['danger', 'Error: ' . $conn->error];
    }
    elseif ($action === 'edit') {
        $stmt = $conn->prepare("UPDATE drivers SET name=?, email=?, phone=?, license_number=?, license_category=?, license_expiry=?, status=? WHERE id=?");
        $stmt->bind_param("sssssssi", $_POST['name'], $_POST['email'], $_POST['phone'], $_POST['license_number'], $_POST['license_category'], $_POST['license_expiry'], $_POST['status'], $_POST['id']);
        if ($stmt->execute()) $msg = ['success', 'Driver updated!'];
        else $msg = ['danger', 'Error: ' . $conn->error];
    }
    elseif ($action === 'delete') {
        $conn->query("DELETE FROM drivers WHERE id=" . intval($_POST['id']));
        $msg = ['success', 'Driver deleted.'];
    }
}

$drivers = $conn->query("SELECT d.*, 
    DATEDIFF(d.license_expiry, CURDATE()) as days_to_expiry,
    (SELECT COUNT(*) FROM trips t WHERE t.driver_id=d.id AND t.status='Completed') as actual_completed
    FROM drivers d ORDER BY d.id DESC");

include '../includes/header.php';
?>
<div class="page-header">
    <div>
        <div class="page-title"><i class="fas fa-id-card"></i> Driver Profiles</div>
        <div class="page-subtitle">HR, compliance & safety management</div>
    </div>
    <button class="btn btn-primary" onclick="openModal('addDriverModal')">
        <i class="fas fa-plus"></i> Add Driver
    </button>
</div>

<?php if ($msg): ?>
<div class="alert alert-<?= $msg[0] ?>"><i class="fas fa-check-circle"></i> <?= $msg[1] ?></div>
<?php endif; ?>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>#</th><th>Driver</th><th>License</th><th>Category</th><th>Expiry</th><th>Safety Score</th><th>Completion Rate</th><th>Status</th><th>Compliance</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php if ($drivers->num_rows === 0): ?>
                <tr><td colspan="10"><div class="empty-state"><div class="icon">👤</div><p>No drivers found.</p></div></td></tr>
            <?php endif; ?>
            <?php $i=1; while ($d = $drivers->fetch_assoc()):
                $status_class = ['On Duty'=>'blue','Off Duty'=>'green','Suspended'=>'red'][$d['status']] ?? 'gray';
                $expired = $d['days_to_expiry'] < 0;
                $expiring_soon = $d['days_to_expiry'] >= 0 && $d['days_to_expiry'] <= 30;
                $completion_rate = $d['trips_total'] > 0 ? round(($d['trips_completed'] / $d['trips_total']) * 100) : 100;
                $score_class = $d['safety_score'] >= 90 ? 'high' : ($d['safety_score'] >= 75 ? 'mid' : 'low');
            ?>
            <tr>
                <td style="color:var(--text-muted)"><?= $i++ ?></td>
                <td>
                    <div style="font-weight:600"><?= htmlspecialchars($d['name']) ?></div>
                    <div style="font-size:11px;color:var(--text-muted)"><?= htmlspecialchars($d['email'] ?: '—') ?></div>
                    <div style="font-size:11px;color:var(--text-muted)"><?= htmlspecialchars($d['phone'] ?: '') ?></div>
                </td>
                <td><code style="background:var(--dark);padding:2px 6px;border-radius:4px;font-size:11px"><?= htmlspecialchars($d['license_number']) ?></code></td>
                <td><span class="pill pill-blue"><?= htmlspecialchars($d['license_category']) ?></span></td>
                <td>
                    <div style="font-size:12px;color:<?= $expired?'#f87171':($expiring_soon?'#fbbf24':'#4ade80') ?>">
                        <?= date('d M Y', strtotime($d['license_expiry'])) ?>
                    </div>
                    <?php if ($expired): ?>
                        <div style="font-size:10px;color:#f87171">EXPIRED</div>
                    <?php elseif ($expiring_soon): ?>
                        <div style="font-size:10px;color:#fbbf24">Expires in <?= $d['days_to_expiry'] ?> days</div>
                    <?php endif; ?>
                </td>
                <td><span class="score score-<?= $score_class ?>"><?= number_format($d['safety_score'], 1) ?></span></td>
                <td>
                    <div style="font-size:12px;margin-bottom:4px"><?= $completion_rate ?>% (<?= $d['trips_completed'] ?>/<?= $d['trips_total'] ?>)</div>
                    <div class="progress-bar"><div class="progress-fill" style="width:<?= $completion_rate ?>%"></div></div>
                </td>
                <td><span class="pill pill-<?= $status_class ?>"><?= $d['status'] ?></span></td>
                <td>
                    <?php if ($expired): ?>
                        <span class="pill pill-red">❌ Blocked</span>
                    <?php elseif ($expiring_soon): ?>
                        <span class="pill pill-yellow">⚠️ Expiring</span>
                    <?php else: ?>
                        <span class="pill pill-green">✓ Valid</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div style="display:flex;gap:4px">
                        <button class="action-btn edit" onclick="editDriver(<?= htmlspecialchars(json_encode($d)) ?>)" title="Edit"><i class="fas fa-edit"></i></button>
                        <form method="POST" style="display:inline" onsubmit="return confirmDelete()">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $d['id'] ?>">
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
<div class="modal-overlay" id="addDriverModal">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title">Add New Driver</div>
            <button class="modal-close" onclick="closeModal('addDriverModal')">×</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="form-grid">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" placeholder="Driver's full name" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="email@example.com">
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone" placeholder="9876543210">
                </div>
                <div class="form-group">
                    <label>License Number (Unique)</label>
                    <input type="text" name="license_number" placeholder="DL-GJ-2024-000001" required>
                </div>
                <div class="form-group">
                    <label>License Category</label>
                    <select name="license_category">
                        <option value="Van">Van</option>
                        <option value="Truck">Truck</option>
                        <option value="Bike">Bike</option>
                        <option value="All">All Vehicles</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>License Expiry Date</label>
                    <input type="date" name="license_expiry" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('addDriverModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Driver</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal-overlay" id="editDriverModal">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title">Edit Driver</div>
            <button class="modal-close" onclick="closeModal('editDriverModal')">×</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_did">
            <div class="form-grid">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" id="edit_dname" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" id="edit_demail">
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone" id="edit_dphone">
                </div>
                <div class="form-group">
                    <label>License Number</label>
                    <input type="text" name="license_number" id="edit_dlicense" required>
                </div>
                <div class="form-group">
                    <label>License Category</label>
                    <select name="license_category" id="edit_dcat">
                        <option value="Van">Van</option>
                        <option value="Truck">Truck</option>
                        <option value="Bike">Bike</option>
                        <option value="All">All Vehicles</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>License Expiry</label>
                    <input type="date" name="license_expiry" id="edit_dexpiry" required>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" id="edit_dstatus">
                        <option value="On Duty">On Duty</option>
                        <option value="Off Duty">Off Duty</option>
                        <option value="Suspended">Suspended</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Safety Score</label>
                    <input type="number" name="safety_score" id="edit_dscore" min="0" max="100" step="0.1">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('editDriverModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Driver</button>
            </div>
        </form>
    </div>
</div>

<script>
function editDriver(d) {
    document.getElementById('edit_did').value = d.id;
    document.getElementById('edit_dname').value = d.name;
    document.getElementById('edit_demail').value = d.email || '';
    document.getElementById('edit_dphone').value = d.phone || '';
    document.getElementById('edit_dlicense').value = d.license_number;
    document.getElementById('edit_dcat').value = d.license_category;
    document.getElementById('edit_dexpiry').value = d.license_expiry;
    document.getElementById('edit_dstatus').value = d.status;
    document.getElementById('edit_dscore').value = d.safety_score;
    openModal('editDriverModal');
}
</script>
<?php include '../includes/footer.php'; ?>
