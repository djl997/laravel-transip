<?php

namespace Djl997\LaravelTransip\Tests;

use Djl997\LaravelTransip\Facades\Transip;
use Djl997\LaravelTransip\TransipException;
use Djl997\LaravelTransip\TransipServiceProvider;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Orchestra\Testbench\TestCase;

class TransipTest extends TestCase
{
    private string $publicKey;

    protected function getPackageProviders($app): array
    {
        return [TransipServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048]);
        openssl_pkey_export($key, $pem);
        $this->publicKey = openssl_pkey_get_details($key)['key'];

        $app['config']->set('transip.login', 'jdoe');
        $app['config']->set('transip.private_key', $pem);
        $app['config']->set('cache.default', 'array');
    }

    public function test_authenticates_with_signed_body_and_caches_token(): void
    {
        Http::fake([
            '*/v6/auth' => Http::response(['token' => 'jwt-1']),
            '*/v6/domains*' => Http::response(['domains' => [['name' => 'example.com']]]),
        ]);

        $this->assertSame([['name' => 'example.com']], Transip::get('domains', ['pageSize' => 5])['domains']);
        Transip::get('/domains');

        Http::assertSentCount(3); // 1 auth + 2 calls: token is cached

        Http::assertSent(function (Request $r) {
            if (! str_ends_with($r->url(), '/v6/auth')) {
                return false;
            }
            $body = json_decode($r->body(), true);

            return $body['login'] === 'jdoe'
                && strlen($body['nonce']) === 32
                && $body['global_key'] === true
                && openssl_verify($r->body(), base64_decode($r->header('Signature')[0]), $this->publicKey, OPENSSL_ALGO_SHA512) === 1;
        });

        Http::assertSent(fn (Request $r) => $r->url() === 'https://api.transip.nl/v6/domains?pageSize=5'
            && $r->hasHeader('Authorization', 'Bearer jwt-1'));
    }

    public function test_sends_json_body_and_test_flag(): void
    {
        config(['transip.token' => 'demo']);
        $this->app->forgetInstance(\Djl997\LaravelTransip\Transip::class);
        Transip::clearResolvedInstances();
        config(['transip.test_mode' => true]);

        Http::fake(['*' => Http::response(null, 204)]);

        $entry = ['dnsEntry' => ['name' => 'www', 'expire' => 300, 'type' => 'A', 'content' => '1.2.3.4']];
        $this->assertSame([], Transip::delete('domains/example.com/dns', $entry));
        Transip::get('domains', ['page' => 2]);

        Http::assertSent(fn (Request $r) => $r->method() === 'DELETE'
            && $r->url() === 'https://api.transip.nl/v6/domains/example.com/dns?test=1'
            && $r->data() === $entry
            && $r->hasHeader('Authorization', 'Bearer demo'));

        Http::assertSent(fn (Request $r) => $r->method() === 'GET'
            && str_contains($r->url(), 'test=1') && str_contains($r->url(), 'page=2'));

        Http::assertNotSent(fn (Request $r) => str_ends_with($r->url(), '/auth'));
    }

    public function test_reauthenticates_once_on_401(): void
    {
        Http::fake([
            '*/v6/auth' => Http::sequence()->push(['token' => 'old'])->push(['token' => 'new']),
            '*/v6/vps' => fn (Request $r) => $r->hasHeader('Authorization', 'Bearer new')
                ? Http::response(['vpss' => []])
                : Http::response(['error' => 'Your access token has been revoked.'], 401),
        ]);

        $this->assertSame(['vpss' => []], Transip::get('vps'));
    }

    public function test_throws_api_error_message(): void
    {
        Http::fake([
            '*/v6/auth' => Http::response(['token' => 'jwt']),
            '*' => Http::response(['error' => "Domain with name 'nope.nl' not found"], 404),
        ]);

        try {
            Transip::get('domains/nope.nl');
            $this->fail('No exception thrown');
        } catch (TransipException $e) {
            $this->assertSame("Domain with name 'nope.nl' not found", $e->getMessage());
            $this->assertSame(404, $e->getCode());
        }
    }

    public function test_bad_private_key_throws(): void
    {
        config(['transip.private_key' => 'not a key']);
        $this->app->forgetInstance(\Djl997\LaravelTransip\Transip::class);
        Transip::clearResolvedInstances();
        Http::fake();

        $this->expectException(TransipException::class);
        Transip::get('domains');
    }
}
