<?php

namespace Recurrente\Laravel\Webhooks;

/**
 * Representa un evento de webhook de Recurrente ya parseado.
 */
class WebhookEvent
{
    /**
     * @param  array<string,mixed>  $data     Cuerpo del recurso (checkout / subscription / payment)
     * @param  array<string,mixed>  $raw      Payload completo decodificado
     */
    public function __construct(
        public readonly string $type,
        public readonly ?string $id,
        public readonly array $data,
        public readonly array $raw = [],
    ) {}

    /**
     * @param  array<string,mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        // Recurrente envía típicamente { event_type | type, id, data: {...} } —
        // soportamos variantes comunes para ser tolerantes.
        $type = $payload['event_type']
            ?? $payload['type']
            ?? $payload['event']
            ?? 'unknown';

        $id = $payload['id']
            ?? $payload['event_id']
            ?? ($payload['data']['id'] ?? null);

        $data = $payload['data'] ?? $payload;

        return new self((string) $type, $id !== null ? (string) $id : null, is_array($data) ? $data : [], $payload);
    }

    public function metadata(): array
    {
        return $this->data['metadata'] ?? ($this->raw['metadata'] ?? []);
    }

    public function metaValue(string $key, mixed $default = null): mixed
    {
        return $this->metadata()[$key] ?? $default;
    }

    public function isPaymentCompleted(): bool
    {
        return in_array($this->type, ['payment.completed', 'payment_intent.succeeded', 'checkout.completed'], true);
    }

    public function isPaymentFailed(): bool
    {
        return in_array($this->type, ['payment.failed', 'payment_intent.failed'], true);
    }

    public function isSubscriptionCreated(): bool
    {
        return in_array($this->type, ['subscription.created', 'subscription.activated'], true);
    }

    public function isSubscriptionCancelled(): bool
    {
        return in_array($this->type, ['subscription.cancelled', 'subscription.canceled'], true);
    }
}
