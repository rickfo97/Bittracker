SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;


CREATE TABLE `tracker_Peer` (
  `info_hash` binary(20) NOT NULL,
  `peer_id` binary(20) NOT NULL,
  `ip` varchar(16) COLLATE utf8_bin NOT NULL,
  `port` smallint(5) UNSIGNED NOT NULL,
  `compact` binary(6) NOT NULL,
  `bytes_downloaded` bigint(20) UNSIGNED NOT NULL,
  `bytes_uploaded` bigint(20) UNSIGNED NOT NULL,
  `bytes_left` bigint(20) UNSIGNED NOT NULL,
  `status` enum('started','stopped','completed') COLLATE utf8_bin NOT NULL DEFAULT 'started',
  `expires` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `torrent_pass` varchar(40) COLLATE utf8_bin DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

CREATE TABLE `tracker_Torrent` (
  `info_hash` binary(20) NOT NULL,
  `user_id` varchar(40) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `complete` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `banned` tinyint(1) NOT NULL DEFAULT '0',
  `free_leech` tinyint(1) NOT NULL DEFAULT '0',
  `seed` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `leech` bigint(20) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `tracker_User` (
  `torrent_pass` varchar(40) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `download` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `upload` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `banned` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


ALTER TABLE `tracker_Peer`
  ADD PRIMARY KEY (`info_hash`,`peer_id`);

ALTER TABLE `tracker_Torrent`
  ADD PRIMARY KEY (`info_hash`);

ALTER TABLE `tracker_User`
  ADD PRIMARY KEY (`torrent_pass`);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
