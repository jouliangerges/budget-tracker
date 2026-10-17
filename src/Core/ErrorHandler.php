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
        $status = HttpStatus::InternalServerError;
        $message = HttpStatus::InternalServerError->getReasonPhrase();

        if ($e instanceof JsonException) {

            $status = HttpStatus::BadRequest;
            $message = "Invalid JSON body";
        }

        if ($status === HttpStatus::InternalServerError) {
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
