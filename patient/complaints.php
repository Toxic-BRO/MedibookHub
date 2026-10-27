<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

$user = require_login('patient');
$pid  = (int) $user['id'];

$errors = [];
$message = '';
$doctorId = 0;

if (is_post()) {
    verify_csrf();

    $message  = mb_substr(post('message'), 0, 2000);
    $doctorId = post_int('doctor_id');

    if (mb_strlen($message) < 10) {
        $errors[] = 'Describe the issue in at least 10 characters so we can act on it.';
    }
    if ($doctorId > 0) {
        $chk = db()->prepare('SELECT 1 FROM doctors WHERE user_id = ?');
        $chk->execute([$doctorId]);
        if (!$chk->fetchColumn()) {
            $errors[] = 'That doctor could not be found.';
        }
    }

    if (!$errors) {
        db()->prepare(
            'INSERT INTO complaints (patient_id, doctor_id, message, status) VALUES (?, ?, ?, ?)'
        )->execute([$pid, $doctorId > 0 ? $doctorId : null, $message, 'open']);

        $admins = db()->query("SELECT id FROM users WHERE role = 'admin'")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($admins as $adminId) {
            notify((int) $adminId, 'New complaint from ' . $user['name'] . '.', '/admin/complaints.php', false);
        }

        flash('success', 'Complaint submitted. An administrator will review it.');
        redirect('patient/complaints.php');
    }
}


$docs = db()->prepare(
    "SELECT DISTINCT u.id, u.name, d.specialty
       FROM appointments a
       JOIN users u   ON u.id = a.doctor_id
       JOIN doctors d ON d.user_id = a.doctor_id
      WHERE a.patient_id = ?
      ORDER BY u.name"
);
$docs->execute([$pid]);
$myDoctors = $docs->fetchAll();

$list = db()->prepare(
    "SELECT c.*, u.name AS doctor_name
       FROM complaints c
       LEFT JOIN users u ON u.id = c.doctor_id
      WHERE c.patient_id = ?
      ORDER BY c.created_at DESC"
);
$list->execute([$pid]);
$complaints = $list->fetchAll();

$pageTitle = 'Complaints';
$pageSub   = 'Something went wrong? Tell the administrator directly.';
$layout    = 'dashboard';
$activeNav = 'complaints';

require __DIR__ . '/../includes/header.php';
?>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">File a complaint</div>
            <div class="card-body">
                <?php foreach ($errors as $err): ?>
                    <div class="alert alert-danger py-2" style="font-size:.85rem"><?= e($err) ?></div>
                <?php endforeach; ?>

                <form method="post" data-once>
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label" for="doctor_id">About a doctor <span class="text-faint fw-normal">(optional)</span></label>
                        <select class="form-select" id="doctor_id" name="doctor_id">
                            <option value="0">Not about a specific doctor</option>
                            <?php foreach ($myDoctors as $d): ?>
                                <option value="<?= (int) $d['id'] ?>" <?= $doctorId === (int) $d['id'] ? 'selected' : '' ?>>
                                    <?= e($d['name']) ?> — <?= e($d['specialty']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="message">What happened?</label>
                        <textarea class="form-control" id="message" name="message" rows="5" maxlength="2000"
                                  placeholder="Include dates and appointment numbers if you have them."><?= e($message) ?></textarea>
                        <div class="form-text">Minimum 10 characters. Only administrators can read this.</div>
                    </div>

                    <button class="btn btn-primary w-100" type="submit">Submit complaint</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Your complaints</span>
                <span class="text-muted" style="font-size:.8rem"><?= count($complaints) ?> total</span>
            </div>

            <?php if (!$complaints): ?>
                <div class="empty-state py-4">
                    <div class="ico"><?= icon('chat', 24) ?></div>
                    <p class="mb-0" style="font-size:.875rem">No complaints filed. Hopefully it stays that way.</p>
                </div>
            <?php else: ?>
                <div class="card-body d-flex flex-column gap-3">
                    <?php foreach ($complaints as $c): ?>
                        <div class="pb-3 border-bottom">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                <div class="text-muted" style="font-size:.78rem">
                                    <?= e(fmt_datetime($c['created_at'])) ?>
                                    <?php if ($c['doctor_name']): ?> · about <?= e($c['doctor_name']) ?><?php endif; ?>
                                </div>
                                <?php
                                [$cls, $label] = match ($c['status']) {
                                    'open'      => ['warning text-dark', 'Open'],
                                    'in_review' => ['info text-dark', 'In review'],
                                    'resolved'  => ['success', 'Resolved'],
                                    default     => ['secondary', ucfirst($c['status'])],
                                };
                                ?>
                                <span class="badge rounded-pill bg-<?= $cls ?>"><?= e($label) ?></span>
                            </div>
                            <p class="mb-0" style="font-size:.875rem;white-space:pre-line"><?= e($c['message']) ?></p>

                            <?php if (!empty($c['admin_reply'])): ?>
                                <div class="rounded-3 p-3 mt-2" style="background:var(--mb-primary-soft);border:1px solid var(--mb-primary-line)">
                                    <div class="small-caps mb-1">Admin reply</div>
                                    <div style="font-size:.85rem;white-space:pre-line"><?= e($c['admin_reply']) ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
