<?php

declare(strict_types=1);

namespace App\Core;

enum HttpStatus: int
{
    case Ok = 200;
    case Created = 201;
    case NoContent = 204;
    case BadRequest = 400;
    case NotFound = 404;
    case MethodNotAllowed = 405;
    case UnprocessableContent = 422;
    case InternalServerError = 500;

    public function getReasonPhrase(): string
    {
        return match ($this) {
            self::Ok => 'OK',
            self::Created => 'Created',
            self::NoContent => 'No Content',
            self::BadRequest => 'Bad Request',
            self::NotFound => 'Not Found',
            self::MethodNotAllowed => 'Method Not Allowed',
            self::UnprocessableContent => 'Unprocessable Content',
            self::InternalServerError => 'Internal Server Error',
        };
    }
}
