<?php

declare(strict_types=1);

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'doctor_appointment');
define('DB_USER', 'root');
define('DB_PASS', '');          
define('DB_CHARSET', 'utf8mb4');

define('APP_NAME', 'MediBook Hub');

if (!defined('BASE_URL')) {
    $projectRoot = str_replace('\\', '/', (string) realpath(__DIR__ . '/..'));
    $scriptFile  = str_replace('\\', '/', (string) realpath($_SERVER['SCRIPT_FILENAME'] ?? ''));
    $scriptName  = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));

    
    while (preg_match('#/[^/]+/\.\./#', $scriptName)) {
        $scriptName = preg_replace('#/[^/]+/\.\./#', '/', $scriptName);
    }
    $scriptName = str_replace('/./', '/', $scriptName);

    $base = '';

    
    if ($projectRoot !== '' && $scriptFile !== '' && str_starts_with($scriptFile, $projectRoot . '/')) {
        $relative = ltrim(substr($scriptFile, strlen($projectRoot)), '/');

        if ($relative !== '' && str_ends_with($scriptName, $relative)) {
            $base = rtrim(substr($scriptName, 0, -strlen($relative)), '/');
        }
    }

    
    if ($base === '') {
        $docRoot = str_replace('\\', '/', (string) realpath($_SERVER['DOCUMENT_ROOT'] ?? ''));

        if ($docRoot !== '' && str_starts_with($projectRoot, $docRoot)) {
            $base = rtrim(substr($projectRoot, strlen($docRoot)), '/');
        }
    }

    
    define('BASE_URL', $base);
}

define('APP_TIMEZONE', 'Asia/Dhaka');   
define('CURRENCY_SYMBOL', '৳');         

define('MAIL_ENABLED', false);

define('UPLOAD_DIR', realpath(__DIR__ . '/../assets/uploads/doctors') ?: __DIR__ . '/../assets/uploads/doctors');
define('UPLOAD_URL', BASE_URL . '/assets/uploads/doctors');

define('SLOT_LEAD_MINUTES', 60);        
define('BOOKING_HORIZON_DAYS', 30);     
define('MAX_UPLOAD_BYTES', 2 * 1024 * 1024);

date_default_timezone_set(APP_TIMEZONE);

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        
    ]);
    session_name('MEDIBOOKSESS');
    session_start();
}

ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/ai_health.php';
