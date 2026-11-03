<?php

namespace Recurrente\Laravel\Resources;

use Recurrente\Laravel\RecurrenteClient;

class Users
{
    public function __construct(protected RecurrenteClient $client) {}

    /**
     * Crea un usuario asociable a checkouts. Si ya existe uno con ese email,
     * Recurrente devuelve el existente.
     *
     * @param  array<string,mixed>  $data  { email, full_name }
     * @return array<string,mixed>
     */
    public function create(array $data): array
    {
        return $this->client->post('/users', $data);
    }
}
