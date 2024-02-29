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
     * @var string[]
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
     *
     * @param array|string  $methods
     * @param string  $route
     * @param array|string|callable|null  $callback
     */
    public static function addRoute(array|string $methods, string $route, $callback)
    {
        $methods = (array) $methods;
        foreach ($methods as $method)
            static::$routeMap[$method][trim($route)] = $callback;

        return new static;
    }

    /**
     * Register a new GET route with the router.
     *
     * @param string $route
     * @param array|string|callable|null $callback
     * @param array $args
     * @return Router
     */
    public static function get(string $route, $callback, $args = []): Router
    {
        return static::addRoute(['GET', 'HEAD'], $route, $callback, $args);
    }

    /**
     * Register a new POST route with the router.
     *
     * @param string  $route
     * @param array|string|callable|null  $callback
     * @param array $args
     */
    public static function post(string $route, $callback, $args = []): Router
    {
        return static::addRoute('POST', $route, $callback, $args);
    }

    /**
     * Register a new PUT route with the router.
     *
     * @param string  $route
     * @param array|string|callable|null  $callback
     * @param array $args
     */
    public static function put(string $route, $callback, $args = []): Router
    {
        return static::addRoute('PUT', $route, $callback, $args);
    }

    /**
     * Register a new PATCH route with the router.
     *
     * @param string  $route
     * @param array|string|callable|null  $callback
     * @param array $args
     */
    public static function patch(string $route, $callback, $args = []): Router
    {
        return static::addRoute('PATCH', $route, $callback, $args);
    }

    /**
     * Register a new DELETE route with the router.
     *
     * @param string  $route
     * @param array|string|callable|null  $callback
     * @param array $args
     */
    public static function delete(string $route, $callback, $args = []): Router
    {
        return static::addRoute('DELETE', $route, $callback, $args);
    }

    /**
     * Register a new OPTIONS route with the router.
     *
     * @param  string  $route
     * @param  array|string|callable|null  $callback
     * @param  array $args
     */
    public static function options(string $route, $callback, $args = []): Router
    {
        return static::addRoute('OPTIONS', $route, $callback, $args);
    }

    /**
     * Register a new route responding to all verbs.
     *
     * @param  string  $route
     * @param  array|string|callable|null  $callback
     * @param  array $args
     */
    public static function any(string $route, $callback, $args = []): Router
    {
        return static::addRoute(static::$verbs, $route, $callback, $args);
    }

    /**
     * Register a new route that responds to all verbs.
     *
     * @param  array|string  $methods
     * @param  string  $route
     * @param  array|string|callable|null  $callback
     * @param  array $args
     */
    public static function match($methods, string $route, $callback, $args = []): Router
    {
        return static::addRoute(array_map('strtoupper', (array) $methods), $route, $callback, $args);
    }

    /**
     * Return one route defined for user
     * @return array
     */
    public function getRoutes(string $method): array
    {
        return static::$routeMap[$method] ?? [];
    }

    /**
     * Return all routes defined
     * @return array
     */
    public function getAllRoutes(): array
    {
        return static::$routeMap ?? [];
    }

    /**
     * Gets the callback of a route.
     *
     * @param string $method
     * @return array
     */
    public function getRouteMap(string $method): array
    {
        return static::$routeMap[$method] ?? [];
    }

    /**
     * Search for Middlewares in controllers and run them.
     *
     * @param array $middlewares 
     * @return callback
     * */
    private function executeMiddlewares(array $middlewares = [])
    {
        foreach ($middlewares as $middleware)
            $middleware->execute();
    }

    /**
     * Registers a middleware for the current route.
     *
     * @param string $middleware
     * @return $this
     */
    public static function middleware($middleware)
    {
        // if (!class_exists($middleware))
        //     throw new RuntimeException(sprintf(' [ %s ] middleware does not exist', $middleware));

        app()
            ->controller
            ->registerMiddleware(new $middleware);

        return new static;
    }

    /**
     * Allows defining groups/prefixes of routes with shared 
     * properties (middleware, namespace, etc).
     * @param string $prefix
     * @param callable $callback
     */
    public static function group($prefix, $callback)
    {
        // Saves the current status of routes
        $currentRoutes = static::$routeMap;

        // Applies the prefix to the routes within the group
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
        static::get($route, static fn () => view($view, $params, true));
    }

    /**
     * Dispatches the request, resolving the route callback 
     * or handling unregistered routes.
     */
    public function dispatch()
    {
        $this->method   = strtoupper($_SERVER['REQUEST_METHOD']);
        $this->uri      = $this->getUri();
        $this->callback = $this->getRouteCallback($this->method, $this->uri);

        return $this->callback ? $this->resolve($this->callback) : $this->notRouteRegistered();
    }

    /**
     *  Parse the current uri from REQUEST_URI server variable.
     */
    private function getUri(): string
    {
        $uri = strtok(rawurldecode($_SERVER['REQUEST_URI'] ?? '/'), '?');
        if ($this->prefix !== '/') {  // Delete the base path
            $uri = str_replace($this->prefix, '', $uri);
        }

        return '/' . trim($uri, '/');
    }

    /**
     * Retrieves the route callback based on HTTP method and URI, using direct matching or regex extraction.
     *
     * @param string $method The HTTP method of the request.
     * @param string $uri    The URI of the request.
     * @return mixed The route callback or the URI if not found.
     */
    private function getRouteCallback(string $method, string $uri)
    {
        return static::$routeMap[$method][$uri] ?? $this->extractRegex($method, $uri) ?? $uri;
    }

    /**
     * Resolves a route callback using different strategies based on its type.
     *
     * @param mixed $callback The route callback to be resolved.
     * @return mixed Result of resolving the route callback using appropriate strategy.
     */
    private function resolve($callback)
    {
        $middleware = $this->app->controller->getMiddlewares();
        $this->executeMiddlewares($middleware);

        return match (true) {
            is_callable($callback) => $this->callCallback($callback),       // if  it's callable - just execute
            is_array($callback)    => $this->invokeRouteWithMethodCall(),  // If it's an array, assume [ControllerClass, MethodName].
            is_string($callback)   => $this->invokeRouteWithSingleCallback($callback) //if  it's just a Myclass::class
        };
    }

    /**
     * Handles unregistered route cases, sending a 404 response in production or 
     * throwing an exception in development.
     *
     * @return mixed Sends a 404 response or throws a RuntimeException.
     * @throws RuntimeException When no route is registered, and the application is not in production.
     */
    private function notRouteRegistered()
    {
        // return $this->app->isProduction()
        //     ? $this->response->send($this->renderErrorView(), 404)
        //     : 
        throw new RuntimeException(sprintf(' "%s" path does not exist.', !empty($this->callback)
            ? $this->callback : $this->getUri()), 404);
    }

    /**
     * Calls a callback function, passing the necessary arguments.
     *
     * This method inspects the parameters of the given callback function and prepares the arguments
     * accordingly.
     * @param callable $callback The callback function to be called.
     * @return mixed The result of calling the callback function with the prepared arguments.
     * @throws ReflectionException If there is an error reflecting the callback function.
     */
    private function callCallback($callback)
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
     *
     * @param array $params The parameters of the callback function.
     * @return array The prepared arguments.
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
     *
     * @param ReflectionParameter $param The reflection parameter.
     * @return mixed The default value of the parameter or null.
     */
    private function getDefaultParamValue(ReflectionParameter $param)
    {
        return $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null;
    }

    /**
     * Invokes the route using a single callback, handling different scenarios based on the callback type.
     *
     * @param string $callback
     * @return void|null
     * @throws \Exception
     */
    private function invokeRouteWithSingleCallback(string $callback)
    {
        if (!str_contains($callback, '\\')) {
            return view($callback, [], true);
        }

        $instance = $this->createInstance($callback);
        if (method_exists($instance, 'index')) {

            return $instance->response()->send($instance->index());
        }

        return $instance->response()->send($callback);
    }

    /**
     * Creates an instance of a class specified by the callback.
     *
     * @param string $className The class name.
     * @return object The instance of the specified class.
     * @throws Exception When the class is not found.
     */
    private function createInstance(string $className)
    {
        return class_exists($className) ? new $className
            : throw new Exception(sprintf('Class [  %s  ] not found.', $className));
    }

    /**
     * Invokes the route by calling a method on a controller instance.
     *
     * @return mixed The result of invoking the specified method on the controller.
     * @throws RuntimeException When the specified method does not exist in the controller class.
     */
    private function invokeRouteWithMethodCall(): mixed
    {
        [$class, $method] = $this->callback;
        $controller = new $class;

        if (method_exists($controller, $method)) {
            return call_user_func([$controller, $method]);
        }

        throw new RuntimeException(sprintf('The method [ %s ] does not exist in [ %s ].',  $method, $class));
    }

    /**
     * Extracts a callback using regular expression matching for the given HTTP method and URI.
     *
     * @param string $method The HTTP method of the request.
     * @param string $uri    The URI of the request.
     * @return mixed The extracted callback or false if no match is found.
     */
    private function extractRegex(string $method, string $uri)
    {
        foreach (static::$routeMap[$method] as $route => $handler) {
            $pattern = '~^' . $this->compileRoute($route) . '$~';
            if (preg_match($pattern, $uri, $params)) {
                $params = array_filter($params, 'is_string', ARRAY_FILTER_USE_KEY);
                $this->request->setRouteParams($params);

                return $handler;
            }
        }

        return false;
    }

    /**
     * Transforms the pattern of a route with dynamic parameters.
     * 
     * @param string $route The pattern of the route to be transformed.
     * @return string The transformed regular expression.
     */
    private function compileRoute(string $route)
    {
        // If it is already formatted {name:regex} it leaves it unchanged
        if (str_contains($route, ':')) {
            return preg_replace('/{(\w+):([^\}]+)}/', '(?P<\1>\2)', $route);
        }

        // Otherwise, it assumes that anything between {} is a parameter. 
        return preg_replace('/{(\w+)}/', '(?P<\1>[^\/]+)', $route);
    }

    /**
     * Generates the URL associated with the path name.
     *
     * @param string $routeName
     * @param array $params
     * @return string|null
     */
    public static function url(string $routeName, array $params = null)
    {
        if ($route = static::$routeMap['GET'][$routeName] ?? null) {
            foreach ($params as $key => $value) {
                $route = str_replace("{$key}", $value, $route);
            }
            return $route;
        }
        return null;
    }

    /**
     * Renders the error view based on the specified HTTP error code.
     * @return mixed The result of rendering the error view.
     */
    private function renderErrorView()
    {
        $viewFile = config('paths.viewsErrorsPath') .
            DIRECTORY_SEPARATOR . $this->app->config('app.errorPages.404');

        $controller = $this->app->controller;

        // Calling the render() method on the controller instance
        return $controller->renderView($viewFile);
    }

    /**
     * Boot the services specified in the 'boot' configuration array.
     */
    public function openRoutesUser(): self
    {
        $ext = '.php';
        $pathConfig = config('paths.routesPath') . DIRECTORY_SEPARATOR;
        $files = glob($pathConfig . "*$ext");
        foreach ($files ?? [] as $file) {
            require_once($file);
        }

        return $this;
    }
}
