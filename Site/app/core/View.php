<?php

/**
 * Created by PhpStorm.
 * User: Rickardh
 * Date: 2017-03-11
 * Time: 12:21
 */
class View
{

    public static function render($path, $params = array()){
        return Twig::render($path, $params);
    }

}