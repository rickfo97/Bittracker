<?php
class Config
{

    private static $config = array(
        'db_driver' => 'mysql',
        'db_host' => 'localhost',
        'db_name' => 'tracker',
        'db_user' => 'root',
        'db_password' => '',

        'numwant_max' => 100,
        'announce_interval' => (90 * 60),
        'announce_interval_min' => (60 * 60),
        'open_track' => true,
        'expire_interval' => '2 HOUR',
        'save_stats' => false,
        'full_scrape' => false,

        'log_file' => 'log.txt',
        'time_format' => 'Y-m-d H:i:s'
    );

    public static function get($key)
    {
        if (key_exists($key, self::$config)) {
            return self::$config[$key];
        }
    }
}
