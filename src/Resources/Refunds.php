<?php

namespace Recurrente\Laravel\Resources;

use Recurrente\Laravel\RecurrenteClient;

class Refunds
{
    public function __construct(protected RecurrenteClient $client) {}

    /**
     * Reembolsa un cobro exitoso. Sin $amountInCents se reembolsa el saldo
     * restante completo (los parciales solo aplican a algunos procesadores).
     *
     * Se recomienda siempre un $idempotencyKey único por reembolso para que un
     * reintento no genere un segundo reembolso.
     *
     * @param  string  $intentId  ID "in_..." (o legacy "pa_...")
     * @return array<string,mixed>
     */
    public function create(
        string $intentId,
        ?int $amountInCents = null,
        bool $refundApplicationFee = false,
        ?string $idempotencyKey = null,
    ): array {
        // "pa_..." es el ID legacy; la API lo acepta bajo payment_intent_id.
        $idField = str_starts_with($intentId, 'pa_') ? 'payment_intent_id' : 'intent_id';

        $payload = array_filter([
            $idField                 => $intentId,
            'amount_in_cents'        => $amountInCents,
            'refund_application_fee' => $refundApplicationFee ?: null,
        ], static fn ($v) => $v !== null);

        return $this->client->post('/refunds', $payload, $idempotencyKey ? ['Idempotency-Key' => $idempotencyKey] : []);
    }

    /** @return array<string,mixed> */
    public function get(string $id): array
    {
        return $this->client->get("/refunds/{$id}");
    }

    /** @return array<string,mixed> */
    public function void(string $id): array
    {
        return $this->client->post("/refunds/{$id}/void");
    }
}
