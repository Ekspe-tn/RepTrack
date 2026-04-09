<?php

declare(strict_types=1);

$basePath = dirname(__DIR__);

$autoload = $basePath . '/vendor/autoload.php';
if (file_exists($autoload)) {
    require $autoload;
}

require $basePath . '/app/Bootstrap.php';
\App\Bootstrap::init($basePath);

require $basePath . '/config/auth.php';
require $basePath . '/config/db.php';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$path = rtrim($path ?? '/', '/');
$path = $path === '' ? '/' : $path;

if ($path === '/') {
    if (is_logged_in()) {
        header('Location: /dashboard');
    } else {
        header('Location: /login');
    }
    exit;
}

if (str_starts_with($path, '/api/')) {
    $apiPath = $basePath . $path;
    if (!str_ends_with($apiPath, '.php')) {
        $apiPath .= '.php';
    }

    if (file_exists($apiPath)) {
        require $apiPath;
        exit;
    }

    http_response_code(404);
    echo json_encode(['error' => 'Not found']);
    exit;
}

switch ($path) {
    case '/login':
        if (is_logged_in()) {
            header('Location: /dashboard');
            exit;
        }
        require $basePath . '/pages/login.php';
        break;
    case '/logout':
        require $basePath . '/public/logout.php';
        break;
    case '/dashboard':
        require_login();
        require $basePath . '/pages/dashboard.php';
        break;
    case '/notifications':
        require_login();
        require $basePath . '/pages/notifications.php';
        break;
    case '/contacts':
        require_login();
        require $basePath . '/pages/contacts.php';
        break;
    case '/contacts_new':
        require_login();
        require $basePath . '/pages/contacts_new.php';
        break;
    case '/visits':
        require_login();
        require $basePath . '/pages/visits_list.php';
        break;
    case '/visits/new':
        require_login();
        require $basePath . '/pages/visits.php';
        break;
    case '/visits/view':
        require_login();
        require $basePath . '/pages/visit_detail.php';
        break;
    case '/planning':
        require_login();
        require $basePath . '/pages/planning.php';
        break;
    case '/stock':
        require_login();
        require $basePath . '/pages/stock.php';
        break;
    case '/stock/history':
        require_login();
        require $basePath . '/pages/stock_history.php';
        break;
    case '/expenses':
        require_login();
        require $basePath . '/pages/expenses.php';
        break;
    case '/reports':
        require_login();
        require $basePath . '/pages/reports.php';
        break;
    case '/map':
        require_login();
        require $basePath . '/pages/map.php';
        break;
    case '/users':
        require_role('admin');
        require $basePath . '/pages/users.php';
        break;
    case '/delegues':
        require_role('admin');
        require $basePath . '/pages/delegues.php';
        break;
    case '/delegues/new':
        require_role('admin');
        require $basePath . '/pages/delegues_new.php';
        break;
    case '/delegues/map':
        require_role('admin');
        require $basePath . '/pages/delegues_map.php';
        break;
    default:
        http_response_code(404);
        require $basePath . '/pages/404.php';
        break;
}
