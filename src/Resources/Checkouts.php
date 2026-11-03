<?php

namespace Recurrente\Laravel\Resources;

use Recurrente\Laravel\DataObjects\CheckoutData;
use Recurrente\Laravel\RecurrenteClient;

class Checkouts
{
    public function __construct(protected RecurrenteClient $client) {}

    /**
     * Crea un checkout. Acepta un array crudo o un CheckoutData.
     *
     * @param  array<string,mixed>|CheckoutData  $data
     * @return array<string,mixed>  { id, checkout_url, ... }
     */
    public function create(array|CheckoutData $data): array
    {
        $payload = $data instanceof CheckoutData ? $data->toArray() : $data;

        return $this->client->post('/checkouts', $payload);
    }

    /** @return array<string,mixed> */
    public function get(string $id): array
    {
        return $this->client->get("/checkouts/{$id}");
    }

    /**
     * @param  array<string,mixed>  $filters  page, items, user_id, from_time, until_time
     * @return array<string,mixed>
     */
    public function list(array $filters = []): array
    {
        return $this->client->get('/checkouts', $filters);
    }

    /**
     * Actualiza un checkout abierto (items, success_url, cancel_url, metadata,
     * expires_at, discount_code).
     *
     * @param  array<string,mixed>|CheckoutData  $data
     * @return array<string,mixed>
     */
    public function update(string $id, array|CheckoutData $data, ?string $idempotencyKey = null): array
    {
        $payload = $data instanceof CheckoutData ? $data->toArray() : $data;

        return $this->client->patch("/checkouts/{$id}", $payload, $idempotencyKey ? ['Idempotency-Key' => $idempotencyKey] : []);
    }
}
