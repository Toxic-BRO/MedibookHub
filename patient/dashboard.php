<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

$user = require_login('patient');
$pid  = (int) $user['id'];


$next = db()->prepare(
    "SELECT a.*, u.name AS doctor_name, d.specialty, d.location, d.fee, d.photo
       FROM appointments a
       JOIN doctors d ON d.user_id = a.doctor_id
       JOIN users u   ON u.id = a.doctor_id
      WHERE a.patient_id = ?
        AND a.status IN ('pending','confirmed')
        AND TIMESTAMP(a.`date`, a.`time`) >= NOW()
      ORDER BY (a.status = 'confirmed') DESC, a.`date`, a.`time`
      LIMIT 1"
);
$next->execute([$pid]);
$nextAppt = $next->fetch() ?: null;


$counts = db()->prepare(
    "SELECT
        SUM(status IN ('pending','confirmed')) AS upcoming,
        SUM(status = 'completed')              AS completed,
        SUM(status = 'cancelled')              AS cancelled
     FROM appointments WHERE patient_id = ?"
);
$counts->execute([$pid]);
$c = $counts->fetch() ?: ['upcoming' => 0, 'completed' => 0, 'cancelled' => 0];

$rxCount = db()->prepare(
    "SELECT COUNT(*) FROM appointments
      WHERE patient_id = ? AND status = 'completed' AND prescription IS NOT NULL AND prescription <> ''"
);
$rxCount->execute([$pid]);
$prescriptions = (int) $rxCount->fetchColumn();


$toReview = db()->prepare(
    "SELECT a.id, a.`date`, u.name AS doctor_name, d.specialty
       FROM appointments a
       JOIN users u   ON u.id = a.doctor_id
       JOIN doctors d ON d.user_id = a.doctor_id
       LEFT JOIN reviews r ON r.appointment_id = a.id
      WHERE a.patient_id = ? AND a.status = 'completed' AND r.id IS NULL
      ORDER BY a.`date` DESC LIMIT 3"
);
$toReview->execute([$pid]);
$pendingReviews = $toReview->fetchAll();


$recent = db()->prepare(
    "SELECT a.*, u.name AS doctor_name, d.specialty, r.rating
       FROM appointments a
       JOIN users u   ON u.id = a.doctor_id
       JOIN doctors d ON d.user_id = a.doctor_id
       LEFT JOIN reviews r ON r.appointment_id = a.id
      WHERE a.patient_id = ?
      ORDER BY a.`date` DESC, a.`time` DESC LIMIT 5"
);
$recent->execute([$pid]);
$recentAppts = $recent->fetchAll();

$pageTitle  = 'Hi, ' . explode(' ', $user['name'])[0];
$pageSub    = 'Here is what is coming up on your schedule.';
$layout     = 'dashboard';
$activeNav  = 'dashboard';
$pageActions = '<a class="btn btn-soft btn-sm" href="' . e(url('patient/symptom-checker.php')) . '">'
    . icon('stetho', 16) . ' AI Health Assistant</a>'
    . '<a class="btn btn-primary btn-sm" href="' . e(url('patient/doctors.php')) . '">'
    . icon('plus', 16) . ' Book appointment</a>';

require __DIR__ . '/../includes/header.php';
?>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat">
            <div class="stat-ico"><?= icon('calendar', 18) ?></div>
            <div class="stat-value"><?= (int) $c['upcoming'] ?></div>
            <div class="stat-label">Upcoming</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat">
            <div class="stat-ico"><?= icon('check', 18) ?></div>
            <div class="stat-value"><?= (int) $c['completed'] ?></div>
            <div class="stat-label">Visits completed</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat">
            <div class="stat-ico"><?= icon('file', 18) ?></div>
            <div class="stat-value"><?= $prescriptions ?></div>
            <div class="stat-label">Prescriptions</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat">
            <div class="stat-ico"><?= icon('x', 18) ?></div>
            <div class="stat-value"><?= (int) $c['cancelled'] ?></div>
            <div class="stat-label">Cancelled</div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">

        <!-- AI Health Assistant banner -->
        <div class="card mb-4 ai-banner-card">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <div class="ai-banner-icon">
                        <?= icon('stetho', 26) ?>
                    </div>
                    <div class="flex-grow-1">
                        <h2 class="h5 mb-1">
                            Not sure which doctor to see?
                            <span class="badge rounded-pill bg-success" style="font-size:.65rem;vertical-align:middle">AI</span>
                        </h2>
                        <p class="text-muted mb-0" style="font-size:.86rem">
                            Describe your symptoms and the AI assistant will recommend the right specialist instantly.
                        </p>
                    </div>
                    <a class="btn btn-primary btn-sm" href="<?= e(url('patient/symptom-checker.php')) ?>">
                        Try AI Assistant <?= icon('arrow', 16) ?>
                    </a>
                </div>
            </div>
        </div>

        <?php if ($nextAppt): ?>
            <div class="card mb-4" style="border-color:var(--mb-primary-line)">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Next appointment</span>
                    <?= status_badge($nextAppt['status']) ?>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <?= doctor_avatar($nextAppt, 'avatar-lg') ?>
                        <div class="flex-grow-1">
                            <h2 class="h5 mb-1"><?= e($nextAppt['doctor_name']) ?></h2>
                            <div class="text-muted mb-2" style="font-size:.875rem">
                                <?= e($nextAppt['specialty']) ?> · <?= e($nextAppt['location']) ?>
                            </div>
                            <div class="d-flex flex-wrap gap-3">
                                <span class="meta-row"><?= icon('calendar', 15) ?> <?= e(fmt_date($nextAppt['date'])) ?></span>
                                <span class="meta-row"><?= icon('clock', 15) ?> <?= e(fmt_time($nextAppt['time'])) ?></span>
                                <span class="meta-row"><?= icon('card', 15) ?> <?= e(money($nextAppt['fee'])) ?></span>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($nextAppt['reason'])): ?>
                        <div class="rounded-3 p-3 mb-3" style="background:#f8fafb;border:1px solid var(--mb-border)">
                            <div class="small-caps mb-1">Reason for visit</div>
                            <div style="font-size:.875rem"><?= nl2br(e($nextAppt['reason'])) ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if ($nextAppt['status'] === 'pending'): ?>
                        <div class="alert alert-warning py-2 mb-3 d-flex gap-2 align-items-start" style="font-size:.84rem">
                            <?= icon('clock', 16) ?>
                            <span>Waiting for the doctor to confirm. You will get a notification either way.</span>
                        </div>
                    <?php endif; ?>

                    <div class="d-flex gap-2">
                        <a class="btn btn-sm btn-outline-primary" href="<?= e(url('patient/appointments.php')) ?>">Manage</a>
                        <a class="btn btn-sm btn-soft" href="<?= e(url('patient/doctor.php?id=' . (int) $nextAppt['doctor_id'] . '&reschedule=' . (int) $nextAppt['id'])) ?>">
                            <?= icon('refresh', 15) ?> Reschedule
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="card mb-4">
                <div class="empty-state">
                    <div class="ico"><?= icon('calendar', 26) ?></div>
                    <h2 class="h6">No upcoming appointments</h2>
                    <p class="mb-3" style="font-size:.875rem">Find a doctor and grab a slot that suits you.</p>
                    <a class="btn btn-primary btn-sm" href="<?= e(url('patient/doctors.php')) ?>">Find a doctor</a>
                </div>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Recent activity</span>
                <a class="small fw-semibold" href="<?= e(url('patient/appointments.php')) ?>">View all</a>
            </div>
            <?php if (!$recentAppts): ?>
                <div class="empty-state py-4">
                    <p class="mb-0" style="font-size:.875rem">Nothing yet — your bookings will show up here.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr><th>Doctor</th><th>Date</th><th>Status</th><th class="text-end">Review</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($recentAppts as $a): ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold text-ink" style="font-size:.875rem"><?= e($a['doctor_name']) ?></div>
                                    <div class="text-muted" style="font-size:.76rem"><?= e($a['specialty']) ?></div>
                                </td>
                                <td style="font-size:.82rem">
                                    <?= e(fmt_date($a['date'])) ?><br>
                                    <span class="text-muted"><?= e(fmt_time($a['time'])) ?></span>
                                </td>
                                <td><?= status_badge($a['status']) ?></td>
                                <td class="text-end">
                                    <?php if ($a['status'] === 'completed' && $a['rating'] === null): ?>
                                        <a class="btn btn-sm btn-soft" href="<?= e(url('patient/review.php?id=' . (int) $a['id'])) ?>">Rate</a>
                                    <?php elseif ($a['rating'] !== null): ?>
                                        <span class="text-warning" style="font-size:.85rem"><?= str_repeat('★', (int) $a['rating']) ?></span>
                                    <?php else: ?>
                                        <span class="text-faint">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-5">
        <?php if ($pendingReviews): ?>
            <div class="card mb-3" style="border-color:#fde68a;background:#fffbeb">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <?= icon('star', 18) ?>
                        <strong class="text-ink" style="font-size:.9rem">How did it go?</strong>
                    </div>
                    <p class="text-muted mb-3" style="font-size:.82rem">
                        You have <?= count($pendingReviews) ?> completed visit<?= count($pendingReviews) === 1 ? '' : 's' ?> without a review.
                    </p>
                    <?php foreach ($pendingReviews as $r): ?>
                        <div class="d-flex justify-content-between align-items-center py-2 border-top">
                            <div style="font-size:.83rem">
                                <div class="fw-semibold text-ink"><?= e($r['doctor_name']) ?></div>
                                <div class="text-muted" style="font-size:.76rem"><?= e(fmt_date($r['date'])) ?></div>
                            </div>
                            <a class="btn btn-sm btn-outline-primary" href="<?= e(url('patient/review.php?id=' . (int) $r['id'])) ?>">Rate</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">Quick links</div>
            <div class="card-body d-flex flex-column gap-2">
                <a class="side-link" href="<?= e(url('patient/symptom-checker.php')) ?>" style="background:linear-gradient(100deg,#e5f3ef,#f0f8f6);color:#075f5c;font-weight:700">
                    <?= icon('stetho', 18) ?> <span>AI Health Assistant</span>
                    <span class="badge rounded-pill bg-success ms-auto">AI</span>
                </a>
                <a class="side-link" href="<?= e(url('patient/doctors.php')) ?>"><?= icon('search', 18) ?> <span>Find a doctor</span></a>
                <a class="side-link" href="<?= e(url('patient/prescriptions.php')) ?>"><?= icon('file', 18) ?> <span>My prescriptions</span></a>
                <a class="side-link" href="<?= e(url('patient/complaints.php')) ?>"><?= icon('chat', 18) ?> <span>File a complaint</span></a>
                <a class="side-link" href="<?= e(url('patient/profile.php')) ?>"><?= icon('user', 18) ?> <span>Update my details</span></a>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
