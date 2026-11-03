<?php

namespace Recurrente\Laravel;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Recurrente\Laravel\Exceptions\RecurrenteException;
use Recurrente\Laravel\Resources\Checkouts;
use Recurrente\Laravel\Resources\Coupons;
use Recurrente\Laravel\Resources\Customers;
use Recurrente\Laravel\Resources\Intents;
use Recurrente\Laravel\Resources\Products;
use Recurrente\Laravel\Resources\Refunds;
use Recurrente\Laravel\Resources\Subscriptions;
use Recurrente\Laravel\Resources\Users;
use Recurrente\Laravel\Resources\WebhookEndpoints;

class RecurrenteClient
{
    public function __construct(
        protected ?string $secretKey,
        protected ?string $publicKey = null,
        protected string $baseUrl = 'https://app.recurrente.com/api',
        protected int $timeout = 30,
        protected ?string $accountId = null,
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    /**
     * Devuelve una copia del cliente que opera sobre una cuenta conectada
     * (header X-ACCOUNT-ID). El cliente original no se modifica.
     */
    public function forAccount(?string $accountId): static
    {
        $clone = clone $this;
        $clone->accountId = $accountId;

        return $clone;
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

    public function users(): Users
    {
        return new Users($this);
    }

    public function refunds(): Refunds
    {
        return new Refunds($this);
    }

    public function intents(): Intents
    {
        return new Intents($this);
    }

    public function coupons(): Coupons
    {
        return new Coupons($this);
    }

    public function webhookEndpoints(): WebhookEndpoints
    {
        return new WebhookEndpoints($this);
    }

    /**
     * Verifica las credenciales. Devuelve, entre otros, el ambiente
     * (sandbox | live) que corresponde a la llave secreta.
     *
     * @return array<string,mixed>
     */
    public function test(): array
    {
        return $this->get('/test');
    }

    // ── Verbos HTTP ─────────────────────────────────────────────────────────

    /**
     * @param  array<string,mixed>  $query
     * @param  array<string,string>  $headers
     */
    public function get(string $path, array $query = [], array $headers = []): array
    {
        return $this->send('get', $path, ['query' => $query, 'headers' => $headers]);
    }

    /**
     * @param  array<string,mixed>  $payload
     * @param  array<string,string>  $headers
     */
    public function post(string $path, array $payload = [], array $headers = []): array
    {
        return $this->send('post', $path, ['json' => $payload, 'headers' => $headers]);
    }

    /**
     * @param  array<string,mixed>  $payload
     * @param  array<string,string>  $headers
     */
    public function put(string $path, array $payload = [], array $headers = []): array
    {
        return $this->send('put', $path, ['json' => $payload, 'headers' => $headers]);
    }

    /**
     * @param  array<string,mixed>  $payload
     * @param  array<string,string>  $headers
     */
    public function patch(string $path, array $payload = [], array $headers = []): array
    {
        return $this->send('patch', $path, ['json' => $payload, 'headers' => $headers]);
    }

    /** @param  array<string,string>  $headers */
    public function delete(string $path, array $headers = []): array
    {
        return $this->send('delete', $path, ['headers' => $headers]);
    }

    // ── Núcleo ──────────────────────────────────────────────────────────────

    /**
     * @param  array{query?: array<string,mixed>, json?: array<string,mixed>, headers?: array<string,string>}  $options
     * @return array<string,mixed>
     */
    protected function send(string $method, string $path, array $options): array
    {
        if (empty($this->secretKey)) {
            throw RecurrenteException::missingCredentials();
        }

        $url = $this->baseUrl.'/'.ltrim($path, '/');

        $request = $this->request();

        if (! empty($options['headers'])) {
            $request->withHeaders($options['headers']);
        }

        $response = match ($method) {
            'get'    => $request->get($url, $options['query'] ?? []),
            'post'   => $request->post($url, $options['json'] ?? []),
            'put'    => $request->put($url, $options['json'] ?? []),
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
        return Http::withHeaders(array_filter([
            'X-SECRET-KEY' => $this->secretKey,
            'X-ACCOUNT-ID' => $this->accountId,
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json',
        ], static fn ($v) => $v !== null && $v !== ''))->timeout($this->timeout);
    }

    public function publicKey(): ?string
    {
        return $this->publicKey;
    }

    public function accountId(): ?string
    {
        return $this->accountId;
    }

    public function baseUrl(): string
    {
        return $this->baseUrl;
    }
}
