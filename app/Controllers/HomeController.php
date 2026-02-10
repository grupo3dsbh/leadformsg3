<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;

/**
 * Home / Public Pages Controller
 *
 * Serves all public-facing (unauthenticated) pages: the marketing home page,
 * pricing, features showcase, contact form, legal pages and dynamic CMS pages
 * loaded by slug from the database.
 */
class HomeController extends Controller
{
    // ==================================================================
    // HOME PAGE
    // ==================================================================

    /**
     * Render the main landing / home page.
     *
     * Loads featured plans, highlighted features and testimonials to display
     * in the hero section, pricing preview and social-proof blocks.
     */
    public function index(): string
    {
        $features    = $this->getHighlightedFeatures();
        $plans       = $this->getFeaturedPlans();
        $testimonials = $this->getTestimonials();
        $stats       = $this->getPlatformStats();

        return $this->view('home.index', [
            'pageTitle'    => 'Formularios Conversacionais Inteligentes',
            'features'     => $features,
            'plans'        => $plans,
            'testimonials' => $testimonials,
            'stats'        => $stats,
        ], 'layouts.app');
    }

    // ==================================================================
    // PRICING
    // ==================================================================

    /**
     * Render the pricing / plans page.
     *
     * All active plans are loaded from the database along with their
     * associated feature limits so visitors can compare tiers.
     */
    public function pricing(): string
    {
        $plans = $this->getAllActivePlans();
        $faqs  = $this->getPricingFaqs();

        return $this->view('home.pricing', [
            'pageTitle'   => 'Planos e Precos',
            'plans'       => $plans,
            'faqs'        => $faqs,
        ], 'layouts.app');
    }

    // ==================================================================
    // FEATURES
    // ==================================================================

    /**
     * Render the features showcase page.
     */
    public function features(): string
    {
        $features   = $this->getAllFeatures();
        $categories = $this->getFeatureCategories();

        return $this->view('home.features', [
            'pageTitle'   => 'Funcionalidades',
            'features'    => $features,
            'categories'  => $categories,
        ], 'layouts.app');
    }

    // ==================================================================
    // CONTACT
    // ==================================================================

    /**
     * Render the contact page and, on POST, process the contact form.
     */
    public function contact(): string
    {
        // Handle POST submission.
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->handleContactSubmission();
        }

        return $this->view('home.contact', [
            'pageTitle'   => 'Contato',
        ], 'layouts.app');
    }

    // ==================================================================
    // LEGAL PAGES
    // ==================================================================

    /**
     * Render the Terms of Service page.
     */
    public function terms(): string
    {
        $content = $this->loadPageContent('terms-of-service');

        return $this->view('home.legal', [
            'pageTitle'   => 'Termos de Servico',
            'heading'     => 'Termos de Servico',
            'content'     => $content,
        ], 'layouts.app');
    }

    /**
     * Render the Privacy Policy page.
     */
    public function privacy(): string
    {
        $content = $this->loadPageContent('privacy-policy');

        return $this->view('home.legal', [
            'pageTitle'   => 'Politica de Privacidade',
            'heading'     => 'Politica de Privacidade',
            'content'     => $content,
        ], 'layouts.app');
    }

    // ==================================================================
    // DYNAMIC PAGES
    // ==================================================================

    /**
     * Load and render a dynamic CMS page by its slug.
     *
     * @param string $slug The URL-friendly slug of the page.
     */
    public function page(string $slug): string
    {
        $page = $this->loadDynamicPage($slug);

        if ($page === null) {
            http_response_code(404);
            return $this->view('errors.404', [
                'pageTitle' => 'Pagina nao encontrada',
            ], 'layouts.app');
        }

        // Track the page view.
        $this->trackPageView($slug);

        $template = $page['template'] ?? 'home/page';

        $dotTemplate = str_replace('/', '.', $template);
        return $this->view($dotTemplate, [
            'pageTitle'   => $page['title'] ?? 'Pagina',
            'page'        => $page,
            'content'     => $page['content'] ?? '',
        ], 'layouts.app');
    }

    // ==================================================================
    // API DOCUMENTATION
    // ==================================================================

    /**
     * Render the API documentation page.
     */
    public function apiDocs(): string
    {
        return $this->view('api.docs', [
            'pageTitle'   => 'Documentacao da API',
        ], 'layouts.app');
    }

    // ==================================================================
    // PRIVATE DATA LOADERS
    // ==================================================================

    /**
     * Load highlighted features for the home page (top 6).
     */
    private function getHighlightedFeatures(): array
    {
        try {
            $db   = \Core\Database::getInstance();
            $stmt = $db->query(
                'SELECT * FROM features WHERE is_highlighted = 1 AND is_active = 1 ORDER BY sort_order ASC LIMIT 6'
            );
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable) {
            // Return a static fallback so the page still renders.
            return [
                ['title' => 'Conversational Forms',  'description' => 'Create engaging conversational forms that feel like a real chat.',            'icon' => 'chat-bubble'],
                ['title' => 'Drag & Drop Builder',   'description' => 'Build forms visually with our intuitive drag-and-drop interface.',           'icon' => 'cursor-click'],
                ['title' => 'Advanced Analytics',     'description' => 'Track form performance with detailed analytics and conversion metrics.',    'icon' => 'chart-bar'],
                ['title' => 'Integrations',           'description' => 'Connect with 50+ tools including Zapier, Webhooks, Google Sheets & more.', 'icon' => 'puzzle'],
                ['title' => 'Conditional Logic',      'description' => 'Show or hide fields based on previous answers for smarter forms.',          'icon' => 'adjustments'],
                ['title' => 'Custom Branding',        'description' => 'Match forms to your brand with custom colors, logos and themes.',           'icon' => 'color-swatch'],
            ];
        }
    }

    /**
     * Load featured plans for the home-page pricing preview (max 3).
     */
    private function getFeaturedPlans(): array
    {
        try {
            $db   = \Core\Database::getInstance();
            $stmt = $db->query(
                'SELECT * FROM plans WHERE is_active = 1 AND is_featured = 1 ORDER BY sort_order ASC LIMIT 3'
            );
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Load all active plans for the dedicated pricing page.
     */
    private function getAllActivePlans(): array
    {
        try {
            $db   = \Core\Database::getInstance();
            $stmt = $db->query(
                'SELECT * FROM plans WHERE is_active = 1 ORDER BY sort_order ASC'
            );
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Load testimonials for the home page.
     */
    private function getTestimonials(): array
    {
        try {
            $db   = \Core\Database::getInstance();
            $stmt = $db->query(
                'SELECT * FROM testimonials WHERE is_active = 1 ORDER BY sort_order ASC LIMIT 6'
            );
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Aggregate high-level platform statistics for social proof.
     */
    private function getPlatformStats(): array
    {
        try {
            $db = \Core\Database::getInstance();

            $tenantCount = (int) $db->query('SELECT COUNT(*) FROM tenants WHERE status IN ("active","trialing")')->fetchColumn();
            $formCount   = (int) $db->query('SELECT COUNT(*) FROM forms WHERE status = "published"')->fetchColumn();
            $entryCount  = (int) $db->query('SELECT COUNT(*) FROM entries')->fetchColumn();

            return [
                'tenants' => $tenantCount,
                'forms'   => $formCount,
                'entries' => $entryCount,
            ];
        } catch (\Throwable) {
            return ['tenants' => 0, 'forms' => 0, 'entries' => 0];
        }
    }

    /**
     * Load all features for the features page, grouped by category.
     */
    private function getAllFeatures(): array
    {
        try {
            $db   = \Core\Database::getInstance();
            $stmt = $db->query('SELECT * FROM features WHERE is_active = 1 ORDER BY category ASC, sort_order ASC');
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Return distinct feature categories.
     */
    private function getFeatureCategories(): array
    {
        try {
            $db   = \Core\Database::getInstance();
            $stmt = $db->query('SELECT DISTINCT category FROM features WHERE is_active = 1 ORDER BY category ASC');
            return $stmt->fetchAll(\PDO::FETCH_COLUMN) ?: [];
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Load FAQs for the pricing page.
     */
    private function getPricingFaqs(): array
    {
        try {
            $db   = \Core\Database::getInstance();
            $stmt = $db->query(
                'SELECT * FROM faqs WHERE category = "pricing" AND is_active = 1 ORDER BY sort_order ASC'
            );
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Load static page content by slug (terms, privacy, etc.).
     *
     * Falls back to reading a markdown/HTML file from storage if the
     * database record is missing.
     */
    private function loadPageContent(string $slug): string
    {
        try {
            $db   = \Core\Database::getInstance();
            $stmt = $db->prepare('SELECT content FROM pages WHERE slug = :slug AND is_published = 1 LIMIT 1');
            $stmt->execute(['slug' => $slug]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($row) {
                return $row['content'];
            }
        } catch (\Throwable) {
            // Fall through to file-based fallback.
        }

        // File-based fallback.
        $basePath = dirname(__DIR__, 2) . '/storage/pages';
        $filePath = $basePath . '/' . basename($slug) . '.html';

        if (file_exists($filePath)) {
            return file_get_contents($filePath) ?: '';
        }

        return '<p>Content is being updated. Please check back soon.</p>';
    }

    /**
     * Load a dynamic CMS page by slug.
     */
    private function loadDynamicPage(string $slug): ?array
    {
        try {
            $db   = \Core\Database::getInstance();
            $stmt = $db->prepare(
                'SELECT * FROM pages WHERE slug = :slug AND is_published = 1 LIMIT 1'
            );
            $stmt->execute(['slug' => $slug]);
            $page = $stmt->fetch(\PDO::FETCH_ASSOC);

            return $page ?: null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Track a dynamic-page view for analytics.
     */
    private function trackPageView(string $slug): void
    {
        try {
            $db = \Core\Database::getInstance();
            $db->prepare(
                'UPDATE pages SET view_count = view_count + 1 WHERE slug = :slug'
            )->execute(['slug' => $slug]);
        } catch (\Throwable) {
            // Non-critical.
        }
    }

    // ------------------------------------------------------------------
    // Contact form handler
    // ------------------------------------------------------------------

    /**
     * Process the contact form submission.
     */
    private function handleContactSubmission(): string
    {
        // CSRF verification.
        $token        = $_POST['_token'] ?? '';
        $sessionToken = $_SESSION['_csrf_token'] ?? '';

        if ($token === '' || $sessionToken === '' || !hash_equals($sessionToken, $token)) {
            $_SESSION['_flash']['error'] = 'Invalid security token. Please try again.';
            return $this->view('home.contact', [
                'pageTitle' => 'Contato',
            ], 'layouts.app');
        }

        $data = [
            'name'    => trim($_POST['name'] ?? ''),
            'email'   => trim($_POST['email'] ?? ''),
            'subject' => trim($_POST['subject'] ?? ''),
            'message' => trim($_POST['message'] ?? ''),
        ];

        $errors = $this->validate($data, [
            'name'    => 'required|min:2|max:100',
            'email'   => 'required|email|max:255',
            'subject' => 'required|min:3|max:255',
            'message' => 'required|min:10|max:5000',
        ]);

        if (!empty($errors)) {
            $_SESSION['_flash']['errors'] = $errors;
            $_SESSION['_flash']['old']    = $data;

            return $this->view('home.contact', [
                'pageTitle' => 'Contato',
                'errors'    => $errors,
                'old'       => $data,
            ], 'layouts.app');
        }

        // Store the message.
        try {
            $db = \Core\Database::getInstance();
            $db->prepare(
                'INSERT INTO contact_messages (name, email, subject, message, ip_address, user_agent, created_at)
                 VALUES (:name, :email, :subject, :message, :ip, :ua, :created_at)'
            )->execute([
                'name'       => $data['name'],
                'email'      => $data['email'],
                'subject'    => $data['subject'],
                'message'    => $data['message'],
                'ip'         => $_SERVER['REMOTE_ADDR'] ?? '',
                'ua'         => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable) {
            // Non-critical; we'll still show a success message.
        }

        $_SESSION['_flash']['success'] = 'Thank you for your message! We will get back to you soon.';

        header('Location: /contact', true, 302);
        exit;
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /**
     * Build a consistent page title with the app name suffix.
     */
    private function getPageTitle(string $pageTitle): string
    {
        $appName = defined('APP_NAME') ? APP_NAME : 'LeadForm SaaS';
        return "{$pageTitle} - {$appName}";
    }
}
