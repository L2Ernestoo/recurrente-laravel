<?php

namespace Recurrente\Laravel\Resources;

use Recurrente\Laravel\RecurrenteClient;

class Subscriptions
{
    public function __construct(protected RecurrenteClient $client) {}

    /** @return array<string,mixed> */
    public function get(string $id): array
    {
        return $this->client->get("/subscriptions/{$id}");
    }

    /**
     * @param  array<string,mixed>  $filters  page, items
     * @return array<string,mixed>
     */
    public function list(array $filters = []): array
    {
        return $this->client->get('/subscriptions', $filters);
    }

    /**
     * Actualiza una suscripción (PUT): cambio de plan, pausa, etc.
     *
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>
     */
    public function update(string $id, array $data): array
    {
        return $this->client->put("/subscriptions/{$id}", $data);
    }

    /**
     * Calcula el prorrateo de un cambio sin aplicarlo.
     *
     * @param  array<string,mixed>  $data  Mismo cuerpo que update()
     * @return array<string,mixed>
     */
    public function previewProration(string $id, array $data): array
    {
        return $this->client->post("/subscriptions/{$id}/proration_preview", $data);
    }

    /**
     * Cancela una suscripción de forma permanente. Recurrente expone la
     * cancelación vía DELETE.
     *
     * @return array<string,mixed>
     */
    public function cancel(string $id): array
    {
        return $this->client->delete("/subscriptions/{$id}");
    }

    /** @return array<string,mixed> */
    public function invoiceItems(string $subscriptionId): array
    {
        return $this->client->get("/subscriptions/{$subscriptionId}/invoice_items");
    }

    /** @return array<string,mixed> */
    public function cancelInvoiceItem(string $subscriptionId, string $invoiceItemId, ?string $idempotencyKey = null): array
    {
        return $this->client->delete(
            "/subscriptions/{$subscriptionId}/invoice_items/{$invoiceItemId}",
            $idempotencyKey ? ['Idempotency-Key' => $idempotencyKey] : [],
        );
    }
}
