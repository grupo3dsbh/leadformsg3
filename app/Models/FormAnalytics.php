<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

/**
 * FormAnalytics model for aggregated form performance metrics.
 *
 * Stores daily snapshots of views, starts, completions, and other KPIs
 * to power analytics dashboards and reporting.
 */
class FormAnalytics extends Model
{
    protected string $table = 'form_analytics';

    protected array $fillable = [
        'form_id',
        'date',
        'views',
        'starts',
        'completions',
        'abandons',
        'avg_completion_time',
        'unique_visitors',
        'device_breakdown',
        'referrer_breakdown',
        'created_at',
        'updated_at',
    ];

    /**
     * Retrieve analytics data for a specific form within a date range.
     *
     * @param int        $formId    The form ID.
     * @param array{from: string, to: string} $dateRange Associative array with "from" and "to" dates (Y-m-d).
     * @return array List of daily analytics records.
     */
    public function forForm(int $formId, array $dateRange = []): array
    {
        $sql    = "SELECT * FROM {$this->table} WHERE form_id = :form_id";
        $params = ['form_id' => $formId];

        if (!empty($dateRange['from'])) {
            $sql .= ' AND date >= :date_from';
            $params['date_from'] = $dateRange['from'];
        }

        if (!empty($dateRange['to'])) {
            $sql .= ' AND date <= :date_to';
            $params['date_to'] = $dateRange['to'];
        }

        $sql .= ' ORDER BY date ASC';

        $stmt = $this->db()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Calculate the conversion rate for a form over a given period.
     *
     * Conversion rate = (total completions / total views) * 100.
     *
     * @param int   $formId    The form ID.
     * @param array $dateRange Optional date range filter with "from" and "to" keys.
     * @return float The conversion rate as a percentage (0.0 - 100.0).
     */
    public function getConversionRate(int $formId, array $dateRange = []): float
    {
        $records = $this->forForm($formId, $dateRange);

        $totalViews       = 0;
        $totalCompletions = 0;

        foreach ($records as $record) {
            $totalViews       += (int) ($record['views'] ?? 0);
            $totalCompletions += (int) ($record['completions'] ?? 0);
        }

        if ($totalViews === 0) {
            return 0.0;
        }

        return round(($totalCompletions / $totalViews) * 100, 2);
    }
}
