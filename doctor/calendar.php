<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

$user = require_login('doctor');
$did  = (int) $user['id'];

$monthParam = query('m', date('Y-m'));
if (!preg_match('/^\d{4}-\d{2}$/', $monthParam)) {
    $monthParam = date('Y-m');
}

$first = new DateTimeImmutable($monthParam . '-01');
$gridStart = $first->modify('-' . (int) $first->format('w') . ' days');
$daysInMonth = (int) $first->format('t');
$weeks = (int) ceil((((int) $first->format('w')) + $daysInMonth) / 7);
$gridEnd = $gridStart->modify('+' . ($weeks * 7 - 1) . ' days');

$stmt = db()->prepare(
    "SELECT a.id, a.`date`, a.`time`, a.status, a.reason, u.name AS patient_name
       FROM appointments a
       JOIN users u ON u.id = a.patient_id
      WHERE a.doctor_id = ?
        AND a.`date` BETWEEN ? AND ?
        AND a.status <> 'cancelled'
      ORDER BY a.`date`, a.`time`"
);
$stmt->execute([$did, $gridStart->format('Y-m-d'), $gridEnd->format('Y-m-d')]);

$byDay = [];
foreach ($stmt->fetchAll() as $row) {
    $byDay[$row['date']][] = $row;
}

$prev = $first->modify('-1 month')->format('Y-m');
$next = $first->modify('+1 month')->format('Y-m');

$monthCount = array_sum(array_map('count', $byDay));

$pageTitle = 'Calendar';
$pageSub   = 'Every booking for the month, at a glance.';
$layout    = 'dashboard';
$activeNav = 'calendar';
$pageActions = '<a class="btn btn-sm btn-outline-primary" href="' . e(url('doctor/appointments.php?status=upcoming')) . '">List view</a>';

require __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div class="d-flex align-items-center gap-2">
            <a class="btn btn-sm btn-light border" href="<?= e(url('doctor/calendar.php?m=' . $prev)) ?>" aria-label="Previous month">
                <?= icon('chevron', 16) ?>
            </a>
            <strong class="text-ink"><?= e($first->format('F Y')) ?></strong>
            <a class="btn btn-sm btn-light border" href="<?= e(url('doctor/calendar.php?m=' . $next)) ?>" aria-label="Next month">
                <span style="display:inline-block;transform:rotate(180deg)"><?= icon('chevron', 16) ?></span>
            </a>
            <a class="btn btn-sm btn-link text-decoration-none" href="<?= e(url('doctor/calendar.php')) ?>">Today</a>
        </div>
        <span class="badge rounded-pill bg-light text-dark border"><?= $monthCount ?> appointment<?= $monthCount === 1 ? '' : 's' ?></span>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered mb-0" style="table-layout:fixed">
            <thead>
                <tr>
                    <?php foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $d): ?>
                        <th class="text-center" style="font-size:.7rem"><?= $d ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
            <?php for ($w = 0; $w < $weeks; $w++): ?>
                <tr>
                    <?php for ($d = 0; $d < 7; $d++): ?>
                        <?php
                        $cell = $gridStart->modify('+' . ($w * 7 + $d) . ' days');
                        $key  = $cell->format('Y-m-d');
                        $inMonth = $cell->format('Y-m') === $first->format('Y-m');
                        $isToday = $key === date('Y-m-d');
                        $items = $byDay[$key] ?? [];
                        ?>
                        <td class="align-top <?= $inMonth ? '' : 'bg-light' ?>"
                            style="height:110px;padding:.4rem;<?= $isToday ? 'box-shadow:inset 0 0 0 2px var(--mb-primary)' : '' ?>">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="<?= $inMonth ? 'text-ink' : 'text-faint' ?> fw-semibold" style="font-size:.78rem">
                                    <?= $cell->format('j') ?>
                                </span>
                                <?php if (count($items) > 2): ?>
                                    <span class="text-faint" style="font-size:.66rem">+<?= count($items) - 2 ?></span>
                                <?php endif; ?>
                            </div>

                            <?php foreach (array_slice($items, 0, 2) as $it): ?>
                                <?php
                                [$bg, $fg] = match ($it['status']) {
                                    'confirmed' => ['#ecfdf9', '#0b7a70'],
                                    'pending'   => ['#fffbeb', '#92400e'],
                                    'completed' => ['#eff6ff', '#1d4ed8'],
                                    default     => ['#f1f5f9', '#475569'],
                                };
                                ?>
                                <div class="mb-1 px-1 py-1 rounded"
                                     style="background:<?= $bg ?>;color:<?= $fg ?>;font-size:.66rem;line-height:1.25;overflow:hidden">
                                    <strong><?= e(date('g:i', strtotime($it['time']))) ?></strong>
                                    <?= e(mb_strimwidth(explode(' ', $it['patient_name'])[0], 0, 12, '…')) ?>
                                </div>
                            <?php endforeach; ?>
                        </td>
                    <?php endfor; ?>
                </tr>
            <?php endfor; ?>
            </tbody>
        </table>
    </div>

    <div class="card-body d-flex flex-wrap gap-3 border-top" style="font-size:.76rem">
        <span class="d-flex align-items-center gap-1"><span style="width:10px;height:10px;border-radius:3px;background:#0b7a70;display:inline-block"></span> Confirmed</span>
        <span class="d-flex align-items-center gap-1"><span style="width:10px;height:10px;border-radius:3px;background:#92400e;display:inline-block"></span> Pending</span>
        <span class="d-flex align-items-center gap-1"><span style="width:10px;height:10px;border-radius:3px;background:#1d4ed8;display:inline-block"></span> Completed</span>
        <span class="text-muted ms-auto">Cancelled appointments are hidden from the calendar.</span>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
