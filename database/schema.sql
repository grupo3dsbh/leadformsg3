-- ============================================================================
-- LeadForm SaaS - Conversational Form Builder
-- Database Schema for MySQL 8.0+
-- Engine: InnoDB | Charset: utf8mb4 | Collation: utf8mb4_unicode_ci
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- ============================================================================
-- SYSTEM TABLES
-- ============================================================================

-- ---------------------------------------------------------------------------
-- Table: system_settings
-- Global platform configuration key-value store
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS `system_settings`;
CREATE TABLE `system_settings` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `key`        VARCHAR(191)    NOT NULL,
    `value`      TEXT            NULL,
    `type`       ENUM('string','int','bool','json') NOT NULL DEFAULT 'string',
    `group`      VARCHAR(100)    NULL     COMMENT 'Logical group for settings UI',
    `created_at` TIMESTAMP       NULL DEFAULT NULL,
    `updated_at` TIMESTAMP       NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_system_settings_key` (`key`),
    KEY `idx_system_settings_group` (`group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Global platform configuration';

-- ---------------------------------------------------------------------------
-- Table: pages
-- CMS pages (terms, privacy, landing, etc.)
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS `pages`;
CREATE TABLE `pages` (
    `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `slug`            VARCHAR(191)    NOT NULL,
    `title`           VARCHAR(255)    NOT NULL,
    `content`         LONGTEXT        NULL,
    `seo_title`       VARCHAR(255)    NULL,
    `seo_description` VARCHAR(500)    NULL,
    `seo_image`       VARCHAR(500)    NULL,
    `status`          ENUM('published','draft') NOT NULL DEFAULT 'draft',
    `locale`          VARCHAR(10)     NOT NULL DEFAULT 'en',
    `created_at`      TIMESTAMP       NULL DEFAULT NULL,
    `updated_at`      TIMESTAMP       NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_pages_slug_locale` (`slug`, `locale`),
    KEY `idx_pages_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='CMS pages for the platform';

-- ---------------------------------------------------------------------------
-- Table: translations
-- i18n key-value translation store
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS `translations`;
CREATE TABLE `translations` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `locale`     VARCHAR(10)     NOT NULL,
    `group`      VARCHAR(100)    NOT NULL,
    `key`        VARCHAR(191)    NOT NULL,
    `value`      TEXT            NULL,
    `created_at` TIMESTAMP       NULL DEFAULT NULL,
    `updated_at` TIMESTAMP       NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_translations_locale_group_key` (`locale`, `group`, `key`),
    KEY `idx_translations_locale` (`locale`),
    KEY `idx_translations_group` (`group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Application translations';

-- ---------------------------------------------------------------------------
-- Table: features
-- Feature flags / capabilities catalogue
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS `features`;
CREATE TABLE `features` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`          VARCHAR(255)    NOT NULL,
    `slug`          VARCHAR(191)    NOT NULL,
    `description`   TEXT            NULL,
    `category`      VARCHAR(100)    NULL,
    `is_active`     TINYINT(1)      NOT NULL DEFAULT 1,
    `plan_required` JSON            NULL     COMMENT 'Array of plan slugs that include this feature',
    `created_at`    TIMESTAMP       NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_features_slug` (`slug`),
    KEY `idx_features_category` (`category`),
    KEY `idx_features_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Platform feature catalogue linked to plans';

-- ============================================================================
-- PLAN & SUBSCRIPTION TABLES
-- ============================================================================

-- ---------------------------------------------------------------------------
-- Table: plans
-- SaaS pricing plans
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS `plans`;
CREATE TABLE `plans` (
    `id`                    BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `name`                  VARCHAR(255)     NOT NULL,
    `slug`                  VARCHAR(191)     NOT NULL,
    `description`           TEXT             NULL,
    `price_monthly`         DECIMAL(10,2)    NOT NULL DEFAULT 0.00,
    `price_yearly`          DECIMAL(10,2)    NOT NULL DEFAULT 0.00,
    `currency`              VARCHAR(3)       NOT NULL DEFAULT 'USD',
    `max_forms`             INT UNSIGNED     NOT NULL DEFAULT 5,
    `max_entries_per_month` INT UNSIGNED     NOT NULL DEFAULT 100,
    `max_file_storage`      INT UNSIGNED     NOT NULL DEFAULT 100   COMMENT 'Max storage in MB',
    `max_users`             INT UNSIGNED     NOT NULL DEFAULT 1,
    `max_file_size`         INT UNSIGNED     NOT NULL DEFAULT 5     COMMENT 'Max single file size in MB',
    `features`              JSON             NULL     COMMENT 'Array of feature slugs included',
    `integrations`          JSON             NULL     COMMENT 'Array of allowed integration types',
    `is_active`             TINYINT(1)       NOT NULL DEFAULT 1,
    `is_featured`           TINYINT(1)       NOT NULL DEFAULT 0,
    `sort_order`            INT UNSIGNED     NOT NULL DEFAULT 0,
    `created_at`            TIMESTAMP        NULL DEFAULT NULL,
    `updated_at`            TIMESTAMP        NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_plans_slug` (`slug`),
    KEY `idx_plans_is_active` (`is_active`),
    KEY `idx_plans_sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='SaaS pricing plans';

-- ============================================================================
-- TENANT (ORGANISATION) TABLES
-- ============================================================================

-- ---------------------------------------------------------------------------
-- Table: tenants
-- Client organisations / workspaces
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS `tenants`;
CREATE TABLE `tenants` (
    `id`                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`                VARCHAR(255)    NOT NULL,
    `slug`                VARCHAR(191)    NOT NULL,
    `email`               VARCHAR(255)    NULL,
    `phone`               VARCHAR(50)     NULL,
    `logo`                VARCHAR(500)    NULL,
    `domain`              VARCHAR(255)    NULL     COMMENT 'Default subdomain slug',
    `custom_domain`       VARCHAR(255)    NULL     COMMENT 'Custom domain pointed by client',
    `plan_id`             BIGINT UNSIGNED NULL,
    `subscription_status` ENUM('active','trialing','past_due','cancelled','expired','none')
                                          NOT NULL DEFAULT 'none',
    `subscription_ends_at` TIMESTAMP      NULL DEFAULT NULL,
    `settings`            JSON            NULL     COMMENT 'Tenant-level settings',
    `api_key`             VARCHAR(191)    NULL,
    `api_secret`          VARCHAR(191)    NULL,
    `storage_used`        BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Bytes used',
    `max_storage`         BIGINT UNSIGNED NOT NULL DEFAULT 104857600 COMMENT 'Bytes allowed (default 100 MB)',
    `status`              ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active',
    `created_at`          TIMESTAMP       NULL DEFAULT NULL,
    `updated_at`          TIMESTAMP       NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_tenants_slug` (`slug`),
    UNIQUE KEY `uk_tenants_api_key` (`api_key`),
    UNIQUE KEY `uk_tenants_custom_domain` (`custom_domain`),
    KEY `idx_tenants_plan_id` (`plan_id`),
    KEY `idx_tenants_status` (`status`),
    KEY `idx_tenants_subscription_status` (`subscription_status`),
    CONSTRAINT `fk_tenants_plan` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Client organisations / workspaces';

-- ---------------------------------------------------------------------------
-- Table: subscriptions
-- Tenant billing subscriptions
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS `subscriptions`;
CREATE TABLE `subscriptions` (
    `id`                      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id`               BIGINT UNSIGNED NOT NULL,
    `plan_id`                 BIGINT UNSIGNED NOT NULL,
    `payment_gateway`         VARCHAR(50)     NULL     COMMENT 'stripe, paypal, manual, etc.',
    `gateway_subscription_id` VARCHAR(255)    NULL,
    `amount`                  DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
    `currency`                VARCHAR(3)      NOT NULL DEFAULT 'USD',
    `interval`                ENUM('monthly','yearly') NOT NULL DEFAULT 'monthly',
    `status`                  ENUM('active','trialing','past_due','cancelled','expired','incomplete')
                                              NOT NULL DEFAULT 'active',
    `trial_ends_at`           TIMESTAMP       NULL DEFAULT NULL,
    `current_period_start`    TIMESTAMP       NULL DEFAULT NULL,
    `current_period_end`      TIMESTAMP       NULL DEFAULT NULL,
    `cancelled_at`            TIMESTAMP       NULL DEFAULT NULL,
    `created_at`              TIMESTAMP       NULL DEFAULT NULL,
    `updated_at`              TIMESTAMP       NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_subscriptions_tenant_id` (`tenant_id`),
    KEY `idx_subscriptions_plan_id` (`plan_id`),
    KEY `idx_subscriptions_status` (`status`),
    KEY `idx_subscriptions_gateway_sub_id` (`gateway_subscription_id`),
    CONSTRAINT `fk_subscriptions_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_subscriptions_plan`   FOREIGN KEY (`plan_id`)   REFERENCES `plans`   (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Tenant billing subscriptions';

-- ---------------------------------------------------------------------------
-- Table: payments
-- Individual payment records
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS `payments`;
CREATE TABLE `payments` (
    `id`                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id`          BIGINT UNSIGNED NOT NULL,
    `subscription_id`    BIGINT UNSIGNED NULL,
    `payment_gateway`    VARCHAR(50)     NULL,
    `gateway_payment_id` VARCHAR(255)    NULL,
    `amount`             DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
    `currency`           VARCHAR(3)      NOT NULL DEFAULT 'USD',
    `status`             ENUM('pending','succeeded','failed','refunded') NOT NULL DEFAULT 'pending',
    `paid_at`            TIMESTAMP       NULL DEFAULT NULL,
    `created_at`         TIMESTAMP       NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_payments_tenant_id` (`tenant_id`),
    KEY `idx_payments_subscription_id` (`subscription_id`),
    KEY `idx_payments_status` (`status`),
    KEY `idx_payments_paid_at` (`paid_at`),
    CONSTRAINT `fk_payments_tenant`       FOREIGN KEY (`tenant_id`)       REFERENCES `tenants`       (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_payments_subscription` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Individual payment transactions';

-- ============================================================================
-- USER & PERMISSION TABLES
-- ============================================================================

-- ---------------------------------------------------------------------------
-- Table: users
-- Platform users (super admins + tenant members)
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `id`                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id`           BIGINT UNSIGNED NULL     COMMENT 'NULL for super_admin',
    `name`                VARCHAR(255)    NOT NULL,
    `email`               VARCHAR(191)    NOT NULL,
    `password`            VARCHAR(255)    NOT NULL,
    `role`                ENUM('super_admin','admin','editor','viewer') NOT NULL DEFAULT 'viewer',
    `avatar`              VARCHAR(500)    NULL,
    `phone`               VARCHAR(50)     NULL,
    `two_factor_secret`   VARCHAR(255)    NULL,
    `two_factor_enabled`  TINYINT(1)      NOT NULL DEFAULT 0,
    `email_verified_at`   TIMESTAMP       NULL DEFAULT NULL,
    `last_login_at`       TIMESTAMP       NULL DEFAULT NULL,
    `status`              ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active',
    `locale`              VARCHAR(10)     NOT NULL DEFAULT 'en',
    `timezone`            VARCHAR(50)     NOT NULL DEFAULT 'UTC',
    `created_at`          TIMESTAMP       NULL DEFAULT NULL,
    `updated_at`          TIMESTAMP       NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_users_email` (`email`),
    KEY `idx_users_tenant_id` (`tenant_id`),
    KEY `idx_users_role` (`role`),
    KEY `idx_users_status` (`status`),
    KEY `idx_users_created_at` (`created_at`),
    CONSTRAINT `fk_users_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Platform users (super admins + tenant members)';

-- ---------------------------------------------------------------------------
-- Table: permissions
-- Permission definitions
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS `permissions`;
CREATE TABLE `permissions` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(255)    NOT NULL,
    `slug`        VARCHAR(191)    NOT NULL,
    `group`       VARCHAR(100)    NULL,
    `description` VARCHAR(500)    NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_permissions_slug` (`slug`),
    KEY `idx_permissions_group` (`group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Permission definitions';

-- ---------------------------------------------------------------------------
-- Table: role_permissions
-- Maps roles to default permissions
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS `role_permissions`;
CREATE TABLE `role_permissions` (
    `role`          ENUM('super_admin','admin','editor','viewer') NOT NULL,
    `permission_id` BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (`role`, `permission_id`),
    CONSTRAINT `fk_role_permissions_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Default permissions per role';

-- ---------------------------------------------------------------------------
-- Table: user_permissions
-- Per-user permission overrides
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS `user_permissions`;
CREATE TABLE `user_permissions` (
    `user_id`       BIGINT UNSIGNED NOT NULL,
    `permission_id` BIGINT UNSIGNED NOT NULL,
    `granted`       TINYINT(1)      NOT NULL DEFAULT 1 COMMENT '1 = granted, 0 = revoked',
    PRIMARY KEY (`user_id`, `permission_id`),
    CONSTRAINT `fk_user_permissions_user`       FOREIGN KEY (`user_id`)       REFERENCES `users`       (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_user_permissions_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Per-user permission overrides';

-- ---------------------------------------------------------------------------
-- Table: password_resets
-- Password reset tokens
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE `password_resets` (
    `email`      VARCHAR(191) NOT NULL,
    `token`      VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP    NULL DEFAULT NULL,
    KEY `idx_password_resets_email` (`email`),
    KEY `idx_password_resets_token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Password reset tokens';

-- ---------------------------------------------------------------------------
-- Table: sessions
-- User sessions for session-based auth
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS `sessions`;
CREATE TABLE `sessions` (
    `id`            VARCHAR(191)    NOT NULL,
    `user_id`       BIGINT UNSIGNED NULL,
    `ip_address`    VARCHAR(45)     NULL,
    `user_agent`    TEXT            NULL,
    `payload`       TEXT            NOT NULL,
    `last_activity` INT UNSIGNED    NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_sessions_user_id` (`user_id`),
    KEY `idx_sessions_last_activity` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='User sessions';

-- ---------------------------------------------------------------------------
-- Table: notifications
-- In-app notifications
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`    BIGINT UNSIGNED NOT NULL,
    `tenant_id`  BIGINT UNSIGNED NULL,
    `type`       VARCHAR(100)    NOT NULL,
    `title`      VARCHAR(255)    NOT NULL,
    `message`    TEXT            NULL,
    `data`       JSON            NULL,
    `read_at`    TIMESTAMP       NULL DEFAULT NULL,
    `created_at` TIMESTAMP       NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_notifications_user_id` (`user_id`),
    KEY `idx_notifications_tenant_id` (`tenant_id`),
    KEY `idx_notifications_read_at` (`read_at`),
    KEY `idx_notifications_created_at` (`created_at`),
    CONSTRAINT `fk_notifications_user`   FOREIGN KEY (`user_id`)   REFERENCES `users`   (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_notifications_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='In-app user notifications';

-- ============================================================================
-- FORM TABLES
-- ============================================================================

-- ---------------------------------------------------------------------------
-- Table: form_themes
-- Reusable form themes per tenant
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS `form_themes`;
CREATE TABLE `form_themes` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id`  BIGINT UNSIGNED NULL     COMMENT 'NULL = system-wide default themes',
    `name`       VARCHAR(255)    NOT NULL,
    `settings`   JSON            NULL     COMMENT 'primary_color, secondary_color, bg_color, text_color, font_family, font_size, border_radius, button_style, animation_type, custom_css',
    `is_default` TINYINT(1)      NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP       NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_form_themes_tenant_id` (`tenant_id`),
    CONSTRAINT `fk_form_themes_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Reusable form visual themes';

-- ---------------------------------------------------------------------------
-- Table: forms
-- Central forms table
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS `forms`;
CREATE TABLE `forms` (
    `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id`         BIGINT UNSIGNED NOT NULL,
    `user_id`           BIGINT UNSIGNED NULL     COMMENT 'Creator',
    `title`             VARCHAR(255)    NOT NULL,
    `description`       TEXT            NULL,
    `slug`              VARCHAR(191)    NOT NULL,
    `type`              ENUM('conversational','classic') NOT NULL DEFAULT 'conversational',
    `status`            ENUM('draft','published','archived','expired') NOT NULL DEFAULT 'draft',
    `settings`          JSON            NULL     COMMENT 'Theme, colors, fonts, logo, bg_image, progress_bar, animations, completion_action, redirect_url, thank_you_message, subform_id, save_continue, auto_save, step_form, partial_save_at_field, unique_link, expiry_date, password, max_submissions, allowed_roles, start_date, end_date, locale, custom_css, custom_js, seo_title, seo_description, seo_image, embed_allowed_domains',
    `views_count`       INT UNSIGNED    NOT NULL DEFAULT 0,
    `submissions_count` INT UNSIGNED    NOT NULL DEFAULT 0,
    `completion_rate`   DECIMAL(5,2)    NOT NULL DEFAULT 0.00,
    `ab_test_enabled`   TINYINT(1)      NOT NULL DEFAULT 0,
    `ab_test_variant`   VARCHAR(10)     NULL     COMMENT 'A, B, C, etc.',
    `parent_form_id`    BIGINT UNSIGNED NULL     COMMENT 'Reference to original form for A/B testing',
    `created_at`        TIMESTAMP       NULL DEFAULT NULL,
    `updated_at`        TIMESTAMP       NULL DEFAULT NULL,
    `published_at`      TIMESTAMP       NULL DEFAULT NULL,
    `expires_at`        TIMESTAMP       NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_forms_tenant_slug` (`tenant_id`, `slug`),
    KEY `idx_forms_tenant_id` (`tenant_id`),
    KEY `idx_forms_user_id` (`user_id`),
    KEY `idx_forms_status` (`status`),
    KEY `idx_forms_type` (`type`),
    KEY `idx_forms_parent_form_id` (`parent_form_id`),
    KEY `idx_forms_created_at` (`created_at`),
    KEY `idx_forms_published_at` (`published_at`),
    CONSTRAINT `fk_forms_tenant`      FOREIGN KEY (`tenant_id`)      REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_forms_user`        FOREIGN KEY (`user_id`)        REFERENCES `users`   (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_forms_parent_form` FOREIGN KEY (`parent_form_id`) REFERENCES `forms`   (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Central forms table';

-- ---------------------------------------------------------------------------
-- Table: form_fields
-- Fields belonging to a form
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS `form_fields`;
CREATE TABLE `form_fields` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `form_id`     BIGINT UNSIGNED NOT NULL,
    `type`        ENUM(
                      'name','email','phone','company','address','cpf_cnpj',
                      'text','textarea','radio','select','checkbox','password',
                      'url','social_media','username','signature',
                      'datepicker','file_upload',
                      'opinion_scale','rating','picture_choice',
                      'calendly','hidden',
                      'paragraph','divider','section_break'
                  ) NOT NULL,
    `label`       VARCHAR(500)    NULL,
    `description` TEXT            NULL,
    `placeholder` VARCHAR(255)    NULL,
    `required`    TINYINT(1)      NOT NULL DEFAULT 0,
    `settings`    JSON            NULL     COMMENT 'validation_rules, mask, options, min, max, step, default_value, conditional_logic, file_allowed_types, file_max_size, rating_icon, rating_max, opinion_min, opinion_max, social_type, address_autocomplete, name_format, country_code, position_x, position_y, width, height, connections',
    `sort_order`  INT UNSIGNED    NOT NULL DEFAULT 0,
    `group_id`    VARCHAR(100)    NULL     COMMENT 'Group identifier for sections/steps',
    `created_at`  TIMESTAMP       NULL DEFAULT NULL,
    `updated_at`  TIMESTAMP       NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_form_fields_form_id` (`form_id`),
    KEY `idx_form_fields_type` (`type`),
    KEY `idx_form_fields_sort_order` (`sort_order`),
    KEY `idx_form_fields_group_id` (`group_id`),
    CONSTRAINT `fk_form_fields_form` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Fields belonging to a form';

-- ---------------------------------------------------------------------------
-- Table: form_field_options
-- Predefined options for radio, select, checkbox, picture_choice fields
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS `form_field_options`;
CREATE TABLE `form_field_options` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `field_id`   BIGINT UNSIGNED NOT NULL,
    `label`      VARCHAR(500)    NOT NULL,
    `value`      VARCHAR(500)    NULL,
    `image_url`  VARCHAR(500)    NULL,
    `sort_order` INT UNSIGNED    NOT NULL DEFAULT 0,
    `is_default` TINYINT(1)      NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_form_field_options_field_id` (`field_id`),
    KEY `idx_form_field_options_sort_order` (`sort_order`),
    CONSTRAINT `fk_form_field_options_field` FOREIGN KEY (`field_id`) REFERENCES `form_fields` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Predefined options for choice-type fields';

-- ---------------------------------------------------------------------------
-- Table: form_connections
-- Flow builder logic connections between fields
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS `form_connections`;
CREATE TABLE `form_connections` (
    `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `form_id`         BIGINT UNSIGNED NOT NULL,
    `source_field_id` BIGINT UNSIGNED NOT NULL,
    `target_field_id` BIGINT UNSIGNED NOT NULL,
    `condition_type`  ENUM('equals','not_equals','contains','gt','lt','always') NOT NULL DEFAULT 'always',
    `condition_value` VARCHAR(500)    NULL,
    `sort_order`      INT UNSIGNED    NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_form_connections_form_id` (`form_id`),
    KEY `idx_form_connections_source` (`source_field_id`),
    KEY `idx_form_connections_target` (`target_field_id`),
    CONSTRAINT `fk_form_connections_form`   FOREIGN KEY (`form_id`)         REFERENCES `forms`       (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_form_connections_source` FOREIGN KEY (`source_field_id`) REFERENCES `form_fields` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_form_connections_target` FOREIGN KEY (`target_field_id`) REFERENCES `form_fields` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Flow builder logic connections between fields';

-- ============================================================================
-- ENTRY / SUBMISSION TABLES
-- ============================================================================

-- ---------------------------------------------------------------------------
-- Table: entries
-- Form submissions
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS `entries`;
CREATE TABLE `entries` (
    `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `form_id`          BIGINT UNSIGNED NOT NULL,
    `tenant_id`        BIGINT UNSIGNED NOT NULL,
    `status`           ENUM('complete','partial','abandoned') NOT NULL DEFAULT 'partial',
    `ip_address`       VARCHAR(45)     NULL,
    `user_agent`       TEXT            NULL,
    `referrer`         VARCHAR(2000)   NULL,
    `utm_source`       VARCHAR(255)    NULL,
    `utm_medium`       VARCHAR(255)    NULL,
    `utm_campaign`     VARCHAR(255)    NULL,
    `utm_term`         VARCHAR(255)    NULL,
    `utm_content`      VARCHAR(255)    NULL,
    `device_type`      ENUM('desktop','mobile','tablet') NULL,
    `browser`          VARCHAR(100)    NULL,
    `os`               VARCHAR(100)    NULL,
    `country`          VARCHAR(100)    NULL,
    `city`             VARCHAR(100)    NULL,
    `unique_token`     VARCHAR(191)    NULL     COMMENT 'Token for save & continue / unique link',
    `started_at`       TIMESTAMP       NULL DEFAULT NULL,
    `completed_at`     TIMESTAMP       NULL DEFAULT NULL,
    `duration_seconds` INT UNSIGNED    NULL,
    `score`            DECIMAL(8,2)    NULL     COMMENT 'Calculated lead score',
    `ai_analysis`      JSON            NULL     COMMENT 'AI-generated analysis payload',
    `ai_temperature`   ENUM('cold','warm','hot') NULL COMMENT 'AI lead temperature',
    `created_at`       TIMESTAMP       NULL DEFAULT NULL,
    `updated_at`       TIMESTAMP       NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_entries_unique_token` (`unique_token`),
    KEY `idx_entries_form_id` (`form_id`),
    KEY `idx_entries_tenant_id` (`tenant_id`),
    KEY `idx_entries_status` (`status`),
    KEY `idx_entries_created_at` (`created_at`),
    KEY `idx_entries_completed_at` (`completed_at`),
    KEY `idx_entries_ai_temperature` (`ai_temperature`),
    KEY `idx_entries_ip_address` (`ip_address`),
    CONSTRAINT `fk_entries_form`   FOREIGN KEY (`form_id`)   REFERENCES `forms`   (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_entries_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Form submissions / entries';

-- ---------------------------------------------------------------------------
-- Table: entry_values
-- Individual field values within an entry
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS `entry_values`;
CREATE TABLE `entry_values` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `entry_id`   BIGINT UNSIGNED NOT NULL,
    `field_id`   BIGINT UNSIGNED NOT NULL,
    `field_type` VARCHAR(50)     NOT NULL COMMENT 'Denormalised for quick reads',
    `value`      TEXT            NULL,
    `file_path`  VARCHAR(500)    NULL,
    `created_at` TIMESTAMP       NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_entry_values_entry_id` (`entry_id`),
    KEY `idx_entry_values_field_id` (`field_id`),
    CONSTRAINT `fk_entry_values_entry` FOREIGN KEY (`entry_id`) REFERENCES `entries`     (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_entry_values_field` FOREIGN KEY (`field_id`) REFERENCES `form_fields` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Individual field values within an entry';

-- ---------------------------------------------------------------------------
-- Table: entry_notes
-- Internal notes on entries by team members
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS `entry_notes`;
CREATE TABLE `entry_notes` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `entry_id`   BIGINT UNSIGNED NOT NULL,
    `user_id`    BIGINT UNSIGNED NOT NULL,
    `note`       TEXT            NOT NULL,
    `created_at` TIMESTAMP       NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_entry_notes_entry_id` (`entry_id`),
    KEY `idx_entry_notes_user_id` (`user_id`),
    CONSTRAINT `fk_entry_notes_entry` FOREIGN KEY (`entry_id`) REFERENCES `entries` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_entry_notes_user`  FOREIGN KEY (`user_id`)  REFERENCES `users`   (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Internal notes on entries';

-- ---------------------------------------------------------------------------
-- Table: file_uploads
-- Uploaded files linked to entries
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS `file_uploads`;
CREATE TABLE `file_uploads` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id`     BIGINT UNSIGNED NOT NULL,
    `entry_id`      BIGINT UNSIGNED NULL,
    `field_id`      BIGINT UNSIGNED NULL,
    `original_name` VARCHAR(500)    NOT NULL,
    `stored_name`   VARCHAR(500)    NOT NULL,
    `path`          VARCHAR(1000)   NOT NULL,
    `mime_type`     VARCHAR(255)    NULL,
    `size`          BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'File size in bytes',
    `created_at`    TIMESTAMP       NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_file_uploads_tenant_id` (`tenant_id`),
    KEY `idx_file_uploads_entry_id` (`entry_id`),
    KEY `idx_file_uploads_field_id` (`field_id`),
    CONSTRAINT `fk_file_uploads_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants`     (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_file_uploads_entry`  FOREIGN KEY (`entry_id`)  REFERENCES `entries`      (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_file_uploads_field`  FOREIGN KEY (`field_id`)  REFERENCES `form_fields`  (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Uploaded files linked to entries';

-- ============================================================================
-- ANALYTICS TABLES
-- ============================================================================

-- ---------------------------------------------------------------------------
-- Table: form_analytics
-- Daily aggregated form-level analytics
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS `form_analytics`;
CREATE TABLE `form_analytics` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `form_id`        BIGINT UNSIGNED NOT NULL,
    `date`           DATE            NOT NULL,
    `views`          INT UNSIGNED    NOT NULL DEFAULT 0,
    `starts`         INT UNSIGNED    NOT NULL DEFAULT 0,
    `completions`    INT UNSIGNED    NOT NULL DEFAULT 0,
    `abandons`       INT UNSIGNED    NOT NULL DEFAULT 0,
    `avg_duration`   INT UNSIGNED    NULL     COMMENT 'Average seconds to complete',
    `device_desktop` INT UNSIGNED    NOT NULL DEFAULT 0,
    `device_mobile`  INT UNSIGNED    NOT NULL DEFAULT 0,
    `device_tablet`  INT UNSIGNED    NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_form_analytics_form_date` (`form_id`, `date`),
    KEY `idx_form_analytics_date` (`date`),
    CONSTRAINT `fk_form_analytics_form` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Daily aggregated form-level analytics';

-- ---------------------------------------------------------------------------
-- Table: field_analytics
-- Daily aggregated field-level analytics
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS `field_analytics`;
CREATE TABLE `field_analytics` (
    `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `field_id`         BIGINT UNSIGNED NOT NULL,
    `form_id`          BIGINT UNSIGNED NOT NULL,
    `date`             DATE            NOT NULL,
    `views`            INT UNSIGNED    NOT NULL DEFAULT 0,
    `interactions`     INT UNSIGNED    NOT NULL DEFAULT 0,
    `drop_offs`        INT UNSIGNED    NOT NULL DEFAULT 0,
    `avg_time_seconds` INT UNSIGNED    NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_field_analytics_field_date` (`field_id`, `date`),
    KEY `idx_field_analytics_form_id` (`form_id`),
    KEY `idx_field_analytics_date` (`date`),
    CONSTRAINT `fk_field_analytics_field` FOREIGN KEY (`field_id`) REFERENCES `form_fields` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_field_analytics_form`  FOREIGN KEY (`form_id`)  REFERENCES `forms`       (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Daily aggregated field-level analytics';

-- ---------------------------------------------------------------------------
-- Table: form_visits
-- Raw visit / page-view events
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS `form_visits`;
CREATE TABLE `form_visits` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `form_id`      BIGINT UNSIGNED NOT NULL,
    `visitor_id`   VARCHAR(191)    NULL     COMMENT 'Browser fingerprint or cookie-based ID',
    `ip_address`   VARCHAR(45)     NULL,
    `user_agent`   TEXT            NULL,
    `referrer`     VARCHAR(2000)   NULL,
    `utm_source`   VARCHAR(255)    NULL,
    `utm_medium`   VARCHAR(255)    NULL,
    `utm_campaign` VARCHAR(255)    NULL,
    `country`      VARCHAR(100)    NULL,
    `city`         VARCHAR(100)    NULL,
    `device_type`  ENUM('desktop','mobile','tablet') NULL,
    `created_at`   TIMESTAMP       NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_form_visits_form_id` (`form_id`),
    KEY `idx_form_visits_visitor_id` (`visitor_id`),
    KEY `idx_form_visits_created_at` (`created_at`),
    KEY `idx_form_visits_country` (`country`),
    CONSTRAINT `fk_form_visits_form` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Raw visit / page-view events';

-- ============================================================================
-- INTEGRATION TABLES
-- ============================================================================

-- ---------------------------------------------------------------------------
-- Table: integrations
-- Tenant-level integration credentials / configs
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS `integrations`;
CREATE TABLE `integrations` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id`  BIGINT UNSIGNED NOT NULL,
    `type`       ENUM(
                     'webhook','google_sheets','crm','zapier',
                     'facebook_pixel','google_analytics','google_tag_manager',
                     'mailchimp','hubspot','stripe','paypal','custom'
                 ) NOT NULL,
    `name`       VARCHAR(255)    NOT NULL,
    `settings`   JSON            NULL     COMMENT 'Credentials, tokens, config',
    `status`     ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP       NULL DEFAULT NULL,
    `updated_at` TIMESTAMP       NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_integrations_tenant_id` (`tenant_id`),
    KEY `idx_integrations_type` (`type`),
    KEY `idx_integrations_status` (`status`),
    CONSTRAINT `fk_integrations_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Tenant-level integration credentials';

-- ---------------------------------------------------------------------------
-- Table: form_integrations
-- Links a form to a tenant integration with form-specific overrides
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS `form_integrations`;
CREATE TABLE `form_integrations` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `form_id`        BIGINT UNSIGNED NOT NULL,
    `integration_id` BIGINT UNSIGNED NOT NULL,
    `settings`       JSON            NULL     COMMENT 'Form-specific integration overrides',
    `is_active`      TINYINT(1)      NOT NULL DEFAULT 1,
    `created_at`     TIMESTAMP       NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_form_integrations_form_integration` (`form_id`, `integration_id`),
    KEY `idx_form_integrations_integration_id` (`integration_id`),
    CONSTRAINT `fk_form_integrations_form`        FOREIGN KEY (`form_id`)        REFERENCES `forms`        (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_form_integrations_integration` FOREIGN KEY (`integration_id`) REFERENCES `integrations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Form-to-integration mapping with overrides';

-- ---------------------------------------------------------------------------
-- Table: webhooks
-- Webhook endpoints
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS `webhooks`;
CREATE TABLE `webhooks` (
    `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id`        BIGINT UNSIGNED NOT NULL,
    `form_id`          BIGINT UNSIGNED NULL     COMMENT 'NULL = fires for all tenant forms',
    `url`              VARCHAR(2000)   NOT NULL,
    `events`           JSON            NOT NULL COMMENT 'Array: form.submitted, form.partial, entry.updated',
    `secret`           VARCHAR(255)    NULL     COMMENT 'HMAC signing secret',
    `headers`          JSON            NULL     COMMENT 'Custom HTTP headers',
    `is_active`        TINYINT(1)      NOT NULL DEFAULT 1,
    `last_triggered_at` TIMESTAMP      NULL DEFAULT NULL,
    `last_status_code`  SMALLINT UNSIGNED NULL,
    `created_at`       TIMESTAMP       NULL DEFAULT NULL,
    `updated_at`       TIMESTAMP       NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_webhooks_tenant_id` (`tenant_id`),
    KEY `idx_webhooks_form_id` (`form_id`),
    KEY `idx_webhooks_is_active` (`is_active`),
    CONSTRAINT `fk_webhooks_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_webhooks_form`   FOREIGN KEY (`form_id`)   REFERENCES `forms`   (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Webhook endpoints';

-- ---------------------------------------------------------------------------
-- Table: webhook_logs
-- Webhook delivery attempt logs
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS `webhook_logs`;
CREATE TABLE `webhook_logs` (
    `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `webhook_id`       BIGINT UNSIGNED NOT NULL,
    `entry_id`         BIGINT UNSIGNED NULL,
    `request_url`      VARCHAR(2000)   NOT NULL,
    `request_headers`  JSON            NULL,
    `request_body`     TEXT            NULL,
    `response_code`    SMALLINT UNSIGNED NULL,
    `response_body`    TEXT            NULL,
    `duration_ms`      INT UNSIGNED    NULL,
    `created_at`       TIMESTAMP       NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_webhook_logs_webhook_id` (`webhook_id`),
    KEY `idx_webhook_logs_entry_id` (`entry_id`),
    KEY `idx_webhook_logs_created_at` (`created_at`),
    CONSTRAINT `fk_webhook_logs_webhook` FOREIGN KEY (`webhook_id`) REFERENCES `webhooks` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_webhook_logs_entry`   FOREIGN KEY (`entry_id`)   REFERENCES `entries`  (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Webhook delivery attempt logs';

-- ============================================================================
-- PIXEL / TAGS TABLE
-- ============================================================================

-- ---------------------------------------------------------------------------
-- Table: tracking_pixels
-- Third-party tracking pixels / tags
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS `tracking_pixels`;
CREATE TABLE `tracking_pixels` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id`  BIGINT UNSIGNED NOT NULL,
    `form_id`    BIGINT UNSIGNED NULL     COMMENT 'NULL = applies to all tenant forms',
    `type`       ENUM('facebook_pixel','google_analytics','google_tag_manager','tiktok_pixel','custom') NOT NULL,
    `pixel_id`   VARCHAR(255)    NOT NULL COMMENT 'The external pixel/tag identifier',
    `settings`   JSON            NULL,
    `is_active`  TINYINT(1)      NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP       NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_tracking_pixels_tenant_id` (`tenant_id`),
    KEY `idx_tracking_pixels_form_id` (`form_id`),
    KEY `idx_tracking_pixels_type` (`type`),
    CONSTRAINT `fk_tracking_pixels_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_tracking_pixels_form`   FOREIGN KEY (`form_id`)   REFERENCES `forms`   (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Third-party tracking pixels / tags';

-- ============================================================================
-- AUDIT TABLES
-- ============================================================================

-- ---------------------------------------------------------------------------
-- Table: audit_logs
-- Immutable audit trail
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id`   BIGINT UNSIGNED NULL,
    `user_id`     BIGINT UNSIGNED NULL,
    `action`      VARCHAR(100)    NOT NULL COMMENT 'e.g. created, updated, deleted, login, export',
    `entity_type` VARCHAR(100)    NULL     COMMENT 'e.g. form, entry, user, integration',
    `entity_id`   BIGINT UNSIGNED NULL,
    `old_values`  JSON            NULL,
    `new_values`  JSON            NULL,
    `ip_address`  VARCHAR(45)     NULL,
    `user_agent`  TEXT            NULL,
    `created_at`  TIMESTAMP       NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_audit_logs_tenant_id` (`tenant_id`),
    KEY `idx_audit_logs_user_id` (`user_id`),
    KEY `idx_audit_logs_action` (`action`),
    KEY `idx_audit_logs_entity` (`entity_type`, `entity_id`),
    KEY `idx_audit_logs_created_at` (`created_at`),
    CONSTRAINT `fk_audit_logs_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_audit_logs_user`   FOREIGN KEY (`user_id`)   REFERENCES `users`   (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Immutable audit trail';

-- ============================================================================
-- API TABLES
-- ============================================================================

-- ---------------------------------------------------------------------------
-- Table: api_tokens
-- Personal / programmatic API tokens
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS `api_tokens`;
CREATE TABLE `api_tokens` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id`    BIGINT UNSIGNED NOT NULL,
    `user_id`      BIGINT UNSIGNED NOT NULL,
    `name`         VARCHAR(255)    NOT NULL,
    `token`        VARCHAR(191)    NOT NULL COMMENT 'SHA-256 hashed token',
    `abilities`    JSON            NULL     COMMENT 'Array of allowed scopes',
    `last_used_at` TIMESTAMP       NULL DEFAULT NULL,
    `expires_at`   TIMESTAMP       NULL DEFAULT NULL,
    `created_at`   TIMESTAMP       NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_api_tokens_token` (`token`),
    KEY `idx_api_tokens_tenant_id` (`tenant_id`),
    KEY `idx_api_tokens_user_id` (`user_id`),
    KEY `idx_api_tokens_expires_at` (`expires_at`),
    CONSTRAINT `fk_api_tokens_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_api_tokens_user`   FOREIGN KEY (`user_id`)   REFERENCES `users`   (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Personal / programmatic API tokens';

-- ---------------------------------------------------------------------------
-- Table: api_logs
-- API request log for rate limiting and debugging
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS `api_logs`;
CREATE TABLE `api_logs` (
    `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id`       BIGINT UNSIGNED NULL,
    `api_token_id`    BIGINT UNSIGNED NULL,
    `method`          VARCHAR(10)     NOT NULL,
    `endpoint`        VARCHAR(500)    NOT NULL,
    `request_body`    TEXT            NULL,
    `response_code`   SMALLINT UNSIGNED NOT NULL,
    `response_time_ms` INT UNSIGNED   NULL,
    `ip_address`      VARCHAR(45)     NULL,
    `created_at`      TIMESTAMP       NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_api_logs_tenant_id` (`tenant_id`),
    KEY `idx_api_logs_api_token_id` (`api_token_id`),
    KEY `idx_api_logs_created_at` (`created_at`),
    KEY `idx_api_logs_endpoint` (`endpoint`(191)),
    CONSTRAINT `fk_api_logs_tenant`    FOREIGN KEY (`tenant_id`)    REFERENCES `tenants`    (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_api_logs_api_token` FOREIGN KEY (`api_token_id`) REFERENCES `api_tokens` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='API request log';

-- ============================================================================
-- RE-ENABLE FOREIGN KEY CHECKS
-- ============================================================================
SET FOREIGN_KEY_CHECKS = 1;
