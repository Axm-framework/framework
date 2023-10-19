<?php

namespace Axm\Middlewares;

use Axm;
use Axm\Auth\Auth;
use Axm\Exception\AxmException;
use Axm\Middlewares\BaseMiddleware;

/**
 * Middleware que verifica si un usuario está autenticado y si tiene permisos para acceder a la acción actual.
 */
class AuthMiddleware extends BaseMiddleware
{
    private const ALLOWED_ACTION     = true;
    private const NOT_ALLOWED_ACTION = false;
    protected $app;

    protected array $actions;
    private bool $allowedAction;

    public function __construct(array $actions = [], bool $allowedAction = self::NOT_ALLOWED_ACTION)
    {
        $this->actions       = $actions;
        $this->allowedAction = $allowedAction;
        $this->app           = Axm::app();
    }

    /**
     * Verifica si el usuario está autenticado y tiene permisos para acceder a la acción actual.
     *
     * @throws AxmException Si el usuario no tiene permisos para acceder a la acción actual.
     */
    public function execute()
    {
        $auth = new Auth();

        if (!$auth->check()) {
            return $this->validatePermission();
        }
    }

    /**
     * Valida si el usuario tiene permisos para acceder a la acción actual.
     *
     * @throws AxmException Si el usuario no tiene permisos para acceder a la acción actual.
     */
    private function validatePermission()
    {
        $action = $this->app->controller->getAction();

        if ($this->allowedAction === self::NOT_ALLOWED_ACTION) {
            if (empty($this->actions) || in_array($action, $this->actions))
                return $this->throwPermissionException();
        } else {
            if (!in_array($action, $this->actions))
                return $this->throwPermissionException();
        }
    }

    /**
     * Lanza una excepción indicando que el usuario no tiene permisos para acceder a la acción actual.
     *
     * @throws AxmException Indicando que el usuario no tiene permisos para acceder a la acción actual.
     */
    private function throwPermissionException()
    {
        if (Axm::isProduction()) {

            $viewFile = trim(ROOT_PATH . '/' . $this->app->config()->get('errorPages.500'));
            $output   = $this->app->controller->renderView($viewFile);
            die($output);
        }

        throw new AxmException(Axm::t('axm', 'Usted no tiene permisos suficientes para realizar esta operacion.'));
    }
}
