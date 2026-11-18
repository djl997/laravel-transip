<?php

namespace Djl997\LaravelTransip;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Http\Client\Factory as Http;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;

/**
 * Thin client for https://api.transip.nl/rest/docs.html. Paths are relative to /v6,
 * e.g. Transip::get('domains/example.com/dns'), or use the typed resources:
 * Transip::domains()->listDnsEntries('example.com').
 */
class Transip
{
    use HasResources;

    public function __construct(
        protected Http $http,
        protected Cache $cache,
        protected array $config,
    ) {}

    public function get(string $path, array $query = []): array
    {
        return $this->send('GET', $path, $query);
    }

    public function post(string $path, array $data = []): array
    {
        return $this->send('POST', $path, $data);
    }

    public function put(string $path, array $data = []): array
    {
        return $this->send('PUT', $path, $data);
    }

    public function patch(string $path, array $data = []): array
    {
        return $this->send('PATCH', $path, $data);
    }

    public function delete(string $path, array $data = []): array
    {
        return $this->send('DELETE', $path, $data);
    }

    /** Sends a request and returns the decoded JSON body ([] for empty responses). */
    public function send(string $method, string $path, array $data = []): array
    {
        $response = $this->request($method, $path, $data);

        // Cached token revoked or expired early: get a fresh one and retry once.
        if ($response->status() === 401 && ! $this->config['token']) {
            $this->cache->forget($this->cacheKey());
            $response = $this->request($method, $path, $data);
        }

        if ($response->failed()) {
            throw TransipException::fromResponse($response);
        }

        return $response->json() ?? [];
    }

    public function token(): string
    {
        if ($this->config['token']) {
            return $this->config['token'];
        }

        $ttl = strtotime('+'.$this->config['expiration_time']) - time();

        // ponytail: two workers on a cold cache both authenticate; harmless, just one extra token.
        return $this->cache->remember($this->cacheKey(), max($ttl - 60, 1), fn () => $this->authenticate());
    }

    protected function authenticate(): string
    {
        $body = json_encode([
            'login' => $this->config['login'],
            'nonce' => bin2hex(random_bytes(16)),
            'read_only' => (bool) $this->config['read_only'],
            'expiration_time' => $this->config['expiration_time'],
            'label' => 'laravel-transip-'.time().'-'.bin2hex(random_bytes(3)),
            'global_key' => (bool) $this->config['global_key'],
        ]);

        $key = (string) $this->config['private_key'];
        $key = is_file($key) ? file_get_contents($key) : $key;

        if (! @openssl_sign($body, $signature, $key, OPENSSL_ALGO_SHA512)) {
            throw new TransipException('Could not sign the TransIP auth request: check transip.private_key.');
        }

        $response = $this->http->baseUrl($this->config['endpoint'])
            ->acceptJson()
            ->withHeaders(['Signature' => base64_encode($signature)])
            ->withBody($body, 'application/json')
            ->post('auth');

        if ($response->failed() || ! $response->json('token')) {
            throw TransipException::fromResponse($response);
        }

        return $response->json('token');
    }

    protected function request(string $method, string $path, array $data): Response
    {
        $request = $this->http->baseUrl($this->config['endpoint'])
            ->acceptJson()
            ->withToken($this->token());

        if ($this->config['test_mode']) {
            $request->withQueryParameters(['test' => 1]);
        }

        $path = ltrim($path, '/');

        return $method === 'GET'
            ? $request->get($path, $data)
            : $this->withJson($request, $data)->send($method, $path);
    }

    protected function withJson(PendingRequest $request, array $data): PendingRequest
    {
        return $data === [] ? $request : $request->withBody(json_encode($data), 'application/json');
    }

    protected function cacheKey(): string
    {
        return 'transip.token.'.sha1($this->config['login'].'|'.$this->config['endpoint'].'|'.(int) $this->config['read_only'].'|'.(int) $this->config['global_key']);
    }
}
