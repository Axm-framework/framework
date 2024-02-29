<?php

function axm_autoloader(string $class)
{
    static $classMap;
    $classMap ??= [
        'Container'       => AXM_PATH . DIRECTORY_SEPARATOR . 'Container.php',
        'App'             => AXM_PATH . DIRECTORY_SEPARATOR . 'App.php',
        'Config'          => AXM_PATH . DIRECTORY_SEPARATOR . 'Config.php',
        'Env'             => AXM_PATH . DIRECTORY_SEPARATOR . 'Env.php',
        'Facade'          => AXM_PATH . DIRECTORY_SEPARATOR . 'Facade.php',
        'Config'          => AXM_PATH . DIRECTORY_SEPARATOR . 'Config.php',
        'Controller'      => AXM_PATH . DIRECTORY_SEPARATOR . 'Controller.php',
        // 'Router'          => AXM_PATH . DIRECTORY_SEPARATOR . 'Router.php',
        'BaseModel'       => AXM_PATH . DIRECTORY_SEPARATOR . 'BaseModel.php',
    ];

    if (isset($classMap[$class])) {
        include $classMap[$class];
        return;
    }

    if (is_file(AXM_PATH . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . $class . '.php')) {
        include AXM_PATH . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . $class . '.php';
        return;
    }

    if (is_file(AXM_PATH . DIRECTORY_SEPARATOR . 'libraries' . $class . DIRECTORY_SEPARATOR . $class . '.php')) {
        include AXM_PATH . DIRECTORY_SEPARATOR . 'libraries' . $class . DIRECTORY_SEPARATOR . $class . '.php';
        return;
    }
}

spl_autoload_register('axm_autoloader');
