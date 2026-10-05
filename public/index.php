<?php

declare(strict_types=1);

use App\Core\Request;
use App\Core\Router;

// Built-in-Server: echte Dateien (CSS, JS, Bilder) direkt ausliefern statt durch die App zu routen.
if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($file)) {
        return false;
    }
}

require dirname(__DIR__) . '/vendor/autoload.php';

$router = new Router();
(require dirname(__DIR__) . '/config/routes.php')($router);

$router->dispatch(Request::fromGlobals())->send();
