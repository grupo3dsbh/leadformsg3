<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

/**
 * SystemSetting model for global application configuration.
 *
 * Stores typed key-value pairs grouped into logical sections
 * (e.g. "mail", "storage", "appearance").
 */
class SystemSetting extends Model
{
    protected string $table = 'system_settings';

    protected array $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
        'created_at',
        'updated_at',
    ];

    /**
     * Retrieve a system setting value by key.
     *
     * The raw value is cast according to the stored type column.
     *
     * @param string $key     The setting key (e.g. "mail.from_address").
     * @param mixed  $default The fallback value if the key does not exist.
     * @return mixed The setting value, cast to the appropriate type.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $instance = new static();
        $record   = $instance->findBy('key', $key);

        if ($record === null) {
            return $default;
        }

        return self::castValue($record['value'], $record['type'] ?? 'string');
    }

    /**
     * Set (insert or update) a system setting.
     *
     * @param string $key   The setting key.
     * @param mixed  $value The value to store.
     * @param string $type  The value type for casting ("string", "int", "float", "bool", "json").
     * @param string $group The logical group (default "general").
     * @return bool True on success.
     */
    public static function set(
        string $key,
        mixed $value,
        string $type = 'string',
        string $group = 'general',
    ): bool {
        $instance = new static();
        $record   = $instance->findBy('key', $key);

        $storedValue = match ($type) {
            'bool'  => $value ? '1' : '0',
            'json'  => json_encode($value),
            default => (string) $value,
        };

        if ($record !== null) {
            return (bool) $instance->update((int) $record['id'], [
                'value'      => $storedValue,
                'type'       => $type,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        return (bool) $instance->create([
            'key'        => $key,
            'value'      => $storedValue,
            'type'       => $type,
            'group'      => $group,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get all settings within a logical group.
     *
     * @param string $group The group name (e.g. "mail", "storage").
     * @return array<string, mixed> Associative array of key => casted value.
     */
    public static function getGroup(string $group): array
    {
        $instance = new static();
        $rows     = $instance->where(['group' => $group]);

        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['key']] = self::castValue($row['value'], $row['type'] ?? 'string');
        }

        return $settings;
    }

    /**
     * Cast a raw string value to the appropriate PHP type.
     *
     * @param string|null $value The raw value from the database.
     * @param string      $type  The type hint ("string", "int", "float", "bool", "json").
     * @return mixed The cast value.
     */
    private static function castValue(?string $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'int', 'integer' => (int) $value,
            'float', 'double' => (float) $value,
            'bool', 'boolean' => in_array($value, ['1', 'true', 'yes'], true),
            'json', 'array' => json_decode($value, true),
            default => $value,
        };
    }
}
