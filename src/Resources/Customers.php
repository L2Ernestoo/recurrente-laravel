<?php

namespace Recurrente\Laravel\Resources;

use Recurrente\Laravel\RecurrenteClient;

class Customers
{
    public function __construct(protected RecurrenteClient $client) {}

    /**
     * Crea un *usuario* (POST /users) — se mantiene así por compatibilidad.
     *
     * @deprecated 1.1.0 Usa createCustomer() para crear un cliente (POST /customers,
     *             recomendado por Recurrente) o users()->create() para conservar
     *             este comportamiento. En 2.0 create() creará un cliente.
     *
     * @param  array<string,mixed>  $data  { email, full_name }
     * @return array<string,mixed>
     */
    public function create(array $data): array
    {
        return $this->client->post('/users', $data);
    }

    /**
     * Crea un cliente (y su usuario).
     *
     * @param  array<string,mixed>  $data  { email, full_name, name, phone, nit, address, metadata }
     * @return array<string,mixed>
     */
    public function createCustomer(array $data): array
    {
        return $this->client->post('/customers', $data);
    }

    /**
     * Obtiene un cliente. Los IDs de usuario ("us_...") devueltos por create()
     * se siguen consultando en /users por compatibilidad.
     *
     * @return array<string,mixed>
     */
    public function get(string $id): array
    {
        return str_starts_with($id, 'us_')
            ? $this->client->get("/users/{$id}")
            : $this->client->get("/customers/{$id}");
    }

    /**
     * @param  array<string,mixed>  $filters  page, items
     * @return array<string,mixed>
     */
    public function list(array $filters = []): array
    {
        return $this->client->get('/customers', $filters);
    }

    /**
     * @param  array<string,mixed>  $data  { name, phone, nit, address, metadata }
     * @return array<string,mixed>
     */
    public function update(string $id, array $data): array
    {
        return $this->client->put("/customers/{$id}", $data);
    }
}
