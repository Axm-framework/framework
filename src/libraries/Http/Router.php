<?php

namespace Http;

use ReflectionFunction;
use RuntimeException;
use ReflectionException;
use ReflectionParameter;
use Exception;

/**
 * Class Router
 *
 * @author  Juan Cristobal <juancristobalgd1@gmail.com>
 * @package System
 */
class Router
{
    private $request;
    private $response;
    private $app;
    private static $routeMap = [];
    private $callback;
    private $uri;
    private $method;
    /**
     * Middleware registered for the current route.
     * @var string|null
     */
    private $currentMiddleware;

    /**
     * Registered route names.
     * @var array
     */
    private $routeNames = [];

    /**
     * All of the verbs supported by the router.
     */
    public static $verbs = ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];
    private string $prefix;

    /**
     * Constructor.
     * Initializes the instance with the application's request and response objects.
     */
    public function __construct()
    {
        $this->prefix ??= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');

        // Get the application instance
        $this->app = app();
    }

    /**
     * Add a route to the underlying route collection.
     */
    public static function addRoute(array|string $methods, string $route, array|string|callable|null $callback)
    {
        $methods = (array) $methods;
        foreach ($methods as $method)
            static::$routeMap[$method][trim($route)] = $callback;

        return new static;
    }

    /**
     * Register a new GET route with the router.
     */
    public static function get(string $route, array|string|callable|null $callback): Router
    {
        return static::addRoute(['GET', 'HEAD'], $route, $callback);
    }

    /**
     * Register a new POST route with the router.
     */
    public static function post(string $route, array|string|callable|null $callback): Router
    {
        return static::addRoute('POST', $route, $callback);
    }

    /**
     * Register a new PUT route with the router.
     */
    public static function put(string $route, array|string|callable|null $callback): Router
    {
        return static::addRoute('PUT', $route, $callback);
    }

    /**
     * Register a new PATCH route with the router.
     */
    public static function patch(string $route, array|string|callable|null $callback): Router
    {
        return static::addRoute('PATCH', $route, $callback);
    }

    /**
     * Register a new DELETE route with the router.
     */
    public static function delete(string $route, array|string|callable|null $callback): Router
    {
        return static::addRoute('DELETE', $route, $callback);
    }

    /**
     * Register a new OPTIONS route with the router.
     */
    public static function options(string $route, array|string|callable|null $callback): Router
    {
        return static::addRoute('OPTIONS', $route, $callback);
    }

    /**
     * Register a new route responding to all verbs.
     */
    public static function any(string $route, array|string|callable|null $callback): Router
    {
        return static::addRoute(static::$verbs, $route, $callback);
    }

    /**
     * Register a new route that responds to all verbs.
     */
    public static function match(array|string $methods, string $route, array|string|callable|null $callback): Router
    {
        return static::addRoute(array_map('strtoupper', (array) $methods), $route, $callback);
    }

    /**
     * Return one route defined for user
     */
    public function getRoutes(string $method): array
    {
        return static::$routeMap[$method] ?? [];
    }

    /**
     * Return all routes defined
     */
    public function getAllRoutes(): array
    {
        return static::$routeMap ?? [];
    }

    /**
     * Isset route.
     */
    public function isRoute(string $routeName, string $method = 'GET'): bool
    {
        return isset(static::$routeMap[$method][$routeName]);
    }

    /**
     * Search for Middlewares in controllers and run them.
     */
    private function executeMiddlewares(array $middlewares = [])
    {
        foreach ($middlewares as $middleware)
            $middleware->execute();
    }

    /**
     * Registers a middleware for the current route.
     */
    public static function middleware(string $middleware): self
    {
        // if (!class_exists($middleware))
        //     throw new RuntimeException(sprintf(' [ %s ] middleware does not exist', $middleware));

        app()
            ->controller
            ->registerMiddleware(new $middleware);

        return new static;
    }

    /**
     * Allows defining groups/prefixes of routes with shared properties (middleware, namespace, etc).
     */
    public static function group(string $prefix, callable $callback): self
    {
        $currentRoutes = static::$routeMap;

        static::$routeMap = [];
        call_user_func($callback);
        $groupedRoutes = static::$routeMap;

        // Restore the previous state of the routes
        static::$routeMap = array_merge_recursive($currentRoutes, $groupedRoutes);

        return new static;
    }

    /**
     * Returns the rendered view for a given route.
     */
    public static function view(string $route, string $view, array $params = [])
    {
        static::get($route, static fn() => view($view, $params, true));
    }

    /**
     * Dispatches the request, resolving the route callback or handling unregistered routes.
     */
    public function dispatch()
    {
        $this->method = strtoupper($_SERVER['REQUEST_METHOD']);
        $this->uri = $this->getUri();
        $this->callback = $this->getRouteCallback($this->method, $this->uri);

        return $this->callback ? $this->resolve($this->callback) : $this->notRouteRegistered();
    }

    /**
     *  Parse the current uri from REQUEST_URI server variable.
     */
    public function getUri(): string
    {
        $uri = strtok(rawurldecode($_SERVER['REQUEST_URI'] ?? '/'), '?');
        if ($this->prefix !== '/') {  // Delete the base path
            $uri = str_replace($this->prefix, '', $uri);
        }

        return '/' . trim($uri, '/');
    }

    /**
     * Retrieves the route callback based on HTTP method and URI, using direct matching or regex extraction.
     */
    private function getRouteCallback(string $method, string $uri): mixed
    {
        return static::$routeMap[$method][$uri] ?? $this->extractRegex($method, $uri) ?? $uri;
    }

    /**
     * Resolves a route callback using different strategies based on its type.
     */
    private function resolve($callback)
    {
        $middleware = $this->app->controller->getMiddlewares();
        $this->executeMiddlewares($middleware);

        return match (true) {
            is_callable($callback) => $this->callCallback($callback),       // if  it's callable - just execute
            is_array($callback) => $this->invokeRouteWithMethodCall(),  // If it's an array, assume [ControllerClass, MethodName].
            is_string($callback) => $this->invokeRouteWithSingleCallback($callback)  //if  it's just a Myclass::class
        };
    }

    /**
     * Handles unregistered route cases, sending a 404 response in production or throwing an exception in development.
     */
    private function notRouteRegistered()
    {
        return $this->app->isProduction()
            ? $this->response->send($this->renderErrorView(), 404)
            :
            throw new RuntimeException(sprintf(' "%s" path does not exist.', !empty($this->callback)
                ? $this->callback : $this->getUri()), 404);
    }

    /**
     * Calls a callback function, passing the necessary arguments.
     */
    private function callCallback(callable $callback)
    {
        try {
            $reflection = new ReflectionFunction($callback);
            $params = $this->prepareCallbackArguments($reflection->getParameters());

            return $reflection->invokeArgs($params);
        } catch (ReflectionException $e) {
            throw $e;
        }
    }

    /**
     * Prepares the arguments for a callback function.
     */
    private function prepareCallbackArguments(array $params): array
    {
        $routeParams = $this->app->request->getRouteParams();
        if (empty($routeParams)) {
            return [];
        }

        $args = [];
        foreach ($params as $param) {
            if (isset($routeParams[$param->name])) {
                $args[] = $routeParams[$param->name];
            } else {
                $args[] = $this->getDefaultParamValue($param);
            }
        }

        return $args;
    }

    /**
     * Gets the default value of a parameter if available, otherwise returns null.
     */
    private function getDefaultParamValue(ReflectionParameter $param)
    {
        return $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null;
    }

    /**
     * Invokes the route using a single callback, handling different scenarios based on the callback type.
     */
    private function invokeRouteWithSingleCallback(string $callback)
    {
        if (!str_contains($callback, '\\')) {
            return view($callback, [], true);
        }

        $instance = $this->createInstance($callback);
        if (method_exists($instance, 'index')) {
            return $instance->response()->send($instance->index($instance));
        }

        return $instance->response()->send($callback);
    }

    /**
     * Creates an instance of a class specified by the callback.
     */
    private function createInstance(string $className): object
    {
        return class_exists($className) ? new $className
            : throw new Exception(sprintf('Class [  %s  ] not found.', $className));
    }

    /**
     * Invokes the route by calling a method on a controller instance.
     */
    private function invokeRouteWithMethodCall(): mixed
    {
        [$class, $method] = $this->callback;
        $controller = new $class;

        if (method_exists($controller, $method)) {
            return call_user_func([$controller, $method]);
        }

        throw new RuntimeException(sprintf('The method [ %s ] does not exist in [ %s ].', $method, $class));
    }

    /**
     * Extracts a callback using regular expression matching for the given HTTP method and URI.
     */
    private function extractRegex(string $method, string $uri)
    {
        foreach (static::$routeMap[$method] ?? [] as $route => $handler) {
            $pattern = '~^' . $this->compileRoute($route) . '$~';
            if (preg_match($pattern, $uri, $params)) {
                $params = array_filter($params, 'is_string', ARRAY_FILTER_USE_KEY);
                $this->app->request->setRouteParams($params);

                return $handler;
            }
        }

        return false;
    }

    /**
     * Transforms the pattern of a route with dynamic parameters.
     */
    private function compileRoute(string $route): string
    {
        // If it is already formatted {name:regex} it leaves it unchanged
        if (str_contains($route, ':')) {
            return preg_replace('/{(\w+):([^\}]+)}/', '(?P<\1>\2)', $route);
        }

        // Otherwise, it assumes that anything between {} is a parameter. 
        return preg_replace('/{(\w+)}/', '(?P<\1>[^\/]+)', $route);
    }

    /**
     * Generates the URL associated with the route name.
     */
    public function route(string $routeName, array $params = null): ?string
    {
        if ($route = static::$routeMap['GET'][$routeName] ?? null) {
            foreach ($params ?? [] as $key => $value) {
                $route = str_replace("{$key}", $value, $route);
            }
            return $route;
        }
        return null;
    }

    /**
     * Generates the URL associated with the path name.
     */
    public function url(string $routeName): ?string
    {
        return $this->baseUrl($routeName);
    }

    /**
     * Returns the full site root. 
     **/
    public function baseUrl(string $path = '/'): string
    {
        $scheme = isset($_SERVER['HTTPS']) ? 'https' : 'http';
        $scriptPath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
        $baseUrl = "{$scheme}://{$_SERVER['HTTP_HOST']}{$scriptPath}";
        $path = trim($this->prefix . '/' . ltrim($path, '/'), '/');

        return "{$baseUrl}/{$path}";
    }

    /**
     * Renders the error view based on the specified HTTP error code.
     */
    private function renderErrorView()
    {
        $viewFile = config('paths.viewsErrorsPath') .
            DIRECTORY_SEPARATOR . $this->app->config('app.errorPages.404');

        $controller = $this->app->controller;
        return $controller->renderView($viewFile);
    }

    /**
     * Boot the services specified in the 'boot' configuration array.
     */
    public function openRoutesUser(): self
    {
        $pathConfig = config('paths.routesPath') . DIRECTORY_SEPARATOR;
        $files = glob($pathConfig . "*.php");
        foreach ($files ?? [] as $file) {
            require_once ($file);
        }

        return $this;
    }
}
