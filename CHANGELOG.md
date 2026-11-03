# Changelog

Todas las versiones siguen [SemVer](https://semver.org/lang/es/). Los cambios de una versión menor (1.x) son compatibles hacia atrás.

## [1.1.0] - 2026-11-03

Alinea el paquete con la [API actual de Recurrente](https://docs.recurrente.com). **Sin cambios incompatibles**: el código escrito para 1.0 sigue funcionando igual.

### Agregado
- Soporte para Laravel 13.
- Recursos nuevos: `refunds()`, `intents()`, `coupons()`, `users()` y `webhookEndpoints()`.
- `Recurrente::test()` para verificar las credenciales y el ambiente.
- `Recurrente::forAccount('ac_...')` y la config `account_id` para operar sobre cuentas conectadas (`X-ACCOUNT-ID`).
- Verbo `put()` en el cliente (Recurrente usa PUT para actualizar) y parámetro opcional `$headers` en todos los verbos.
- Soporte de `Idempotency-Key` en reembolsos, actualización de checkouts y cancelación de invoice items.
- `products()->list()` y `products()->update()`.
- `subscriptions()->update()`, `previewProration()`, `invoiceItems()` y `cancelInvoiceItem()`.
- `checkouts()->update()`.
- `customers()->createCustomer()`, `list()` y `update()` sobre `/customers`.
- `CheckoutData`: `withProduct()`, `customerId()`, `discountCode()`, `setupMode()`, `bankTransferMemo()`, `claveEnabled()`, `paymentMethodTypes()`, `with()` y un parámetro `$extra` en `withPrice()` (metadata por item, etc.).
- Webhooks: clase `EventType` con todos los eventos documentados; `WebhookEvent::fromRequest()`; `is()`, `isIntentSucceeded()`, `isIntentFailed()`, `isSubscriptionStarted()`, `isSubscriptionEnded()`, `isSubscriptionPastDue()` e `isRefundCreated()`; accesores `paymentId()`, `checkoutId()`, `customerId()`, `subscriptionId()`, `amountInCents()`, `currency()`, `status()` e `idempotencyKey()` (basado en `svix-id`).
- `WebhookVerifier::verifyRequest()` y middleware `recurrente.webhook`.
- Archivo `LICENSE`, CI en GitHub Actions y este changelog.

### Corregido
- `paymentMethods()` ahora también envía `payment_method_types`, el campo actual de la API. Antes solo enviaba campos que la API ya no documenta, así que la elección podía ignorarse. Si llamas a `paymentMethods(card: true, bankTransfer: false)`, la transferencia ahora sí se deshabilita en ese item.
- `customers()->get()` consultaba `/users/{id}`, que no existe en la API, para cualquier ID. Ahora consulta `/customers/{id}` y mantiene `/users/{id}` para IDs `us_...`.
- `WebhookVerifier::verify()` lanzaba `TypeError` cuando el secreto era `null` (por ejemplo, `RECURRENTE_WEBHOOK_SECRET` vacío). Ahora devuelve `false`, y si no pasas secreto usa el configurado.
- `WebhookEvent::metadata()` ahora también lee `checkout.metadata`, que es donde la trae el formato unificado de cobros.
- El esquema de firma en config ya no distingue mayúsculas (`SVIX` = `svix`).

### Deprecado (se mantiene en 1.x, cambia en 2.0)
- `customers()->create()`: crea un usuario (`POST /users`). Usa `createCustomer()` o `users()->create()`.
- `WebhookEvent::isSubscriptionCreated()` / `isSubscriptionCancelled()`: nunca coincidían con los eventos reales (`subscription.create` / `subscription.cancel`). Usa `isSubscriptionStarted()` / `isSubscriptionEnded()`.

### Sin cambios a propósito
- `isPaymentCompleted()` / `isPaymentFailed()` siguen detectando solo el formato legacy. Agregarles `intent.*` haría que las cuentas que reciben ambos formatos procesaran cada cobro dos veces. Ver la sección "Formatos de evento" del README.

## [1.0.0] - 2026-06-21

- Primera versión.
