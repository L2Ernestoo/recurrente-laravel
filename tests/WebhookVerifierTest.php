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

    protected function signedSvix(string $payload, string $secret): array
    {
        $id        = 'msg_123';
        $timestamp = (string) time();
        $signature = base64_encode(hash_hmac('sha256', "{$id}.{$timestamp}.{$payload}", base64_decode(substr($secret, 6)), true));

        return ['svix-id' => $id, 'svix-timestamp' => $timestamp, 'svix-signature' => "v1,{$signature}"];
    }

    public function test_null_secret_returns_false_instead_of_crashing(): void
    {
        $this->assertFalse((new WebhookVerifier())->verify('{}', [], null));
    }

    public function test_uses_configured_secret_when_none_given(): void
    {
        $secret = 'whsec_'.base64_encode('configured');
        config(['recurrente.webhook_secret' => $secret]);
        $this->app->forgetInstance(WebhookVerifier::class);

        $payload = '{"event_type":"intent.succeeded"}';

        $this->assertTrue($this->app->make(WebhookVerifier::class)->verify($payload, $this->signedSvix($payload, $secret)));
    }

    public function test_rejects_expired_timestamp(): void
    {
        $secret  = 'whsec_'.base64_encode('k');
        $payload = '{}';
        $headers = $this->signedSvix($payload, $secret);
        $old     = (string) (time() - 3600);
        $headers['svix-timestamp'] = $old;
        $headers['svix-signature'] = 'v1,'.base64_encode(hash_hmac('sha256', "msg_123.{$old}.{$payload}", 'k', true));

        $this->assertFalse((new WebhookVerifier(tolerance: 300))->verify($payload, $headers, $secret));
    }

    public function test_accepts_one_valid_signature_among_several_and_uppercase_scheme(): void
    {
        $secret  = 'whsec_'.base64_encode('k');
        $payload = '{}';
        $headers = $this->signedSvix($payload, $secret);
        $headers['svix-signature'] = 'v1,invalida '.$headers['svix-signature'];

        $this->assertTrue((new WebhookVerifier(scheme: 'SVIX'))->verify($payload, $headers, $secret));
    }

    public function test_middleware_rejects_invalid_and_accepts_valid_signatures(): void
    {
        $secret = 'whsec_'.base64_encode('mw');
        config(['recurrente.webhook_secret' => $secret]);
        $this->app->forgetInstance(WebhookVerifier::class);

        $this->app['router']->post('/hook', fn () => 'ok')->middleware('recurrente.webhook');

        $payload = '{"event_type":"intent.succeeded"}';
        $server  = ['CONTENT_TYPE' => 'application/json'];

        $this->call('POST', '/hook', [], [], [], $server, $payload)->assertStatus(400);

        foreach ($this->signedSvix($payload, $secret) as $name => $value) {
            $server['HTTP_'.strtoupper(str_replace('-', '_', $name))] = $value;
        }

        $this->call('POST', '/hook', [], [], [], $server, $payload)->assertOk()->assertSee('ok');
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
