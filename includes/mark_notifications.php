<?php


declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

$user = require_login();
verify_csrf();

$stmt = db()->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0');
$stmt->execute([(int) $user['id']]);

$back = $_SERVER['HTTP_REFERER'] ?? '';
$path = parse_url($back, PHP_URL_PATH) ?: '';


redirect($path !== '' && str_starts_with($path, '/') ? $path : dashboard_url($user['role']));
