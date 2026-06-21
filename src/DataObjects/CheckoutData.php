<?php

namespace Recurrente\Laravel\DataObjects;

/**
 * Builder tipado para crear un checkout en Recurrente.
 *
 * Soporta dos modos de items:
 *  - withPrice(priceId)            → usa un precio existente (producto/suscripción)
 *  - withInlineItem(...)           → crea un producto invisible al vuelo
 */
class CheckoutData
{
    /** @var array<int,array<string,mixed>> */
    protected array $items = [];

    protected ?string $successUrl = null;
    protected ?string $cancelUrl = null;
    protected ?string $userId = null;
    protected ?string $expiresAt = null;

    /** @var array<string,mixed> */
    protected array $metadata = [];

    protected bool $cardEnabled = true;
    protected bool $bankTransferEnabled = true;

    public static function make(): self
    {
        return new self();
    }

    public function withPrice(string $priceId, int $quantity = 1): self
    {
        $this->items[] = array_filter([
            'price_id' => $priceId,
            'quantity' => $quantity,
        ]);

        return $this;
    }

    /**
     * Item inline (crea producto invisible). Para suscripción usar charge_type=recurring.
     *
     * @param  array<string,mixed>  $extra  billing_interval, billing_interval_count, free_trial_interval, etc.
     */
    public function withInlineItem(
        string $name,
        int $amountInCents,
        string $currency = 'GTQ',
        string $chargeType = 'one_time',
        array $extra = [],
    ): self {
        $this->items[] = array_merge([
            'name'            => $name,
            'amount_in_cents' => $amountInCents,
            'currency'        => $currency,
            'charge_type'     => $chargeType,
        ], $extra);

        return $this;
    }

    public function successUrl(string $url): self
    {
        $this->successUrl = $url;

        return $this;
    }

    public function cancelUrl(string $url): self
    {
        $this->cancelUrl = $url;

        return $this;
    }

    public function userId(?string $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    public function expiresAt(?string $iso8601): self
    {
        $this->expiresAt = $iso8601;

        return $this;
    }

    /** @param array<string,mixed> $metadata */
    public function metadata(array $metadata): self
    {
        $this->metadata = $metadata;

        return $this;
    }

    public function paymentMethods(bool $card = true, bool $bankTransfer = true): self
    {
        $this->cardEnabled = $card;
        $this->bankTransferEnabled = $bankTransfer;

        return $this;
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        // Inyecta configuración de métodos de pago a nivel de item (modo inline).
        $items = array_map(function (array $item): array {
            if (isset($item['name'])) { // item inline
                $item['custom_payment_method_settings'] = true;
                $item['card_payments_enabled'] = $this->cardEnabled ? 'true' : 'false';
                $item['bank_transfer_payments_enabled'] = $this->bankTransferEnabled ? 'true' : 'false';
            }

            return $item;
        }, $this->items);

        return array_filter([
            'items'       => $items,
            'success_url' => $this->successUrl,
            'cancel_url'  => $this->cancelUrl,
            'user_id'     => $this->userId,
            'expires_at'  => $this->expiresAt,
            'metadata'    => $this->metadata ?: null,
        ], static fn ($v) => $v !== null && $v !== []);
    }
}
