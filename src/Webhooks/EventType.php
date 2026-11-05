<?php

namespace Recurrente\Laravel\Webhooks;

/**
 * Tipos de evento (event_type) documentados por Recurrente.
 *
 * Los cobros llegan en dos formatos: el unificado "intent.*" y el legacy por
 * método de pago ("payment_intent.*", "bank_transfer_intent.*", ...). Las
 * cuentas antiguas reciben AMBOS por cada cobro; las nuevas solo "intent.*".
 */
final class EventType
{
    // Cobros — formato unificado
    public const INTENT_SUCCEEDED = 'intent.succeeded';
    public const INTENT_PENDING   = 'intent.pending';
    public const INTENT_FAILED    = 'intent.failed';
    public const INTENT_CANCELED  = 'intent.canceled';
    public const INTENT_PAID      = 'intent.paid';

    // Cobros — formato legacy
    public const PAYMENT_INTENT_SUCCEEDED       = 'payment_intent.succeeded';
    public const PAYMENT_INTENT_FAILED          = 'payment_intent.failed';
    public const BANK_TRANSFER_INTENT_SUCCEEDED = 'bank_transfer_intent.succeeded';
    public const BANK_TRANSFER_INTENT_PENDING   = 'bank_transfer_intent.pending';
    public const BANK_TRANSFER_INTENT_FAILED    = 'bank_transfer_intent.failed';

    // Suscripciones
    public const SUBSCRIPTION_CREATE              = 'subscription.create';
    public const SUBSCRIPTION_PAST_DUE            = 'subscription.past_due';
    public const SUBSCRIPTION_PAUSE               = 'subscription.pause';
    public const SUBSCRIPTION_UNPAUSE             = 'subscription.unpause';
    public const SUBSCRIPTION_REACTIVATE          = 'subscription.reactivate';
    public const SUBSCRIPTION_UPDATE              = 'subscription.update';
    public const SUBSCRIPTION_ITEM_ADDED          = 'subscription.item_added';
    public const SUBSCRIPTION_ITEM_REMOVED        = 'subscription.item_removed';
    public const SUBSCRIPTION_INVOICE_ITEM_ADDED  = 'subscription.invoice_item_added';
    public const SUBSCRIPTION_CANCEL              = 'subscription.cancel';

    // Setup intents (tarjeta tokenizada con mode=setup)
    public const SETUP_INTENT_SUCCEEDED = 'setup_intent.succeeded';
    public const SETUP_INTENT_CANCELLED = 'setup_intent.cancelled';

    // Reembolsos y contracargos
    public const REFUND_CREATE  = 'refund.create';
    public const DISPUTE_CREATE = 'dispute.create';
    public const DISPUTE_UPDATE = 'dispute.update';
}
