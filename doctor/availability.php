<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

$user = require_login('doctor');
$did  = (int) $user['id'];

$errors = [];

if (is_post()) {
    verify_csrf();
    $action = post('action');

    if ($action === 'add') {
        $dow    = post_int('day_of_week', -1);
        $start  = post('start_time');
        $end    = post('end_time');
        $slot   = post_int('slot_minutes', 30);

        if ($dow < 0 || $dow > 6) {
            $errors[] = 'Choose a weekday.';
        }
        if (!preg_match('/^\d{2}:\d{2}$/', $start) || !preg_match('/^\d{2}:\d{2}$/', $end)) {
            $errors[] = 'Enter a valid start and end time.';
        }
        if ($slot < 5 || $slot > 240) {
            $errors[] = 'Slot length must be between 5 and 240 minutes.';
        }

        if (!$errors) {
            $startS = $start . ':00';
            $endS   = $end . ':00';

            if (strtotime($endS) <= strtotime($startS)) {
                $errors[] = 'The end time must be after the start time.';
            } elseif ((strtotime($endS) - strtotime($startS)) / 60 < $slot) {
                $errors[] = 'That window is shorter than one slot.';
            } else {
                
                $ov = db()->prepare(
                    'SELECT start_time, end_time FROM availability
                      WHERE doctor_id = ? AND day_of_week = ?
                        AND start_time < ? AND end_time > ?'
                );
                $ov->execute([$did, $dow, $endS, $startS]);
                $clash = $ov->fetch();

                if ($clash) {
                    $errors[] = 'That overlaps an existing window (' . fmt_time($clash['start_time']) . '–' . fmt_time($clash['end_time']) . ').';
                } else {
                    db()->prepare(
                        'INSERT INTO availability (doctor_id, day_of_week, start_time, end_time, slot_minutes)
                         VALUES (?, ?, ?, ?, ?)'
                    )->execute([$did, $dow, $startS, $endS, $slot]);

                    flash('success', day_name($dow) . ' ' . fmt_time($startS) . '–' . fmt_time($endS) . ' added.');
                    redirect('doctor/availability.php');
                }
            }
        }
    }

    if ($action === 'delete') {
        $id = post_int('id');
        $del = db()->prepare('DELETE FROM availability WHERE id = ? AND doctor_id = ?');
        $del->execute([$id, $did]);

        if ($del->rowCount() > 0) {
            flash('success', 'Window removed. Existing bookings are untouched.');
        } else {
            flash('danger', 'That window could not be found.');
        }
        redirect('doctor/availability.php');
    }
}

$week = weekly_availability($did);

$totalSlots = 0;
foreach ($week as $windows) {
    foreach ($windows as $w) {
        $totalSlots += count(generate_slots((string) $w['start_time'], (string) $w['end_time'], (int) $w['slot_minutes']));
    }
}

$pageTitle = 'Availability';
$pageSub   = 'Define your week once — the booking engine generates the slots.';
$layout    = 'dashboard';
$activeNav = 'availability';

require __DIR__ . '/../includes/header.php';
?>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">Add a window</div>
            <div class="card-body">
                <?php foreach ($errors as $err): ?>
                    <div class="alert alert-danger py-2" style="font-size:.85rem"><?= e($err) ?></div>
                <?php endforeach; ?>

                <form method="post" data-once>
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="add">

                    <div class="mb-3">
                        <label class="form-label" for="day_of_week">Weekday</label>
                        <select class="form-select" id="day_of_week" name="day_of_week" required>
                            <?php for ($d = 1; $d <= 6; $d++): ?>
                                <option value="<?= $d ?>"><?= e(day_name($d)) ?></option>
                            <?php endfor; ?>
                            <option value="0">Sunday</option>
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label" for="start_time">From</label>
                            <input class="form-control" type="time" id="start_time" name="start_time" value="09:00" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="end_time">To</label>
                            <input class="form-control" type="time" id="end_time" name="end_time" value="13:00" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="slot_minutes">Slot length</label>
                        <select class="form-select" id="slot_minutes" name="slot_minutes">
                            <?php foreach ([10, 15, 20, 30, 45, 60] as $m): ?>
                                <option value="<?= $m ?>" <?= $m === 30 ? 'selected' : '' ?>><?= $m ?> minutes</option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Patients see one button per slot inside the window.</div>
                    </div>

                    <button class="btn btn-primary w-100" type="submit"><?= icon('plus', 16) ?> Add window</button>
                </form>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-ico mb-0"><?= icon('clock', 18) ?></div>
                <div>
                    <div class="fw-bold text-ink" style="font-size:1.1rem"><?= $totalSlots ?> slots / week</div>
                    <div class="text-muted" style="font-size:.8rem">across <?= array_sum(array_map('count', $week)) ?> windows</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">Your week</div>
            <div class="card-body">
                <?php if (!$week): ?>
                    <div class="empty-state py-4">
                        <div class="ico"><?= icon('sliders', 24) ?></div>
                        <p class="mb-0" style="font-size:.875rem">
                            No availability set. Patients will not see any slots until you add at least one window.
                        </p>
                    </div>
                <?php else: ?>
                    <?php for ($d = 0; $d <= 6; $d++): ?>
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 py-2 border-bottom">
                            <div class="fw-semibold text-ink" style="font-size:.875rem;min-width:90px"><?= e(day_name($d)) ?></div>
                            <div class="flex-grow-1 d-flex flex-wrap gap-2">
                                <?php if (empty($week[$d])): ?>
                                    <span class="text-faint" style="font-size:.82rem">Closed</span>
                                <?php else: ?>
                                    <?php foreach ($week[$d] as $w): ?>
                                        <span class="specialty-tag" style="background:#f8fafb;border-color:var(--mb-border);color:var(--mb-body);gap:.5rem">
                                            <?= e(fmt_time($w['start_time'])) ?>–<?= e(fmt_time($w['end_time'])) ?>
                                            <span class="text-faint">/<?= (int) $w['slot_minutes'] ?>m</span>
                                            <form method="post" class="m-0" data-confirm="Remove this window?">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= (int) $w['id'] ?>">
                                                <button class="btn btn-sm p-0 border-0 text-danger" type="submit" aria-label="Remove">
                                                    <?= icon('x', 13) ?>
                                                </button>
                                            </form>
                                        </span>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endfor; ?>
                <?php endif; ?>
            </div>
            <div class="card-body border-top pt-3">
                <p class="text-muted mb-0" style="font-size:.8rem">
                    <strong class="text-ink">Note:</strong> removing a window does not cancel existing bookings —
                    those appointments keep their slot. Only future slot generation is affected.
                </p>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
