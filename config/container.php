<?php

declare(strict_types=1);

use App\Controllers\HealthController;
use App\Core\Container;
use App\Core\Database;
use App\Repositories\TransactionRepository;

return function (Container $container): void {
    $container->set(Database::class, function (): Database {
        $config = require __DIR__ . '/database.php';

        return new Database($config['dsn']);
    });

    $container->set(TransactionRepository::class, fn(Container $c) => new TransactionRepository($c->get(Database::class)));

    $container->set(HealthController::class, fn() => new HealthController());
};
