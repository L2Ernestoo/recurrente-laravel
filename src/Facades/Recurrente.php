<?php

namespace Recurrente\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use Recurrente\Laravel\RecurrenteClient;

/**
 * @method static \Recurrente\Laravel\Resources\Checkouts checkouts()
 * @method static \Recurrente\Laravel\Resources\Products products()
 * @method static \Recurrente\Laravel\Resources\Subscriptions subscriptions()
 * @method static \Recurrente\Laravel\Resources\Customers customers()
 * @method static \Recurrente\Laravel\Resources\Users users()
 * @method static \Recurrente\Laravel\Resources\Refunds refunds()
 * @method static \Recurrente\Laravel\Resources\Intents intents()
 * @method static \Recurrente\Laravel\Resources\Coupons coupons()
 * @method static \Recurrente\Laravel\Resources\WebhookEndpoints webhookEndpoints()
 * @method static \Recurrente\Laravel\RecurrenteClient forAccount(?string $accountId)
 * @method static array test()
 * @method static array get(string $path, array $query = [], array $headers = [])
 * @method static array post(string $path, array $payload = [], array $headers = [])
 * @method static array put(string $path, array $payload = [], array $headers = [])
 * @method static array patch(string $path, array $payload = [], array $headers = [])
 * @method static array delete(string $path, array $headers = [])
 * @method static string|null publicKey()
 * @method static string|null accountId()
 * @method static string baseUrl()
 *
 * @see RecurrenteClient
 */
class Recurrente extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return RecurrenteClient::class;
    }
}
