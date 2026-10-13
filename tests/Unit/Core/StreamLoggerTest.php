<?php

declare(strict_types=1);

namespace App\Tests\Unit\Core;

use App\Core\StreamLogger;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(StreamLogger::class)]
final class StreamLoggerTest extends TestCase
{
    private string $file;

    protected function setUp(): void
    {
        $this->file = tempnam(sys_get_temp_dir(), 'log');
    }

    protected function tearDown(): void
    {
        unlink($this->file);
    }

    public function testLogWritesTimestampLevelAndMessage(): void
    {
        $logger = new StreamLogger($this->file);

        $logger->warning('Disk almost full');

        self::assertMatchesRegularExpression(
            '/^\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\] WARNING: Disk almost full\n$/',
            file_get_contents($this->file),
        );
    }

    public function testLogAppendsInsteadOfOverwriting(): void
    {
        $logger = new StreamLogger($this->file);

        $logger->info('first');
        $logger->info('second');

        $lines = file($this->file, FILE_IGNORE_NEW_LINES);
        self::assertCount(2, $lines);
        self::assertStringEndsWith('INFO: first', $lines[0]);
        self::assertStringEndsWith('INFO: second', $lines[1]);
    }

    public function testLogAppendsExceptionFromContext(): void
    {
        $logger = new StreamLogger($this->file);
        $exception = new RuntimeException('Boom');

        $logger->error('Request failed', ['exception' => $exception]);

        $content = file_get_contents($this->file);
        self::assertStringContainsString('ERROR: Request failed' . PHP_EOL . 'RuntimeException: Boom', $content);
        self::assertStringContainsString('Stack trace:', $content);
    }

    public function testLogIgnoresExceptionKeyThatIsNoThrowable(): void
    {
        $logger = new StreamLogger($this->file);

        $logger->error('Request failed', ['exception' => 'not a throwable']);

        self::assertStringEndsWith('ERROR: Request failed' . PHP_EOL, file_get_contents($this->file));
    }
}
