-- phpMyAdmin SQL Dump
-- version 3.4.11.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Oct 15, 2013 at 04:27 AM

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

-- --------------------------------------------------------

--
-- Table structure for table `craft`
--

CREATE TABLE IF NOT EXISTS `craft` (
  `id` int(32) unsigned NOT NULL AUTO_INCREMENT,
  `link` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `name` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `craft` varchar(2048) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `link` (`link`,`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `items`
--

CREATE TABLE IF NOT EXISTS `items` (
  `id` int(32) unsigned NOT NULL AUTO_INCREMENT,
  `link` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `name` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `damage` varchar(1024) COLLATE utf8_unicode_ci NOT NULL,
  `effects` varchar(1024) COLLATE utf8_unicode_ci NOT NULL,
  `conditions` varchar(1024) COLLATE utf8_unicode_ci NOT NULL,
  `features` varchar(2048) COLLATE utf8_unicode_ci NOT NULL,
  `craft` varchar(2048) COLLATE utf8_unicode_ci NOT NULL,
  `image` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `link` (`link`,`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sets`
--

CREATE TABLE IF NOT EXISTS `sets` (
  `id` int(32) unsigned NOT NULL AUTO_INCREMENT,
  `link` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `name` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `items` varchar(2048) COLLATE utf8_unicode_ci NOT NULL,
  `bonus` varchar(4096) COLLATE utf8_unicode_ci NOT NULL,
  `characteristics` varchar(2048) COLLATE utf8_unicode_ci NOT NULL,
  `attack` varchar(2048) COLLATE utf8_unicode_ci NOT NULL,
  `defense` varchar(2048) COLLATE utf8_unicode_ci NOT NULL,
  `misc` varchar(2048) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `link` (`link`,`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `translate`
--

CREATE TABLE IF NOT EXISTS `translate` (
  `id` int(32) unsigned NOT NULL AUTO_INCREMENT,
  `fr` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `pt` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `it` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `nl` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `de` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `en` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `es` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `jp` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `en` (`en`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
