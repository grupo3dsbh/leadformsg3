<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

/**
 * ApiToken model for managing API authentication tokens.
 *
 * Tokens are scoped to a tenant and user, with fine-grained abilities
 * (permissions) and optional expiration dates.
 */
class ApiToken extends Model
{
    protected string $table = 'api_tokens';

    protected array $fillable = [
        'tenant_id',
        'user_id',
        'name',
        'token_hash',
        'abilities',
        'last_used_at',
        'expires_at',
        'created_at',
        'updated_at',
    ];

    /**
     * Generate a new API token for a tenant user.
     *
     * Returns an array with the plaintext token (shown once) and the created record.
     *
     * @param int      $tenantId  The tenant the token belongs to.
     * @param int      $userId    The user who created the token.
     * @param string   $name      A human-readable name for the token.
     * @param string[] $abilities List of abilities/scopes (e.g. ["forms:read", "entries:write"]).
     * @return array{token: string, record: array|null} The plaintext token and the stored record.
     */
    public static function generate(int $tenantId, int $userId, string $name, array $abilities = ['*']): array
    {
        $plaintext = 'lf_' . bin2hex(random_bytes(32));
        $hash      = hash('sha256', $plaintext);

        $instance = new static();

        $id = $instance->create([
            'tenant_id'  => $tenantId,
            'user_id'    => $userId,
            'name'       => $name,
            'token_hash' => $hash,
            'abilities'  => json_encode($abilities),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return [
            'token'  => $plaintext,
            'record' => $id ? $instance->find($id) : null,
        ];
    }

    /**
     * Verify a plaintext token and return the matching record.
     *
     * @param string $token The plaintext API token.
     * @return array|null The token record if valid and not expired, or null.
     */
    public function verify(string $token): ?array
    {
        $hash = hash('sha256', $token);

        $record = $this->findBy('token_hash', $hash);

        if ($record === null) {
            return null;
        }

        // Check expiration.
        if (!empty($record['expires_at']) && strtotime($record['expires_at']) < time()) {
            return null;
        }

        return $record;
    }

    /**
     * Check if a token record has a specific ability.
     *
     * The wildcard ability "*" grants access to everything.
     *
     * @param array  $tokenRecord The token record.
     * @param string $ability     The ability to check (e.g. "forms:read").
     * @return bool True if the token has the requested ability.
     */
    public function hasAbility(array $tokenRecord, string $ability): bool
    {
        $abilities = json_decode($tokenRecord['abilities'] ?? '[]', true) ?: [];

        if (in_array('*', $abilities, true)) {
            return true;
        }

        return in_array($ability, $abilities, true);
    }

    /**
     * Update the last_used_at timestamp on a token.
     *
     * @param int $tokenId The API token ID.
     * @return bool True on success.
     */
    public function markUsed(int $tokenId): bool
    {
        return (bool) $this->update($tokenId, [
            'last_used_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
