<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

/**
 * TrackingPixel model for embedding analytics tracking codes in forms.
 *
 * Supports Facebook Pixel, Google Analytics, Google Tag Manager, and
 * custom tracking scripts scoped to tenants or individual forms.
 */
class TrackingPixel extends Model
{
    protected string $table = 'tracking_pixels';

    protected array $fillable = [
        'tenant_id',
        'form_id',
        'name',
        'provider',
        'pixel_id',
        'config',
        'is_active',
        'created_at',
        'updated_at',
    ];

    /**
     * Get the tenant that owns this tracking pixel.
     *
     * @return array|null The tenant record or null.
     */
    public function tenant(): ?array
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * Get the form this tracking pixel is scoped to (null means all tenant forms).
     *
     * @param array $pixel The tracking pixel record.
     * @return array|null The form record, or null if tenant-wide.
     */
    public function form(array $pixel): ?array
    {
        if (empty($pixel['form_id'])) {
            return null;
        }

        return (new Form())->find((int) $pixel['form_id']);
    }

    /**
     * Check if the tracking pixel is enabled.
     *
     * @param array $pixel The tracking pixel record.
     * @return bool True if the pixel is active.
     */
    public function isActive(array $pixel): bool
    {
        return !empty($pixel['is_active']);
    }
}
