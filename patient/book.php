<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

$user = require_login('patient');

if (!is_post()) {
    redirect('patient/doctors.php');
}

verify_csrf();

$patientId    = (int) $user['id'];
$doctorId     = post_int('doctor_id');
$date         = post('date');
$time         = post('time');
$reason       = mb_substr(post('reason'), 0, 500);
$rescheduleId = post_int('reschedule_id');

$backTo = url('patient/doctor.php?id=' . $doctorId . '&date=' . urlencode($date));

$fail = static function (string $msg) use ($backTo): never {
    flash('danger', $msg);
    redirect($backTo);
};

$doctor = doctor_detail($doctorId);
if (!$doctor || !(int) $doctor['is_verified'] || $doctor['status'] !== 'active') {
    flash('danger', 'That doctor is not accepting appointments.');
    redirect('patient/doctors.php');
}

$old = null;
if ($rescheduleId > 0) {
    $stmt = db()->prepare(
        "SELECT * FROM appointments
          WHERE id = ? AND patient_id = ? AND doctor_id = ?
            AND status IN ('pending','confirmed')"
    );
    $stmt->execute([$rescheduleId, $patientId, $doctorId]);
    $old = $stmt->fetch() ?: null;

    if (!$old) {
        $fail('That appointment can no longer be rescheduled.');
    }
    if ($old['date'] === $date && substr((string) $old['time'], 0, 5) === substr($time, 0, 5)) {
        $fail('That is the same slot you already have. Pick a different time.');
    }
}

if (!slot_is_bookable($doctorId, $date, $time)) {
    $fail('Sorry — that slot was just taken or is no longer available. Please pick another.');
}

$time = substr($time, 0, 5) . ':00';

$pdo = db();
try {
    $pdo->beginTransaction();

    if ($old) {
    
        $pdo->prepare("UPDATE appointments SET status = 'cancelled' WHERE id = ?")
            ->execute([(int) $old['id']]);
    }

    $ins = $pdo->prepare(
        "INSERT INTO appointments (patient_id, doctor_id, `date`, `time`, status, reason)
         VALUES (?, ?, ?, ?, 'pending', ?)"
    );
    $ins->execute([$patientId, $doctorId, $date, $time, $reason !== '' ? $reason : null]);
    $newId = (int) $pdo->lastInsertId();

    $pdo->commit();
} catch (PDOException $ex) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    
    if ($ex->getCode() === '23000') {
        $fail('Someone else booked that slot a moment before you. Please choose another time.');
    }
    $fail('We could not save that booking. Please try again.');
}

notify(
    $doctorId,
    $user['name'] . ' requested ' . fmt_date($date) . ' at ' . fmt_time($time) . '.',
    '/doctor/appointments.php'
);

if ($old) {
    flash('success', 'Rescheduled to ' . fmt_date($date) . ' at ' . fmt_time($time) . '. Waiting for the doctor to confirm.');
} else {
    flash('success', 'Booking requested for ' . fmt_date($date) . ' at ' . fmt_time($time) . '. You will be notified once ' . $doctor['name'] . ' confirms.');
}

redirect('patient/appointments.php');
