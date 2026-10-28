<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

$doctorId = query_int('id');
$doctor   = $doctorId > 0 ? doctor_detail($doctorId) : null;

if (!$doctor || !(int) $doctor['is_verified'] || $doctor['status'] !== 'active') {
    http_response_code(404);
    $pageTitle = 'Doctor not found';
    $layout    = 'public';
    require __DIR__ . '/../includes/header.php';
    ?>
    <section class="py-5"><div class="container" style="max-width:640px">
        <div class="card"><div class="empty-state">
            <div class="ico"><?= icon('stetho', 26) ?></div>
            <h1 class="h5">This doctor is not available</h1>
            <p class="mb-3" style="font-size:.875rem">The profile may have been removed, or the account is still awaiting verification.</p>
            <a class="btn btn-primary btn-sm" href="<?= e(url('patient/doctors.php')) ?>">Browse other doctors</a>
        </div></div>
    </div></section>
    <?php
    require __DIR__ . '/../includes/footer.php';
    exit;
}

$user      = auth_user();
$isPatient = $user && $user['role'] === 'patient';

 
$reschedule    = null;
$rescheduleId  = query_int('reschedule');
if ($rescheduleId > 0 && $isPatient) {
    $rs = db()->prepare(
        "SELECT * FROM appointments
          WHERE id = ? AND patient_id = ? AND doctor_id = ?
            AND status IN ('pending','confirmed')"
    );
    $rs->execute([$rescheduleId, (int) $user['id'], $doctorId]);
    $reschedule = $rs->fetch() ?: null;
}

$days = doctor_open_days($doctorId, 14);
$date = query('date');

if (!isset($days[$date])) {
    $date = '';
    foreach ($days as $d => $label) {
        foreach (doctor_day_slots($doctorId, $d) as $slot) {
            if ($slot['available']) {
                $date = $d;
                break 2;
            }
        }
    }
    if ($date === '') {
        $date = array_key_first($days) ?: date('Y-m-d');
    }
}

$slots   = doctor_day_slots($doctorId, $date);
$hasFree = false;
foreach ($slots as $s) {
    if ($s['available']) { $hasFree = true; break; }
}

$rev = db()->prepare(
    'SELECT r.rating, r.comment, r.created_at, u.name AS patient_name
       FROM reviews r
       JOIN appointments a ON a.id = r.appointment_id
       JOIN users u ON u.id = a.patient_id
      WHERE a.doctor_id = ?
      ORDER BY r.created_at DESC LIMIT 6'
);
$rev->execute([$doctorId]);
$reviews = $rev->fetchAll();

$rating = doctor_rating($doctorId);
$hours  = weekly_availability($doctorId);

$pageTitle = $doctor['name'];
$layout    = 'public';
require __DIR__ . '/../includes/header.php';
?>

<section class="py-4 doctor-profile-hero">
    <div class="container">
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb mb-0" style="font-size:.8rem">
                <li class="breadcrumb-item"><a href="<?= e(url('index.php')) ?>">Home</a></li>
                <li class="breadcrumb-item"><a href="<?= e(url('patient/doctors.php')) ?>">Doctors</a></li>
                <li class="breadcrumb-item active"><?= e($doctor['name']) ?></li>
            </ol>
        </nav>

        <div class="doctor-profile-summary">
            <?= doctor_avatar($doctor, 'avatar-lg') ?>

            <div class="flex-grow-1">
                <h1 class="h3 mb-2 doctor-profile-name"><?= e($doctor['name']) ?></h1>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    <span class="specialty-tag"><?= e($doctor['specialty']) ?></span>
                    <span class="badge rounded-pill bg-success-subtle text-success-emphasis border border-success-subtle">
                        <?= icon('check', 12) ?> Verified
                    </span>
                    <?= star_html($rating['avg'], $rating['total']) ?>
                </div>

                <div class="doctor-profile-facts">
                    <span class="doctor-profile-fact"><?= icon('pin', 16) ?> <?= e($doctor['location']) ?></span>
                    <span class="doctor-profile-fact"><?= icon('stetho', 16) ?> <?= (int) $doctor['experience'] ?> years experience</span>
                    <span class="doctor-profile-fact doctor-profile-fact--fee"><?= icon('card', 16) ?> <?= e(money($doctor['fee'])) ?> per visit</span>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-4">
    <div class="container doctor-profile-content">
        <div class="row g-4">

            
            <div class="col-lg-7">
                <div class="card profile-section-card mb-4">
                    <div class="card-header">About</div>
                    <div class="card-body">
                        <p class="mb-0" style="white-space:pre-line">
                            <?= e($doctor['bio'] ?: 'This doctor has not written a bio yet.') ?>
                        </p>
                    </div>
                </div>

                <div class="card profile-section-card availability-card mb-4">
                    <div class="card-header">Weekly availability</div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table mb-0 availability-table">
                                <tbody>
                                <?php for ($d = 0; $d <= 6; $d++): ?>
                                    <tr>
                                        <td class="fw-semibold text-ink" style="width:130px"><?= e(day_name($d)) ?></td>
                                        <td>
                                            <?php if (empty($hours[$d])): ?>
                                                <span class="text-faint" style="font-size:.85rem">Closed</span>
                                            <?php else: ?>
                                                <?php foreach ($hours[$d] as $w): ?>
                                                    <span class="specialty-tag mb-1" style="background:#f8fafb;border-color:var(--mb-border);color:var(--mb-body)">
                                                        <?= e(fmt_time($w['start_time'])) ?> – <?= e(fmt_time($w['end_time'])) ?>
                                                        <span class="text-faint">· <?= (int) $w['slot_minutes'] ?> min</span>
                                                    </span>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endfor; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card profile-section-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Patient reviews</span>
                        <?php if ($rating['total'] > 0): ?>
                            <span class="text-muted" style="font-size:.8rem"><?= $rating['total'] ?> review<?= $rating['total'] === 1 ? '' : 's' ?></span>
                        <?php endif; ?>
                    </div>

                    <?php if (!$reviews): ?>
                        <div class="empty-state py-4">
                            <p class="mb-0" style="font-size:.875rem">No reviews yet. Be the first after your visit.</p>
                        </div>
                    <?php else: ?>
                        <div class="card-body d-flex flex-column gap-3">
                            <?php foreach ($reviews as $r): ?>
                                <div class="pb-3 border-bottom">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="avatar avatar-sm" style="width:30px;height:30px;font-size:.72rem;border-radius:8px">
                                                <?= e(initials($r['patient_name'])) ?>
                                            </span>
                                            <span class="fw-semibold text-ink" style="font-size:.85rem"><?= e($r['patient_name']) ?></span>
                                        </div>
                                        <span class="text-warning" style="font-size:.85rem">
                                            <?= str_repeat('★', (int) $r['rating']) ?><span class="text-secondary opacity-25"><?= str_repeat('★', 5 - (int) $r['rating']) ?></span>
                                        </span>
                                    </div>
                                    <?php if (!empty($r['comment'])): ?>
                                        <p class="mb-1" style="font-size:.875rem"><?= e($r['comment']) ?></p>
                                    <?php endif; ?>
                                    <div class="text-faint" style="font-size:.74rem"><?= e(time_ago($r['created_at'])) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            
            <div class="col-lg-5">
                <div class="card position-sticky booking-card" style="top:88px">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Book an appointment</span>
                        <span class="fee"><?= e(money($doctor['fee'])) ?></span>
                    </div>

                    <div class="card-body">
                        <?php if ($reschedule): ?>
                            <div class="alert alert-info py-2 mb-3" style="font-size:.82rem">
                                <?= icon('refresh', 15) ?>
                                Rescheduling your <?= e(fmt_date($reschedule['date'])) ?> appointment.
                                The old slot is released once you confirm the new one.
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($days)): ?>
                            <div class="small-caps mb-2">Choose a day</div>
                            <div class="day-strip mb-3">
                                <?php foreach ($days as $d => $label): ?>
                                    <a class="day-chip <?= $d === $date ? 'active' : '' ?>"
                                       href="<?= e(url('patient/doctor.php?id=' . $doctorId . '&date=' . $d . ($rescheduleId ? '&reschedule=' . $rescheduleId : ''))) ?>">
                                        <?= e($label) ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!$slots): ?>
                            <div class="alert alert-light border text-center mb-0" style="font-size:.85rem">
                                <?= e($doctor['name']) ?> has no availability on <?= e(fmt_date($date)) ?>.
                                <?= $days ? 'Pick another day above.' : 'Check back later.' ?>
                            </div>
                        <?php elseif (!$hasFree): ?>
                            <div class="alert alert-light border text-center mb-0" style="font-size:.85rem">
                                Every slot on <?= e(fmt_date($date)) ?> is taken. Try another day.
                            </div>
                        <?php else: ?>
                            <form method="post" action="<?= e(url('patient/book.php')) ?>" data-once>
                                <?= csrf_field() ?>
                                <input type="hidden" name="doctor_id" value="<?= $doctorId ?>">
                                <input type="hidden" name="date" value="<?= e($date) ?>">
                                <?php if ($rescheduleId): ?>
                                    <input type="hidden" name="reschedule_id" value="<?= $rescheduleId ?>">
                                <?php endif; ?>

                                <div class="small-caps mb-2">
                                    Slots for <?= e(fmt_date($date)) ?>
                                </div>

                                <div class="slot-grid mb-3">
                                    <?php foreach ($slots as $time => $info): ?>
                                        <label class="slot-btn <?= $info['available'] ? '' : 'is-taken' ?>"
                                               <?= $info['available'] ? '' : 'title="Already booked"' ?>>
                                            <input type="radio" name="time" value="<?= e($time) ?>"
                                                   data-label="<?= e(fmt_time($time)) ?>"
                                                   <?= $info['available'] ? '' : 'disabled' ?>
                                                   <?= $info['available'] ? '' : '' ?>>
                                            <?= e(fmt_time($time)) ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>

                                <?php if ($isPatient): ?>
                                    <div class="mb-3">
                                        <label class="form-label" for="reason">Reason for visit</label>
                                        <textarea class="form-control" id="reason" name="reason" rows="3" maxlength="500"
                                                  placeholder="Briefly describe your symptoms or what you need."></textarea>
                                        <div class="form-text">Optional, but it helps the doctor prepare.</div>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center mb-2" style="font-size:.82rem">
                                        <span class="text-muted">Selected slot</span>
                                        <span class="fw-bold text-ink" id="selectedSlotLabel">—</span>
                                    </div>

                                    <button class="btn btn-primary w-100" type="submit" id="bookSubmit" disabled>
                                        <?= $reschedule ? 'Confirm new slot' : 'Request booking' ?>
                                    </button>
                                    <p class="text-center text-muted mt-2 mb-0" style="font-size:.75rem">
                                        The doctor confirms manually — usually within a few hours.
                                    </p>
                                <?php else: ?>
                                    <div class="alert alert-light border mb-0" style="font-size:.85rem">
                                        <a class="fw-semibold" href="<?= e(url('auth/login.php')) ?>">Sign in as a patient</a>
                                        to request this slot.
                                    </div>
                                <?php endif; ?>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
