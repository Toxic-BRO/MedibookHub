<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

if (is_logged_in()) {
    redirect(dashboard_url(auth_user()['role']));
}

$errors = [];
$email  = '';

$throttle = $_SESSION['login_throttle'] ?? ['count' => 0, 'until' => 0];
$lockedOut = (int) $throttle['until'] > time();

if (is_post()) {
    verify_csrf();

    $email    = post('email');
    $password = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';

    if ($lockedOut) {
        $wait    = (int) ceil(((int) $throttle['until'] - time()) / 60);
        $errors[] = 'Too many failed attempts. Try again in about ' . $wait . ' minute(s).';
    } elseif ($email === '' || $password === '') {
        $errors[] = 'Enter both your email and password.';
    } else {
        $stmt = db()->prepare('SELECT id, name, email, password_hash, role, status FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        
        if (!$user || !password_verify($password, $user['password_hash'])) {
            $throttle['count'] = (int) $throttle['count'] + 1;
            if ($throttle['count'] >= 5) {
                $throttle['until'] = time() + 600;
                $throttle['count'] = 0;
                $errors[] = 'Too many failed attempts. Locked for 10 minutes.';
            } else {
                $errors[] = 'Those credentials do not match our records.';
            }
            $_SESSION['login_throttle'] = $throttle;
        } elseif ($user['status'] === 'blocked') {
            $errors[] = 'This account has been blocked. Please contact the administrator.';
        } else {
            
            if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
                $up = db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
                $up->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);
            }

            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['id'];
            unset($_SESSION['login_throttle']);

            $intended = $_SESSION['intended'] ?? null;
            unset($_SESSION['intended']);

            flash('success', 'Welcome back, ' . explode(' ', $user['name'])[0] . '.');

            
            if (is_string($intended) && str_contains($intended, '/' . $user['role'] . '/')) {
                redirect($intended);
            }
            redirect(dashboard_url($user['role']));
        }
    }
}

$pageTitle = 'Sign in';
$layout    = 'public';
require __DIR__ . '/../includes/header.php';
?>

<section class="py-5">
    <div class="container" style="max-width:520px">
        <div class="row g-0 card overflow-hidden" style="box-shadow:var(--mb-shadow-lg)">

            <div class="col-12 p-4 p-lg-5">
                <h1 class="h3 mb-1">Welcome back</h1>
                <p class="text-muted mb-4" style="font-size:.9rem">Sign in to manage your appointments.</p>

                <?php foreach ($errors as $err): ?>
                    <div class="alert alert-danger py-2" style="font-size:.875rem"><?= e($err) ?></div>
                <?php endforeach; ?>

                <form method="post" novalidate>
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label" for="email">Email address</label>
                        <input class="form-control" type="email" id="email" name="email"
                               value="<?= e($email) ?>" required autofocus autocomplete="email">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="password">Password</label>
                        <div class="input-group">
                            <input class="form-control" type="password" id="password" name="password"
                                   required autocomplete="current-password">
                            <button class="btn btn-outline-secondary" type="button"
                                    data-toggle-password="password" aria-label="Show password">
                                <?= icon('eye', 17) ?>
                            </button>
                        </div>
                    </div>

                    <button class="btn btn-primary w-100" type="submit" <?= $lockedOut ? 'disabled' : '' ?>>
                        Sign in
                    </button>
                </form>

                <div class="divider-label my-4">or</div>

                <p class="text-center mb-0" style="font-size:.875rem">
                    New here? <a href="<?= e(url('auth/register.php')) ?>" class="fw-semibold">Create an account</a>
                </p>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
