<?php

namespace Djl997\LaravelTransip\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array get(string $path, array $query = [])
 * @method static array post(string $path, array $data = [])
 * @method static array put(string $path, array $data = [])
 * @method static array patch(string $path, array $data = [])
 * @method static array delete(string $path, array $data = [])
 * @method static array send(string $method, string $path, array $data = [])
 * @method static string token()
 *
 * @see \Djl997\LaravelTransip\Transip
 */
class Transip extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Djl997\LaravelTransip\Transip::class;
    }
}
