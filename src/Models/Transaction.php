<?php

declare(strict_types=1);

namespace App\Models;

final readonly class Transaction
{
    public function __construct(
        private int $id,
        private int $categoryId,
        private TransactionType $type,
        private int $amount,
        private ?string $description,
        private string $date,
        private string $createdAt
    ) {}

    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            categoryId: (int) $row['category_id'],
            type: TransactionType::from($row['type']),
            amount: (int) $row['amount'],
            description: $row['description'],
            date: $row['date'],
            createdAt: $row['created_at']
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'categoryId' => $this->categoryId,
            'type' => $this->type->value,
            'amount' => $this->amount,
            'description' => $this->description,
            'date' => $this->date,
            'createdAt' => $this->createdAt
        ];
    }
}
