<?php

/**
 * @author Juan Cristobal <juancristobalgd1@gmail.com>
 * @link http://www.axm.com/
 * @license http://www.axm.com/license/
 * @package Axm
 * 
---------------------------------------------------------
               SETUP OUR PATH CONSTANTS
--------------------------------------------------------- */

// Define the root path
defined('AXM_BEGIN_TIME') or define('AXM_BEGIN_TIME', time());

// Defines the application charset
const APP_CHARSET = 'UTF-8';

defined('ROOT_PATH') or define('ROOT_PATH', getcwd());

// Define the public path
// const PUBLIC_PATH = '/';
const PUBLIC_PATH = ROOT_PATH . DIRECTORY_SEPARATOR . 'public';

// Defines the path of the dependencies
const VENDOR_PATH = ROOT_PATH . DIRECTORY_SEPARATOR . 'vendor';

// Define AXM framework installation path
const AXM_PATH = VENDOR_PATH . DIRECTORY_SEPARATOR . 'axm';

// Define the application path
const APP_PATH = ROOT_PATH . DIRECTORY_SEPARATOR . 'app';

// Defines the path for writing files
const STORAGE_PATH = ROOT_PATH . DIRECTORY_SEPARATOR . 'storage';

// Defines the clean path of the request URI
defined('CLEAN_URI_PATH') or define('CLEAN_URI_PATH', substr($_SERVER['SCRIPT_NAME'], 0, -9));

// Defines the development environment
const ENV_PRODUCTION = 'production';
const ENV_DEBUG = 'debug';

const APP_NAMESPACE = 'App\\';
const AXM_NAMESPACE = 'Axm\\';

require_once('axm_helper.php');

require_once(VENDOR_PATH . DIRECTORY_SEPARATOR . 'vlucas' . DIRECTORY_SEPARATOR .
    'phpdotenv' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Dotenv.php');

try {
    \Dotenv\Dotenv::createImmutable(ROOT_PATH)->load();
} catch (\Throwable $th) {
    trigger_error($th);
}
