<?php

declare(strict_types=1);

use App\Core\Database;

if (PHP_SAPI !== 'cli') {
    exit('This script must be run from the command line.' . PHP_EOL);
}

require dirname(__DIR__) . '/vendor/autoload.php';
$config = require dirname(__DIR__) . '/config/database.php';

$pdo = new Database($config['dsn'])->getConnection();

$files = glob(dirname(__DIR__) . '/database/seeds/*.sql');
sort($files);

if ($files === []) {
    echo "No seed files found." . PHP_EOL;
    exit(0);
}

// All seed files run in one transaction: either everything is seeded or nothing.
$pdo->beginTransaction();

try {
    foreach ($files as $file) {
        $pdo->exec(file_get_contents($file));

        echo "Seeded: " . basename($file) . PHP_EOL;
    }

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, "Seeding failed, nothing was written." . PHP_EOL);
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    fwrite(STDERR, "Hint: run migrations first. To reseed, delete database/budget.sqlite and run both again." . PHP_EOL);
    exit(1);
}
