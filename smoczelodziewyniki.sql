-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Maj 19, 2026 at 11:07 PM
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

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `druzyny`
--

CREATE TABLE `druzyny` (
  `id` int(11) NOT NULL,
  `nazwa` text NOT NULL,
  `wynik` varchar(16) DEFAULT NULL,
  `miejsce` int(11) NOT NULL,
  `tor` int(11) NOT NULL,
  `id_wyscigu` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `druzyny`
--

INSERT INTO `druzyny` (`id`, `nazwa`, `wynik`, `miejsce`, `tor`, `id_wyscigu`) VALUES
(1, 'Drużyna I', '12:34,677', 1, 0, 1),
(2, 'Drużyna II', '23:45,678', 2, 0, 1),
(3, 'Drużyna III', '34:56,789', 3, 0, 1),
(4, 'Drużyna I', '12:34,567', 1, 0, 2),
(5, 'Drużyna II', '2:23,456', 2, 0, 2),
(6, 'Drużyna III', '34:56,789', 3, 0, 2);

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`) VALUES
(1, 'admin', 'zaq1@WSX');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `ustawienia`
--

CREATE TABLE `ustawienia` (
  `id` int(11) NOT NULL,
  `klucz` varchar(255) NOT NULL,
  `wartosc` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ustawienia`
--

INSERT INTO `ustawienia` (`id`, `klucz`, `wartosc`) VALUES
(1, 'aktywne_zawody', '1');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `wyscigi`
--

CREATE TABLE `wyscigi` (
  `id` int(11) NOT NULL,
  `id_zawodow` int(11) NOT NULL,
  `nazwa` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wyscigi`
--

INSERT INTO `wyscigi` (`id`, `id_zawodow`, `nazwa`) VALUES
(1, 1, 'Wyścig I'),
(2, 1, 'Wyścig II'),
(3, 1, 'Wyścig III'),
(4, 3, 'djhHDgShdgassh'),
(7, 3, 'Wyścig I'),
(8, 1, 'fvfdzsc'),
(9, 1, 'zbfdfd');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `zawody`
--

CREATE TABLE `zawody` (
  `id` int(11) NOT NULL,
  `nazwa` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `zawody`
--

INSERT INTO `zawody` (`id`, `nazwa`) VALUES
(1, 'Mistrzostwa Polski 2026'),
(3, 'Mistrzostwa Polski 2025'),
(4, 'W wyciąganiu chuja z wody XD 2026');

--
-- Indeksy dla zrzutów tabel
--

--
-- Indeksy dla tabeli `druzyny`
--
ALTER TABLE `druzyny`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_id_wyscigu` (`id_wyscigu`);

--
-- Indeksy dla tabeli `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indeksy dla tabeli `ustawienia`
--
ALTER TABLE `ustawienia`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `klucz` (`klucz`),
  ADD KEY `idx_klucz` (`klucz`);

--
-- Indeksy dla tabeli `wyscigi`
--
ALTER TABLE `wyscigi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_id_zawodow` (`id_zawodow`);

--
-- Indeksy dla tabeli `zawody`
--
ALTER TABLE `zawody`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `druzyny`
--
ALTER TABLE `druzyny`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `ustawienia`
--
ALTER TABLE `ustawienia`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `wyscigi`
--
ALTER TABLE `wyscigi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `zawody`
--
ALTER TABLE `zawody`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

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
