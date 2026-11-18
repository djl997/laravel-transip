# Laravel TransIP

A small Laravel client for the [TransIP REST API v6](https://api.transip.nl/rest/docs.html). It signs the auth request with your private key, caches the access token, and gives you `get` / `post` / `put` / `patch` / `delete` for every endpoint.

## Install

```bash
composer require djl997/laravel-transip
php artisan vendor:publish --tag=transip-config   # optional
```

`.env`:

```dotenv
TRANSIP_LOGIN=your-username
TRANSIP_PRIVATE_KEY=/path/to/transip.key   # path to the PEM file, or the PEM contents in quotes
# TRANSIP_GLOBAL_KEY=false   # only when your key is limited to whitelisted IPs
# TRANSIP_READ_ONLY=true
# TRANSIP_TEST_MODE=true     # adds ?test=1: TransIP validates calls but doesn't run them
# TRANSIP_TOKEN=...          # use a fixed token (such as the demo token) instead of key auth
```

Make a key pair in the TransIP control panel under *Account → API*.

## Usage

Paths are relative to `https://api.transip.nl/v6`. Each call returns the decoded JSON as an array (`[]` for empty responses).

```php
use Djl997\LaravelTransip\Facades\Transip;

Transip::get('domains', ['pageSize' => 25, 'page' => 1])['domains'];
Transip::get('domains/example.com/dns')['dnsEntries'];

Transip::post('domains/example.com/dns', [
    'dnsEntry' => ['name' => 'www', 'expire' => 300, 'type' => 'A', 'content' => '1.2.3.4'],
]);

Transip::delete('domains/example.com/dns', [
    'dnsEntry' => ['name' => 'www', 'expire' => 300, 'type' => 'A', 'content' => '1.2.3.4'],
]);

Transip::patch('vps/example-vps', ['action' => 'reset']);
```

You can also inject `Djl997\LaravelTransip\Transip` instead of using the facade.

### Errors

A non-2xx response throws `Djl997\LaravelTransip\TransipException`. The message is TransIP's `error` text, the code is the HTTP status, and `$e->response` holds the full response. On a `401` the client gets a new token and retries once.

### Rate limits

TransIP allows 1000 requests per 15 minutes. Read the `X-Rate-Limit-*` headers from `$e->response` when you get a `429`.

## Testing

```bash
composer test
```

In your own tests, use `Http::fake()`. Set `transip.token` so no auth call is made.
