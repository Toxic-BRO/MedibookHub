<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

if (!is_post()) {
    redirect('index.php');
}

verify_csrf();

destroy_session();
session_start();            

flash('success', 'You have been signed out.');
redirect('index.php');
