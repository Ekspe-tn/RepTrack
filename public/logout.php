<?php

declare(strict_types=1);

require dirname(__DIR__) . '/config/auth.php';

logout_user();
header('Location: /login');
exit;
