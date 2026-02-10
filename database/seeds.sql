-- ============================================================================
-- LeadForm SaaS - Seed Data
-- Run AFTER schema.sql
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- 1. DEFAULT PLANS
-- ============================================================================

INSERT INTO `plans` (`id`, `name`, `slug`, `description`, `price_monthly`, `price_yearly`, `currency`,
                     `max_forms`, `max_entries_per_month`, `max_file_storage`, `max_users`, `max_file_size`,
                     `features`, `integrations`, `is_active`, `is_featured`, `sort_order`, `created_at`, `updated_at`)
VALUES
(1, 'Free', 'free',
 'Get started with conversational forms. Perfect for individuals and small projects.',
 0.00, 0.00, 'USD',
 3, 100, 100, 1, 5,
 '["basic_form_builder","conversational_forms","basic_analytics","email_notifications","basic_themes","partial_submissions"]',
 '["webhook"]',
 1, 0, 1, NOW(), NOW()),

(2, 'Pro', 'pro',
 'For growing teams that need advanced features, integrations, and higher limits.',
 29.00, 290.00, 'USD',
 25, 5000, 2048, 5, 25,
 '["basic_form_builder","conversational_forms","classic_forms","advanced_analytics","email_notifications","custom_themes","file_uploads","conditional_logic","flow_builder","ab_testing","custom_thank_you","redirect_on_complete","partial_submissions","save_and_continue","password_protection","scheduling","custom_css","seo_settings","lead_scoring","export_csv","export_pdf","api_access","custom_branding","remove_watermark","priority_support"]',
 '["webhook","google_sheets","zapier","mailchimp","facebook_pixel","google_analytics","google_tag_manager"]',
 1, 1, 2, NOW(), NOW()),

(3, 'Enterprise', 'enterprise',
 'Unlimited power for large organisations. Custom domains, white-label, dedicated support, and all integrations.',
 99.00, 990.00, 'USD',
 0, 0, 10240, 0, 100,
 '["basic_form_builder","conversational_forms","classic_forms","advanced_analytics","email_notifications","custom_themes","file_uploads","conditional_logic","flow_builder","ab_testing","custom_thank_you","redirect_on_complete","partial_submissions","save_and_continue","password_protection","scheduling","custom_css","custom_js","seo_settings","lead_scoring","ai_analysis","ai_lead_temperature","export_csv","export_pdf","export_api","api_access","custom_branding","remove_watermark","white_label","custom_domain","sla_support","audit_logs","team_permissions","signature_field","calendly_integration","stripe_payments","paypal_payments","hipaa_mode","gdpr_tools","data_retention_policies","priority_support","dedicated_account_manager"]',
 '["webhook","google_sheets","crm","zapier","facebook_pixel","google_analytics","google_tag_manager","mailchimp","hubspot","stripe","paypal","custom","tiktok_pixel"]',
 1, 0, 3, NOW(), NOW());

-- NOTE: max_forms = 0 and max_entries_per_month = 0 in Enterprise means UNLIMITED

-- ============================================================================
-- 2. DEFAULT TENANT (platform owner / demo)
-- ============================================================================

INSERT INTO `tenants` (`id`, `name`, `slug`, `email`, `phone`, `logo`, `domain`, `custom_domain`,
                       `plan_id`, `subscription_status`, `settings`, `api_key`, `api_secret`,
                       `storage_used`, `max_storage`, `status`, `created_at`, `updated_at`)
VALUES
(1, 'LeadForm Platform', 'leadform', 'admin@leadform.io', NULL, NULL, 'app', NULL,
 3, 'active',
 '{"locale":"en","timezone":"UTC","date_format":"YYYY-MM-DD","branding":true}',
 'lf_key_000000000000000000000000000000', 'lf_sec_000000000000000000000000000000',
 0, 107374182400, 'active', NOW(), NOW());

-- ============================================================================
-- 3. DEFAULT SUPER ADMIN USER
-- Password: admin123
-- bcrypt hash generated with cost 10
-- ============================================================================

INSERT INTO `users` (`id`, `tenant_id`, `name`, `email`, `password`, `role`, `avatar`, `phone`,
                     `two_factor_enabled`, `email_verified_at`, `last_login_at`, `status`,
                     `locale`, `timezone`, `created_at`, `updated_at`)
VALUES
(1, NULL, 'Super Admin', 'admin@leadform.io',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'super_admin', NULL, NULL,
 0, NOW(), NOW(), 'active',
 'en', 'UTC', NOW(), NOW());

-- ============================================================================
-- 4. DEFAULT PERMISSIONS
-- ============================================================================

INSERT INTO `permissions` (`id`, `name`, `slug`, `group`, `description`) VALUES
-- Dashboard
(1,  'View Dashboard',           'dashboard.view',            'dashboard',     'Access the main dashboard'),

-- Forms
(2,  'View Forms',               'forms.view',                'forms',         'View list of forms'),
(3,  'Create Forms',             'forms.create',              'forms',         'Create new forms'),
(4,  'Edit Forms',               'forms.edit',                'forms',         'Edit existing forms'),
(5,  'Delete Forms',             'forms.delete',              'forms',         'Delete forms'),
(6,  'Publish Forms',            'forms.publish',             'forms',         'Publish / unpublish forms'),
(7,  'Duplicate Forms',          'forms.duplicate',           'forms',         'Duplicate an existing form'),

-- Entries
(8,  'View Entries',             'entries.view',              'entries',       'View form submissions'),
(9,  'Export Entries',           'entries.export',            'entries',       'Export entries to CSV/PDF'),
(10, 'Delete Entries',           'entries.delete',            'entries',       'Delete form entries'),
(11, 'Add Entry Notes',          'entries.notes',             'entries',       'Add internal notes to entries'),

-- Analytics
(12, 'View Analytics',           'analytics.view',            'analytics',     'View analytics dashboards'),
(13, 'Export Analytics',          'analytics.export',          'analytics',     'Export analytics data'),

-- Integrations
(14, 'View Integrations',        'integrations.view',         'integrations',  'View integration settings'),
(15, 'Manage Integrations',      'integrations.manage',       'integrations',  'Create, edit, delete integrations'),

-- Webhooks
(16, 'View Webhooks',            'webhooks.view',             'webhooks',      'View webhook configurations'),
(17, 'Manage Webhooks',          'webhooks.manage',           'webhooks',      'Create, edit, delete webhooks'),

-- Tracking Pixels
(18, 'View Tracking Pixels',     'pixels.view',               'pixels',        'View tracking pixel settings'),
(19, 'Manage Tracking Pixels',   'pixels.manage',             'pixels',        'Create, edit, delete tracking pixels'),

-- Users & Team
(20, 'View Users',               'users.view',                'users',         'View team members'),
(21, 'Invite Users',             'users.invite',              'users',         'Invite new team members'),
(22, 'Edit Users',               'users.edit',                'users',         'Edit team member details'),
(23, 'Delete Users',             'users.delete',              'users',         'Remove team members'),

-- Settings
(24, 'View Settings',            'settings.view',             'settings',      'View tenant settings'),
(25, 'Edit Settings',            'settings.edit',             'settings',      'Modify tenant settings'),

-- Billing
(26, 'View Billing',             'billing.view',              'billing',       'View billing and subscription info'),
(27, 'Manage Billing',           'billing.manage',            'billing',       'Change plan, update payment method'),

-- API
(28, 'View API Tokens',          'api.view',                  'api',           'View API tokens'),
(29, 'Manage API Tokens',        'api.manage',                'api',           'Create, revoke API tokens'),

-- Audit
(30, 'View Audit Logs',          'audit.view',                'audit',         'View audit trail'),

-- Themes
(31, 'View Themes',              'themes.view',               'themes',        'View form themes'),
(32, 'Manage Themes',            'themes.manage',             'themes',        'Create, edit, delete themes'),

-- System (super_admin only)
(33, 'Manage Tenants',           'system.tenants',            'system',        'View and manage all tenants'),
(34, 'Manage Plans',             'system.plans',              'system',        'Create and edit pricing plans'),
(35, 'Manage System Settings',   'system.settings',           'system',        'Edit global system settings'),
(36, 'Manage Pages',             'system.pages',              'system',        'Manage CMS pages'),
(37, 'Manage Translations',      'system.translations',       'system',        'Manage translations'),
(38, 'Manage Features',          'system.features',           'system',        'Manage feature flags'),
(39, 'View System Logs',         'system.logs',               'system',        'View API and system logs'),
(40, 'Impersonate Users',        'system.impersonate',        'system',        'Log in as any user');

-- ============================================================================
-- 5. ROLE-PERMISSION MAPPINGS
-- ============================================================================

-- super_admin: ALL permissions
INSERT INTO `role_permissions` (`role`, `permission_id`)
SELECT 'super_admin', `id` FROM `permissions`;

-- admin: everything except system-level
INSERT INTO `role_permissions` (`role`, `permission_id`)
SELECT 'admin', `id` FROM `permissions` WHERE `group` != 'system';

-- editor: forms, entries, analytics, themes, integrations, webhooks, pixels, dashboard
INSERT INTO `role_permissions` (`role`, `permission_id`)
SELECT 'editor', `id` FROM `permissions`
WHERE `group` IN ('dashboard', 'forms', 'entries', 'analytics', 'integrations', 'webhooks', 'pixels', 'themes');

-- viewer: read-only access
INSERT INTO `role_permissions` (`role`, `permission_id`)
SELECT 'viewer', `id` FROM `permissions`
WHERE `slug` IN (
    'dashboard.view',
    'forms.view',
    'entries.view',
    'analytics.view',
    'integrations.view',
    'webhooks.view',
    'pixels.view',
    'themes.view',
    'settings.view',
    'billing.view'
);

-- ============================================================================
-- 6. DEFAULT SYSTEM SETTINGS
-- ============================================================================

INSERT INTO `system_settings` (`key`, `value`, `type`, `group`, `created_at`, `updated_at`) VALUES
-- General
('app_name',                  'LeadForm SaaS',                          'string', 'general',  NOW(), NOW()),
('app_url',                   'https://app.leadform.io',                'string', 'general',  NOW(), NOW()),
('app_logo',                  '/images/logo.svg',                       'string', 'general',  NOW(), NOW()),
('app_favicon',               '/images/favicon.ico',                    'string', 'general',  NOW(), NOW()),
('app_locale',                'en',                                     'string', 'general',  NOW(), NOW()),
('app_timezone',              'UTC',                                    'string', 'general',  NOW(), NOW()),
('app_date_format',           'YYYY-MM-DD',                             'string', 'general',  NOW(), NOW()),

-- Auth
('registration_enabled',      '1',                                      'bool',   'auth',     NOW(), NOW()),
('email_verification_required','1',                                     'bool',   'auth',     NOW(), NOW()),
('two_factor_enabled',        '1',                                      'bool',   'auth',     NOW(), NOW()),
('default_plan_id',           '1',                                      'int',    'auth',     NOW(), NOW()),
('trial_days',                '14',                                     'int',    'auth',     NOW(), NOW()),

-- Email / SMTP
('mail_driver',               'smtp',                                   'string', 'mail',     NOW(), NOW()),
('mail_host',                 'smtp.mailtrap.io',                       'string', 'mail',     NOW(), NOW()),
('mail_port',                 '587',                                    'int',    'mail',     NOW(), NOW()),
('mail_username',             '',                                       'string', 'mail',     NOW(), NOW()),
('mail_password',             '',                                       'string', 'mail',     NOW(), NOW()),
('mail_encryption',           'tls',                                    'string', 'mail',     NOW(), NOW()),
('mail_from_address',         'noreply@leadform.io',                    'string', 'mail',     NOW(), NOW()),
('mail_from_name',            'LeadForm',                               'string', 'mail',     NOW(), NOW()),

-- Storage
('storage_driver',            'local',                                  'string', 'storage',  NOW(), NOW()),
('s3_bucket',                 '',                                       'string', 'storage',  NOW(), NOW()),
('s3_region',                 'us-east-1',                              'string', 'storage',  NOW(), NOW()),
('s3_key',                    '',                                       'string', 'storage',  NOW(), NOW()),
('s3_secret',                 '',                                       'string', 'storage',  NOW(), NOW()),

-- Payments
('payment_gateway',           'stripe',                                 'string', 'payments', NOW(), NOW()),
('stripe_public_key',         '',                                       'string', 'payments', NOW(), NOW()),
('stripe_secret_key',         '',                                       'string', 'payments', NOW(), NOW()),
('stripe_webhook_secret',     '',                                       'string', 'payments', NOW(), NOW()),
('paypal_client_id',          '',                                       'string', 'payments', NOW(), NOW()),
('paypal_secret',             '',                                       'string', 'payments', NOW(), NOW()),
('paypal_mode',               'sandbox',                                'string', 'payments', NOW(), NOW()),

-- SEO
('seo_title',                 'LeadForm - Conversational Form Builder', 'string', 'seo',      NOW(), NOW()),
('seo_description',           'Build beautiful conversational forms that convert. Engage your leads with interactive, smart forms.', 'string', 'seo', NOW(), NOW()),
('seo_image',                 '/images/og-image.jpg',                   'string', 'seo',      NOW(), NOW()),

-- Appearance
('primary_color',             '#4F46E5',                                'string', 'appearance', NOW(), NOW()),
('secondary_color',           '#7C3AED',                                'string', 'appearance', NOW(), NOW()),
('footer_text',               '© 2025 LeadForm. All rights reserved.', 'string', 'appearance', NOW(), NOW()),

-- Limits / Security
('rate_limit_api',            '60',                                     'int',    'security', NOW(), NOW()),
('rate_limit_submissions',    '30',                                     'int',    'security', NOW(), NOW()),
('recaptcha_enabled',         '0',                                      'bool',   'security', NOW(), NOW()),
('recaptcha_site_key',        '',                                       'string', 'security', NOW(), NOW()),
('recaptcha_secret_key',      '',                                       'string', 'security', NOW(), NOW()),

-- AI
('ai_enabled',                '1',                                      'bool',   'ai',       NOW(), NOW()),
('ai_provider',               'openai',                                 'string', 'ai',       NOW(), NOW()),
('ai_api_key',                '',                                       'string', 'ai',       NOW(), NOW()),
('ai_model',                  'gpt-4o',                                 'string', 'ai',       NOW(), NOW()),

-- Analytics
('analytics_retention_days',  '365',                                    'int',    'analytics', NOW(), NOW()),
('form_visit_retention_days', '90',                                     'int',    'analytics', NOW(), NOW());

-- ============================================================================
-- 7. DEFAULT FEATURES
-- ============================================================================

INSERT INTO `features` (`name`, `slug`, `description`, `category`, `is_active`, `plan_required`, `created_at`) VALUES
-- Form Builder
('Basic Form Builder',         'basic_form_builder',       'Drag-and-drop form builder with essential field types',         'form_builder',   1, '["free","pro","enterprise"]',     NOW()),
('Conversational Forms',       'conversational_forms',     'One-question-at-a-time conversational form experience',         'form_builder',   1, '["free","pro","enterprise"]',     NOW()),
('Classic Forms',              'classic_forms',            'Traditional multi-field form layout',                           'form_builder',   1, '["pro","enterprise"]',            NOW()),
('Conditional Logic',          'conditional_logic',        'Show/hide fields based on previous answers',                    'form_builder',   1, '["pro","enterprise"]',            NOW()),
('Flow Builder',               'flow_builder',             'Visual flow builder for complex branching logic',               'form_builder',   1, '["pro","enterprise"]',            NOW()),
('A/B Testing',                'ab_testing',               'Split-test form variants to optimise conversion',               'form_builder',   1, '["pro","enterprise"]',            NOW()),
('File Uploads',               'file_uploads',             'Allow respondents to upload files',                             'form_builder',   1, '["pro","enterprise"]',            NOW()),
('Signature Field',            'signature_field',          'Capture electronic signatures',                                 'form_builder',   1, '["enterprise"]',                  NOW()),
('Calendly Integration Field', 'calendly_integration',     'Embed Calendly scheduling inside forms',                        'form_builder',   1, '["enterprise"]',                  NOW()),

-- Submissions
('Partial Submissions',        'partial_submissions',      'Capture and view incomplete submissions',                       'submissions',    1, '["free","pro","enterprise"]',     NOW()),
('Save and Continue',          'save_and_continue',        'Allow respondents to save progress and return later',           'submissions',    1, '["pro","enterprise"]',            NOW()),
('Password Protection',        'password_protection',      'Require a password to access a form',                           'submissions',    1, '["pro","enterprise"]',            NOW()),
('Scheduling',                 'scheduling',               'Set start/end dates for form availability',                     'submissions',    1, '["pro","enterprise"]',            NOW()),

-- Customisation
('Basic Themes',               'basic_themes',             'Pre-built form themes',                                         'customisation',  1, '["free","pro","enterprise"]',     NOW()),
('Custom Themes',              'custom_themes',            'Create and save custom visual themes',                          'customisation',  1, '["pro","enterprise"]',            NOW()),
('Custom CSS',                 'custom_css',               'Add custom CSS to forms',                                       'customisation',  1, '["pro","enterprise"]',            NOW()),
('Custom JavaScript',          'custom_js',                'Add custom JavaScript to forms',                                'customisation',  1, '["enterprise"]',                  NOW()),
('Custom Thank You Page',      'custom_thank_you',         'Customise the post-submission thank you page',                  'customisation',  1, '["pro","enterprise"]',            NOW()),
('Redirect on Complete',       'redirect_on_complete',     'Redirect respondents to a URL after submission',                'customisation',  1, '["pro","enterprise"]',            NOW()),
('SEO Settings',               'seo_settings',             'Customise SEO title, description, and OG image per form',      'customisation',  1, '["pro","enterprise"]',            NOW()),
('Custom Branding',            'custom_branding',          'Replace LeadForm branding with your own logo',                  'customisation',  1, '["pro","enterprise"]',            NOW()),
('Remove Watermark',           'remove_watermark',         'Remove the "Powered by LeadForm" watermark',                    'customisation',  1, '["pro","enterprise"]',            NOW()),
('White Label',                'white_label',              'Full white-label experience with custom domain',                'customisation',  1, '["enterprise"]',                  NOW()),
('Custom Domain',              'custom_domain',            'Use your own domain for hosted forms',                          'customisation',  1, '["enterprise"]',                  NOW()),

-- Analytics
('Basic Analytics',            'basic_analytics',          'Views, submissions, and completion rate',                       'analytics',      1, '["free","pro","enterprise"]',     NOW()),
('Advanced Analytics',         'advanced_analytics',       'Field-level analytics, drop-offs, UTM tracking, geography',    'analytics',      1, '["pro","enterprise"]',            NOW()),
('Lead Scoring',               'lead_scoring',             'Automatic lead scoring based on responses',                     'analytics',      1, '["pro","enterprise"]',            NOW()),
('AI Analysis',                'ai_analysis',              'AI-powered analysis and temperature scoring of leads',          'analytics',      1, '["enterprise"]',                  NOW()),
('AI Lead Temperature',        'ai_lead_temperature',      'AI classifies leads as cold, warm, or hot',                     'analytics',      1, '["enterprise"]',                  NOW()),

-- Notifications
('Email Notifications',        'email_notifications',      'Get notified by email on new submissions',                      'notifications',  1, '["free","pro","enterprise"]',     NOW()),

-- Export
('Export CSV',                 'export_csv',               'Export entries as CSV files',                                    'export',         1, '["pro","enterprise"]',            NOW()),
('Export PDF',                 'export_pdf',               'Export entries as PDF files',                                    'export',         1, '["pro","enterprise"]',            NOW()),
('Export via API',             'export_api',               'Programmatic export via REST API',                              'export',         1, '["enterprise"]',                  NOW()),
('API Access',                 'api_access',               'Full REST API access for forms, entries, and analytics',        'export',         1, '["pro","enterprise"]',            NOW()),

-- Security & Compliance
('Audit Logs',                 'audit_logs',               'Full audit trail for all actions',                              'security',       1, '["enterprise"]',                  NOW()),
('Team Permissions',           'team_permissions',         'Granular role-based permissions for team members',              'security',       1, '["enterprise"]',                  NOW()),
('HIPAA Mode',                 'hipaa_mode',               'HIPAA-compliant data handling configuration',                   'security',       1, '["enterprise"]',                  NOW()),
('GDPR Tools',                 'gdpr_tools',               'Data export, deletion requests, consent management',           'security',       1, '["enterprise"]',                  NOW()),
('Data Retention Policies',    'data_retention_policies',  'Automatic data expiry and retention policies',                  'security',       1, '["enterprise"]',                  NOW()),

-- Support
('Priority Support',           'priority_support',         'Priority email and chat support',                               'support',        1, '["pro","enterprise"]',            NOW()),
('SLA Support',                'sla_support',              'Guaranteed response times with SLA',                            'support',        1, '["enterprise"]',                  NOW()),
('Dedicated Account Manager',  'dedicated_account_manager','A dedicated account manager for onboarding and support',        'support',        1, '["enterprise"]',                  NOW()),

-- Payments
('Stripe Payments',            'stripe_payments',          'Accept payments via Stripe inside forms',                       'payments',       1, '["enterprise"]',                  NOW()),
('PayPal Payments',            'paypal_payments',          'Accept payments via PayPal inside forms',                       'payments',       1, '["enterprise"]',                  NOW());

-- ============================================================================
-- 8. DEFAULT FORM THEMES
-- ============================================================================

INSERT INTO `form_themes` (`id`, `tenant_id`, `name`, `settings`, `is_default`, `created_at`) VALUES
(1, NULL, 'Default Light', '{
    "primary_color": "#4F46E5",
    "secondary_color": "#7C3AED",
    "bg_color": "#FFFFFF",
    "text_color": "#1F2937",
    "font_family": "Inter, sans-serif",
    "font_size": "16px",
    "border_radius": "8px",
    "button_style": "filled",
    "animation_type": "slide",
    "custom_css": ""
}', 1, NOW()),

(2, NULL, 'Default Dark', '{
    "primary_color": "#818CF8",
    "secondary_color": "#A78BFA",
    "bg_color": "#111827",
    "text_color": "#F9FAFB",
    "font_family": "Inter, sans-serif",
    "font_size": "16px",
    "border_radius": "8px",
    "button_style": "filled",
    "animation_type": "fade",
    "custom_css": ""
}', 0, NOW()),

(3, NULL, 'Minimal', '{
    "primary_color": "#000000",
    "secondary_color": "#6B7280",
    "bg_color": "#FAFAFA",
    "text_color": "#111827",
    "font_family": "system-ui, sans-serif",
    "font_size": "15px",
    "border_radius": "4px",
    "button_style": "outline",
    "animation_type": "none",
    "custom_css": ""
}', 0, NOW()),

(4, NULL, 'Vibrant', '{
    "primary_color": "#EC4899",
    "secondary_color": "#F59E0B",
    "bg_color": "#FDF2F8",
    "text_color": "#1F2937",
    "font_family": "Poppins, sans-serif",
    "font_size": "16px",
    "border_radius": "12px",
    "button_style": "filled",
    "animation_type": "bounce",
    "custom_css": ""
}', 0, NOW());

-- ============================================================================
-- 9. SAMPLE TRANSLATIONS (pt_BR - Brazilian Portuguese)
-- ============================================================================

INSERT INTO `translations` (`locale`, `group`, `key`, `value`, `created_at`, `updated_at`) VALUES
-- General UI
('pt_BR', 'general',  'app_name',              'LeadForm SaaS',                           NOW(), NOW()),
('pt_BR', 'general',  'dashboard',             'Painel',                                  NOW(), NOW()),
('pt_BR', 'general',  'home',                  'Inicio',                                  NOW(), NOW()),
('pt_BR', 'general',  'settings',              'Configuracoes',                            NOW(), NOW()),
('pt_BR', 'general',  'save',                  'Salvar',                                  NOW(), NOW()),
('pt_BR', 'general',  'cancel',                'Cancelar',                                NOW(), NOW()),
('pt_BR', 'general',  'delete',                'Excluir',                                 NOW(), NOW()),
('pt_BR', 'general',  'edit',                  'Editar',                                  NOW(), NOW()),
('pt_BR', 'general',  'create',                'Criar',                                   NOW(), NOW()),
('pt_BR', 'general',  'search',                'Buscar',                                  NOW(), NOW()),
('pt_BR', 'general',  'filter',                'Filtrar',                                 NOW(), NOW()),
('pt_BR', 'general',  'export',                'Exportar',                                NOW(), NOW()),
('pt_BR', 'general',  'import',                'Importar',                                NOW(), NOW()),
('pt_BR', 'general',  'back',                  'Voltar',                                  NOW(), NOW()),
('pt_BR', 'general',  'next',                  'Proximo',                                 NOW(), NOW()),
('pt_BR', 'general',  'previous',              'Anterior',                                NOW(), NOW()),
('pt_BR', 'general',  'confirm',               'Confirmar',                               NOW(), NOW()),
('pt_BR', 'general',  'loading',               'Carregando...',                            NOW(), NOW()),
('pt_BR', 'general',  'no_results',            'Nenhum resultado encontrado',              NOW(), NOW()),
('pt_BR', 'general',  'actions',               'Acoes',                                   NOW(), NOW()),
('pt_BR', 'general',  'status',                'Status',                                  NOW(), NOW()),
('pt_BR', 'general',  'active',                'Ativo',                                   NOW(), NOW()),
('pt_BR', 'general',  'inactive',              'Inativo',                                 NOW(), NOW()),
('pt_BR', 'general',  'yes',                   'Sim',                                     NOW(), NOW()),
('pt_BR', 'general',  'no',                    'Nao',                                     NOW(), NOW()),

-- Auth
('pt_BR', 'auth',     'login',                 'Entrar',                                  NOW(), NOW()),
('pt_BR', 'auth',     'logout',                'Sair',                                    NOW(), NOW()),
('pt_BR', 'auth',     'register',              'Cadastrar',                               NOW(), NOW()),
('pt_BR', 'auth',     'email',                 'E-mail',                                  NOW(), NOW()),
('pt_BR', 'auth',     'password',              'Senha',                                   NOW(), NOW()),
('pt_BR', 'auth',     'confirm_password',      'Confirmar Senha',                         NOW(), NOW()),
('pt_BR', 'auth',     'forgot_password',       'Esqueci minha senha',                     NOW(), NOW()),
('pt_BR', 'auth',     'reset_password',        'Redefinir Senha',                         NOW(), NOW()),
('pt_BR', 'auth',     'remember_me',           'Lembrar-me',                              NOW(), NOW()),
('pt_BR', 'auth',     'two_factor',            'Autenticacao de Dois Fatores',            NOW(), NOW()),
('pt_BR', 'auth',     'verify_email',          'Verifique seu e-mail',                    NOW(), NOW()),
('pt_BR', 'auth',     'verification_sent',     'Link de verificacao enviado!',            NOW(), NOW()),

-- Forms
('pt_BR', 'forms',    'forms',                 'Formularios',                              NOW(), NOW()),
('pt_BR', 'forms',    'create_form',           'Criar Formulario',                        NOW(), NOW()),
('pt_BR', 'forms',    'edit_form',             'Editar Formulario',                       NOW(), NOW()),
('pt_BR', 'forms',    'form_title',            'Titulo do Formulario',                    NOW(), NOW()),
('pt_BR', 'forms',    'form_description',      'Descricao do Formulario',                 NOW(), NOW()),
('pt_BR', 'forms',    'conversational',        'Conversacional',                          NOW(), NOW()),
('pt_BR', 'forms',    'classic',               'Classico',                                NOW(), NOW()),
('pt_BR', 'forms',    'draft',                 'Rascunho',                                NOW(), NOW()),
('pt_BR', 'forms',    'published',             'Publicado',                               NOW(), NOW()),
('pt_BR', 'forms',    'archived',              'Arquivado',                               NOW(), NOW()),
('pt_BR', 'forms',    'publish',               'Publicar',                                NOW(), NOW()),
('pt_BR', 'forms',    'unpublish',             'Despublicar',                             NOW(), NOW()),
('pt_BR', 'forms',    'duplicate',             'Duplicar',                                NOW(), NOW()),
('pt_BR', 'forms',    'share',                 'Compartilhar',                            NOW(), NOW()),
('pt_BR', 'forms',    'embed',                 'Incorporar',                              NOW(), NOW()),
('pt_BR', 'forms',    'preview',               'Visualizar',                              NOW(), NOW()),
('pt_BR', 'forms',    'submissions',           'Respostas',                               NOW(), NOW()),
('pt_BR', 'forms',    'views',                 'Visualizacoes',                            NOW(), NOW()),
('pt_BR', 'forms',    'completion_rate',        'Taxa de Conclusao',                       NOW(), NOW()),
('pt_BR', 'forms',    'thank_you_message',     'Mensagem de Agradecimento',               NOW(), NOW()),
('pt_BR', 'forms',    'redirect_url',          'URL de Redirecionamento',                 NOW(), NOW()),

-- Fields
('pt_BR', 'fields',   'add_field',             'Adicionar Campo',                         NOW(), NOW()),
('pt_BR', 'fields',   'field_label',           'Rotulo do Campo',                         NOW(), NOW()),
('pt_BR', 'fields',   'field_placeholder',     'Texto de Exemplo',                        NOW(), NOW()),
('pt_BR', 'fields',   'field_required',        'Campo Obrigatorio',                       NOW(), NOW()),
('pt_BR', 'fields',   'field_description',     'Descricao do Campo',                      NOW(), NOW()),
('pt_BR', 'fields',   'name',                  'Nome',                                    NOW(), NOW()),
('pt_BR', 'fields',   'email',                 'E-mail',                                  NOW(), NOW()),
('pt_BR', 'fields',   'phone',                 'Telefone',                                NOW(), NOW()),
('pt_BR', 'fields',   'company',               'Empresa',                                 NOW(), NOW()),
('pt_BR', 'fields',   'address',               'Endereco',                                NOW(), NOW()),
('pt_BR', 'fields',   'cpf_cnpj',              'CPF/CNPJ',                                NOW(), NOW()),
('pt_BR', 'fields',   'text',                  'Texto Curto',                             NOW(), NOW()),
('pt_BR', 'fields',   'textarea',              'Texto Longo',                             NOW(), NOW()),
('pt_BR', 'fields',   'radio',                 'Escolha Unica',                           NOW(), NOW()),
('pt_BR', 'fields',   'select',                'Lista Suspensa',                          NOW(), NOW()),
('pt_BR', 'fields',   'checkbox',              'Multipla Escolha',                        NOW(), NOW()),
('pt_BR', 'fields',   'file_upload',           'Upload de Arquivo',                       NOW(), NOW()),
('pt_BR', 'fields',   'datepicker',            'Seletor de Data',                         NOW(), NOW()),
('pt_BR', 'fields',   'signature',             'Assinatura',                              NOW(), NOW()),
('pt_BR', 'fields',   'rating',                'Avaliacao',                               NOW(), NOW()),
('pt_BR', 'fields',   'opinion_scale',         'Escala de Opiniao',                       NOW(), NOW()),
('pt_BR', 'fields',   'picture_choice',        'Escolha com Imagem',                      NOW(), NOW()),
('pt_BR', 'fields',   'hidden',                'Campo Oculto',                            NOW(), NOW()),
('pt_BR', 'fields',   'divider',               'Divisor',                                 NOW(), NOW()),
('pt_BR', 'fields',   'section_break',         'Quebra de Secao',                         NOW(), NOW()),
('pt_BR', 'fields',   'paragraph',             'Paragrafo',                               NOW(), NOW()),

-- Entries
('pt_BR', 'entries',  'entries',               'Respostas',                               NOW(), NOW()),
('pt_BR', 'entries',  'entry_details',         'Detalhes da Resposta',                    NOW(), NOW()),
('pt_BR', 'entries',  'complete',              'Completa',                                NOW(), NOW()),
('pt_BR', 'entries',  'partial',               'Parcial',                                 NOW(), NOW()),
('pt_BR', 'entries',  'abandoned',             'Abandonada',                              NOW(), NOW()),
('pt_BR', 'entries',  'add_note',              'Adicionar Nota',                          NOW(), NOW()),
('pt_BR', 'entries',  'export_csv',            'Exportar CSV',                            NOW(), NOW()),
('pt_BR', 'entries',  'export_pdf',            'Exportar PDF',                            NOW(), NOW()),
('pt_BR', 'entries',  'lead_score',            'Pontuacao do Lead',                       NOW(), NOW()),
('pt_BR', 'entries',  'ai_temperature',        'Temperatura IA',                          NOW(), NOW()),
('pt_BR', 'entries',  'cold',                  'Frio',                                    NOW(), NOW()),
('pt_BR', 'entries',  'warm',                  'Morno',                                   NOW(), NOW()),
('pt_BR', 'entries',  'hot',                   'Quente',                                  NOW(), NOW()),

-- Analytics
('pt_BR', 'analytics','analytics',             'Analiticos',                               NOW(), NOW()),
('pt_BR', 'analytics','total_views',           'Total de Visualizacoes',                   NOW(), NOW()),
('pt_BR', 'analytics','total_submissions',     'Total de Respostas',                       NOW(), NOW()),
('pt_BR', 'analytics','conversion_rate',       'Taxa de Conversao',                        NOW(), NOW()),
('pt_BR', 'analytics','avg_duration',          'Duracao Media',                            NOW(), NOW()),
('pt_BR', 'analytics','drop_off_rate',         'Taxa de Abandono',                         NOW(), NOW()),
('pt_BR', 'analytics','devices',               'Dispositivos',                             NOW(), NOW()),
('pt_BR', 'analytics','desktop',               'Desktop',                                  NOW(), NOW()),
('pt_BR', 'analytics','mobile',                'Celular',                                  NOW(), NOW()),
('pt_BR', 'analytics','tablet',                'Tablet',                                   NOW(), NOW()),
('pt_BR', 'analytics','geography',             'Geografia',                                NOW(), NOW()),
('pt_BR', 'analytics','utm_tracking',          'Rastreamento UTM',                         NOW(), NOW()),

-- Integrations
('pt_BR', 'integrations', 'integrations',      'Integracoes',                              NOW(), NOW()),
('pt_BR', 'integrations', 'add_integration',   'Adicionar Integracao',                     NOW(), NOW()),
('pt_BR', 'integrations', 'webhook',           'Webhook',                                  NOW(), NOW()),
('pt_BR', 'integrations', 'google_sheets',     'Google Sheets',                            NOW(), NOW()),
('pt_BR', 'integrations', 'zapier',            'Zapier',                                   NOW(), NOW()),
('pt_BR', 'integrations', 'mailchimp',         'Mailchimp',                                NOW(), NOW()),
('pt_BR', 'integrations', 'hubspot',           'HubSpot',                                  NOW(), NOW()),

-- Plans / Billing
('pt_BR', 'billing',  'billing',               'Faturamento',                             NOW(), NOW()),
('pt_BR', 'billing',  'current_plan',          'Plano Atual',                             NOW(), NOW()),
('pt_BR', 'billing',  'upgrade',               'Fazer Upgrade',                           NOW(), NOW()),
('pt_BR', 'billing',  'downgrade',             'Fazer Downgrade',                         NOW(), NOW()),
('pt_BR', 'billing',  'monthly',               'Mensal',                                  NOW(), NOW()),
('pt_BR', 'billing',  'yearly',                'Anual',                                   NOW(), NOW()),
('pt_BR', 'billing',  'free',                  'Gratuito',                                NOW(), NOW()),
('pt_BR', 'billing',  'pro',                   'Profissional',                            NOW(), NOW()),
('pt_BR', 'billing',  'enterprise',            'Empresarial',                             NOW(), NOW()),
('pt_BR', 'billing',  'payment_history',       'Historico de Pagamentos',                 NOW(), NOW()),
('pt_BR', 'billing',  'invoice',               'Fatura',                                  NOW(), NOW()),

-- Users
('pt_BR', 'users',    'users',                 'Usuarios',                                NOW(), NOW()),
('pt_BR', 'users',    'invite_user',           'Convidar Usuario',                        NOW(), NOW()),
('pt_BR', 'users',    'role',                  'Funcao',                                  NOW(), NOW()),
('pt_BR', 'users',    'admin',                 'Administrador',                           NOW(), NOW()),
('pt_BR', 'users',    'editor',                'Editor',                                  NOW(), NOW()),
('pt_BR', 'users',    'viewer',                'Visualizador',                            NOW(), NOW()),
('pt_BR', 'users',    'profile',               'Perfil',                                  NOW(), NOW()),

-- Validation messages
('pt_BR', 'validation','required',             'Este campo e obrigatorio.',                NOW(), NOW()),
('pt_BR', 'validation','email',                'Informe um e-mail valido.',                NOW(), NOW()),
('pt_BR', 'validation','min',                  'O valor minimo e :min.',                   NOW(), NOW()),
('pt_BR', 'validation','max',                  'O valor maximo e :max.',                   NOW(), NOW()),
('pt_BR', 'validation','url',                  'Informe uma URL valida.',                  NOW(), NOW()),
('pt_BR', 'validation','file_too_large',       'O arquivo e muito grande. Tamanho maximo: :max MB.', NOW(), NOW()),
('pt_BR', 'validation','invalid_file_type',    'Tipo de arquivo nao permitido.',            NOW(), NOW()),
('pt_BR', 'validation','cpf_invalid',          'CPF invalido.',                            NOW(), NOW()),
('pt_BR', 'validation','cnpj_invalid',         'CNPJ invalido.',                           NOW(), NOW()),
('pt_BR', 'validation','phone_invalid',        'Numero de telefone invalido.',              NOW(), NOW());

-- ============================================================================
-- 10. ENGLISH BASE TRANSLATIONS (en)
-- ============================================================================

INSERT INTO `translations` (`locale`, `group`, `key`, `value`, `created_at`, `updated_at`) VALUES
('en', 'general', 'dashboard',       'Dashboard',       NOW(), NOW()),
('en', 'general', 'home',            'Home',            NOW(), NOW()),
('en', 'general', 'settings',        'Settings',        NOW(), NOW()),
('en', 'general', 'save',            'Save',            NOW(), NOW()),
('en', 'general', 'cancel',          'Cancel',          NOW(), NOW()),
('en', 'general', 'delete',          'Delete',          NOW(), NOW()),
('en', 'general', 'edit',            'Edit',            NOW(), NOW()),
('en', 'general', 'create',          'Create',          NOW(), NOW()),
('en', 'general', 'search',          'Search',          NOW(), NOW()),
('en', 'general', 'loading',         'Loading...',      NOW(), NOW()),
('en', 'general', 'no_results',      'No results found', NOW(), NOW()),
('en', 'auth',    'login',           'Sign In',         NOW(), NOW()),
('en', 'auth',    'logout',          'Sign Out',        NOW(), NOW()),
('en', 'auth',    'register',        'Sign Up',         NOW(), NOW()),
('en', 'auth',    'forgot_password', 'Forgot Password', NOW(), NOW()),
('en', 'validation', 'required',     'This field is required.', NOW(), NOW()),
('en', 'validation', 'email',        'Please enter a valid email address.', NOW(), NOW());

SET FOREIGN_KEY_CHECKS = 1;
