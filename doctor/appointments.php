<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

$user = require_login('doctor');
$did  = (int) $user['id'];

if (is_post()) {
    verify_csrf();

    $action = post('action');
    $id     = post_int('id');

    $stmt = db()->prepare(
        "SELECT a.*, u.name AS patient_name
           FROM appointments a JOIN users u ON u.id = a.patient_id
          WHERE a.id = ? AND a.doctor_id = ?"
    );
    $stmt->execute([$id, $did]);
    $appt = $stmt->fetch();

    if (!$appt) {
        flash('danger', 'That appointment does not belong to you.');
        redirect('doctor/appointments.php');
    }

    $patientId = (int) $appt['patient_id'];
    $when      = fmt_date($appt['date']) . ' at ' . fmt_time($appt['time']);

    if ($action === 'accept') {
        if ($appt['status'] !== 'pending') {
            flash('warning', 'That request was already handled.');
        } else {
            db()->prepare("UPDATE appointments SET status = 'confirmed' WHERE id = ? AND status = 'pending'")
                ->execute([$id]);
            notify($patientId, 'Your appointment with Dr. ' . preg_replace('/^Dr\.?\s*/i', '', $user['name']) . ' on ' . $when . ' is confirmed.', '/patient/appointments.php');
            flash('success', 'Appointment confirmed. The patient has been notified.');
        }
    } elseif ($action === 'reject') {
        if ($appt['status'] !== 'pending') {
            flash('warning', 'That request was already handled.');
        } else {
            db()->prepare("UPDATE appointments SET status = 'rejected' WHERE id = ? AND status = 'pending'")
                ->execute([$id]);
            notify($patientId, 'Your appointment request for ' . $when . ' was declined. Please pick another slot.', '/patient/doctors.php');
            flash('success', 'Request declined and the slot released.');
        }
    } elseif ($action === 'complete') {
        $notes        = mb_substr(post('notes'), 0, 5000);
        $prescription = mb_substr(post('prescription'), 0, 5000);

        if ($appt['status'] !== 'confirmed') {
            flash('warning', 'Only confirmed appointments can be completed.');
        } elseif (mb_strlen(trim($notes)) < 5) {
            flash('danger', 'Add visit notes before marking the appointment complete.');
        } else {
            db()->prepare(
                "UPDATE appointments SET status = 'completed', notes = ?, prescription = ? WHERE id = ?"
            )->execute([$notes, $prescription !== '' ? $prescription : null, $id]);

            notify($patientId, 'Your visit notes from ' . $when . ' are ready to read.', '/patient/prescriptions.php');
            flash('success', 'Visit closed. Notes and prescription saved.');
        }
    }

    redirect('doctor/appointments.php?status=' . urlencode(post('status', 'pending')));
}

$status = query('status', 'pending');
if (!in_array($status, ['pending', 'upcoming', 'past', 'all'], true)) {
    $status = 'pending';
}
$q = query('q');

$sql = "SELECT a.*, u.name AS patient_name, p.age, p.gender, p.phone, r.rating
          FROM appointments a
          JOIN users u    ON u.id = a.patient_id
          LEFT JOIN patients p ON p.user_id = a.patient_id
          LEFT JOIN reviews r  ON r.appointment_id = a.id
         WHERE a.doctor_id = ?";
$params = [$did];

if ($status === 'pending') {
    $sql .= " AND a.status = 'pending'";
} elseif ($status === 'upcoming') {
    $sql .= " AND a.status = 'confirmed' AND a.`date` >= CURDATE()";
} elseif ($status === 'past') {
    $sql .= " AND a.status IN ('completed','rejected','cancelled')";
}

if ($q !== '') {
    $sql .= ' AND u.name LIKE ?';
    $params[] = '%' . $q . '%';
}

$sql .= $status === 'past'
    ? ' ORDER BY a.`date` DESC, a.`time` DESC LIMIT 100'
    : ' ORDER BY a.`date`, a.`time` LIMIT 100';

$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$counts = db()->prepare(
    "SELECT
        SUM(status = 'pending') AS pending,
        SUM(status = 'confirmed' AND `date` >= CURDATE()) AS upcoming,
        SUM(status IN ('completed','rejected','cancelled')) AS past,
        COUNT(*) AS total
     FROM appointments WHERE doctor_id = ?"
);
$counts->execute([$did]);
$tabCounts = $counts->fetch() ?: [];

$pageTitle = 'Appointments';
$pageSub   = 'Accept requests, then close visits with notes and a prescription.';
$layout    = 'dashboard';
$activeNav = 'appointments';

require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <ul class="nav nav-pills gap-2" style="font-size:.875rem">
        <?php
        $tabs = [
            'pending'  => ['Requests', (int) ($tabCounts['pending'] ?? 0)],
            'upcoming' => ['Upcoming', (int) ($tabCounts['upcoming'] ?? 0)],
            'past'     => ['Past', (int) ($tabCounts['past'] ?? 0)],
            'all'      => ['All', (int) ($tabCounts['total'] ?? 0)],
        ];
        foreach ($tabs as $key => [$label, $n]): ?>
            <li class="nav-item">
                <a class="nav-link <?= $status === $key ? 'active text-white' : 'text-muted' ?>"
                   style="<?= $status === $key ? 'background:var(--mb-primary)' : '' ?>"
                   href="<?= e(url('doctor/appointments.php?status=' . $key)) ?>">
                    <?= e($label) ?> <span class="badge bg-light text-dark ms-1"><?= $n ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>

    <form class="d-flex gap-2" method="get" action="<?= e(url('doctor/appointments.php')) ?>">
        <input type="hidden" name="status" value="<?= e($status) ?>">
        <input class="form-control form-control-sm" type="search" name="q" value="<?= e($q) ?>"
               placeholder="Patient name" style="width:180px">
        <button class="btn btn-sm btn-outline-secondary" type="submit">Filter</button>
    </form>
</div>

<?php if (!$rows): ?>
    <div class="card">
        <div class="empty-state">
            <div class="ico"><?= icon('calendar', 26) ?></div>
            <h2 class="h6">Nothing in this tab</h2>
            <p class="mb-0" style="font-size:.875rem">
                <?= $status === 'pending' ? 'No booking requests waiting for an answer.' : 'Appointments will appear here as they come in.' ?>
            </p>
        </div>
    </div>
<?php else: ?>
    <div class="d-flex flex-column gap-3">
        <?php foreach ($rows as $a): ?>
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap align-items-start gap-3">
                        <div class="avatar"><?= e(initials($a['patient_name'])) ?></div>

                        <div class="flex-grow-1 min-w-0">
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                <span class="fw-bold text-ink"><?= e($a['patient_name']) ?></span>
                                <?= status_badge($a['status']) ?>
                                <?php if ($a['rating'] !== null): ?>
                                    <span class="text-warning" style="font-size:.8rem"><?= str_repeat('★', (int) $a['rating']) ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="d-flex flex-wrap gap-3 mb-2">
                                <span class="meta-row"><?= icon('calendar', 15) ?> <?= e(fmt_date($a['date'])) ?></span>
                                <span class="meta-row"><?= icon('clock', 15) ?> <?= e(fmt_time($a['time'])) ?></span>
                                <?php if ($a['age']): ?>
                                    <span class="meta-row"><?= icon('user', 15) ?> <?= (int) $a['age'] ?> yrs<?= $a['gender'] ? ', ' . e($a['gender']) : '' ?></span>
                                <?php endif; ?>
                                <?php if ($a['phone']): ?>
                                    <span class="meta-row"><?= icon('phone', 15) ?> <?= e($a['phone']) ?></span>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($a['reason'])): ?>
                                <div class="text-muted" style="font-size:.82rem">
                                    <strong class="text-ink">Reason:</strong> <?= e($a['reason']) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            <?php if ($a['status'] === 'pending'): ?>
                                <form method="post" class="m-0">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="status" value="<?= e($status) ?>">
                                    <input type="hidden" name="action" value="accept">
                                    <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                                    <button class="btn btn-sm btn-primary" type="submit"><?= icon('check', 14) ?> Accept</button>
                                </form>
                                <form method="post" class="m-0" data-confirm="Reject this request?">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="status" value="<?= e($status) ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Reject</button>
                                </form>
                            <?php elseif ($a['status'] === 'confirmed'): ?>
                                <button class="btn btn-sm btn-primary" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#close<?= (int) $a['id'] ?>">
                                    <?= icon('clipboard', 14) ?> Complete visit
                                </button>
                            <?php elseif ($a['status'] === 'completed'): ?>
                                <button class="btn btn-sm btn-outline-primary" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#close<?= (int) $a['id'] ?>">
                                    <?= icon('file', 14) ?> View / edit notes
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (in_array($a['status'], ['confirmed', 'completed'], true)): ?>
                        <div class="collapse mt-3 <?= $a['status'] === 'completed' ? '' : '' ?>" id="close<?= (int) $a['id'] ?>">
                            <form method="post" data-once class="rounded-3 p-3" style="background:#f8fafb;border:1px solid var(--mb-border)">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="complete">
                                <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                                <input type="hidden" name="status" value="<?= e($status) ?>">

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label" for="notes<?= (int) $a['id'] ?>">Visit notes</label>
                                        <textarea class="form-control" id="notes<?= (int) $a['id'] ?>" name="notes" rows="5"
                                                  placeholder="Findings, diagnosis, advice…" required><?= e($a['notes'] ?? '') ?></textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="rx<?= (int) $a['id'] ?>">
                                            Prescription <span class="text-faint fw-normal">(optional)</span>
                                        </label>
                                        <textarea class="form-control" id="rx<?= (int) $a['id'] ?>" name="prescription" rows="5"
                                                  placeholder="One medicine per line, with dose and duration"><?= e($a['prescription'] ?? '') ?></textarea>
                                    </div>
                                </div>

                                <div class="d-flex align-items-center justify-content-between mt-3">
                                    <span class="text-muted" style="font-size:.78rem">
                                        Saving marks the visit <strong>completed</strong> and notifies the patient.
                                    </span>
                                    <button class="btn btn-sm btn-primary" type="submit">
                                        <?= $a['status'] === 'completed' ? 'Update notes' : 'Save & complete' ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
