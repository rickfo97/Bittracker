<?php

/**
 * Created by PhpStorm.
 * User: Rickardh
 * Date: 2017-03-05
 * Time: 13:58
 */
include_once '../config.php';

class Log{

    public static function write($message){
        $write = '[INFO] - [' . $_SERVER['REMOTE_ADDR'] . '] '. $message;
        self::append($write);
    }

    public static function warning($message){
        $warning = '[WARNING] - [' . $_SERVER['REMOTE_ADDR'] . '] ' . $message;
        self::append($warning);
    }

    public static function error($message){
        $error = '[ERROR] - [' . $_SERVER['REMOTE_ADDR'] . '] ' . $message;
        self::append($error);
    }

    private static function append($message){
        file_put_contents(Config::get('log_file'), date(Config::get('time_format')) . ' ' . $message . "\n", FILE_APPEND);
    }

}