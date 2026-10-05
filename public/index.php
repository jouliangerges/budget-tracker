<?php

declare(strict_types=1);

use App\Core\ErrorHandler;
use App\Core\Request;
use App\Core\Router;

// Built-in server: serve real files (CSS, JS, images) directly instead of routing them through the app.
if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($file)) {
        return false;
    }
}

require dirname(__DIR__) . '/vendor/autoload.php';
ini_set('display_errors', '0');

set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
    throw new ErrorException($message, 0, $severity, $file, $line);
});


$config = require dirname(__DIR__) . '/config/app.php';
$errorHandler = new ErrorHandler($config['debug']);
$router = new Router();
(require dirname(__DIR__) . '/config/routes.php')($router);

try {
    $request = Request::fromGlobals();
    $response = $router->dispatch($request);
} catch (Throwable $e) {

    $response = $errorHandler->toResponse($e);
}

$response->send();
