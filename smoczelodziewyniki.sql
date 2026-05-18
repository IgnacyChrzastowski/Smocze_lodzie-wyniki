-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Maj 18, 2026 at 08:40 PM
-- Wersja serwera: 10.4.32-MariaDB
-- Wersja PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `smoczelodziewyniki`
--
CREATE DATABASE IF NOT EXISTS `smoczelodziewyniki` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `smoczelodziewyniki`;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `druzyny`
--

CREATE TABLE IF NOT EXISTS `druzyny` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nazwa` text NOT NULL,
  `wynik` varchar(16) DEFAULT NULL,
  `miejsce` int(11) NOT NULL,
  `id_wyscigu` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_id_wyscigu` (`id_wyscigu`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `druzyny`
--

INSERT INTO `druzyny` (`id`, `nazwa`, `wynik`, `miejsce`, `id_wyscigu`) VALUES
(1, 'Drużyna I', '12:34,678', 1, 1),
(2, 'Drużyna II', '23:45,678', 2, 1),
(3, 'Drużyna III', '34:56,789', 3, 1),
(4, 'Drużyna I', '12:34,567', 1, 2),
(5, 'Drużyna II', '2:23,456', 2, 2),
(6, 'Drużyna III', '34:56,789', 3, 2);

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`) VALUES
(1, 'admin', 'zaq1@WSX');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `wyscigi`
--

CREATE TABLE IF NOT EXISTS `wyscigi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_zawodow` int(11) NOT NULL,
  `nazwa` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_id_zawodow` (`id_zawodow`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wyscigi`
--

INSERT INTO `wyscigi` (`id`, `id_zawodow`, `nazwa`) VALUES
(1, 1, 'Wyścig I'),
(2, 1, 'Wyścig II');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `zawody`
--

CREATE TABLE IF NOT EXISTS `zawody` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nazwa` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `zawody`
--

INSERT INTO `zawody` (`id`, `nazwa`) VALUES
(1, 'Mistrzostwa Polski');

--
-- Constraints for dumped tables
--

--
-- Constraints for table `druzyny`
--
ALTER TABLE `druzyny`
  ADD CONSTRAINT `fk_druzyny_wyscigi` FOREIGN KEY (`id_wyscigu`) REFERENCES `wyscigi` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `wyscigi`
--
ALTER TABLE `wyscigi`
  ADD CONSTRAINT `fk_wyscigi_zawody` FOREIGN KEY (`id_zawodow`) REFERENCES `zawody` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
