<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

$pageTitle = $pageTitle ?? APP_NAME;
$pageSub   = $pageSub   ?? '';
$layout    = $layout    ?? 'public';
$activeNav = $activeNav ?? '';
$bodyClass = $bodyClass ?? '';

$user     = auth_user();
$__unread = 0;
$__notifs = [];

if ($user) {
    $__unread = unread_notifications((int) $user['id']);
    $__stmt   = db()->prepare(
        'SELECT id, message, link, is_read, created_at
           FROM notifications WHERE user_id = ?
          ORDER BY created_at DESC, id DESC LIMIT 6'
    );
    $__stmt->execute([(int) $user['id']]);
    $__notifs = $__stmt->fetchAll();
}

$__navMap = [
    'patient' => [
        ['dashboard.php',         'Dashboard',           'grid',     ''],
        ['symptom-checker.php',   'AI Health Assistant', 'stetho',   ''],
        ['doctors.php',           'Find a doctor',       'search',   ''],
        ['appointments.php',      'My appointments',     'calendar', ''],
        ['prescriptions.php',     'Prescriptions',       'file',     ''],
        ['complaints.php',        'Complaints',          'chat',     ''],
        ['profile.php',           'My profile',          'user',     ''],
    ],
    'doctor' => [
        ['dashboard.php',    'Dashboard',       'grid',      ''],
        ['appointments.php', 'Appointments',    'calendar',  'pendingRequests'],
        ['calendar.php',     'Calendar',        'grid',      ''],
        ['patients.php',     'My patients',     'users',     ''],
        ['availability.php', 'Availability',    'sliders',   ''],
        ['profile.php',      'My profile',      'user',      ''],
    ],
    'admin' => [
        ['dashboard.php',  'Dashboard',   'grid',     ''],
        ['doctors.php',    'Doctors',     'stetho',   'pendingDoctors'],
        ['users.php',      'Users',       'users',    ''],
        ['reports.php',    'Reports',     'chart',    ''],
        ['complaints.php', 'Complaints',  'chat',     'openComplaints'],
    ],
];

$__navBadges = [];
if ($user) {
    if ($user['role'] === 'doctor') {
        $__q = db()->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND status = 'pending'");
        $__q->execute([(int) $user['id']]);
        $__navBadges['pendingRequests'] = (int) $__q->fetchColumn();
    }
    if ($user['role'] === 'admin') {
        $__navBadges['pendingDoctors'] = (int) db()
            ->query("SELECT COUNT(*) FROM users WHERE role = 'doctor' AND status = 'pending'")
            ->fetchColumn();
        $__navBadges['openComplaints'] = (int) db()
            ->query("SELECT COUNT(*) FROM complaints WHERE status = 'open'")
            ->fetchColumn();
    }
}
ob_start('translate_html');
?>
<!doctype html>
<html lang="<?= e(app_language()) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(app_translation($pageTitle)) ?> · <?= e(APP_NAME) ?></title>
<meta name="description" content="<?= e(APP_NAME) ?> — book appointments directly with your doctor. No receptionist, no waiting on hold.">
<link href="<?= e(url('assets/vendor/bootstrap.min.css')) ?>" rel="stylesheet">
<link href="<?= e(url('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body class="<?= e($bodyClass) ?>">

<?php if ($layout === 'dashboard' && $user): ?>

<div class="app-shell">

    <aside class="app-sidebar" id="sidebar">
        <div class="brand">
            <a class="mb-brand" href="<?= e(url('index.php')) ?>">
                <span class="mb-brand-mark"><?= icon('heart', 18) ?></span>
                <?= e(APP_NAME) ?>
            </a>
        </div>

        <div class="side-label"><?= e(ucfirst($user['role'])) ?> menu</div>

        <?php foreach ($__navMap[$user['role']] ?? [] as [$__file, $__label, $__ico, $__badgeKey]): ?>
            <?php
                $__isActive = $activeNav === basename($__file, '.php');
                $__badge    = $__badgeKey !== '' ? ($__navBadges[$__badgeKey] ?? 0) : 0;
            ?>
            <a class="side-link <?= $__isActive ? 'active' : '' ?>"
               href="<?= e(url($user['role'] . '/' . $__file)) ?>">
                <?= icon($__ico, 18) ?>
                <span><?= e($__label) ?></span>
                <?php if ($__badge > 0): ?>
                    <span class="badge rounded-pill bg-danger"><?= $__badge ?></span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>

        <div class="side-footer">
            <a class="side-link" href="<?= e(url('index.php')) ?>"><?= icon('search', 18) ?><span>Public site</span></a>
            <form method="post" action="<?= e(url('auth/logout.php')) ?>" class="m-0">
                <?= csrf_field() ?>
                <button class="side-link w-100 border-0 bg-transparent text-start" type="submit">
                    <?= icon('logout', 18) ?><span>Sign out</span>
                </button>
            </form>
        </div>
    </aside>

    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <div class="app-main">
        <header class="app-topbar">
            <button class="btn btn-sm btn-light border d-lg-none" id="sidebarToggle" type="button" aria-label="Menu">
                <?= icon('menu', 18) ?>
            </button>

            <div class="ms-auto d-flex align-items-center gap-2">
                <div class="dropdown">
                    <button class="btn btn-sm btn-light border position-relative" data-bs-toggle="dropdown"
                            data-bs-auto-close="outside" aria-label="Notifications">
                        <?= icon('bell', 18) ?>
                        <?php if ($__unread > 0): ?><span class="notif-dot"></span><?php endif; ?>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end p-0 shadow" style="width:min(340px,92vw)">
                        <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom">
                            <strong style="font-size:.85rem">Notifications</strong>
                            <?php if ($__unread > 0): ?>
                                <form method="post" action="<?= e(url('includes/mark_notifications.php')) ?>" class="m-0">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-link btn-sm p-0 text-decoration-none" style="font-size:.78rem">Mark all read</button>
                                </form>
                            <?php endif; ?>
                        </div>
                        <?php if (!$__notifs): ?>
                            <div class="px-3 py-4 text-center text-muted small">Nothing here yet.</div>
                        <?php else: ?>
                            <div style="max-height:320px;overflow:auto">
                                <?php foreach ($__notifs as $__n): ?>
                                    <a class="d-block notif-item <?= (int) $__n['is_read'] === 0 ? 'unread' : '' ?> text-reset"
                                       href="<?= e($__n['link'] ? url(ltrim($__n['link'], '/')) : '#') ?>">
                                        <div><?= e($__n['message']) ?></div>
                                        <div class="text-faint mt-1" style="font-size:.72rem"><?= e(time_ago($__n['created_at'])) ?></div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="dropdown">
                    <button class="btn btn-sm btn-light border d-flex align-items-center gap-2" data-bs-toggle="dropdown">
                        <span class="avatar avatar-sm"><?= e(strtoupper(mb_substr($user['name'], 0, 1))) ?></span>
                        <span class="d-none d-sm-inline fw-semibold" style="font-size:.85rem"><?= e(explode(' ', $user['name'])[0]) ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        <li class="px-3 py-2 border-bottom">
                            <div class="fw-semibold text-ink"><?= e($user['name']) ?></div>
                            <div class="text-muted" style="font-size:.78rem"><?= e($user['email']) ?></div>
                        </li>
                        <li><a class="dropdown-item" href="<?= e(url($user['role'] . '/profile.php')) ?>"><?= icon('user', 16) ?> My profile</a></li>
                        <li><a class="dropdown-item" href="<?= e(url('index.php')) ?>"><?= icon('search', 16) ?> Public site</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="post" action="<?= e(url('auth/logout.php')) ?>" class="m-0">
                                <?= csrf_field() ?>
                                <button class="dropdown-item text-danger" type="submit"><?= icon('logout', 16) ?> Sign out</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <main class="app-content">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
                <div>
                    <h1 class="page-title"><?= e($pageTitle) ?></h1>
                    <?php if ($pageSub !== ''): ?><p class="page-sub"><?= e($pageSub) ?></p><?php endif; ?>
                </div>
                <?php if (isset($pageActions)): ?><div class="d-flex gap-2 flex-wrap"><?= $pageActions ?></div><?php endif; ?>
            </div>

            <?php foreach (take_flashes() as $__f): ?>
                <div class="alert alert-<?= e($__f['type']) ?> alert-dismissible fade show" role="alert">
                    <?= e($__f['msg']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endforeach; ?>

<?php else: ?>

<nav class="mb-nav navbar navbar-expand-lg">
    <div class="container">
        <a class="mb-brand" href="<?= e(url('index.php')) ?>">
            <span class="mb-brand-mark"><?= icon('heart', 18) ?></span>
            <?= e(APP_NAME) ?>
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMain" aria-label="Toggle navigation">
            <?= icon('menu', 22) ?>
        </button>

        <div class="collapse navbar-collapse" id="navMain">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
                <li class="nav-item"><a class="nav-link" href="<?= e(url('index.php')) ?>">Find a doctor</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= e(url('index.php#how')) ?>">How it works</a></li>
                <?php if ($user): ?>
                    <li class="nav-item ms-lg-2">
                        <a class="btn btn-primary btn-sm px-3" href="<?= e(dashboard_url($user['role'])) ?>">
                            My dashboard
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item ms-lg-2"><a class="nav-link" href="<?= e(url('auth/login.php')) ?>">Sign in</a></li>
                    <li class="nav-item"><a class="btn btn-primary btn-sm px-3" href="<?= e(url('auth/register.php')) ?>">Get started</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<main>
    <?php $__flashes = take_flashes(); ?>
    <?php if ($__flashes): ?>
        <div class="container mt-3">
            <?php foreach ($__flashes as $__f): ?>
                <div class="alert alert-<?= e($__f['type']) ?> alert-dismissible fade show" role="alert">
                    <?= e($__f['msg']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

<?php endif; ?>
