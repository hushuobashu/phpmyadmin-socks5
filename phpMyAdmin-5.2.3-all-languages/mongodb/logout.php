<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/session.php';

session_destroy();
header('Location: index.php');
exit;
