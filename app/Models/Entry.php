<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

/**
 * Entry model representing a single form submission.
 *
 * An entry is created when a respondent starts filling out a form and may
 * be partial (abandoned) or complete.
 */
class Entry extends Model
{
    protected string $table = 'entries';

    protected array $fillable = [
        'form_id',
        'tenant_id',
        'respondent_id',
        'status',
        'score',
        'metadata',
        'ip_address',
        'user_agent',
        'started_at',
        'completed_at',
        'created_at',
        'updated_at',
    ];

    /** @var string Entry in progress (respondent still answering). */
    public const STATUS_PARTIAL = 'partial';

    /** @var string Entry fully completed by the respondent. */
    public const STATUS_COMPLETE = 'complete';

    /**
     * Get the form this entry belongs to.
     *
     * @return array|null The form record or null.
     */
    public function form(): ?array
    {
        return $this->belongsTo(Form::class, 'form_id');
    }

    /**
     * Get all field values submitted in this entry.
     *
     * @param int $entryId The entry ID.
     * @return array List of entry value records.
     */
    public function values(int $entryId): array
    {
        return (new EntryValue())->where(['entry_id' => $entryId]);
    }

    /**
     * Get all notes attached to this entry by team members.
     *
     * @param int $entryId The entry ID.
     * @return array List of note records.
     */
    public function notes(int $entryId): array
    {
        $stmt = $this->db()->prepare(
            'SELECT * FROM entry_notes WHERE entry_id = :entry_id ORDER BY created_at ASC'
        );
        $stmt->execute(['entry_id' => $entryId]);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Determine if the entry has been fully completed.
     *
     * @param array $entry The entry record.
     * @return bool True if the status is complete.
     */
    public function isComplete(array $entry): bool
    {
        return ($entry['status'] ?? '') === self::STATUS_COMPLETE;
    }

    /**
     * Determine if the entry is partial (abandoned or in progress).
     *
     * @param array $entry The entry record.
     * @return bool True if the status is partial.
     */
    public function isPartial(array $entry): bool
    {
        return ($entry['status'] ?? '') === self::STATUS_PARTIAL;
    }

    /**
     * Mark an entry as completed.
     *
     * @param int $entryId The entry ID.
     * @return bool True on success.
     */
    public function markComplete(int $entryId): bool
    {
        return (bool) $this->update($entryId, [
            'status'       => self::STATUS_COMPLETE,
            'completed_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Calculate and store a score for the entry based on field weights.
     *
     * Scoring is useful for quiz-style forms or lead qualification.
     *
     * @param int $entryId The entry ID.
     * @return float The calculated score.
     */
    public function calculateScore(int $entryId): float
    {
        $entry  = $this->find($entryId);
        $values = $this->values($entryId);
        $score  = 0.0;

        $fieldModel = new FormField();

        foreach ($values as $value) {
            $field = $fieldModel->find((int) $value['field_id']);

            if ($field === null) {
                continue;
            }

            $settings = json_decode($field['settings'] ?? '{}', true) ?: [];

            if (isset($settings['score_map'])) {
                $scoreMap = $settings['score_map'];
                $answer   = $value['value'] ?? '';

                if (isset($scoreMap[$answer])) {
                    $score += (float) $scoreMap[$answer];
                }
            }
        }

        $this->update($entryId, ['score' => $score]);

        return $score;
    }

    /**
     * Get the tenant that owns this entry.
     *
     * @return array|null The tenant record or null.
     */
    public function tenant(): ?array
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}
