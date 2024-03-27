<?php

/**
 * @author Juan Cristobal <juancristobalgd1@gmail.com>
 * @link http://www.axm.com/
 * @license http://www.axm.com/license/
 * @package Framework
 * 
------------------------------------------------------------------------------
                        SETUP OUR PATH CONSTANTS                         
-------------------------------------------------------------------------------*/

// Get the current directory
defined('ROOT_PATH') or define('ROOT_PATH', getcwd());

// Define the public path
const PUBLIC_PATH = ROOT_PATH . DIRECTORY_SEPARATOR . 'public';

// Defines the path of the dependencies
const VENDOR_PATH = ROOT_PATH . DIRECTORY_SEPARATOR . 'vendor';

// Define AXM framework installation path
const AXM_PATH = VENDOR_PATH . DIRECTORY_SEPARATOR . 'axm'
    . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'src';

// Define the application path
const APP_PATH = ROOT_PATH . DIRECTORY_SEPARATOR . 'app';

// Defines the path for writing files
const STORAGE_PATH = ROOT_PATH . DIRECTORY_SEPARATOR . 'storage';

const APP_NAMESPACE = 'App\\';

/**
--------------------------------------------------------------------------------
                       FILES FOR INITIALIZATION                                     
-------------------------------------------------------------------------------- */
require (AXM_PATH . DIRECTORY_SEPARATOR . 'autoload.php');
require (AXM_PATH . DIRECTORY_SEPARATOR . 'HandlerErrors.php');
require (AXM_PATH . DIRECTORY_SEPARATOR . 'functions.php');

// Add Composer autoload to load external dependencies
require VENDOR_PATH . DIRECTORY_SEPARATOR . 'autoload.php';
