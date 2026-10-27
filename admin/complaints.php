<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

$user = require_login('admin');

if (is_post()) {
    verify_csrf();

    $id     = post_int('id');
    $status = post('status');
    $reply  = mb_substr(post('admin_reply'), 0, 2000);

    if (!in_array($status, ['open', 'in_review', 'resolved'], true)) {
        flash('danger', 'Invalid status.');
        redirect('admin/complaints.php');
    }

    $stmt = db()->prepare('SELECT patient_id FROM complaints WHERE id = ?');
    $stmt->execute([$id]);
    $patientId = $stmt->fetchColumn();

    if (!$patientId) {
        flash('danger', 'Complaint not found.');
        redirect('admin/complaints.php');
    }

    db()->prepare('UPDATE complaints SET status = ?, admin_reply = ? WHERE id = ?')
        ->execute([$status, $reply !== '' ? $reply : null, $id]);

    if ($reply !== '') {
        notify((int) $patientId, 'The administrator replied to your complaint: ' . mb_strimwidth($reply, 0, 120, '…'), '/patient/complaints.php');
    } elseif ($status === 'resolved') {
        notify((int) $patientId, 'Your complaint has been marked resolved.', '/patient/complaints.php');
    }

    flash('success', 'Complaint #' . $id . ' updated.');
    redirect('admin/complaints.php?status=' . urlencode(post('filter', 'all')));
}

$filter = query('status', 'all');
if (!in_array($filter, ['all', 'open', 'in_review', 'resolved'], true)) {
    $filter = 'all';
}

$sql = "SELECT c.*, p.name AS patient_name, p.email AS patient_email, du.name AS doctor_name
          FROM complaints c
          JOIN users p ON p.id = c.patient_id
          LEFT JOIN users du ON du.id = c.doctor_id";
$params = [];

if ($filter !== 'all') {
    $sql .= ' WHERE c.status = ?';
    $params[] = $filter;
}
$sql .= ' ORDER BY (c.status = \'open\') DESC, c.created_at DESC';

$stmt = db()->prepare($sql);
$stmt->execute($params);
$complaints = $stmt->fetchAll();

$counts = db()->query(
    "SELECT COUNT(*) AS total,
            SUM(status = 'open')      AS open,
            SUM(status = 'in_review') AS in_review,
            SUM(status = 'resolved')  AS resolved
       FROM complaints"
)->fetch();

$pageTitle = 'Complaints';
$pageSub   = 'Read, reply and close out patient complaints.';
$layout    = 'dashboard';
$activeNav = 'complaints';

require __DIR__ . '/../includes/header.php';
?>

<ul class="nav nav-pills gap-2 mb-3" style="font-size:.875rem">
    <?php
    $tabs = [
        'all'       => ['All', (int) $counts['total']],
        'open'      => ['Open', (int) $counts['open']],
        'in_review' => ['In review', (int) $counts['in_review']],
        'resolved'  => ['Resolved', (int) $counts['resolved']],
    ];
    foreach ($tabs as $key => [$label, $n]): ?>
        <li class="nav-item">
            <a class="nav-link <?= $filter === $key ? 'active text-white' : 'text-muted' ?>"
               style="<?= $filter === $key ? 'background:var(--mb-primary)' : '' ?>"
               href="<?= e(url('admin/complaints.php?status=' . $key)) ?>">
                <?= e($label) ?> <span class="badge bg-light text-dark ms-1"><?= $n ?></span>
            </a>
        </li>
    <?php endforeach; ?>
</ul>

<?php if (!$complaints): ?>
    <div class="card">
        <div class="empty-state">
            <div class="ico"><?= icon('chat', 26) ?></div>
            <h2 class="h6">Nothing to triage</h2>
            <p class="mb-0" style="font-size:.875rem">No complaints in this tab.</p>
        </div>
    </div>
<?php else: ?>
    <div class="d-flex flex-column gap-3">
        <?php foreach ($complaints as $c): ?>
            <?php
            [$cls, $label] = match ($c['status']) {
                'open'      => ['warning text-dark', 'Open'],
                'in_review' => ['info text-dark', 'In review'],
                'resolved'  => ['success', 'Resolved'],
                default     => ['secondary', ucfirst($c['status'])],
            };
            ?>
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="avatar avatar-sm"><?= e(initials($c['patient_name'])) ?></span>
                                <div>
                                    <div class="fw-semibold text-ink" style="font-size:.875rem"><?= e($c['patient_name']) ?></div>
                                    <div class="text-muted" style="font-size:.74rem">
                                        <?= e($c['patient_email']) ?> · <?= e(fmt_datetime($c['created_at'])) ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <?php if ($c['doctor_name']): ?>
                                <span class="specialty-tag"><?= icon('stetho', 12) ?> <?= e($c['doctor_name']) ?></span>
                            <?php endif; ?>
                            <span class="badge rounded-pill bg-<?= $cls ?>"><?= e($label) ?></span>
                        </div>
                    </div>

                    <p class="mb-3" style="font-size:.875rem;white-space:pre-line"><?= e($c['message']) ?></p>

                    <form method="post" data-once class="rounded-3 p-3" style="background:#f8fafb;border:1px solid var(--mb-border)">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                        <input type="hidden" name="filter" value="<?= e($filter) ?>">

                        <div class="row g-2 align-items-start">
                            <div class="col-md-3">
                                <label class="form-label" for="status<?= (int) $c['id'] ?>">Status</label>
                                <select class="form-select form-select-sm" id="status<?= (int) $c['id'] ?>" name="status">
                                    <?php foreach (['open' => 'Open', 'in_review' => 'In review', 'resolved' => 'Resolved'] as $val => $lab): ?>
                                        <option value="<?= $val ?>" <?= $c['status'] === $val ? 'selected' : '' ?>><?= $lab ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-7">
                                <label class="form-label" for="reply<?= (int) $c['id'] ?>">Reply to patient <span class="text-faint fw-normal">(optional)</span></label>
                                <textarea class="form-control form-control-sm" id="reply<?= (int) $c['id'] ?>" name="admin_reply" rows="2"
                                          maxlength="2000" placeholder="What was done about it?"><?= e($c['admin_reply'] ?? '') ?></textarea>
                            </div>
                            <div class="col-md-2 d-flex align-items-end" style="min-height:62px">
                                <button class="btn btn-sm btn-primary w-100" type="submit">Save</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
