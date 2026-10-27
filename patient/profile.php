<?php


declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

$user = require_login('patient');
$pid  = (int) $user['id'];

$row = db()->prepare(
    'SELECT u.name, u.email, p.age, p.gender, p.phone, p.address
       FROM users u LEFT JOIN patients p ON p.user_id = u.id
      WHERE u.id = ?'
);
$row->execute([$pid]);
$profile = $row->fetch();

$errors  = [];
$pwErrors = [];

if (is_post()) {
    verify_csrf();
    $action = post('action');

    
    if ($action === 'details') {
        $name    = post('name');
        $email   = post('email');
        $age     = post('age');
        $gender  = post('gender');
        $phone   = post('phone');
        $address = post('address');

        if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
            $errors['name'] = 'Enter your full name (2–100 characters).';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Enter a valid email address.';
        } else {
            $chk = db()->prepare('SELECT 1 FROM users WHERE email = ? AND id <> ?');
            $chk->execute([$email, $pid]);
            if ($chk->fetchColumn()) {
                $errors['email'] = 'That email belongs to another account.';
            }
        }
        $ageVal = null;
        if ($age !== '') {
            if (!ctype_digit($age) || (int) $age > 120) {
                $errors['age'] = 'Age must be between 0 and 120.';
            } else {
                $ageVal = (int) $age;
            }
        }
        if ($gender !== '' && !in_array($gender, ['male', 'female', 'other'], true)) {
            $errors['gender'] = 'Pick a valid option.';
        }
        if ($phone !== '' && !preg_match('/^[0-9 +()\-]{6,30}$/', $phone)) {
            $errors['phone'] = 'That phone number does not look right.';
        }

        if (!$errors) {
            $pdo = db();
            $pdo->beginTransaction();
            try {
                $pdo->prepare('UPDATE users SET name = ?, email = ? WHERE id = ?')
                    ->execute([$name, $email, $pid]);

                $exists = $pdo->prepare('SELECT 1 FROM patients WHERE user_id = ?');
                $exists->execute([$pid]);

                if ($exists->fetchColumn()) {
                    $pdo->prepare('UPDATE patients SET age = ?, gender = ?, phone = ?, address = ? WHERE user_id = ?')
                        ->execute([$ageVal, $gender !== '' ? $gender : null, $phone !== '' ? $phone : null, $address !== '' ? $address : null, $pid]);
                } else {
                    $pdo->prepare('INSERT INTO patients (user_id, age, gender, phone, address) VALUES (?, ?, ?, ?, ?)')
                        ->execute([$pid, $ageVal, $gender !== '' ? $gender : null, $phone !== '' ? $phone : null, $address !== '' ? $address : null]);
                }

                $pdo->commit();
                flash('success', 'Your details were updated.');
                redirect('patient/profile.php');
            } catch (Throwable $ex) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $errors['form'] = 'Could not save your details. Please try again.';
            }
        }

        $profile = array_merge($profile, [
            'name' => $name, 'email' => $email, 'age' => $age,
            'gender' => $gender, 'phone' => $phone, 'address' => $address,
        ]);
    }

    
    if ($action === 'password') {
        $current = isset($_POST['current_password']) && is_string($_POST['current_password']) ? $_POST['current_password'] : '';
        $new     = isset($_POST['new_password']) && is_string($_POST['new_password']) ? $_POST['new_password'] : '';
        $confirm = isset($_POST['confirm_password']) && is_string($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

        $stmt = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
        $stmt->execute([$pid]);
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
        if ($new !== '' && $new === $current) {
            $pwErrors[] = 'Choose a password different from your current one.';
        }

        if (!$pwErrors) {
            db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                ->execute([password_hash($new, PASSWORD_DEFAULT), $pid]);

            session_regenerate_id(true);
            flash('success', 'Password changed. Use it next time you sign in.');
            redirect('patient/profile.php');
        }
    }
}

$pageTitle = 'My profile';
$pageSub   = 'Keep your contact details current so doctors can reach you.';
$layout    = 'dashboard';
$activeNav = 'profile';

require __DIR__ . '/../includes/header.php';
?>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">Personal details</div>
            <div class="card-body">
                <?php if (!empty($errors['form'])): ?>
                    <div class="alert alert-danger py-2" style="font-size:.85rem"><?= e($errors['form']) ?></div>
                <?php endif; ?>

                <form method="post" data-once>
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="details">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="name">Full name</label>
                            <input class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" id="name" name="name"
                                   value="<?= e($profile['name']) ?>" required>
                            <?php if (isset($errors['name'])): ?><div class="invalid-feedback"><?= e($errors['name']) ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="email">Email</label>
                            <input class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" type="email" id="email" name="email"
                                   value="<?= e($profile['email']) ?>" required>
                            <?php if (isset($errors['email'])): ?><div class="invalid-feedback"><?= e($errors['email']) ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="age">Age</label>
                            <input class="form-control <?= isset($errors['age']) ? 'is-invalid' : '' ?>" type="number" min="0" max="120"
                                   id="age" name="age" value="<?= e($profile['age'] ?? '') ?>">
                            <?php if (isset($errors['age'])): ?><div class="invalid-feedback"><?= e($errors['age']) ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="gender">Gender</label>
                            <select class="form-select" id="gender" name="gender">
                                <option value="">Prefer not to say</option>
                                <?php foreach (['male' => 'Male', 'female' => 'Female', 'other' => 'Other'] as $val => $label): ?>
                                    <option value="<?= $val ?>" <?= ($profile['gender'] ?? '') === $val ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="phone">Phone</label>
                            <input class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>" id="phone" name="phone"
                                   value="<?= e($profile['phone'] ?? '') ?>">
                            <?php if (isset($errors['phone'])): ?><div class="invalid-feedback"><?= e($errors['phone']) ?></div><?php endif; ?>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="address">Address</label>
                            <input class="form-control" id="address" name="address" value="<?= e($profile['address'] ?? '') ?>">
                        </div>
                    </div>

                    <button class="btn btn-primary mt-4" type="submit">Save changes</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
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
                        <div class="form-text">8+ characters, with a letter and a number.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="confirm_password">Confirm new password</label>
                        <input class="form-control" type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password">
                    </div>

                    <button class="btn btn-outline-primary w-100" type="submit">Update password</button>
                </form>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-body" style="font-size:.82rem">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Member since</span>
                    <span class="fw-semibold text-ink"><?= e(fmt_date($user['created_at'])) ?></span>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-muted">Account status</span>
                    <span class="badge rounded-pill bg-success">Active</span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
