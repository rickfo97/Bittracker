<?php

include_once 'bencode/bencode.php';
include_once 'config.php';
class Core
{

    private $dbc;

    public function __construct()
    {
        $this->dbc = new Database();
    }

    public function announce()
    {
        Log::write('announcing ' . $_GET['info_hash']);
        if ($_SERVER['REQUEST_METHOD'] != 'GET') {
            Log::error('request method was not GET');
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
            Log::error('missing info_hash');
            return $this->announceError('Missing info_hash.');
        }
        if (!isset($request['peer_id'])) {
            Log::error('missing peer_id');
            return $this->announceError('Missing peer_id.');
        }
        if (!isset($request['port'])) {
            Log::error('missing port');
            return $this->announceError('Missing port.');
        }
        if (strlen($request['info_hash']) != 20) {
            Log::error('sent invalid info_hash: ' . $request['info_hash']);
            return $this->announceError('Invalid infohash: infohash is not 20 bytes long.');
        }
        if (strlen($request['peer_id']) != 20) {
            Log::error('sent invalid peer_id: ' . $request['peer_id']);
            return $this->announceError('Invalid peerid: peerid is not 20 bytes long.');
        }
        if (intval($request['numwant']) > Config::get('numwant_max')) {
            Log::error('sent invalud numwant: ' . $request['numwant']);
            return $this->announceError('Invalid numwant. Client requested more peers than allowed by tracker.');
        }
        if (!Config::get('open_track')) {
            if (!$this->torrentExits($request['info_hash'])) {
                Log::error('open_track is false');
                return $this->announceError('info_hash not found in the database.');
            }
        }

        //TODO Get feedback
        $peerAddSQL = "INSERT INTO `Peer`(`info_hash`, `peer_id`, `ip`, `port`, compact, `bytes_downloaded`, `bytes_uploaded`, `bytes_left`, status, expires, torrent_pass) VALUES (:info_hash, :peer_id, :ip, :port, :compact, :downloaded, :uploaded, :bytes_left, IFNULL(:status, DEFAULT(status)), DATE_ADD(NOW(), INTERVAL " . Config::get('expire_interval') . "), :torrent_pass) ON DUPLICATE KEY UPDATE ip = :ip, port = :port, compact = :compact, `bytes_downloaded` = :downloaded, `bytes_uploaded` = :uploaded, `bytes_left` = :bytes_left, status = " . (isset($request['event']) == true ? ':status' : 'status') . ", `expires` = DATE_ADD(NOW(), INTERVAL " . Config::get('expire_interval') . "), torrent_pass = :torrent_pass";
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
            Log::warning("Failed to add peer: " . $peerAdd->errorCode() . ' - ' . $peerAdd->errorInfo()[2]);
            Log::error($peerAddSQL);
            Log::error('ip: ' . $request['ip'] . '; port: ' . $request['port'] . '; downloaded: ' . $request['downloaded'] . '; uploaded: ' . $request['uploaded'] . '; bytes_left: ' . $request['left'] . '; event: ' . $request['event']);
        }

        $peerSQL = "SELECT ";
        if (!$request['no_peer_id']) {
            $peerSQL .= "peer_id as id, ";
        }
        $peerSQL .= "ip, port, compact FROM `Peer` WHERE info_hash = :info_hash AND peer_id != :peer_id AND status != 'stopped' AND expires >= NOW() LIMIT " . $request['numwant'];

        $peerStmt = $this->dbc->prepare($peerSQL);
        $peerResult = $peerStmt->execute(array(':info_hash' => $request['info_hash'], ':peer_id' => $request['peer_id']));
        if (!$peerResult) {
            Log::warning('Failed to fetch peers: ' . $peerStmt->errorCode() . ' - ' . $peerStmt->errorInfo()[2]);
            Log::error($peerSQL);
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
        $bResponse = Bencode::build($response);
        Log::write('Announce response: ' . $bResponse);
        return $bResponse;
    }

    public function scrape()
    {
        Log::write('scrapeing: ' . $_GET['info_hash']);
        $response = Bencode::build($_GET['info_hash']) . Bencode::build($this->torrentStats($_GET['info_hash']));
        return $response;
    }

    private function torrentExits($info_hash)
    {
        $result = $this->dbc->query("SELECT *FROM Torrent WHERE info_hash = " . $this->dbc->quote($info_hash));
        if ($result->rowCount() == 1) {
            return true;
        }
        return false;
    }

    private function torrentStats($info_hash)
    {
        $sqlHash = $this->dbc->quote($info_hash);
        $result = $this->dbc->query("SELECT (SELECT COUNT(*) FROM Peer WHERE (status = 'completed' || (status = 'started' && bytes_left = 0)) AND expires >= NOW() AND info_hash = " . $sqlHash . ") as complete, (SELECT COUNT(*) FROM Peer WHERE (status = 'started' && bytes_left > 0) AND expires >= NOW() AND info_hash = " . $sqlHash . ") as incomplete FROM Peer WHERE info_hash = " . $sqlHash . " GROUP BY info_hash AND peer_id");
        $stats = $result->fetch();
        return array('complete' => intval($stats[0]), 'incomplete' => intval($stats[1]));
    }

    private function announceError($message)
    {
        return Bencode::build(array('failure reason' => $message));
    }
}
