# Guía de actualización

## De `dev-master` a una versión estable

Hasta la 1.0, el paquete solo existía en Packagist como `dev-master`. Con `dev-master`, cualquier `composer update` instala lo último que se suba a la rama, sin control de versiones. Cambia la restricción en tu `composer.json`:

```bash
composer require arrendales/recurrente-laravel:^1.0
```

`^1.0` instala cualquier 1.x (compatible) y nunca una 2.0.

## De 1.0 a 1.1

No requiere cambios. Recomendado:

1. **Webhooks de cobros.** Revisa qué formato recibe tu cuenta (ver README, "Formatos de evento"). Si es una cuenta nueva, `isPaymentCompleted()` no detecta `intent.succeeded`: usa `isIntentSucceeded()`.
2. **Suscripciones.** Si usabas `isSubscriptionCreated()` / `isSubscriptionCancelled()`, nunca se ejecutaban con eventos reales. Cámbialos por `isSubscriptionStarted()` / `isSubscriptionEnded()`, y revisa que ese código no duplique lo que ya haces al recibir el pago.
3. **Deduplicación.** Usa `$event->idempotencyKey()` (basado en `svix-id`) para no procesar dos veces el mismo webhook.
4. **Clientes.** Reemplaza `customers()->create()` por `customers()->createCustomer()`. Ojo: devuelve un cliente (`cus_...`) en vez de un usuario (`us_...`). Para checkouts usa `->customerId()` en lugar de `->userId()`.

## Planeado para 2.0 (incompatible)

- `customers()->create()` creará un cliente (`POST /customers`).
- Se eliminan `isSubscriptionCreated()` e `isSubscriptionCancelled()`.
- Los errores de red (`Illuminate\Http\Client\ConnectionException`) se envolverán en `RecurrenteException`.
- `CheckoutData` dejará de enviar los campos legacy de métodos de pago (`custom_payment_method_settings`, `card_payments_enabled`, `bank_transfer_payments_enabled`).
