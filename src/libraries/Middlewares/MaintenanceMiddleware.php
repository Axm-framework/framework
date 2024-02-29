<?php

declare(strict_types=1);

namespace Middlewares;

use App\Middlewares\BaseMiddleware;

/**
 * Checks if the site is in maintenance mode.
 */
class MaintenanceMiddleware extends BaseMiddleware
{
    /**
     * Configuration property.
     * @var mixed
     */
    protected $config;

    /**
     * Checks if the user is authenticated and has
     * permissions to access the current action.
     *
     * @return void
     */
    public function execute()
    {
        $maintenance = env('APP_DOWN', false);
        if ($maintenance === true)
            return $this->showViewMaintenance();
    }

    /**
     * Displays the maintenance view.
     * @return void
     */
    private function showViewMaintenance()
    {
        $viewFile = config('paths.viewsErrorsPath') .
            DIRECTORY_SEPARATOR . config('view.errorPages.503');
        $output = app()->controller->renderView($viewFile);
        die($output);
    }
}
