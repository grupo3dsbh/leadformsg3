<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

/**
 * Payment model for recording financial transactions.
 *
 * Tracks individual payment events linked to tenants and subscriptions,
 * including gateway references, amounts, and statuses.
 */
class Payment extends Model
{
    protected string $table = 'payments';

    protected array $fillable = [
        'tenant_id',
        'subscription_id',
        'amount',
        'currency',
        'status',
        'payment_gateway',
        'gateway_payment_id',
        'gateway_invoice_id',
        'description',
        'metadata',
        'paid_at',
        'refunded_at',
        'created_at',
        'updated_at',
    ];

    /** @var string Successful payment status. */
    public const STATUS_PAID = 'paid';

    /** @var string Pending payment status. */
    public const STATUS_PENDING = 'pending';

    /** @var string Failed payment status. */
    public const STATUS_FAILED = 'failed';

    /** @var string Refunded payment status. */
    public const STATUS_REFUNDED = 'refunded';

    /**
     * Get the tenant this payment belongs to.
     *
     * @return array|null The tenant record or null.
     */
    public function tenant(): ?array
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * Get the subscription associated with this payment.
     *
     * @return array|null The subscription record or null.
     */
    public function subscription(): ?array
    {
        return $this->belongsTo(Subscription::class, 'subscription_id');
    }

    /**
     * Check if the payment was successfully completed.
     *
     * @param array $payment The payment record.
     * @return bool True if the payment status is "paid".
     */
    public function isPaid(array $payment): bool
    {
        return ($payment['status'] ?? '') === self::STATUS_PAID;
    }
}
