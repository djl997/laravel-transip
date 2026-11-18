# Laravel TransIP

[![Version](https://img.shields.io/badge/version-v6.55.0-blue)](https://github.com/djl997/laravel-transip/releases/tag/v6.55.0)
[![TransIP API](https://img.shields.io/badge/TransIP%20API-v6.55-blue)](https://api.transip.nl/rest/docs.html)
[![Tests](https://img.shields.io/badge/tests-passing-brightgreen)](#testing)

A small Laravel client for the [TransIP REST API v6](https://api.transip.nl/rest/docs.html). It signs the auth request with your private key, caches the access token, and gives you a typed method for every documented endpoint, plus `get` / `post` / `put` / `patch` / `delete` for raw calls.

Checked against **TransIP API v6.55** (293 endpoints).

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

Each call returns the decoded JSON as an array (`[]` for empty responses).

### Resources

The resources follow the groups in the TransIP docs: `general()`, `account()`, `domains()`, `forwardingService()`, `vps()`, `haip()`, `kubernetes()`, `acronis()`, `filehosting()`, `openStack()`, `sslCertificates()`, `colocations()`, `email()` and `actions()`.

```php
use Djl997\LaravelTransip\Facades\Transip;

Transip::domains()->checkAvailability('xyz.nl')['availability']['status'];
Transip::domains()->register(['domainName' => 'xyz.nl']);
Transip::domains()->list(['include' => 'nameservers'])['domains'];
Transip::domains()->addDnsEntry('example.com', [
    'dnsEntry' => ['name' => 'www', 'expire' => 300, 'type' => 'A', 'content' => '1.2.3.4'],
]);

Transip::vps()->start('example-vps');   // sends {"action": "start"} for you
Transip::vps()->createSnapshot('example-vps', ['description' => 'before upgrade']);
```

Every method's docblock has the TransIP description, the options with their types and descriptions, the HTTP method and path, a link to the docs, and array shapes for `$data` and the result. Your IDE (and AI agents) can complete the option keys and response fields without the docs. For GET calls, `$data` is sent as the query string. Otherwise it's the JSON body.

### Raw calls

Paths are relative to `https://api.transip.nl/v6`.

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

## Updating to a new TransIP API version

The resources are generated from TransIP's [API Blueprint](https://api.transip.nl/rest/blueprint.apib), the source of the docs page:

```bash
composer generate
```

This rewrites `src/Resources/*`, `src/HasResources.php` and the `@method` lines in the facade, then runs Pint. If TransIP adds a method name that collides or reads badly, the script stops and points you to `METHOD_NAMES` in `bin/generate-resources`. Update the version above after you've checked the diff.

## Testing

```bash
composer test
```

In your own tests, use `Http::fake()`. Set `transip.token` so no auth call is made.
