<?php

namespace Recurrente\Laravel\Resources;

use Recurrente\Laravel\RecurrenteClient;

class Customers
{
    public function __construct(protected RecurrenteClient $client) {}

    /**
     * @param  array<string,mixed>  $data  { email, full_name, ... }
     * @return array<string,mixed>
     */
    public function create(array $data): array
    {
        return $this->client->post('/users', $data);
    }

    /** @return array<string,mixed> */
    public function get(string $id): array
    {
        return $this->client->get("/users/{$id}");
    }
}
