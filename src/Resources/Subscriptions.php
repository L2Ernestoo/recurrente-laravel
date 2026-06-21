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
     * @param  array<string,mixed>  $filters
     * @return array<string,mixed>
     */
    public function list(array $filters = []): array
    {
        return $this->client->get('/subscriptions', $filters);
    }

    /**
     * Cancela una suscripción. Recurrente expone la cancelación vía DELETE.
     *
     * @return array<string,mixed>
     */
    public function cancel(string $id): array
    {
        return $this->client->delete("/subscriptions/{$id}");
    }
}
