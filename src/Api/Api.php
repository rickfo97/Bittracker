<?php

namespace Rickfo97\Bittracker\Api;


use Rickfo97\Bittracker\Core\Config;

class Api
{

    private static $retunValue = [];

    public static function process($method)
    {
        if (!Config::get('api')) {
            header("HTTP/1.1 404 Not Found");
            exit();
        }
        if (Config::get('api_force_https') && empty($_SERVER['HTTPS'])) {
            return 'Connection is not secure';
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return 'Request was not POST';
        }
        if (self::authenticate(Request::post('auth_token')) === false) {
            header("HTTP/1.1 401 Unauthorized");
            exit();
        }
        $target = $_GET['target'];
        if ($target === 'user') {
            switch ($method) {
                case 'add':
                    if (User::add(Request::post('torrent_pass'))) {
                        self::$retunValue['message'] = 'Success';
                    } else {
                        self::$retunValue['message'] = 'Failed';
                    }
                    break;
                case 'remove':
                    if (User::remove(Request::post('torrent_pass'))) {
                        self::$retunValue['message'] = 'Success';
                    } else {
                        self::$retunValue['message'] = 'Failed';
                    }
                    break;
                case 'change':
                    if (User::change(Request::post('torrent_pass'), Request::post('new_pass'))) {
                        self::$retunValue['message'] = 'Success';
                    } else {
                        self::$retunValue['message'] = 'Failed';
                    }
                    break;
                case 'update':
                    if (User::update(Request::post('torrent_pass'))) {
                        self::$retunValue['message'] = 'Success';
                    } else {
                        self::$retunValue['message'] = 'Failed';
                    }
                    break;
            }
        } elseif ($target === 'torrent') {
            $info_hash = Request::post('info_hash');
            if (strlen($info_hash) != 20) {
                return 'Invalid info_hash';
            }
            switch ($method) {
                case 'add':
                    if (Torrent::add($info_hash)) {
                        self::$retunValue['message'] = 'Success';
                    } else {
                        self::$retunValue['message'] = 'Failed';
                    }
                    break;
                case 'remove':
                    if (Torrent::remove($info_hash)) {
                        self::$retunValue['message'] = 'Success';
                    } else {
                        self::$retunValue['message'] = 'Failed';
                    }
                    break;
                case 'freeleech':
                    if (Torrent::setFreeLeech($info_hash)) {
                        self::$retunValue['message'] = 'Success';
                    } else {
                        self::$retunValue['message'] = 'Failed';
                    }
                    break;
                case 'update':
                    if (Torrent::update($info_hash)) {
                        self::$retunValue['message'] = 'Update successful';
                    } else {
                        self::$retunValue['message'] = 'Update failed';
                    }
                    break;
                case 'ban':
                    Torrent::ban($info_hash);
                    break;
            }
        } elseif ($target === 'tracker') {
            switch ($method){
                case 'settings':
                    if(Tracker::changeSettings(Request::post('newSettings'))){
                        self::$retunValue['message'] = 'Success';
                    } else {
                        self::$retunValue['message'] = 'Failed';
                    }
                    break;
            }
        }
        return self::$retunValue;
    }

    private static function authenticate($token)
    {
        //TODO Check if ip is okay
        $api_token = Config::get('api_token');
        if (strlen($api_token) === 0 && strlen($token) === 0) {
            Config::change('api_token', self::generateToken());
            Config::save();
            self::$retunValue['token'] = Config::get('api_token');
        }
        if ($token === Config::get('api_token')) {
            return true;
        }
        return false;
    }

    private static function generateToken()
    {
        return bin2hex(random_bytes(16));
    }
}