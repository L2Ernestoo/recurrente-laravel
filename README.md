# Recurrente para Laravel

[![tests](https://github.com/L2Ernestoo/recurrente-laravel/actions/workflows/tests.yml/badge.svg)](https://github.com/L2Ernestoo/recurrente-laravel/actions/workflows/tests.yml)

SDK no oficial de [Recurrente](https://recurrente.com) (pasarela de pagos de Guatemala) para Laravel. Permite crear checkouts, productos con precios recurrentes (suscripciones), gestionar suscripciones, clientes, reembolsos y cupones, y **verificar la firma de los webhooks**.

> Paquete extraído del proyecto ArrendaLes. Framework: Laravel 11/12/13, PHP 8.2+.

## Instalación

```bash
composer require arrendales/recurrente-laravel:^1.1
php artisan vendor:publish --tag=recurrente-config
```

Configura tus llaves en `.env` (o inyéctalas en runtime, ver abajo):

```env
RECURRENTE_PUBLIC_KEY=pk_...
RECURRENTE_SECRET_KEY=sk_...
RECURRENTE_WEBHOOK_SECRET=whsec_...
RECURRENTE_MODE=test
# Opcional: cuenta conectada por defecto (header X-ACCOUNT-ID)
RECURRENTE_ACCOUNT_ID=
```

La llave secreta decide el ambiente: `sk_test_...` opera en sandbox y `sk_live_...` en producción. Para comprobarlo:

```php
Recurrente::test(); // ['environment' => 'sandbox', ...]
```

## Checkouts

```php
use Recurrente\Laravel\Facades\Recurrente;
use Recurrente\Laravel\DataObjects\CheckoutData;

$checkout = Recurrente::checkouts()->create(
    CheckoutData::make()
        ->withPrice('price_abc')              // o ->withProduct('prod_abc') / ->withInlineItem('Plan Pro', 29900)
        ->customerId($customer['id'])         // opcional: prepopula los datos del comprador
        ->successUrl(route('billing.success'))
        ->cancelUrl(route('billing.cancel'))
        ->metadata(['organization_id' => $org->id])
);

return redirect($checkout['checkout_url']);
```

Otras opciones del builder: `discountCode()`, `expiresAt()`, `setupMode()` (tokenizar tarjeta sin cobrar), `bankTransferMemo()`, `claveEnabled(false)` y `with([...])` para campos que el builder aún no modela.

### Métodos de pago (solo items inline)

Recurrente solo permite elegir métodos de pago por item en **items inline**. Con `withPrice()` / `withProduct()` se usa la configuración del producto.

```php
CheckoutData::make()
    ->withInlineItem('Consulta', 15000)
    ->paymentMethods(card: true, bankTransfer: false);

// o con control total:
    ->paymentMethodTypes(['card', 'bank_transfer', 'balance']);
```

## Llaves en runtime (multi-cuenta / desde base de datos)

```php
use Recurrente\Laravel\RecurrenteClient;

$client = new RecurrenteClient(secretKey: $secretFromDb, publicKey: $publicFromDb);
$client->subscriptions()->cancel($subscriptionId);

// Operar sobre una cuenta conectada (X-ACCOUNT-ID):
Recurrente::forAccount('ac_123')->checkouts()->create($data);
```

## Productos recurrentes (suscripciones)

```php
$product = Recurrente::products()->createRecurring(
    name: 'Plan Pro Mensual',
    amountInCents: 29900,
    currency: 'GTQ',
    billingInterval: 'month',
    freeTrialDays: 14,
);

Recurrente::products()->list();
Recurrente::products()->update('prod_123', ['name' => 'Plan Pro']);
```

## Suscripciones

```php
Recurrente::subscriptions()->get('su_123');
Recurrente::subscriptions()->list();
Recurrente::subscriptions()->update('su_123', [...]);
Recurrente::subscriptions()->previewProration('su_123', [...]);
Recurrente::subscriptions()->cancel('su_123');
```

## Clientes

```php
$customer = Recurrente::customers()->createCustomer([
    'email'     => 'ana@example.com',
    'full_name' => 'Ana López',
]);

Recurrente::customers()->get($customer['id']);
Recurrente::customers()->list();
Recurrente::customers()->update($customer['id'], ['phone' => '+50255555555']);
```

> `customers()->create()` crea un **usuario** (`POST /users`) por compatibilidad con la 1.0 y está deprecado. Usa `createCustomer()` o `users()->create()`.

## Reembolsos, cobros y cupones

```php
// Siempre con una llave de idempotencia para que un reintento no reembolse dos veces
Recurrente::refunds()->create('in_123', idempotencyKey: "refund-{$order->id}");
Recurrente::refunds()->create('in_123', amountInCents: 5000);   // parcial (según procesador)
Recurrente::refunds()->void('rf_123');

Recurrente::intents()->list(['from_time' => '2026-01-01T00:00:00Z']);
Recurrente::intents()->get('in_123');

Recurrente::coupons()->create([...]);
```

## Webhooks

### Registrar el endpoint

```php
$endpoint = Recurrente::webhookEndpoints()->create(url: route('webhooks.recurrente'));
// Guarda $endpoint['signingSecret'] como RECURRENTE_WEBHOOK_SECRET: solo se devuelve aquí.
```

### Recibir eventos

La forma más simple es el middleware `recurrente.webhook`, que responde 400 si la firma no es válida:

```php
// routes/api.php (sin CSRF)
use Recurrente\Laravel\Webhooks\EventType;
use Recurrente\Laravel\Webhooks\WebhookEvent;

Route::post('/webhooks/recurrente', function (Request $request) {
    $event = WebhookEvent::fromRequest($request);

    // Los webhooks pueden llegar más de una vez: deduplica.
    if (! Cache::add('recurrente:'.$event->idempotencyKey(), true, now()->addDay())) {
        return response()->noContent();
    }

    if ($event->isIntentSucceeded()) {
        ActivatePlan::dispatch($event->checkoutId(), $event->metaValue('organization_id'));
    } elseif ($event->isSubscriptionEnded()) {
        CancelPlan::dispatch($event->subscriptionId());
    } elseif ($event->is(EventType::REFUND_CREATE)) {
        // ...
    }

    return response()->noContent();
})->middleware('recurrente.webhook')->name('webhooks.recurrente');
```

O verificando manualmente:

```php
public function handle(Request $request, WebhookVerifier $verifier)
{
    abort_unless($verifier->verifyRequest($request), 400); // usa RECURRENTE_WEBHOOK_SECRET

    $event = WebhookEvent::fromRequest($request);
    // ...
}
```

### Formatos de evento: `intent.*` vs `payment_intent.*`

Recurrente envía los cobros en dos formatos:

| Cuenta | Recibe |
|---|---|
| Creada antes del formato unificado | **ambos**: `payment_intent.succeeded` **y** `intent.succeeded` por cada cobro |
| Creada después | solo `intent.succeeded` |

- `isIntentSucceeded()` / `isIntentFailed()` detectan el formato unificado.
- `isPaymentCompleted()` / `isPaymentFailed()` detectan el legacy (sin cambios desde 1.0).
- **Escucha solo uno de los dos.** Si escuchas ambos, procesarías cada cobro dos veces. `paymentId()` devuelve el mismo `pa_...` en los dos formatos por si necesitas deduplicar entre ellos.

Todos los tipos documentados están en `EventType`. Datos útiles del evento: `checkoutId()`, `customerId()`, `subscriptionId()`, `paymentId()`, `amountInCents()`, `currency()`, `status()`, `metadata()` y `metaValue()`.

Esquemas de firma soportados: `svix` (por defecto, el que usa Recurrente) y `hmac`. Configurable en `config/recurrente.php`.

## Tests

```bash
composer install
composer test
```

## Licencia

MIT.
