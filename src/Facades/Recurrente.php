<?php

namespace Recurrente\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use Recurrente\Laravel\RecurrenteClient;

/**
 * @method static \Recurrente\Laravel\Resources\Checkouts checkouts()
 * @method static \Recurrente\Laravel\Resources\Products products()
 * @method static \Recurrente\Laravel\Resources\Subscriptions subscriptions()
 * @method static \Recurrente\Laravel\Resources\Customers customers()
 * @method static array get(string $path, array $query = [])
 * @method static array post(string $path, array $payload = [])
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
