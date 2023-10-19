<?php

namespace Axm\Middlewares;

use Axm;
use Axm\Cache\Cache;
use Axm\Exception\AxmException;
use Axm\Middlewares\BaseMiddleware;

/**
 * Verifica si el sitio esta en mantenimiento.
 */
class MaintenanceMiddleware extends BaseMiddleware
{
    protected $config;
    /**
     * Verifica si el usuario está autenticado y tiene permisos para acceder a la acción actual.
     *
     * @throws AxmException Si el usuario no tiene permisos para acceder a la acción actual.
     */
    public function execute()
    {
        $this->config = Axm::app()->config();

        if ($this->config->maintenance === true) {
            $this->clearCache();
            return $this->showViewMaintenance();
        }

        return;
    }

    /***
     * 
     */
    public function clearCache()
    {
        return Cache::driver()->flush();
    }

    /**
     * muestra la vista de mantenimiento.
     */
    private function showViewMaintenance()
    {
        $viewFile = trim(ROOT_PATH . '/' . $this->config->get('errorPages.503'));
        $output = Axm::app()->controller->renderView($viewFile);
        die($output);
    }
}
