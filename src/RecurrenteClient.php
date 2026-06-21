<?php

namespace Recurrente\Laravel;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Recurrente\Laravel\Exceptions\RecurrenteException;
use Recurrente\Laravel\Resources\Checkouts;
use Recurrente\Laravel\Resources\Customers;
use Recurrente\Laravel\Resources\Products;
use Recurrente\Laravel\Resources\Subscriptions;

class RecurrenteClient
{
    public function __construct(
        protected ?string $secretKey,
        protected ?string $publicKey = null,
        protected string $baseUrl = 'https://app.recurrente.com/api',
        protected int $timeout = 30,
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    // ── Recursos ────────────────────────────────────────────────────────────

    public function checkouts(): Checkouts
    {
        return new Checkouts($this);
    }

    public function products(): Products
    {
        return new Products($this);
    }

    public function subscriptions(): Subscriptions
    {
        return new Subscriptions($this);
    }

    public function customers(): Customers
    {
        return new Customers($this);
    }

    // ── Verbos HTTP ─────────────────────────────────────────────────────────

    /** @param array<string,mixed> $query */
    public function get(string $path, array $query = []): array
    {
        return $this->send('get', $path, ['query' => $query]);
    }

    /** @param array<string,mixed> $payload */
    public function post(string $path, array $payload = []): array
    {
        return $this->send('post', $path, ['json' => $payload]);
    }

    /** @param array<string,mixed> $payload */
    public function patch(string $path, array $payload = []): array
    {
        return $this->send('patch', $path, ['json' => $payload]);
    }

    public function delete(string $path): array
    {
        return $this->send('delete', $path, []);
    }

    // ── Núcleo ──────────────────────────────────────────────────────────────

    /**
     * @param  array{query?: array<string,mixed>, json?: array<string,mixed>}  $options
     * @return array<string,mixed>
     */
    protected function send(string $method, string $path, array $options): array
    {
        if (empty($this->secretKey)) {
            throw RecurrenteException::missingCredentials();
        }

        $url = $this->baseUrl.'/'.ltrim($path, '/');

        $request = $this->request();

        $response = match ($method) {
            'get'    => $request->get($url, $options['query'] ?? []),
            'post'   => $request->post($url, $options['json'] ?? []),
            'patch'  => $request->patch($url, $options['json'] ?? []),
            'delete' => $request->delete($url),
            default  => throw new RecurrenteException("Método HTTP no soportado: {$method}"),
        };

        $body = $response->json() ?? [];

        if ($response->failed()) {
            throw RecurrenteException::fromResponse($response->status(), is_array($body) ? $body : []);
        }

        return is_array($body) ? $body : [];
    }

    protected function request(): PendingRequest
    {
        return Http::withHeaders([
            'X-SECRET-KEY' => $this->secretKey,
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json',
        ])->timeout($this->timeout);
    }

    public function publicKey(): ?string
    {
        return $this->publicKey;
    }

    public function baseUrl(): string
    {
        return $this->baseUrl;
    }
}
