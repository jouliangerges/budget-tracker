<?php

declare(strict_types=1);

namespace App\Core;

use Psr\Log\AbstractLogger;
use Throwable;

final class StreamLogger extends AbstractLogger
{
    public function __construct(
        private readonly string $stream = 'php://stderr'
    ) {}

    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $logMessage = '[' . date('Y-m-d H:i:s') . '] ' . strtoupper($level) . ': ' . (string) $message;

        if (
            isset($context['exception']) &&
            $context['exception'] instanceof Throwable
        ) {
            $logMessage .= PHP_EOL . (string) $context['exception'];
        }

        $logMessage .= PHP_EOL;

        file_put_contents($this->stream, $logMessage, FILE_APPEND);
    }
}
