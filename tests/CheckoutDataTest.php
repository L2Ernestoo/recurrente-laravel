<?php

namespace Recurrente\Laravel\Tests;

use Recurrente\Laravel\DataObjects\CheckoutData;

class CheckoutDataTest extends TestCase
{
    public function test_payment_methods_send_current_api_field(): void
    {
        $item = CheckoutData::make()
            ->withInlineItem('Consulta', 15000)
            ->paymentMethods(card: true, bankTransfer: false)
            ->toArray()['items'][0];

        $this->assertSame(['card'], $item['payment_method_types']);
        $this->assertSame('false', $item['bank_transfer_payments_enabled']);
    }

    public function test_explicit_payment_method_types(): void
    {
        $data = CheckoutData::make()
            ->withInlineItem('A', 1000)
            ->withInlineItem('B', 1000, extra: ['payment_method_types' => ['balance']])
            ->paymentMethodTypes(['card', 'bank_transfer', 'balance'])
            ->toArray();

        $this->assertSame(['card', 'bank_transfer', 'balance'], $data['items'][0]['payment_method_types']);
        $this->assertSame(['balance'], $data['items'][1]['payment_method_types']); // el item manda
    }

    public function test_payment_methods_do_not_touch_price_items(): void
    {
        $item = CheckoutData::make()->withPrice('price_1')->paymentMethods(card: true, bankTransfer: false)->toArray()['items'][0];

        $this->assertSame(['price_id' => 'price_1', 'quantity' => 1], $item);
    }

    public function test_new_fields(): void
    {
        $data = CheckoutData::make()
            ->withProduct('prod_1', 2, ['metadata' => ['row' => 1]])
            ->withPrice('price_1', 1, ['tax_category' => 'service'])
            ->customerId('cus_1')
            ->discountCode('PROMO')
            ->setupMode()
            ->bankTransferMemo('ORDEN123')
            ->claveEnabled(false)
            ->with(['application_fee_amount' => 100])
            ->toArray();

        $this->assertSame(['product_id' => 'prod_1', 'quantity' => 2, 'metadata' => ['row' => 1]], $data['items'][0]);
        $this->assertSame(['price_id' => 'price_1', 'quantity' => 1, 'tax_category' => 'service'], $data['items'][1]);
        $this->assertSame('cus_1', $data['customer_id']);
        $this->assertSame('PROMO', $data['discount_code']);
        $this->assertSame('setup', $data['mode']);
        $this->assertSame('ORDEN123', $data['bank_transfer_memo']);
        $this->assertFalse($data['clave_enabled']);
        $this->assertSame(100, $data['application_fee_amount']);
    }
}
