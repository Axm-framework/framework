<?php

/**
 * Class Router
 **/
final class Router
{
    private string $prefix;
    private const CONTROLLERS_NAMESPACE = 'App\\Controllers\\';
    private const DEFAULT_CONTROLLER_NAME = 'HomeController';

    private array $routesMap = [];


    public function __construct(string $prefix = null)
    {
        $this->prefix ??= $prefix ?? rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    }

    public function addRoutes(array $routes): self
    {
        $this->routesMap += $routes;
        return $this;
    }

    /**
     * This function, when called, will parse the URI, 
     * dispatch the appropriate controller and method
     */
    public function run(): ?string
    {
        $uri = $this->parseUri();

        $results = $this->parserControllerAndMethod($uri);
        $httpMethod = $results[0];
        $controller = $results[1];
        $methodName = $results[2];

        $controllerFile = $this->getControllerPath($controller);

        if (!is_file($controllerFile)) {
            $this->handleNotFound($controllerFile);
        }

        require $controllerFile; // include file

        $controllerClass = self::CONTROLLERS_NAMESPACE . $controller;
        $instance = new $controllerClass();

        if ($httpMethod === strtoupper($_SERVER['REQUEST_METHOD'])) {
            call_user_func([$instance, $methodName], $_SERVER['REQUEST_METHOD']);
        }

        return $uri;
    }

    /**
     *  Parse the current uri from REQUEST_URI server variable.
     */
    private function parseUri(): string
    {
        $uri = strtok(rawurldecode($_SERVER['REQUEST_URI']), '?');

        // Delete the base path
        if ($this->prefix !== '/') {
            $uri = str_replace($this->prefix, '', $uri);
        }

        return '/' . trim($uri, '/');
    }

    private function getControllerPath(string $controllerName): string
    {
        $path = Config::get('paths.controllersPath');
        return $path . DIRECTORY_SEPARATOR . $controllerName . '.php';
    }

    function parserControllerAndMethod(string $uri)
    {
        $result = $this->getHandler($uri);
        if (count($result) !== 2) {
            throw new \RuntimeException('Invalid handler: ' . implode(',', $result));
        }

        return $this->parserController($result);
    }


    function parserController($result)
    {
        $httpMethod = strtoupper($result[0]);
        $controller = ucfirst($result[1]);

        if (is_string($controller) && str_contains($controller, '@')) {
            $result = explode('@', $controller);
            $controller = $result[0];
            $methodName = $result[1];
        } elseif(is_callable($controller)){
            call_user_func( $controller );
        }else {
            $methodName = 'index';
        }

        return [$httpMethod, $controller, $methodName];
    }

    private function handleNotFound(string $controllerFile): void
    {
        about("Controlador no encontrado: [ $controllerFile ]");
    }

    private function getHandler(string $uri): array
    {
        return $this->routesMap[$uri] ?? $this->getDefaultHandler();
    }

    private function getDefaultHandler(): array
    {
        return [strtoupper($_SERVER['REQUEST_METHOD']), self::DEFAULT_CONTROLLER_NAME];
    }

    // Función para obtener la ruta base del proyecto
    static function getBaseUrl()
    {
        // Obtener el nombre del archivo actual
        $scriptName = $_SERVER['SCRIPT_NAME'];
        // Obtener la carpeta en la que se encuentra el archivo actual
        $scriptPath = dirname($scriptName);
        // Obtener la URL base
        $baseUrl = isset($_SERVER['HTTPS']) ? 'https' : 'http' . '://' . $_SERVER['HTTP_HOST'] . $scriptPath;
        return $baseUrl;
    }
}
