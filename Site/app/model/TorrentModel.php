<?php

/**
 * Created by PhpStorm.
 * User: Rickardh
 * Date: 2017-03-11
 * Time: 20:12
 */

class Torrent{

    public static function getTorrent($info_hash){
        $dbc = Database::getFactory()->getConnection();
        $stmt = $dbc->prepare("SELECT info_hash, name, description, path, magnet FROM Torrent WHERE info_hash = :info_hash");
        $stmt->execute(array(':info_hash' => $info_hash));
        if($torrent = $stmt->fetchObject()){
            return $torrent;
        }
        return false;
    }

    public static function addTorrent($torrent){
        $dbc = Database::getFactory()->getConnection();
        $stmt = $dbc->prepare("INSERT INTO Torrent(info_hash, name, description, path, magnet) VALUES(:info_hash, :name, :description, :path, :magnet)");
        $success = $stmt->execute(array(
            ':info_hash' => $torrent->info_hash,
            ':name' => $torrent->name,
            ':description' => $torrent->description,
            ':path' => $torrent->path,
            ':magnet' => $torrent->magnet
        ));
        if($success){
            return $torrent->info_hash;
        }
        return false;
    }

    public static function getRecent($limit){
        $dbc = Database::getFactory()->getConnection();
        $stmt = $dbc->prepare("SELECT info_hash, name, description, path, magnet, date_added FROM Torrent ORDER BY date_added DESC LIMIT $limit");
        $stmt->execute();
        $torrents = array();
        while ($torrents[] = $stmt->fetchObject());
        return $torrents;
    }

    //TODO search based on name maybe description. Add to search terms.
    public static function searchTorrent(){

    }
}