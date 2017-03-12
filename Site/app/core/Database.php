<?php

/**
 * Created by PhpStorm.
 * User: Rickardh
 * Date: 2017-03-04
 * Time: 13:42
 */
class Database{

    private static $factory;
    private $dbc;

    public static function getFactory(){
        if(!isset(self::$factory)){
            self::$factory = new Database();
        }
        return self::$factory;
    }

    public function getConnection(){
        if(!isset($this->dbc)){
            $this->dbc = new PDO(Config::get('db_driver') . ':host=' . Config::get('db_host') . ';dbname=' . Config::get('db_name') . ';charset=utf8', Config::get('db_user'), Config::get('db_password'));
        }
        return $this->dbc;
    }
}