<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

$user = require_login('admin');

$totals = db()->query(
    "SELECT
        COUNT(*) AS appts,
        SUM(status = 'completed') AS completed,
        SUM(status = 'pending')   AS pending,
        SUM(status = 'confirmed') AS confirmed,
        SUM(status = 'cancelled') AS cancelled,
        SUM(status = 'rejected')  AS rejected
     FROM appointments"
)->fetch();

$revenue = db()->query(
    "SELECT COALESCE(SUM(d.fee), 0)
       FROM appointments a JOIN doctors d ON d.user_id = a.doctor_id
      WHERE a.status = 'completed'"
)->fetchColumn();

$activeDoctors = (int) db()->query(
    "SELECT COUNT(DISTINCT a.doctor_id) FROM appointments a
      WHERE a.`date` >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"
)->fetchColumn();

$verifiedDoctors = (int) db()->query('SELECT COUNT(*) FROM doctors WHERE is_verified = 1')->fetchColumn();

$newUsers = db()->query(
    "SELECT
        SUM(role = 'patient') AS patients,
        SUM(role = 'doctor')  AS doctors
     FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
)->fetch();

$trendRows = db()->query(
    "SELECT DATE_FORMAT(`date`, '%Y-%m') AS m, COUNT(*) AS c
       FROM appointments
      WHERE `date` >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 5 MONTH), '%Y-%m-01')
      GROUP BY m"
)->fetchAll(PDO::FETCH_KEY_PAIR);

$trend = [];
for ($i = 5; $i >= 0; $i--) {
    $key = date('Y-m', strtotime("-{$i} months"));
    $trend[$key] = (int) ($trendRows[$key] ?? 0);
}
$trendMax = max(1, max($trend));

$topDocs = db()->query(
    "SELECT u.name, d.specialty, ROUND(AVG(r.rating), 2) AS avg_rating, COUNT(r.id) AS reviews
       FROM reviews r
       JOIN appointments a ON a.id = r.appointment_id
       JOIN users u   ON u.id = a.doctor_id
       JOIN doctors d ON d.user_id = u.id
      GROUP BY u.id, u.name, d.specialty
      HAVING reviews > 0
      ORDER BY avg_rating DESC, reviews DESC
      LIMIT 8"
)->fetchAll();

$busiest = db()->query(
    "SELECT u.name, d.specialty, COUNT(a.id) AS total,
            SUM(a.status = 'completed') AS done
       FROM appointments a
       JOIN users u   ON u.id = a.doctor_id
       JOIN doctors d ON d.user_id = u.id
      GROUP BY u.id, u.name, d.specialty
      ORDER BY total DESC
      LIMIT 8"
)->fetchAll();

$statusRows = [
    ['Confirmed', (int) $totals['confirmed'], '#0d9488'],
    ['Pending',   (int) $totals['pending'],   '#f59e0b'],
    ['Completed', (int) $totals['completed'], '#2563eb'],
    ['Cancelled', (int) $totals['cancelled'], '#94a3b8'],
    ['Rejected',  (int) $totals['rejected'],  '#ef4444'],
];

$pageTitle = 'Reports';
$pageSub   = 'Volume, revenue and ratings across the platform.';
$layout    = 'dashboard';
$activeNav = 'reports';

require __DIR__ . '/../includes/header.php';
?>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat">
            <div class="stat-ico"><?= icon('calendar', 18) ?></div>
            <div class="stat-value"><?= (int) $totals['appts'] ?></div>
            <div class="stat-label">Total appointments</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat">
            <div class="stat-ico"><?= icon('check', 18) ?></div>
            <div class="stat-value">
                <?= (int) $totals['appts'] > 0 ? round(((int) $totals['completed'] / (int) $totals['appts']) * 100) . '%' : '—' ?>
            </div>
            <div class="stat-label">Completion rate</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat">
            <div class="stat-ico"><?= icon('card', 18) ?></div>
            <div class="stat-value"><?= e(money((float) $revenue)) ?></div>
            <div class="stat-label">Completed visit value</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat">
            <div class="stat-ico"><?= icon('stetho', 18) ?></div>
            <div class="stat-value"><?= $activeDoctors ?></div>
            <div class="stat-label">Active doctors (30d)</div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Appointments · last 6 months</span>
                <span class="text-muted" style="font-size:.78rem">max <?= $trendMax ?></span>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-end gap-3" style="height:190px">
                    <?php foreach ($trend as $month => $count): ?>
                        <?php $h = $count > 0 ? max(6, (int) round(($count / $trendMax) * 150)) : 3; ?>
                        <div class="flex-fill d-flex flex-column align-items-center justify-content-end h-100">
                            <div class="fw-bold text-ink mb-1" style="font-size:.78rem"><?= $count ?></div>
                            <div style="width:100%;max-width:46px;height:<?= $h ?>px;border-radius:6px 6px 3px 3px;
                                        background:linear-gradient(180deg,#14b8a6,#0d9488)"></div>
                            <div class="text-muted mt-2" style="font-size:.72rem"><?= e(date('M', strtotime($month . '-01'))) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header">Status breakdown</div>
            <div class="card-body">
                <?php $total = max(1, (int) $totals['appts']); ?>
                <?php foreach ($statusRows as [$label, $n, $color]): ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1" style="font-size:.8rem">
                            <span class="text-ink fw-semibold"><?= $label ?></span>
                            <span class="text-muted"><?= $n ?> · <?= round(($n / $total) * 100) ?>%</span>
                        </div>
                        <div style="height:7px;background:#eef2f5;border-radius:99px;overflow:hidden">
                            <div style="width:<?= round(($n / $total) * 100) ?>%;height:100%;background:<?= $color ?>;border-radius:99px"></div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="d-flex justify-content-between pt-2 border-top" style="font-size:.8rem">
                    <span class="text-muted">New users (30d)</span>
                    <span class="fw-semibold text-ink">
                        <?= (int) $newUsers['patients'] ?> patients · <?= (int) $newUsers['doctors'] ?> doctors
                    </span>
                </div>
                <div class="d-flex justify-content-between pt-2" style="font-size:.8rem">
                    <span class="text-muted">Verified doctors</span>
                    <span class="fw-semibold text-ink"><?= $verifiedDoctors ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">Top-rated doctors</div>
            <?php if (!$topDocs): ?>
                <div class="empty-state py-4"><p class="mb-0" style="font-size:.875rem">No reviews yet.</p></div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead><tr><th>#</th><th>Doctor</th><th>Rating</th><th class="text-end">Reviews</th></tr></thead>
                        <tbody>
                        <?php foreach ($topDocs as $i => $d): ?>
                            <tr>
                                <td class="text-faint fw-bold"><?= $i + 1 ?></td>
                                <td>
                                    <div class="fw-semibold text-ink" style="font-size:.85rem"><?= e($d['name']) ?></div>
                                    <div class="text-muted" style="font-size:.74rem"><?= e($d['specialty']) ?></div>
                                </td>
                                <td class="text-warning" style="font-size:.85rem">★ <?= number_format((float) $d['avg_rating'], 2) ?></td>
                                <td class="text-end" style="font-size:.83rem"><?= (int) $d['reviews'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">Busiest doctors</div>
            <?php if (!$busiest): ?>
                <div class="empty-state py-4"><p class="mb-0" style="font-size:.875rem">No appointments yet.</p></div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead><tr><th>Doctor</th><th>Total</th><th class="text-end">Completed</th></tr></thead>
                        <tbody>
                        <?php foreach ($busiest as $d): ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold text-ink" style="font-size:.85rem"><?= e($d['name']) ?></div>
                                    <div class="text-muted" style="font-size:.74rem"><?= e($d['specialty']) ?></div>
                                </td>
                                <td style="font-size:.83rem"><?= (int) $d['total'] ?></td>
                                <td class="text-end" style="font-size:.83rem"><?= (int) $d['done'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
