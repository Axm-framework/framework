<?php

/**
 * @author Juan Cristobal <juancristobalgd1@gmail.com>
 * @link http://www.axm.com/
 * @license http://www.axm.com/license/
 * @package Axm
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
const AXM_PATH = ROOT_PATH . DIRECTORY_SEPARATOR . 'core';

// Define the application path
const APP_PATH = ROOT_PATH . DIRECTORY_SEPARATOR . 'app';

// Defines the path for writing files
const STORAGE_PATH = ROOT_PATH . DIRECTORY_SEPARATOR . 'storage';

const APP_NAMESPACE = 'App\\';

/**
--------------------------------------------------------------------------------
                       FILES FOR INITIALIZATION                                     
-------------------------------------------------------------------------------- */
require(ROOT_PATH . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'autoload.php');
require(ROOT_PATH . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'HandlerErrors.php');
require(ROOT_PATH . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'functions.php');
// Add Composer autoload to load external dependencies
require VENDOR_PATH . DIRECTORY_SEPARATOR . 'autoload.php';
