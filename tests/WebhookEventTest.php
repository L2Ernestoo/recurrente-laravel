<?php

namespace Recurrente\Laravel\Tests;

use Illuminate\Http\Request;
use Recurrente\Laravel\Webhooks\EventType;
use Recurrente\Laravel\Webhooks\WebhookEvent;

class WebhookEventTest extends TestCase
{
    /** Payload real (abreviado) del formato unificado, según docs de Recurrente. */
    protected function unifiedPayload(): array
    {
        return [
            'type'            => 'payment',
            'event_type'      => 'intent.succeeded',
            'api_version'     => '2026-06-01',
            'id'              => 'in_id123',
            'legacy_id'       => 'pa_id123',
            'status'          => 'succeeded',
            'customer_id'     => 'cus_id123',
            'amount_in_cents' => 25000,
            'currency'        => 'GTQ',
            'product'         => ['id' => 'prod_id123'],
            'checkout'        => ['id' => 'ch_id123', 'status' => 'paid', 'metadata' => ['organization_id' => 5]],
            'payment'         => ['id' => 'pa_id123'],
            'subscription'    => ['id' => 'su_id123'],
        ];
    }

    public function test_parses_unified_intent_event(): void
    {
        $event = WebhookEvent::fromArray($this->unifiedPayload());

        $this->assertTrue($event->isIntentSucceeded());
        $this->assertTrue($event->is(EventType::INTENT_SUCCEEDED, EventType::INTENT_PAID));
        $this->assertSame('in_id123', $event->id);
        $this->assertSame('pa_id123', $event->paymentId());
        $this->assertSame('ch_id123', $event->checkoutId());
        $this->assertSame('cus_id123', $event->customerId());
        $this->assertSame('su_id123', $event->subscriptionId());
        $this->assertSame(25000, $event->amountInCents());
        $this->assertSame('GTQ', $event->currency());
        $this->assertSame('succeeded', $event->status());
        $this->assertSame(5, $event->metaValue('organization_id'));
    }

    public function test_payment_id_is_the_same_in_both_formats(): void
    {
        $unified = WebhookEvent::fromArray($this->unifiedPayload());
        $legacy  = WebhookEvent::fromArray(['event_type' => 'payment_intent.succeeded', 'id' => 'pa_id123']);

        $this->assertSame($unified->paymentId(), $legacy->paymentId());
    }

    public function test_subscription_events(): void
    {
        $created = WebhookEvent::fromArray(['event_type' => 'subscription.create', 'id' => 'su_1']);
        $ended   = WebhookEvent::fromArray(['event_type' => 'subscription.cancel', 'id' => 'su_1']);

        $this->assertTrue($created->isSubscriptionStarted());
        $this->assertTrue($ended->isSubscriptionEnded());
        $this->assertSame('su_1', $ended->subscriptionId());
        $this->assertTrue(WebhookEvent::fromArray(['event_type' => 'subscription.past_due'])->isSubscriptionPastDue());
        $this->assertTrue(WebhookEvent::fromArray(['event_type' => 'refund.create'])->isRefundCreated());
    }

    public function test_from_request_captures_svix_id_as_idempotency_key(): void
    {
        $request = Request::create('/hook', 'POST', server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_SVIX_ID' => 'msg_abc',
        ], content: json_encode($this->unifiedPayload()));

        $event = WebhookEvent::fromRequest($request);

        $this->assertSame('msg_abc', $event->messageId);
        $this->assertSame('msg_abc', $event->idempotencyKey());
        $this->assertTrue($event->isIntentSucceeded());
    }

    public function test_idempotency_key_fallback_and_non_array_metadata(): void
    {
        $event = WebhookEvent::fromArray(['event_type' => 'intent.failed', 'id' => 'in_9', 'metadata' => 'oops']);

        $this->assertSame('intent.failed:in_9', $event->idempotencyKey());
        $this->assertSame([], $event->metadata());
        $this->assertNull($event->checkoutId());
        $this->assertNull($event->amountInCents());
    }
}
