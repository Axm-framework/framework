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
     * Checks if the user is authenticated and has
     * permissions to access the current action.
     */
    public function execute()
    {
        $maintenance = env('APP_DOWN', false);
        if ($maintenance === true)
            return $this->showMaintenanceView();
    }

    /**
     * Displays the maintenance view.
     */
    private function showMaintenanceView()
    {
        $errorViewPath = config('paths.viewsErrorsPath');
        $maintenanceView = config('view.errorPages.503');

        $viewFile = $errorViewPath . DIRECTORY_SEPARATOR . $maintenanceView;
        $output = app()->controller->renderView($viewFile);
        exit($output);
    }
}
