<?php

declare(strict_types=1);

namespace App\Tests\Unit\Core;

use App\Core\HttpStatus;
use App\Core\Response;
use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Response::class)]
#[UsesClass(HttpStatus::class)]
final class ResponseTest extends TestCase
{
    public function testConstructorDefaults(): void
    {
        $response = new Response();

        self::assertSame('', $response->getBody());
        self::assertSame(HttpStatus::Ok, $response->getStatus());
        self::assertSame([], $response->getHeaders());
    }

    public function testJsonEncodesDataAndSetsContentType(): void
    {
        $response = Response::json(['status' => 'ok']);

        self::assertSame('{"status":"ok"}', $response->getBody());
        self::assertSame(HttpStatus::Ok, $response->getStatus());
        self::assertSame(['Content-Type' => 'application/json; charset=utf-8'], $response->getHeaders());
    }

    public function testJsonUsesGivenStatus(): void
    {
        $response = Response::json(['id' => 1], HttpStatus::Created);

        self::assertSame(HttpStatus::Created, $response->getStatus());
    }

    public function testJsonDoesNotEscapeSlashesOrUnicode(): void
    {
        $response = Response::json(['path' => '/api/health', 'description' => 'Grüße']);

        self::assertSame('{"path":"/api/health","description":"Grüße"}', $response->getBody());
    }

    public function testJsonThrowsForUnencodableData(): void
    {
        $this->expectException(JsonException::class);

        // Invalid UTF-8 byte sequence
        Response::json(['description' => "\xB1\x31"]);
    }

    public function testNoContentHasStatus204AndEmptyBody(): void
    {
        $response = Response::noContent();

        self::assertSame(HttpStatus::NoContent, $response->getStatus());
        self::assertSame('', $response->getBody());
        self::assertSame([], $response->getHeaders());
    }

    public function testWithHeaderReturnsCopyWithHeader(): void
    {
        $original = new Response();

        $changed = $original->withHeader('Allow', 'GET, POST');

        self::assertNotSame($original, $changed);
        self::assertSame(['Allow' => 'GET, POST'], $changed->getHeaders());
        self::assertSame([], $original->getHeaders(), 'withHeader() must not modify the original');
    }

    public function testWithHeaderReplacesExistingHeader(): void
    {
        $response = Response::json([])->withHeader('Content-Type', 'text/plain');

        self::assertSame(['Content-Type' => 'text/plain'], $response->getHeaders());
    }

    /**
     * Runs in its own process: PHPUnit has already printed output in the main process,
     * so http_response_code() and header() would fail with "headers already sent".
     * Note: headers cannot be checked here, because the CLI never collects them.
     */
    #[RunInSeparateProcess]
    public function testSendOutputsBodyAndSetsStatus(): void
    {
        $response = Response::json(['status' => 'created'], HttpStatus::Created);

        $this->expectOutputString('{"status":"created"}');

        $response->send();

        self::assertSame(HttpStatus::Created->value, http_response_code());
    }
}
