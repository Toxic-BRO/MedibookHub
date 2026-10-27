<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

$user = require_login('admin');

if (is_post()) {
    verify_csrf();

    $action = post('action');
    $id     = post_int('id');

    $stmt = db()->prepare(
        "SELECT u.id, u.name, u.status, d.is_verified
           FROM users u JOIN doctors d ON d.user_id = u.id
          WHERE u.id = ? AND u.role = 'doctor'"
    );
    $stmt->execute([$id]);
    $doc = $stmt->fetch();

    if (!$doc) {
        flash('danger', 'That doctor could not be found.');
        redirect('admin/doctors.php');
    }

    if ($action === 'verify') {
        db()->prepare("UPDATE users SET status = 'active' WHERE id = ?")->execute([$id]);
        db()->prepare('UPDATE doctors SET is_verified = 1 WHERE user_id = ?')->execute([$id]);
        notify($id, 'Your doctor account has been verified. Patients can now find and book you.', '/doctor/dashboard.php');
        flash('success', $doc['name'] . ' is verified and live.');
    } elseif ($action === 'reject') {
        db()->prepare("UPDATE users SET status = 'blocked' WHERE id = ?")->execute([$id]);
        db()->prepare('UPDATE doctors SET is_verified = 0 WHERE user_id = ?')->execute([$id]);
        notify($id, 'Your doctor application was declined. Contact the administrator for details.', null);
        flash('success', $doc['name'] . "'s application was rejected and the account blocked.");
    } elseif ($action === 'unverify') {
        db()->prepare('UPDATE doctors SET is_verified = 0 WHERE user_id = ?')->execute([$id]);
        notify($id, 'Your profile has been hidden from patients pending a re-check by the administrator.', '/doctor/dashboard.php');
        flash('success', $doc['name'] . ' is no longer listed publicly.');
    }

    redirect('admin/doctors.php?filter=' . urlencode(post('filter', 'all')));
}

$filter = query('filter', 'all');
if (!in_array($filter, ['all', 'pending', 'verified', 'blocked'], true)) {
    $filter = 'all';
}
$q = query('q');

$sql = "SELECT u.id, u.name, u.email, u.status, u.created_at,
               d.specialty, d.fee, d.experience, d.location, d.photo, d.is_verified,
               (SELECT COUNT(*) FROM appointments a WHERE a.doctor_id = u.id) AS appts
          FROM users u
          JOIN doctors d ON d.user_id = u.id
         WHERE 1 = 1";
$params = [];

if ($filter === 'pending') {
    $sql .= " AND u.status = 'pending'";
} elseif ($filter === 'verified') {
    $sql .= ' AND d.is_verified = 1';
} elseif ($filter === 'blocked') {
    $sql .= " AND u.status = 'blocked'";
}
if ($q !== '') {
    $sql .= ' AND (u.name LIKE ? OR u.email LIKE ? OR d.specialty LIKE ?)';
    $params = ['%' . $q . '%', '%' . $q . '%', '%' . $q . '%'];
}
$sql .= " ORDER BY (u.status = 'pending') DESC, u.created_at DESC";

$stmt = db()->prepare($sql);
$stmt->execute($params);
$doctors = $stmt->fetchAll();

$counts = db()->query(
    "SELECT
        COUNT(*) AS all_docs,
        SUM(u.status = 'pending') AS pending,
        SUM(d.is_verified = 1)    AS verified,
        SUM(u.status = 'blocked') AS blocked
     FROM users u JOIN doctors d ON d.user_id = u.id"
)->fetch();

$pageTitle = 'Doctors';
$pageSub   = 'Approve applications so they become visible to patients.';
$layout    = 'dashboard';
$activeNav = 'doctors';

require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <ul class="nav nav-pills gap-2" style="font-size:.875rem">
        <?php
        $tabs = [
            'all'      => ['All', (int) $counts['all_docs']],
            'pending'  => ['Pending', (int) $counts['pending']],
            'verified' => ['Verified', (int) $counts['verified']],
            'blocked'  => ['Blocked', (int) $counts['blocked']],
        ];
        foreach ($tabs as $key => [$label, $n]): ?>
            <li class="nav-item">
                <a class="nav-link <?= $filter === $key ? 'active text-white' : 'text-muted' ?>"
                   style="<?= $filter === $key ? 'background:var(--mb-primary)' : '' ?>"
                   href="<?= e(url('admin/doctors.php?filter=' . $key)) ?>">
                    <?= e($label) ?> <span class="badge bg-light text-dark ms-1"><?= $n ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>

    <form class="d-flex gap-2" method="get" action="<?= e(url('admin/doctors.php')) ?>">
        <input type="hidden" name="filter" value="<?= e($filter) ?>">
        <input class="form-control form-control-sm" type="search" name="q" value="<?= e($q) ?>"
               placeholder="Name, email, specialty" style="width:200px">
        <button class="btn btn-sm btn-outline-secondary" type="submit">Search</button>
    </form>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Doctor</th>
                    <th>Specialty</th>
                    <th>Fee</th>
                    <th>Appts</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($doctors as $d): ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <?= doctor_avatar($d, 'avatar-sm') ?>
                            <div>
                                <div class="fw-semibold text-ink" style="font-size:.85rem"><?= e($d['name']) ?></div>
                                <div class="text-muted" style="font-size:.74rem"><?= e($d['email']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div style="font-size:.83rem"><?= e($d['specialty']) ?></div>
                        <div class="text-muted" style="font-size:.74rem"><?= e($d['location']) ?></div>
                    </td>
                    <td style="font-size:.83rem"><?= e(money($d['fee'])) ?></td>
                    <td style="font-size:.83rem"><?= (int) $d['appts'] ?></td>
                    <td>
                        <?php if ($d['status'] === 'blocked'): ?>
                            <span class="badge rounded-pill bg-danger">Blocked</span>
                        <?php elseif ((int) $d['is_verified'] === 1): ?>
                            <span class="badge rounded-pill bg-success">Verified</span>
                        <?php else: ?>
                            <span class="badge rounded-pill bg-warning text-dark">Pending</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end">
                        <div class="d-flex gap-2 justify-content-end">
                            <?php if ($d['status'] === 'pending'): ?>
                                <form method="post" class="m-0">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="verify">
                                    <input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
                                    <input type="hidden" name="filter" value="<?= e($filter) ?>">
                                    <button class="btn btn-sm btn-primary" type="submit">Verify</button>
                                </form>
                                <form method="post" class="m-0" data-confirm="Reject and block this account?">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="reject">
                                    <input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
                                    <input type="hidden" name="filter" value="<?= e($filter) ?>">
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Reject</button>
                                </form>
                            <?php elseif ((int) $d['is_verified'] === 1): ?>
                                <form method="post" class="m-0" data-confirm="Hide this doctor from patients?">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="unverify">
                                    <input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
                                    <input type="hidden" name="filter" value="<?= e($filter) ?>">
                                    <button class="btn btn-sm btn-outline-secondary" type="submit">Unverify</button>
                                </form>
                            <?php elseif ($d['status'] === 'blocked'): ?>
                                <form method="post" class="m-0">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="verify">
                                    <input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
                                    <input type="hidden" name="filter" value="<?= e($filter) ?>">
                                    <button class="btn btn-sm btn-outline-primary" type="submit">Re-verify</button>
                                </form>
                            <?php else: ?>
                                <span class="text-faint" style="font-size:.78rem">—</span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$doctors): ?>
                <tr><td colspan="6" class="text-center text-muted py-5" style="font-size:.875rem">No doctors match this filter.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
