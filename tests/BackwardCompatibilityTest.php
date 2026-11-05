<?php

namespace Recurrente\Laravel\Tests;

use Recurrente\Laravel\DataObjects\CheckoutData;
use Recurrente\Laravel\RecurrenteClient;
use Recurrente\Laravel\Webhooks\WebhookEvent;
use Recurrente\Laravel\Webhooks\WebhookVerifier;

/**
 * Fija el comportamiento de la 1.0 para que una 1.x no rompa integraciones
 * existentes. Si uno de estos tests falla, el cambio es incompatible.
 */
class BackwardCompatibilityTest extends TestCase
{
    public function test_price_checkout_payload_is_unchanged(): void
    {
        $payload = CheckoutData::make()
            ->withPrice('price_abc', 2)
            ->successUrl('https://app.test/ok')
            ->cancelUrl('https://app.test/cancel')
            ->userId('us_1')
            ->expiresAt('2026-12-31T00:00:00Z')
            ->metadata(['organization_id' => 7])
            ->toArray();

        $this->assertEquals([
            'items'       => [['price_id' => 'price_abc', 'quantity' => 2]],
            'success_url' => 'https://app.test/ok',
            'cancel_url'  => 'https://app.test/cancel',
            'user_id'     => 'us_1',
            'expires_at'  => '2026-12-31T00:00:00Z',
            'metadata'    => ['organization_id' => 7],
        ], $payload);
    }

    public function test_inline_checkout_without_payment_methods_is_unchanged(): void
    {
        $payload = CheckoutData::make()
            ->withInlineItem('Plan', 29900, 'GTQ', 'recurring', ['billing_interval' => 'month'])
            ->toArray();

        $this->assertEquals([
            'items' => [[
                'name'                           => 'Plan',
                'amount_in_cents'                => 29900,
                'currency'                       => 'GTQ',
                'charge_type'                    => 'recurring',
                'billing_interval'               => 'month',
                'custom_payment_method_settings' => true,
                'card_payments_enabled'          => 'true',
                'bank_transfer_payments_enabled' => 'true',
            ]],
        ], $payload);
    }

    public function test_legacy_payment_helpers_match_exactly_the_same_events(): void
    {
        $completed = ['payment.completed', 'payment_intent.succeeded', 'checkout.completed'];
        $failed    = ['payment.failed', 'payment_intent.failed'];

        foreach ($completed as $type) {
            $this->assertTrue(WebhookEvent::fromArray(['event_type' => $type])->isPaymentCompleted(), $type);
        }
        foreach ($failed as $type) {
            $this->assertTrue(WebhookEvent::fromArray(['event_type' => $type])->isPaymentFailed(), $type);
        }

        // Clave para no duplicar cobros en cuentas que reciben ambos formatos.
        $this->assertFalse(WebhookEvent::fromArray(['event_type' => 'intent.succeeded'])->isPaymentCompleted());
        $this->assertFalse(WebhookEvent::fromArray(['event_type' => 'intent.failed'])->isPaymentFailed());
    }

    public function test_deprecated_subscription_helpers_are_unchanged(): void
    {
        $this->assertTrue(WebhookEvent::fromArray(['event_type' => 'subscription.created'])->isSubscriptionCreated());
        $this->assertTrue(WebhookEvent::fromArray(['event_type' => 'subscription.canceled'])->isSubscriptionCancelled());
        $this->assertFalse(WebhookEvent::fromArray(['event_type' => 'subscription.create'])->isSubscriptionCreated());
        $this->assertFalse(WebhookEvent::fromArray(['event_type' => 'subscription.cancel'])->isSubscriptionCancelled());
    }

    public function test_event_constructor_and_fields_are_unchanged(): void
    {
        $event = new WebhookEvent('payment.completed', 'evt_1', ['metadata' => ['a' => 1]], ['x' => 1]);

        $this->assertSame('payment.completed', $event->type);
        $this->assertSame('evt_1', $event->id);
        $this->assertSame(1, $event->metaValue('a'));

        $nested = WebhookEvent::fromArray(['type' => 'x', 'data' => ['id' => 'ch_1']]);
        $this->assertSame('ch_1', $nested->id);
        $this->assertSame(['id' => 'ch_1'], $nested->data);
    }

    public function test_client_and_verifier_constructors_accept_1_0_arguments(): void
    {
        $client = new RecurrenteClient('sk', 'pk', 'https://example.test/api/', 10);
        $this->assertSame('https://example.test/api', $client->baseUrl());
        $this->assertSame('pk', $client->publicKey());

        $verifier = new WebhookVerifier('hmac', 'X-Sig', 0);
        $sig = hash_hmac('sha256', 'body', 'secret');
        $this->assertTrue($verifier->verify('body', ['X-Sig' => $sig], 'secret'));
        $this->assertFalse($verifier->verify('body', ['X-Sig' => $sig], ''));
    }
}
