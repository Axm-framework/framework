<?php

namespace Model;

/**
 *  Class Model 
 * 
 * @author Juan Cristobal <juancristobalgd1@gmail.com>
 * @link http://www.axm.com/
 * @license http://www.axm.com/license/
 * @package Axm
 */
class Model extends \Illuminate\Database\Eloquent\Model
{
    public function __construct()
    {
        parent::__construct();
        app()->database::connect();
    }
}
