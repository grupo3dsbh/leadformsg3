<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

/**
 * Translation model for managing i18n strings.
 *
 * Stores localised text grouped by keys, locales, and logical groups
 * (e.g. "ui", "emails", "validation").
 */
class Translation extends Model
{
    protected string $table = 'translations';

    protected array $fillable = [
        'key',
        'value',
        'locale',
        'group',
        'created_at',
        'updated_at',
    ];

    /**
     * Retrieve a translated value by key and locale.
     *
     * Falls back to the key itself if no translation is found.
     *
     * @param string $key    The translation key (e.g. "form.submit_button").
     * @param string $locale The locale code (e.g. "en", "fr", "de").
     * @param string $group  The logical group (default "general").
     * @return string The translated value or the original key as fallback.
     */
    public static function get(string $key, string $locale = 'en', string $group = 'general'): string
    {
        $instance = new static();

        $stmt = $instance->db()->prepare(
            "SELECT value FROM {$instance->table}
             WHERE `key` = :key AND locale = :locale AND `group` = :group
             LIMIT 1"
        );
        $stmt->execute([
            'key'    => $key,
            'locale' => $locale,
            'group'  => $group,
        ]);

        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result['value'] ?? $key;
    }

    /**
     * Set (insert or update) a translation value.
     *
     * @param string $key    The translation key.
     * @param string $value  The translated text.
     * @param string $locale The locale code.
     * @param string $group  The logical group (default "general").
     * @return bool True on success.
     */
    public static function set(
        string $key,
        string $value,
        string $locale = 'en',
        string $group = 'general',
    ): bool {
        $instance = new static();

        $stmt = $instance->db()->prepare(
            "SELECT id FROM {$instance->table}
             WHERE `key` = :key AND locale = :locale AND `group` = :group
             LIMIT 1"
        );
        $stmt->execute([
            'key'    => $key,
            'locale' => $locale,
            'group'  => $group,
        ]);

        $existing = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($existing) {
            return (bool) $instance->update((int) $existing['id'], [
                'value'      => $value,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        return (bool) $instance->create([
            'key'        => $key,
            'value'      => $value,
            'locale'     => $locale,
            'group'      => $group,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get all translations for a specific locale, optionally filtered by group.
     *
     * @param string      $locale The locale code.
     * @param string|null $group  Optional group filter.
     * @return array<string, string> Associative array of key => value pairs.
     */
    public function allForLocale(string $locale, ?string $group = null): array
    {
        $sql    = "SELECT `key`, value FROM {$this->table} WHERE locale = :locale";
        $params = ['locale' => $locale];

        if ($group !== null) {
            $sql .= ' AND `group` = :group';
            $params['group'] = $group;
        }

        $stmt = $this->db()->prepare($sql);
        $stmt->execute($params);

        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        $translations = [];
        foreach ($rows as $row) {
            $translations[$row['key']] = $row['value'];
        }

        return $translations;
    }
}
