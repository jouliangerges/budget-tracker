<?php

declare(strict_types=1);

namespace App\Tests\Unit\Models;

use App\Models\Transaction;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ValueError;

#[CoversClass(Transaction::class)]
final class TransactionTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function row(array $overrides = []): array
    {
        return [
            'id' => 10,
            'category_id' => 6,
            'type' => 'income',
            'amount' => 1250,
            'description' => 'Pharmacy refund',
            'date' => '2026-09-12',
            'created_at' => '2026-10-07 22:12:38',
            ...$overrides,
        ];
    }

    public function testFromRowAndToArrayMapDatabaseRowToApiArray(): void
    {
        $transaction = Transaction::fromRow($this->row());

        self::assertSame([
            'id' => 10,
            'categoryId' => 6,
            'type' => 'income',
            'amount' => 1250,
            'description' => 'Pharmacy refund',
            'date' => '2026-09-12',
            'createdAt' => '2026-10-07 22:12:38',
        ], $transaction->toArray());
    }

    public function testFromRowKeepsNullDescription(): void
    {
        $transaction = Transaction::fromRow($this->row(['description' => null]));

        self::assertNull($transaction->toArray()['description']);
    }

    /**
     * Some PDO drivers return numbers as strings; fromRow() must turn them into ints.
     */
    public function testFromRowCastsNumericStringsToInt(): void
    {
        $transaction = Transaction::fromRow($this->row(['id' => '10', 'category_id' => '6', 'amount' => '1250']));

        $data = $transaction->toArray();

        self::assertSame(10, $data['id']);
        self::assertSame(6, $data['categoryId']);
        self::assertSame(1250, $data['amount']);
    }

    public function testFromRowThrowsForUnknownType(): void
    {
        $this->expectException(ValueError::class);

        Transaction::fromRow($this->row(['type' => 'transfer']));
    }
}
