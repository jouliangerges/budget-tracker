<?php

declare(strict_types=1);

namespace App\Core;

use JsonException;
use Throwable;
use Psr\Log\LoggerInterface;

final class ErrorHandler
{
    public function __construct(
        private readonly bool $debug,
        private readonly LoggerInterface $logger
    ) {}

    public function toResponse(Throwable $e): Response
    {
        $status = 500;
        $message = "Internal Server Error";

        if ($e instanceof JsonException) {

            $status = 400;
            $message = "Invalid JSON body";
        }

        if ($status === 500) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);
        }
        
        $body = ['error' => $message];

        if ($this->debug) {
            $body['debug'] = [
                'exception' => $e::class,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ];
        }

        return Response::json($body, $status);
    }
}
