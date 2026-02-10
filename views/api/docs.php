<?php
/**
 * API Documentation Page
 *
 * Rendered inside layouts/app.php.
 * Available variables:
 *   $user   - null if not logged in, user array if logged in
 *   $apiKey - real API key if logged in, placeholder string if not
 */

$apiKey   = $apiKey ?? 'your-api-key-here';
$baseUrl  = rtrim((isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'https') . '://' . ($_SERVER['HTTP_HOST'] ?? 'api.leadform.com'), '/');
$email    = $user['email'] ?? 'user@example.com';
$isAuth   = !empty($user);
?>

<style>
/* ============================================
   API DOCS - PAGE LAYOUT
   ============================================ */
.apidocs-hero {
    padding: calc(var(--space-24) + 16px) 0 var(--space-12);
    background: linear-gradient(135deg, #f8f9ff 0%, #eef2ff 50%, #f5f3ff 100%);
    position: relative;
    overflow: hidden;
}

.apidocs-hero::before {
    content: '';
    position: absolute;
    top: -40%;
    right: -15%;
    width: 600px;
    height: 600px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(79,70,229,0.06) 0%, transparent 70%);
    pointer-events: none;
}

.apidocs-hero-inner {
    position: relative;
    z-index: 2;
    max-width: 720px;
}

.apidocs-hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 14px;
    background: var(--primary-100);
    color: var(--primary-700);
    border-radius: var(--radius-full);
    font-size: var(--font-size-xs);
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    margin-bottom: var(--space-4);
}

.apidocs-hero h1 {
    font-size: clamp(2rem, 4vw, 2.75rem);
    font-weight: 800;
    letter-spacing: -0.02em;
    margin-bottom: var(--space-4);
}

.apidocs-hero p {
    font-size: var(--font-size-lg);
    color: var(--gray-600);
    line-height: 1.7;
    max-width: 600px;
}

/* Two-column layout */
.apidocs-wrap {
    display: flex;
    gap: var(--space-10);
    max-width: var(--container-max);
    margin: 0 auto;
    padding: var(--space-10) var(--space-6) var(--space-24);
    align-items: flex-start;
}

/* ============================================
   SIDEBAR NAVIGATION
   ============================================ */
.apidocs-sidebar {
    width: 220px;
    min-width: 220px;
    position: sticky;
    top: calc(var(--header-height) + 24px);
    max-height: calc(100vh - var(--header-height) - 48px);
    overflow-y: auto;
    padding-right: var(--space-4);
    scrollbar-width: thin;
    scrollbar-color: var(--gray-200) transparent;
}

.apidocs-sidebar::-webkit-scrollbar {
    width: 4px;
}

.apidocs-sidebar::-webkit-scrollbar-track {
    background: transparent;
}

.apidocs-sidebar::-webkit-scrollbar-thumb {
    background: var(--gray-200);
    border-radius: 4px;
}

.apidocs-nav-group {
    margin-bottom: var(--space-6);
}

.apidocs-nav-label {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--gray-400);
    padding: 0 0 var(--space-2) 0;
    margin: 0;
}

.apidocs-nav-link {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    padding: 7px 12px;
    font-size: var(--font-size-sm);
    font-weight: 500;
    color: var(--gray-600);
    border-radius: var(--radius-md);
    transition: all var(--transition-fast);
    text-decoration: none;
    margin-bottom: 2px;
}

.apidocs-nav-link:hover {
    background: var(--primary-50);
    color: var(--primary);
}

.apidocs-nav-link.active {
    background: var(--primary-50);
    color: var(--primary-700);
    font-weight: 600;
}

.apidocs-nav-method {
    display: inline-block;
    font-size: 9px;
    font-weight: 700;
    font-family: var(--font-mono);
    letter-spacing: 0.03em;
    padding: 1px 5px;
    border-radius: 3px;
    min-width: 32px;
    text-align: center;
    flex-shrink: 0;
}

.apidocs-nav-method.get    { background: #D1FAE5; color: #065F46; }
.apidocs-nav-method.post   { background: #DBEAFE; color: #1E40AF; }
.apidocs-nav-method.put    { background: #FEF3C7; color: #92400E; }
.apidocs-nav-method.delete { background: #FEE2E2; color: #991B1B; }

/* ============================================
   MAIN CONTENT
   ============================================ */
.apidocs-content {
    flex: 1;
    min-width: 0;
}

/* Section */
.apidocs-section {
    margin-bottom: var(--space-16);
    scroll-margin-top: calc(var(--header-height) + 24px);
}

.apidocs-section-title {
    font-size: var(--font-size-2xl);
    font-weight: 800;
    color: var(--gray-900);
    margin-bottom: var(--space-2);
    letter-spacing: -0.01em;
    padding-bottom: var(--space-3);
    border-bottom: 2px solid var(--gray-100);
}

.apidocs-section-desc {
    font-size: var(--font-size-base);
    color: var(--gray-600);
    line-height: 1.7;
    margin-bottom: var(--space-6);
    margin-top: var(--space-3);
}

/* ============================================
   API KEY BOX
   ============================================ */
.apidocs-key-box {
    background: #fff;
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-xl);
    padding: var(--space-6);
    margin-bottom: var(--space-6);
}

.apidocs-key-label {
    font-size: var(--font-size-sm);
    font-weight: 600;
    color: var(--gray-700);
    margin-bottom: var(--space-2);
}

.apidocs-key-row {
    display: flex;
    align-items: center;
    gap: var(--space-3);
}

.apidocs-key-value {
    flex: 1;
    padding: 10px 14px;
    font-family: var(--font-mono);
    font-size: var(--font-size-sm);
    background: var(--gray-50);
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-lg);
    color: var(--gray-800);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    user-select: all;
}

.apidocs-key-note {
    font-size: var(--font-size-xs);
    color: var(--gray-400);
    margin-top: var(--space-2);
}

.apidocs-key-note a {
    font-weight: 600;
}

/* ============================================
   ENDPOINT CARD
   ============================================ */
.apidocs-endpoint {
    background: #fff;
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-xl);
    overflow: hidden;
    margin-bottom: var(--space-6);
    transition: box-shadow var(--transition-base);
    scroll-margin-top: calc(var(--header-height) + 24px);
}

.apidocs-endpoint:hover {
    box-shadow: var(--shadow-md);
}

.apidocs-endpoint.method-get    { border-left: 4px solid #10B981; }
.apidocs-endpoint.method-post   { border-left: 4px solid #3B82F6; }
.apidocs-endpoint.method-put    { border-left: 4px solid #F59E0B; }
.apidocs-endpoint.method-delete { border-left: 4px solid #EF4444; }

.apidocs-endpoint-header {
    padding: var(--space-5) var(--space-6);
    border-bottom: 1px solid var(--gray-100);
    display: flex;
    align-items: center;
    gap: var(--space-3);
    flex-wrap: wrap;
}

.apidocs-method-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 4px 12px;
    font-family: var(--font-mono);
    font-size: var(--font-size-xs);
    font-weight: 700;
    border-radius: var(--radius-full);
    letter-spacing: 0.03em;
    flex-shrink: 0;
}

.apidocs-method-badge.get    { background: #D1FAE5; color: #065F46; }
.apidocs-method-badge.post   { background: #DBEAFE; color: #1E40AF; }
.apidocs-method-badge.put    { background: #FEF3C7; color: #92400E; }
.apidocs-method-badge.delete { background: #FEE2E2; color: #991B1B; }

.apidocs-endpoint-url {
    font-family: var(--font-mono);
    font-size: var(--font-size-sm);
    font-weight: 600;
    color: var(--gray-800);
    word-break: break-all;
}

.apidocs-endpoint-url .url-param {
    color: var(--primary);
}

.apidocs-endpoint-body {
    padding: var(--space-6);
}

.apidocs-endpoint-desc {
    font-size: var(--font-size-sm);
    color: var(--gray-600);
    line-height: 1.7;
    margin-bottom: var(--space-5);
}

/* Parameters table */
.apidocs-params-title {
    font-size: var(--font-size-sm);
    font-weight: 700;
    color: var(--gray-800);
    margin-bottom: var(--space-3);
}

.apidocs-params {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: var(--space-5);
    font-size: var(--font-size-sm);
}

.apidocs-params th {
    text-align: left;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--gray-400);
    padding: 8px 12px;
    border-bottom: 2px solid var(--gray-100);
    background: var(--gray-50);
}

.apidocs-params td {
    padding: 10px 12px;
    border-bottom: 1px solid var(--gray-100);
    color: var(--gray-700);
    vertical-align: top;
}

.apidocs-params tr:last-child td {
    border-bottom: none;
}

.apidocs-params code {
    font-family: var(--font-mono);
    font-size: 12px;
    background: var(--gray-100);
    padding: 2px 6px;
    border-radius: 4px;
    color: var(--gray-800);
    font-weight: 600;
}

.apidocs-params .param-required {
    font-size: 10px;
    font-weight: 700;
    color: #991B1B;
    background: #FEE2E2;
    padding: 1px 6px;
    border-radius: 3px;
    text-transform: uppercase;
}

.apidocs-params .param-optional {
    font-size: 10px;
    font-weight: 600;
    color: var(--gray-400);
}

/* Code blocks */
.apidocs-code-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: var(--space-2);
}

.apidocs-code-label {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--gray-400);
}

.apidocs-code-wrap {
    position: relative;
    margin-bottom: var(--space-4);
}

.apidocs-code-block {
    background: #1e1e2e;
    border-radius: var(--radius-lg);
    padding: var(--space-5);
    overflow-x: auto;
    font-family: var(--font-mono);
    font-size: 13px;
    line-height: 1.65;
    color: #cdd6f4;
    tab-size: 2;
    scrollbar-width: thin;
    scrollbar-color: #45475a #1e1e2e;
}

.apidocs-code-block::-webkit-scrollbar {
    height: 6px;
}

.apidocs-code-block::-webkit-scrollbar-track {
    background: #1e1e2e;
}

.apidocs-code-block::-webkit-scrollbar-thumb {
    background: #45475a;
    border-radius: 3px;
}

.apidocs-code-block .c-keyword { color: #cba6f7; }
.apidocs-code-block .c-string  { color: #a6e3a1; }
.apidocs-code-block .c-url     { color: #89b4fa; }
.apidocs-code-block .c-flag    { color: #f9e2af; }
.apidocs-code-block .c-header  { color: #94e2d5; }
.apidocs-code-block .c-comment { color: #6c7086; font-style: italic; }
.apidocs-code-block .c-number  { color: #fab387; }
.apidocs-code-block .c-key     { color: #89b4fa; }
.apidocs-code-block .c-null    { color: #f38ba8; }
.apidocs-code-block .c-bool    { color: #fab387; }

.apidocs-copy-btn {
    position: absolute;
    top: 10px;
    right: 10px;
    padding: 5px 10px;
    font-size: 11px;
    font-weight: 600;
    font-family: var(--font-family);
    color: #a6adc8;
    background: rgba(69, 71, 90, 0.6);
    border: 1px solid rgba(108, 112, 134, 0.3);
    border-radius: var(--radius-sm);
    cursor: pointer;
    transition: all var(--transition-fast);
    z-index: 2;
}

.apidocs-copy-btn:hover {
    background: rgba(69, 71, 90, 0.9);
    color: #cdd6f4;
}

.apidocs-copy-btn.copied {
    color: #a6e3a1;
    border-color: rgba(166, 227, 161, 0.4);
}

/* Info box */
.apidocs-info {
    padding: var(--space-4) var(--space-5);
    border-radius: var(--radius-lg);
    font-size: var(--font-size-sm);
    line-height: 1.6;
    margin-bottom: var(--space-5);
    display: flex;
    align-items: flex-start;
    gap: var(--space-3);
}

.apidocs-info code {
    font-family: var(--font-mono);
    font-size: 12px;
    background: rgba(0,0,0,0.08);
    padding: 1px 5px;
    border-radius: 3px;
}

.apidocs-info.info    { background: #EFF6FF; border: 1px solid #BFDBFE; color: #1E40AF; }
.apidocs-info.warning { background: #FFFBEB; border: 1px solid #FDE68A; color: #92400E; }
.apidocs-info.success { background: #F0FDF4; border: 1px solid #BBF7D0; color: #166534; }

.apidocs-info-icon {
    flex-shrink: 0;
    font-size: 16px;
    margin-top: 1px;
}

/* Error codes table */
.apidocs-errors-table {
    width: 100%;
    border-collapse: collapse;
    font-size: var(--font-size-sm);
}

.apidocs-errors-table th {
    text-align: left;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--gray-400);
    padding: 10px 14px;
    border-bottom: 2px solid var(--gray-100);
    background: var(--gray-50);
}

.apidocs-errors-table td {
    padding: 12px 14px;
    border-bottom: 1px solid var(--gray-100);
    color: var(--gray-700);
}

.apidocs-errors-table tr:last-child td {
    border-bottom: none;
}

.apidocs-errors-table code {
    font-family: var(--font-mono);
    font-size: 12px;
    background: var(--gray-100);
    padding: 2px 6px;
    border-radius: 4px;
    color: var(--gray-800);
    font-weight: 600;
}

.apidocs-status-code {
    font-family: var(--font-mono);
    font-weight: 700;
    font-size: 13px;
}

.apidocs-status-code.s2xx { color: #059669; }
.apidocs-status-code.s4xx { color: #DC2626; }
.apidocs-status-code.s5xx { color: #7C3AED; }

/* Rate limit header table */
.apidocs-header-table {
    width: 100%;
    border-collapse: collapse;
    font-size: var(--font-size-sm);
    margin-bottom: var(--space-4);
}

.apidocs-header-table th {
    text-align: left;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--gray-400);
    padding: 8px 12px;
    border-bottom: 2px solid var(--gray-100);
    background: var(--gray-50);
}

.apidocs-header-table td {
    padding: 10px 12px;
    border-bottom: 1px solid var(--gray-100);
    color: var(--gray-700);
}

.apidocs-header-table tr:last-child td {
    border-bottom: none;
}

.apidocs-header-table code {
    font-family: var(--font-mono);
    font-size: 12px;
    background: var(--gray-100);
    padding: 2px 6px;
    border-radius: 4px;
    color: var(--gray-800);
    font-weight: 600;
}

/* ============================================
   RESPONSIVE
   ============================================ */
@media (max-width: 1024px) {
    .apidocs-sidebar {
        width: 190px;
        min-width: 190px;
    }
}

@media (max-width: 768px) {
    .apidocs-wrap {
        flex-direction: column;
        padding: var(--space-6) var(--space-4) var(--space-16);
    }

    .apidocs-sidebar {
        width: 100%;
        min-width: 100%;
        position: relative;
        top: 0;
        max-height: none;
        display: flex;
        flex-wrap: wrap;
        gap: var(--space-4);
        padding-right: 0;
        padding-bottom: var(--space-4);
        border-bottom: 1px solid var(--gray-200);
        margin-bottom: var(--space-4);
    }

    .apidocs-nav-group {
        margin-bottom: 0;
    }

    .apidocs-endpoint-header {
        flex-direction: column;
        align-items: flex-start;
        gap: var(--space-2);
    }

    .apidocs-params {
        display: block;
        overflow-x: auto;
    }

    .apidocs-key-row {
        flex-direction: column;
    }

    .apidocs-key-value {
        width: 100%;
    }
}
</style>

<!-- ============================================
     HERO SECTION
     ============================================ -->
<section class="apidocs-hero">
    <div class="container">
        <div class="apidocs-hero-inner">
            <div class="apidocs-hero-badge">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                REST API v1
            </div>
            <h1>API <span class="text-gradient">Documentation</span></h1>
            <p>
                Build powerful integrations with the LeadForm REST API.
                Manage forms, retrieve entries, configure webhooks, and automate your
                lead collection workflow programmatically.
            </p>
        </div>
    </div>
</section>

<!-- ============================================
     MAIN LAYOUT: SIDEBAR + CONTENT
     ============================================ -->
<div class="apidocs-wrap">

    <!-- ==========================================
         LEFT SIDEBAR NAVIGATION
         ========================================== -->
    <aside class="apidocs-sidebar" id="apidocsSidebar">
        <nav>
            <div class="apidocs-nav-group">
                <div class="apidocs-nav-label">Getting Started</div>
                <a href="#authentication" class="apidocs-nav-link">Authentication</a>
            </div>

            <div class="apidocs-nav-group">
                <div class="apidocs-nav-label">Forms</div>
                <a href="#list-forms" class="apidocs-nav-link">
                    <span class="apidocs-nav-method get">GET</span> List Forms
                </a>
                <a href="#create-form" class="apidocs-nav-link">
                    <span class="apidocs-nav-method post">POST</span> Create Form
                </a>
                <a href="#get-form" class="apidocs-nav-link">
                    <span class="apidocs-nav-method get">GET</span> Get Form
                </a>
                <a href="#update-form" class="apidocs-nav-link">
                    <span class="apidocs-nav-method put">PUT</span> Update Form
                </a>
                <a href="#delete-form" class="apidocs-nav-link">
                    <span class="apidocs-nav-method delete">DEL</span> Delete Form
                </a>
                <a href="#list-form-entries" class="apidocs-nav-link">
                    <span class="apidocs-nav-method get">GET</span> Form Entries
                </a>
            </div>

            <div class="apidocs-nav-group">
                <div class="apidocs-nav-label">Entries</div>
                <a href="#get-entry" class="apidocs-nav-link">
                    <span class="apidocs-nav-method get">GET</span> Get Entry
                </a>
                <a href="#create-entry" class="apidocs-nav-link">
                    <span class="apidocs-nav-method post">POST</span> Create Entry
                </a>
                <a href="#delete-entry" class="apidocs-nav-link">
                    <span class="apidocs-nav-method delete">DEL</span> Delete Entry
                </a>
            </div>

            <div class="apidocs-nav-group">
                <div class="apidocs-nav-label">Webhooks</div>
                <a href="#list-webhooks" class="apidocs-nav-link">
                    <span class="apidocs-nav-method get">GET</span> List Webhooks
                </a>
                <a href="#create-webhook" class="apidocs-nav-link">
                    <span class="apidocs-nav-method post">POST</span> Create Webhook
                </a>
                <a href="#update-webhook" class="apidocs-nav-link">
                    <span class="apidocs-nav-method put">PUT</span> Update Webhook
                </a>
                <a href="#delete-webhook" class="apidocs-nav-link">
                    <span class="apidocs-nav-method delete">DEL</span> Delete Webhook
                </a>
            </div>

            <div class="apidocs-nav-group">
                <div class="apidocs-nav-label">Account</div>
                <a href="#get-account" class="apidocs-nav-link">
                    <span class="apidocs-nav-method get">GET</span> Account Info
                </a>
                <a href="#get-usage" class="apidocs-nav-link">
                    <span class="apidocs-nav-method get">GET</span> Usage Stats
                </a>
            </div>

            <div class="apidocs-nav-group">
                <div class="apidocs-nav-label">Reference</div>
                <a href="#rate-limiting" class="apidocs-nav-link">Rate Limiting</a>
                <a href="#errors" class="apidocs-nav-link">Error Codes</a>
            </div>
        </nav>
    </aside>

    <!-- ==========================================
         RIGHT CONTENT
         ========================================== -->
    <div class="apidocs-content">

        <!-- ======================================
             AUTHENTICATION
             ====================================== -->
        <section class="apidocs-section" id="authentication">
            <h2 class="apidocs-section-title">Authentication</h2>
            <p class="apidocs-section-desc">
                The LeadForm API uses <strong>Bearer token</strong> authentication.
                Include your API key in the <code>Authorization</code> header of every request.
                All API requests must be made over HTTPS. Calls made over plain HTTP will be rejected.
            </p>

            <div class="apidocs-key-box">
                <div class="apidocs-key-label">Your API Key</div>
                <div class="apidocs-key-row">
                    <div class="apidocs-key-value" id="apiKeyDisplay"><?= e($apiKey) ?></div>
                    <button class="btn btn-outline btn-sm" onclick="copyToClipboard('apiKeyDisplay', this)" type="button">
                        Copy
                    </button>
                </div>
                <?php if (!$isAuth): ?>
                    <div class="apidocs-key-note">
                        <a href="/login">Log in</a> or <a href="/register">create an account</a> to see your real API key.
                    </div>
                <?php else: ?>
                    <div class="apidocs-key-note">
                        Keep this key secret. You can regenerate it from your <a href="/dashboard/settings">account settings</a>.
                    </div>
                <?php endif; ?>
            </div>

            <div class="apidocs-code-wrap">
                <div class="apidocs-code-header">
                    <span class="apidocs-code-label">Example Request Header</span>
                </div>
                <button class="apidocs-copy-btn" onclick="copyCodeBlock(this)" type="button">Copy</button>
                <pre class="apidocs-code-block"><span class="c-flag">Authorization:</span> <span class="c-string">Bearer <?= e($apiKey) ?></span>
<span class="c-flag">Content-Type:</span>  <span class="c-string">application/json</span>
<span class="c-flag">Accept:</span>        <span class="c-string">application/json</span></pre>
            </div>

            <div class="apidocs-info info">
                <span class="apidocs-info-icon">&#8505;</span>
                <div>
                    <strong>Base URL:</strong> All API endpoints are relative to
                    <code><?= e($baseUrl) ?>/api/v1/</code>
                </div>
            </div>
        </section>

        <!-- ======================================
             FORMS
             ====================================== -->
        <section class="apidocs-section" id="forms">
            <h2 class="apidocs-section-title">Forms</h2>
            <p class="apidocs-section-desc">
                Forms are the core resource in LeadForm. Use these endpoints to create,
                read, update, and delete forms, as well as retrieve their entries.
            </p>

            <!-- LIST FORMS -->
            <div class="apidocs-endpoint method-get" id="list-forms">
                <div class="apidocs-endpoint-header">
                    <span class="apidocs-method-badge get">GET</span>
                    <span class="apidocs-endpoint-url">/api/v1/forms</span>
                </div>
                <div class="apidocs-endpoint-body">
                    <p class="apidocs-endpoint-desc">
                        Returns a paginated list of all forms belonging to your account.
                        Results are ordered by creation date (newest first).
                    </p>

                    <div class="apidocs-params-title">Query Parameters</div>
                    <table class="apidocs-params">
                        <thead>
                            <tr><th>Name</th><th>Type</th><th>Required</th><th>Description</th></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>page</code></td>
                                <td>integer</td>
                                <td><span class="param-optional">optional</span></td>
                                <td>Page number for pagination. Default: <code>1</code></td>
                            </tr>
                            <tr>
                                <td><code>per_page</code></td>
                                <td>integer</td>
                                <td><span class="param-optional">optional</span></td>
                                <td>Items per page (1-100). Default: <code>20</code></td>
                            </tr>
                            <tr>
                                <td><code>status</code></td>
                                <td>string</td>
                                <td><span class="param-optional">optional</span></td>
                                <td>Filter by status: <code>active</code>, <code>draft</code>, <code>archived</code></td>
                            </tr>
                            <tr>
                                <td><code>search</code></td>
                                <td>string</td>
                                <td><span class="param-optional">optional</span></td>
                                <td>Search forms by name</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="apidocs-code-wrap">
                        <div class="apidocs-code-header">
                            <span class="apidocs-code-label">Example Request</span>
                        </div>
                        <button class="apidocs-copy-btn" onclick="copyCodeBlock(this)" type="button">Copy</button>
                        <pre class="apidocs-code-block"><span class="c-keyword">curl</span> <span class="c-flag">-X GET</span> <span class="c-url"><?= e($baseUrl) ?>/api/v1/forms?page=1&amp;per_page=10</span> \
  <span class="c-flag">-H</span> <span class="c-string">"Authorization: Bearer <?= e($apiKey) ?>"</span> \
  <span class="c-flag">-H</span> <span class="c-string">"Accept: application/json"</span></pre>
                    </div>

                    <div class="apidocs-code-wrap">
                        <div class="apidocs-code-header">
                            <span class="apidocs-code-label">Example Response</span>
                            <span class="apidocs-code-label" style="color: #10B981;">200 OK</span>
                        </div>
                        <button class="apidocs-copy-btn" onclick="copyCodeBlock(this)" type="button">Copy</button>
                        <pre class="apidocs-code-block">{
  <span class="c-key">"data"</span>: [
    {
      <span class="c-key">"id"</span>: <span class="c-number">1</span>,
      <span class="c-key">"name"</span>: <span class="c-string">"Contact Form"</span>,
      <span class="c-key">"slug"</span>: <span class="c-string">"contact-form"</span>,
      <span class="c-key">"status"</span>: <span class="c-string">"active"</span>,
      <span class="c-key">"entries_count"</span>: <span class="c-number">342</span>,
      <span class="c-key">"created_at"</span>: <span class="c-string">"2025-01-15T10:30:00Z"</span>,
      <span class="c-key">"updated_at"</span>: <span class="c-string">"2025-02-01T14:22:00Z"</span>
    }
  ],
  <span class="c-key">"meta"</span>: {
    <span class="c-key">"current_page"</span>: <span class="c-number">1</span>,
    <span class="c-key">"per_page"</span>: <span class="c-number">10</span>,
    <span class="c-key">"total"</span>: <span class="c-number">24</span>,
    <span class="c-key">"last_page"</span>: <span class="c-number">3</span>
  }
}</pre>
                    </div>
                </div>
            </div>

            <!-- CREATE FORM -->
            <div class="apidocs-endpoint method-post" id="create-form">
                <div class="apidocs-endpoint-header">
                    <span class="apidocs-method-badge post">POST</span>
                    <span class="apidocs-endpoint-url">/api/v1/forms</span>
                </div>
                <div class="apidocs-endpoint-body">
                    <p class="apidocs-endpoint-desc">
                        Creates a new form. The form will be in <code>draft</code> status by default.
                    </p>

                    <div class="apidocs-params-title">Body Parameters</div>
                    <table class="apidocs-params">
                        <thead>
                            <tr><th>Name</th><th>Type</th><th>Required</th><th>Description</th></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>name</code></td>
                                <td>string</td>
                                <td><span class="param-required">required</span></td>
                                <td>Form name (max 255 characters)</td>
                            </tr>
                            <tr>
                                <td><code>description</code></td>
                                <td>string</td>
                                <td><span class="param-optional">optional</span></td>
                                <td>Form description for internal reference</td>
                            </tr>
                            <tr>
                                <td><code>fields</code></td>
                                <td>array</td>
                                <td><span class="param-optional">optional</span></td>
                                <td>Array of field objects defining the form structure</td>
                            </tr>
                            <tr>
                                <td><code>settings</code></td>
                                <td>object</td>
                                <td><span class="param-optional">optional</span></td>
                                <td>Form settings (notifications, redirect URL, theme, etc.)</td>
                            </tr>
                            <tr>
                                <td><code>status</code></td>
                                <td>string</td>
                                <td><span class="param-optional">optional</span></td>
                                <td><code>draft</code> or <code>active</code>. Default: <code>draft</code></td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="apidocs-code-wrap">
                        <div class="apidocs-code-header">
                            <span class="apidocs-code-label">Example Request</span>
                        </div>
                        <button class="apidocs-copy-btn" onclick="copyCodeBlock(this)" type="button">Copy</button>
                        <pre class="apidocs-code-block"><span class="c-keyword">curl</span> <span class="c-flag">-X POST</span> <span class="c-url"><?= e($baseUrl) ?>/api/v1/forms</span> \
  <span class="c-flag">-H</span> <span class="c-string">"Authorization: Bearer <?= e($apiKey) ?>"</span> \
  <span class="c-flag">-H</span> <span class="c-string">"Content-Type: application/json"</span> \
  <span class="c-flag">-d</span> <span class="c-string">'{
    "name": "Newsletter Signup",
    "description": "Collect email subscribers",
    "fields": [
      {
        "type": "email",
        "label": "Your Email",
        "required": true,
        "placeholder": "name@company.com"
      },
      {
        "type": "text",
        "label": "Full Name",
        "required": true
      }
    ],
    "settings": {
      "redirect_url": "https://example.com/thank-you",
      "notification_email": "<?= e($email) ?>"
    },
    "status": "active"
  }'</span></pre>
                    </div>

                    <div class="apidocs-code-wrap">
                        <div class="apidocs-code-header">
                            <span class="apidocs-code-label">Example Response</span>
                            <span class="apidocs-code-label" style="color: #10B981;">201 Created</span>
                        </div>
                        <button class="apidocs-copy-btn" onclick="copyCodeBlock(this)" type="button">Copy</button>
                        <pre class="apidocs-code-block">{
  <span class="c-key">"data"</span>: {
    <span class="c-key">"id"</span>: <span class="c-number">25</span>,
    <span class="c-key">"name"</span>: <span class="c-string">"Newsletter Signup"</span>,
    <span class="c-key">"slug"</span>: <span class="c-string">"newsletter-signup"</span>,
    <span class="c-key">"status"</span>: <span class="c-string">"active"</span>,
    <span class="c-key">"fields"</span>: [
      {
        <span class="c-key">"id"</span>: <span class="c-string">"fld_a1b2c3"</span>,
        <span class="c-key">"type"</span>: <span class="c-string">"email"</span>,
        <span class="c-key">"label"</span>: <span class="c-string">"Your Email"</span>,
        <span class="c-key">"required"</span>: <span class="c-bool">true</span>,
        <span class="c-key">"placeholder"</span>: <span class="c-string">"name@company.com"</span>
      },
      {
        <span class="c-key">"id"</span>: <span class="c-string">"fld_d4e5f6"</span>,
        <span class="c-key">"type"</span>: <span class="c-string">"text"</span>,
        <span class="c-key">"label"</span>: <span class="c-string">"Full Name"</span>,
        <span class="c-key">"required"</span>: <span class="c-bool">true</span>
      }
    ],
    <span class="c-key">"settings"</span>: {
      <span class="c-key">"redirect_url"</span>: <span class="c-string">"https://example.com/thank-you"</span>,
      <span class="c-key">"notification_email"</span>: <span class="c-string">"<?= e($email) ?>"</span>
    },
    <span class="c-key">"public_url"</span>: <span class="c-string">"<?= e($baseUrl) ?>/f/newsletter-signup"</span>,
    <span class="c-key">"entries_count"</span>: <span class="c-number">0</span>,
    <span class="c-key">"created_at"</span>: <span class="c-string">"2025-03-10T09:15:00Z"</span>,
    <span class="c-key">"updated_at"</span>: <span class="c-string">"2025-03-10T09:15:00Z"</span>
  }
}</pre>
                    </div>
                </div>
            </div>

            <!-- GET FORM -->
            <div class="apidocs-endpoint method-get" id="get-form">
                <div class="apidocs-endpoint-header">
                    <span class="apidocs-method-badge get">GET</span>
                    <span class="apidocs-endpoint-url">/api/v1/forms/<span class="url-param">{id}</span></span>
                </div>
                <div class="apidocs-endpoint-body">
                    <p class="apidocs-endpoint-desc">
                        Retrieves the full details for a single form, including its fields and settings.
                    </p>

                    <div class="apidocs-params-title">Path Parameters</div>
                    <table class="apidocs-params">
                        <thead>
                            <tr><th>Name</th><th>Type</th><th>Required</th><th>Description</th></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>id</code></td>
                                <td>integer</td>
                                <td><span class="param-required">required</span></td>
                                <td>The form ID</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="apidocs-code-wrap">
                        <div class="apidocs-code-header">
                            <span class="apidocs-code-label">Example Request</span>
                        </div>
                        <button class="apidocs-copy-btn" onclick="copyCodeBlock(this)" type="button">Copy</button>
                        <pre class="apidocs-code-block"><span class="c-keyword">curl</span> <span class="c-flag">-X GET</span> <span class="c-url"><?= e($baseUrl) ?>/api/v1/forms/25</span> \
  <span class="c-flag">-H</span> <span class="c-string">"Authorization: Bearer <?= e($apiKey) ?>"</span> \
  <span class="c-flag">-H</span> <span class="c-string">"Accept: application/json"</span></pre>
                    </div>

                    <div class="apidocs-code-wrap">
                        <div class="apidocs-code-header">
                            <span class="apidocs-code-label">Example Response</span>
                            <span class="apidocs-code-label" style="color: #10B981;">200 OK</span>
                        </div>
                        <button class="apidocs-copy-btn" onclick="copyCodeBlock(this)" type="button">Copy</button>
                        <pre class="apidocs-code-block">{
  <span class="c-key">"data"</span>: {
    <span class="c-key">"id"</span>: <span class="c-number">25</span>,
    <span class="c-key">"name"</span>: <span class="c-string">"Newsletter Signup"</span>,
    <span class="c-key">"slug"</span>: <span class="c-string">"newsletter-signup"</span>,
    <span class="c-key">"status"</span>: <span class="c-string">"active"</span>,
    <span class="c-key">"fields"</span>: [ <span class="c-comment">/* ... field objects ... */</span> ],
    <span class="c-key">"settings"</span>: { <span class="c-comment">/* ... settings object ... */</span> },
    <span class="c-key">"public_url"</span>: <span class="c-string">"<?= e($baseUrl) ?>/f/newsletter-signup"</span>,
    <span class="c-key">"entries_count"</span>: <span class="c-number">142</span>,
    <span class="c-key">"created_at"</span>: <span class="c-string">"2025-03-10T09:15:00Z"</span>,
    <span class="c-key">"updated_at"</span>: <span class="c-string">"2025-03-12T16:40:00Z"</span>
  }
}</pre>
                    </div>
                </div>
            </div>

            <!-- UPDATE FORM -->
            <div class="apidocs-endpoint method-put" id="update-form">
                <div class="apidocs-endpoint-header">
                    <span class="apidocs-method-badge put">PUT</span>
                    <span class="apidocs-endpoint-url">/api/v1/forms/<span class="url-param">{id}</span></span>
                </div>
                <div class="apidocs-endpoint-body">
                    <p class="apidocs-endpoint-desc">
                        Updates an existing form. Only the fields you include in the request body
                        will be updated; omitted fields remain unchanged.
                    </p>

                    <div class="apidocs-params-title">Path Parameters</div>
                    <table class="apidocs-params">
                        <thead>
                            <tr><th>Name</th><th>Type</th><th>Required</th><th>Description</th></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>id</code></td>
                                <td>integer</td>
                                <td><span class="param-required">required</span></td>
                                <td>The form ID</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="apidocs-params-title">Body Parameters</div>
                    <table class="apidocs-params">
                        <thead>
                            <tr><th>Name</th><th>Type</th><th>Required</th><th>Description</th></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>name</code></td>
                                <td>string</td>
                                <td><span class="param-optional">optional</span></td>
                                <td>Updated form name</td>
                            </tr>
                            <tr>
                                <td><code>description</code></td>
                                <td>string</td>
                                <td><span class="param-optional">optional</span></td>
                                <td>Updated description</td>
                            </tr>
                            <tr>
                                <td><code>fields</code></td>
                                <td>array</td>
                                <td><span class="param-optional">optional</span></td>
                                <td>Replace entire fields array</td>
                            </tr>
                            <tr>
                                <td><code>settings</code></td>
                                <td>object</td>
                                <td><span class="param-optional">optional</span></td>
                                <td>Merge with existing settings</td>
                            </tr>
                            <tr>
                                <td><code>status</code></td>
                                <td>string</td>
                                <td><span class="param-optional">optional</span></td>
                                <td><code>draft</code>, <code>active</code>, or <code>archived</code></td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="apidocs-code-wrap">
                        <div class="apidocs-code-header">
                            <span class="apidocs-code-label">Example Request</span>
                        </div>
                        <button class="apidocs-copy-btn" onclick="copyCodeBlock(this)" type="button">Copy</button>
                        <pre class="apidocs-code-block"><span class="c-keyword">curl</span> <span class="c-flag">-X PUT</span> <span class="c-url"><?= e($baseUrl) ?>/api/v1/forms/25</span> \
  <span class="c-flag">-H</span> <span class="c-string">"Authorization: Bearer <?= e($apiKey) ?>"</span> \
  <span class="c-flag">-H</span> <span class="c-string">"Content-Type: application/json"</span> \
  <span class="c-flag">-d</span> <span class="c-string">'{
    "name": "Newsletter Signup v2",
    "status": "active"
  }'</span></pre>
                    </div>

                    <div class="apidocs-code-wrap">
                        <div class="apidocs-code-header">
                            <span class="apidocs-code-label">Example Response</span>
                            <span class="apidocs-code-label" style="color: #10B981;">200 OK</span>
                        </div>
                        <button class="apidocs-copy-btn" onclick="copyCodeBlock(this)" type="button">Copy</button>
                        <pre class="apidocs-code-block">{
  <span class="c-key">"data"</span>: {
    <span class="c-key">"id"</span>: <span class="c-number">25</span>,
    <span class="c-key">"name"</span>: <span class="c-string">"Newsletter Signup v2"</span>,
    <span class="c-key">"slug"</span>: <span class="c-string">"newsletter-signup"</span>,
    <span class="c-key">"status"</span>: <span class="c-string">"active"</span>,
    <span class="c-key">"updated_at"</span>: <span class="c-string">"2025-03-15T11:20:00Z"</span>
  }
}</pre>
                    </div>
                </div>
            </div>

            <!-- DELETE FORM -->
            <div class="apidocs-endpoint method-delete" id="delete-form">
                <div class="apidocs-endpoint-header">
                    <span class="apidocs-method-badge delete">DELETE</span>
                    <span class="apidocs-endpoint-url">/api/v1/forms/<span class="url-param">{id}</span></span>
                </div>
                <div class="apidocs-endpoint-body">
                    <p class="apidocs-endpoint-desc">
                        Permanently deletes a form and all its associated entries. This action cannot be undone.
                    </p>

                    <div class="apidocs-params-title">Path Parameters</div>
                    <table class="apidocs-params">
                        <thead>
                            <tr><th>Name</th><th>Type</th><th>Required</th><th>Description</th></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>id</code></td>
                                <td>integer</td>
                                <td><span class="param-required">required</span></td>
                                <td>The form ID</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="apidocs-info warning">
                        <span class="apidocs-info-icon">&#9888;</span>
                        <div>
                            <strong>Destructive action.</strong> Deleting a form also permanently removes
                            all of its entries. Consider archiving the form instead by setting
                            <code>status</code> to <code>archived</code>.
                        </div>
                    </div>

                    <div class="apidocs-code-wrap">
                        <div class="apidocs-code-header">
                            <span class="apidocs-code-label">Example Request</span>
                        </div>
                        <button class="apidocs-copy-btn" onclick="copyCodeBlock(this)" type="button">Copy</button>
                        <pre class="apidocs-code-block"><span class="c-keyword">curl</span> <span class="c-flag">-X DELETE</span> <span class="c-url"><?= e($baseUrl) ?>/api/v1/forms/25</span> \
  <span class="c-flag">-H</span> <span class="c-string">"Authorization: Bearer <?= e($apiKey) ?>"</span></pre>
                    </div>

                    <div class="apidocs-code-wrap">
                        <div class="apidocs-code-header">
                            <span class="apidocs-code-label">Example Response</span>
                            <span class="apidocs-code-label" style="color: #10B981;">200 OK</span>
                        </div>
                        <button class="apidocs-copy-btn" onclick="copyCodeBlock(this)" type="button">Copy</button>
                        <pre class="apidocs-code-block">{
  <span class="c-key">"message"</span>: <span class="c-string">"Form deleted successfully."</span>
}</pre>
                    </div>
                </div>
            </div>

            <!-- LIST FORM ENTRIES -->
            <div class="apidocs-endpoint method-get" id="list-form-entries">
                <div class="apidocs-endpoint-header">
                    <span class="apidocs-method-badge get">GET</span>
                    <span class="apidocs-endpoint-url">/api/v1/forms/<span class="url-param">{id}</span>/entries</span>
                </div>
                <div class="apidocs-endpoint-body">
                    <p class="apidocs-endpoint-desc">
                        Returns a paginated list of entries (submissions) for a specific form.
                    </p>

                    <div class="apidocs-params-title">Path Parameters</div>
                    <table class="apidocs-params">
                        <thead>
                            <tr><th>Name</th><th>Type</th><th>Required</th><th>Description</th></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>id</code></td>
                                <td>integer</td>
                                <td><span class="param-required">required</span></td>
                                <td>The form ID</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="apidocs-params-title">Query Parameters</div>
                    <table class="apidocs-params">
                        <thead>
                            <tr><th>Name</th><th>Type</th><th>Required</th><th>Description</th></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>page</code></td>
                                <td>integer</td>
                                <td><span class="param-optional">optional</span></td>
                                <td>Page number. Default: <code>1</code></td>
                            </tr>
                            <tr>
                                <td><code>per_page</code></td>
                                <td>integer</td>
                                <td><span class="param-optional">optional</span></td>
                                <td>Items per page (1-100). Default: <code>20</code></td>
                            </tr>
                            <tr>
                                <td><code>since</code></td>
                                <td>string</td>
                                <td><span class="param-optional">optional</span></td>
                                <td>ISO 8601 date. Return entries created after this date.</td>
                            </tr>
                            <tr>
                                <td><code>until</code></td>
                                <td>string</td>
                                <td><span class="param-optional">optional</span></td>
                                <td>ISO 8601 date. Return entries created before this date.</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="apidocs-code-wrap">
                        <div class="apidocs-code-header">
                            <span class="apidocs-code-label">Example Request</span>
                        </div>
                        <button class="apidocs-copy-btn" onclick="copyCodeBlock(this)" type="button">Copy</button>
                        <pre class="apidocs-code-block"><span class="c-keyword">curl</span> <span class="c-flag">-X GET</span> <span class="c-url"><?= e($baseUrl) ?>/api/v1/forms/25/entries?per_page=5</span> \
  <span class="c-flag">-H</span> <span class="c-string">"Authorization: Bearer <?= e($apiKey) ?>"</span> \
  <span class="c-flag">-H</span> <span class="c-string">"Accept: application/json"</span></pre>
                    </div>

                    <div class="apidocs-code-wrap">
                        <div class="apidocs-code-header">
                            <span class="apidocs-code-label">Example Response</span>
                            <span class="apidocs-code-label" style="color: #10B981;">200 OK</span>
                        </div>
                        <button class="apidocs-copy-btn" onclick="copyCodeBlock(this)" type="button">Copy</button>
                        <pre class="apidocs-code-block">{
  <span class="c-key">"data"</span>: [
    {
      <span class="c-key">"id"</span>: <span class="c-number">1087</span>,
      <span class="c-key">"form_id"</span>: <span class="c-number">25</span>,
      <span class="c-key">"fields"</span>: {
        <span class="c-key">"email"</span>: <span class="c-string">"jane@example.com"</span>,
        <span class="c-key">"full_name"</span>: <span class="c-string">"Jane Doe"</span>
      },
      <span class="c-key">"metadata"</span>: {
        <span class="c-key">"ip"</span>: <span class="c-string">"203.0.113.42"</span>,
        <span class="c-key">"user_agent"</span>: <span class="c-string">"Mozilla/5.0..."</span>,
        <span class="c-key">"referrer"</span>: <span class="c-string">"https://example.com/blog"</span>
      },
      <span class="c-key">"created_at"</span>: <span class="c-string">"2025-03-14T08:22:00Z"</span>
    }
  ],
  <span class="c-key">"meta"</span>: {
    <span class="c-key">"current_page"</span>: <span class="c-number">1</span>,
    <span class="c-key">"per_page"</span>: <span class="c-number">5</span>,
    <span class="c-key">"total"</span>: <span class="c-number">142</span>,
    <span class="c-key">"last_page"</span>: <span class="c-number">29</span>
  }
}</pre>
                    </div>
                </div>
            </div>
        </section>

        <!-- ======================================
             ENTRIES
             ====================================== -->
        <section class="apidocs-section" id="entries">
            <h2 class="apidocs-section-title">Entries</h2>
            <p class="apidocs-section-desc">
                Entries represent individual form submissions. Use these endpoints to
                retrieve, create, or delete submission data.
            </p>

            <!-- GET ENTRY -->
            <div class="apidocs-endpoint method-get" id="get-entry">
                <div class="apidocs-endpoint-header">
                    <span class="apidocs-method-badge get">GET</span>
                    <span class="apidocs-endpoint-url">/api/v1/entries/<span class="url-param">{id}</span></span>
                </div>
                <div class="apidocs-endpoint-body">
                    <p class="apidocs-endpoint-desc">
                        Retrieves the full details of a single entry, including all submitted field data and metadata.
                    </p>

                    <div class="apidocs-params-title">Path Parameters</div>
                    <table class="apidocs-params">
                        <thead>
                            <tr><th>Name</th><th>Type</th><th>Required</th><th>Description</th></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>id</code></td>
                                <td>integer</td>
                                <td><span class="param-required">required</span></td>
                                <td>The entry ID</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="apidocs-code-wrap">
                        <div class="apidocs-code-header">
                            <span class="apidocs-code-label">Example Request</span>
                        </div>
                        <button class="apidocs-copy-btn" onclick="copyCodeBlock(this)" type="button">Copy</button>
                        <pre class="apidocs-code-block"><span class="c-keyword">curl</span> <span class="c-flag">-X GET</span> <span class="c-url"><?= e($baseUrl) ?>/api/v1/entries/1087</span> \
  <span class="c-flag">-H</span> <span class="c-string">"Authorization: Bearer <?= e($apiKey) ?>"</span> \
  <span class="c-flag">-H</span> <span class="c-string">"Accept: application/json"</span></pre>
                    </div>

                    <div class="apidocs-code-wrap">
                        <div class="apidocs-code-header">
                            <span class="apidocs-code-label">Example Response</span>
                            <span class="apidocs-code-label" style="color: #10B981;">200 OK</span>
                        </div>
                        <button class="apidocs-copy-btn" onclick="copyCodeBlock(this)" type="button">Copy</button>
                        <pre class="apidocs-code-block">{
  <span class="c-key">"data"</span>: {
    <span class="c-key">"id"</span>: <span class="c-number">1087</span>,
    <span class="c-key">"form_id"</span>: <span class="c-number">25</span>,
    <span class="c-key">"form_name"</span>: <span class="c-string">"Newsletter Signup"</span>,
    <span class="c-key">"fields"</span>: {
      <span class="c-key">"email"</span>: <span class="c-string">"jane@example.com"</span>,
      <span class="c-key">"full_name"</span>: <span class="c-string">"Jane Doe"</span>
    },
    <span class="c-key">"metadata"</span>: {
      <span class="c-key">"ip"</span>: <span class="c-string">"203.0.113.42"</span>,
      <span class="c-key">"user_agent"</span>: <span class="c-string">"Mozilla/5.0..."</span>,
      <span class="c-key">"referrer"</span>: <span class="c-string">"https://example.com/blog"</span>,
      <span class="c-key">"country"</span>: <span class="c-string">"BR"</span>
    },
    <span class="c-key">"is_complete"</span>: <span class="c-bool">true</span>,
    <span class="c-key">"created_at"</span>: <span class="c-string">"2025-03-14T08:22:00Z"</span>
  }
}</pre>
                    </div>
                </div>
            </div>

            <!-- CREATE ENTRY -->
            <div class="apidocs-endpoint method-post" id="create-entry">
                <div class="apidocs-endpoint-header">
                    <span class="apidocs-method-badge post">POST</span>
                    <span class="apidocs-endpoint-url">/api/v1/entries</span>
                </div>
                <div class="apidocs-endpoint-body">
                    <p class="apidocs-endpoint-desc">
                        Programmatically creates a new entry for a specified form.
                        Useful for importing data from external sources or building custom submission flows.
                    </p>

                    <div class="apidocs-params-title">Body Parameters</div>
                    <table class="apidocs-params">
                        <thead>
                            <tr><th>Name</th><th>Type</th><th>Required</th><th>Description</th></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>form_id</code></td>
                                <td>integer</td>
                                <td><span class="param-required">required</span></td>
                                <td>The target form ID</td>
                            </tr>
                            <tr>
                                <td><code>fields</code></td>
                                <td>object</td>
                                <td><span class="param-required">required</span></td>
                                <td>Key-value pairs of field data (use field labels or IDs as keys)</td>
                            </tr>
                            <tr>
                                <td><code>metadata</code></td>
                                <td>object</td>
                                <td><span class="param-optional">optional</span></td>
                                <td>Custom metadata (IP, source, UTM tags, etc.)</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="apidocs-code-wrap">
                        <div class="apidocs-code-header">
                            <span class="apidocs-code-label">Example Request</span>
                        </div>
                        <button class="apidocs-copy-btn" onclick="copyCodeBlock(this)" type="button">Copy</button>
                        <pre class="apidocs-code-block"><span class="c-keyword">curl</span> <span class="c-flag">-X POST</span> <span class="c-url"><?= e($baseUrl) ?>/api/v1/entries</span> \
  <span class="c-flag">-H</span> <span class="c-string">"Authorization: Bearer <?= e($apiKey) ?>"</span> \
  <span class="c-flag">-H</span> <span class="c-string">"Content-Type: application/json"</span> \
  <span class="c-flag">-d</span> <span class="c-string">'{
    "form_id": 25,
    "fields": {
      "email": "john@example.com",
      "full_name": "John Smith"
    },
    "metadata": {
      "source": "csv_import",
      "utm_campaign": "spring_2025"
    }
  }'</span></pre>
                    </div>

                    <div class="apidocs-code-wrap">
                        <div class="apidocs-code-header">
                            <span class="apidocs-code-label">Example Response</span>
                            <span class="apidocs-code-label" style="color: #10B981;">201 Created</span>
                        </div>
                        <button class="apidocs-copy-btn" onclick="copyCodeBlock(this)" type="button">Copy</button>
                        <pre class="apidocs-code-block">{
  <span class="c-key">"data"</span>: {
    <span class="c-key">"id"</span>: <span class="c-number">1088</span>,
    <span class="c-key">"form_id"</span>: <span class="c-number">25</span>,
    <span class="c-key">"fields"</span>: {
      <span class="c-key">"email"</span>: <span class="c-string">"john@example.com"</span>,
      <span class="c-key">"full_name"</span>: <span class="c-string">"John Smith"</span>
    },
    <span class="c-key">"metadata"</span>: {
      <span class="c-key">"source"</span>: <span class="c-string">"csv_import"</span>,
      <span class="c-key">"utm_campaign"</span>: <span class="c-string">"spring_2025"</span>
    },
    <span class="c-key">"is_complete"</span>: <span class="c-bool">true</span>,
    <span class="c-key">"created_at"</span>: <span class="c-string">"2025-03-15T12:00:00Z"</span>
  }
}</pre>
                    </div>
                </div>
            </div>

            <!-- DELETE ENTRY -->
            <div class="apidocs-endpoint method-delete" id="delete-entry">
                <div class="apidocs-endpoint-header">
                    <span class="apidocs-method-badge delete">DELETE</span>
                    <span class="apidocs-endpoint-url">/api/v1/entries/<span class="url-param">{id}</span></span>
                </div>
                <div class="apidocs-endpoint-body">
                    <p class="apidocs-endpoint-desc">
                        Permanently deletes an entry. This action cannot be undone.
                    </p>

                    <div class="apidocs-params-title">Path Parameters</div>
                    <table class="apidocs-params">
                        <thead>
                            <tr><th>Name</th><th>Type</th><th>Required</th><th>Description</th></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>id</code></td>
                                <td>integer</td>
                                <td><span class="param-required">required</span></td>
                                <td>The entry ID</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="apidocs-code-wrap">
                        <div class="apidocs-code-header">
                            <span class="apidocs-code-label">Example Request</span>
                        </div>
                        <button class="apidocs-copy-btn" onclick="copyCodeBlock(this)" type="button">Copy</button>
                        <pre class="apidocs-code-block"><span class="c-keyword">curl</span> <span class="c-flag">-X DELETE</span> <span class="c-url"><?= e($baseUrl) ?>/api/v1/entries/1087</span> \
  <span class="c-flag">-H</span> <span class="c-string">"Authorization: Bearer <?= e($apiKey) ?>"</span></pre>
                    </div>

                    <div class="apidocs-code-wrap">
                        <div class="apidocs-code-header">
                            <span class="apidocs-code-label">Example Response</span>
                            <span class="apidocs-code-label" style="color: #10B981;">200 OK</span>
                        </div>
                        <button class="apidocs-copy-btn" onclick="copyCodeBlock(this)" type="button">Copy</button>
                        <pre class="apidocs-code-block">{
  <span class="c-key">"message"</span>: <span class="c-string">"Entry deleted successfully."</span>
}</pre>
                    </div>
                </div>
            </div>
        </section>

        <!-- ======================================
             WEBHOOKS
             ====================================== -->
        <section class="apidocs-section" id="webhooks">
            <h2 class="apidocs-section-title">Webhooks</h2>
            <p class="apidocs-section-desc">
                Webhooks allow you to receive real-time HTTP POST notifications when events occur
                in your account, such as new form submissions. Configure target URLs and choose
                which events to subscribe to.
            </p>

            <!-- LIST WEBHOOKS -->
            <div class="apidocs-endpoint method-get" id="list-webhooks">
                <div class="apidocs-endpoint-header">
                    <span class="apidocs-method-badge get">GET</span>
                    <span class="apidocs-endpoint-url">/api/v1/webhooks</span>
                </div>
                <div class="apidocs-endpoint-body">
                    <p class="apidocs-endpoint-desc">
                        Returns all webhooks configured for your account.
                    </p>

                    <div class="apidocs-code-wrap">
                        <div class="apidocs-code-header">
                            <span class="apidocs-code-label">Example Request</span>
                        </div>
                        <button class="apidocs-copy-btn" onclick="copyCodeBlock(this)" type="button">Copy</button>
                        <pre class="apidocs-code-block"><span class="c-keyword">curl</span> <span class="c-flag">-X GET</span> <span class="c-url"><?= e($baseUrl) ?>/api/v1/webhooks</span> \
  <span class="c-flag">-H</span> <span class="c-string">"Authorization: Bearer <?= e($apiKey) ?>"</span> \
  <span class="c-flag">-H</span> <span class="c-string">"Accept: application/json"</span></pre>
                    </div>

                    <div class="apidocs-code-wrap">
                        <div class="apidocs-code-header">
                            <span class="apidocs-code-label">Example Response</span>
                            <span class="apidocs-code-label" style="color: #10B981;">200 OK</span>
                        </div>
                        <button class="apidocs-copy-btn" onclick="copyCodeBlock(this)" type="button">Copy</button>
                        <pre class="apidocs-code-block">{
  <span class="c-key">"data"</span>: [
    {
      <span class="c-key">"id"</span>: <span class="c-number">12</span>,
      <span class="c-key">"url"</span>: <span class="c-string">"https://example.com/hooks/leadform"</span>,
      <span class="c-key">"events"</span>: [<span class="c-string">"entry.created"</span>, <span class="c-string">"entry.updated"</span>],
      <span class="c-key">"form_id"</span>: <span class="c-null">null</span>,
      <span class="c-key">"is_active"</span>: <span class="c-bool">true</span>,
      <span class="c-key">"secret"</span>: <span class="c-string">"whsec_a1b2c3d4e5..."</span>,
      <span class="c-key">"created_at"</span>: <span class="c-string">"2025-02-20T10:00:00Z"</span>
    }
  ]
}</pre>
                    </div>
                </div>
            </div>

            <!-- CREATE WEBHOOK -->
            <div class="apidocs-endpoint method-post" id="create-webhook">
                <div class="apidocs-endpoint-header">
                    <span class="apidocs-method-badge post">POST</span>
                    <span class="apidocs-endpoint-url">/api/v1/webhooks</span>
                </div>
                <div class="apidocs-endpoint-body">
                    <p class="apidocs-endpoint-desc">
                        Creates a new webhook subscription. You can scope a webhook to a specific form
                        or listen to events across all forms.
                    </p>

                    <div class="apidocs-params-title">Body Parameters</div>
                    <table class="apidocs-params">
                        <thead>
                            <tr><th>Name</th><th>Type</th><th>Required</th><th>Description</th></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>url</code></td>
                                <td>string</td>
                                <td><span class="param-required">required</span></td>
                                <td>The HTTPS endpoint URL that will receive webhook payloads</td>
                            </tr>
                            <tr>
                                <td><code>events</code></td>
                                <td>array</td>
                                <td><span class="param-required">required</span></td>
                                <td>Events to subscribe to: <code>entry.created</code>, <code>entry.updated</code>, <code>entry.deleted</code>, <code>form.updated</code></td>
                            </tr>
                            <tr>
                                <td><code>form_id</code></td>
                                <td>integer</td>
                                <td><span class="param-optional">optional</span></td>
                                <td>Scope webhook to a specific form. Omit for all forms.</td>
                            </tr>
                            <tr>
                                <td><code>secret</code></td>
                                <td>string</td>
                                <td><span class="param-optional">optional</span></td>
                                <td>A signing secret for verifying payloads. Auto-generated if omitted.</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="apidocs-code-wrap">
                        <div class="apidocs-code-header">
                            <span class="apidocs-code-label">Example Request</span>
                        </div>
                        <button class="apidocs-copy-btn" onclick="copyCodeBlock(this)" type="button">Copy</button>
                        <pre class="apidocs-code-block"><span class="c-keyword">curl</span> <span class="c-flag">-X POST</span> <span class="c-url"><?= e($baseUrl) ?>/api/v1/webhooks</span> \
  <span class="c-flag">-H</span> <span class="c-string">"Authorization: Bearer <?= e($apiKey) ?>"</span> \
  <span class="c-flag">-H</span> <span class="c-string">"Content-Type: application/json"</span> \
  <span class="c-flag">-d</span> <span class="c-string">'{
    "url": "https://example.com/hooks/leadform",
    "events": ["entry.created"],
    "form_id": 25
  }'</span></pre>
                    </div>

                    <div class="apidocs-code-wrap">
                        <div class="apidocs-code-header">
                            <span class="apidocs-code-label">Example Response</span>
                            <span class="apidocs-code-label" style="color: #10B981;">201 Created</span>
                        </div>
                        <button class="apidocs-copy-btn" onclick="copyCodeBlock(this)" type="button">Copy</button>
                        <pre class="apidocs-code-block">{
  <span class="c-key">"data"</span>: {
    <span class="c-key">"id"</span>: <span class="c-number">13</span>,
    <span class="c-key">"url"</span>: <span class="c-string">"https://example.com/hooks/leadform"</span>,
    <span class="c-key">"events"</span>: [<span class="c-string">"entry.created"</span>],
    <span class="c-key">"form_id"</span>: <span class="c-number">25</span>,
    <span class="c-key">"is_active"</span>: <span class="c-bool">true</span>,
    <span class="c-key">"secret"</span>: <span class="c-string">"whsec_x9y8z7w6v5..."</span>,
    <span class="c-key">"created_at"</span>: <span class="c-string">"2025-03-15T14:30:00Z"</span>
  }
}</pre>
                    </div>
                </div>
            </div>

            <!-- UPDATE WEBHOOK -->
            <div class="apidocs-endpoint method-put" id="update-webhook">
                <div class="apidocs-endpoint-header">
                    <span class="apidocs-method-badge put">PUT</span>
                    <span class="apidocs-endpoint-url">/api/v1/webhooks/<span class="url-param">{id}</span></span>
                </div>
                <div class="apidocs-endpoint-body">
                    <p class="apidocs-endpoint-desc">
                        Updates an existing webhook configuration. You can change the target URL,
                        events, scope, or active status.
                    </p>

                    <div class="apidocs-params-title">Path Parameters</div>
                    <table class="apidocs-params">
                        <thead>
                            <tr><th>Name</th><th>Type</th><th>Required</th><th>Description</th></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>id</code></td>
                                <td>integer</td>
                                <td><span class="param-required">required</span></td>
                                <td>The webhook ID</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="apidocs-params-title">Body Parameters</div>
                    <table class="apidocs-params">
                        <thead>
                            <tr><th>Name</th><th>Type</th><th>Required</th><th>Description</th></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>url</code></td>
                                <td>string</td>
                                <td><span class="param-optional">optional</span></td>
                                <td>Updated webhook endpoint URL</td>
                            </tr>
                            <tr>
                                <td><code>events</code></td>
                                <td>array</td>
                                <td><span class="param-optional">optional</span></td>
                                <td>Updated list of events</td>
                            </tr>
                            <tr>
                                <td><code>is_active</code></td>
                                <td>boolean</td>
                                <td><span class="param-optional">optional</span></td>
                                <td>Enable or disable the webhook</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="apidocs-code-wrap">
                        <div class="apidocs-code-header">
                            <span class="apidocs-code-label">Example Request</span>
                        </div>
                        <button class="apidocs-copy-btn" onclick="copyCodeBlock(this)" type="button">Copy</button>
                        <pre class="apidocs-code-block"><span class="c-keyword">curl</span> <span class="c-flag">-X PUT</span> <span class="c-url"><?= e($baseUrl) ?>/api/v1/webhooks/13</span> \
  <span class="c-flag">-H</span> <span class="c-string">"Authorization: Bearer <?= e($apiKey) ?>"</span> \
  <span class="c-flag">-H</span> <span class="c-string">"Content-Type: application/json"</span> \
  <span class="c-flag">-d</span> <span class="c-string">'{
    "events": ["entry.created", "entry.deleted"],
    "is_active": true
  }'</span></pre>
                    </div>

                    <div class="apidocs-code-wrap">
                        <div class="apidocs-code-header">
                            <span class="apidocs-code-label">Example Response</span>
                            <span class="apidocs-code-label" style="color: #10B981;">200 OK</span>
                        </div>
                        <button class="apidocs-copy-btn" onclick="copyCodeBlock(this)" type="button">Copy</button>
                        <pre class="apidocs-code-block">{
  <span class="c-key">"data"</span>: {
    <span class="c-key">"id"</span>: <span class="c-number">13</span>,
    <span class="c-key">"url"</span>: <span class="c-string">"https://example.com/hooks/leadform"</span>,
    <span class="c-key">"events"</span>: [<span class="c-string">"entry.created"</span>, <span class="c-string">"entry.deleted"</span>],
    <span class="c-key">"form_id"</span>: <span class="c-number">25</span>,
    <span class="c-key">"is_active"</span>: <span class="c-bool">true</span>,
    <span class="c-key">"updated_at"</span>: <span class="c-string">"2025-03-15T15:10:00Z"</span>
  }
}</pre>
                    </div>
                </div>
            </div>

            <!-- DELETE WEBHOOK -->
            <div class="apidocs-endpoint method-delete" id="delete-webhook">
                <div class="apidocs-endpoint-header">
                    <span class="apidocs-method-badge delete">DELETE</span>
                    <span class="apidocs-endpoint-url">/api/v1/webhooks/<span class="url-param">{id}</span></span>
                </div>
                <div class="apidocs-endpoint-body">
                    <p class="apidocs-endpoint-desc">
                        Deletes a webhook subscription. The endpoint will no longer receive event payloads.
                    </p>

                    <div class="apidocs-params-title">Path Parameters</div>
                    <table class="apidocs-params">
                        <thead>
                            <tr><th>Name</th><th>Type</th><th>Required</th><th>Description</th></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>id</code></td>
                                <td>integer</td>
                                <td><span class="param-required">required</span></td>
                                <td>The webhook ID</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="apidocs-code-wrap">
                        <div class="apidocs-code-header">
                            <span class="apidocs-code-label">Example Request</span>
                        </div>
                        <button class="apidocs-copy-btn" onclick="copyCodeBlock(this)" type="button">Copy</button>
                        <pre class="apidocs-code-block"><span class="c-keyword">curl</span> <span class="c-flag">-X DELETE</span> <span class="c-url"><?= e($baseUrl) ?>/api/v1/webhooks/13</span> \
  <span class="c-flag">-H</span> <span class="c-string">"Authorization: Bearer <?= e($apiKey) ?>"</span></pre>
                    </div>

                    <div class="apidocs-code-wrap">
                        <div class="apidocs-code-header">
                            <span class="apidocs-code-label">Example Response</span>
                            <span class="apidocs-code-label" style="color: #10B981;">200 OK</span>
                        </div>
                        <button class="apidocs-copy-btn" onclick="copyCodeBlock(this)" type="button">Copy</button>
                        <pre class="apidocs-code-block">{
  <span class="c-key">"message"</span>: <span class="c-string">"Webhook deleted successfully."</span>
}</pre>
                    </div>
                </div>
            </div>
        </section>

        <!-- ======================================
             ACCOUNT
             ====================================== -->
        <section class="apidocs-section" id="account">
            <h2 class="apidocs-section-title">Account</h2>
            <p class="apidocs-section-desc">
                Retrieve information about your account, subscription plan, and current usage statistics.
            </p>

            <!-- GET ACCOUNT -->
            <div class="apidocs-endpoint method-get" id="get-account">
                <div class="apidocs-endpoint-header">
                    <span class="apidocs-method-badge get">GET</span>
                    <span class="apidocs-endpoint-url">/api/v1/account</span>
                </div>
                <div class="apidocs-endpoint-body">
                    <p class="apidocs-endpoint-desc">
                        Returns your account details, including plan information and limits.
                    </p>

                    <div class="apidocs-code-wrap">
                        <div class="apidocs-code-header">
                            <span class="apidocs-code-label">Example Request</span>
                        </div>
                        <button class="apidocs-copy-btn" onclick="copyCodeBlock(this)" type="button">Copy</button>
                        <pre class="apidocs-code-block"><span class="c-keyword">curl</span> <span class="c-flag">-X GET</span> <span class="c-url"><?= e($baseUrl) ?>/api/v1/account</span> \
  <span class="c-flag">-H</span> <span class="c-string">"Authorization: Bearer <?= e($apiKey) ?>"</span> \
  <span class="c-flag">-H</span> <span class="c-string">"Accept: application/json"</span></pre>
                    </div>

                    <div class="apidocs-code-wrap">
                        <div class="apidocs-code-header">
                            <span class="apidocs-code-label">Example Response</span>
                            <span class="apidocs-code-label" style="color: #10B981;">200 OK</span>
                        </div>
                        <button class="apidocs-copy-btn" onclick="copyCodeBlock(this)" type="button">Copy</button>
                        <pre class="apidocs-code-block">{
  <span class="c-key">"data"</span>: {
    <span class="c-key">"id"</span>: <span class="c-number">42</span>,
    <span class="c-key">"name"</span>: <span class="c-string">"<?= e($user['name'] ?? 'Acme Corp') ?>"</span>,
    <span class="c-key">"email"</span>: <span class="c-string">"<?= e($email) ?>"</span>,
    <span class="c-key">"plan"</span>: {
      <span class="c-key">"name"</span>: <span class="c-string">"Pro"</span>,
      <span class="c-key">"forms_limit"</span>: <span class="c-number">-1</span>,
      <span class="c-key">"entries_limit"</span>: <span class="c-number">10000</span>,
      <span class="c-key">"api_access"</span>: <span class="c-bool">true</span>
    },
    <span class="c-key">"created_at"</span>: <span class="c-string">"2024-11-05T08:00:00Z"</span>
  }
}</pre>
                    </div>
                </div>
            </div>

            <!-- GET USAGE -->
            <div class="apidocs-endpoint method-get" id="get-usage">
                <div class="apidocs-endpoint-header">
                    <span class="apidocs-method-badge get">GET</span>
                    <span class="apidocs-endpoint-url">/api/v1/account/usage</span>
                </div>
                <div class="apidocs-endpoint-body">
                    <p class="apidocs-endpoint-desc">
                        Returns your current billing cycle usage, including form count, entry count,
                        and API call statistics.
                    </p>

                    <div class="apidocs-params-title">Query Parameters</div>
                    <table class="apidocs-params">
                        <thead>
                            <tr><th>Name</th><th>Type</th><th>Required</th><th>Description</th></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>period</code></td>
                                <td>string</td>
                                <td><span class="param-optional">optional</span></td>
                                <td>Usage period: <code>current</code> (default), <code>previous</code>, or ISO month <code>2025-03</code></td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="apidocs-code-wrap">
                        <div class="apidocs-code-header">
                            <span class="apidocs-code-label">Example Request</span>
                        </div>
                        <button class="apidocs-copy-btn" onclick="copyCodeBlock(this)" type="button">Copy</button>
                        <pre class="apidocs-code-block"><span class="c-keyword">curl</span> <span class="c-flag">-X GET</span> <span class="c-url"><?= e($baseUrl) ?>/api/v1/account/usage?period=current</span> \
  <span class="c-flag">-H</span> <span class="c-string">"Authorization: Bearer <?= e($apiKey) ?>"</span> \
  <span class="c-flag">-H</span> <span class="c-string">"Accept: application/json"</span></pre>
                    </div>

                    <div class="apidocs-code-wrap">
                        <div class="apidocs-code-header">
                            <span class="apidocs-code-label">Example Response</span>
                            <span class="apidocs-code-label" style="color: #10B981;">200 OK</span>
                        </div>
                        <button class="apidocs-copy-btn" onclick="copyCodeBlock(this)" type="button">Copy</button>
                        <pre class="apidocs-code-block">{
  <span class="c-key">"data"</span>: {
    <span class="c-key">"period"</span>: <span class="c-string">"2025-03"</span>,
    <span class="c-key">"forms"</span>: {
      <span class="c-key">"used"</span>: <span class="c-number">12</span>,
      <span class="c-key">"limit"</span>: <span class="c-number">-1</span>
    },
    <span class="c-key">"entries"</span>: {
      <span class="c-key">"used"</span>: <span class="c-number">4832</span>,
      <span class="c-key">"limit"</span>: <span class="c-number">10000</span>
    },
    <span class="c-key">"api_calls"</span>: {
      <span class="c-key">"used"</span>: <span class="c-number">12450</span>,
      <span class="c-key">"limit"</span>: <span class="c-number">100000</span>
    },
    <span class="c-key">"storage_mb"</span>: {
      <span class="c-key">"used"</span>: <span class="c-number">245</span>,
      <span class="c-key">"limit"</span>: <span class="c-number">5000</span>
    }
  }
}</pre>
                    </div>
                </div>
            </div>
        </section>

        <!-- ======================================
             RATE LIMITING
             ====================================== -->
        <section class="apidocs-section" id="rate-limiting">
            <h2 class="apidocs-section-title">Rate Limiting</h2>
            <p class="apidocs-section-desc">
                The API enforces rate limits to ensure fair usage and service stability.
                You can make up to <strong>100 requests per minute</strong> per API key.
                Rate limit information is included in every response via HTTP headers.
            </p>

            <div class="apidocs-info info">
                <span class="apidocs-info-icon">&#8505;</span>
                <div>
                    When you exceed the rate limit, the API responds with a <code>429 Too Many Requests</code>
                    status code. Wait until the <code>X-RateLimit-Reset</code> timestamp before retrying.
                </div>
            </div>

            <div class="card" style="overflow: hidden;">
                <table class="apidocs-header-table">
                    <thead>
                        <tr>
                            <th>Header</th>
                            <th>Description</th>
                            <th>Example</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>X-RateLimit-Limit</code></td>
                            <td>Maximum requests allowed per window</td>
                            <td><code>100</code></td>
                        </tr>
                        <tr>
                            <td><code>X-RateLimit-Remaining</code></td>
                            <td>Requests remaining in the current window</td>
                            <td><code>87</code></td>
                        </tr>
                        <tr>
                            <td><code>X-RateLimit-Reset</code></td>
                            <td>Unix timestamp when the window resets</td>
                            <td><code>1710500460</code></td>
                        </tr>
                        <tr>
                            <td><code>Retry-After</code></td>
                            <td>Seconds to wait (only on 429 responses)</td>
                            <td><code>23</code></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="apidocs-code-wrap" style="margin-top: var(--space-6);">
                <div class="apidocs-code-header">
                    <span class="apidocs-code-label">Rate Limit Exceeded Response</span>
                    <span class="apidocs-code-label" style="color: #EF4444;">429 Too Many Requests</span>
                </div>
                <button class="apidocs-copy-btn" onclick="copyCodeBlock(this)" type="button">Copy</button>
                <pre class="apidocs-code-block">{
  <span class="c-key">"error"</span>: {
    <span class="c-key">"code"</span>: <span class="c-string">"rate_limit_exceeded"</span>,
    <span class="c-key">"message"</span>: <span class="c-string">"Too many requests. Please retry after 23 seconds."</span>,
    <span class="c-key">"retry_after"</span>: <span class="c-number">23</span>
  }
}</pre>
            </div>
        </section>

        <!-- ======================================
             ERROR CODES
             ====================================== -->
        <section class="apidocs-section" id="errors">
            <h2 class="apidocs-section-title">Error Codes</h2>
            <p class="apidocs-section-desc">
                The API uses standard HTTP status codes and returns consistent JSON error
                responses. Every error includes a machine-readable <code>code</code> and
                a human-readable <code>message</code>.
            </p>

            <div class="apidocs-code-wrap">
                <div class="apidocs-code-header">
                    <span class="apidocs-code-label">Standard Error Format</span>
                </div>
                <button class="apidocs-copy-btn" onclick="copyCodeBlock(this)" type="button">Copy</button>
                <pre class="apidocs-code-block">{
  <span class="c-key">"error"</span>: {
    <span class="c-key">"code"</span>: <span class="c-string">"resource_not_found"</span>,
    <span class="c-key">"message"</span>: <span class="c-string">"The requested form was not found."</span>,
    <span class="c-key">"details"</span>: {} <span class="c-comment">// optional, additional context</span>
  }
}</pre>
            </div>

            <div class="card" style="overflow: hidden; margin-top: var(--space-6);">
                <table class="apidocs-errors-table">
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Code</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><span class="apidocs-status-code s2xx">200</span></td>
                            <td>-</td>
                            <td>Request succeeded.</td>
                        </tr>
                        <tr>
                            <td><span class="apidocs-status-code s2xx">201</span></td>
                            <td>-</td>
                            <td>Resource created successfully.</td>
                        </tr>
                        <tr>
                            <td><span class="apidocs-status-code s4xx">400</span></td>
                            <td><code>bad_request</code></td>
                            <td>The request body is malformed or contains invalid JSON.</td>
                        </tr>
                        <tr>
                            <td><span class="apidocs-status-code s4xx">401</span></td>
                            <td><code>unauthorized</code></td>
                            <td>Missing or invalid API key. Check the <code>Authorization</code> header.</td>
                        </tr>
                        <tr>
                            <td><span class="apidocs-status-code s4xx">403</span></td>
                            <td><code>forbidden</code></td>
                            <td>Your plan does not include API access, or you lack permission for this resource.</td>
                        </tr>
                        <tr>
                            <td><span class="apidocs-status-code s4xx">404</span></td>
                            <td><code>resource_not_found</code></td>
                            <td>The requested resource does not exist.</td>
                        </tr>
                        <tr>
                            <td><span class="apidocs-status-code s4xx">422</span></td>
                            <td><code>validation_error</code></td>
                            <td>The request body failed validation. Check the <code>details</code> field for specifics.</td>
                        </tr>
                        <tr>
                            <td><span class="apidocs-status-code s4xx">429</span></td>
                            <td><code>rate_limit_exceeded</code></td>
                            <td>Too many requests. Wait and retry after the <code>Retry-After</code> period.</td>
                        </tr>
                        <tr>
                            <td><span class="apidocs-status-code s5xx">500</span></td>
                            <td><code>internal_error</code></td>
                            <td>An unexpected server error occurred. Contact support if it persists.</td>
                        </tr>
                        <tr>
                            <td><span class="apidocs-status-code s5xx">503</span></td>
                            <td><code>service_unavailable</code></td>
                            <td>The API is temporarily unavailable for maintenance.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="apidocs-info info" style="margin-top: var(--space-6);">
                <span class="apidocs-info-icon">&#8505;</span>
                <div>
                    For <code>422</code> validation errors, the <code>details</code> object contains
                    field-level error messages:
                </div>
            </div>

            <div class="apidocs-code-wrap">
                <div class="apidocs-code-header">
                    <span class="apidocs-code-label">Validation Error Example</span>
                    <span class="apidocs-code-label" style="color: #EF4444;">422 Unprocessable Entity</span>
                </div>
                <button class="apidocs-copy-btn" onclick="copyCodeBlock(this)" type="button">Copy</button>
                <pre class="apidocs-code-block">{
  <span class="c-key">"error"</span>: {
    <span class="c-key">"code"</span>: <span class="c-string">"validation_error"</span>,
    <span class="c-key">"message"</span>: <span class="c-string">"The given data failed validation."</span>,
    <span class="c-key">"details"</span>: {
      <span class="c-key">"name"</span>: [<span class="c-string">"The name field is required."</span>],
      <span class="c-key">"fields.0.type"</span>: [<span class="c-string">"The selected type is invalid."</span>]
    }
  }
}</pre>
            </div>
        </section>

    </div><!-- /.apidocs-content -->
</div><!-- /.apidocs-wrap -->

<!-- ============================================
     JAVASCRIPT: Copy, Sidebar Active State,
     Smooth Scroll
     ============================================ -->
<script>
(function() {
    'use strict';

    /**
     * Copy text from an element by ID
     */
    window.copyToClipboard = function(elementId, btn) {
        var el = document.getElementById(elementId);
        if (!el) return;

        var text = el.textContent || el.innerText;
        navigator.clipboard.writeText(text.trim()).then(function() {
            var original = btn.textContent;
            btn.textContent = 'Copied!';
            btn.classList.add('btn-success');
            setTimeout(function() {
                btn.textContent = original;
                btn.classList.remove('btn-success');
            }, 2000);
        });
    };

    /**
     * Copy the code block content (sibling <pre>)
     */
    window.copyCodeBlock = function(btn) {
        var wrap = btn.parentElement;
        var pre = wrap.querySelector('pre');
        if (!pre) return;

        var text = pre.textContent || pre.innerText;
        navigator.clipboard.writeText(text.trim()).then(function() {
            var original = btn.textContent;
            btn.textContent = 'Copied!';
            btn.classList.add('copied');
            setTimeout(function() {
                btn.textContent = original;
                btn.classList.remove('copied');
            }, 2000);
        });
    };

    /**
     * Sidebar active state on scroll
     */
    var navLinks = document.querySelectorAll('.apidocs-nav-link');
    var sections = [];

    navLinks.forEach(function(link) {
        var href = link.getAttribute('href');
        if (href && href.charAt(0) === '#') {
            var target = document.getElementById(href.substring(1));
            if (target) {
                sections.push({ el: target, link: link });
            }
        }
    });

    function updateActiveLink() {
        var scrollPos = window.scrollY + 120;
        var active = null;

        for (var i = sections.length - 1; i >= 0; i--) {
            if (sections[i].el.offsetTop <= scrollPos) {
                active = sections[i];
                break;
            }
        }

        navLinks.forEach(function(link) {
            link.classList.remove('active');
        });

        if (active) {
            active.link.classList.add('active');
        }
    }

    window.addEventListener('scroll', updateActiveLink, { passive: true });
    updateActiveLink();

    /**
     * Smooth scroll for sidebar links
     */
    navLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            var href = this.getAttribute('href');
            if (href && href.charAt(0) === '#') {
                e.preventDefault();
                var target = document.getElementById(href.substring(1));
                if (target) {
                    var offset = 100;
                    var top = target.getBoundingClientRect().top + window.pageYOffset - offset;
                    window.scrollTo({ top: top, behavior: 'smooth' });
                    history.pushState(null, '', href);
                }
            }
        });
    });

})();
</script>
