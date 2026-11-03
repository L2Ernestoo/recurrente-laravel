<?php

namespace Recurrente\Laravel\Resources;

use Recurrente\Laravel\RecurrenteClient;

class Coupons
{
    public function __construct(protected RecurrenteClient $client) {}

    /**
     * @param  array<string,mixed>  $data  Se envuelve en "coupon" si no lo está.
     * @return array<string,mixed>
     */
    public function create(array $data): array
    {
        return $this->client->post('/coupons', $this->wrap($data));
    }

    /** @return array<string,mixed> */
    public function get(string $id): array
    {
        return $this->client->get("/coupons/{$id}");
    }

    /**
     * @param  array<string,mixed>  $filters
     * @return array<string,mixed>
     */
    public function list(array $filters = []): array
    {
        return $this->client->get('/coupons', $filters);
    }

    /**
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>
     */
    public function update(string $id, array $data): array
    {
        return $this->client->put("/coupons/{$id}", $this->wrap($data));
    }

    /** @return array<string,mixed> */
    public function delete(string $id): array
    {
        return $this->client->delete("/coupons/{$id}");
    }

    /**
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>
     */
    protected function wrap(array $data): array
    {
        return isset($data['coupon']) ? $data : ['coupon' => $data];
    }
}
