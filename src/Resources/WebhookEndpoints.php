<?php

namespace Recurrente\Laravel\Resources;

use Recurrente\Laravel\RecurrenteClient;

class WebhookEndpoints
{
    public function __construct(protected RecurrenteClient $client) {}

    /**
     * Registra una URL de webhooks. La respuesta incluye "signingSecret"
     * (whsec_...) SOLO en esta llamada: guárdalo como RECURRENTE_WEBHOOK_SECRET.
     *
     * @param  array<string,mixed>  $metadata
     * @return array<string,mixed>
     */
    public function create(string $url, ?string $description = null, array $metadata = []): array
    {
        return $this->client->post('/webhook_endpoints', array_filter([
            'url'         => $url,
            'description' => $description,
            'metadata'    => $metadata ?: null,
        ], static fn ($v) => $v !== null));
    }

    /** @return array<string,mixed> */
    public function list(): array
    {
        return $this->client->get('/webhook_endpoints');
    }

    /** @return array<string,mixed> */
    public function delete(string $id): array
    {
        return $this->client->delete("/webhook_endpoints/{$id}");
    }
}
