<?php

/**
 * LeadForm SaaS - Route Definitions
 *
 * This file is loaded by the App bootstrap. The $router variable is
 * injected automatically and is an instance of \Core\Router.
 *
 * @var \Core\Router $router
 */

// ======================================================================
// Public pages
// ======================================================================

$router->get('/', 'HomeController@index');
$router->get('/pricing', 'HomeController@pricing');
$router->get('/features', 'HomeController@features');
$router->get('/contact', 'HomeController@contact');
$router->post('/contact', 'HomeController@contactSubmit');

// ======================================================================
// Authentication routes
// ======================================================================

$router->get('/login', 'AuthController@loginForm');
$router->post('/login', 'AuthController@login');
$router->get('/register', 'AuthController@registerForm');
$router->post('/register', 'AuthController@register');
$router->get('/logout', 'AuthController@logout');
$router->get('/forgot-password', 'AuthController@forgotForm');
$router->post('/forgot-password', 'AuthController@forgot');
$router->get('/reset-password/{token}', 'AuthController@resetForm');
$router->post('/reset-password', 'AuthController@reset');

// 2FA verification
$router->get('/2fa/verify', 'AuthController@verify2FAForm');
$router->post('/2fa/verify', 'AuthController@verify2FA');

// ======================================================================
// Super Admin routes
// ======================================================================

$router->group('/admin', ['middleware' => 'superadmin'], function ($router) {

    $router->get('/', 'Admin\\DashboardController@index');

    // Client management
    $router->get('/clients', 'Admin\\ClientsController@index');
    $router->get('/clients/create', 'Admin\\ClientsController@create');
    $router->post('/clients', 'Admin\\ClientsController@store');
    $router->get('/clients/{id}', 'Admin\\ClientsController@show');
    $router->get('/clients/{id}/edit', 'Admin\\ClientsController@edit');
    $router->put('/clients/{id}', 'Admin\\ClientsController@update');
    $router->delete('/clients/{id}', 'Admin\\ClientsController@destroy');

    // Plans management
    $router->get('/plans', 'Admin\\PlansController@index');
    $router->get('/plans/create', 'Admin\\PlansController@create');
    $router->post('/plans', 'Admin\\PlansController@store');
    $router->get('/plans/{id}/edit', 'Admin\\PlansController@edit');
    $router->put('/plans/{id}', 'Admin\\PlansController@update');
    $router->delete('/plans/{id}', 'Admin\\PlansController@destroy');

    // All forms across tenants
    $router->get('/forms', 'Admin\\FormsController@index');
    $router->get('/forms/{id}', 'Admin\\FormsController@show');
    $router->get('/forms/{id}/entries', 'Admin\\FormsController@entries');

    // All entries across tenants
    $router->get('/entries', 'Admin\\EntriesController@index');
    $router->get('/entries/{id}', 'Admin\\EntriesController@show');
    $router->get('/entries/export', 'Admin\\EntriesController@export');

    // System settings (multiple sections)
    $router->get('/settings', 'Admin\\SettingsController@index');
    $router->post('/settings', 'Admin\\SettingsController@update');
    $router->get('/settings/seo', 'Admin\\SettingsController@seo');
    $router->post('/settings/seo', 'Admin\\SettingsController@updateSeo');
    $router->get('/settings/theme', 'Admin\\SettingsController@theme');
    $router->post('/settings/theme', 'Admin\\SettingsController@updateTheme');
    $router->get('/settings/ai', 'Admin\\SettingsController@ai');
    $router->post('/settings/ai', 'Admin\\SettingsController@updateAi');
    $router->get('/settings/site', 'Admin\\SettingsController@site');
    $router->post('/settings/site', 'Admin\\SettingsController@updateSite');
    $router->get('/settings/dev', 'Admin\\SettingsController@dev');
    $router->post('/settings/dev', 'Admin\\SettingsController@updateDev');

    // Audit log
    $router->get('/audit', 'Admin\\AuditController@index');
    $router->get('/audit/{id}', 'Admin\\AuditController@show');

    // Features / Integrations management
    $router->get('/features', 'Admin\\FeaturesController@index');
    $router->post('/features/{id}/toggle', 'Admin\\FeaturesController@toggle');
    $router->post('/features/{id}', 'Admin\\FeaturesController@update');

    // Translations
    $router->get('/translations', 'Admin\\TranslationsController@index');
    $router->get('/translations/{locale}/{group}', 'Admin\\TranslationsController@edit');
    $router->post('/translations', 'Admin\\TranslationsController@update');

    // Impersonation
    $router->post('/clients/{id}/login-as', 'Admin\\ClientsController@loginAs');
    $router->post('/clients/{id}/suspend', 'Admin\\ClientsController@suspend');
    $router->post('/clients/{id}/activate', 'Admin\\ClientsController@activate');
    $router->get('/stop-impersonate', 'Admin\\ClientsController@stopImpersonate');

    // System reports
    $router->get('/reports', 'Admin\\ReportsController@index');
});

// ======================================================================
// Client dashboard routes (authenticated)
// ======================================================================

$router->group('/dashboard', ['middleware' => 'auth'], function ($router) {

    $router->get('/', 'Client\\DashboardController@index');

    // Form builder
    $router->get('/forms', 'Client\\FormsController@index');
    $router->get('/forms/create', 'Client\\FormsController@create');
    $router->post('/forms', 'Client\\FormsController@store');
    $router->get('/forms/{id}', 'Client\\FormsController@show');
    $router->get('/forms/{id}/edit', 'Client\\FormsController@edit');
    $router->put('/forms/{id}', 'Client\\FormsController@update');
    $router->delete('/forms/{id}', 'Client\\FormsController@destroy');
    $router->get('/forms/{id}/builder', 'Client\\FormBuilderController@index');
    $router->post('/forms/{id}/builder', 'Client\\FormBuilderController@save');
    $router->get('/forms/{id}/analytics', 'Client\\FormAnalyticsController@index');

    // Leads / submissions
    $router->get('/leads', 'Client\\LeadsController@index');
    $router->get('/leads/{id}', 'Client\\LeadsController@show');
    $router->delete('/leads/{id}', 'Client\\LeadsController@destroy');
    $router->post('/leads/export', 'Client\\LeadsController@export');

    // Integrations
    $router->get('/integrations', 'Client\\IntegrationsController@index');
    $router->post('/integrations', 'Client\\IntegrationsController@store');
    $router->put('/integrations/{id}', 'Client\\IntegrationsController@update');
    $router->delete('/integrations/{id}', 'Client\\IntegrationsController@destroy');

    // Profile / account settings
    $router->get('/profile', 'Client\\ProfileController@index');
    $router->put('/profile', 'Client\\ProfileController@update');
    $router->put('/profile/password', 'Client\\ProfileController@updatePassword');
    $router->get('/profile/2fa', 'Client\\ProfileController@show2FA');
    $router->post('/profile/2fa/enable', 'Client\\ProfileController@enable2FA');
    $router->post('/profile/2fa/disable', 'Client\\ProfileController@disable2FA');

    // Webhooks
    $router->get('/webhooks', 'Client\\IntegrationsController@webhooks');
    $router->get('/webhooks/create', 'Client\\IntegrationsController@createWebhook');
    $router->post('/webhooks', 'Client\\IntegrationsController@storeWebhook');
    $router->get('/webhooks/{id}/edit', 'Client\\IntegrationsController@editWebhook');
    $router->post('/webhooks/{id}', 'Client\\IntegrationsController@updateWebhook');
    $router->post('/webhooks/{id}/delete', 'Client\\IntegrationsController@deleteWebhook');
    $router->post('/webhooks/{id}/test', 'Client\\IntegrationsController@testWebhook');

    // Security settings
    $router->get('/security', 'Client\\ProfileController@security');
    $router->post('/security/password', 'Client\\ProfileController@updatePassword');
    $router->post('/security/2fa/enable', 'Client\\ProfileController@enable2FA');
    $router->post('/security/2fa/verify', 'Client\\ProfileController@verify2FA');
    $router->post('/security/2fa/disable', 'Client\\ProfileController@disable2FA');

    // API Keys
    $router->get('/api-keys', 'Client\\ProfileController@apiKeys');
    $router->post('/api-keys', 'Client\\ProfileController@generateApiKey');
    $router->post('/api-keys/{id}/revoke', 'Client\\ProfileController@revokeApiKey');

    // Pixels & Tags
    $router->get('/pixels', 'Client\\ProfileController@pixels');
    $router->post('/pixels', 'Client\\ProfileController@savePixel');
    $router->post('/pixels/{id}/delete', 'Client\\ProfileController@deletePixel');

    // AI Settings
    $router->get('/ai-settings', 'Client\\ProfileController@aiSettings');
    $router->post('/ai-settings', 'Client\\ProfileController@updateAi');

    // Form builder AJAX
    $router->post('/forms/ajax-save', 'Client\\FormsController@ajaxSave');

    // Billing
    $router->get('/billing', 'Client\\BillingController@index');
    $router->post('/billing/subscribe', 'Client\\BillingController@subscribe');
    $router->post('/billing/cancel', 'Client\\BillingController@cancel');
});

// ======================================================================
// API v1 routes (JWT authentication)
// ======================================================================

$router->group('/api/v1', ['middleware' => 'api'], function ($router) {

    // Forms
    $router->get('/forms', 'Api\\FormApiController@index');
    $router->get('/forms/{id}', 'Api\\FormApiController@show');
    $router->post('/forms', 'Api\\FormApiController@store');
    $router->put('/forms/{id}', 'Api\\FormApiController@update');
    $router->delete('/forms/{id}', 'Api\\FormApiController@destroy');

    // Leads / Submissions
    $router->get('/leads', 'Api\\LeadApiController@index');
    $router->get('/leads/{id}', 'Api\\LeadApiController@show');
    $router->delete('/leads/{id}', 'Api\\LeadApiController@destroy');

    // Webhooks config
    $router->get('/webhooks', 'Api\\WebhookApiController@index');
    $router->post('/webhooks', 'Api\\WebhookApiController@store');
    $router->delete('/webhooks/{id}', 'Api\\WebhookApiController@destroy');
});

// ======================================================================
// Public form rendering and submission
// ======================================================================

$router->get('/f/{slug}', 'FormController@render');
$router->post('/f/{slug}/submit', 'FormController@submit');
$router->post('/f/{slug}/save-partial', 'FormController@savePartial');
$router->get('/f/{slug}/continue/{token}', 'FormController@continueForm');
$router->get('/f/{slug}/thankyou', 'FormController@thankyou');
$router->post('/f/{slug}/track', 'FormController@trackVisit');
$router->post('/f/{slug}/upload', 'FormController@uploadFile');

// Embed script
$router->get('/embed/{slug}.js', 'FormController@embedScript');

// API Documentation page
$router->get('/api-docs', 'HomeController@apiDocs');
