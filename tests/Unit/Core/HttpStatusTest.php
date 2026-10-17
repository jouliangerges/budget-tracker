<?php

declare(strict_types=1);

namespace App\Tests\Unit\Core;

use App\Core\HttpStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(HttpStatus::class)]
final class HttpStatusTest extends TestCase
{
    /**
     * Status codes and reason phrases as defined in RFC 9110.
     *
     * @return array<string, array{HttpStatus, int, string}>
     */
    public static function statusProvider(): array
    {
        return [
            'Ok' => [HttpStatus::Ok, 200, 'OK'],
            'Created' => [HttpStatus::Created, 201, 'Created'],
            'NoContent' => [HttpStatus::NoContent, 204, 'No Content'],
            'BadRequest' => [HttpStatus::BadRequest, 400, 'Bad Request'],
            'NotFound' => [HttpStatus::NotFound, 404, 'Not Found'],
            'MethodNotAllowed' => [HttpStatus::MethodNotAllowed, 405, 'Method Not Allowed'],
            'UnprocessableContent' => [HttpStatus::UnprocessableContent, 422, 'Unprocessable Content'],
            'InternalServerError' => [HttpStatus::InternalServerError, 500, 'Internal Server Error'],
        ];
    }

    #[DataProvider('statusProvider')]
    public function testCodeAndReasonPhraseMatchRfc(HttpStatus $status, int $code, string $reasonPhrase): void
    {
        self::assertSame($code, $status->value);
        self::assertSame($reasonPhrase, $status->getReasonPhrase());
    }

    /**
     * Fails when a new case is added to the enum but not to the provider above.
     */
    public function testProviderCoversAllCases(): void
    {
        self::assertCount(count(HttpStatus::cases()), self::statusProvider());
    }
}
