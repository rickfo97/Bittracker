<?php
/**
 * Created by PhpStorm.
 * User: Rickardh
 * Date: 2017-03-11
 * Time: 01:13
 */

require __DIR__ . '/vendor/autoload.php';

spl_autoload_register(function($class_name){
    include_once 'controller/' . $class_name . '.php';
});

include_once 'core/Config.php';
include_once 'core/Database.php';
include_once 'core/View.php';
include_once 'core/Route.php';
include_once 'core/Twig.php';

// Load in route
include_once 'route/Routes.php';

return Route::run();