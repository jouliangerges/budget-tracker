<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Core\Request;
use App\Core\Response;

/**
 * Test double for controllers: remembers which action was called with which arguments.
 */
final class SpyController
{
    /** @var list<array{action: string, request: Request, params: array<string, string>}> */
    public array $calls = [];

    public function index(Request $request): Response
    {
        $this->calls[] = ['action' => 'index', 'request' => $request, 'params' => []];

        return Response::json(['action' => 'index']);
    }

    public function show(Request $request, string $id): Response
    {
        $this->calls[] = ['action' => 'show', 'request' => $request, 'params' => ['id' => $id]];

        return Response::json(['action' => 'show']);
    }

    public function showNested(Request $request, string $categoryId, string $id): Response
    {
        $this->calls[] = [
            'action' => 'showNested',
            'request' => $request,
            'params' => ['categoryId' => $categoryId, 'id' => $id],
        ];

        return Response::json(['action' => 'showNested']);
    }
}
