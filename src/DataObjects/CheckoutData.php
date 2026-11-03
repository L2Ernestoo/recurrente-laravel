<?php

namespace Recurrente\Laravel\DataObjects;

/**
 * Builder tipado para crear un checkout en Recurrente.
 *
 * Soporta dos modos de items:
 *  - withPrice(priceId) / withProduct(productId) → usa un producto/precio existente
 *  - withInlineItem(...)                         → crea un producto invisible al vuelo
 */
class CheckoutData
{
    /** @var array<int,array<string,mixed>> */
    protected array $items = [];

    protected ?string $successUrl = null;
    protected ?string $cancelUrl = null;
    protected ?string $userId = null;
    protected ?string $customerId = null;
    protected ?string $expiresAt = null;
    protected ?string $discountCode = null;
    protected ?string $mode = null;
    protected ?string $bankTransferMemo = null;
    protected ?bool $claveEnabled = null;

    /** @var array<string,mixed> */
    protected array $metadata = [];

    /** @var array<string,mixed> */
    protected array $extra = [];

    protected bool $cardEnabled = true;
    protected bool $bankTransferEnabled = true;

    /**
     * Métodos de pago explícitos para items inline (payment_method_types).
     * null = no se envía y el item hereda la configuración de la cuenta.
     *
     * @var array<int,string>|null
     */
    protected ?array $paymentMethodTypes = null;

    public static function make(): self
    {
        return new self();
    }

    /**
     * @param  array<string,mixed>  $extra  metadata, tax_category, etc. del item
     */
    public function withPrice(string $priceId, int $quantity = 1, array $extra = []): self
    {
        $this->items[] = array_merge(array_filter([
            'price_id' => $priceId,
            'quantity' => $quantity,
        ]), $extra);

        return $this;
    }

    /**
     * Usa el precio por defecto de un producto existente.
     *
     * @param  array<string,mixed>  $extra  metadata, tax_category, etc. del item
     */
    public function withProduct(string $productId, int $quantity = 1, array $extra = []): self
    {
        $this->items[] = array_merge(array_filter([
            'product_id' => $productId,
            'quantity'   => $quantity,
        ]), $extra);

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

    /** Prepopula los datos del comprador. Tiene prioridad sobre userId(). */
    public function customerId(?string $customerId): self
    {
        $this->customerId = $customerId;

        return $this;
    }

    public function expiresAt(?string $iso8601): self
    {
        $this->expiresAt = $iso8601;

        return $this;
    }

    public function discountCode(?string $code): self
    {
        $this->discountCode = $code;

        return $this;
    }

    /** Tokeniza una tarjeta sin cobrarla (mode=setup). */
    public function setupMode(bool $setup = true): self
    {
        $this->mode = $setup ? 'setup' : null;

        return $this;
    }

    /** Referencia que el pagador copia al hacer una transferencia bancaria (3–32 caracteres). */
    public function bankTransferMemo(?string $memo): self
    {
        $this->bankTransferMemo = $memo;

        return $this;
    }

    /** Envía false para ocultar Clave (tarjetas guardadas) en este checkout. */
    public function claveEnabled(bool $enabled): self
    {
        $this->claveEnabled = $enabled;

        return $this;
    }

    /** @param array<string,mixed> $metadata */
    public function metadata(array $metadata): self
    {
        $this->metadata = $metadata;

        return $this;
    }

    /**
     * Campos de nivel superior que el builder aún no modela
     * (p. ej. application_fee_amount, transfer_setups).
     *
     * @param  array<string,mixed>  $fields
     */
    public function with(array $fields): self
    {
        $this->extra = array_merge($this->extra, $fields);

        return $this;
    }

    /**
     * Habilita tarjeta y/o transferencia en los items inline.
     *
     * Ojo: Recurrente solo admite métodos de pago por item en modo inline; con
     * withPrice()/withProduct() se usa la configuración del producto.
     */
    public function paymentMethods(bool $card = true, bool $bankTransfer = true): self
    {
        $this->cardEnabled = $card;
        $this->bankTransferEnabled = $bankTransfer;

        return $this->paymentMethodTypes(array_keys(array_filter([
            'card'          => $card,
            'bank_transfer' => $bankTransfer,
        ])));
    }

    /**
     * Lista explícita de métodos para items inline: card, bank_transfer,
     * stablecoins, balance, akisi.
     *
     * @param  array<int,string>  $types
     */
    public function paymentMethodTypes(array $types): self
    {
        $this->paymentMethodTypes = array_values($types);

        return $this;
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        // Inyecta configuración de métodos de pago a nivel de item (modo inline).
        $items = array_map(function (array $item): array {
            if (isset($item['name'])) { // item inline
                // Campos legacy: se conservan para no alterar checkouts existentes.
                $item['custom_payment_method_settings'] = true;
                $item['card_payments_enabled'] = $this->cardEnabled ? 'true' : 'false';
                $item['bank_transfer_payments_enabled'] = $this->bankTransferEnabled ? 'true' : 'false';

                if ($this->paymentMethodTypes !== null && ! isset($item['payment_method_types'])) {
                    $item['payment_method_types'] = $this->paymentMethodTypes;
                }
            }

            return $item;
        }, $this->items);

        return array_filter(array_merge([
            'items'              => $items,
            'mode'               => $this->mode,
            'success_url'        => $this->successUrl,
            'cancel_url'         => $this->cancelUrl,
            'user_id'            => $this->userId,
            'customer_id'        => $this->customerId,
            'expires_at'         => $this->expiresAt,
            'discount_code'      => $this->discountCode,
            'bank_transfer_memo' => $this->bankTransferMemo,
            'clave_enabled'      => $this->claveEnabled,
            'metadata'           => $this->metadata ?: null,
        ], $this->extra), static fn ($v) => $v !== null && $v !== []);
    }
}
