<?php

namespace Recurrente\Laravel\Tests;

use Illuminate\Support\Facades\Http;
use Recurrente\Laravel\DataObjects\CheckoutData;
use Recurrente\Laravel\Exceptions\RecurrenteException;
use Recurrente\Laravel\RecurrenteClient;

class CheckoutTest extends TestCase
{
    public function test_creates_a_checkout_with_price(): void
    {
        Http::fake([
            'app.recurrente.com/api/checkouts' => Http::response([
                'id'           => 'ch_123',
                'checkout_url' => 'https://app.recurrente.com/checkout/ch_123',
            ], 201),
        ]);

        $client = new RecurrenteClient('sk_test_abc');

        $result = $client->checkouts()->create(
            CheckoutData::make()
                ->withPrice('price_abc')
                ->successUrl('https://app.test/exito')
                ->cancelUrl('https://app.test/cancelado')
                ->metadata(['organization_id' => 7, 'plan_id' => 2])
        );

        $this->assertSame('ch_123', $result['id']);
        $this->assertStringContainsString('checkout/ch_123', $result['checkout_url']);

        Http::assertSent(function ($request) {
            $body = $request->data();

            return $request->hasHeader('X-SECRET-KEY', 'sk_test_abc')
                && $body['items'][0]['price_id'] === 'price_abc'
                && $body['metadata']['organization_id'] === 7;
        });
    }

    public function test_throws_without_secret_key(): void
    {
        $this->expectException(RecurrenteException::class);

        (new RecurrenteClient(null))->checkouts()->get('ch_1');
    }

    public function test_throws_on_api_error(): void
    {
        Http::fake([
            'app.recurrente.com/api/checkouts' => Http::response(['message' => 'Monto inválido'], 422),
        ]);

        $this->expectException(RecurrenteException::class);
        $this->expectExceptionMessage('Monto inválido');

        (new RecurrenteClient('sk_test_abc'))->checkouts()->create(
            CheckoutData::make()->withInlineItem('Plan', 100)
        );
    }
}
