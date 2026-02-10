<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use Core\Controller;

/**
 * Super Admin Translations Controller
 *
 * Manages i18n translation strings across all supported locales.
 * Translations are stored in JSON files under storage/lang/{locale}/{group}.json.
 */
class TranslationsController extends Controller
{
    private string $langPath;

    public function __construct()
    {
        parent::__construct();
        $this->langPath = ROOT_PATH . '/storage/lang';
    }

    /**
     * List all available locales and translation groups.
     */
    public function index(): string
    {
        $locales = $this->getLocales();
        $groups  = $this->getGroups();

        // Build a matrix: locale => group => key count
        $matrix = [];
        foreach ($locales as $locale) {
            $matrix[$locale] = [];
            foreach ($groups as $group) {
                $translations = $this->loadTranslations($locale, $group);
                $matrix[$locale][$group] = count($translations);
            }
        }

        // Get the default locale for completeness checking
        $db = $this->db();
        $defaultLocaleStmt = $db->prepare(
            "SELECT `value` FROM system_settings WHERE `key` = 'default_locale' LIMIT 1"
        );
        $defaultLocaleStmt->execute();
        $defaultLocale = $defaultLocaleStmt->fetchColumn() ?: 'en';

        // Calculate completeness for each locale relative to default
        $completeness = [];
        foreach ($locales as $locale) {
            if ($locale === $defaultLocale) {
                $completeness[$locale] = 100.0;
                continue;
            }

            $totalKeys     = 0;
            $translatedKeys = 0;

            foreach ($groups as $group) {
                $defaultTranslations = $this->loadTranslations($defaultLocale, $group);
                $localeTranslations  = $this->loadTranslations($locale, $group);

                $totalKeys += count($defaultTranslations);
                foreach (array_keys($defaultTranslations) as $key) {
                    if (isset($localeTranslations[$key]) && $localeTranslations[$key] !== '') {
                        $translatedKeys++;
                    }
                }
            }

            $completeness[$locale] = $totalKeys > 0
                ? round(($translatedKeys / $totalKeys) * 100, 1)
                : 0.0;
        }

        return $this->view('admin/translations/index', [
            'locales'       => $locales,
            'groups'        => $groups,
            'matrix'        => $matrix,
            'defaultLocale' => $defaultLocale,
            'completeness'  => $completeness,
        ], 'layouts.admin');
    }

    /**
     * Edit translations for a specific locale and group.
     */
    public function edit(string $locale, string $group): string
    {
        if (!$this->isValidLocale($locale) || !$this->isValidGroup($group)) {
            return $this->redirect('/admin/translations', ['error' => 'Invalid locale or group.']);
        }

        $translations = $this->loadTranslations($locale, $group);

        // Load the default locale translations as reference
        $db = $this->db();
        $defaultLocaleStmt = $db->prepare(
            "SELECT `value` FROM system_settings WHERE `key` = 'default_locale' LIMIT 1"
        );
        $defaultLocaleStmt->execute();
        $defaultLocale = $defaultLocaleStmt->fetchColumn() ?: 'en';

        $defaultTranslations = ($locale !== $defaultLocale)
            ? $this->loadTranslations($defaultLocale, $group)
            : [];

        // Merge keys: ensure all default keys are present
        $allKeys = array_unique(array_merge(
            array_keys($defaultTranslations),
            array_keys($translations)
        ));
        sort($allKeys);

        $combined = [];
        foreach ($allKeys as $key) {
            $combined[$key] = [
                'default' => $defaultTranslations[$key] ?? '',
                'value'   => $translations[$key] ?? '',
            ];
        }

        return $this->view('admin/translations/edit', [
            'locale'       => $locale,
            'group'        => $group,
            'translations' => $combined,
            'defaultLocale' => $defaultLocale,
        ], 'layouts.admin');
    }

    /**
     * Save updated translations for a locale and group.
     */
    public function update(): string
    {
        $locale = $_POST['locale'] ?? '';
        $group  = $_POST['group'] ?? '';

        if (!$this->isValidLocale($locale) || !$this->isValidGroup($group)) {
            return $this->redirect('/admin/translations', ['error' => 'Invalid locale or group.']);
        }

        $translations = $_POST['translations'] ?? [];

        if (!is_array($translations)) {
            return $this->redirect("/admin/translations/{$locale}/{$group}", [
                'error' => 'Invalid translation data.',
            ]);
        }

        // Clean up empty strings and trim values
        $cleaned = [];
        foreach ($translations as $key => $value) {
            $key   = trim((string) $key);
            $value = trim((string) $value);

            if ($key !== '') {
                $cleaned[$key] = $value;
            }
        }

        // Handle new keys added by the admin
        if (!empty($_POST['new_keys'])) {
            $newKeys   = (array) ($_POST['new_keys'] ?? []);
            $newValues = (array) ($_POST['new_values'] ?? []);

            foreach ($newKeys as $i => $newKey) {
                $newKey   = trim((string) $newKey);
                $newValue = trim((string) ($newValues[$i] ?? ''));

                if ($newKey !== '') {
                    $cleaned[$newKey] = $newValue;
                }
            }
        }

        ksort($cleaned);

        $this->saveTranslations($locale, $group, $cleaned);

        return $this->redirect("/admin/translations/{$locale}/{$group}", [
            'success' => 'Translations saved successfully.',
        ]);
    }

    /**
     * Export translations for a locale as a downloadable JSON file.
     */
    public function export(string $locale): void
    {
        if (!$this->isValidLocale($locale)) {
            header('HTTP/1.1 404 Not Found');
            echo 'Locale not found.';
            exit;
        }

        $groups      = $this->getGroups();
        $allData     = [];

        foreach ($groups as $group) {
            $allData[$group] = $this->loadTranslations($locale, $group);
        }

        $filename = "translations_{$locale}_" . date('Y-m-d') . '.json';

        header('Content-Type: application/json; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");

        echo json_encode($allData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Import translations from an uploaded JSON file.
     */
    public function import(): string
    {
        $locale = $_POST['locale'] ?? '';

        if (!$this->isValidLocale($locale)) {
            return $this->redirect('/admin/translations', ['error' => 'Invalid locale.']);
        }

        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            return $this->redirect('/admin/translations', ['error' => 'No file uploaded or upload error.']);
        }

        $mimeType = mime_content_type($_FILES['file']['tmp_name']);

        if (!in_array($mimeType, ['application/json', 'text/plain'], true)) {
            return $this->redirect('/admin/translations', ['error' => 'Only JSON files are accepted.']);
        }

        $content = file_get_contents($_FILES['file']['tmp_name']);
        $data    = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->redirect('/admin/translations', [
                'error' => 'Invalid JSON file: ' . json_last_error_msg(),
            ]);
        }

        $mode          = $_POST['import_mode'] ?? 'merge'; // merge or overwrite
        $importedCount = 0;

        foreach ($data as $group => $translations) {
            if (!is_array($translations) || !$this->isValidGroup((string) $group)) {
                continue;
            }

            if ($mode === 'merge') {
                $existing     = $this->loadTranslations($locale, (string) $group);
                $translations = array_merge($existing, $translations);
            }

            // Clean and sort
            $cleaned = [];
            foreach ($translations as $key => $value) {
                $cleaned[trim((string) $key)] = trim((string) $value);
            }
            ksort($cleaned);

            $this->saveTranslations($locale, (string) $group, $cleaned);
            $importedCount += count($cleaned);
        }

        return $this->redirect('/admin/translations', [
            'success' => "Imported {$importedCount} translation key(s) for locale \"{$locale}\".",
        ]);
    }

    // -------------------------------------------------------------------------
    // File I/O helpers
    // -------------------------------------------------------------------------

    /**
     * Load translations from a JSON file.
     */
    private function loadTranslations(string $locale, string $group): array
    {
        $filePath = "{$this->langPath}/{$locale}/{$group}.json";

        if (!file_exists($filePath)) {
            return [];
        }

        $content = file_get_contents($filePath);
        $decoded = json_decode($content, true);

        return (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : [];
    }

    /**
     * Save translations to a JSON file.
     */
    private function saveTranslations(string $locale, string $group, array $translations): void
    {
        $dir = "{$this->langPath}/{$locale}";

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filePath = "{$dir}/{$group}.json";

        file_put_contents(
            $filePath,
            json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n",
            LOCK_EX
        );
    }

    /**
     * Get all locale directories.
     */
    private function getLocales(): array
    {
        if (!is_dir($this->langPath)) {
            return ['en'];
        }

        $dirs    = scandir($this->langPath);
        $locales = [];

        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..') {
                continue;
            }
            if (is_dir("{$this->langPath}/{$dir}")) {
                $locales[] = $dir;
            }
        }

        sort($locales);

        return $locales ?: ['en'];
    }

    /**
     * Get all translation group names (JSON file basenames from any locale).
     */
    private function getGroups(): array
    {
        $groups = [];

        foreach ($this->getLocales() as $locale) {
            $dir = "{$this->langPath}/{$locale}";
            if (!is_dir($dir)) {
                continue;
            }

            $files = scandir($dir);
            foreach ($files as $file) {
                if (str_ends_with($file, '.json')) {
                    $groups[] = basename($file, '.json');
                }
            }
        }

        return array_unique($groups);
    }

    /**
     * Validate a locale string (basic sanity check).
     */
    private function isValidLocale(string $locale): bool
    {
        return (bool) preg_match('/^[a-z]{2}(_[A-Z]{2})?$/', $locale);
    }

    /**
     * Validate a group name (alphanumeric, hyphens, underscores).
     */
    private function isValidGroup(string $group): bool
    {
        return (bool) preg_match('/^[a-zA-Z0-9_-]+$/', $group);
    }
}
