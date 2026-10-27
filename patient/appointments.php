<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

$user = require_login('patient');
$pid  = (int) $user['id'];


if (is_post() && post('action') === 'cancel') {
    verify_csrf();

    $id = post_int('id');

    $stmt = db()->prepare(
        "SELECT a.*, u.name AS doctor_name
           FROM appointments a JOIN users u ON u.id = a.doctor_id
          WHERE a.id = ? AND a.patient_id = ? AND a.status IN ('pending','confirmed')"
    );
    $stmt->execute([$id, $pid]);
    $appt = $stmt->fetch();

    if (!$appt) {
        flash('danger', 'That appointment cannot be cancelled.');
    } elseif (strtotime($appt['date'] . ' ' . $appt['time']) < time()) {
        flash('danger', 'That appointment is already in the past.');
    } else {
        db()->prepare("UPDATE appointments SET status = 'cancelled' WHERE id = ?")->execute([$id]);
        notify(
            (int) $appt['doctor_id'],
            $user['name'] . ' cancelled the appointment on ' . fmt_date($appt['date']) . ' at ' . fmt_time($appt['time']) . '.',
            '/doctor/appointments.php'
        );
        flash('success', 'Appointment cancelled. The slot is free for other patients again.');
    }

    redirect('patient/appointments.php');
}

$tab = query('tab', 'upcoming');
if (!in_array($tab, ['upcoming', 'past', 'all'], true)) {
    $tab = 'upcoming';
}

$sql = "SELECT a.*, u.name AS doctor_name, d.specialty, d.location, d.photo, r.rating, r.id AS review_id
          FROM appointments a
          JOIN users u   ON u.id = a.doctor_id
          JOIN doctors d ON d.user_id = a.doctor_id
          LEFT JOIN reviews r ON r.appointment_id = a.id
         WHERE a.patient_id = ?";
$params = [$pid];

if ($tab === 'upcoming') {
    $sql .= " AND a.status IN ('pending','confirmed') AND TIMESTAMP(a.`date`, a.`time`) >= NOW()";
    $sql .= ' ORDER BY a.`date`, a.`time`';
} elseif ($tab === 'past') {
    $sql .= " AND (a.status IN ('completed','rejected','cancelled') OR TIMESTAMP(a.`date`, a.`time`) < NOW())";
    $sql .= ' ORDER BY a.`date` DESC, a.`time` DESC';
} else {
    $sql .= ' ORDER BY a.`date` DESC, a.`time` DESC';
}

$stmt = db()->prepare($sql);
$stmt->execute($params);
$appointments = $stmt->fetchAll();

$counts = db()->prepare(
    "SELECT
        SUM(status IN ('pending','confirmed') AND TIMESTAMP(`date`,`time`) >= NOW()) AS upcoming,
        SUM(status IN ('completed','rejected','cancelled') OR TIMESTAMP(`date`,`time`) < NOW()) AS past,
        COUNT(*) AS total
     FROM appointments WHERE patient_id = ?"
);
$counts->execute([$pid]);
$tabCounts = $counts->fetch() ?: ['upcoming' => 0, 'past' => 0, 'total' => 0];

$pageTitle = 'My appointments';
$pageSub   = 'Cancel, reschedule, or read the notes from a past visit.';
$layout    = 'dashboard';
$activeNav = 'appointments';
$pageActions = '<a class="btn btn-primary btn-sm" href="' . e(url('patient/doctors.php')) . '">' . icon('plus', 16) . ' New booking</a>';

require __DIR__ . '/../includes/header.php';
?>

<ul class="nav nav-pills mb-3 gap-2" style="font-size:.875rem">
    <?php
    $tabs = [
        'upcoming' => ['Upcoming', (int) $tabCounts['upcoming']],
        'past'     => ['Past', (int) $tabCounts['past']],
        'all'      => ['All', (int) $tabCounts['total']],
    ];
    foreach ($tabs as $key => [$label, $n]): ?>
        <li class="nav-item">
            <a class="nav-link <?= $tab === $key ? 'active' : 'text-muted' ?>"
               style="<?= $tab === $key ? 'background:var(--mb-primary)' : '' ?>"
               href="<?= e(url('patient/appointments.php?tab=' . $key)) ?>">
                <?= e($label) ?> <span class="badge bg-light text-dark ms-1"><?= $n ?></span>
            </a>
        </li>
    <?php endforeach; ?>
</ul>

<?php if (!$appointments): ?>
    <div class="card">
        <div class="empty-state">
            <div class="ico"><?= icon('calendar', 26) ?></div>
            <h2 class="h6">No <?= e($tab === 'all' ? '' : $tab) ?> appointments</h2>
            <p class="mb-3" style="font-size:.875rem">
                <?= $tab === 'upcoming' ? 'Book a doctor and your upcoming visits will appear here.' : 'Nothing to show in this tab yet.' ?>
            </p>
            <a class="btn btn-primary btn-sm" href="<?= e(url('patient/doctors.php')) ?>">Find a doctor</a>
        </div>
    </div>
<?php else: ?>
    <div class="d-flex flex-column gap-3">
        <?php foreach ($appointments as $a): ?>
            <?php
                $isFuture = strtotime($a['date'] . ' ' . $a['time']) >= time();
                $canCancel = $isFuture && in_array($a['status'], ['pending', 'confirmed'], true);
            ?>
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap align-items-start gap-3">

                        <?= doctor_avatar($a, '') ?>

                        <div class="flex-grow-1 min-w-0">
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                <a class="fw-bold text-ink" href="<?= e(url('patient/doctor.php?id=' . (int) $a['doctor_id'])) ?>">
                                    <?= e($a['doctor_name']) ?>
                                </a>
                                <?= status_badge($a['status']) ?>
                            </div>
                            <div class="text-muted mb-2" style="font-size:.8rem">
                                <?= e($a['specialty']) ?> · <?= e($a['location']) ?>
                            </div>

                            <div class="d-flex flex-wrap gap-3">
                                <span class="meta-row"><?= icon('calendar', 15) ?> <?= e(fmt_date($a['date'])) ?></span>
                                <span class="meta-row"><?= icon('clock', 15) ?> <?= e(fmt_time($a['time'])) ?></span>
                                <span class="meta-row"><?= icon('file', 15) ?> #<?= (int) $a['id'] ?></span>
                            </div>

                            <?php if (!empty($a['reason'])): ?>
                                <div class="mt-2 text-muted" style="font-size:.82rem">
                                    <strong class="text-ink">Reason:</strong> <?= e($a['reason']) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="d-flex flex-wrap gap-2 align-items-start">
                            <?php if ($canCancel): ?>
                                <a class="btn btn-sm btn-soft"
                                   href="<?= e(url('patient/doctor.php?id=' . (int) $a['doctor_id'] . '&reschedule=' . (int) $a['id'])) ?>">
                                    <?= icon('refresh', 14) ?> Reschedule
                                </a>
                                <form method="post" class="m-0" data-confirm="Cancel this appointment? The slot will be released.">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="cancel">
                                    <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Cancel</button>
                                </form>
                            <?php endif; ?>

                            <?php if ($a['status'] === 'completed'): ?>
                                <?php if (!empty($a['prescription']) || !empty($a['notes'])): ?>
                                    <button class="btn btn-sm btn-outline-primary" type="button"
                                            data-bs-toggle="collapse" data-bs-target="#notes<?= (int) $a['id'] ?>">
                                        <?= icon('file', 14) ?> Visit notes
                                    </button>
                                <?php endif; ?>

                                <?php if ($a['review_id'] === null): ?>
                                    <a class="btn btn-sm btn-primary" href="<?= e(url('patient/review.php?id=' . (int) $a['id'])) ?>">
                                        <?= icon('star', 14) ?> Rate visit
                                    </a>
                                <?php else: ?>
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle align-self-center">
                                        <?= str_repeat('★', (int) $a['rating']) ?> rated
                                    </span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($a['status'] === 'completed' && (!empty($a['notes']) || !empty($a['prescription']))): ?>
                        <div class="collapse mt-3" id="notes<?= (int) $a['id'] ?>">
                            <div class="row g-3">
                                <?php if (!empty($a['notes'])): ?>
                                    <div class="col-md-6">
                                        <div class="rounded-3 p-3 h-100" style="background:#f8fafb;border:1px solid var(--mb-border)">
                                            <div class="small-caps mb-1">Doctor's notes</div>
                                            <div style="font-size:.85rem;white-space:pre-line"><?= e($a['notes']) ?></div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($a['prescription'])): ?>
                                    <div class="col-md-6">
                                        <div class="rounded-3 p-3 h-100" style="background:var(--mb-primary-soft);border:1px solid var(--mb-primary-line)">
                                            <div class="small-caps mb-1">Prescription</div>
                                            <div style="font-size:.85rem;white-space:pre-line"><?= e($a['prescription']) ?></div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
