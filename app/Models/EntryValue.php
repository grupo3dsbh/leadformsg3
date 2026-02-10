<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

/**
 * EntryValue model representing a single field answer within a form entry.
 *
 * Each entry value links an entry to a form field and stores the respondent's answer.
 */
class EntryValue extends Model
{
    protected string $table = 'entry_values';

    protected array $fillable = [
        'entry_id',
        'field_id',
        'value',
        'file_path',
        'metadata',
        'created_at',
    ];

    /**
     * Get the entry this value belongs to.
     *
     * @return array|null The parent entry record or null.
     */
    public function entry(): ?array
    {
        return $this->belongsTo(Entry::class, 'entry_id');
    }

    /**
     * Get the form field this value answers.
     *
     * @return array|null The form field record or null.
     */
    public function field(): ?array
    {
        return $this->belongsTo(FormField::class, 'field_id');
    }
}
