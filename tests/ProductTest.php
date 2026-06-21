<?php

namespace Recurrente\Laravel\Tests;

use Illuminate\Support\Facades\Http;
use Recurrente\Laravel\RecurrenteClient;

class ProductTest extends TestCase
{
    public function test_creates_recurring_product_with_correct_payload(): void
    {
        Http::fake([
            'app.recurrente.com/api/products' => Http::response([
                'id'     => 'prod_123',
                'name'   => 'Pro (monthly)',
                'prices' => [['id' => 'price_abc', 'amount_in_cents' => 29900]],
            ], 201),
        ]);

        $client = new RecurrenteClient('sk_test_abc');

        $product = $client->products()->createRecurring(
            name: 'Pro (monthly)',
            amountInCents: 29900,
            currency: 'GTQ',
            billingInterval: 'month',
        );

        $this->assertSame('price_abc', $product['prices'][0]['id']);

        // El payload debe envolverse en "product" con "prices_attributes".
        Http::assertSent(function ($request) {
            $body = $request->data();

            return isset($body['product']['name'])
                && $body['product']['name'] === 'Pro (monthly)'
                && $body['product']['prices_attributes'][0]['charge_type'] === 'recurring'
                && $body['product']['prices_attributes'][0]['amount_in_cents'] === 29900
                && $body['product']['prices_attributes'][0]['billing_interval'] === 'month';
        });
    }
}
