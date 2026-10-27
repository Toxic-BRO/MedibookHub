<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

$user = require_login('admin');

$kpi = db()->query(
    "SELECT
        (SELECT COUNT(*) FROM users WHERE role = 'patient' AND status <> 'blocked') AS patients,
        (SELECT COUNT(*) FROM doctors WHERE is_verified = 1)                        AS doctors,
        (SELECT COUNT(*) FROM users WHERE role = 'doctor' AND status = 'pending')   AS pending_doctors,
        (SELECT COUNT(*) FROM appointments)                                        AS appts,
        (SELECT COUNT(*) FROM appointments WHERE `date` = CURDATE())               AS today,
        (SELECT COUNT(*) FROM appointments WHERE status = 'pending')               AS pending_appts,
        (SELECT COUNT(*) FROM complaints WHERE status = 'open')                    AS open_complaints,
        (SELECT COALESCE(SUM(d.fee), 0) FROM appointments a JOIN doctors d ON d.user_id = a.doctor_id
          WHERE a.status = 'completed')                                            AS revenue"
)->fetch();

$pending = db()->query(
    "SELECT u.id, u.name, u.email, u.created_at, d.specialty, d.fee, d.experience, d.location, d.bio
       FROM users u JOIN doctors d ON d.user_id = u.id
      WHERE u.role = 'doctor' AND u.status = 'pending'
      ORDER BY u.created_at ASC LIMIT 5"
)->fetchAll();

$recent = db()->query(
    "SELECT a.id, a.`date`, a.`time`, a.status, u.name AS patient_name, du.name AS doctor_name
       FROM appointments a
       JOIN users u  ON u.id = a.patient_id
       JOIN users du ON du.id = a.doctor_id
      ORDER BY a.created_at DESC LIMIT 6"
)->fetchAll();

$topDocs = db()->query(
    "SELECT u.id, u.name, d.specialty,
            ROUND(AVG(r.rating), 1) AS avg_rating, COUNT(r.id) AS reviews
       FROM reviews r
       JOIN appointments a ON a.id = r.appointment_id
       JOIN users u ON u.id = a.doctor_id
       JOIN doctors d ON d.user_id = a.doctor_id
      GROUP BY u.id, u.name, d.specialty
      ORDER BY avg_rating DESC, reviews DESC
      LIMIT 5"
)->fetchAll();

$pageTitle = 'Admin overview';
$pageSub   = 'Everything happening across ' . APP_NAME . '.';
$layout    = 'dashboard';
$activeNav = 'dashboard';

require __DIR__ . '/../includes/header.php';
?>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat">
            <div class="stat-ico"><?= icon('users', 18) ?></div>
            <div class="stat-value"><?= (int) $kpi['patients'] ?></div>
            <div class="stat-label">Active patients</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat">
            <div class="stat-ico"><?= icon('stetho', 18) ?></div>
            <div class="stat-value"><?= (int) $kpi['doctors'] ?></div>
            <div class="stat-label">Verified doctors</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat">
            <div class="stat-ico"><?= icon('calendar', 18) ?></div>
            <div class="stat-value"><?= (int) $kpi['appts'] ?></div>
            <div class="stat-label">Appointments total</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat">
            <div class="stat-ico"><?= icon('card', 18) ?></div>
            <div class="stat-value"><?= e(money((float) $kpi['revenue'])) ?></div>
            <div class="stat-label">Completed visit value</div>
        </div>
    </div>
</div>

<?php if ((int) $kpi['pending_doctors'] > 0): ?>
    <div class="alert alert-warning d-flex align-items-center gap-2">
        <?= icon('shield', 18) ?>
        <span style="font-size:.875rem">
            <strong><?= (int) $kpi['pending_doctors'] ?> doctor application<?= (int) $kpi['pending_doctors'] === 1 ? '' : 's' ?></strong>
            waiting for verification — they are invisible to patients until approved.
        </span>
        <a class="btn btn-sm btn-warning ms-auto" href="<?= e(url('admin/doctors.php')) ?>">Review</a>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Doctor applications</span>
                <a class="small fw-semibold" href="<?= e(url('admin/doctors.php')) ?>">All doctors</a>
            </div>

            <?php if (!$pending): ?>
                <div class="empty-state py-4">
                    <div class="ico"><?= icon('check', 24) ?></div>
                    <p class="mb-0" style="font-size:.875rem">No applications waiting. The queue is clear.</p>
                </div>
            <?php else: ?>
                <div class="card-body d-flex flex-column gap-3">
                    <?php foreach ($pending as $p): ?>
                        <div class="d-flex flex-wrap align-items-start gap-3 pb-3 border-bottom">
                            <div class="avatar"><?= e(initials($p['name'])) ?></div>
                            <div class="flex-grow-1 min-w-0">
                                <div class="fw-bold text-ink" style="font-size:.9rem"><?= e($p['name']) ?></div>
                                <div class="text-muted" style="font-size:.78rem">
                                    <?= e($p['specialty']) ?> · <?= e($p['location']) ?> ·
                                    <?= e(money($p['fee'])) ?> · <?= (int) $p['experience'] ?> yrs
                                </div>
                                <div class="text-faint" style="font-size:.74rem">Applied <?= e(time_ago($p['created_at'])) ?></div>
                            </div>
                            <div class="d-flex gap-2">
                                <form method="post" action="<?= e(url('admin/doctors.php')) ?>" class="m-0">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="verify">
                                    <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                                    <button class="btn btn-sm btn-primary" type="submit">Verify</button>
                                </form>
                                <form method="post" action="<?= e(url('admin/doctors.php')) ?>" class="m-0"
                                      data-confirm="Reject this application? The account is blocked.">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="reject">
                                    <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Reject</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <div class="card-header">Latest bookings</div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Patient</th><th>Doctor</th><th>When</th><th class="text-end">Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($recent as $r): ?>
                        <tr>
                            <td style="font-size:.85rem"><?= e($r['patient_name']) ?></td>
                            <td style="font-size:.85rem"><?= e($r['doctor_name']) ?></td>
                            <td style="font-size:.8rem" class="text-muted">
                                <?= e(fmt_date($r['date'])) ?><br><?= e(fmt_time($r['time'])) ?>
                            </td>
                            <td class="text-end"><?= status_badge($r['status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$recent): ?>
                        <tr><td colspan="4" class="text-center text-muted py-4" style="font-size:.85rem">No bookings yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card mb-3">
            <div class="card-header">Needs attention</div>
            <div class="card-body d-flex flex-column gap-2">
                <a class="d-flex justify-content-between align-items-center p-2 rounded text-reset"
                   style="background:#fffbeb;border:1px solid #fde68a" href="<?= e(url('admin/complaints.php')) ?>">
                    <span style="font-size:.85rem"><?= icon('chat', 16) ?> Open complaints</span>
                    <span class="badge bg-dark rounded-pill"><?= (int) $kpi['open_complaints'] ?></span>
                </a>
                <a class="d-flex justify-content-between align-items-center p-2 rounded text-reset"
                   style="background:#f8fafb;border:1px solid var(--mb-border)" href="<?= e(url('admin/doctors.php')) ?>">
                    <span style="font-size:.85rem"><?= icon('shield', 16) ?> Pending doctors</span>
                    <span class="badge bg-dark rounded-pill"><?= (int) $kpi['pending_doctors'] ?></span>
                </a>
                <a class="d-flex justify-content-between align-items-center p-2 rounded text-reset"
                   style="background:#f8fafb;border:1px solid var(--mb-border)" href="<?= e(url('admin/reports.php')) ?>">
                    <span style="font-size:.85rem"><?= icon('clock', 16) ?> Awaiting confirmation</span>
                    <span class="badge bg-dark rounded-pill"><?= (int) $kpi['pending_appts'] ?></span>
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Top rated doctors</span>
                <a class="small fw-semibold" href="<?= e(url('admin/reports.php')) ?>">Reports</a>
            </div>
            <div class="card-body">
                <?php if (!$topDocs): ?>
                    <p class="text-muted mb-0" style="font-size:.85rem">No reviews yet.</p>
                <?php else: ?>
                    <?php foreach ($topDocs as $i => $t): ?>
                        <div class="d-flex align-items-center gap-2 py-2 <?= $i > 0 ? 'border-top' : '' ?>">
                            <span class="text-faint fw-bold" style="width:16px;font-size:.8rem"><?= $i + 1 ?></span>
                            <div class="flex-grow-1 min-w-0">
                                <div class="fw-semibold text-ink" style="font-size:.85rem"><?= e($t['name']) ?></div>
                                <div class="text-muted" style="font-size:.74rem"><?= e($t['specialty']) ?></div>
                            </div>
                            <div class="text-end">
                                <div class="text-warning" style="font-size:.8rem">★ <?= number_format((float) $t['avg_rating'], 1) ?></div>
                                <div class="text-faint" style="font-size:.7rem"><?= (int) $t['reviews'] ?> review<?= (int) $t['reviews'] === 1 ? '' : 's' ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
