<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

$user = require_login('doctor');
$did  = (int) $user['id'];

$stmt = db()->prepare(
    "SELECT u.id, u.name, u.email, p.age, p.gender, p.phone,
            COUNT(a.id)                                  AS total_visits,
            SUM(a.status = 'completed')                  AS completed_visits,
            MAX(CASE WHEN a.status = 'completed' THEN a.`date` END) AS last_visit,
            MIN(CASE WHEN a.status = 'confirmed' AND a.`date` >= CURDATE() THEN a.`date` END) AS next_visit
       FROM appointments a
       JOIN users u ON u.id = a.patient_id
       LEFT JOIN patients p ON p.user_id = a.patient_id
      WHERE a.doctor_id = ?
      GROUP BY u.id, u.name, u.email, p.age, p.gender, p.phone
      ORDER BY last_visit DESC, u.name"
);
$stmt->execute([$did]);
$patients = $stmt->fetchAll();

$history = [];
if ($patients) {
    $ids = array_map(static fn ($p) => (int) $p['id'], $patients);
    $in  = implode(',', array_fill(0, count($ids), '?'));
    $h   = db()->prepare(
        "SELECT patient_id, id, `date`, `time`, status, reason, notes, prescription
           FROM appointments
          WHERE doctor_id = ? AND patient_id IN ($in)
          ORDER BY `date` DESC, `time` DESC"
    );
    $h->execute(array_merge([$did], $ids));
    foreach ($h->fetchAll() as $row) {
        $history[(int) $row['patient_id']][] = $row;
    }
}

$pageTitle = 'My patients';
$pageSub   = 'Everyone who has booked with you, and what happened.';
$layout    = 'dashboard';
$activeNav = 'patients';

require __DIR__ . '/../includes/header.php';
?>

<?php if (!$patients): ?>
    <div class="card">
        <div class="empty-state">
            <div class="ico"><?= icon('users', 26) ?></div>
            <h2 class="h6">No patients yet</h2>
            <p class="mb-0" style="font-size:.875rem">
                Once someone books and you confirm, they will appear here with their visit history.
            </p>
        </div>
    </div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($patients as $p): ?>
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-start gap-3">
                            <div class="avatar"><?= e(initials($p['name'])) ?></div>
                            <div class="flex-grow-1 min-w-0">
                                <div class="fw-bold text-ink"><?= e($p['name']) ?></div>
                                <div class="text-muted" style="font-size:.78rem">
                                    <?php if ($p['age']): ?><?= (int) $p['age'] ?> yrs<?php endif; ?>
                                    <?php if ($p['gender']): ?> · <?= e($p['gender']) ?><?php endif; ?>
                                    <?php if ($p['phone']): ?> · <?= e($p['phone']) ?><?php endif; ?>
                                </div>
                                <div class="text-muted" style="font-size:.78rem"><?= e($p['email']) ?></div>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold text-ink" style="font-size:1.15rem"><?= (int) $p['completed_visits'] ?></div>
                                <div class="text-faint" style="font-size:.7rem">visits</div>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-3 mt-3 pt-3 border-top">
                            <span class="meta-row">
                                <?= icon('calendar', 15) ?>
                                Last: <?= $p['last_visit'] ? e(fmt_date($p['last_visit'])) : '—' ?>
                            </span>
                            <?php if ($p['next_visit']): ?>
                                <span class="meta-row text-success">
                                    <?= icon('clock', 15) ?>
                                    Next: <?= e(fmt_date($p['next_visit'])) ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <button class="btn btn-sm btn-soft w-100 mt-3" type="button"
                                data-bs-toggle="collapse" data-bs-target="#hist<?= (int) $p['id'] ?>">
                            <?= icon('file', 14) ?> Visit history (<?= count($history[(int) $p['id']] ?? []) ?>)
                        </button>

                        <div class="collapse mt-3" id="hist<?= (int) $p['id'] ?>">
                            <?php foreach ($history[(int) $p['id']] ?? [] as $h): ?>
                                <div class="py-2 border-top">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span style="font-size:.8rem" class="text-ink fw-semibold">
                                            <?= e(fmt_date($h['date'])) ?> · <?= e(fmt_time($h['time'])) ?>
                                        </span>
                                        <?= status_badge($h['status']) ?>
                                    </div>
                                    <?php if ($h['reason']): ?>
                                        <div class="text-muted" style="font-size:.78rem"><?= e($h['reason']) ?></div>
                                    <?php endif; ?>
                                    <?php if ($h['notes']): ?>
                                        <div class="mt-1" style="font-size:.78rem">
                                            <strong class="text-ink">Notes:</strong> <?= e(mb_strimwidth($h['notes'], 0, 160, '…')) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
