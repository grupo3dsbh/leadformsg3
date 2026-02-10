<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use Core\Controller;

/**
 * Super Admin Settings Controller
 *
 * Manages all platform-wide configuration: general settings, SEO, theme,
 * AI configuration, API settings, development mode, and site information.
 */
class SettingsController extends Controller
{
    /**
     * General settings page.
     */
    public function index(): string
    {
        $settings = $this->loadSettings();

        return $this->view('admin/settings/index', [
            'settings' => $settings,
        ]);
    }

    /**
     * Save general settings.
     */
    public function update(): string
    {
        $errors = $this->validate($_POST, [
            'app_timezone'     => 'required|string',
            'default_locale'   => 'required|string|max:10',
            'items_per_page'   => 'required|integer|min:5|max:100',
            'registration'     => 'required|in:open,closed,invite',
            'email_from_name'  => 'required|string|max:255',
            'email_from_email' => 'required|email',
        ]);

        if (!empty($errors)) {
            return $this->view('admin/settings/index', [
                'settings' => array_merge($this->loadSettings(), $_POST),
                'errors'   => $errors,
            ]);
        }

        $this->saveSetting('app_timezone', $_POST['app_timezone']);
        $this->saveSetting('default_locale', $_POST['default_locale']);
        $this->saveSetting('items_per_page', $_POST['items_per_page']);
        $this->saveSetting('registration', $_POST['registration']);
        $this->saveSetting('email_from_name', $_POST['email_from_name']);
        $this->saveSetting('email_from_email', $_POST['email_from_email']);
        $this->saveSetting('maintenance_mode', !empty($_POST['maintenance_mode']) ? '1' : '0');
        $this->saveSetting('allow_file_uploads', !empty($_POST['allow_file_uploads']) ? '1' : '0');
        $this->saveSetting('max_upload_size_mb', $_POST['max_upload_size_mb'] ?? '10');

        return $this->redirect('/admin/settings', ['success' => 'General settings saved.']);
    }

    /**
     * SEO settings page.
     */
    public function seo(): string
    {
        $settings = $this->loadSettings('seo_');

        return $this->view('admin/settings/seo', [
            'settings' => $settings,
        ]);
    }

    /**
     * Save SEO settings.
     */
    public function updateSeo(): string
    {
        $errors = $this->validate($_POST, [
            'seo_title'       => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:500',
        ]);

        if (!empty($errors)) {
            return $this->view('admin/settings/seo', [
                'settings' => $_POST,
                'errors'   => $errors,
            ]);
        }

        $this->saveSetting('seo_title', trim($_POST['seo_title'] ?? ''));
        $this->saveSetting('seo_description', trim($_POST['seo_description'] ?? ''));
        $this->saveSetting('seo_keywords', trim($_POST['seo_keywords'] ?? ''));
        $this->saveSetting('seo_og_image', trim($_POST['seo_og_image'] ?? ''));
        $this->saveSetting('seo_robots', trim($_POST['seo_robots'] ?? 'index, follow'));
        $this->saveSetting('seo_google_verification', trim($_POST['seo_google_verification'] ?? ''));
        $this->saveSetting('seo_bing_verification', trim($_POST['seo_bing_verification'] ?? ''));
        $this->saveSetting('seo_custom_head', trim($_POST['seo_custom_head'] ?? ''));

        return $this->redirect('/admin/settings/seo', ['success' => 'SEO settings saved.']);
    }

    /**
     * Theme and home page settings.
     */
    public function theme(): string
    {
        $settings = $this->loadSettings('theme_');

        return $this->view('admin/settings/theme', [
            'settings' => $settings,
        ]);
    }

    /**
     * Save theme and home page settings.
     */
    public function updateTheme(): string
    {
        $this->saveSetting('theme_primary_color', trim($_POST['theme_primary_color'] ?? '#3B82F6'));
        $this->saveSetting('theme_secondary_color', trim($_POST['theme_secondary_color'] ?? '#10B981'));
        $this->saveSetting('theme_font_family', trim($_POST['theme_font_family'] ?? 'Inter'));
        $this->saveSetting('theme_dark_mode', !empty($_POST['theme_dark_mode']) ? '1' : '0');
        $this->saveSetting('theme_hero_title', trim($_POST['theme_hero_title'] ?? ''));
        $this->saveSetting('theme_hero_subtitle', trim($_POST['theme_hero_subtitle'] ?? ''));
        $this->saveSetting('theme_hero_cta_text', trim($_POST['theme_hero_cta_text'] ?? ''));
        $this->saveSetting('theme_hero_cta_url', trim($_POST['theme_hero_cta_url'] ?? ''));
        $this->saveSetting('theme_hero_image', trim($_POST['theme_hero_image'] ?? ''));
        $this->saveSetting('theme_show_pricing', !empty($_POST['theme_show_pricing']) ? '1' : '0');
        $this->saveSetting('theme_show_testimonials', !empty($_POST['theme_show_testimonials']) ? '1' : '0');
        $this->saveSetting('theme_custom_css', trim($_POST['theme_custom_css'] ?? ''));
        $this->saveSetting('theme_custom_js', trim($_POST['theme_custom_js'] ?? ''));

        return $this->redirect('/admin/settings/theme', ['success' => 'Theme settings saved.']);
    }

    /**
     * AI configuration page.
     */
    public function ai(): string
    {
        $settings = $this->loadSettings('ai_');

        return $this->view('admin/settings/ai', [
            'settings' => $settings,
        ]);
    }

    /**
     * Save AI configuration.
     */
    public function updateAi(): string
    {
        $errors = $this->validate($_POST, [
            'ai_provider'          => 'required|in:openai,anthropic,google,disabled',
            'ai_model'             => 'nullable|string|max:100',
            'ai_max_tokens'        => 'nullable|integer|min:100|max:100000',
            'ai_temperature'       => 'nullable|numeric|min:0|max:2',
            'ai_monthly_limit'     => 'nullable|integer|min:0',
        ]);

        if (!empty($errors)) {
            return $this->view('admin/settings/ai', [
                'settings' => $_POST,
                'errors'   => $errors,
            ]);
        }

        $this->saveSetting('ai_provider', trim($_POST['ai_provider']));
        $this->saveSetting('ai_api_key', trim($_POST['ai_api_key'] ?? ''));
        $this->saveSetting('ai_model', trim($_POST['ai_model'] ?? ''));
        $this->saveSetting('ai_max_tokens', $_POST['ai_max_tokens'] ?? '4096');
        $this->saveSetting('ai_temperature', $_POST['ai_temperature'] ?? '0.7');
        $this->saveSetting('ai_monthly_limit', $_POST['ai_monthly_limit'] ?? '0');
        $this->saveSetting('ai_enabled_for_clients', !empty($_POST['ai_enabled_for_clients']) ? '1' : '0');
        $this->saveSetting('ai_form_generation', !empty($_POST['ai_form_generation']) ? '1' : '0');
        $this->saveSetting('ai_entry_analysis', !empty($_POST['ai_entry_analysis']) ? '1' : '0');

        return $this->redirect('/admin/settings/ai', ['success' => 'AI configuration saved.']);
    }

    /**
     * API settings page.
     */
    public function api(): string
    {
        $settings = $this->loadSettings('api_');

        return $this->view('admin/settings/api', [
            'settings' => $settings,
        ]);
    }

    /**
     * Save API settings.
     */
    public function updateApi(): string
    {
        $errors = $this->validate($_POST, [
            'api_rate_limit'        => 'required|integer|min:1|max:10000',
            'api_rate_window'       => 'required|integer|min:1|max:3600',
            'api_key_expiry_days'   => 'nullable|integer|min:0',
        ]);

        if (!empty($errors)) {
            return $this->view('admin/settings/api', [
                'settings' => $_POST,
                'errors'   => $errors,
            ]);
        }

        $this->saveSetting('api_enabled', !empty($_POST['api_enabled']) ? '1' : '0');
        $this->saveSetting('api_rate_limit', $_POST['api_rate_limit']);
        $this->saveSetting('api_rate_window', $_POST['api_rate_window']);
        $this->saveSetting('api_key_expiry_days', $_POST['api_key_expiry_days'] ?? '0');
        $this->saveSetting('api_cors_origins', trim($_POST['api_cors_origins'] ?? '*'));
        $this->saveSetting('api_webhook_timeout', $_POST['api_webhook_timeout'] ?? '30');
        $this->saveSetting('api_log_requests', !empty($_POST['api_log_requests']) ? '1' : '0');

        return $this->redirect('/admin/settings/api', ['success' => 'API settings saved.']);
    }

    /**
     * Development mode settings page.
     */
    public function dev(): string
    {
        $settings = $this->loadSettings('dev_');

        // Also include APP_DEBUG and APP_ENV
        $settings['app_debug'] = $this->getSetting('app_debug', $_ENV['APP_DEBUG'] ?? '0');
        $settings['app_env']   = $this->getSetting('app_env', $_ENV['APP_ENV'] ?? 'production');

        return $this->view('admin/settings/dev', [
            'settings' => $settings,
        ]);
    }

    /**
     * Save development mode settings.
     */
    public function updateDev(): string
    {
        $this->saveSetting('app_debug', !empty($_POST['app_debug']) ? '1' : '0');
        $this->saveSetting('app_env', in_array($_POST['app_env'] ?? '', ['production', 'staging', 'development'], true)
            ? $_POST['app_env']
            : 'production');
        $this->saveSetting('dev_show_query_log', !empty($_POST['dev_show_query_log']) ? '1' : '0');
        $this->saveSetting('dev_show_route_debug', !empty($_POST['dev_show_route_debug']) ? '1' : '0');
        $this->saveSetting('dev_profiling_enabled', !empty($_POST['dev_profiling_enabled']) ? '1' : '0');
        $this->saveSetting('dev_log_level', in_array($_POST['dev_log_level'] ?? '', ['debug', 'info', 'warning', 'error', 'critical'], true)
            ? $_POST['dev_log_level']
            : 'error');
        $this->saveSetting('dev_error_reporting', !empty($_POST['dev_error_reporting']) ? '1' : '0');

        return $this->redirect('/admin/settings/dev', ['success' => 'Development settings saved.']);
    }

    /**
     * Site information page (name, logo, description, etc.).
     */
    public function site(): string
    {
        $settings = $this->loadSettings('site_');

        return $this->view('admin/settings/site', [
            'settings' => $settings,
        ]);
    }

    /**
     * Save site information.
     */
    public function updateSite(): string
    {
        $errors = $this->validate($_POST, [
            'site_name'  => 'required|string|max:255',
            'site_email' => 'required|email',
            'site_url'   => 'required|url',
        ]);

        if (!empty($errors)) {
            return $this->view('admin/settings/site', [
                'settings' => $_POST,
                'errors'   => $errors,
            ]);
        }

        $this->saveSetting('site_name', trim($_POST['site_name']));
        $this->saveSetting('site_tagline', trim($_POST['site_tagline'] ?? ''));
        $this->saveSetting('site_description', trim($_POST['site_description'] ?? ''));
        $this->saveSetting('site_email', trim($_POST['site_email']));
        $this->saveSetting('site_phone', trim($_POST['site_phone'] ?? ''));
        $this->saveSetting('site_url', trim($_POST['site_url']));
        $this->saveSetting('site_address', trim($_POST['site_address'] ?? ''));

        // Handle logo upload
        if (isset($_FILES['site_logo']) && $_FILES['site_logo']['error'] === UPLOAD_ERR_OK) {
            $allowed  = ['image/png', 'image/jpeg', 'image/svg+xml', 'image/webp'];
            $mimeType = mime_content_type($_FILES['site_logo']['tmp_name']);

            if (in_array($mimeType, $allowed, true)) {
                $ext       = pathinfo($_FILES['site_logo']['name'], PATHINFO_EXTENSION);
                $filename  = 'logo_' . time() . '.' . $ext;
                $uploadDir = ROOT_PATH . '/public/uploads/';

                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                move_uploaded_file($_FILES['site_logo']['tmp_name'], $uploadDir . $filename);
                $this->saveSetting('site_logo', '/uploads/' . $filename);
            }
        }

        // Handle favicon upload
        if (isset($_FILES['site_favicon']) && $_FILES['site_favicon']['error'] === UPLOAD_ERR_OK) {
            $allowed  = ['image/x-icon', 'image/png', 'image/svg+xml'];
            $mimeType = mime_content_type($_FILES['site_favicon']['tmp_name']);

            if (in_array($mimeType, $allowed, true)) {
                $ext       = pathinfo($_FILES['site_favicon']['name'], PATHINFO_EXTENSION);
                $filename  = 'favicon_' . time() . '.' . $ext;
                $uploadDir = ROOT_PATH . '/public/uploads/';

                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                move_uploaded_file($_FILES['site_favicon']['tmp_name'], $uploadDir . $filename);
                $this->saveSetting('site_favicon', '/uploads/' . $filename);
            }
        }

        $this->saveSetting('site_social_facebook', trim($_POST['site_social_facebook'] ?? ''));
        $this->saveSetting('site_social_twitter', trim($_POST['site_social_twitter'] ?? ''));
        $this->saveSetting('site_social_linkedin', trim($_POST['site_social_linkedin'] ?? ''));
        $this->saveSetting('site_social_github', trim($_POST['site_social_github'] ?? ''));
        $this->saveSetting('site_footer_text', trim($_POST['site_footer_text'] ?? ''));
        $this->saveSetting('site_terms_url', trim($_POST['site_terms_url'] ?? ''));
        $this->saveSetting('site_privacy_url', trim($_POST['site_privacy_url'] ?? ''));

        return $this->redirect('/admin/settings/site', ['success' => 'Site information saved.']);
    }

    // -------------------------------------------------------------------------
    // Settings helpers (key-value store in settings table)
    // -------------------------------------------------------------------------

    /**
     * Load all settings matching an optional prefix.
     */
    private function loadSettings(string $prefix = ''): array
    {
        $db = $this->db();

        if ($prefix !== '') {
            $stmt = $db->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE :prefix");
            $stmt->execute(['prefix' => $prefix . '%']);
        } else {
            $stmt = $db->query("SELECT setting_key, setting_value FROM settings");
        }

        $rows     = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $settings = [];

        foreach ($rows as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        return $settings;
    }

    /**
     * Get a single setting value with a default.
     */
    private function getSetting(string $key, string $default = ''): string
    {
        $stmt = $this->db()->prepare("SELECT setting_value FROM settings WHERE setting_key = :key LIMIT 1");
        $stmt->execute(['key' => $key]);
        $value = $stmt->fetchColumn();

        return $value !== false ? (string) $value : $default;
    }

    /**
     * Insert or update a setting by key.
     */
    private function saveSetting(string $key, string $value): void
    {
        $db   = $this->db();
        $stmt = $db->prepare(
            "INSERT INTO settings (setting_key, setting_value, updated_at)
             VALUES (:key, :value, NOW())
             ON DUPLICATE KEY UPDATE setting_value = :value2, updated_at = NOW()"
        );
        $stmt->execute([
            'key'    => $key,
            'value'  => $value,
            'value2' => $value,
        ]);
    }
}
