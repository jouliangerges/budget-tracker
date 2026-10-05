<?php

declare(strict_types=1);

use App\Controllers\HealthController;
use App\Core\Router;

return function (Router $router): void {
    $router->get('/api/health', [HealthController::class, 'index']);
};
