<?php

declare(strict_types=1);

namespace Core;

/**
 * HTTP Router
 *
 * Supports GET, POST, PUT, PATCH, DELETE methods with:
 *  - Named parameters  e.g. /users/{id}
 *  - Optional parameters  e.g. /users/{id?}
 *  - Middleware per-route or per-group
 *  - Route groups with shared prefix and options
 *  - Controller@method string resolution
 *
 * Routes are loaded from config/routes.php via the App bootstrap.
 */
class Router
{
    // ------------------------------------------------------------------
    // Route storage
    // ------------------------------------------------------------------

    /** @var array<string, list<array{pattern: string, handler: mixed, params: list<string>, middleware: list<string>}>> */
    private array $routes = [
        'GET'    => [],
        'POST'   => [],
        'PUT'    => [],
        'PATCH'  => [],
        'DELETE' => [],
    ];

    /** @var list<array{prefix: string, middleware: list<string>}> */
    private array $groupStack = [];

    /** @var array<string, string> Named routes: name => pattern */
    private array $namedRoutes = [];

    /** @var array<string, class-string<Middleware>> Registered middleware aliases */
    private array $middlewareAliases = [
        'auth'       => \App\Middleware\AuthMiddleware::class,
        'superadmin' => \App\Middleware\SuperAdminMiddleware::class,
        'api'        => \App\Middleware\ApiMiddleware::class,
        'csrf'       => \App\Middleware\CsrfMiddleware::class,
        'guest'      => \App\Middleware\GuestMiddleware::class,
        'throttle'   => \App\Middleware\ThrottleMiddleware::class,
    ];

    // ------------------------------------------------------------------
    // Convenience registration methods
    // ------------------------------------------------------------------

    public function get(string $uri, mixed $handler, array $options = []): static
    {
        return $this->addRoute('GET', $uri, $handler, $options);
    }

    public function post(string $uri, mixed $handler, array $options = []): static
    {
        return $this->addRoute('POST', $uri, $handler, $options);
    }

    public function put(string $uri, mixed $handler, array $options = []): static
    {
        return $this->addRoute('PUT', $uri, $handler, $options);
    }

    public function patch(string $uri, mixed $handler, array $options = []): static
    {
        return $this->addRoute('PATCH', $uri, $handler, $options);
    }

    public function delete(string $uri, mixed $handler, array $options = []): static
    {
        return $this->addRoute('DELETE', $uri, $handler, $options);
    }

    /**
     * Register a route for multiple HTTP methods at once.
     *
     * @param list<string> $methods
     */
    public function match(array $methods, string $uri, mixed $handler, array $options = []): static
    {
        foreach ($methods as $method) {
            $this->addRoute(strtoupper($method), $uri, $handler, $options);
        }

        return $this;
    }

    /**
     * Register a resource controller (index, create, store, show, edit, update, destroy).
     */
    public function resource(string $uri, string $controller, array $options = []): static
    {
        $param = rtrim($uri, '/');
        $singular = basename($param);

        $this->get($uri, "{$controller}@index", $options);
        $this->get("{$uri}/create", "{$controller}@create", $options);
        $this->post($uri, "{$controller}@store", $options);
        $this->get("{$uri}/{{$singular}}", "{$controller}@show", $options);
        $this->get("{$uri}/{{$singular}}/edit", "{$controller}@edit", $options);
        $this->put("{$uri}/{{$singular}}", "{$controller}@update", $options);
        $this->delete("{$uri}/{{$singular}}", "{$controller}@destroy", $options);

        return $this;
    }

    // ------------------------------------------------------------------
    // Route groups
    // ------------------------------------------------------------------

    /**
     * Group routes under a shared prefix with shared options (middleware, etc.).
     */
    public function group(string $prefix, array $options, \Closure $callback): static
    {
        $middleware = (array) ($options['middleware'] ?? []);

        $this->groupStack[] = [
            'prefix'     => $prefix,
            'middleware'  => $middleware,
        ];

        $callback($this);

        array_pop($this->groupStack);

        return $this;
    }

    // ------------------------------------------------------------------
    // Named routes
    // ------------------------------------------------------------------

    /**
     * Assign a name to the most recently added route (across all methods).
     */
    public function name(string $name): static
    {
        // Find the last added route across all methods.
        foreach (array_reverse(array_keys($this->routes)) as $method) {
            $count = count($this->routes[$method]);
            if ($count > 0) {
                $last = $this->routes[$method][$count - 1];
                $this->namedRoutes[$name] = $last['pattern'];
                break;
            }
        }

        return $this;
    }

    /**
     * Generate a URL from a named route, substituting parameters.
     */
    public function url(string $name, array $params = []): string
    {
        if (!isset($this->namedRoutes[$name])) {
            throw new \InvalidArgumentException("No route named [{$name}].");
        }

        $url = $this->namedRoutes[$name];

        foreach ($params as $key => $value) {
            $url = str_replace("{{$key}}", (string) $value, $url);
            $url = str_replace("{{$key}?}", (string) $value, $url);
        }

        // Remove any remaining optional placeholders.
        $url = preg_replace('/\{[a-zA-Z_]+\?\}/', '', $url);

        return rtrim($url, '/') ?: '/';
    }

    // ------------------------------------------------------------------
    // Middleware alias registration
    // ------------------------------------------------------------------

    public function aliasMiddleware(string $alias, string $class): static
    {
        $this->middlewareAliases[$alias] = $class;
        return $this;
    }

    // ------------------------------------------------------------------
    // Internal: add a single route
    // ------------------------------------------------------------------

    private function addRoute(string $method, string $uri, mixed $handler, array $options = []): static
    {
        $prefix     = $this->currentPrefix();
        $middleware  = $this->currentMiddleware();

        // Merge route-level middleware.
        if (isset($options['middleware'])) {
            $middleware = array_merge($middleware, (array) $options['middleware']);
        }

        $fullUri = rtrim($prefix . '/' . ltrim($uri, '/'), '/') ?: '/';

        // Extract parameter names from the URI pattern.
        $paramNames = [];
        preg_match_all('/\{([a-zA-Z_]+)\??}/', $fullUri, $matches);
        if (!empty($matches[1])) {
            $paramNames = $matches[1];
        }

        // Convert the URI pattern into a regex.
        $regex = $this->compilePattern($fullUri);

        $this->routes[$method][] = [
            'pattern'    => $fullUri,
            'regex'      => $regex,
            'handler'    => $handler,
            'params'     => $paramNames,
            'middleware'  => array_unique($middleware),
        ];

        return $this;
    }

    /**
     * Build the current accumulated prefix from the group stack.
     */
    private function currentPrefix(): string
    {
        $prefix = '';
        foreach ($this->groupStack as $group) {
            $prefix .= $group['prefix'];
        }

        return $prefix;
    }

    /**
     * Collect all middleware from the current group stack.
     *
     * @return list<string>
     */
    private function currentMiddleware(): array
    {
        $middleware = [];
        foreach ($this->groupStack as $group) {
            $middleware = array_merge($middleware, $group['middleware']);
        }

        return $middleware;
    }

    /**
     * Turn a URI pattern like /users/{id}/posts/{slug?} into a regex.
     */
    private function compilePattern(string $pattern): string
    {
        // Required parameters: {name}
        $regex = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $pattern);

        // Optional parameters: {name?}
        $regex = preg_replace('/\{([a-zA-Z_]+)\?\}/', '(?P<$1>[^/]*)', $regex);

        return '#^' . $regex . '$#u';
    }

    // ------------------------------------------------------------------
    // Dispatch
    // ------------------------------------------------------------------

    /**
     * Find a matching route and execute its handler (after middleware).
     */
    public function dispatch(string $method, string $uri): mixed
    {
        // Normalize: strip trailing slash (except root).
        $uri = $uri !== '/' ? rtrim($uri, '/') : '/';

        // Support method override via _method field (for HTML forms).
        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }

        if (!isset($this->routes[$method])) {
            throw new \Core\Exceptions\NotFoundException("Method not allowed: {$method}");
        }

        foreach ($this->routes[$method] as $route) {
            if (preg_match($route['regex'], $uri, $matches)) {
                // Extract named captures.
                $params = [];
                foreach ($route['params'] as $name) {
                    $params[$name] = $matches[$name] ?? null;
                }

                // Run middleware pipeline, then call the handler.
                return $this->runMiddleware(
                    $route['middleware'],
                    fn () => $this->callHandler($route['handler'], $params),
                );
            }
        }

        // No route matched.
        throw new \Core\Exceptions\NotFoundException(
            "No route matched: {$method} {$uri}"
        );
    }

    // ------------------------------------------------------------------
    // Middleware pipeline
    // ------------------------------------------------------------------

    /**
     * Execute the middleware stack, passing control to $final at the end.
     */
    private function runMiddleware(array $middlewareList, \Closure $final): mixed
    {
        if (empty($middlewareList)) {
            return $final();
        }

        // Build a nested pipeline.
        $pipeline = array_reduce(
            array_reverse($middlewareList),
            function (\Closure $next, string $alias) {
                return function () use ($alias, $next) {
                    $class = $this->middlewareAliases[$alias] ?? $alias;

                    if (!class_exists($class)) {
                        // If class doesn't exist, skip it silently in dev.
                        return $next();
                    }

                    /** @var Middleware $instance */
                    $instance = new $class();

                    return $instance->handle($next);
                };
            },
            $final,
        );

        return $pipeline();
    }

    // ------------------------------------------------------------------
    // Handler resolution
    // ------------------------------------------------------------------

    /**
     * Resolve and call a route handler.
     *
     * Supported handler types:
     *   - 'Controller@method'  (string)
     *   - Closure
     *   - [ControllerClass, 'method']  (array callable)
     */
    private function callHandler(mixed $handler, array $params): mixed
    {
        if ($handler instanceof \Closure) {
            return call_user_func_array($handler, $params);
        }

        if (is_string($handler) && str_contains($handler, '@')) {
            [$controllerName, $method] = explode('@', $handler, 2);

            $controllerClass = $this->resolveControllerClass($controllerName);

            if (!class_exists($controllerClass)) {
                throw new \RuntimeException("Controller not found: {$controllerClass}");
            }

            $controller = new $controllerClass();

            if (!method_exists($controller, $method)) {
                throw new \RuntimeException(
                    "Method [{$method}] not found on controller [{$controllerClass}]."
                );
            }

            return call_user_func_array([$controller, $method], $params);
        }

        if (is_array($handler) && count($handler) === 2) {
            return call_user_func_array($handler, $params);
        }

        throw new \RuntimeException('Invalid route handler.');
    }

    /**
     * Resolve a short controller name to its fully-qualified class name.
     *
     * "HomeController"                => \App\Controllers\HomeController
     * "Admin\\DashboardController"    => \App\Controllers\Admin\DashboardController
     * "Api\\FormApiController"        => \App\Controllers\Api\FormApiController
     */
    private function resolveControllerClass(string $name): string
    {
        // Already fully qualified.
        if (str_starts_with($name, '\\')) {
            return $name;
        }

        return '\\App\\Controllers\\' . $name;
    }

    // ------------------------------------------------------------------
    // Debugging / introspection
    // ------------------------------------------------------------------

    /**
     * Return all registered routes (useful for debugging or a route:list command).
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }
}
