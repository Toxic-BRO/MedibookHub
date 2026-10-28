<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

$user = require_login('doctor');
$did  = (int) $user['id'];

$doc = doctor_detail($did);
if (!$doc) {
    flash('danger', 'Doctor profile not found.');
    redirect('index.php');
}

$errors   = [];
$pwErrors = [];

if (is_post()) {
    verify_csrf();
    $action = post('action');

    if ($action === 'profile') {
        $name       = post('name');
        $email      = post('email');
        $specialty  = post('specialty');
        $fee        = post('fee');
        $experience = post('experience');
        $location   = post('location');
        $bio        = mb_substr(post('bio'), 0, 2000);

        if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
            $errors['name'] = 'Enter your full name (2–100 characters).';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Enter a valid email address.';
        } else {
            $chk = db()->prepare('SELECT 1 FROM users WHERE email = ? AND id <> ?');
            $chk->execute([$email, $did]);
            if ($chk->fetchColumn()) {
                $errors['email'] = 'That email belongs to another account.';
            }
        }
        if (!in_array($specialty, common_specialties(), true)) {
            $errors['specialty'] = 'Choose a valid specialty.';
        }
        if (!is_numeric($fee) || (float) $fee < 0 || (float) $fee > 100000) {
            $errors['fee'] = 'Enter a fee of 0 or more.';
        }
        if (!ctype_digit($experience) || (int) $experience > 70) {
            $errors['experience'] = 'Enter your years of experience.';
        }
        if (mb_strlen($location) < 2 || mb_strlen($location) > 150) {
            $errors['location'] = 'Where do you practise?';
        }

        
        $newPhoto = null;
        $file = $_FILES['photo'] ?? null;
        if (is_array($file) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $errors['photo'] = 'The upload failed (error ' . (int) $file['error'] . '). Try a smaller file.';
            } elseif ($file['size'] > MAX_UPLOAD_BYTES) {
                $errors['photo'] = 'Keep the image under ' . round(MAX_UPLOAD_BYTES / 1048576, 1) . ' MB.';
            } else {
                $info = @getimagesize($file['tmp_name']);
                $allowed = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];

                if (!$info || !isset($allowed[$info[2]])) {
                    $errors['photo'] = 'Only JPG, PNG or WebP images are accepted.';
                } else {
                    if (!is_dir(UPLOAD_DIR)) {
                        @mkdir(UPLOAD_DIR, 0775, true);
                    }
                    $filename = bin2hex(random_bytes(10)) . '.' . $allowed[$info[2]];

                    if (move_uploaded_file($file['tmp_name'], UPLOAD_DIR . '/' . $filename)) {
                        $newPhoto = $filename;
                    } else {
                        $errors['photo'] = 'Could not save the image. Check folder permissions.';
                    }
                }
            }
        }

        if (!$errors) {
            $pdo = db();
            $pdo->beginTransaction();
            try {
                $pdo->prepare('UPDATE users SET name = ?, email = ? WHERE id = ?')
                    ->execute([$name, $email, $did]);

                if ($newPhoto !== null) {
                    $pdo->prepare(
                        'UPDATE doctors SET specialty = ?, fee = ?, experience = ?, location = ?, bio = ?, photo = ?
                          WHERE user_id = ?'
                    )->execute([$specialty, round((float) $fee, 2), (int) $experience, $location, $bio !== '' ? $bio : null, $newPhoto, $did]);
                } else {
                    $pdo->prepare(
                        'UPDATE doctors SET specialty = ?, fee = ?, experience = ?, location = ?, bio = ?
                          WHERE user_id = ?'
                    )->execute([$specialty, round((float) $fee, 2), (int) $experience, $location, $bio !== '' ? $bio : null, $did]);
                }

                $pdo->commit();

                
                if ($newPhoto !== null && !empty($doc['photo']) && is_file(UPLOAD_DIR . '/' . $doc['photo'])) {
                    @unlink(UPLOAD_DIR . '/' . $doc['photo']);
                }

                flash('success', 'Profile updated.');
                redirect('doctor/profile.php');
            } catch (Throwable $ex) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $errors['form'] = 'Could not save your profile. Please try again.';
            }
        }
    }

    if ($action === 'password') {
        $current = isset($_POST['current_password']) && is_string($_POST['current_password']) ? $_POST['current_password'] : '';
        $new     = isset($_POST['new_password']) && is_string($_POST['new_password']) ? $_POST['new_password'] : '';
        $confirm = isset($_POST['confirm_password']) && is_string($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

        $stmt = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
        $stmt->execute([$did]);
        $hash = (string) $stmt->fetchColumn();

        if (!password_verify($current, $hash)) {
            $pwErrors[] = 'Your current password is not correct.';
        }
        if (strlen($new) < 8 || !preg_match('/[A-Za-z]/', $new) || !preg_match('/\d/', $new)) {
            $pwErrors[] = 'The new password needs 8+ characters with a letter and a number.';
        }
        if ($new !== $confirm) {
            $pwErrors[] = 'The new passwords do not match.';
        }

        if (!$pwErrors) {
            db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                ->execute([password_hash($new, PASSWORD_DEFAULT), $did]);
            session_regenerate_id(true);
            flash('success', 'Password updated.');
            redirect('doctor/profile.php');
        }
    }
}

$pageTitle = 'My profile';
$pageSub   = 'This is what patients see before they book you.';
$layout    = 'dashboard';
$activeNav = 'profile';

require __DIR__ . '/../includes/header.php';
?>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">Profile details</div>
            <div class="card-body">
                <?php if (!empty($errors['form'])): ?>
                    <div class="alert alert-danger py-2" style="font-size:.85rem"><?= e($errors['form']) ?></div>
                <?php endif; ?>

                <form method="post" enctype="multipart/form-data" data-once>
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="profile">

                    <div class="d-flex flex-wrap align-items-center gap-3 mb-4 pb-3 border-bottom">
                        <?= doctor_avatar($doc, 'avatar-lg') ?>
                        <div class="flex-grow-1">
                            <label class="form-label" for="photo">Profile photo</label>
                            <input class="form-control <?= isset($errors['photo']) ? 'is-invalid' : '' ?>"
                                   type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/webp">
                            <?php if (isset($errors['photo'])): ?><div class="invalid-feedback"><?= e($errors['photo']) ?></div><?php endif; ?>
                            <div class="form-text">JPG, PNG or WebP, up to <?= round(MAX_UPLOAD_BYTES / 1048576, 1) ?> MB. Square images look best.</div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="name">Full name</label>
                            <input class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" id="name" name="name"
                                   value="<?= e($doc['name']) ?>" required>
                            <?php if (isset($errors['name'])): ?><div class="invalid-feedback"><?= e($errors['name']) ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="email">Email</label>
                            <input class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" type="email" id="email" name="email"
                                   value="<?= e($doc['email']) ?>" required>
                            <?php if (isset($errors['email'])): ?><div class="invalid-feedback"><?= e($errors['email']) ?></div><?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="specialty">Specialty</label>
                            <select class="form-select <?= isset($errors['specialty']) ? 'is-invalid' : '' ?>" id="specialty" name="specialty">
                                <?php foreach (common_specialties() as $sp): ?>
                                    <option value="<?= e($sp) ?>" <?= $doc['specialty'] === $sp ? 'selected' : '' ?>><?= e($sp) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['specialty'])): ?><div class="invalid-feedback"><?= e($errors['specialty']) ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="fee">Fee</label>
                            <div class="input-group">
                                <span class="input-group-text"><?= e(defined('CURRENCY_SYMBOL') ? CURRENCY_SYMBOL : '৳') ?></span>
                                <input class="form-control <?= isset($errors['fee']) ? 'is-invalid' : '' ?>" type="number" step="0.01" min="0"
                                       id="fee" name="fee" value="<?= e((string) $doc['fee']) ?>">
                                <?php if (isset($errors['fee'])): ?><div class="invalid-feedback"><?= e($errors['fee']) ?></div><?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="experience">Years exp.</label>
                            <input class="form-control <?= isset($errors['experience']) ? 'is-invalid' : '' ?>" type="number" min="0" max="70"
                                   id="experience" name="experience" value="<?= (int) $doc['experience'] ?>">
                            <?php if (isset($errors['experience'])): ?><div class="invalid-feedback"><?= e($errors['experience']) ?></div><?php endif; ?>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="location">Practice location</label>
                            <input class="form-control <?= isset($errors['location']) ? 'is-invalid' : '' ?>" id="location" name="location"
                                   value="<?= e($doc['location']) ?>">
                            <?php if (isset($errors['location'])): ?><div class="invalid-feedback"><?= e($errors['location']) ?></div><?php endif; ?>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="bio">Bio</label>
                            <textarea class="form-control" id="bio" name="bio" rows="6" maxlength="2000"><?= e($doc['bio'] ?? '') ?></textarea>
                            <div class="form-text">Plain language works best. Patients read this before booking.</div>
                        </div>
                    </div>

                    <button class="btn btn-primary mt-4" type="submit">Save profile</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-body" style="font-size:.85rem">
                <div class="d-flex justify-content-between py-1">
                    <span class="text-muted">Verification</span>
                    <?php if ((int) $doc['is_verified'] === 1): ?>
                        <span class="badge rounded-pill bg-success">Verified</span>
                    <?php else: ?>
                        <span class="badge rounded-pill bg-warning text-dark">Pending</span>
                    <?php endif; ?>
                </div>
                <div class="d-flex justify-content-between py-1">
                    <span class="text-muted">Rating</span>
                    <span class="fw-semibold text-ink">
                        <?php $rt = doctor_rating($did); ?>
                        <?= $rt['total'] > 0 ? number_format($rt['avg'], 1) . ' / 5 (' . $rt['total'] . ')' : 'No reviews' ?>
                    </span>
                </div>
                <a class="btn btn-sm btn-soft w-100 mt-3" href="<?= e(url('patient/doctor.php?id=' . $did)) ?>">
                    <?= icon('eye', 14) ?> View public profile
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Change password</div>
            <div class="card-body">
                <?php foreach ($pwErrors as $err): ?>
                    <div class="alert alert-danger py-2" style="font-size:.85rem"><?= e($err) ?></div>
                <?php endforeach; ?>

                <form method="post" data-once>
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="password">

                    <div class="mb-3">
                        <label class="form-label" for="current_password">Current password</label>
                        <input class="form-control" type="password" id="current_password" name="current_password" required autocomplete="current-password">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="new_password">New password</label>
                        <input class="form-control" type="password" id="new_password" name="new_password" required autocomplete="new-password">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="confirm_password">Confirm</label>
                        <input class="form-control" type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password">
                    </div>

                    <button class="btn btn-outline-primary w-100" type="submit">Update password</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
