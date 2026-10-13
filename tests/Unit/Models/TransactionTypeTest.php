<?php

declare(strict_types=1);

namespace App\Tests\Unit\Models;

use App\Models\TransactionType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TransactionType::class)]
final class TransactionTypeTest extends TestCase
{
    /**
     * The values are a contract with the CHECK constraint in the migration and with the API.
     */
    public function testValuesMatchDatabaseValues(): void
    {
        self::assertSame(['income', 'expense'], array_column(TransactionType::cases(), 'value'));
    }
}
