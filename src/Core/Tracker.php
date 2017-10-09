<?php

namespace Rickfo97\Bittracker\Core;

use Rickfo97\Bittracker\Logger\Log;

class Tracker
{

    private $dbc;

    public function __construct($settings = [])
    {
        $this->dbc = new Database();
        if(sizeof($settings) > 0){
            Config::set($settings);
        }
    }

    public function announce()
    {
        Log::getDefaultLogger()->debug('announcing ' . bin2hex($_GET['info_hash']));

        if ($_SERVER['REQUEST_METHOD'] != 'GET') {
            Log::getDefaultLogger()->error('request method was not GET');
            return $this->announceError('Invalid request type: client request was not a HTTP GET.');
        }

        if (get_magic_quotes_gpc()) {
            $_GET['info_hash'] = stripslashes($_GET['info_hash']);
            $_GET['peer_id'] = stripslashes($_GET['peer_id']);
        }

        $request = array(
            'info_hash' => $_GET['info_hash'],
            'peer_id' => $_GET['peer_id'],
            'ip' => isset($_GET['ip']) == true ? $_GET['ip'] : $_SERVER['REMOTE_ADDR'],
            'port' => $_GET['port'],
            'uploaded' => $_GET['uploaded'],
            'downloaded' => $_GET['downloaded'],
            'left' => $_GET['left'],
            'event' => $_GET['event'],
            'numwant' => $_GET['numwant'],
            'no_peer_id' => isset($_GET['no_peer_id']) == true ? $_GET['no_peer_id'] : false,
            'compact' => $_GET['compact'],
            'torrent_pass' => $_GET['torrent_pass']
        );
        if (!isset($request['info_hash'])) {
            Log::getDefaultLogger()->error('missing info_hash');
            return $this->announceError('Missing info_hash.');
        }
        if (!isset($request['peer_id'])) {
            Log::getDefaultLogger()->error('missing peer_id');
            return $this->announceError('Missing peer_id.');
        }
        if (!isset($request['port'])) {
            Log::getDefaultLogger()->error('missing port');
            return $this->announceError('Missing port.');
        }
        if (strlen($request['info_hash']) != 20) {
            Log::getDefaultLogger()->error('sent invalid info_hash: ' . $request['info_hash']);
            return $this->announceError('Invalid infohash: infohash is not 20 bytes long.');
        }
        if (strlen($request['peer_id']) != 20) {
            Log::getDefaultLogger()->error('sent invalid peer_id: ' . $request['peer_id']);
            return $this->announceError('Invalid peerid: peerid is not 20 bytes long.');
        }
        if (intval($request['numwant']) > Config::get('numwant_max')) {
            if (Config::get('numwant_max_force')) {
                Log::getDefaultLogger()->error('sent invalid numwant: ' . $request['numwant']);
                return $this->announceError('Invalid numwant. Client requested more peers than allowed by tracker.');
            } else {
                Log::getDefaultLogger()->warning('sent invalid numwant: ' . $request['numwant']);
                $request['numwant'] = Config::get('numwant_max');
            }
        }
        if (Config::get('private')) {
            if (!isset($request['torrent_pass']) || strlen($request['torrent_pass']) == 0) {
                Log::getDefaultLogger()->debug("Not a member of tracker: " . $request['torrent_pass']);
                return $this->announceError("Not a member of tracker");
            } else {
                $member = $this->dbc->prepare("SELECT *FROM tracker_User WHERE torrent_pass = :torrent_pass");
                $member->execute([':torrent_pass' => $request['torrent_pass']]);
                if ($member->rowCount() != 1) {
                    Log::getDefaultLogger()->debug("Not a member of tracker: " . $request['torrent_pass']);
                    return $this->announceError("Not a member of tracker");
                }
            }
        }
        if (!Config::get('auto_track')) {
            if (!$this->torrentExists($request['info_hash'])) {
                Log::getDefaultLogger()->error('auto_track is false');
                return $this->announceError('info_hash not found in the database.');
            }
        }
        if (Config::get('ratio_limit') && (!$this->freeLeech($request['info_hash']) && $request['left'] > 0)) {
            $ratio_stmt = null;
            if (isset($request['torrent_pass'])) {
                $ratio_stmt = $this->dbc->prepare("SELECT COALESCE ((upload / download), -1) as ratio, created FROM " . Config::get('db_prefix') . "User WHERE torrent_pass = :torrent_pass");
                $ratio_stmt->execute([':torrent_pass' => $request['torrent_pass']]);
            } elseif (!Config::get('private')) {
                $ratio_stmt = $this->dbc->prepare("SELECT (SUM(bytes_uploaded) / SUM(bytes_downloaded)) as ratio FROM " . Config::get('db_prefix') . "Peer WHERE peer_id = :peer_id AND ip = :ip");
                $ratio_stmt->execute([':peer_id' => $request['peer_id'], ':ip' => $request['ip']]);
            }
            if ($ratio_stmt->rowCount() == 1) {
                $user = $ratio_stmt->fetchObject();
                Log::getDefaultLogger()->debug("Ratio: " . $user->ratio);
                if (($user->ratio < Config::get('ratio_min_limit') && $user->ratio != -1) && (strtotime($user->created) + (Config::get('ratio_grace_time') * 3600)) < (time())) {
                    return $this->announceError("Ratio is to low");
                }
            } else {
                if (isset($request['torrent_pass'])) {
                    Log::getDefaultLogger()->warning('Torrent_pass not in database');
                } elseif (Config::get('private')) {
                    Log::getDefaultLogger()->error($_SERVER['REMOTE_ADDR'] . ' tried to announce without torrent_pass');
                    return $this->announceError("Not part of tracker");
                }
            }
        }
        if (!Config::get('save_stats') && $request['event'] == 'stopped') {
            $removeStmt = $this->dbc->prepare("DELETE FROM Peer WHERE info_hash = :info_hash AND peer_id = :peer_id");
            $success = $removeStmt->execute([
                ':info_hash' => $request['info_hash'],
                ':peer_id' => $request['peer_id']
            ]);
            if (!$success) {
                Log::getDefaultLogger()->error('Failed to remove peer: ' . $removeStmt->errorCode() . ' - ' . $removeStmt->errorInfo()[2]);
            }
            die();
        }

        $peerAddSQL = "INSERT INTO `" . Config::get('db_prefix') . "Peer`(`info_hash`, `peer_id`, `ip`, `port`, compact, `bytes_downloaded`, `bytes_uploaded`, `bytes_left`, status, expires, torrent_pass) VALUES (:info_hash, :peer_id, :ip, :port, :compact, :downloaded, :uploaded, :bytes_left, IFNULL(:status, DEFAULT(status)), DATE_ADD(NOW(), INTERVAL " . Config::get('expire_interval') . "), :torrent_pass) ON DUPLICATE KEY UPDATE ip = :ip, port = :port, compact = :compact, `bytes_downloaded` = :downloaded, `bytes_uploaded` = :uploaded, `bytes_left` = :bytes_left, status = " . (isset($request['event']) == true ? ':status' : 'status') . ", `expires` = DATE_ADD(NOW(), INTERVAL " . Config::get('expire_interval') . "), torrent_pass = :torrent_pass";
        $peerAddParams = array(
            ':info_hash' => $request['info_hash'],
            ':peer_id' => $request['peer_id'],
            ':ip' => $request['ip'],
            ':port' => $request['port'],
            ':compact' => pack('Nn', ip2long($request['ip']), $request['port']),
            ':downloaded' => $request['downloaded'],
            ':uploaded' => $request['uploaded'],
            ':bytes_left' => $request['left'],
            ':status' => $request['event'],
            ':torrent_pass' => isset($_GET['torrent_pass']) == true ? $_GET['torrent_pass'] : null
        );

        $peerAdd = $this->dbc->prepare($peerAddSQL);
        $result = $peerAdd->execute($peerAddParams);
        if (!$result) {
            Log::getDefaultLogger()->warning("Failed to add peer: " . $peerAdd->errorCode() . ' - ' . $peerAdd->errorInfo()[2]);
            Log::getDefaultLogger()->debug($peerAddSQL);
            Log::getDefaultLogger()->debug('ip: ' . $request['ip'] . '; port: ' . $request['port'] . '; downloaded: ' . $request['downloaded'] . '; uploaded: ' . $request['uploaded'] . '; bytes_left: ' . $request['left'] . '; event: ' . $request['event']);
        }

        if ($request[':status'] == 'finished') {
            $downloadStmt = $this->dbc->prepare("INSERT INTO " . Config::get('db_prefix') . "Torrent(info_hash, complete) VALUES(:info_hash, 1) ON DUPLICATE KEY UPDATE complete = (complete + 1)");
            $result = $downloadStmt->execute([':info_hash' => $request['info_hash']]);
            if (!$result) {
                Log::getDefaultLogger()->error("Failed to add completed downloads");
            }
        }

        $peerSQL = "SELECT " . (!$request['no_peer_id'] ? "peer_id as id, " : '') . "ip, port, compact FROM `" . Config::get('db_prefix') . "Peer` WHERE info_hash = :info_hash AND peer_id != :peer_id AND status != 'stopped' AND expires >= NOW() LIMIT " . $request['numwant'];
        $peerStmt = $this->dbc->prepare($peerSQL);
        $peerResult = $peerStmt->execute(array(':info_hash' => $request['info_hash'], ':peer_id' => $request['peer_id']));
        if (!$peerResult) {
            Log::getDefaultLogger()->warning('Failed to fetch peers: ' . $peerStmt->errorCode() . ' - ' . $peerStmt->errorInfo()[2]);
            Log::getDefaultLogger()->error($peerSQL);
        }

        $response = array('interval' => Config::get('announce_interval'), 'min interval' => Config::get('announce_interval_min'));
        if ($request['compact']) {
            $peerString = "";
            while ($peer = $peerStmt->fetchObject()) {
                $peerString .= $peer->compact;
            }
            $response['peers'] = $peerString;
        } else {
            $response['peers'] = $peerStmt->fetchAll();
        }
        $response = array_merge($response, $this->torrentStats($request['info_hash']));
        $bResponse = BEncode::build($response);
        Log::getDefaultLogger()->debug('Announce response: ' . $bResponse);
        Log::getDefaultLogger()->debug("Announce execution time: " . (microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"]));
        return $bResponse;
    }

    public function scrape()
    {
        $response = "";
        if (Config::get('full_scrape') && !isset($_GET['info_hash'])) {
            Log::getDefaultLogger()->info($_SERVER['REMOTE_ADDR'] . " is performing a full scrape");
            $response = BEncode::build($this->dbc->query("SELECT info_hash, seed, leech, complete FROM " . Config::get('db_prefix') . "Torrent", \PDO::FETCH_ASSOC));
        } else {
            Log::getDefaultLogger()->debug('scrapeing: ' . bin2hex($_GET['info_hash']));
            $stats = $this->torrentStats($_GET['info_hash']);
            Log::getDefaultLogger()->debug("Scrape stats: ", $stats);
            $response = BEncode::build(['files' => [$_GET['info_hash'] => $stats]]);
        }
        Log::getDefaultLogger()->debug($response);
        Log::getDefaultLogger()->debug("Scrape execution time: " . (microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"]));
        return $response;
    }

    private function torrentExists($info_hash)
    {
        $result = $this->dbc->query("SELECT *FROM " . Config::get('db_prefix') . "Torrent WHERE banned = 0 AND info_hash = " . $this->dbc->quote($info_hash));
        if ($result->rowCount() == 1) {
            return true;
        }
        return false;
    }

    private function freeLeech($info_hash)
    {
        $torrent = $this->dbc->prepare("SELECT free_leech FROM " . Config::get('db_prefix') . "Torrent WHERE info_hash = :info_hash");
        $torrent->execute([':info_hash' => $info_hash]);
        if ($torrent->rowCount() == 1) {
            return $torrent->fetchObject()->free_leech;
        }
        return 0;
    }

    private function torrentStats($info_hash)
    {
        $result = $this->dbc->prepare("SELECT COALESCE (seed, 0) as seed, COALESCE (leech, 0) as leech FROM " . Config::get('db_prefix') . "Torrent WHERE info_hash = :info_hash");
        $result->execute([':info_hash' => $info_hash]);
        $stats = [];
        if ($result->rowCount() == 1) {
            $stats = $result->fetch(\PDO::FETCH_ASSOC);
        } else {
            $this->updateStats($info_hash);
            $result->execute([':info_hash' => $info_hash]);
            $stats = $result->fetch(\PDO::FETCH_ASSOC);
        }
        Log::getDefaultLogger()->debug("Torrent stats: " . $stats[0] . " : " . $stats[1]);
        return array('complete' => intval($stats['seed']), 'incomplete' => intval($stats['leech']));
    }

    public function updateStats($info_hash)
    {
        $addStats = $this->dbc->prepare("INSERT INTO " . Config::get('db_prefix') . "Torrent(info_hash, seed, leech) SELECT stats.info_hash, stats.complete, stats.incomplete FROM (SELECT " . Config::get('db_prefix') . "Peer.info_hash, COALESCE(SUM((CASE WHEN ((status = 'completed') OR (status = 'started' AND bytes_left = 0)) AND expires >= NOW() THEN 1 END)),0) as complete, COALESCE(SUM((CASE WHEN (status = 'started' AND bytes_left > 0) AND expires >= NOW() THEN 1 END)), 0) as incomplete FROM `" . Config::get('db_prefix') . "Peer` WHERE " . Config::get('db_prefix') . "Peer.expires >= NOW() AND " . Config::get('db_prefix') . "Peer.status != 'stopped' AND " . Config::get('db_prefix') . "Peer.info_hash = :info_hash GROUP BY info_hash) as stats ON DUPLICATE KEY UPDATE seed = stats.complete, leech = stats.incomplete");
        $success = $addStats->execute([
            ':info_hash' => $info_hash
        ]);
        if (!$success) {
            Log::getDefaultLogger()->error("Failed to add torrent stats: " . $addStats->errorCode() . ' - ' . $addStats->errorInfo()[2]);
        }
        return $success;
    }

    private function announceError($message)
    {
        return BEncode::build(array('failure reason' => $message));
    }

    private function checkStats($info_hash, $torrent_pass)
    {
        $torrentStmt = $this->dbc->prepare("SELECT SUM(bytes_downloaded) as download, SUM(bytes_uploaded) as upload FROM " . Config::get('db_prefix') . "Peer WHERE info_hash = :infohash AND torrent_pass = :torrentpass");
        $torrentStmt->execute([
            ':infohash' => $info_hash,
            ':torrentpass' => $torrent_pass
        ]);
        if($torrent = $torrentStmt->fetchObject()){

        }else{
            return true;
        }
    }
}
