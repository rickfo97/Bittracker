-- phpMyAdmin SQL Dump
-- version 4.5.4.1deb2ubuntu2
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Apr 02, 2017 at 05:34 PM
-- Server version: 5.7.17-0ubuntu0.16.04.1
-- PHP Version: 7.0.15-0ubuntu0.16.04.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `tracker`
--

-- --------------------------------------------------------

--
-- Table structure for table `Peer`
--

CREATE TABLE `Peer` (
  `info_hash` binary(20) NOT NULL,
  `peer_id` binary(20) NOT NULL,
  `ip` varchar(16) NOT NULL,
  `port` smallint(5) UNSIGNED NOT NULL,
  `compact` binary(6) NOT NULL,
  `bytes_downloaded` bigint(20) UNSIGNED NOT NULL,
  `bytes_uploaded` bigint(20) UNSIGNED NOT NULL,
  `bytes_left` bigint(20) UNSIGNED NOT NULL,
  `status` enum('started','stopped','completed') NOT NULL DEFAULT 'started',
  `expires` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `torrent_pass` varchar(40) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `Torrent`
--

CREATE TABLE `Torrent` (
  `info_hash` binary(20) NOT NULL,
  `user_id` varchar(8) DEFAULT NULL,
  `complete` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `banned` tinyint(1) NOT NULL DEFAULT '0',
  `free_leech` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `Torrent_stats`
--

CREATE TABLE `Torrent_stats` (
  `info_hash` binary(20) NOT NULL,
  `complete` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `incomplete` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `downloaded` int(10) UNSIGNED DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `User`
--

CREATE TABLE `User` (
  `torrent_pass` varchar(40) NOT NULL,
  `download` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `upload` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `Peer`
--
ALTER TABLE `Peer`
  ADD PRIMARY KEY (`info_hash`,`peer_id`);

--
-- Indexes for table `Torrent`
--
ALTER TABLE `Torrent`
  ADD PRIMARY KEY (`info_hash`);

--
-- Indexes for table `Torrent_stats`
--
ALTER TABLE `Torrent_stats`
  ADD PRIMARY KEY (`info_hash`);

--
-- Indexes for table `User`
--
ALTER TABLE `User`
  ADD PRIMARY KEY (`torrent_pass`);

DELIMITER $$
--
-- Events
--
CREATE DEFINER=`root`@`localhost` EVENT `update_stats` ON SCHEDULE EVERY 90 MINUTE STARTS '2017-03-28 00:00:00' ON COMPLETION NOT PRESERVE ENABLE DO INSERT INTO Torrent_stats(info_hash, complete, incomplete, downloaded) 
SELECT stats.info_hash, stats.complete, stats.incomplete, stats.downloaded 
FROM (SELECT 
      Peer.info_hash, 
      COALESCE(SUM((CASE WHEN ((status = 'completed') OR (status = 'started' AND bytes_left = 0)) AND expires >= NOW() THEN 1 END)),0) as complete, 
      COALESCE(SUM((CASE WHEN (status = 'started' AND bytes_left > 0) AND expires >= NOW() THEN 1 END)), 0) as incomplete, 
      Torrent.complete as downloaded 
      FROM `Peer` 
      LEFT JOIN Torrent ON Torrent.info_hash = Peer.info_hash
      WHERE Peer.expires >= NOW() AND Peer.status != 'stopped'
      GROUP BY info_hash) as stats
ON DUPLICATE KEY UPDATE complete = stats.complete, incomplete = stats.incomplete, downloaded = stats.downloaded$$

CREATE DEFINER=`root`@`localhost` EVENT `user_stats` ON SCHEDULE EVERY 2 HOUR STARTS '2017-04-02 00:00:00' ON COMPLETION NOT PRESERVE ENABLE DO INSERT INTO User(torrent_pass, download, upload) SELECT torrent_pass, downloaded, uploaded FROM (SELECT torrent_pass, SUM(bytes_downloaded) as downloaded, SUM(bytes_uploaded) as uploaded FROM Peer WHERE Peer.torrent_pass != NULL GROUP BY Peer.torrent_pass) as stats
ON DUPLICATE KEY UPDATE download = stats.downloaded, upload = stats.uploaded$$

DELIMITER ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
