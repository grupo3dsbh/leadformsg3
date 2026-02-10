<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

/**
 * Feature model for feature flags and plan-gated capabilities.
 *
 * Allows toggling features globally and restricting access to specific plan tiers.
 */
class Feature extends Model
{
    protected string $table = 'features';

    protected array $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
        'required_plan_id',
        'settings',
        'created_at',
        'updated_at',
    ];

    /**
     * Check if the feature is globally active.
     *
     * @param array $feature The feature record.
     * @return bool True if the feature is enabled.
     */
    public function isActive(array $feature): bool
    {
        return !empty($feature['is_active']);
    }

    /**
     * Get the minimum plan required to access this feature.
     *
     * @param array $feature The feature record.
     * @return array|null The required plan record, or null if no plan restriction exists.
     */
    public function requiredPlan(array $feature): ?array
    {
        if (empty($feature['required_plan_id'])) {
            return null;
        }

        return (new Plan())->find((int) $feature['required_plan_id']);
    }

    /**
     * Find a feature by its slug.
     *
     * @param string $slug The feature slug (e.g. "custom_branding", "file_uploads").
     * @return array|null The feature record or null.
     */
    public function findBySlug(string $slug): ?array
    {
        return $this->findBy('slug', $slug);
    }
}
