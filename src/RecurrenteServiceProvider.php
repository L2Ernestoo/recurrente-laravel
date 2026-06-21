<?php

namespace Recurrente\Laravel;

use Illuminate\Support\ServiceProvider;
use Recurrente\Laravel\Webhooks\WebhookVerifier;

class RecurrenteServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/recurrente.php', 'recurrente');

        // Cliente por defecto basado en config (.env). La aplicación puede
        // re-bindear este singleton con llaves desde la base de datos.
        $this->app->singleton(RecurrenteClient::class, function ($app) {
            $config = $app['config']['recurrente'];

            return new RecurrenteClient(
                secretKey: $config['secret_key'] ?? null,
                publicKey: $config['public_key'] ?? null,
                baseUrl:   $config['base_url'] ?? 'https://app.recurrente.com/api',
                timeout:   (int) ($config['timeout'] ?? 30),
            );
        });

        $this->app->singleton(WebhookVerifier::class, function ($app) {
            $config = $app['config']['recurrente']['webhook'] ?? [];

            return new WebhookVerifier(
                scheme:          $config['scheme'] ?? 'svix',
                signatureHeader: $config['signature_header'] ?? 'X-Recurrente-Signature',
                tolerance:       (int) ($config['tolerance'] ?? 300),
            );
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/recurrente.php' => $this->app->configPath('recurrente.php'),
            ], 'recurrente-config');
        }
    }
}
