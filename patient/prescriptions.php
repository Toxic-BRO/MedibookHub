<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

$user = require_login('patient');
$pid  = (int) $user['id'];

$stmt = db()->prepare(
    "SELECT a.id, a.`date`, a.`time`, a.notes, a.prescription, a.reason,
            u.name AS doctor_name, d.specialty
       FROM appointments a
       JOIN users u   ON u.id = a.doctor_id
       JOIN doctors d ON d.user_id = a.doctor_id
      WHERE a.patient_id = ?
        AND a.status = 'completed'
        AND (a.notes IS NOT NULL OR a.prescription IS NOT NULL)
      ORDER BY a.`date` DESC"
);
$stmt->execute([$pid]);
$records = $stmt->fetchAll();

$pageTitle = 'Prescriptions';
$pageSub   = 'Clinical notes and prescriptions filed by your doctors.';
$layout    = 'dashboard';
$activeNav = 'prescriptions';

require __DIR__ . '/../includes/header.php';
?>

<?php if (!$records): ?>
    <div class="card">
        <div class="empty-state">
            <div class="ico"><?= icon('file', 26) ?></div>
            <h2 class="h6">No prescriptions yet</h2>
            <p class="mb-3" style="font-size:.875rem">
                Once a doctor completes a visit and files notes, they will appear here.
            </p>
            <a class="btn btn-primary btn-sm" href="<?= e(url('patient/doctors.php')) ?>">Book a visit</a>
        </div>
    </div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($records as $r): ?>
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-ink" style="font-size:.9rem"><?= e($r['doctor_name']) ?></div>
                            <div class="text-muted fw-normal" style="font-size:.76rem">
                                <?= e($r['specialty']) ?> · <?= e(fmt_date($r['date'])) ?>
                            </div>
                        </div>
                        <span class="badge rounded-pill bg-primary-subtle text-primary-emphasis border border-primary-subtle">
                            #<?= (int) $r['id'] ?>
                        </span>
                    </div>
                    <div class="card-body d-flex flex-column gap-3">
                        <?php if (!empty($r['reason'])): ?>
                            <div>
                                <div class="small-caps mb-1">Reason</div>
                                <div style="font-size:.85rem"><?= e($r['reason']) ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($r['notes'])): ?>
                            <div>
                                <div class="small-caps mb-1">Doctor's notes</div>
                                <div style="font-size:.85rem;white-space:pre-line"><?= e($r['notes']) ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($r['prescription'])): ?>
                            <div class="rounded-3 p-3 mt-auto" style="background:var(--mb-primary-soft);border:1px solid var(--mb-primary-line)">
                                <div class="small-caps mb-1">Prescription</div>
                                <div style="font-size:.85rem;white-space:pre-line"><?= e($r['prescription']) ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
