<?php
require_once __DIR__ . '/_guards/adminGuard.php';
require_once __DIR__ . '/../database/connect.php';

define('PROJECT_ROOT', dirname(__DIR__));
define('UPLOAD_PATH', PROJECT_ROOT . '/database/uploads');
define('UPLOAD_URL', '/PROGNET/database/uploads');