<?php

namespace Recurrente\Laravel\Tests;

use Recurrente\Laravel\Webhooks\WebhookEvent;
use Recurrente\Laravel\Webhooks\WebhookVerifier;

class WebhookVerifierTest extends TestCase
{
    public function test_verifies_valid_svix_signature(): void
    {
        $secret    = 'whsec_'.base64_encode('super-secret-key');
        $rawSecret = base64_decode(substr($secret, 6));

        $id        = 'msg_123';
        $timestamp = (string) time();
        $payload   = '{"event_type":"payment.completed","data":{"id":"ch_1"}}';

        $signature = base64_encode(hash_hmac('sha256', "{$id}.{$timestamp}.{$payload}", $rawSecret, true));

        $verifier = new WebhookVerifier(scheme: 'svix');

        $ok = $verifier->verify($payload, [
            'svix-id'        => $id,
            'svix-timestamp' => $timestamp,
            'svix-signature' => "v1,{$signature}",
        ], $secret);

        $this->assertTrue($ok);
    }

    public function test_rejects_tampered_payload(): void
    {
        $secret    = 'whsec_'.base64_encode('super-secret-key');
        $rawSecret = base64_decode(substr($secret, 6));
        $id        = 'msg_123';
        $timestamp = (string) time();

        $signature = base64_encode(hash_hmac('sha256', "{$id}.{$timestamp}.original", $rawSecret, true));

        $verifier = new WebhookVerifier(scheme: 'svix');

        $this->assertFalse($verifier->verify('tampered', [
            'svix-id'        => $id,
            'svix-timestamp' => $timestamp,
            'svix-signature' => "v1,{$signature}",
        ], $secret));
    }

    public function test_verifies_plain_hmac_scheme(): void
    {
        $secret  = 'my-secret';
        $payload = '{"event_type":"subscription.cancelled"}';
        $sig     = hash_hmac('sha256', $payload, $secret);

        $verifier = new WebhookVerifier(scheme: 'hmac', signatureHeader: 'X-Recurrente-Signature');

        $this->assertTrue($verifier->verify($payload, [
            'X-Recurrente-Signature' => "sha256={$sig}",
        ], $secret));
    }

    public function test_parses_event(): void
    {
        $event = WebhookEvent::fromArray([
            'event_type' => 'payment.completed',
            'id'         => 'evt_9',
            'data'       => ['id' => 'ch_1', 'metadata' => ['organization_id' => 5]],
        ]);

        $this->assertTrue($event->isPaymentCompleted());
        $this->assertSame('evt_9', $event->id);
        $this->assertSame(5, $event->metaValue('organization_id'));
    }
}
