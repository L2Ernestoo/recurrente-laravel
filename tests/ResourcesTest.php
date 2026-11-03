<?php

namespace Recurrente\Laravel\Tests;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Recurrente\Laravel\RecurrenteClient;

class ResourcesTest extends TestCase
{
    protected RecurrenteClient $client;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake(['*' => Http::response(['id' => 'x'])]);
        $this->client = new RecurrenteClient('sk_test_abc');
    }

    protected function assertRequest(string $method, string $path, ?array $body = null): void
    {
        Http::assertSent(function (Request $r) use ($method, $path, $body) {
            return $r->method() === $method
                && $r->url() === 'https://app.recurrente.com/api'.$path
                && ($body === null || $r->data() === $body);
        });
    }

    public function test_customers(): void
    {
        $this->client->customers()->createCustomer(['email' => 'a@b.c']);
        $this->assertRequest('POST', '/customers', ['email' => 'a@b.c']);

        $this->client->customers()->get('cus_1');
        $this->assertRequest('GET', '/customers/cus_1');

        // IDs de usuario devueltos por el create() de la 1.0 siguen yendo a /users.
        $this->client->customers()->get('us_1');
        $this->assertRequest('GET', '/users/us_1');

        $this->client->customers()->update('cus_1', ['phone' => '1']);
        $this->assertRequest('PUT', '/customers/cus_1', ['phone' => '1']);

        $this->client->customers()->create(['email' => 'a@b.c']);
        $this->assertRequest('POST', '/users', ['email' => 'a@b.c']);

        $this->client->users()->create(['email' => 'u@b.c']);
        $this->assertRequest('POST', '/users', ['email' => 'u@b.c']);
    }

    public function test_products(): void
    {
        $this->client->products()->list(['page' => 2]);
        $this->assertRequest('GET', '/products?page=2');

        $this->client->products()->update('prod_1', ['name' => 'Pro']);
        $this->assertRequest('PUT', '/products/prod_1', ['product' => ['name' => 'Pro']]);

        $this->client->products()->update('prod_2', ['product' => ['name' => 'Pro']]);
        $this->assertRequest('PUT', '/products/prod_2', ['product' => ['name' => 'Pro']]);
    }

    public function test_subscriptions(): void
    {
        $this->client->subscriptions()->update('su_1', ['price_id' => 'p']);
        $this->assertRequest('PUT', '/subscriptions/su_1', ['price_id' => 'p']);

        $this->client->subscriptions()->previewProration('su_1', ['price_id' => 'p']);
        $this->assertRequest('POST', '/subscriptions/su_1/proration_preview');

        $this->client->subscriptions()->cancel('su_1');
        $this->assertRequest('DELETE', '/subscriptions/su_1');

        $this->client->subscriptions()->invoiceItems('su_1');
        $this->assertRequest('GET', '/subscriptions/su_1/invoice_items');

        $this->client->subscriptions()->cancelInvoiceItem('su_1', 'ii_1', 'key-1');
        Http::assertSent(fn (Request $r) => $r->method() === 'DELETE'
            && str_ends_with($r->url(), '/subscriptions/su_1/invoice_items/ii_1')
            && $r->hasHeader('Idempotency-Key', 'key-1'));
    }

    public function test_refunds(): void
    {
        $this->client->refunds()->create('in_1', idempotencyKey: 'refund-9');
        Http::assertSent(fn (Request $r) => $r->method() === 'POST'
            && str_ends_with($r->url(), '/refunds')
            && $r->data() === ['intent_id' => 'in_1']
            && $r->hasHeader('Idempotency-Key', 'refund-9'));

        $this->client->refunds()->create('pa_1', amountInCents: 500, refundApplicationFee: true);
        $this->assertRequest('POST', '/refunds', [
            'payment_intent_id'      => 'pa_1',
            'amount_in_cents'        => 500,
            'refund_application_fee' => true,
        ]);

        $this->client->refunds()->void('rf_1');
        $this->assertRequest('POST', '/refunds/rf_1/void');

        $this->client->refunds()->get('rf_1');
        $this->assertRequest('GET', '/refunds/rf_1');
    }

    public function test_intents_coupons_webhook_endpoints_and_checkout_update(): void
    {
        $this->client->intents()->get('in_1');
        $this->assertRequest('GET', '/intents/in_1');

        $this->client->coupons()->create(['code' => 'X']);
        $this->assertRequest('POST', '/coupons', ['coupon' => ['code' => 'X']]);

        $this->client->coupons()->delete('co_1');
        $this->assertRequest('DELETE', '/coupons/co_1');

        $this->client->webhookEndpoints()->create('https://app.test/hook');
        $this->assertRequest('POST', '/webhook_endpoints', ['url' => 'https://app.test/hook']);

        $this->client->checkouts()->update('ch_1', ['metadata' => ['a' => 1]]);
        $this->assertRequest('PATCH', '/checkouts/ch_1', ['metadata' => ['a' => 1]]);
    }
}
