<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

/**
 * Page model for CMS-style static pages.
 *
 * Supports slug-based routing, publish/draft states, and SEO metadata.
 */
class Page extends Model
{
    protected string $table = 'pages';

    protected array $fillable = [
        'title',
        'slug',
        'content',
        'excerpt',
        'meta_title',
        'meta_description',
        'status',
        'template',
        'author_id',
        'published_at',
        'created_at',
        'updated_at',
    ];

    /** @var string Published page status. */
    public const STATUS_PUBLISHED = 'published';

    /** @var string Draft page status. */
    public const STATUS_DRAFT = 'draft';

    /**
     * Find a page by its URL slug.
     *
     * @param string $slug The unique page slug.
     * @return array|null The page record or null.
     */
    public function findBySlug(string $slug): ?array
    {
        return $this->findBy('slug', $slug);
    }

    /**
     * Check if the page is currently published.
     *
     * @param array $page The page record.
     * @return bool True if the page is published.
     */
    public function isPublished(array $page): bool
    {
        return ($page['status'] ?? '') === self::STATUS_PUBLISHED;
    }
}
