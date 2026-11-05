<?php

namespace Recurrente\Laravel\Resources;

use Recurrente\Laravel\RecurrenteClient;

class Products
{
    public function __construct(protected RecurrenteClient $client) {}

    /**
     * Crea un producto. Recibe el payload ya envuelto en la clave "product",
     * tal como lo espera la API de Recurrente:
     *   { "product": { "name": ..., "prices_attributes": [ ... ] } }
     *
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>
     */
    public function create(array $data): array
    {
        return $this->client->post('/products', $this->wrap($data));
    }

    /**
     * Helper: producto con un precio recurrente (suscripción).
     *
     * @return array<string,mixed>
     */
    public function createRecurring(
        string $name,
        int $amountInCents,
        string $currency = 'GTQ',
        string $billingInterval = 'month',
        int $billingIntervalCount = 1,
        ?int $freeTrialDays = null,
        ?string $description = null,
    ): array {
        $price = array_filter([
            'amount_in_cents'        => $amountInCents,
            'currency'               => $currency,
            'charge_type'            => 'recurring',
            'billing_interval'       => $billingInterval,
            'billing_interval_count' => $billingIntervalCount,
            'free_trial_interval'        => $freeTrialDays ? 'day' : null,
            'free_trial_interval_count'  => $freeTrialDays,
        ], static fn ($v) => $v !== null);

        return $this->create([
            'product' => array_filter([
                'name'              => $name,
                'description'       => $description,
                'prices_attributes' => [$price],
            ], static fn ($v) => $v !== null),
        ]);
    }

    /** @return array<string,mixed> */
    public function get(string $id): array
    {
        return $this->client->get("/products/{$id}");
    }

    /**
     * @param  array<string,mixed>  $filters  page, items
     * @return array<string,mixed>
     */
    public function list(array $filters = []): array
    {
        return $this->client->get('/products', $filters);
    }

    /**
     * Actualiza un producto (PUT). Igual que create(), envuelve en "product"
     * si el caller no lo hizo.
     *
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>
     */
    public function update(string $id, array $data): array
    {
        return $this->client->put("/products/{$id}", $this->wrap($data));
    }

    /** @return array<string,mixed> */
    public function delete(string $id): array
    {
        return $this->client->delete("/products/{$id}");
    }

    /**
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>
     */
    protected function wrap(array $data): array
    {
        // Si el caller no envolvió en "product", lo envolvemos por conveniencia.
        return isset($data['product']) ? $data : ['product' => $data];
    }
}
