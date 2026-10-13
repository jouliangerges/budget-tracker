<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Transaction;

final class TransactionRepository
{
    public function __construct(
        private readonly Database $database
    ) {}

    /**
     * @return list<Transaction>
     */
    public function findAll(): array
    {
        $transactions = [];
        $pdo = $this->database->getConnection();

        $rows = $pdo->query(<<<'SQL'
            SELECT id, category_id, type, amount, description, date, created_at
            FROM transactions
            ORDER BY date DESC, id DESC
            SQL)->fetchAll();

        foreach ($rows as $row) {
            $transactions[] = Transaction::fromRow($row);
        }

        return $transactions;
    }

    public function findById(int $id): ?Transaction
    {
        $pdo = $this->database->getConnection();

        $statement = $pdo->prepare(<<<'SQL'
            SELECT id, category_id, type, amount, description, date, created_at
            FROM transactions
            WHERE id = :id
            SQL);

        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        if ($row === false) {
            return null;
        }

        return Transaction::fromRow($row);
    }
}
