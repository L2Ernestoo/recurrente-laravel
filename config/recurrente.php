<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Credenciales de API
    |--------------------------------------------------------------------------
    | Estas pueden venir del .env como valores por defecto, pero la aplicación
    | puede sobreescribirlas en runtime (por ejemplo, leyéndolas desde la base
    | de datos) construyendo un RecurrenteClient con llaves explícitas.
    */
    'public_key'     => env('RECURRENTE_PUBLIC_KEY'),
    'secret_key'     => env('RECURRENTE_SECRET_KEY'),
    'webhook_secret' => env('RECURRENTE_WEBHOOK_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Endpoint base de la API
    |--------------------------------------------------------------------------
    */
    'base_url' => env('RECURRENTE_BASE_URL', 'https://app.recurrente.com/api'),

    /*
    |--------------------------------------------------------------------------
    | Modo (test | live) — informativo para la UI/logs
    |--------------------------------------------------------------------------
    */
    'mode' => env('RECURRENTE_MODE', 'test'),

    /*
    |--------------------------------------------------------------------------
    | Verificación de webhooks
    |--------------------------------------------------------------------------
    | scheme: "svix" (headers svix-id/svix-timestamp/svix-signature, base64 HMAC)
    |         o "hmac" (un solo header con HMAC-SHA256 hex del cuerpo crudo).
    | signature_header: nombre del header cuando scheme = "hmac".
    | tolerance: segundos de tolerancia para el timestamp (anti-replay).
    */
    'webhook' => [
        'scheme'           => env('RECURRENTE_WEBHOOK_SCHEME', 'svix'),
        'signature_header' => env('RECURRENTE_WEBHOOK_SIGNATURE_HEADER', 'X-Recurrente-Signature'),
        'tolerance'        => (int) env('RECURRENTE_WEBHOOK_TOLERANCE', 300),
    ],

    /*
    |--------------------------------------------------------------------------
    | Timeout de las peticiones HTTP (segundos)
    |--------------------------------------------------------------------------
    */
    'timeout' => (int) env('RECURRENTE_TIMEOUT', 30),
];
