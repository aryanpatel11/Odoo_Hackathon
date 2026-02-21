<?php
require_once '../includes/config.php';
requireLogin();
requireRole(['admin']); // STRICT: Only admins can manage users

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $email = trim($_POST['email']);
        // Check if exists
        $chk = $conn->prepare("SELECT id FROM users WHERE email=?");
        $chk->bind_param("s", $email);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $msg = ['danger', 'A user with this email already exists.'];
        } else {
            $hashed = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?,?,?,?)");
            $stmt->bind_param("ssss", $_POST['name'], $email, $hashed, $_POST['role']);
            if ($stmt->execute()) $msg = ['success', 'User added successfully!'];
            else $msg = ['danger', 'Database error: ' . $stmt->error];
        }
    } elseif ($action === 'edit_role') {
        $uid = intval($_POST['id']);
        if ($uid !== $_SESSION['user_id']) { // Prevent admin from changing their own role easily
            $stmt = $conn->prepare("UPDATE users SET role=? WHERE id=?");
            $stmt->bind_param("si", $_POST['role'], $uid);
            if ($stmt->execute()) $msg = ['success', 'User role updated!'];
            else $msg = ['danger', 'Database error: ' . $stmt->error];
        } else {
            $msg = ['danger', 'You cannot change your own admin role here.'];
        }
    } elseif ($action === 'delete') {
        $uid = intval($_POST['id']);
        if ($uid !== $_SESSION['user_id']) {
            $conn->query("DELETE FROM users WHERE id=$uid");
            $msg = ['success', 'User deleted.'];
        } else {
            $msg = ['danger', 'You cannot delete yourself.'];
        }
    }
}

$users = $conn->query("SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC");

include '../includes/header.php';
?>
<div class="page-header">
    <div>
        <div class="page-title"><i class="fas fa-users-cog"></i> Admin Control Panel</div>
        <div class="page-subtitle">Manage system users, approve registrations, and assign roles.</div>
    </div>
    <button class="btn btn-primary" onclick="openModal('addUserModal')">
        <i class="fas fa-plus"></i> Add User
    </button>
</div>

<?php if ($msg): ?>
<div class="alert alert-<?= $msg[0] ?>"><i class="fas fa-info-circle"></i> <?= $msg[1] ?></div>
<?php endif; ?>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>User</th>
                    <th>Email</th>
                    <th>Role Status</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php $i=1; while($u = $users->fetch_assoc()): 
                    $role_colors = [
                        'admin' => 'purple',
                        'manager' => 'blue',
                        'dispatcher' => 'green',
                        'safety_officer' => 'yellow',
                        'financial_analyst' => 'gray',
                        'pending' => 'red'
                    ];
                    $badge_color = $role_colors[$u['role']] ?? 'gray';
                ?>
                <tr>
                    <td style="color:var(--text-muted)"><?= $i++ ?></td>
                    <td style="font-weight:600"><?= htmlspecialchars($u['name']) ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td>
                        <span class="pill pill-<?= $badge_color ?>" style="text-transform:uppercase; font-size:10px;">
                            <?= htmlspecialchars($u['role']) ?>
                        </span>
                    </td>
                    <td style="font-size:12px;color:var(--text-muted)"><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                    <td>
                        <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                        <div style="display:flex;gap:4px">
                            <button class="action-btn edit" onclick="editRole(<?= $u['id'] ?>, '<?= $u['role'] ?>')" title="Change Role"><i class="fas fa-user-shield"></i></button>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Are you sure you want to delete this user? This cannot be undone.');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                <button type="submit" class="action-btn delete"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                        <?php else: ?>
                            <span style="font-size:11px;color:var(--text-muted)">Master Account</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal-overlay" id="addUserModal">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title">Manually Add User</div>
            <button class="modal-close" onclick="closeModal('addUserModal')">×</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label>Temporary Password</label>
                <input type="password" name="password" required>
            </div>
            <div class="form-group">
                <label>Assign Role</label>
                <select name="role" required>
                    <option value="manager">Manager</option>
                    <option value="dispatcher">Dispatcher</option>
                    <option value="safety_officer">Safety Officer</option>
                    <option value="financial_analyst">Financial Analyst</option>
                </select>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:20px">
                <button type="button" class="btn btn-outline" onclick="closeModal('addUserModal')">Cancel</button>
                <button type="submit" class="btn btn-primary" style="background:#fbbf24;color:#000;">Add User</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Role Modal -->
<div class="modal-overlay" id="editRoleModal">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title">Update User Role</div>
            <button class="modal-close" onclick="closeModal('editRoleModal')">×</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="edit_role">
            <input type="hidden" name="id" id="edit_user_id">
            <div class="form-group">
                <label>Select New Role</label>
                <select name="role" id="edit_user_role" required>
                    <option value="pending">Pending (Block Access)</option>
                    <option value="manager">Manager</option>
                    <option value="dispatcher">Dispatcher</option>
                    <option value="safety_officer">Safety Officer</option>
                    <option value="financial_analyst">Financial Analyst</option>
                    <option value="admin">System Admin</option>
                </select>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:20px">
                <button type="button" class="btn btn-outline" onclick="closeModal('editRoleModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Role</button>
            </div>
        </form>
    </div>
</div>

<script>
function editRole(id, currentRole) {
    document.getElementById('edit_user_id').value = id;
    document.getElementById('edit_user_role').value = currentRole;
    openModal('editRoleModal');
}
</script>

<?php include '../includes/footer.php'; ?>
