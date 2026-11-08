<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/autoload.php';

use Services\Security\SecurityKernel;

SecurityKernel::startSession(true);
SecurityKernel::applySecurityHeaders('admin');

if (empty($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}
