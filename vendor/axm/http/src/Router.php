<?php

namespace Axm\Http;

use Axm;
use Axm\Application;
use Axm\Http\Request;
use Axm\Http\Response;
use Axm\Exception\AxmException;

/**
 * Class Router
 *
 * @author  Juan Cristobal <juancristobalgd1@gmail.com>
 * @package System
 */
class Router
{
    private static $request;
    private static $response;
    private static array $routeMap = [];
    private static $callback;
    private static $uri;
    private static $method;

    private static $app;

    /**
     * All of the verbs supported by the router.
     *
     * @var string[]
     */
    public static $verbs = ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];


    public function __construct(Application $app, Request $request, Response $response)
    {
        static::$app      = $app;
        static::$request  = $request;
        static::$response = $response;
    }

    /**
     * Add a route to the underlying route collection.
     *
     * @param array|string  $methods
     * @param string  $route
     * @param array|string|callable|null  $callback
     */
    public static function addRoute(array|string $methods, string $route, $callback): void
    {
        $methods = (array) $methods;

        foreach ($methods as $method) {
            static::$routeMap[$method][$route] = $callback;
        }
    }

    /**
     * Register a new GET route with the router.
     *
     * @param string  $route
     * @param array|string|callable|null  $callback
     * @param array $args
     */
    public static function get(string $route, $callback, $args = []): void
    {
        static::addRoute(['GET', 'HEAD'], $route, $callback, $args);
    }

    /**
     * Register a new POST route with the router.
     *
     * @param string  $route
     * @param array|string|callable|null  $callback
     * @param array $args
     */
    public static function post(string $route, $callback, $args = []): void
    {
        static::addRoute('POST', $route, $callback, $args);
    }

    /**
     * Register a new PUT route with the router.
     *
     * @param string  $route
     * @param array|string|callable|null  $callback
     * @param array $args
     */
    public static function put(string $route, $callback, $args = []): void
    {
        static::addRoute('PUT', $route, $callback, $args);
    }

    /**
     * Register a new PATCH route with the router.
     *
     * @param string  $route
     * @param array|string|callable|null  $callback
     * @param array $args
     */
    public static function patch(string $route, $callback, $args = []): void
    {
        static::addRoute('PATCH', $route, $callback, $args);
    }

    /**
     * Register a new DELETE route with the router.
     *
     * @param string  $route
     * @param array|string|callable|null  $callback
     * @param array $args
     */
    public static function delete(string $route, $callback, $args = []): void
    {
        static::addRoute('DELETE', $route, $callback, $args);
    }

    /**
     * Register a new OPTIONS route with the router.
     *
     * @param  string  $route
     * @param  array|string|callable|null  $callback
     * @param  array $args
     */
    public static function options(string $route, $callback, $args = []): void
    {
        static::addRoute('OPTIONS', $route, $callback, $args);
    }

    /**
     * Register a new route responding to all verbs.
     *
     * @param  string  $route
     * @param  array|string|callable|null  $callback
     * @param  array $args
     */
    public static function any(string $route, $callback, $args = []): void
    {
        static::addRoute(self::$verbs, $route, $callback, $args);
    }

    /**
     * Register a new route that responds to all verbs.
     *
     * @param  array|string  $methods
     * @param  string  $route
     * @param  array|string|callable|null  $callback
     * @param  array $args
     */
    public static function match($methods, string $route, $callback, $args = []): void
    {
        static::addRoute(array_map('strtoupper', (array) $methods), $route, $callback, $args);
    }

    /**
     * Return one route defined for user
     * 
     * @return array
     */
    public static function getRoutes(string $method)
    {
        return static::$routeMap[$method] ?? [];
    }

    /**
     * Return all routes defined
     * 
     * @return array
     */
    public static function getAllRoutes()
    {
        return static::$routeMap ?? [];
    }

    /**
     * Gets the callback of a route.
     *
     * @param string $method
     * @return array
     */
    public static function getRouteMap(string $method): array
    {
        return static::$routeMap[$method] ?? [];
    }

    /**
     * Search for Middlewares in controllers and run them.
     *
     * @param array $middlewares 
     * @return callback
     * */
    private static function getMiddlewares(array $middlewares)
    {
        foreach ($middlewares as $middleware)
            return $middleware->execute();      //executes the default execute method
    }

    /**
     * Register a Middleware in the Controller
     *
     * @param array $actions
     * @param bool  $allowedAction
     */
    public static function middleware($middleware = null)
    {
        if (!is_null($middleware)) {
            # register middleware in controller
            Axm::app()->controller->registerMiddleware(new $middleware);
        }

        return static::class;
    }


    /**
     * 
     */
    public static function dispatch()
    {
        static::$method   = static::$request->getMethod();
        static::$uri      = static::$request->getUri();
        static::$callback = static::getRouteCallback(static::$method, static::$uri);

        if (!static::$callback) {
            return static::notRouteRegistered();
        }

        return static::resolve(static::$method, static::$callback);
    }

    /**
     * 
     */
    private static function getRouteCallback($method, $uri)
    {
        $callback = static::$routeMap[$method][$uri] ?? false;

        if (!$callback) {
            $callback = static::getCallback($method, $uri) ?? $uri;
        }

        return $callback;
    }

    /**
     * 
     */
    private static function resolve($method, $callback)
    {
        if (is_callable($callback)) {
            return call_user_func($callback);
        }

        if (is_array($callback)) {
            return static::invokeRouteWithMethodCall();
        }

        if (is_string($callback)) {
            return static::invokeRouteWithSingleCallback($method);
        }
    }

    /**
     * 
     */
    private static function notRouteRegistered()
    {
        if (static::$app->isProduction()) {
            $nameView = static::$app->config()->get('errorPages.404');
            return static::$response->send(static::renderViewOnly($nameView), 404);
        }

        throw new AxmException(
            Axm::t('axm', " %s path does not exist.", [
                !empty(static::$callback) ? static::$callback : static::$app->request->getUri()
            ]),
            'no_route'
        );
    }

    /**
     * 
     */
    private static function invokeRouteWithSingleCallback(string $method): mixed
    {
        $controller = static::$app->controller;

        // Calling the render() method on the controller instance
        return $controller->renderView(static::$callback);
    }

    /**
     * 
     */
    private static function invokeRouteWithMethodCall(): mixed
    {
        [$class, $method] = static::$callback;
        $controller = new $class;

        if (!method_exists($controller, $method)) {
            throw new AxmException(
                Axm::t('axm', 'The method "%s" does not exist in %s.', [
                    $method,  $controller
                ])
            );
        }

        static::getMiddlewares($controller->getMiddlewares());

        return call_user_func([$controller, $method]);
    }


    /**
     * It checks the parameters in the routes, breaks them up and returns the callback.
     *
     * @return array|false
     */
    private static function getCallBack($method, $uri)
    {
        foreach (static::$routeMap[$method] as $route => $handler) {

            $pattern = '~^' . preg_replace('/{(\w+):([^}]+)}/', '(?P<$1>$2)', $route) . '$~';

            if (preg_match($pattern, $uri, $params)) {
                $params = array_filter($params, 'is_string', ARRAY_FILTER_USE_KEY);
                static::$request->setRouteParams($params);

                return $handler;
            }
        }

        return false;
    }


    /**
     * Shows the internal view
     * */
    private static function renderViewOnly(string $viewFile)
    {
        $viewFile = ROOT_PATH . '/' . $viewFile;

        $controller = static::$app->controller;

        // Calling the render() method on the controller instance
        return $controller->renderView($viewFile);
    }
}
