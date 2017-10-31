<?php

namespace Rickfo97\Bittracker\Api;

use Rickfo97\Bittracker\Core\Tracker;
use Rickfo97\Bittracker\Core\Database;
use Rickfo97\Bittracker\Core\Config;

class Torrent
{

    //TODO Add torrent
    public static function add($info_hash)
    {
        $dbc = new Database();
        $stmt = $dbc->prepare("INSERT INTO " . Config::get('db_prefix') . "Torrent(info_hash, user_id) VALUES(:info_hash, :user)");
        return $stmt->execute([
            ':info_hash' => $info_hash,
            ':user' => Request::post('user_id')
        ]);
    }

    //TODO Remove torrent
    public static function remove($info_hash)
    {
        $dbc = new Database();
        $stmt = $dbc->prepare("DELETE FROM " . Config::get('db_prefix') . "Torrent WHERE info_hash = :info_hash");
        return $stmt->execute([
            ':info_hash' => $info_hash
        ]);
    }

    //TODO Update all or selected torrent stats
    public static function update($info_hash = "")
    {
        $core = new Tracker();
        return $core->updateStats($info_hash);
    }

    //TODO Set status for free leech
    public static function setFreeLeech($info_hash)
    {
        $dbc = new Database();
        $stmt = $dbc->prepare("UPDATE " . Config::get('db_prefix') . "Torrent SET free_leech = :leech WHERE info_hash = :info_hash");
        return $stmt->execute([
            ':leech' => Request::post('free_leech'),
            ':info_hash' => $info_hash
        ]);
    }

    //TODO Ban torrent based on info_hash
    public static function ban($info_hash)
    {
        $dbc = new Database();
        $stmt = $dbc->prepare("INSERT INTO " . Config::get('db_prefix') . "Torrent(info_hash, banned) VALUES(:info_hash, 1)");
        return $stmt->execute([
            ':info_hash' => $info_hash
        ]);
    }

}