<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use FastRoute\RouteCollector;
use App\Core\Database;
use App\Controllers\Web\AdminController;
use App\Controllers\Api\ProductController;
use App\Middleware\JwtMiddleware;

// --------------------------------------------------
// LOAD ENVIRONMENT VARIABLES
// --------------------------------------------------
$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

// --------------------------------------------------
// SECURE SESSION SETTINGS
// --------------------------------------------------
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => (bool)($_ENV['SESSION_COOKIE_SECURE'] ?? false),
    'cookie_samesite' => 'Lax',
    'use_strict_mode' => true,
    'use_only_cookies' => true,
    'sid_length' => 64,
    'sid_bits_per_character' => 6,
]);

// --------------------------------------------------
// DATABASE CONNECTION (PDO)
// --------------------------------------------------
$db = new Database(
    $_ENV['DB_HOST'],
    (int)$_ENV['DB_PORT'],
    $_ENV['DB_DATABASE'],
    $_ENV['DB_USERNAME'],
    $_ENV['DB_PASSWORD']
);

// --------------------------------------------------
// ROUTER (strictly following assignment URLs)
// --------------------------------------------------
$dispatcher = FastRoute\simpleDispatcher(function (RouteCollector $r) {

    // -------------------------
    // ADMIN PANEL ROUTES
    // -------------------------
    $r->addRoute('GET', '/admin/login', [AdminController::class, 'showLogin']);
    $r->addRoute('POST', '/admin/login', [AdminController::class, 'login']);

    // User management
    $r->addRoute('GET', '/admin/users', [AdminController::class, 'listUsers']);
    $r->addRoute('POST', '/admin/users', [AdminController::class, 'createUser']);
    $r->addRoute('PUT', '/admin/users/{id:\d+}', [AdminController::class, 'updateUser']);
    $r->addRoute('DELETE', '/admin/users/{id:\d+}', [AdminController::class, 'deleteUser']);

    // -------------------------
    // PRODUCT API ROUTES (assignment mandates `/api/products`)
    // -------------------------
    $r->addRoute('POST', '/api/auth/login', [ProductController::class, 'login']);

    $r->addRoute('GET', '/api/products', [ProductController::class, 'index']);
    $r->addRoute('POST', '/api/products', [ProductController::class, 'store']);
    $r->addRoute('GET', '/api/products/{id:\d+}', [ProductController::class, 'show']);
    $r->addRoute('PUT', '/api/products/{id:\d+}', [ProductController::class, 'update']);
    $r->addRoute('DELETE', '/api/products/{id:\d+}', [ProductController::class, 'delete']);
});

// --------------------------------------------------
// DISPATCH REQUEST
// --------------------------------------------------
$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);

switch ($routeInfo[0]) {

    case FastRoute\Dispatcher::NOT_FOUND:
        header('Content-Type: application/json');
        http_response_code(404);
        echo json_encode(['error' => 'Not Found']);
        break;

    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        header('Content-Type: application/json');
        http_response_code(405);
        echo json_encode(['error' => 'Method Not Allowed']);
        break;

    case FastRoute\Dispatcher::FOUND:
        [$class, $method] = $routeInfo[1];
        $vars = $routeInfo[2];

        $controller = new $class($db);

        // JWT protection for ALL /api/products/* routes except login
        $isApiRoute = str_starts_with($uri, '/api/products');

        if ($isApiRoute && $method !== 'login') {
            $jwt = new JwtMiddleware($_ENV['JWT_SECRET']);
            $userId = $jwt->handle();

            if (!$userId) {
                header('Content-Type: application/json');
                http_response_code(401);
                echo json_encode(['error' => 'Unauthorized']);
                exit;
            }

            if (method_exists($controller, 'setUserId')) {
                $controller->setUserId($userId);
            }
        }

        $controller->$method($vars);

        break;
}
