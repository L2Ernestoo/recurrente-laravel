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
        // Si el caller no envolvió en "product", lo envolvemos por conveniencia.
        if (! isset($data['product'])) {
            $data = ['product' => $data];
        }

        return $this->client->post('/products', $data);
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

    /** @return array<string,mixed> */
    public function delete(string $id): array
    {
        return $this->client->delete("/products/{$id}");
    }
}
