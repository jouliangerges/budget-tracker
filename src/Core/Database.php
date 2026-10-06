<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

final class Database
{
    private ?PDO $connection = null;

    public function __construct(
        private readonly string $dsn,
        private readonly ?string $username = null,
        private readonly ?string $password = null
    ) {}

    public function getConnection(): PDO
    {
        $this->connection ??= $this->connect();

        return $this->connection;
    }

    private function connect(): PDO
    {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        $pdo = new PDO($this->dsn, $this->username, $this->password, $options);

        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
            $pdo->exec('PRAGMA foreign_keys = ON');
        }

        return $pdo;
    }
}
