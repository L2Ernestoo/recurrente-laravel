<?php

namespace Recurrente\Laravel\Webhooks;

use Illuminate\Http\Request;

/**
 * Representa un evento de webhook de Recurrente ya parseado.
 */
class WebhookEvent
{
    /**
     * @param  array<string,mixed>  $data       Cuerpo del recurso (checkout / subscription / payment)
     * @param  array<string,mixed>  $raw        Payload completo decodificado
     * @param  string|null          $messageId  Header svix-id: único por entrega y estable entre reintentos
     */
    public function __construct(
        public readonly string $type,
        public readonly ?string $id,
        public readonly array $data,
        public readonly array $raw = [],
        public readonly ?string $messageId = null,
    ) {}

    /**
     * @param  array<string,mixed>  $payload
     */
    public static function fromArray(array $payload, ?string $messageId = null): self
    {
        // Recurrente envía { event_type, id, ... } con los datos en el nivel
        // superior; soportamos también variantes con "data" para ser tolerantes.
        $type = $payload['event_type']
            ?? $payload['type']
            ?? $payload['event']
            ?? 'unknown';

        $id = $payload['id']
            ?? $payload['event_id']
            ?? ($payload['data']['id'] ?? null);

        $data = $payload['data'] ?? $payload;

        return new self(
            (string) $type,
            $id !== null ? (string) $id : null,
            is_array($data) ? $data : [],
            $payload,
            $messageId,
        );
    }

    public static function fromRequest(Request $request): self
    {
        $messageId = $request->header('svix-id') ?? $request->header('webhook-id');

        return self::fromArray($request->json()->all(), $messageId);
    }

    // ── Tipo de evento ──────────────────────────────────────────────────────

    public function is(string ...$types): bool
    {
        return in_array($this->type, $types, true);
    }

    /** Cobro exitoso, formato unificado (intent.succeeded). */
    public function isIntentSucceeded(): bool
    {
        return $this->is(EventType::INTENT_SUCCEEDED);
    }

    /** Cobro fallido, formato unificado (intent.failed). */
    public function isIntentFailed(): bool
    {
        return $this->is(EventType::INTENT_FAILED);
    }

    /** Suscripción nueva (subscription.create). */
    public function isSubscriptionStarted(): bool
    {
        return $this->is(EventType::SUBSCRIPTION_CREATE);
    }

    /** Suscripción cancelada (subscription.cancel). */
    public function isSubscriptionEnded(): bool
    {
        return $this->is(EventType::SUBSCRIPTION_CANCEL);
    }

    public function isSubscriptionPastDue(): bool
    {
        return $this->is(EventType::SUBSCRIPTION_PAST_DUE);
    }

    public function isRefundCreated(): bool
    {
        return $this->is(EventType::REFUND_CREATE);
    }

    /**
     * Detecta payment_intent.succeeded (legacy). Las cuentas creadas después
     * del formato unificado solo reciben intent.succeeded: usa
     * isIntentSucceeded() o is(...) según lo que reciba tu cuenta.
     *
     * Se conserva sin cambios para no duplicar el procesamiento en cuentas que
     * reciben ambos formatos.
     */
    public function isPaymentCompleted(): bool
    {
        return in_array($this->type, ['payment.completed', 'payment_intent.succeeded', 'checkout.completed'], true);
    }

    /** Ver nota en isPaymentCompleted(); el equivalente unificado es isIntentFailed(). */
    public function isPaymentFailed(): bool
    {
        return in_array($this->type, ['payment.failed', 'payment_intent.failed'], true);
    }

    /**
     * @deprecated 1.1.0 Recurrente envía "subscription.create", que este método no
     *             detecta. Usa isSubscriptionStarted().
     */
    public function isSubscriptionCreated(): bool
    {
        return in_array($this->type, ['subscription.created', 'subscription.activated'], true);
    }

    /**
     * @deprecated 1.1.0 Recurrente envía "subscription.cancel", que este método no
     *             detecta. Usa isSubscriptionEnded().
     */
    public function isSubscriptionCancelled(): bool
    {
        return in_array($this->type, ['subscription.cancelled', 'subscription.canceled'], true);
    }

    // ── Datos del evento ────────────────────────────────────────────────────

    /**
     * Metadata del evento. En cobros con formato unificado la metadata del
     * checkout viene en checkout.metadata.
     *
     * @return array<string,mixed>
     */
    public function metadata(): array
    {
        $metadata = $this->data['metadata']
            ?? $this->raw['metadata']
            ?? $this->raw['checkout']['metadata']
            ?? [];

        return is_array($metadata) ? $metadata : [];
    }

    public function metaValue(string $key, mixed $default = null): mixed
    {
        return $this->metadata()[$key] ?? $default;
    }

    /**
     * ID del pago ("pa_...") igual en ambos formatos: el unificado lo trae en
     * legacy_id / payment.id y el legacy como id. Úsalo para deduplicar
     * cuando tu cuenta recibe intent.* y payment_intent.* del mismo cobro.
     */
    public function paymentId(): ?string
    {
        $value = $this->raw['legacy_id']
            ?? $this->raw['payment']['id']
            ?? (str_starts_with((string) $this->id, 'pa_') ? $this->id : null);

        return $value !== null ? (string) $value : null;
    }

    /** Clave de idempotencia por entrega: svix-id si existe; si no, tipo + id. */
    public function idempotencyKey(): string
    {
        return $this->messageId ?? $this->type.':'.($this->id ?? md5(json_encode($this->raw) ?: ''));
    }

    public function checkoutId(): ?string
    {
        return $this->stringOrNull($this->raw['checkout']['id'] ?? $this->raw['checkout_id'] ?? null);
    }

    public function customerId(): ?string
    {
        return $this->stringOrNull($this->raw['customer_id'] ?? $this->raw['customer']['id'] ?? null);
    }

    public function subscriptionId(): ?string
    {
        if (str_starts_with($this->type, 'subscription.')) {
            return $this->id;
        }

        return $this->stringOrNull($this->raw['subscription']['id'] ?? null);
    }

    public function amountInCents(): ?int
    {
        return isset($this->raw['amount_in_cents']) ? (int) $this->raw['amount_in_cents'] : null;
    }

    public function currency(): ?string
    {
        return $this->stringOrNull($this->raw['currency'] ?? null);
    }

    public function status(): ?string
    {
        return $this->stringOrNull($this->raw['status'] ?? null);
    }

    protected function stringOrNull(mixed $value): ?string
    {
        return $value !== null && ! is_array($value) ? (string) $value : null;
    }
}
