<?php


declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

$user = require_login('doctor');
$did  = (int) $user['id'];

$doc = doctor_detail($did);
$verified = $doc && (int) $doc['is_verified'] === 1;


$today = db()->prepare(
    "SELECT a.*, u.name AS patient_name, p.phone, p.age, p.gender
       FROM appointments a
       JOIN users u    ON u.id = a.patient_id
       LEFT JOIN patients p ON p.user_id = a.patient_id
      WHERE a.doctor_id = ? AND a.`date` = CURDATE() AND a.status IN ('confirmed','completed')
      ORDER BY a.`time`"
);
$today->execute([$did]);
$todayList = $today->fetchAll();


$pending = db()->prepare(
    "SELECT a.*, u.name AS patient_name, p.age, p.gender, p.phone
       FROM appointments a
       JOIN users u    ON u.id = a.patient_id
       LEFT JOIN patients p ON p.user_id = a.patient_id
      WHERE a.doctor_id = ? AND a.status = 'pending'
      ORDER BY a.`date`, a.`time` LIMIT 5"
);
$pending->execute([$did]);
$pendingList = $pending->fetchAll();


$stats = db()->prepare(
    "SELECT
        SUM(status = 'pending')   AS pending,
        SUM(status = 'confirmed' AND `date` >= CURDATE()) AS upcoming,
        SUM(status = 'completed') AS completed,
        COUNT(DISTINCT patient_id) AS patients
     FROM appointments WHERE doctor_id = ?"
);
$stats->execute([$did]);
$s = $stats->fetch() ?: [];

$rating = doctor_rating($did);

$pageTitle = 'Dashboard';
$pageSub   = $verified
    ? 'Welcome back, ' . preg_replace('/^Dr\.?\s*/i', '', $user['name']) . '. Here is your day at a glance.'
    : 'Your account is waiting for admin verification.';
$layout    = 'dashboard';
$activeNav = 'dashboard';

require __DIR__ . '/../includes/header.php';
?>

<?php if (!$verified): ?>
    <div class="alert alert-warning d-flex gap-3 align-items-start">
        <div class="flex-none"><?= icon('shield', 20) ?></div>
        <div>
            <strong class="d-block">Your account is pending verification</strong>
            <span style="font-size:.875rem">
                Patients cannot see your profile or book you until an administrator approves it.
                You can still set up your weekly availability and profile now, so you are ready the moment you go live.
            </span>
            <div class="mt-2">
                <a class="btn btn-sm btn-outline-primary" href="<?= e(url('doctor/availability.php')) ?>">Set availability</a>
                <a class="btn btn-sm btn-outline-primary" href="<?= e(url('doctor/profile.php')) ?>">Complete profile</a>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat">
            <div class="stat-ico"><?= icon('clock', 18) ?></div>
            <div class="stat-value"><?= (int) ($s['pending'] ?? 0) ?></div>
            <div class="stat-label">Awaiting your answer</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat">
            <div class="stat-ico"><?= icon('calendar', 18) ?></div>
            <div class="stat-value"><?= (int) ($s['upcoming'] ?? 0) ?></div>
            <div class="stat-label">Upcoming</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat">
            <div class="stat-ico"><?= icon('users', 18) ?></div>
            <div class="stat-value"><?= (int) ($s['patients'] ?? 0) ?></div>
            <div class="stat-label">Patients seen</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat">
            <div class="stat-ico"><?= icon('star', 18) ?></div>
            <div class="stat-value"><?= $rating['total'] > 0 ? number_format($rating['avg'], 1) : '—' ?></div>
            <div class="stat-label"><?= $rating['total'] ?> review<?= $rating['total'] === 1 ? '' : 's' ?></div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Today · <?= e(fmt_date(date('Y-m-d'))) ?></span>
                <span class="badge rounded-pill bg-light text-dark border"><?= count($todayList) ?> booked</span>
            </div>

            <?php if (!$todayList): ?>
                <div class="empty-state py-4">
                    <div class="ico"><?= icon('calendar', 24) ?></div>
                    <p class="mb-0" style="font-size:.875rem">Nothing on the books today.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead><tr><th>Time</th><th>Patient</th><th>Reason</th><th class="text-end">Status</th></tr></thead>
                        <tbody>
                        <?php foreach ($todayList as $a): ?>
                            <tr>
                                <td class="fw-semibold text-ink" style="font-size:.85rem"><?= e(fmt_time($a['time'])) ?></td>
                                <td>
                                    <div style="font-size:.85rem"><?= e($a['patient_name']) ?></div>
                                    <div class="text-muted" style="font-size:.74rem">
                                        <?php if ($a['age']): ?><?= (int) $a['age'] ?> yrs<?php endif; ?>
                                        <?php if ($a['gender']): ?> · <?= e($a['gender']) ?><?php endif; ?>
                                    </div>
                                </td>
                                <td class="text-muted" style="font-size:.8rem;max-width:220px">
                                    <?= e($a['reason'] ?: '—') ?>
                                </td>
                                <td class="text-end"><?= status_badge($a['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Booking requests</span>
                <a class="small fw-semibold" href="<?= e(url('doctor/appointments.php?status=pending')) ?>">Manage all</a>
            </div>

            <?php if (!$pendingList): ?>
                <div class="empty-state py-4">
                    <div class="ico"><?= icon('check', 24) ?></div>
                    <p class="mb-0" style="font-size:.875rem">No requests waiting. All caught up.</p>
                </div>
            <?php else: ?>
                <div class="card-body d-flex flex-column gap-3">
                    <?php foreach ($pendingList as $a): ?>
                        <div class="d-flex flex-wrap align-items-center gap-3 pb-3 border-bottom">
                            <div class="avatar avatar-sm"><?= e(initials($a['patient_name'])) ?></div>
                            <div class="flex-grow-1 min-w-0">
                                <div class="fw-semibold text-ink" style="font-size:.875rem"><?= e($a['patient_name']) ?></div>
                                <div class="text-muted" style="font-size:.78rem">
                                    <?= e(fmt_date($a['date'])) ?> · <?= e(fmt_time($a['time'])) ?>
                                    <?php if ($a['reason']): ?> · <?= e(mb_strimwidth($a['reason'], 0, 60, '…')) ?><?php endif; ?>
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <form method="post" action="<?= e(url('doctor/appointments.php')) ?>" class="m-0">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="accept">
                                    <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                                    <button class="btn btn-sm btn-primary" type="submit">Accept</button>
                                </form>
                                <form method="post" action="<?= e(url('doctor/appointments.php')) ?>" class="m-0"
                                      data-confirm="Reject this request? The patient is notified and the slot is released.">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="reject">
                                    <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Reject</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card mb-3">
            <div class="card-header">Your practice</div>
            <div class="card-body" style="font-size:.85rem">
                <div class="d-flex justify-content-between py-1">
                    <span class="text-muted">Specialty</span>
                    <span class="fw-semibold text-ink"><?= e($doc['specialty'] ?? '—') ?></span>
                </div>
                <div class="d-flex justify-content-between py-1">
                    <span class="text-muted">Consultation fee</span>
                    <span class="fw-semibold text-ink"><?= e(money($doc['fee'] ?? 0)) ?></span>
                </div>
                <div class="d-flex justify-content-between py-1">
                    <span class="text-muted">Location</span>
                    <span class="fw-semibold text-ink text-end"><?= e($doc['location'] ?? '—') ?></span>
                </div>
                <div class="d-flex justify-content-between py-1">
                    <span class="text-muted">Status</span>
                    <?php if ($verified): ?>
                        <span class="badge rounded-pill bg-success">Verified</span>
                    <?php else: ?>
                        <span class="badge rounded-pill bg-warning text-dark">Pending</span>
                    <?php endif; ?>
                </div>
                <a class="btn btn-sm btn-outline-primary w-100 mt-3" href="<?= e(url('doctor/profile.php')) ?>">
                    <?= icon('pencil', 14) ?> Edit profile
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Weekly availability</span>
                <a class="small fw-semibold" href="<?= e(url('doctor/availability.php')) ?>">Edit</a>
            </div>
            <div class="card-body">
                <?php $week = weekly_availability($did); ?>
                <?php if (!$week): ?>
                    <p class="text-muted mb-2" style="font-size:.85rem">
                        No hours set yet — patients will not see any slots.
                    </p>
                    <a class="btn btn-sm btn-primary" href="<?= e(url('doctor/availability.php')) ?>">Add hours</a>
                <?php else: ?>
                    <?php for ($d = 0; $d <= 6; $d++): ?>
                        <div class="d-flex justify-content-between py-1 border-bottom" style="font-size:.82rem">
                            <span class="text-muted"><?= e(day_name($d)) ?></span>
                            <span class="fw-semibold text-ink text-end">
                                <?php if (empty($week[$d])): ?>
                                    <span class="text-faint fw-normal">Closed</span>
                                <?php else: ?>
                                    <?php
                                    $parts = [];
                                    foreach ($week[$d] as $w) {
                                        $parts[] = fmt_time($w['start_time']) . '–' . fmt_time($w['end_time']);
                                    }
                                    echo e(implode(', ', $parts));
                                    ?>
                                <?php endif; ?>
                            </span>
                        </div>
                    <?php endfor; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
