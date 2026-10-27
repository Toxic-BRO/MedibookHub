<?php
/**
 * install.php — setup health check.
 *
 * Verifies the whole chain: PHP build, PDO MySQL, credentials, schema,
 * seed data, URL detection and folder permissions. If the tables are
 * missing it can import database.sql for you.
 *
 * Local development aid only — delete this file before going live.
 */

declare(strict_types=1);

require_once __DIR__ . '/config/config.php';

/* Only run from the machine that hosts the app. */
$isLocal = in_array($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', ['127.0.0.1', '::1'], true);
if (!$isLocal) {
    http_response_code(403);
    exit('install.php is restricted to localhost. Delete it on production servers.');
}

$checks   = [];
$db       = null;
$dbError  = null;
$tables   = [];
$imported = null;

function add_check(array &$checks, string $label, string $state, string $detail = ''): void
{
    $checks[] = ['label' => $label, 'state' => $state, 'detail' => $detail];
}

/* ------------------------------------------------------------------ */
/*  Import database.sql on demand                                      */
/* ------------------------------------------------------------------ */
if (is_post() && post('action') === 'import') {
    verify_csrf();

    $sqlFile = __DIR__ . '/database.sql';
    if (!is_file($sqlFile)) {
        $imported = ['ok' => false, 'msg' => 'database.sql was not found next to install.php.'];
    } else {
        try {
            // Connect without a database name so DROP/CREATE DATABASE can run.
            $root = new PDO(
                'mysql:host=' . DB_HOST . ';charset=' . DB_CHARSET,
                DB_USER,
                DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            $sql = (string) file_get_contents($sqlFile);

            // Split on semicolons that are not inside quotes.
            $statements = [];
            $buffer = '';
            $quote  = null;
            $len    = strlen($sql);

            for ($i = 0; $i < $len; $i++) {
                $ch = $sql[$i];

                if ($quote !== null) {
                    $buffer .= $ch;
                    if ($ch === '\\' && $i + 1 < $len) {      // escaped char
                        $buffer .= $sql[++$i];
                    } elseif ($ch === $quote) {
                        $quote = null;
                    }
                    continue;
                }

                if ($ch === "'" || $ch === '"' || $ch === '`') {
                    $quote   = $ch;
                    $buffer .= $ch;
                    continue;
                }

                if ($ch === ';') {
                    $statements[] = trim($buffer);
                    $buffer = '';
                    continue;
                }
                $buffer .= $ch;
            }
            if (trim($buffer) !== '') {
                $statements[] = trim($buffer);
            }

            $ran = 0;
            foreach ($statements as $stmt) {
                // Drop pure comment lines, keep the rest.
                $clean = preg_replace('/^\s*--.*$/m', '', $stmt);
                $clean = trim((string) $clean);
                if ($clean === '') {
                    continue;
                }
                $root->exec($clean);
                $ran++;
            }

            $imported = ['ok' => true, 'msg' => 'Imported ' . $ran . ' statements from database.sql.'];
        } catch (Throwable $ex) {
            $imported = ['ok' => false, 'msg' => 'Import failed: ' . $ex->getMessage()];
        }
    }
}

/* ------------------------------------------------------------------ */
/*  Checks                                                             */
/* ------------------------------------------------------------------ */
add_check(
    $checks,
    'PHP version',
    version_compare(PHP_VERSION, '8.0.0', '>=') ? 'pass' : 'fail',
    PHP_VERSION . ' (8.0+ required)'
);

add_check(
    $checks,
    'PDO MySQL driver',
    extension_loaded('pdo_mysql') ? 'pass' : 'fail',
    extension_loaded('pdo_mysql') ? 'pdo_mysql loaded' : 'Enable extension=pdo_mysql in php.ini'
);

add_check(
    $checks,
    'mbstring extension',
    extension_loaded('mbstring') ? 'pass' : 'warn',
    extension_loaded('mbstring') ? 'loaded' : 'Names with accents may break without it'
);

add_check(
    $checks,
    'Session support',
    session_status() === PHP_SESSION_ACTIVE ? 'pass' : 'fail',
    session_status() === PHP_SESSION_ACTIVE ? 'active (' . session_name() . ')' : 'session_start() failed'
);

/* ---- database ---- */
try {
    $db = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]
    );
    $version = (string) $db->query('SELECT VERSION()')->fetchColumn();
    add_check($checks, 'Database connection', 'pass', DB_USER . '@' . DB_HOST . ' → ' . DB_NAME . ' (server ' . $version . ')');
} catch (Throwable $ex) {
    $dbError = $ex->getMessage();
    add_check($checks, 'Database connection', 'fail', $dbError);
}

/* ---- schema ---- */
$required = ['users', 'doctors', 'patients', 'availability', 'appointments', 'reviews', 'complaints', 'notifications'];

if ($db) {
    try {
        $found = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        $missing = array_diff($required, $found);

        foreach ($required as $t) {
            if (in_array($t, $found, true)) {
                $tables[$t] = (int) $db->query('SELECT COUNT(*) FROM `' . $t . '`')->fetchColumn();
            }
        }

        add_check(
            $checks,
            'Schema',
            $missing ? 'fail' : 'pass',
            $missing
                ? 'Missing table(s): ' . implode(', ', $missing) . ' — use the import button below.'
                : count($required) . ' tables present'
        );

        if (!$missing) {
            $users = $tables['users'] ?? 0;
            add_check(
                $checks,
                'Seed data',
                $users > 0 ? 'pass' : 'warn',
                $users > 0
                    ? $users . ' users, ' . ($tables['doctors'] ?? 0) . ' doctors, ' . ($tables['appointments'] ?? 0) . ' appointments'
                    : 'Tables exist but are empty — import the sample data or register an account.'
            );

            $admin = (int) $db->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
            add_check(
                $checks,
                'Admin account',
                $admin > 0 ? 'pass' : 'fail',
                $admin > 0 ? $admin . ' admin account' . ($admin === 1 ? '' : 's') : 'No admin exists — nobody can verify doctors.'
            );
        }
    } catch (Throwable $ex) {
        add_check($checks, 'Schema', 'fail', $ex->getMessage());
    }
}

/* ---- runtime ---- */
add_check(
    $checks,
    'Base URL',
    'pass',
    BASE_URL === '' ? 'served from web root' : 'served from ' . BASE_URL
);

$uploadsOk = is_dir(UPLOAD_DIR) && is_writable(UPLOAD_DIR);
add_check(
    $checks,
    'Photo uploads folder',
    $uploadsOk ? 'pass' : 'warn',
    $uploadsOk ? UPLOAD_DIR : 'Not writable: ' . UPLOAD_DIR
);

$storageOk = is_dir(__DIR__ . '/storage') && is_writable(__DIR__ . '/storage');
add_check(
    $checks,
    'Storage folder',
    $storageOk ? 'pass' : 'warn',
    $storageOk ? 'mail log writable' : 'Not writable: storage/'
);

add_check(
    $checks,
    'Email',
    'info',
    (defined('MAIL_ENABLED') && MAIL_ENABLED ? 'SMTP enabled' : 'Stubbed to storage/mail.log') . ' · timezone ' . APP_TIMEZONE
);

$fails = count(array_filter($checks, static fn ($c) => $c['state'] === 'fail'));
$warns = count(array_filter($checks, static fn ($c) => $c['state'] === 'warn'));

$stateIcon = static fn (string $s): string => match ($s) {
    'pass' => '<span style="color:#0d9488">✔</span>',
    'fail' => '<span style="color:#dc2626">✕</span>',
    'warn' => '<span style="color:#d97706">!</span>',
    default => '<span style="color:#64748b">i</span>',
};
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Setup check · <?= e(APP_NAME) ?></title>
<link href="<?= e(url('assets/vendor/bootstrap.min.css')) ?>" rel="stylesheet">
<link href="<?= e(url('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body>
<div class="container py-5" style="max-width:820px">

    <div class="d-flex align-items-center gap-2 mb-4">
        <span class="mb-brand-mark">♡</span>
        <div>
            <div class="fw-bold text-ink" style="font-size:1.1rem"><?= e(APP_NAME) ?> setup check</div>
            <div class="text-muted" style="font-size:.82rem">Verifies PHP, the database connection, schema and folders.</div>
        </div>
    </div>

    <?php if ($imported): ?>
        <div class="alert alert-<?= $imported['ok'] ? 'success' : 'danger' ?>">
            <?= e($imported['msg']) ?>
        </div>
    <?php endif; ?>

    <?php if ($fails === 0 && $warns === 0): ?>
        <div class="alert alert-success d-flex align-items-center gap-2">
            <strong>Everything checks out.</strong> The database is connected and the app is ready.
        </div>
    <?php elseif ($fails > 0): ?>
        <div class="alert alert-danger">
            <strong><?= $fails ?> problem<?= $fails === 1 ? '' : 's' ?> found.</strong>
            Fix the red rows below, then reload this page.
        </div>
    <?php else: ?>
        <div class="alert alert-warning">
            <strong>Connected, with <?= $warns ?> warning<?= $warns === 1 ? '' : 's' ?>.</strong>
            The app will run; the amber rows are optional improvements.
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <tbody>
                <?php foreach ($checks as $c): ?>
                    <tr>
                        <td style="width:34px;font-size:1rem;text-align:center"><?= $stateIcon($c['state']) ?></td>
                        <td style="width:190px">
                            <span class="fw-semibold text-ink" style="font-size:.86rem"><?= e($c['label']) ?></span>
                        </td>
                        <td class="text-muted" style="font-size:.82rem;word-break:break-word"><?= e($c['detail']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($tables): ?>
        <div class="card mt-3">
            <div class="card-header">Rows per table</div>
            <div class="card-body d-flex flex-wrap gap-2">
                <?php foreach ($tables as $name => $count): ?>
                    <span class="specialty-tag" style="background:#f8fafb;border-color:var(--mb-border);color:var(--mb-body)">
                        <?= e($name) ?> <strong><?= $count ?></strong>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($dbError): ?>
        <div class="card mt-3">
            <div class="card-header">What to try</div>
            <div class="card-body" style="font-size:.85rem">
                <ol class="mb-3 ps-3">
                    <li>Start <strong>MySQL</strong> in the XAMPP Control Panel.</li>
                    <li>Check <code>DB_USER</code> / <code>DB_PASS</code> in <code>config/config.php</code>
                        (XAMPP default is <code>root</code> with an empty password).</li>
                    <li>Import <code>database.sql</code> — phpMyAdmin → Import, or the button below.</li>
                </ol>
                <pre class="mb-0 p-3 rounded" style="background:#f8fafb;border:1px solid var(--mb-border);font-size:.78rem;white-space:pre-wrap"><?= e($dbError) ?></pre>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!$db || ($db && !empty($missing))): ?>
        <form method="post" class="card mt-3" data-confirm="This drops and recreates the doctor_appointment database. Continue?">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="import">
            <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <div class="fw-semibold text-ink" style="font-size:.9rem">Create the schema and sample data</div>
                    <div class="text-muted" style="font-size:.8rem">
                        Runs <code>database.sql</code>. Any existing data in that database is replaced.
                    </div>
                </div>
                <button class="btn btn-primary" type="submit">Import database.sql</button>
            </div>
        </form>
    <?php endif; ?>

    <div class="d-flex flex-wrap gap-2 mt-4">
        <a class="btn btn-primary" href="<?= e(url('index.php')) ?>">Open the app</a>
        <a class="btn btn-light border" href="<?= e(url('auth/login.php')) ?>">Sign in</a>
        <a class="btn btn-light border" href="<?= e(url('install.php')) ?>">Re-run checks</a>
    </div>

    <p class="text-muted mt-4 mb-0" style="font-size:.78rem">
        Local development aid. Delete <code>install.php</code> before deploying to a public server.
    </p>
</div>
</body>
</html>
