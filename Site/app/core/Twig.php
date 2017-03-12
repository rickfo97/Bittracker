<?php

/**
 * Created by PhpStorm.
 * User: Rickardh
 * Date: 2017-03-11
 * Time: 13:03
 */
class Twig{

    private static $loader;
    private static $twig;

    public static function getTwig(){
        if(!isset(self::$twig)){
            self::$loader = new Twig_Loader_Filesystem(__DIR__ . '/../template');
            self::$twig = new Twig_Environment(self::$loader, array(
                'debug' => true,
                'cache' => __DIR__ . '/../cache/twig'
            ));
        }
        return self::$twig;
    }

    public static function render($path, $context){
        $twig = self::getTwig();
        return $twig->render($path, $context);
    }
}