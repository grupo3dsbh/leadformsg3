<?php
/**
 * LeadForm SaaS - Entry Point
 * Conversational Form Builder Platform
 */

define('ROOT_PATH', __DIR__);
define('START_TIME', microtime(true));

// Autoload
require_once ROOT_PATH . '/core/Autoloader.php';
Core\Autoloader::register();

// Load environment
require_once ROOT_PATH . '/config/app.php';

// Load helpers
require_once ROOT_PATH . '/app/Helpers/functions.php';

// Initialize application
$app = new Core\App();
$app->run();
