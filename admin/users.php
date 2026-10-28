<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

$user = require_login('admin');
$me   = (int) $user['id'];

if (is_post()) {
    verify_csrf();

    $action = post('action');
    $id     = post_int('id');

    if ($id === $me) {
        flash('danger', 'You cannot change your own account here.');
        redirect('admin/users.php');
    }

    $stmt = db()->prepare('SELECT id, name, role FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $target = $stmt->fetch();

    if (!$target) {
        flash('danger', 'User not found.');
        redirect('admin/users.php');
    }
    if ($target['role'] === 'admin') {
        flash('danger', 'Administrator accounts cannot be modified from this screen.');
        redirect('admin/users.php');
    }

    if ($action === 'block') {
        db()->prepare("UPDATE users SET status = 'blocked' WHERE id = ?")->execute([$id]);
        notify($id, 'Your account has been blocked by an administrator.', null, false);
        flash('success', $target['name'] . ' is blocked and can no longer sign in.');
    } elseif ($action === 'unblock') {
        
        $newStatus = $target['role'] === 'doctor' ? 'pending' : 'active';
        db()->prepare('UPDATE users SET status = ? WHERE id = ?')->execute([$newStatus, $id]);
        if ($target['role'] === 'doctor') {
            db()->prepare('UPDATE doctors SET is_verified = 0 WHERE user_id = ?')->execute([$id]);
        }
        flash('success', $target['name'] . ' was unblocked' . ($target['role'] === 'doctor' ? ' and is pending re-verification.' : '.'));
    } elseif ($action === 'delete') {
        
        
        db()->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
        flash('success', $target['name'] . ' and all related records were deleted.');
    }

    redirect('admin/users.php?role=' . urlencode(post('filter_role', 'all')) . '&q=' . urlencode(post('filter_q', '')));
}

$roleFilter = query('role', 'all');
if (!in_array($roleFilter, ['all', 'patient', 'doctor', 'admin'], true)) {
    $roleFilter = 'all';
}
$statusFilter = query('status', 'all');
if (!in_array($statusFilter, ['all', 'active', 'pending', 'blocked'], true)) {
    $statusFilter = 'all';
}
$q = query('q');

$sql = "SELECT u.id, u.name, u.email, u.role, u.status, u.created_at,
               (SELECT COUNT(*) FROM appointments a WHERE a.patient_id = u.id OR a.doctor_id = u.id) AS appts
          FROM users u WHERE 1 = 1";
$params = [];

if ($roleFilter !== 'all') {
    $sql .= ' AND u.role = ?';
    $params[] = $roleFilter;
}
if ($statusFilter !== 'all') {
    $sql .= ' AND u.status = ?';
    $params[] = $statusFilter;
}
if ($q !== '') {
    $sql .= ' AND (u.name LIKE ? OR u.email LIKE ?)';
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
}
$sql .= ' ORDER BY u.created_at DESC LIMIT 200';

$stmt = db()->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$counts = db()->query(
    "SELECT COUNT(*) AS total,
            SUM(role = 'patient') AS patients,
            SUM(role = 'doctor')  AS doctors,
            SUM(status = 'blocked') AS blocked
       FROM users"
)->fetch();

$pageTitle = 'Users';
$pageSub   = 'Block abusive accounts or remove them entirely.';
$layout    = 'dashboard';
$activeNav = 'users';

require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-sm <?= $roleFilter === 'all' ? 'btn-primary' : 'btn-light border' ?>" href="<?= e(url('admin/users.php')) ?>">
            All <span class="badge bg-white text-dark ms-1"><?= (int) $counts['total'] ?></span>
        </a>
        <a class="btn btn-sm <?= $roleFilter === 'patient' ? 'btn-primary' : 'btn-light border' ?>" href="<?= e(url('admin/users.php?role=patient')) ?>">
            Patients <span class="badge bg-white text-dark ms-1"><?= (int) $counts['patients'] ?></span>
        </a>
        <a class="btn btn-sm <?= $roleFilter === 'doctor' ? 'btn-primary' : 'btn-light border' ?>" href="<?= e(url('admin/users.php?role=doctor')) ?>">
            Doctors <span class="badge bg-white text-dark ms-1"><?= (int) $counts['doctors'] ?></span>
        </a>
        <a class="btn btn-sm <?= $roleFilter === 'admin' ? 'btn-primary' : 'btn-light border' ?>" href="<?= e(url('admin/users.php?role=admin')) ?>">
            Admins
        </a>
    </div>

    <form class="d-flex gap-2" method="get" action="<?= e(url('admin/users.php')) ?>">
        <input type="hidden" name="role" value="<?= e($roleFilter) ?>">
        <select class="form-select form-select-sm" name="status" style="width:130px">
            <?php foreach (['all' => 'Any status', 'active' => 'Active', 'pending' => 'Pending', 'blocked' => 'Blocked'] as $val => $label): ?>
                <option value="<?= $val ?>" <?= $statusFilter === $val ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
        </select>
        <input class="form-control form-control-sm" type="search" name="q" value="<?= e($q) ?>"
               placeholder="Name or email" style="width:180px">
        <button class="btn btn-sm btn-outline-secondary" type="submit">Filter</button>
    </form>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Role</th>
                    <th>Joined</th>
                    <th>Bookings</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="avatar avatar-sm"><?= e(initials($u['name'])) ?></span>
                            <div>
                                <div class="fw-semibold text-ink" style="font-size:.85rem">
                                    <?= e($u['name']) ?>
                                    <?php if ((int) $u['id'] === $me): ?>
                                        <span class="text-faint fw-normal" style="font-size:.72rem">(you)</span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-muted" style="font-size:.74rem"><?= e($u['email']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <?php
                        $roleCls = match ($u['role']) {
                            'admin'   => 'bg-dark',
                            'doctor'  => 'bg-primary',
                            default   => 'bg-secondary',
                        };
                        ?>
                        <span class="badge rounded-pill <?= $roleCls ?>"><?= e(ucfirst($u['role'])) ?></span>
                    </td>
                    <td class="text-muted" style="font-size:.8rem"><?= e(fmt_date($u['created_at'])) ?></td>
                    <td style="font-size:.83rem"><?= (int) $u['appts'] ?></td>
                    <td>
                        <?php
                        $stCls = match ($u['status']) {
                            'active'  => 'bg-success',
                            'pending' => 'bg-warning text-dark',
                            default   => 'bg-danger',
                        };
                        ?>
                        <span class="badge rounded-pill <?= $stCls ?>"><?= e(ucfirst($u['status'])) ?></span>
                    </td>
                    <td class="text-end">
                        <?php if ($u['role'] === 'admin' || (int) $u['id'] === $me): ?>
                            <span class="text-faint" style="font-size:.78rem">Protected</span>
                        <?php else: ?>
                            <div class="d-flex gap-2 justify-content-end">
                                <?php if ($u['status'] === 'blocked'): ?>
                                    <form method="post" class="m-0">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="unblock">
                                        <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                        <input type="hidden" name="filter_role" value="<?= e($roleFilter) ?>">
                                        <input type="hidden" name="filter_q" value="<?= e($q) ?>">
                                        <button class="btn btn-sm btn-outline-primary" type="submit">Unblock</button>
                                    </form>
                                <?php else: ?>
                                    <form method="post" class="m-0" data-confirm="Block <?= e($u['name']) ?>? They will be signed out immediately.">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="block">
                                        <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                        <input type="hidden" name="filter_role" value="<?= e($roleFilter) ?>">
                                        <input type="hidden" name="filter_q" value="<?= e($q) ?>">
                                        <button class="btn btn-sm btn-outline-secondary" type="submit">Block</button>
                                    </form>
                                <?php endif; ?>

                                <form method="post" class="m-0"
                                      data-confirm="Delete <?= e($u['name']) ?> permanently? Their appointments, reviews and notifications go too. This cannot be undone.">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                    <input type="hidden" name="filter_role" value="<?= e($roleFilter) ?>">
                                    <input type="hidden" name="filter_q" value="<?= e($q) ?>">
                                    <button class="btn btn-sm btn-outline-danger" type="submit" aria-label="Delete">
                                        <?= icon('trash', 14) ?>
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$users): ?>
                <tr><td colspan="6" class="text-center text-muted py-5" style="font-size:.875rem">No users match this filter.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
