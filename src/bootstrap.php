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

// Defines the application charset
const APP_CHARSET = 'UTF-8';

// Define the root path
defined('ROOT_PATH') or define('ROOT_PATH', getcwd());

// Defines the path of the dependencies
const VENDOR_PATH = ROOT_PATH . '/vendor';

// Define AXM framework installation path
const AXM_PATH = VENDOR_PATH . '/axm';

// Define the application path
const APP_PATH = ROOT_PATH . '/app';

// Defines the path for writing files
const STORAGE_PATH = ROOT_PATH . '/storage';

// Defines the clean path of the request URI
defined('PATH_CLEAR_URI') or define('PATH_CLEAR_URI', substr($_SERVER['SCRIPT_NAME'], 0, -9));

// Defines the development environment
const ENV_PRODUCTION = 'production';
const ENV_DEBUG = 'debug';

const APP_NAMESPACE = 'App\\';
const AXM_NAMESPACE = 'Axm\\';

require_once('axm_helper.php');

require_once(VENDOR_PATH . '/vlucas/phpdotenv/src/Dotenv.php');
try {
    \Dotenv\Dotenv::createImmutable(ROOT_PATH)->load();
} catch (\Throwable $th) {
    trigger_error($th);
}
