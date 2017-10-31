<?php

namespace Rickfo97\Bittracker\Api;


use Rickfo97\Bittracker\Core\Config;

class Tracker
{
    public static function changeSettings($newSettings)
    {
        foreach ($newSettings as $key => $value){
            Config::change($key, $value);
        }
        return Config::save() === false ? false : true;
    }
}