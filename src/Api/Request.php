<?php

namespace Rickfo97\Bittracker\Api;


class Request
{
    public static function post($key)
    {
        if (isset($_POST[$key])){
            return $_POST[$key];
        }
    }
}