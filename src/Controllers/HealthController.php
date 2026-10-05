<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;

final class HealthController
{
    public function index(Request $request): Response
    {
        return Response::json(
            [
                'status' => 'ok'
            ]
        );
    }
}
