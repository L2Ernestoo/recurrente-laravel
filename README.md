# Recurrente para Laravel

SDK no oficial de [Recurrente](https://recurrente.com) (pasarela de pagos de Guatemala) para Laravel. Permite crear checkouts, productos con precios recurrentes (suscripciones), cancelar suscripciones y **verificar la firma de los webhooks**.

> Paquete extraído del proyecto ArrendaLes. Framework: Laravel 11/12, PHP 8.2+.

## Instalación

```bash
composer require arrendales/recurrente-laravel
php artisan vendor:publish --tag=recurrente-config
```

Configura tus llaves en `.env` (o inyéctalas en runtime, ver abajo):

```env
RECURRENTE_PUBLIC_KEY=pk_...
RECURRENTE_SECRET_KEY=sk_...
RECURRENTE_WEBHOOK_SECRET=whsec_...
RECURRENTE_MODE=test
```

## Uso básico

```php
use Recurrente\Laravel\Facades\Recurrente;
use Recurrente\Laravel\DataObjects\CheckoutData;

$checkout = Recurrente::checkouts()->create(
    CheckoutData::make()
        ->withPrice('price_abc')              // o ->withInlineItem('Plan Pro', 29900)
        ->successUrl(route('billing.success'))
        ->cancelUrl(route('billing.cancel'))
        ->metadata(['organization_id' => $org->id])
        ->paymentMethods(card: true, bankTransfer: true)
);

return redirect($checkout['checkout_url']);
```

### Llaves en runtime (multi-cuenta / desde base de datos)

```php
use Recurrente\Laravel\RecurrenteClient;

$client = new RecurrenteClient(secretKey: $secretFromDb, publicKey: $publicFromDb);
$client->subscriptions()->cancel($subscriptionId);
```

### Productos recurrentes (suscripciones)

```php
$product = Recurrente::products()->createRecurring(
    name: 'Plan Pro Mensual',
    amountInCents: 29900,
    currency: 'GTQ',
    billingInterval: 'month',
    freeTrialDays: 14,
);
```

## Webhooks

```php
use Recurrente\Laravel\Webhooks\WebhookVerifier;
use Recurrente\Laravel\Webhooks\WebhookEvent;

public function handle(Request $request, WebhookVerifier $verifier)
{
    $ok = $verifier->verify(
        payload: $request->getContent(),
        headers: $request->headers->all(),
        secret:  config('recurrente.webhook_secret'),
    );

    abort_unless($ok, 400);

    $event = WebhookEvent::fromArray($request->json()->all());

    if ($event->isPaymentCompleted()) {
        // activar suscripción usando $event->metaValue('organization_id')
    }
}
```

Esquemas de firma soportados: `svix` (por defecto) y `hmac`. Configurable en `config/recurrente.php`.

## Tests

```bash
composer install
vendor/bin/phpunit
```

## Licencia

MIT.
