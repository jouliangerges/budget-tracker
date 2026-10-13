<?php

declare(strict_types=1);

namespace App\Tests\Unit\Core;

use App\Core\Request;
use JsonException;
use PHPUnit\Framework\Attributes\BackupGlobals;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Request::class)]
final class RequestTest extends TestCase
{
    public function testMethodIsUppercased(): void
    {
        $request = Request::fromArrays(['REQUEST_METHOD' => 'post'], [], '');

        self::assertSame('POST', $request->getMethod());
    }

    public function testMethodDefaultsToGet(): void
    {
        $request = Request::fromArrays([], [], '');

        self::assertSame('GET', $request->getMethod());
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function pathProvider(): array
    {
        return [
            'plain path' => ['/api/health', '/api/health'],
            'query string is removed' => ['/api/health?page=2', '/api/health'],
            'trailing slash is removed' => ['/api/health/', '/api/health'],
            'root stays root' => ['/', '/'],
            'only slashes become root' => ['//', '/'],
        ];
    }

    #[DataProvider('pathProvider')]
    public function testPathIsNormalized(string $uri, string $expectedPath): void
    {
        $request = Request::fromArrays(['REQUEST_URI' => $uri], [], '');

        self::assertSame($expectedPath, $request->getPath());
    }

    public function testPathDefaultsToRoot(): void
    {
        $request = Request::fromArrays([], [], '');

        self::assertSame('/', $request->getPath());
    }

    public function testGetQueryReturnsValueOrDefault(): void
    {
        $request = Request::fromArrays([], ['page' => '2'], '');

        self::assertSame('2', $request->getQuery('page'));
        self::assertNull($request->getQuery('missing'));
        self::assertSame('1', $request->getQuery('missing', '1'));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function emptyBodyProvider(): array
    {
        return [
            'empty string' => [''],
            'only whitespace' => ["  \n\t "],
        ];
    }

    #[DataProvider('emptyBodyProvider')]
    public function testEmptyBodyBecomesEmptyArray(string $rawBody): void
    {
        $request = Request::fromArrays([], [], $rawBody);

        self::assertSame([], $request->getBody());
    }

    public function testJsonObjectBodyIsDecoded(): void
    {
        $request = Request::fromArrays([], [], '{"amount": 1250, "description": "Coffee"}');

        self::assertSame(['amount' => 1250, 'description' => 'Coffee'], $request->getBody());
        self::assertSame(1250, $request->getBodyParam('amount'));
        self::assertNull($request->getBodyParam('missing'));
        self::assertSame('none', $request->getBodyParam('missing', 'none'));
    }

    public function testEmptyJsonObjectIsAccepted(): void
    {
        $request = Request::fromArrays([], [], '{}');

        self::assertSame([], $request->getBody());
    }

    public function testInvalidJsonThrows(): void
    {
        $this->expectException(JsonException::class);

        Request::fromArrays([], [], '{broken');
    }

    /**
     * @return array<string, array{string}>
     */
    public static function scalarJsonProvider(): array
    {
        return [
            'string' => ['"text"'],
            'number' => ['42'],
            'null' => ['null'],
        ];
    }

    #[DataProvider('scalarJsonProvider')]
    public function testScalarJsonThrows(string $rawBody): void
    {
        $this->expectException(JsonException::class);
        $this->expectExceptionMessageIs('Request body must be a JSON object');

        Request::fromArrays([], [], $rawBody);
    }

    public function testJsonArrayThrows(): void
    {
        self::markTestIncomplete('T.3b: a JSON list like [1,2] is still accepted as body');
    }

    /**
     * Superglobals are global state: BackupGlobals restores them after this test.
     * php://input is always empty on the CLI, so only the empty body can be checked here.
     */
    #[BackupGlobals(true)]
    public function testFromGlobalsReadsSuperglobals(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'DELETE';
        $_SERVER['REQUEST_URI'] = '/api/transactions/5?force=1';
        $_GET = ['force' => '1'];

        $request = Request::fromGlobals();

        self::assertSame('DELETE', $request->getMethod());
        self::assertSame('/api/transactions/5', $request->getPath());
        self::assertSame('1', $request->getQuery('force'));
        self::assertSame([], $request->getBody());
    }
}
