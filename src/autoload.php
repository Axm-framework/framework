<?php

function axm_autoloader(string $class)
{
    static $classMap;
    static $directories;

    $classMap ??= [

        'Container'   => AXM_PATH . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Container.php',
        'App'         => AXM_PATH . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'App.php',
        'Config'      => AXM_PATH . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Config.php',
        'Env'         => AXM_PATH . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Env.php',
        'Facade'      => AXM_PATH . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Facade.php',
        'Controller'  => AXM_PATH . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Controller.php',
        'BaseModel'   => AXM_PATH . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'BaseModel.php',
    ];

    if (isset($classMap[$class])) {
        include $classMap[$class];
        return;
    }

    if (is_file(AXM_PATH . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . $class . '.php')) {
        include AXM_PATH . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . $class . '.php';
        return;
    }

    if (is_file(AXM_PATH . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'libraries' . $class . DIRECTORY_SEPARATOR . $class . '.php')) {
        include AXM_PATH . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'libraries' . $class . DIRECTORY_SEPARATOR . $class . '.php';
        return;
    }

    $classMap ??= [
        'Container'   => AXM_PATH . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Container.php',
        'App'         => AXM_PATH . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'App.php',
        'Config'      => AXM_PATH . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Config.php',
        'Env'         => AXM_PATH . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Env.php',
        'Facade'      => AXM_PATH . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Facade.php',
        'Controller'  => AXM_PATH . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Controller.php',
        'BaseModel'   => AXM_PATH . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'BaseModel.php',
    ];

    if (isset($classMap[$class])) {
        include $classMap[$class];
        return;
    }

    $directories ??= [
        APP_PATH . DIRECTORY_SEPARATOR . 'Models' . DIRECTORY_SEPARATOR,
        APP_PATH . DIRECTORY_SEPARATOR . 'Controllers' . DIRECTORY_SEPARATOR,
        APP_PATH . DIRECTORY_SEPARATOR . 'Commands' . DIRECTORY_SEPARATOR,
        APP_PATH . DIRECTORY_SEPARATOR . 'Database' . DIRECTORY_SEPARATOR,
        APP_PATH . DIRECTORY_SEPARATOR . 'Factories' . DIRECTORY_SEPARATOR,
        APP_PATH . DIRECTORY_SEPARATOR . 'Seeds' . DIRECTORY_SEPARATOR,
        APP_PATH . DIRECTORY_SEPARATOR . 'Schema' . DIRECTORY_SEPARATOR,
        APP_PATH . DIRECTORY_SEPARATOR . 'Middlewares' . DIRECTORY_SEPARATOR,
        APP_PATH . DIRECTORY_SEPARATOR . 'Raxm' . DIRECTORY_SEPARATOR,
    ];

    if (isset($directories[$class]) && is_file($directories[$class] . '.php')) {
        include $classMap[$class] . '.php';
        return;
    }
}

spl_autoload_register('axm_autoloader');
