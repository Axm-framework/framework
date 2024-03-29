<?php

/**
 * Axm Framework PHP.
 *
 * @author Juan Cristobal <juancristobalgd1@gmail.com>
 * @link http://www.axm.com/
 * @license http://www.axm.com/license/
 * @package Console
 */

namespace Console\Commands\Utilities;

use Console\BaseCommand;
use Console\CLI;
use Http\Router;

/**
 * Lists all of the user-defined routes. This will include any Routes files
 * that can be discovered, but will NOT include any routes that are not defined
 * in a routes file, but are instead discovered through auto-routing.
 */
class Routes extends BaseCommand
{
    /**
     * The group the command is lumped under
     * when listing commands.
      */
    protected string $group = 'Axm';

    /**
     * The Command's name
      */
    protected string $name = 'routes';

    /**
     * the Command's short description
      */
    protected string $description = 'Displays all of user-defined routes. Does NOT display auto-detected routes.';

    /**
     * the Command's usage
      */
    protected string $usage = 'routes';

    /**
     * the Command's Arguments
      */
    protected array $arguments = [];

    /**
     * the Command's Options
      */
    protected array $options = [];

    /**
     * @var Router|null
     */
    private ?Router $router;


    /**
     * Displays the help for the spark cli script itself.
     */
    public function run(array $params)
    {
        $collection = $this->router();
        $methods = $collection::$verbs;   // get the verbs ['get',post,head....]

        $tbody = [];
        foreach ($methods as $method) {
            $routes = $collection->getRoutes($method);

            foreach ($routes as $route => $handler) {
                // filter for strings, as callbacks aren't displayable
                if (is_string($handler)) {
                    $tbody[] = [
                        $method,
                        $route,
                        $handler,
                    ];
                }
                if (is_array($handler) || is_object($handler)) {
                    $tbody[] = [
                        $method,
                        $route,
                        $this->getProcessAddressHandle($handler)
                    ];
                }
            }
        }

        $thead = [
            CLI::color('Method', 'green'),
            CLI::color('Route', 'green'),
            CLI::color('Handler|Dir', 'green'),
        ];

        CLI::table($tbody, $thead);
    }

    /**
     * router
     * @return Router
     */
    private function router(): Router
    {
        if (!isset($this->routes)) {
            $this->router = app()->router;
        }

        return $this->router;
    }

    /**
     * Returns a string representing the handler of a path based on the data 
     * provided, used to identify the controller and method associated with a given 
     * path in a web system.
     */
    public function getProcessAddressHandle($data): string
    {
        if (is_object($data)) {
            $output = 'Object(' . get_class($data) . ')';
        } elseif (is_array($data)) {
            $output = '';
            if (is_object($data[0])) {
                $output .= 'Object(' . get_class($data[0]) . ')';
            } else {
                $output .= $data[0];
            }
            $output .= '::' . $data[1];
        } else {
            $output = '';
        }

        return $output;
    }
}
