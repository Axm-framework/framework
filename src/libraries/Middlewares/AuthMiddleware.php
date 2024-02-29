<?php

declare(strict_types=1);

namespace Middlewares;

use Auth\Auth;
use RuntimeException;
use App\Middlewares\BaseMiddleware;

/**
 * Middleware that verifies if a user is authenticated 
 * and has permissions to access the current action.
 */
class AuthMiddleware extends BaseMiddleware
{
    protected array $actions;
    private bool $allowedAction;
    private const ALLOWED_ACTION = true;
    private const NOT_ALLOWED_ACTION = false;

    /**
     * __construct
     *
     * @param  mixed $actions
     * @param  mixed $allowedAction
     * @return void
     */
    public function __construct(array $actions = [], bool $allowedAction = self::NOT_ALLOWED_ACTION)
    {
        $this->actions = $actions;
        $this->allowedAction = $allowedAction;
    }

    /**
     * Execute the action, checking authentication and permissions.
     *
     * @return bool True if the action was executed successfully, false otherwise.
     * @throws RuntimeException If the user does not have sufficient permissions.
     */
    public function execute()
    {
        if (!(new Auth(app()))->check()) {
            $this->validatePermission();
        }
    }

    /**
     * Validates if the user has permissions to access the current action.
     * 
     * @throws RuntimeException If the user doesn't have permissions
     * to access the current action.
     */
    private function validatePermission()
    {
        // Get the current action from the application's controller
        $action = app()->controller;
        $isAllowed = ($this->allowedAction === self::NOT_ALLOWED_ACTION)
            ? (empty($this->actions) || in_array($action, $this->actions))
            : in_array($action, $this->actions);

        // Throw an exception if the user doesn't have permissions
        if (!$isAllowed) {
            $this->throwPermissionException();
        }
    }

    /**
     * Throws a RuntimeException indicating insufficient permissions.
     * @throws RuntimeException If the user does not have sufficient permissions.
     */
    private function throwPermissionException()
    {
        if (app()->isProduction()) {
            $viewFile = config('paths.viewsErrorsPath') .
                DIRECTORY_SEPARATOR . config('app.errorPages.500');

            $output = app()->controller->renderView($viewFile);
            die($output);
        }

        throw new RuntimeException('You do not have sufficient permissions to perform this operation.');
    }
}
