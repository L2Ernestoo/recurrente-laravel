<?php

namespace Recurrente\Laravel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Recurrente\Laravel\Webhooks\WebhookVerifier;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rechaza con 400 los webhooks cuya firma no es válida.
 *
 *   Route::post('/webhooks/recurrente', ...)->middleware('recurrente.webhook');
 */
class VerifyRecurrenteWebhook
{
    public function __construct(protected WebhookVerifier $verifier) {}

    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($this->verifier->verifyRequest($request), 400, 'Firma de webhook inválida.');

        return $next($request);
    }
}
