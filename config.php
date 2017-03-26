<?php
class Config
{

    private static $config;

    public static function get($key)
    {
      if(is_null(self::$config)){
        self::$config = parse_ini_file('config.ini');
      }
      if (key_exists($key, self::$config)) {
          return self::$config[$key];
      }
    }
}
