<?php

namespace Axm;

use Axm;
use Axm\Views\View;
use Axm\Http\Request;
use Axm\Http\Response;
use Axm\Exception\AxmException;
use Axm\Middlewares\BaseMiddleware;
use Axm\Middlewares\MaintenanceMiddleware;
use Axm\Middlewares\AuthMiddleware;


/**
 *  Class Controller 
 * 
 * @author Juan Cristobal <juancristobalgd1@gmail.com>
 * @link http://www.axm.com/
 * @license http://www.axm.com/license/
 * @package Axm
 */
abstract class Controller
{
    protected ?object   $user     = null;
    protected ?string   $layout   = null;
    protected ?View     $view     = null;
    protected string    $action   = '';
    protected ?object   $model    = null;
    protected ?Request  $request  = null;
    protected ?Response $response = null;
    protected string    $controllerName = '';

    /**
     * @var BaseMiddleware[]
     */
    protected ?array $middlewares = [];


    public function __construct()
    {
        $app = Axm::app();
        $this->request  = $app->request;
        $this->response = $app->response;
        $this->view     = $app->view;

        $this->init();
    }

    /**
     * 
     */
    public function init()
    {
        $middleware = new MaintenanceMiddleware;
        $this->registerMiddleware($middleware);
    }

    /**
     * Modifies the current layout
     */
    public function setLayout(string $layout): void
    {
        $this->layout = $layout;
    }

    /**
     * Gets the current layout
     */
    public function getLayout(): string
    {
        return $this->layout ?? View::$nameLayoutByDefault;
    }

    /**
     * Specifies that the current view should extend an existing layout.
     */
    public function setPathView(string $path)
    {
        return $this->view::$viewPath = $path;
    }

    /**
     * Adds an action to the controller
     * 
     * @param string|null $action
     * @return void
     */
    public function addAction(?string $action): void
    {
        $this->action = $action ?? '';
    }

    /**
     * Gets the current controller action.
     *
     * @return string
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * Render the view
     * 
     * @param string $view
     * @param array $param
     */
    public function renderView(string $view, ?array $params = [], bool $buffer = true, string $ext = '.php'): ?string
    {
        return $this->view::render($view, $params, $buffer, $ext);
    }

    /**
     * Register a Middleware in the Controller
     *
     * @param BaseMiddleware $middleware
     */
    public function registerMiddleware(BaseMiddleware $middleware): void
    {
        $this->middlewares[] = $middleware;
    }

    /**
     * @return Middlewares\BaseMiddleware[]
     */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    /**
     * Registers and executes the AuthMiddleware middleware in the controller 
     * 
     * for access control to the specified actions 
     * @param array $actions Actions requiring authorization 
     * @param bool $allowedAction Indicates whether access to other 
     * actions than those specified is allowed.    
     **/
    public function accessControl(array $actions, bool $allowedAction = false)
    {
        $middleware = new AuthMiddleware($actions, $allowedAction);
        $this->registerMiddleware($middleware);
    }

    /**
     * Called when there is no method
     *
     * @param string $name      
     * @param array  $arguments
     * @throws AxmException
     * @return void
     */
    public function __call($name, $arguments)
    {
        throw new AxmException(Axm::t('axm', 'El método "%s" no existe', [$name]), 'no_action');
    }
}
