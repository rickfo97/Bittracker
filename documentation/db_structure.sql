-- phpMyAdmin SQL Dump
-- version 4.5.4.1deb2ubuntu2
-- http://www.phpmyadmin.net
--
-- Värd: localhost
-- Tid vid skapande: 26 mars 2017 kl 00:24
-- Serverversion: 5.7.17-0ubuntu0.16.04.1
-- PHP-version: 7.0.15-0ubuntu0.16.04.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Databas: `tracker`
--

-- --------------------------------------------------------

--
-- Tabellstruktur `Peer`
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
-- Tabellstruktur `Torrent`
--

CREATE TABLE `Torrent` (
  `info_hash` binary(20) NOT NULL,
  `user_id` varchar(8) NOT NULL,
  `banned` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Index för dumpade tabeller
--

--
-- Index för tabell `Peer`
--
ALTER TABLE `Peer`
  ADD PRIMARY KEY (`info_hash`,`peer_id`);

--
-- Index för tabell `Torrent`
--
ALTER TABLE `Torrent`
  ADD PRIMARY KEY (`info_hash`);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
