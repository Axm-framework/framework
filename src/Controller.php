<?php

declare(strict_types=1);

use Views\View;
use Http\Request;
use Http\Response;
use Middlewares\AuthMiddleware;
use App\Middlewares\BaseMiddleware;

/**
 * Class Controller
 *
 * @author Juan Cristobal <juancristobalgd1@gmail.com>
 * @link http://www.axm.com/
 * @license http://www.axm.com/license/
 * @package Framework
 */
class Controller
{
    /**
     * @var object|null The current user.
     */
    protected ?object $user = null;

    /**
     * @var string|null The layout to be used.
     */
    protected ?string $layout = null;

    /**
     * @var View|null The View instance.
     */
    protected ?View $view = null;

    /**
     * @var string The current action.
     */
    protected string $action = '';

    /**
     * @var Request|null The Request instance.
     */
    protected ?Request $request = null;

    /**
     * @var Response|null The Response instance.
     */
    protected ?Response $response = null;

    /**
     * @var BaseMiddleware[]|null The array of middlewares.
     */
    protected ?array $middlewares = [];

    /**
     * Controller constructor.
     */
    public function __construct()
    {
        $app = app();
        $this->request  = $app->request;
        $this->response = $app->response;
        $this->view     = $app->view;

        $this->registerDefaultMiddleware();
    }

    /**
     * Execute the registered middlewares.
     */
    private function registerDefaultMiddleware()
    {
        $middlewares = BaseMiddleware::$httpMiddlewares;
        foreach ($middlewares as $middleware) {
            if (is_subclass_of($middleware, BaseMiddleware::class)) {
                $this->middlewares[] = new $middleware;
            }
        }
    }

    /**
     * Set the layout for the current controller.
     */
    public function setLayout(string $layout): void
    {
        $this->layout = $layout;
    }

    /**
     * Get the current layout.
     */
    public function getLayout(): string
    {
        return $this->layout;
    }

    /**
     * Set the path for the current view.
     */
    public function setPathView(string $path)
    {
        $this->view::$viewPath = $path;
    }

    /**
     * Add an action to the controller.
     */
    public function addAction(?string $action): void
    {
        $this->action = $action ?? '';
    }

    /**
     * Get the current controller action.
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * Render the view.
     */
    public function renderView(string $view, string|array $params = null, bool $withLayout = true, string $ext = '.php'): ?string
    {
        return $this->view->render($view, $ext)->withData($params)->withLayout($withLayout)->get();
    }

    /**
     * Get the view object associated with this controller.
     */
    public function view(): ?View
    {
        return $this->view;
    }

    /**
     * Register a middleware in the controller.
     */
    public function registerMiddleware(BaseMiddleware $middleware): void
    {
        $this->middlewares[] = $middleware;
    }

    /**
     * Get the registered middlewares.
     */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    /**
     * Register and execute the AuthMiddleware for access control to the specified actions.
     */
    public function accessControl(array $actions, bool $allowedAction = false): void
    {
        $middleware = new AuthMiddleware($actions, $allowedAction);
        $this->registerMiddleware($middleware);
    }

    /**
     * Response Instance
     */
    public function response(): ?Response
    {
        return $this->response;
    }

    /**
     * Request Instance
     */
    public function request(): ?Request
    {
        return $this->request;
    }

    /**
     * Handle calls to methods that do not exist.
     */
    public function __call(string $name, array $params)
    {
        if (method_exists($this, $name)) {
            return $this->$name(...$params);
        }

        throw new BadMethodCallException(sprintf('Method [%s] does not exist', $name));
    }
}
