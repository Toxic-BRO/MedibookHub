<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

$user = auth_user();
if ($user !== null && $user['role'] !== 'admin') {
    abort_403('Only administrators can run this task directly.');
}

$result = ai_check_doctor_lateness();

header('Content-Type: text/plain; charset=utf-8');
echo "MediBook — Doctor Lateness Reminder Report\n";
echo str_repeat('=', 50) . "\n";
echo "Run at: " . date('Y-m-d H:i:s') . "\n";
echo "Reminders sent: " . $result['sent'] . "\n";

if ($result['sent'] > 0) {
    echo "\nDetails:\n";
    foreach ($result['details'] as $i => $detail) {
        echo '  ' . ($i + 1) . '. ' . $detail . "\n";
    }
} else {
    echo "\nNo pending appointments need reminders at this time.\n";
}
