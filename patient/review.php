<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

$user = require_login('patient');
$pid  = (int) $user['id'];
$id   = query_int('id', post_int('id'));

$stmt = db()->prepare(
    "SELECT a.*, u.name AS doctor_name, d.specialty, r.id AS review_id
       FROM appointments a
       JOIN users u   ON u.id = a.doctor_id
       JOIN doctors d ON d.user_id = a.doctor_id
       LEFT JOIN reviews r ON r.appointment_id = a.id
      WHERE a.id = ? AND a.patient_id = ?"
);
$stmt->execute([$id, $pid]);
$appt = $stmt->fetch();

if (!$appt) {
    flash('danger', 'That appointment does not exist.');
    redirect('patient/appointments.php');
}
if ($appt['status'] !== 'completed') {
    flash('warning', 'You can only review a visit after it has been completed.');
    redirect('patient/appointments.php');
}
if ($appt['review_id'] !== null) {
    flash('info', 'You already reviewed this visit. Thank you.');
    redirect('patient/appointments.php');
}

$errors = [];
$rating = 0;

if (is_post()) {
    verify_csrf();

    $rating = post_int('rating');

    if ($rating < 1 || $rating > 5) {
        $errors[] = 'Pick a rating between 1 and 5 stars.';
    }

    if (!$errors) {
        try {
            db()->prepare('INSERT INTO reviews (appointment_id, rating) VALUES (?, ?)')
                ->execute([$id, $rating]);

            notify(
                (int) $appt['doctor_id'],
                $user['name'] . ' left a ' . $rating . '-star review.',
                '/doctor/dashboard.php'
            );

            flash('success', 'Thanks — your review is live on ' . $appt['doctor_name'] . "'s profile.");
            redirect('patient/appointments.php');
        } catch (PDOException $ex) {
            if ($ex->getCode() === '23000') {
                flash('info', 'You already reviewed this visit.');
                redirect('patient/appointments.php');
            }
            $errors[] = 'Could not save the review. Please try again.';
        }
    }
}

$pageTitle = 'Rate your visit';
$pageSub   = 'Your feedback helps other patients choose well.';
$layout    = 'dashboard';
$activeNav = 'appointments';

require __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-body p-4">

                <div class="d-flex align-items-center gap-3 mb-4">
                    <div class="avatar"><?= e(initials($appt['doctor_name'])) ?></div>
                    <div>
                        <div class="fw-bold text-ink"><?= e($appt['doctor_name']) ?></div>
                        <div class="text-muted" style="font-size:.82rem">
                            <?= e($appt['specialty']) ?> · <?= e(fmt_date($appt['date'])) ?>
                        </div>
                    </div>
                </div>

                <?php foreach ($errors as $err): ?>
                    <div class="alert alert-danger py-2" style="font-size:.875rem"><?= e($err) ?></div>
                <?php endforeach; ?>

                <form method="post" data-once>
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int) $appt['id'] ?>">

                    <div class="mb-3">
                        <label class="form-label d-block">How was your visit?</label>
                        <div class="rating-input" data-output="ratingOut">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <input type="radio" name="rating" id="star<?= $i ?>" value="<?= $i ?>"
                                       <?= $rating === $i ? 'checked' : '' ?> required>
                                <label for="star<?= $i ?>" title="<?= $i ?> star<?= $i === 1 ? '' : 's' ?>">★</label>
                            <?php endfor; ?>
                        </div>
                        <div class="form-text">Selected: <span id="ratingOut"><?= $rating ? $rating . ' / 5' : 'none yet' ?></span></div>
                    </div>

                    <div class="d-flex gap-2">
                        <button class="btn btn-primary" type="submit">Publish review</button>
                        <a class="btn btn-light border" href="<?= e(url('patient/appointments.php')) ?>">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
