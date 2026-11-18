<?php

namespace Djl997\LaravelTransip;

use Illuminate\Http\Client\Response;
use RuntimeException;

class TransipException extends RuntimeException
{
    public ?Response $response = null;

    public static function fromResponse(Response $response): self
    {
        $e = new self($response->json('error') ?? $response->body() ?: 'HTTP '.$response->status(), $response->status());
        $e->response = $response;

        return $e;
    }
}
