<?php

declare(strict_types=1);

use App\Core\Database;

if (PHP_SAPI !== 'cli') {
    exit('This script must be run from the command line.' . PHP_EOL);
}

require dirname(__DIR__) . '/vendor/autoload.php';
$config = require dirname(__DIR__) . '/config/database.php';

$pdo = new Database($config['dsn'])->getConnection();

$pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS migrations(
    id INTEGER PRIMARY KEY,
    filename TEXT NOT NULL UNIQUE,
    applied_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
) STRICT
SQL);

$applied = $pdo->query('SELECT filename FROM migrations')->fetchAll(PDO::FETCH_COLUMN);

$files = glob(dirname(__DIR__) . '/database/migrations/*.sql');
sort($files);

$count = 0;
foreach ($files as $file) {
    $filename = basename($file);

    if (in_array($filename, $applied, true)) {
        continue;
    }

    $pdo->beginTransaction();

    try {
        $sql = file_get_contents($file);
        $pdo->exec($sql);
        $pdo->prepare('INSERT INTO migrations (filename) VALUES (:filename)')
            ->execute(['filename' => $filename]);
        $pdo->commit();

        echo "Applied: {$filename}" . PHP_EOL;

        $count++;
    } catch (Throwable $e) {
        $pdo->rollBack();
        fwrite(STDERR, "Failed: " . $filename . PHP_EOL);
        fwrite(STDERR, $e->getMessage() . PHP_EOL);
        exit(1);
    }
}

if ($count === 0) {
    echo "Nothing to migrate." . PHP_EOL;
}
