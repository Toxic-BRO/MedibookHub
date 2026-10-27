<?php
/**
 * auth/register.php — patient or doctor sign-up.
 *
 * Doctor accounts are created with status 'pending' and is_verified = 0:
 * they can sign in, but they are invisible to patients and cannot take
 * bookings until an admin verifies them.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

if (is_logged_in()) {
    redirect(dashboard_url(auth_user()['role']));
}

$role   = in_array(query('role'), ['patient', 'doctor'], true) ? query('role') : 'patient';
$errors = [];

$form = [
    'name'     => '',
    'email'    => '',
    'age'      => '',
    'gender'   => '',
    'phone'    => '',
    'address'  => '',
    'specialty' => '',
    'fee'      => '',
    'experience' => '',
    'location' => '',
    'bio'      => '',
];

if (is_post()) {
    verify_csrf();

    $role = in_array(post('role'), ['patient', 'doctor'], true) ? post('role') : 'patient';

    foreach ($form as $key => $_) {
        $form[$key] = post($key);
    }

    $name     = $form['name'];
    $email    = $form['email'];
    $password = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';
    $confirm  = isset($_POST['password_confirm']) && is_string($_POST['password_confirm']) ? $_POST['password_confirm'] : '';

    /* ---- shared validation ---- */
    if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
        $errors['name'] = 'Enter your full name (2–100 characters).';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
        $errors['email'] = 'Enter a valid email address.';
    } else {
        $check = db()->prepare('SELECT 1 FROM users WHERE email = ?');
        $check->execute([$email]);
        if ($check->fetchColumn()) {
            $errors['email'] = 'That email is already registered. Try signing in instead.';
        }
    }
    if (strlen($password) < 8) {
        $errors['password'] = 'Use at least 8 characters.';
    } elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
        $errors['password'] = 'Include at least one letter and one number.';
    }
    if ($password !== $confirm) {
        $errors['password_confirm'] = 'The two passwords do not match.';
    }

    /* ---- role-specific validation ---- */
    $age = null;
    if ($role === 'patient') {
        if ($form['age'] !== '') {
            if (!ctype_digit($form['age']) || (int) $form['age'] < 0 || (int) $form['age'] > 120) {
                $errors['age'] = 'Age must be a whole number between 0 and 120.';
            } else {
                $age = (int) $form['age'];
            }
        }
        if ($form['gender'] !== '' && !in_array($form['gender'], ['male', 'female', 'other'], true)) {
            $errors['gender'] = 'Pick a valid option.';
        }
        if ($form['phone'] !== '' && !preg_match('/^[0-9 +()\-]{6,30}$/', $form['phone'])) {
            $errors['phone'] = 'That phone number does not look right.';
        }
        if (mb_strlen($form['address']) > 255) {
            $errors['address'] = 'Keep the address under 255 characters.';
        }
    } else {
        if (!in_array($form['specialty'], common_specialties(), true)) {
            $errors['specialty'] = 'Choose your specialty.';
        }
        if ($form['fee'] === '' || !is_numeric($form['fee']) || (float) $form['fee'] < 0 || (float) $form['fee'] > 100000) {
            $errors['fee'] = 'Enter a consultation fee (0 or more).';
        }
        if ($form['experience'] === '' || !ctype_digit($form['experience']) || (int) $form['experience'] > 70) {
            $errors['experience'] = 'Enter your years of experience.';
        }
        if (mb_strlen($form['location']) < 2 || mb_strlen($form['location']) > 150) {
            $errors['location'] = 'Tell patients where you practise.';
        }
        if (mb_strlen($form['bio']) > 2000) {
            $errors['bio'] = 'Keep the bio under 2000 characters.';
        }
    }

    if (!$errors) {
        $pdo = db();

        try {
            $pdo->beginTransaction();

            $ins = $pdo->prepare(
                'INSERT INTO users (name, email, password_hash, role, status) VALUES (?, ?, ?, ?, ?)'
            );
            $ins->execute([
                $name,
                $email,
                password_hash($password, PASSWORD_DEFAULT),
                $role,
                $role === 'doctor' ? 'pending' : 'active',
            ]);
            $newId = (int) $pdo->lastInsertId();

            if ($role === 'patient') {
                $pdo->prepare(
                    'INSERT INTO patients (user_id, age, gender, phone, address) VALUES (?, ?, ?, ?, ?)'
                )->execute([
                    $newId,
                    $age,
                    $form['gender'] !== '' ? $form['gender'] : null,
                    $form['phone'] !== '' ? $form['phone'] : null,
                    $form['address'] !== '' ? $form['address'] : null,
                ]);
            } else {
                $pdo->prepare(
                    'INSERT INTO doctors (user_id, specialty, fee, experience, bio, location, is_verified)
                     VALUES (?, ?, ?, ?, ?, ?, 0)'
                )->execute([
                    $newId,
                    $form['specialty'],
                    round((float) $form['fee'], 2),
                    (int) $form['experience'],
                    $form['bio'] !== '' ? $form['bio'] : null,
                    $form['location'],
                ]);
            }

            $pdo->commit();

            // Tell every admin there is something to review.
            if ($role === 'doctor') {
                $admins = $pdo->query("SELECT id FROM users WHERE role = 'admin'")->fetchAll(PDO::FETCH_COLUMN);
                foreach ($admins as $adminId) {
                    notify((int) $adminId, 'New doctor application from ' . $name . ' (' . $form['specialty'] . ') awaiting verification.', '/admin/doctors.php', false);
                }
            }

            session_regenerate_id(true);
            $_SESSION['user_id'] = $newId;

            if ($role === 'doctor') {
                flash('info', 'Account created. An administrator will verify it shortly — you can set up your availability in the meantime.');
            } else {
                flash('success', 'Welcome to ' . APP_NAME . ', ' . explode(' ', $name)[0] . '. Your account is ready.');
            }
            redirect(dashboard_url($role));
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors['form'] = 'Something went wrong creating the account. Please try again.';
        }
    }
}

$pageTitle = 'Create your account';
$layout    = 'public';
require __DIR__ . '/../includes/header.php';
?>

<section class="py-5">
    <div class="container" style="max-width:760px">

        <div class="text-center mb-4">
            <h1 class="h3 mb-1">Create your account</h1>
            <p class="text-muted mb-0" style="font-size:.9rem">Patients book instantly. Doctors go live after a quick verification.</p>
        </div>

        <div class="card" style="box-shadow:var(--mb-shadow-lg)">
            <div class="card-body p-4 p-lg-5">

                <?php if (!empty($errors['form'])): ?>
                    <div class="alert alert-danger py-2" style="font-size:.875rem"><?= e($errors['form']) ?></div>
                <?php endif; ?>

                <form method="post" novalidate data-once>
                    <?= csrf_field() ?>
                    <input type="hidden" name="role" id="roleField" value="<?= e($role) ?>">

                    <div class="row g-2 mb-4">
                        <div class="col-6">
                            <button type="button" class="btn w-100 role-btn <?= $role === 'patient' ? 'btn-primary' : 'btn-outline-primary' ?>"
                                    data-role="patient">
                                <?= icon('user', 17) ?> I'm a patient
                            </button>
                        </div>
                        <div class="col-6">
                            <button type="button" class="btn w-100 role-btn <?= $role === 'doctor' ? 'btn-primary' : 'btn-outline-primary' ?>"
                                    data-role="doctor">
                                <?= icon('stetho', 17) ?> I'm a doctor
                            </button>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="name">Full name</label>
                            <input class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" id="name" name="name"
                                   value="<?= e($form['name']) ?>" required>
                            <?php if (isset($errors['name'])): ?><div class="invalid-feedback"><?= e($errors['name']) ?></div><?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="email">Email address</label>
                            <input class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" type="email" id="email" name="email"
                                   value="<?= e($form['email']) ?>" required autocomplete="email">
                            <?php if (isset($errors['email'])): ?><div class="invalid-feedback"><?= e($errors['email']) ?></div><?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="password">Password</label>
                            <div class="input-group">
                                <input class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" type="password"
                                       id="password" name="password" required autocomplete="new-password">
                                <button class="btn btn-outline-secondary" type="button" data-toggle-password="password" aria-label="Show password">
                                    <?= icon('eye', 17) ?>
                                </button>
                                <?php if (isset($errors['password'])): ?><div class="invalid-feedback"><?= e($errors['password']) ?></div><?php endif; ?>
                            </div>
                            <div class="form-text">At least 8 characters, with a letter and a number.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="password_confirm">Confirm password</label>
                            <input class="form-control <?= isset($errors['password_confirm']) ? 'is-invalid' : '' ?>" type="password"
                                   id="password_confirm" name="password_confirm" required autocomplete="new-password">
                            <?php if (isset($errors['password_confirm'])): ?><div class="invalid-feedback"><?= e($errors['password_confirm']) ?></div><?php endif; ?>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- patient-only fields -->
                    <div class="role-fields" data-role-fields="patient" <?= $role === 'doctor' ? 'hidden' : '' ?>>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" for="age">Age <span class="text-faint fw-normal">(optional)</span></label>
                                <input class="form-control <?= isset($errors['age']) ? 'is-invalid' : '' ?>" type="number" min="0" max="120"
                                       id="age" name="age" value="<?= e($form['age']) ?>">
                                <?php if (isset($errors['age'])): ?><div class="invalid-feedback"><?= e($errors['age']) ?></div><?php endif; ?>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="gender">Gender</label>
                                <select class="form-select <?= isset($errors['gender']) ? 'is-invalid' : '' ?>" id="gender" name="gender">
                                    <option value="">Prefer not to say</option>
                                    <option value="male"   <?= $form['gender'] === 'male' ? 'selected' : '' ?>>Male</option>
                                    <option value="female" <?= $form['gender'] === 'female' ? 'selected' : '' ?>>Female</option>
                                    <option value="other"  <?= $form['gender'] === 'other' ? 'selected' : '' ?>>Other</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="phone">Phone</label>
                                <input class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>" id="phone" name="phone"
                                       value="<?= e($form['phone']) ?>" placeholder="+1 555 0100">
                                <?php if (isset($errors['phone'])): ?><div class="invalid-feedback"><?= e($errors['phone']) ?></div><?php endif; ?>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="address">Address <span class="text-faint fw-normal">(optional)</span></label>
                                <input class="form-control <?= isset($errors['address']) ? 'is-invalid' : '' ?>" id="address" name="address"
                                       value="<?= e($form['address']) ?>">
                                <?php if (isset($errors['address'])): ?><div class="invalid-feedback"><?= e($errors['address']) ?></div><?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- doctor-only fields -->
                    <div class="role-fields" data-role-fields="doctor" <?= $role === 'patient' ? 'hidden' : '' ?>>
                        <div class="alert alert-warning py-2 d-flex gap-2 align-items-start" style="font-size:.82rem">
                            <?= icon('shield', 16) ?>
                            <span>Doctor accounts stay <strong>pending</strong> until an administrator verifies them. You can sign in and set up your schedule right away.</span>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="specialty">Specialty</label>
                                <select class="form-select <?= isset($errors['specialty']) ? 'is-invalid' : '' ?>" id="specialty" name="specialty">
                                    <option value="">Choose…</option>
                                    <?php foreach (common_specialties() as $sp): ?>
                                        <option value="<?= e($sp) ?>" <?= $form['specialty'] === $sp ? 'selected' : '' ?>><?= e($sp) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['specialty'])): ?><div class="invalid-feedback"><?= e($errors['specialty']) ?></div><?php endif; ?>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="fee">Fee</label>
                                <div class="input-group">
                                    <span class="input-group-text"><?= e(defined('CURRENCY_SYMBOL') ? CURRENCY_SYMBOL : '৳') ?></span>
                                    <input class="form-control <?= isset($errors['fee']) ? 'is-invalid' : '' ?>" type="number" step="0.01" min="0"
                                           id="fee" name="fee" value="<?= e($form['fee']) ?>">
                                    <?php if (isset($errors['fee'])): ?><div class="invalid-feedback"><?= e($errors['fee']) ?></div><?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="experience">Years exp.</label>
                                <input class="form-control <?= isset($errors['experience']) ? 'is-invalid' : '' ?>" type="number" min="0" max="70"
                                       id="experience" name="experience" value="<?= e($form['experience']) ?>">
                                <?php if (isset($errors['experience'])): ?><div class="invalid-feedback"><?= e($errors['experience']) ?></div><?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="location">Practice location</label>
                                <input class="form-control <?= isset($errors['location']) ? 'is-invalid' : '' ?>" id="location" name="location"
                                       value="<?= e($form['location']) ?>" placeholder="City, State">
                                <?php if (isset($errors['location'])): ?><div class="invalid-feedback"><?= e($errors['location']) ?></div><?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="bio">Short bio <span class="text-faint fw-normal">(optional)</span></label>
                                <textarea class="form-control <?= isset($errors['bio']) ? 'is-invalid' : '' ?>" id="bio" name="bio" rows="2"
                                          placeholder="What do you treat? How do you work?"><?= e($form['bio']) ?></textarea>
                                <?php if (isset($errors['bio'])): ?><div class="invalid-feedback"><?= e($errors['bio']) ?></div><?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <button class="btn btn-primary w-100 mt-4" type="submit">Create account</button>

                    <p class="text-center text-muted mt-3 mb-0" style="font-size:.85rem">
                        Already registered? <a href="<?= e(url('auth/login.php')) ?>" class="fw-semibold">Sign in</a>
                    </p>
                </form>
            </div>
        </div>
    </div>
</section>

<script>
(function () {
    var field = document.getElementById('roleField');
    document.querySelectorAll('.role-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var role = btn.dataset.role;
            field.value = role;

            document.querySelectorAll('.role-btn').forEach(function (b) {
                var on = b.dataset.role === role;
                b.classList.toggle('btn-primary', on);
                b.classList.toggle('btn-outline-primary', !on);
            });

            document.querySelectorAll('.role-fields').forEach(function (group) {
                group.hidden = group.dataset.roleFields !== role;
            });
        });
    });
})();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
