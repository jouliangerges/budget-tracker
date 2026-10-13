<?php

declare(strict_types=1);

namespace App\Core;

use JsonException;

final class Request
{
    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $body
     */
    public function __construct(
        private readonly string $method,
        private readonly string $path,
        private readonly array $query,
        private readonly array $body,
    ) {}

    /**
     * @throws JsonException if the body is not a valid JSON object
     */
    public static function fromGlobals(): self
    {
        return self::fromArrays($_SERVER, $_GET, (string) file_get_contents('php://input'));
    }

    /**
     * @param array<string, mixed> $server
     * @param array<string, mixed> $query
     * @throws JsonException if the body is not a valid JSON object
     */
    public static function fromArrays(array $server, array $query, string $rawBody): self
    {
        $method = strtoupper($server['REQUEST_METHOD'] ?? 'GET');

        $path = parse_url($server['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $path = rtrim($path, '/') ?: '/';
        $body = self::parseJsonBody($rawBody);

        return new self($method, $path, $query, $body);
    }

    /**
     * @return array<string, mixed>
     * @throws JsonException
     */
    private static function parseJsonBody(string $raw): array
    {
        if (trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($decoded)) {
            throw new JsonException('Request body must be a JSON object');
        }

        return $decoded;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getQuery(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function getBodyParam(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $default;
    }

    public function getBody(): array
    {
        return $this->body;
    }
}
