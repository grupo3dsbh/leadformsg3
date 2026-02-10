<?php
namespace Core;

class Autoloader
{
    public static function register(): void
    {
        spl_autoload_register([self::class, 'load']);
    }

    public static function load(string $class): void
    {
        // Convert namespace to path
        $path = str_replace('\\', '/', $class);

        // Map namespaces to directories
        $maps = [
            'Core/' => ROOT_PATH . '/core/',
            'App/Controllers/' => ROOT_PATH . '/app/Controllers/',
            'App/Models/' => ROOT_PATH . '/app/Models/',
            'App/Middleware/' => ROOT_PATH . '/app/Middleware/',
            'App/Helpers/' => ROOT_PATH . '/app/Helpers/',
        ];

        foreach ($maps as $namespace => $dir) {
            if (str_starts_with($path, $namespace)) {
                $file = $dir . substr($path, strlen($namespace)) . '.php';
                if (file_exists($file)) {
                    require_once $file;
                    return;
                }
            }
        }
    }
}
