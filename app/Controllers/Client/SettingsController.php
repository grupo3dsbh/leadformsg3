<?php

declare(strict_types=1);

namespace App\Controllers\Client;

use Core\Controller;
use App\Models\User;

/**
 * Client Settings Controller
 *
 * Manages account-level settings: profile, security (password/2FA),
 * API keys, tracking pixels, and AI configuration for the current user/tenant.
 */
class SettingsController extends Controller
{
    private User $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User();
    }

    // -------------------------------------------------------------------------
    // Profile
    // -------------------------------------------------------------------------

    /**
     * Display the profile settings page.
     */
    public function profile(): string
    {
        $user = auth();

        return $this->view('client/settings/profile', [
            'user' => $user,
        ], 'layouts.client');
    }

    /**
     * Save profile changes.
     */
    public function updateProfile(): string
    {
        $user = auth();

        $errors = $this->validate($_POST, [
            'name'     => 'required|string|max:255',
            'email'    => 'required|email',
            'timezone' => 'nullable|string|max:50',
            'locale'   => 'nullable|string|max:10',
        ]);

        if (!empty($errors)) {
            return $this->view('client/settings/profile', [
                'user'   => array_merge($user, $_POST),
                'errors' => $errors,
            ], 'layouts.client');
        }

        // Check email uniqueness if changed
        $newEmail = trim($_POST['email']);
        if ($newEmail !== $user['email']) {
            $existingUser = $this->userModel->findByEmail($newEmail);
            if ($existingUser && (int) $existingUser['id'] !== (int) $user['id']) {
                return $this->view('client/settings/profile', [
                    'user'   => array_merge($user, $_POST),
                    'errors' => ['email' => 'This email is already in use.'],
                ], 'layouts.client');
            }
        }

        $updateData = [
            'name'       => trim($_POST['name']),
            'email'      => $newEmail,
            'timezone'   => trim($_POST['timezone'] ?? $user['timezone'] ?? 'UTC'),
            'locale'     => trim($_POST['locale'] ?? $user['locale'] ?? 'en'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        // Handle avatar upload
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $allowed  = ['image/jpeg', 'image/png', 'image/webp'];
            $mimeType = mime_content_type($_FILES['avatar']['tmp_name']);

            if (in_array($mimeType, $allowed, true) && $_FILES['avatar']['size'] <= 2 * 1024 * 1024) {
                $ext       = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
                $filename  = 'avatar_' . $user['id'] . '_' . time() . '.' . $ext;
                $uploadDir = ROOT_PATH . '/public/uploads/avatars/';

                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                move_uploaded_file($_FILES['avatar']['tmp_name'], $uploadDir . $filename);
                $updateData['avatar'] = '/uploads/avatars/' . $filename;
            }
        }

        $this->userModel->update((int) $user['id'], $updateData);

        return $this->redirect('/dashboard/profile', [
            'success' => 'Profile updated successfully.',
        ]);
    }

    // -------------------------------------------------------------------------
    // Security
    // -------------------------------------------------------------------------

    /**
     * Display security settings (password, 2FA).
     */
    public function security(): string
    {
        $user = auth();

        return $this->view('client/settings/security', [
            'user'           => $user,
            'twoFactorEnabled' => !empty($user['two_factor_enabled']),
        ], 'layouts.client');
    }

    /**
     * Change the current user's password.
     */
    public function updatePassword(): string
    {
        $user = auth();

        $errors = $this->validate($_POST, [
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:8',
            'confirm_password' => 'required|string',
        ]);

        if (!empty($errors)) {
            return $this->view('client/settings/security', [
                'user'             => $user,
                'twoFactorEnabled' => !empty($user['two_factor_enabled']),
                'errors'           => $errors,
                'section'          => 'password',
            ], 'layouts.client');
        }

        // Verify current password
        if (!$this->userModel->verifyPassword($_POST['current_password'], $user['password'])) {
            return $this->view('client/settings/security', [
                'user'             => $user,
                'twoFactorEnabled' => !empty($user['two_factor_enabled']),
                'errors'           => ['current_password' => 'Current password is incorrect.'],
                'section'          => 'password',
            ], 'layouts.client');
        }

        // Confirm new password match
        if ($_POST['new_password'] !== $_POST['confirm_password']) {
            return $this->view('client/settings/security', [
                'user'             => $user,
                'twoFactorEnabled' => !empty($user['two_factor_enabled']),
                'errors'           => ['confirm_password' => 'Passwords do not match.'],
                'section'          => 'password',
            ], 'layouts.client');
        }

        $this->userModel->update((int) $user['id'], [
            'password' => password_hash($_POST['new_password'], PASSWORD_ARGON2ID),
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);

        return $this->redirect('/dashboard/security', [
            'success' => 'Password changed successfully.',
        ]);
    }

    /**
     * Initiate 2FA setup: generate secret and show QR code.
     */
    public function enable2FA(): string
    {
        $user = auth();

        if (!empty($user['two_factor_enabled'])) {
            return $this->redirect('/dashboard/security', [
                'info' => 'Two-factor authentication is already enabled.',
            ]);
        }

        // Generate a new TOTP secret (base32 encoded, 20 bytes)
        $secret = $this->generateTOTPSecret();

        // Store the pending secret in session until verified
        $_SESSION['pending_2fa_secret'] = $secret;

        $appName  = urlencode($_ENV['APP_NAME'] ?? 'LeadForm');
        $userEmail = urlencode($user['email']);
        $otpAuthUrl = "otpauth://totp/{$appName}:{$userEmail}?secret={$secret}&issuer={$appName}&digits=6&period=30";

        return $this->view('client/settings/enable-2fa', [
            'user'       => $user,
            'secret'     => $secret,
            'otpAuthUrl' => $otpAuthUrl,
        ], 'layouts.client');
    }

    /**
     * Verify the 2FA code and enable two-factor authentication.
     */
    public function verify2FA(): string
    {
        $user   = auth();
        $secret = $_SESSION['pending_2fa_secret'] ?? '';
        $code   = trim($_POST['code'] ?? '');

        if ($secret === '' || $code === '') {
            return $this->redirect('/dashboard/security', [
                'error' => 'Invalid 2FA setup session. Please try again.',
            ]);
        }

        // Verify TOTP code
        if (!$this->verifyTOTP($secret, $code)) {
            return $this->view('client/settings/enable-2fa', [
                'user'       => $user,
                'secret'     => $secret,
                'otpAuthUrl' => '',
                'errors'     => ['code' => 'Invalid verification code. Please try again.'],
            ], 'layouts.client');
        }

        // Store the secret and enable 2FA
        $this->userModel->update((int) $user['id'], [
            'two_factor_secret'  => $secret,
            'two_factor_enabled' => 1,
            'updated_at'         => date('Y-m-d H:i:s'),
        ]);

        unset($_SESSION['pending_2fa_secret']);

        // Generate recovery codes
        $recoveryCodes = [];
        for ($i = 0; $i < 8; $i++) {
            $recoveryCodes[] = strtoupper(bin2hex(random_bytes(4)));
        }

        // Store recovery codes (hashed)
        $hashedCodes = array_map(fn($c) => hash('sha256', $c), $recoveryCodes);
        $this->db()->prepare(
            "UPDATE users SET updated_at = NOW() WHERE id = :id"
        )->execute([
            'codes' => json_encode($hashedCodes),
            'id'    => (int) $user['id'],
        ]);

        return $this->view('client/settings/2fa-recovery', [
            'recoveryCodes' => $recoveryCodes,
        ], 'layouts.client');
    }

    /**
     * Disable two-factor authentication.
     */
    public function disable2FA(): string
    {
        $user = auth();

        $errors = $this->validate($_POST, [
            'password' => 'required|string',
        ]);

        if (!empty($errors)) {
            return $this->redirect('/dashboard/security', [
                'error' => 'Password is required to disable 2FA.',
            ]);
        }

        if (!$this->userModel->verifyPassword($_POST['password'], $user['password'])) {
            return $this->redirect('/dashboard/security', [
                'error' => 'Incorrect password.',
            ]);
        }

        $this->userModel->update((int) $user['id'], [
            'two_factor_secret'   => null,
            'two_factor_enabled'  => 0,
            'updated_at'          => date('Y-m-d H:i:s'),
        ]);

        return $this->redirect('/dashboard/security', [
            'success' => 'Two-factor authentication has been disabled.',
        ]);
    }

    // -------------------------------------------------------------------------
    // API Keys
    // -------------------------------------------------------------------------

    /**
     * Display the API keys management page.
     */
    public function api(): string
    {
        $db       = $this->db();
        $tenantId = (int) tenant()['id'];

        $stmt = $db->prepare(
            "SELECT * FROM api_tokens WHERE tenant_id = :tid ORDER BY created_at DESC"
        );
        $stmt->execute(['tid' => $tenantId]);
        $apiKeys = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $this->view('client/settings/api', [
            'apiKeys' => $apiKeys,
        ], 'layouts.client');
    }

    /**
     * Generate a new API key.
     */
    public function generateApiKey(): string
    {
        $tenantId = (int) tenant()['id'];

        $errors = $this->validate($_POST, [
            'name' => 'required|string|max:100',
        ]);

        if (!empty($errors)) {
            return $this->redirect('/dashboard/api', [
                'error' => 'A name is required for the API key.',
            ]);
        }

        // Generate a secure API key
        $rawKey    = 'lf_' . bin2hex(random_bytes(32));
        $hashedKey = hash('sha256', $rawKey);
        $prefix    = substr($rawKey, 0, 10) . '...';

        $this->db()->prepare(
            "INSERT INTO api_tokens (tenant_id, name, key_hash, key_prefix, permissions, is_active, expires_at, created_at)
             VALUES (:tid, :name, :hash, :prefix, :perms, 1, :expires, NOW())"
        )->execute([
            'tid'     => $tenantId,
            'name'    => trim($_POST['name']),
            'hash'    => $hashedKey,
            'prefix'  => $prefix,
            'perms'   => json_encode($_POST['permissions'] ?? ['*']),
            'expires' => !empty($_POST['expires_days'])
                ? date('Y-m-d H:i:s', strtotime('+' . (int) $_POST['expires_days'] . ' days'))
                : null,
        ]);

        // Flash the raw key (only shown once)
        $_SESSION['flash_api_key'] = $rawKey;

        return $this->redirect('/dashboard/api', [
            'success'   => 'API key generated. Copy it now -- it will not be shown again.',
            'newApiKey' => $rawKey,
        ]);
    }

    /**
     * Revoke an API key.
     */
    public function revokeApiKey(string $id): string
    {
        $tenantId = (int) tenant()['id'];

        $stmt = $this->db()->prepare(
            "SELECT * FROM api_tokens WHERE id = :id AND tenant_id = :tid"
        );
        $stmt->execute(['id' => (int) $id, 'tid' => $tenantId]);
        $apiKey = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$apiKey) {
            return $this->redirect('/dashboard/api', ['error' => 'API key not found.']);
        }

        $this->db()->prepare("DELETE FROM api_tokens WHERE id = :id")->execute(['id' => (int) $id]);

        return $this->redirect('/dashboard/api', [
            'success' => "API key \"{$apiKey['name']}\" has been revoked.",
        ]);
    }

    // -------------------------------------------------------------------------
    // Tracking Pixels
    // -------------------------------------------------------------------------

    /**
     * Display the tracking pixels management page.
     */
    public function pixels(): string
    {
        $db       = $this->db();
        $tenantId = (int) tenant()['id'];

        $stmt = $db->prepare(
            "SELECT * FROM tracking_pixels WHERE tenant_id = :tid ORDER BY created_at DESC"
        );
        $stmt->execute(['tid' => $tenantId]);
        $pixels = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $this->view('client/settings/pixels', [
            'pixels' => $pixels,
        ], 'layouts.client');
    }

    /**
     * Save a new or update an existing tracking pixel.
     */
    public function savePixel(): string
    {
        $tenantId = (int) tenant()['id'];

        $errors = $this->validate($_POST, [
            'name'     => 'required|string|max:100',
            'type'     => 'required|in:facebook,google_analytics,google_tag_manager,custom',
            'pixel_id' => 'required|string|max:255',
        ]);

        if (!empty($errors)) {
            return $this->redirect('/dashboard/pixels', [
                'error' => 'Please fill in all required fields correctly.',
            ]);
        }

        $pixelCode = trim($_POST['pixel_code'] ?? '');

        if (!empty($_POST['pixel_db_id'])) {
            // Update existing
            $this->db()->prepare(
                "UPDATE tracking_pixels
                    SET name       = :name,
                        type       = :type,
                        pixel_id   = :pid,
                        pixel_code = :code,
                        is_active  = :active,
                        updated_at = NOW()
                  WHERE id = :id AND tenant_id = :tid"
            )->execute([
                'name'   => trim($_POST['name']),
                'type'   => $_POST['type'],
                'pid'    => trim($_POST['pixel_id']),
                'code'   => $pixelCode,
                'active' => !empty($_POST['is_active']) ? 1 : 0,
                'id'     => (int) $_POST['pixel_db_id'],
                'tid'    => $tenantId,
            ]);
        } else {
            // Create new
            $this->db()->prepare(
                "INSERT INTO tracking_pixels (tenant_id, name, type, pixel_id, pixel_code, is_active, created_at, updated_at)
                 VALUES (:tid, :name, :type, :pid, :code, :active, NOW(), NOW())"
            )->execute([
                'tid'    => $tenantId,
                'name'   => trim($_POST['name']),
                'type'   => $_POST['type'],
                'pid'    => trim($_POST['pixel_id']),
                'code'   => $pixelCode,
                'active' => !empty($_POST['is_active']) ? 1 : 0,
            ]);
        }

        return $this->redirect('/dashboard/pixels', [
            'success' => 'Tracking pixel saved.',
        ]);
    }

    /**
     * Delete a tracking pixel.
     */
    public function deletePixel(string $id): string
    {
        $tenantId = (int) tenant()['id'];

        $this->db()->prepare(
            "DELETE FROM tracking_pixels WHERE id = :id AND tenant_id = :tid"
        )->execute([
            'id'  => (int) $id,
            'tid' => $tenantId,
        ]);

        return $this->redirect('/dashboard/pixels', [
            'success' => 'Tracking pixel removed.',
        ]);
    }

    // -------------------------------------------------------------------------
    // AI Settings
    // -------------------------------------------------------------------------

    /**
     * Display AI settings for the tenant.
     */
    public function ai(): string
    {
        $db       = $this->db();
        $tenantId = (int) tenant()['id'];

        $tenantData     = tenant();
        $tenantSettings = json_decode($tenantData['settings'] ?? '{}', true) ?: [];
        $aiSettings     = $tenantSettings['ai'] ?? [];

        // Check if AI is enabled platform-wide
        $globalAiStmt = $db->prepare(
            "SELECT `value` FROM system_settings WHERE `key` = 'ai_enabled_for_clients' LIMIT 1"
        );
        $globalAiStmt->execute();
        $aiEnabledGlobally = (bool) $globalAiStmt->fetchColumn();

        return $this->view('client/settings/ai', [
            'aiSettings'        => $aiSettings,
            'aiEnabledGlobally' => $aiEnabledGlobally,
        ], 'layouts.client');
    }

    /**
     * Save AI settings for the tenant.
     */
    public function updateAi(): string
    {
        $db       = $this->db();
        $tenantId = (int) tenant()['id'];

        $tenantData     = tenant();
        $tenantSettings = json_decode($tenantData['settings'] ?? '{}', true) ?: [];

        $tenantSettings['ai'] = [
            'enabled'             => !empty($_POST['ai_enabled']),
            'form_generation'     => !empty($_POST['ai_form_generation']),
            'entry_analysis'      => !empty($_POST['ai_entry_analysis']),
            'auto_categorization' => !empty($_POST['ai_auto_categorization']),
            'custom_prompt'       => trim($_POST['ai_custom_prompt'] ?? ''),
        ];

        $db->prepare(
            "UPDATE tenants SET settings = :settings, updated_at = NOW() WHERE id = :id"
        )->execute([
            'settings' => json_encode($tenantSettings),
            'id'       => $tenantId,
        ]);

        return $this->redirect('/dashboard/ai', [
            'success' => 'AI settings saved.',
        ]);
    }

    // -------------------------------------------------------------------------
    // TOTP helpers
    // -------------------------------------------------------------------------

    /**
     * Generate a random base32-encoded TOTP secret.
     */
    private function generateTOTPSecret(int $length = 20): string
    {
        $chars  = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';

        $bytes = random_bytes($length);
        for ($i = 0; $i < $length; $i++) {
            $secret .= $chars[ord($bytes[$i]) % 32];
        }

        return $secret;
    }

    /**
     * Verify a TOTP code against a secret with a +/- 1 window.
     */
    private function verifyTOTP(string $secret, string $code, int $window = 1): bool
    {
        $timeSlice = (int) floor(time() / 30);

        for ($i = -$window; $i <= $window; $i++) {
            $calculatedCode = $this->calculateTOTP($secret, $timeSlice + $i);
            if (hash_equals($calculatedCode, $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Calculate a TOTP code for a given time slice.
     */
    private function calculateTOTP(string $secret, int $timeSlice): string
    {
        // Decode base32 secret
        $secretBytes = $this->base32Decode($secret);

        // Pack time into 8-byte binary
        $time = pack('N*', 0, $timeSlice);

        // HMAC-SHA1
        $hmac = hash_hmac('sha1', $time, $secretBytes, true);

        // Dynamic truncation
        $offset = ord($hmac[19]) & 0x0F;
        $code   = (
            ((ord($hmac[$offset]) & 0x7F) << 24) |
            ((ord($hmac[$offset + 1]) & 0xFF) << 16) |
            ((ord($hmac[$offset + 2]) & 0xFF) << 8) |
            (ord($hmac[$offset + 3]) & 0xFF)
        ) % 1000000;

        return str_pad((string) $code, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Decode a base32-encoded string.
     */
    private function base32Decode(string $input): string
    {
        $map = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $input = strtoupper(rtrim($input, '='));

        $buffer = 0;
        $bitsLeft = 0;
        $output = '';

        for ($i = 0, $len = strlen($input); $i < $len; $i++) {
            $val = strpos($map, $input[$i]);
            if ($val === false) {
                continue;
            }

            $buffer = ($buffer << 5) | $val;
            $bitsLeft += 5;

            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $output .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }

        return $output;
    }
}
