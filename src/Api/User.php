<?php

namespace Rickfo97\Bittracker\Api;

use Rickfo97\Bittracker\Core\Database;
use Rickfo97\Bittracker\Logger\Log;
use Rickfo97\Bittracker\Core\Config;


class User
{

    //TODO Add new user
    public static function add($torrent_pass)
    {
        $dbc = new Database();
        $stmt = $dbc->prepare("INSERT INTO " . Config::get('db_prefix') . "User (torrent_pass) VALUES(:torrent_pass)");
        return $stmt->execute([
            ':torrent_pass' => $torrent_pass
        ]);
    }

    //TODO Remove selected user
    public static function remove($torrent_pass)
    {
        $dbc = new Database();
        $stmt = $dbc->prepare("DELETE FROM " . Config::get('db_prefix') . "User WHERE torrent_pass = :torrent_pass");
        if($stmt->execute([':torrent_pass' => $torrent_pass])){
            $peerStmt = $dbc->prepare("DELETE FROM " . Config::get('db_prefix') . "Peer WHERE torrent_pass = :torrent_pass");
            $peerStmt->execute([':torrent_pass' => $torrent_pass]);
        }else{
            Log::getDefaultLogger()->debug("API failed to remove user");
            return false;
        }
    }

    //TODO Change torrent_pass in User and Peer
    public static function change($torrent_pass, $new_torrent_pass)
    {
        $dbc = new Database();
        $stmt = $dbc->prepare("UPDATE " . Config::get('db_prefix') . "User SET torrent_pass = :new_torrent_pass WHERE torrent_pass = :torrent_pass");
        if($stmt->execute([':torrent_pass' => $torrent_pass, ':new_torrent_pass' => $new_torrent_pass])){
            $peerStmt = $dbc->prepare("UPDATE " . Config::get('db_prefix') . "Peer SET torrent_pass = :new_torrent_pass WHERE torrent_pass = :torrent_pass");
            $peerStmt->execute([':torrent_pass' => $torrent_pass, ':new_torrent_pass' => $new_torrent_pass]);
            $torrentStmt = $dbc->prepare("UPDATE " . Config::get('db_prefix') . "Torrent SET user_id = :new_torrent_pass WHERE user_id = :torrent_pass");
            $torrentStmt->execute([':torrent_pass' => $torrent_pass, ':new_torrent_pass' => $new_torrent_pass]);
            return true;
        }else{
            Log::getDefaultLogger()->debug("API failed to change torrent_pass");
            return false;
        }
    }

    //TODO Update all or selected users stats
    public static function update($torrent_pass = 0)
    {
        $dbc = new Database();
        $stmt = $dbc->prepare("INSERT INTO " . Config::get('db_prefix') . "User(torrent_pass, download, upload) SELECT torrent_pass, downloaded, uploaded FROM (SELECT torrent_pass, SUM(bytes_downloaded) as downloaded, SUM(bytes_uploaded) as uploaded FROM Peer WHERE " . ($torrent_pass != 0 ? 'Peer.torrent_pass = :torrent_pass' : 'Peer.torrent_pass IS NOT NULL') . " GROUP BY Peer.torrent_pass) as stats ON DUPLICATE KEY UPDATE download = stats.downloaded, upload = stats.uploaded");
        if ($torrent_pass != 0){
            return $stmt->execute();
        }else{
            return $stmt->execute([':torrent_pass' => $torrent_pass]);
        }
    }

    //TODO Ban by user
    public static function ban($torrent_pass)
    {
        $dbc = new Database();
        $stmt = $dbc->prepare("UPDATE " . Config::get('db_prefix') . "User SET banned = 1 WHERE torrent_pass = :torrent_pass");
        return $stmt->execute([':torrent_pass' => $torrent_pass]);
    }
}