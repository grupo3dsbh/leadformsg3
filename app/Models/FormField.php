<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

/**
 * FormField model representing a single question or input in a conversational form.
 *
 * Supports multiple field types (text, email, select, rating, file upload, etc.)
 * with conditional logic connections and validation rules.
 */
class FormField extends Model
{
    protected string $table = 'form_fields';

    protected array $fillable = [
        'form_id',
        'type',
        'label',
        'description',
        'placeholder',
        'options',
        'validation_rules',
        'settings',
        'position',
        'is_required',
        'is_hidden',
        'connections',
        'created_at',
        'updated_at',
    ];

    /**
     * Get the form this field belongs to.
     *
     * @return array|null The parent form record or null.
     */
    public function form(): ?array
    {
        return $this->belongsTo(Form::class, 'form_id');
    }

    /**
     * Get the selectable options for this field (e.g. for dropdowns, radio buttons).
     *
     * @param array $field The field record.
     * @return array Decoded list of option objects.
     */
    public function options(array $field): array
    {
        return json_decode($field['options'] ?? '[]', true) ?: [];
    }

    /**
     * Get the conditional logic connections for this field.
     *
     * Connections define branching rules like "if answer equals X, jump to field Y".
     *
     * @param array $field The field record.
     * @return array Decoded list of connection rules.
     */
    public function connections(array $field): array
    {
        return json_decode($field['connections'] ?? '[]', true) ?: [];
    }

    /**
     * Build a set of validation rules for the field.
     *
     * Returns an associative array suitable for server-side validation,
     * combining stored custom rules with implicit rules based on field type.
     *
     * @param array $field The field record.
     * @return array<string, mixed> Validation rule set.
     */
    public function getValidationRules(array $field): array
    {
        $customRules = json_decode($field['validation_rules'] ?? '{}', true) ?: [];

        $rules = [];

        if ($this->isRequired($field)) {
            $rules['required'] = true;
        }

        // Infer rules from field type.
        $rules = match ($field['type'] ?? 'text') {
            'email'  => array_merge($rules, ['type' => 'email']),
            'url'    => array_merge($rules, ['type' => 'url']),
            'number' => array_merge($rules, ['type' => 'numeric']),
            'phone'  => array_merge($rules, ['type' => 'phone']),
            'date'   => array_merge($rules, ['type' => 'date']),
            'file'   => array_merge($rules, [
                'type'      => 'file',
                'max_size'  => $customRules['max_size'] ?? 10_485_760, // 10 MB default
                'mimetypes' => $customRules['mimetypes'] ?? [],
            ]),
            'rating' => array_merge($rules, [
                'type' => 'numeric',
                'min'  => $customRules['min'] ?? 1,
                'max'  => $customRules['max'] ?? 5,
            ]),
            default => $rules,
        };

        // Merge remaining custom rules without overwriting type-derived ones.
        return array_merge($customRules, $rules);
    }

    /**
     * Determine if the field is required.
     *
     * @param array $field The field record.
     * @return bool True if the field must be answered.
     */
    public function isRequired(array $field): bool
    {
        return !empty($field['is_required']);
    }
}
