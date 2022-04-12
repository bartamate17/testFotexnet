# testFotexnet
Fotexnet Port.hu PHP feladat

TELEPÍTÉSI LEÍRÁS
(Ajánlott program: XAMPP - PHPMYADMIN: importálható mysql file)

* 1 - Hozzuk létre a portprogramme nevű adatbázist

* 2 - Hozzuk létre a programsdownload táblát
   CREATE TABLE `programsdownload` (
  `id` int(11) NOT NULL,
  `dateLoad` date NOT NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

* 3 - Valamint a programs táblát
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

* 4 - Nyissa meg az index.php file-t

