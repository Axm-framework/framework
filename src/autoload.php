<?php

/**
 * @author Juan Cristobal <juancristobalgd1@gmail.com>
 * @link http://www.axm.com/
 * @license http://www.axm.com/license/
 * @package Framework
 * 
----------------------------------------------------------------------
                        AXM AUTOLOAD                        
----------------------------------------------------------------------*/

function axm_autoloader(string $class)
{
    static $classMap = [
        'Axm'        => AXM_PATH . DIRECTORY_SEPARATOR . 'Axm.php',
        'Container'  => AXM_PATH . DIRECTORY_SEPARATOR . 'Container.php',
        'App'        => AXM_PATH . DIRECTORY_SEPARATOR . 'App.php',
        'Config'     => AXM_PATH . DIRECTORY_SEPARATOR . 'Config.php',
        'Env'        => AXM_PATH . DIRECTORY_SEPARATOR . 'Env.php',
        'Facade'     => AXM_PATH . DIRECTORY_SEPARATOR . 'Facade.php',
        'Controller' => AXM_PATH . DIRECTORY_SEPARATOR . 'Controller.php',
        'BaseModel'  => AXM_PATH . DIRECTORY_SEPARATOR . 'BaseModel.php',
    ];
    
    if (isset($classMap[$class])) return include $classMap[$class];    
     
    $lclass =  str_replace('\\', DIRECTORY_SEPARATOR, $class);
    if (is_file(AXM_PATH . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . $lclass . '.php'))
        return include AXM_PATH . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . $lclass . '.php';
    

    if (is_file(AXM_PATH . DIRECTORY_SEPARATOR . 'libraries' . $lclass . DIRECTORY_SEPARATOR . $lclass . '.php'))
        return include AXM_PATH . DIRECTORY_SEPARATOR . 'libraries' . $lclass . DIRECTORY_SEPARATOR . $lclass . '.php';

}

spl_autoload_register('axm_autoloader');
