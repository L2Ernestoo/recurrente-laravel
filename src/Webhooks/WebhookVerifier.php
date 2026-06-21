<?php

namespace Recurrente\Laravel\Webhooks;

/**
 * Verifica la autenticidad de un webhook de Recurrente.
 *
 * Soporta dos esquemas (configurable):
 *  - "svix": headers svix-id, svix-timestamp, svix-signature. La firma es
 *            base64(HMAC-SHA256(secret, "{id}.{timestamp}.{payload}")). El secreto
 *            suele venir con prefijo "whsec_" y estar en base64.
 *  - "hmac": un único header con HMAC-SHA256(secret, payload) en hex.
 */
class WebhookVerifier
{
    public function __construct(
        protected string $scheme = 'svix',
        protected string $signatureHeader = 'X-Recurrente-Signature',
        protected int $tolerance = 300,
    ) {}

    /**
     * @param  string  $payload  Cuerpo crudo del request (json string sin modificar)
     * @param  array<string,string|array<int,string>>  $headers  Headers normalizados (case-insensitive)
     */
    public function verify(string $payload, array $headers, string $secret): bool
    {
        if ($secret === '') {
            return false;
        }

        return $this->scheme === 'svix'
            ? $this->verifySvix($payload, $headers, $secret)
            : $this->verifyHmac($payload, $headers, $secret);
    }

    // ── Esquemas ────────────────────────────────────────────────────────────

    protected function verifySvix(string $payload, array $headers, string $secret): bool
    {
        $id        = $this->header($headers, 'svix-id') ?? $this->header($headers, 'webhook-id');
        $timestamp = $this->header($headers, 'svix-timestamp') ?? $this->header($headers, 'webhook-timestamp');
        $sigHeader = $this->header($headers, 'svix-signature') ?? $this->header($headers, 'webhook-signature');

        if (! $id || ! $timestamp || ! $sigHeader) {
            return false;
        }

        if (! $this->withinTolerance((int) $timestamp)) {
            return false;
        }

        // El secreto svix viene como "whsec_<base64>"; quitamos el prefijo y decodificamos.
        $rawSecret = str_starts_with($secret, 'whsec_') ? substr($secret, 6) : $secret;
        $key = base64_decode($rawSecret, true);
        if ($key === false) {
            $key = $rawSecret; // si no es base64, úsalo tal cual
        }

        $signedContent = "{$id}.{$timestamp}.{$payload}";
        $expected = base64_encode(hash_hmac('sha256', $signedContent, $key, true));

        // El header puede contener varias firmas separadas por espacio, cada una "v1,<sig>".
        foreach (preg_split('/\s+/', trim($sigHeader)) as $part) {
            $candidate = str_contains($part, ',') ? explode(',', $part, 2)[1] : $part;
            if (hash_equals($expected, $candidate)) {
                return true;
            }
        }

        return false;
    }

    protected function verifyHmac(string $payload, array $headers, string $secret): bool
    {
        $provided = $this->header($headers, strtolower($this->signatureHeader));
        if (! $provided) {
            return false;
        }

        $expected = hash_hmac('sha256', $payload, $secret);

        // Tolera prefijos tipo "sha256=".
        $provided = str_contains($provided, '=') ? explode('=', $provided, 2)[1] : $provided;

        return hash_equals($expected, $provided);
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    protected function withinTolerance(int $timestamp): bool
    {
        if ($this->tolerance <= 0) {
            return true;
        }

        return abs(time() - $timestamp) <= $this->tolerance;
    }

    /**
     * @param  array<string,string|array<int,string>>  $headers
     */
    protected function header(array $headers, string $name): ?string
    {
        foreach ($headers as $key => $value) {
            if (strtolower($key) === strtolower($name)) {
                return is_array($value) ? ($value[0] ?? null) : $value;
            }
        }

        return null;
    }
}
