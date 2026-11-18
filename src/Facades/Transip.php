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
 * @method static \Djl997\LaravelTransip\Resources\General general() TransIP API: General
 * @method static \Djl997\LaravelTransip\Resources\Account account() TransIP API: Account
 * @method static \Djl997\LaravelTransip\Resources\Domains domains() TransIP API: Domains
 * @method static \Djl997\LaravelTransip\Resources\ForwardingService forwardingService() TransIP API: Forwarding service
 * @method static \Djl997\LaravelTransip\Resources\Vps vps() TransIP API: VPS
 * @method static \Djl997\LaravelTransip\Resources\Haip haip() TransIP API: HA-IP
 * @method static \Djl997\LaravelTransip\Resources\Kubernetes kubernetes() TransIP API: Kubernetes
 * @method static \Djl997\LaravelTransip\Resources\Acronis acronis() TransIP API: Acronis
 * @method static \Djl997\LaravelTransip\Resources\Filehosting filehosting() TransIP API: Filehosting
 * @method static \Djl997\LaravelTransip\Resources\OpenStack openStack() TransIP API: OpenStack
 * @method static \Djl997\LaravelTransip\Resources\SslCertificates sslCertificates() TransIP API: SSL Certificates
 * @method static \Djl997\LaravelTransip\Resources\Colocations colocations() TransIP API: Colocations
 * @method static \Djl997\LaravelTransip\Resources\Email email() TransIP API: Email
 * @method static \Djl997\LaravelTransip\Resources\Actions actions() TransIP API: Actions
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
