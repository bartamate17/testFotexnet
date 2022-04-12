-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Gép: 127.0.0.1
-- Létrehozás ideje: 2022. Ápr 12. 20:36
-- Kiszolgáló verziója: 10.4.21-MariaDB
-- PHP verzió: 8.0.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Adatbázis: `portprogramme`
--

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `programs`
--

CREATE TABLE `programs` (
  `id` int(11) NOT NULL,
  `channelName` varchar(255) NOT NULL,
  `channelProgramStart` datetime NOT NULL,
  `channelProgramTitle` varchar(255) NOT NULL,
  `channelProgramShortDescription` varchar(255) NOT NULL,
  `channelProgramShortAgeLimit` int(11) NOT NULL,
  `dateUser` datetime NOT NULL,
  `channelUrl` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `programsdownload`
--

CREATE TABLE `programsdownload` (
  `id` int(11) NOT NULL,
  `dateLoad` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Indexek a kiírt táblákhoz
--

--
-- A tábla indexei `programs`
--
ALTER TABLE `programs`
  ADD PRIMARY KEY (`id`);

--
-- A tábla indexei `programsdownload`
--
ALTER TABLE `programsdownload`
  ADD PRIMARY KEY (`id`);

--
-- A kiírt táblák AUTO_INCREMENT értéke
--

--
-- AUTO_INCREMENT a táblához `programs`
--
ALTER TABLE `programs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16742;

--
-- AUTO_INCREMENT a táblához `programsdownload`
--
ALTER TABLE `programsdownload`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=114;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
