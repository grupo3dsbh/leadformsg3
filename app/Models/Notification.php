<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

/**
 * Notification model for in-app user notifications.
 *
 * Supports read/unread state tracking and user-scoped retrieval.
 */
class Notification extends Model
{
    protected string $table = 'notifications';

    protected array $fillable = [
        'user_id',
        'tenant_id',
        'type',
        'title',
        'body',
        'data',
        'action_url',
        'read_at',
        'created_at',
    ];

    /**
     * Get the user this notification is addressed to.
     *
     * @return array|null The user record or null.
     */
    public function user(): ?array
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Mark the notification as read.
     *
     * @param int $notificationId The notification ID.
     * @return bool True on success.
     */
    public function markRead(int $notificationId): bool
    {
        return (bool) $this->update($notificationId, [
            'read_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Retrieve all unread notifications for a specific user, newest first.
     *
     * @param int $userId The user ID.
     * @return array List of unread notification records.
     */
    public function unreadForUser(int $userId): array
    {
        $stmt = $this->db()->prepare(
            "SELECT * FROM {$this->table}
             WHERE user_id = :user_id AND read_at IS NULL
             ORDER BY created_at DESC"
        );
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }
}
