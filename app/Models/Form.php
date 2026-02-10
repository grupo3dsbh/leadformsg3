<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

/**
 * Form model representing a conversational form.
 *
 * Forms are the core entity of the application, containing fields, collecting
 * entries, and supporting analytics, theming, and third-party integrations.
 */
class Form extends Model
{
    protected string $table = 'forms';

    protected array $fillable = [
        'tenant_id',
        'created_by',
        'title',
        'slug',
        'description',
        'status',
        'theme_id',
        'settings',
        'password',
        'welcome_screen',
        'thank_you_screen',
        'published_at',
        'expires_at',
        'archived_at',
        'views_count',
        'created_at',
        'updated_at',
    ];

    /** @var string Draft status (not yet published). */
    public const STATUS_DRAFT = 'draft';

    /** @var string Published and accepting responses. */
    public const STATUS_PUBLISHED = 'published';

    /** @var string Archived and no longer accepting responses. */
    public const STATUS_ARCHIVED = 'archived';

    /**
     * Find a form by its unique slug.
     *
     * @param string $slug The URL-friendly form slug.
     * @return array|null The form record or null.
     */
    public function findBySlug(string $slug): ?array
    {
        return $this->findBy('slug', $slug);
    }

    /**
     * Get all fields belonging to this form, ordered by position.
     *
     * @param int $formId The form ID.
     * @return array List of field records.
     */
    public function fields(int $formId): array
    {
        $stmt = $this->db()->prepare(
            'SELECT * FROM form_fields WHERE form_id = :form_id ORDER BY position ASC'
        );
        $stmt->execute(['form_id' => $formId]);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Get all entries submitted to this form.
     *
     * @param int $formId The form ID.
     * @return array List of entry records.
     */
    public function entries(int $formId): array
    {
        return (new Entry())->where(['form_id' => $formId]);
    }

    /**
     * Check if the form is currently published and accepting responses.
     *
     * @param array $form The form record.
     * @return bool True if the form is published and not expired.
     */
    public function isPublished(array $form): bool
    {
        if (($form['status'] ?? '') !== self::STATUS_PUBLISHED) {
            return false;
        }

        if (!empty($form['expires_at']) && strtotime($form['expires_at']) < time()) {
            return false;
        }

        return true;
    }

    /**
     * Check if the form has passed its expiration date.
     *
     * @param array $form The form record.
     * @return bool True if the form has expired.
     */
    public function isExpired(array $form): bool
    {
        $expiresAt = $form['expires_at'] ?? null;

        if ($expiresAt === null) {
            return false;
        }

        return strtotime($expiresAt) < time();
    }

    /**
     * Check if the form requires a password to access.
     *
     * @param array $form The form record.
     * @return bool True if a password is set.
     */
    public function isPasswordProtected(array $form): bool
    {
        return !empty($form['password']);
    }

    /**
     * Publish the form, making it publicly accessible.
     *
     * @param int $formId The form ID.
     * @return bool True on success.
     */
    public function publish(int $formId): bool
    {
        return (bool) $this->update($formId, [
            'status'       => self::STATUS_PUBLISHED,
            'published_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Archive the form, preventing new submissions.
     *
     * @param int $formId The form ID.
     * @return bool True on success.
     */
    public function archive(int $formId): bool
    {
        return (bool) $this->update($formId, [
            'status'      => self::STATUS_ARCHIVED,
            'archived_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Duplicate a form and all its fields.
     *
     * @param int $formId The ID of the form to duplicate.
     * @return array|null The newly created form record or null on failure.
     */
    public function duplicate(int $formId): ?array
    {
        $original = $this->find($formId);

        if ($original === null) {
            return null;
        }

        $newData = array_intersect_key($original, array_flip($this->fillable));
        $newData['title']        = $original['title'] . ' (Copy)';
        $newData['slug']         = $original['slug'] . '-copy-' . bin2hex(random_bytes(4));
        $newData['status']       = self::STATUS_DRAFT;
        $newData['published_at'] = null;
        $newData['archived_at']  = null;
        $newData['views_count']  = 0;
        $newData['created_at']   = date('Y-m-d H:i:s');
        $newData['updated_at']   = date('Y-m-d H:i:s');

        $newFormId = $this->create($newData);

        if (!$newFormId) {
            return null;
        }

        // Duplicate form fields.
        $fields = $this->fields($formId);
        $fieldModel = new FormField();

        foreach ($fields as $field) {
            unset($field['id']);
            $field['form_id']    = $newFormId;
            $field['created_at'] = date('Y-m-d H:i:s');
            $field['updated_at'] = date('Y-m-d H:i:s');
            $fieldModel->create($field);
        }

        return $this->find($newFormId);
    }

    /**
     * Get the tenant that owns this form.
     *
     * @return array|null The tenant record or null.
     */
    public function tenant(): ?array
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * Get the user who created this form.
     *
     * @return array|null The user record or null.
     */
    public function creator(): ?array
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get analytics data for this form.
     *
     * @param int $formId The form ID.
     * @return array List of analytics records.
     */
    public function analytics(int $formId): array
    {
        return (new FormAnalytics())->where(['form_id' => $formId]);
    }

    /**
     * Get the theme applied to this form.
     *
     * @param array $form The form record.
     * @return array|null The theme record or null.
     */
    public function theme(array $form): ?array
    {
        if (empty($form['theme_id'])) {
            return null;
        }

        $stmt = $this->db()->prepare('SELECT * FROM themes WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $form['theme_id']]);

        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Get all integrations connected to this form.
     *
     * @param int $formId The form ID.
     * @return array List of integration records.
     */
    public function integrations(int $formId): array
    {
        $stmt = $this->db()->prepare(
            'SELECT i.* FROM integrations i
             INNER JOIN form_integrations fi ON fi.integration_id = i.id
             WHERE fi.form_id = :form_id'
        );
        $stmt->execute(['form_id' => $formId]);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Increment the view counter for a form.
     *
     * @param int $formId The form ID.
     * @return bool True on success.
     */
    public function incrementViews(int $formId): bool
    {
        $stmt = $this->db()->prepare(
            "UPDATE {$this->table} SET views_count = views_count + 1 WHERE id = :id"
        );

        return $stmt->execute(['id' => $formId]);
    }
}
