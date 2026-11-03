<?php

namespace Recurrente\Laravel\Resources;

use Recurrente\Laravel\RecurrenteClient;

/**
 * Cobros (formato unificado "in_..."): tarjeta, transferencia, balance, etc.
 */
class Intents
{
    public function __construct(protected RecurrenteClient $client) {}

    /** @return array<string,mixed> */
    public function get(string $id): array
    {
        return $this->client->get("/intents/{$id}");
    }

    /**
     * @param  array<string,mixed>  $filters  from_time, until_time
     * @return array<string,mixed>
     */
    public function list(array $filters = []): array
    {
        return $this->client->get('/intents', $filters);
    }
}
