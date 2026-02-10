<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

/**
 * Plan model representing a subscription tier.
 *
 * Plans define feature access, resource limits, and pricing for tenants.
 */
class Plan extends Model
{
    protected string $table = 'plans';

    protected array $fillable = [
        'name',
        'slug',
        'description',
        'price_monthly',
        'price_yearly',
        'currency',
        'limits',
        'features',
        'integrations',
        'is_featured',
        'is_active',
        'sort_order',
        'trial_days',
        'created_at',
        'updated_at',
    ];

    /**
     * Find a plan by its slug.
     *
     * @param string $slug The URL-friendly plan identifier.
     * @return array|null The plan record or null.
     */
    public function findBySlug(string $slug): ?array
    {
        return $this->findBy('slug', $slug);
    }

    /**
     * Check whether the plan includes a specific feature.
     *
     * @param array  $plan    The plan record.
     * @param string $feature The feature key to check (e.g. "custom_branding").
     * @return bool True if the feature is included in the plan.
     */
    public function hasFeature(array $plan, string $feature): bool
    {
        $features = json_decode($plan['features'] ?? '[]', true) ?: [];

        return in_array($feature, $features, true);
    }

    /**
     * Check whether the plan supports a specific integration.
     *
     * @param array  $plan        The plan record.
     * @param string $integration The integration key (e.g. "mailchimp", "salesforce").
     * @return bool True if the integration is available on this plan.
     */
    public function hasIntegration(array $plan, string $integration): bool
    {
        $integrations = json_decode($plan['integrations'] ?? '[]', true) ?: [];

        return in_array($integration, $integrations, true);
    }

    /**
     * Retrieve all active plans ordered by sort order.
     *
     * @return array List of active plan records.
     */
    public function getActivePlans(): array
    {
        $stmt = $this->db()->prepare(
            "SELECT * FROM {$this->table} WHERE is_active = 1 ORDER BY sort_order ASC"
        );
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Retrieve the featured plan (used for highlighting on the pricing page).
     *
     * @return array|null The featured plan record or null.
     */
    public function getFeaturedPlan(): ?array
    {
        $results = $this->where(['is_featured' => 1, 'is_active' => 1]);

        return $results[0] ?? null;
    }
}
