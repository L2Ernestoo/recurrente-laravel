<?php

namespace Recurrente\Laravel\Tests;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Recurrente\Laravel\Facades\Recurrente;
use Recurrente\Laravel\RecurrenteClient;

class ClientTest extends TestCase
{
    public function test_does_not_send_account_header_by_default(): void
    {
        Http::fake(['*' => Http::response(['environment' => 'sandbox'])]);

        $result = (new RecurrenteClient('sk_test_abc'))->test();

        $this->assertSame('sandbox', $result['environment']);
        Http::assertSent(fn (Request $r) => $r->url() === 'https://app.recurrente.com/api/test'
            && $r->method() === 'GET'
            && ! $r->hasHeader('X-ACCOUNT-ID'));
    }

    public function test_for_account_sends_header_without_mutating_original(): void
    {
        Http::fake(['*' => Http::response([])]);

        $client    = new RecurrenteClient('sk_test_abc');
        $connected = $client->forAccount('ac_123');

        $connected->checkouts()->get('ch_1');
        $client->checkouts()->get('ch_2');

        $this->assertNull($client->accountId());
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'ch_1') && $r->hasHeader('X-ACCOUNT-ID', 'ac_123'));
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'ch_2') && ! $r->hasHeader('X-ACCOUNT-ID'));
    }

    public function test_account_id_from_config(): void
    {
        config(['recurrente.secret_key' => 'sk_test_cfg', 'recurrente.account_id' => 'ac_cfg']);
        $this->app->forgetInstance(RecurrenteClient::class);
        Recurrente::clearResolvedInstances();
        Http::fake(['*' => Http::response([])]);

        Recurrente::products()->list();

        Http::assertSent(fn (Request $r) => $r->hasHeader('X-SECRET-KEY', 'sk_test_cfg') && $r->hasHeader('X-ACCOUNT-ID', 'ac_cfg'));
    }

    public function test_put_and_custom_headers(): void
    {
        Http::fake(['*' => Http::response(['ok' => true])]);

        (new RecurrenteClient('sk'))->put('/subscriptions/su_1', ['a' => 1], ['Idempotency-Key' => 'k1']);

        Http::assertSent(fn (Request $r) => $r->method() === 'PUT'
            && $r->url() === 'https://app.recurrente.com/api/subscriptions/su_1'
            && $r->hasHeader('Idempotency-Key', 'k1')
            && $r->hasHeader('X-SECRET-KEY', 'sk')
            && $r->data() === ['a' => 1]);
    }
}
