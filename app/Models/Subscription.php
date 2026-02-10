<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

/**
 * Subscription model linking tenants to plans with billing cycles.
 *
 * Tracks subscription status, trial periods, renewals, and cancellations.
 */
class Subscription extends Model
{
    protected string $table = 'subscriptions';

    protected array $fillable = [
        'tenant_id',
        'plan_id',
        'status',
        'billing_cycle',
        'amount',
        'currency',
        'payment_gateway',
        'gateway_subscription_id',
        'trial_ends_at',
        'current_period_start',
        'current_period_end',
        'cancelled_at',
        'expires_at',
        'created_at',
        'updated_at',
    ];

    /** @var string Active subscription status. */
    public const STATUS_ACTIVE = 'active';

    /** @var string Trialling subscription status. */
    public const STATUS_TRIAL = 'trial';

    /** @var string Cancelled subscription status. */
    public const STATUS_CANCELLED = 'cancelled';

    /** @var string Expired subscription status. */
    public const STATUS_EXPIRED = 'expired';

    /** @var string Past-due subscription status. */
    public const STATUS_PAST_DUE = 'past_due';

    /**
     * Check if the subscription is currently active (includes trial).
     *
     * @param array $subscription The subscription record.
     * @return bool True if active or trialling.
     */
    public function isActive(array $subscription): bool
    {
        return in_array(
            $subscription['status'] ?? '',
            [self::STATUS_ACTIVE, self::STATUS_TRIAL],
            true
        );
    }

    /**
     * Check if the subscription is in a trial period.
     *
     * @param array $subscription The subscription record.
     * @return bool True if currently trialling.
     */
    public function isTrial(array $subscription): bool
    {
        if (($subscription['status'] ?? '') !== self::STATUS_TRIAL) {
            return false;
        }

        $trialEnd = $subscription['trial_ends_at'] ?? null;

        if ($trialEnd === null) {
            return false;
        }

        return strtotime($trialEnd) > time();
    }

    /**
     * Check if the subscription has expired.
     *
     * @param array $subscription The subscription record.
     * @return bool True if expired or past the current period end.
     */
    public function isExpired(array $subscription): bool
    {
        if (($subscription['status'] ?? '') === self::STATUS_EXPIRED) {
            return true;
        }

        $expiresAt = $subscription['expires_at'] ?? $subscription['current_period_end'] ?? null;

        if ($expiresAt === null) {
            return false;
        }

        return strtotime($expiresAt) < time();
    }

    /**
     * Cancel the subscription.
     *
     * Sets the status to cancelled and records the cancellation timestamp.
     *
     * @param int $subscriptionId The subscription ID.
     * @return bool True on success.
     */
    public function cancel(int $subscriptionId): bool
    {
        return (bool) $this->update($subscriptionId, [
            'status'       => self::STATUS_CANCELLED,
            'cancelled_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Renew the subscription for a new billing period.
     *
     * @param int         $subscriptionId The subscription ID.
     * @param string|null $periodEnd      The new period end date (Y-m-d H:i:s). Defaults to +1 month.
     * @return bool True on success.
     */
    public function renew(int $subscriptionId, ?string $periodEnd = null): bool
    {
        $now = date('Y-m-d H:i:s');
        $periodEnd ??= date('Y-m-d H:i:s', strtotime('+1 month'));

        return (bool) $this->update($subscriptionId, [
            'status'               => self::STATUS_ACTIVE,
            'current_period_start' => $now,
            'current_period_end'   => $periodEnd,
            'cancelled_at'         => null,
            'expires_at'           => null,
        ]);
    }

    /**
     * Change the subscription to a different plan.
     *
     * @param int $subscriptionId The subscription ID.
     * @param int $newPlanId      The new plan ID to switch to.
     * @return bool True on success.
     */
    public function changePlan(int $subscriptionId, int $newPlanId): bool
    {
        return (bool) $this->update($subscriptionId, [
            'plan_id'    => $newPlanId,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get the tenant that owns this subscription.
     *
     * @return array|null The tenant record or null.
     */
    public function tenant(): ?array
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * Get the plan associated with this subscription.
     *
     * @return array|null The plan record or null.
     */
    public function plan(): ?array
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }
}
