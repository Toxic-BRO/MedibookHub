<?php
/**
 * config/config.php — application-wide configuration.
 *
 * Edit the DB_* block to match your XAMPP MySQL credentials (root with an
 * empty password is the XAMPP default).
 */

declare(strict_types=1);

/* ------------------------------------------------------------------ */
/*  Database                                                          */
/* ------------------------------------------------------------------ */
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'doctor_appointment');
define('DB_USER', 'root');
define('DB_PASS', '');          // XAMPP default is an empty password
define('DB_CHARSET', 'utf8mb4');

/* ------------------------------------------------------------------ */
/*  Application                                                       */
/* ------------------------------------------------------------------ */
define('APP_NAME', 'MediBook Hub');

/**
 * BASE_URL is auto-detected so the app works both from a subfolder
 * (http://localhost/saan) and from a virtual host root.
 * Set it manually only if detection gets it wrong.
 */
if (!defined('BASE_URL')) {
    $projectRoot = str_replace('\\', '/', (string) realpath(__DIR__ . '/..'));
    $scriptFile  = str_replace('\\', '/', (string) realpath($_SERVER['SCRIPT_FILENAME'] ?? ''));
    $scriptName  = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));

    // Normalise any /./ or /../ segments so the suffix match below is reliable.
    while (preg_match('#/[^/]+/\.\./#', $scriptName)) {
        $scriptName = preg_replace('#/[^/]+/\.\./#', '/', $scriptName);
    }
    $scriptName = str_replace('/./', '/', $scriptName);

    $base = '';

    /*
     * Preferred method: how deep is the running script inside the project?
     *   project root : I:/saan
     *   running file : I:/saan/patient/dashboard.php -> patient/dashboard.php
     *   SCRIPT_NAME  : /saan/patient/dashboard.php
     *   => BASE_URL  : /saan
     * This works from a subfolder, a vhost root, and through symlinks or
     * junctions (where realpath leaves DOCUMENT_ROOT behind).
     */
    if ($projectRoot !== '' && $scriptFile !== '' && str_starts_with($scriptFile, $projectRoot . '/')) {
        $relative = ltrim(substr($scriptFile, strlen($projectRoot)), '/');

        if ($relative !== '' && str_ends_with($scriptName, $relative)) {
            $base = rtrim(substr($scriptName, 0, -strlen($relative)), '/');
        }
    }

    // Fallback: compare the project root against the document root.
    if ($base === '') {
        $docRoot = str_replace('\\', '/', (string) realpath($_SERVER['DOCUMENT_ROOT'] ?? ''));

        if ($docRoot !== '' && str_starts_with($projectRoot, $docRoot)) {
            $base = rtrim(substr($projectRoot, strlen($docRoot)), '/');
        }
    }

    // CLI (php -S from the project folder) ends up here with ''.
    define('BASE_URL', $base);
}

define('APP_TIMEZONE', 'Asia/Dhaka');   // change if you are elsewhere
define('CURRENCY_SYMBOL', '৳');         // used by money()

/**
 * XAMPP ships without a working SMTP relay, so email is OFF by default and
 * every outbound message is appended to storage/mail.log instead. Set this
 * to true once php.ini has valid sendmail/SMTP settings.
 */
define('MAIL_ENABLED', false);

/* Uploads */
define('UPLOAD_DIR', realpath(__DIR__ . '/../assets/uploads/doctors') ?: __DIR__ . '/../assets/uploads/doctors');
define('UPLOAD_URL', BASE_URL . '/assets/uploads/doctors');

/* Booking rules */
define('SLOT_LEAD_MINUTES', 60);        // a slot must start at least 1h from now
define('BOOKING_HORIZON_DAYS', 30);     // patients can book up to 30 days ahead
define('MAX_UPLOAD_BYTES', 2 * 1024 * 1024);

/* ------------------------------------------------------------------ */
/*  Session                                                           */
/* ------------------------------------------------------------------ */
date_default_timezone_set(APP_TIMEZONE);

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        // 'secure' => true,   // enable once you serve the site over HTTPS
    ]);
    session_name('MEDIBOOKSESS');
    session_start();
}

/* ------------------------------------------------------------------ */
/*  Error display — turn off in production                            */
/* ------------------------------------------------------------------ */
ini_set('display_errors', '1');
error_reporting(E_ALL);

/* ------------------------------------------------------------------ */
/*  Bootstrap                                                         */
/* ------------------------------------------------------------------ */
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/ai_health.php';
