<?php

declare(strict_types=1);

namespace App\Controllers\Client;

use Core\Controller;

/**
 * Client Profile Controller
 *
 * Delegates to SettingsController methods for profile, security, API keys,
 * pixels, and AI settings management.
 */
class ProfileController extends Controller
{
    private SettingsController $settings;

    public function __construct()
    {
        parent::__construct();
        $this->settings = new SettingsController();
    }

    // Profile
    public function index(): string
    {
        return $this->settings->profile();
    }

    public function update(): string
    {
        return $this->settings->updateProfile();
    }

    // Security
    public function security(): string
    {
        return $this->settings->security();
    }

    public function updatePassword(): string
    {
        return $this->settings->updatePassword();
    }

    // 2FA
    public function show2FA(): string
    {
        return $this->settings->enable2FA();
    }

    public function enable2FA(): string
    {
        return $this->settings->enable2FA();
    }

    public function verify2FA(): string
    {
        return $this->settings->verify2FA();
    }

    public function disable2FA(): string
    {
        return $this->settings->disable2FA();
    }

    // API Keys
    public function apiKeys(): string
    {
        return $this->settings->api();
    }

    public function generateApiKey(): string
    {
        return $this->settings->generateApiKey();
    }

    public function revokeApiKey(string $id): string
    {
        return $this->settings->revokeApiKey($id);
    }

    // Pixels
    public function pixels(): string
    {
        return $this->settings->pixels();
    }

    public function savePixel(): string
    {
        return $this->settings->savePixel();
    }

    public function deletePixel(string $id): string
    {
        return $this->settings->deletePixel($id);
    }

    // AI Settings
    public function aiSettings(): string
    {
        return $this->settings->ai();
    }

    public function updateAi(): string
    {
        return $this->settings->updateAi();
    }
}
