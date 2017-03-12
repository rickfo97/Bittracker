<?php

/**
 * Created by PhpStorm.
 * User: Rickardh
 * Date: 2017-03-10
 * Time: 22:40
 */
class Route{

    private static $paths = array();
    private static $current = "";

    private static function addPath($method, $uri, $callback){
        self::$paths[] = array('method' => $method, 'uri' => $uri, 'callback' => $callback);
    }

    public static function get($uri, $callback){
        self::addPath('GET', $uri, $callback);
    }

    public static function post($uri, $callback){
        self::addPath('POST', $uri, $callback);
    }

    public static function delete($uri, $callback){
        self::addPath('DELETE', $uri, $callback);
    }

    public static function put($uri, $callback){
        self::addPath('PUT', $uri, $callback);
    }

    public static function patch($uri, $callback){
        self::addPath('PATCH', $uri, $callback);
    }

    public static function run(){
        self::$current = isset($_REQUEST['uri']) ? $_REQUEST['uri'] : '';
        $method = isset($_REQUEST['method']) ? $_REQUEST['method'] : $_SERVER['REQUEST_METHOD'];
        foreach (Route::$paths as $path){
            if($path['method'] == $method){
                $params = self::match($path['uri'], isset(self::$current) ? self::$current : $_SERVER['REQUEST_URI']);
                if($params !== false){
                    if (is_string($path['callback']) && strpos($path['callback'], '@') > 0){
                        $objectInfo = explode('@', $path['callback']);
                        if(sizeof($objectInfo) === 2){
                            return call_user_func_array(array(new $objectInfo[0](), $objectInfo[1]), $params);
                        }
                    }
                    if (is_callable($path['callback'])){
                        return call_user_func_array($path['callback'], $params);
                    }
                }
            }
        }
    }

    private static function match($uri, $toMatch){
        $uriParts = preg_split('@/@', $uri, NULL, PREG_SPLIT_NO_EMPTY);
        $matchParts = preg_split('@/@', $toMatch, NULL, PREG_SPLIT_NO_EMPTY);
        $params = array();
        if(sizeof($uriParts) == sizeof($matchParts)){
            foreach ($uriParts as $key => $part){
                if (strpos($part, '{{') === false){
                    if($part != $matchParts[$key]){
                        return false;
                    }
                }else{
                    $params[] = $matchParts[$key];
                }
            }
        }else{
            return false;
        }
        return $params;
    }
}