<?php

declare(strict_types=1);

namespace App\Tests\Unit\Core;

use App\Core\ErrorHandler;
use App\Core\Response;
use App\Tests\Support\SpyLogger;
use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(ErrorHandler::class)]
#[UsesClass(Response::class)]
final class ErrorHandlerTest extends TestCase
{
    private SpyLogger $logger;

    protected function setUp(): void
    {
        $this->logger = new SpyLogger();
    }

    public function testGenericExceptionBecomes500(): void
    {
        $handler = new ErrorHandler(false, $this->logger);

        $response = $handler->toResponse(new RuntimeException('Database is gone'));

        self::assertSame(500, $response->getStatus());
        self::assertSame('{"error":"Internal Server Error"}', $response->getBody());
    }

    public function testServerErrorIsLoggedWithException(): void
    {
        $handler = new ErrorHandler(false, $this->logger);
        $exception = new RuntimeException('Database is gone');

        $handler->toResponse($exception);

        self::assertCount(1, $this->logger->records);
        self::assertSame('error', $this->logger->records[0]['level']);
        self::assertSame('Database is gone', $this->logger->records[0]['message']);
        self::assertSame($exception, $this->logger->records[0]['context']['exception']);
    }

    public function testJsonExceptionBecomes400AndIsNotLogged(): void
    {
        $handler = new ErrorHandler(false, $this->logger);

        $response = $handler->toResponse(new JsonException('Syntax error'));

        self::assertSame(400, $response->getStatus());
        self::assertSame('{"error":"Invalid JSON body"}', $response->getBody());
        self::assertSame([], $this->logger->records, 'Client errors must not be logged');
    }

    public function testDebugModeAddsExceptionDetails(): void
    {
        $handler = new ErrorHandler(true, $this->logger);
        $exception = new RuntimeException('Database is gone');

        $response = $handler->toResponse($exception);

        $body = json_decode($response->getBody(), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame([
            'exception' => RuntimeException::class,
            'message' => 'Database is gone',
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ], $body['debug']);
    }

    public function testProductionModeHidesExceptionDetails(): void
    {
        $handler = new ErrorHandler(false, $this->logger);

        $response = $handler->toResponse(new RuntimeException('secret detail'));

        self::assertStringNotContainsString('debug', $response->getBody());
        self::assertStringNotContainsString('secret detail', $response->getBody());
    }
}
