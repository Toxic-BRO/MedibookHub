<?php
/**
 * config/database.php — PDO connection factory.
 *
 * Uses prepared statements with emulation turned off, so every value the
 * app binds is sent to MySQL separately from the SQL text.
 */

declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        DB_HOST,
        DB_NAME,
        DB_CHARSET
    );

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_STRINGIFY_FETCHES  => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        http_response_code(500);
        exit(
            '<div style="font-family:system-ui;padding:2rem;max-width:640px;margin:4rem auto;'
            . 'border:1px solid #e3c2c2;border-radius:12px;background:#fdf3f3">'
            . '<h2 style="margin-top:0;color:#a33">Database connection failed</h2>'
            . '<p>Check <code>config/config.php</code>, make sure MySQL is running in XAMPP, '
            . 'and that you imported <code>database.sql</code>.</p>'
            . '<pre style="background:#fff;padding:1rem;border-radius:8px;overflow:auto">'
            . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')
            . '</pre></div>'
        );
    }

    // Keep MySQL's clock in sync with PHP's configured timezone so NOW()
    // and CURDATE() agree with date('Y-m-d') in the app.
    $offset = (new DateTimeImmutable('now', new DateTimeZone(APP_TIMEZONE)))->format('P');
    $pdo->exec("SET time_zone = '{$offset}'");

    return $pdo;
}
