-- phpMyAdmin SQL Dump
-- version 4.5.1
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: 13-Mar-2016 às 23:59
-- Versão do servidor: 10.1.9-MariaDB
-- PHP Version: 5.6.15

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `pokemon`
--

-- --------------------------------------------------------

--
-- Estrutura da tabela `ataques`
--

CREATE TABLE `ataques` (
  `Id` int(11) NOT NULL,
  `Name` varchar(255) NOT NULL,
  `Type` int(11) NOT NULL,
  `Power` int(11) NOT NULL,
  `Acc` int(11) NOT NULL,
  `PP` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Extraindo dados da tabela `ataques`
--

INSERT INTO `ataques` (`Id`, `Name`, `Type`, `Power`, `Acc`, `PP`) VALUES
(1, 'Absorb', 15, 20, 100, 25),
(2, 'Barrage', 1, 15, 85, 20),
(3, 'Bind', 1, 15, 85, 20),
(4, 'Bite', 13, 60, 100, 25),
(5, 'Blizzard', 10, 110, 70, 5),
(6, 'Body Slam', 1, 85, 100, 15),
(7, 'Bone Club', 7, 65, 85, 20),
(8, 'Bonemerang', 7, 50, 90, 10),
(9, 'Bubble', 4, 40, 100, 30),
(10, 'Bubble Beam', 4, 65, 100, 20),
(11, 'Clamp', 4, 35, 85, 10),
(12, 'Comet Punch', 1, 18, 85, 15),
(13, 'Confusion', 8, 50, 100, 25),
(14, 'Constrict', 1, 10, 100, 35),
(15, 'Crabhammer', 4, 100, 90, 10),
(16, 'Cut', 1, 50, 95, 30),
(17, 'Dig', 7, 80, 100, 10),
(18, 'Dizzy Punch', 1, 70, 100, 10),
(19, 'Double Kick', 3, 30, 100, 30),
(20, 'Double-Edge', 1, 120, 100, 15),
(21, 'Dragon Rage', 12, 40, 100, 10),
(22, 'Dream Eater', 8, 100, 100, 15),
(23, 'Drill Peck', 16, 80, 100, 20),
(24, 'Earthquake', 7, 100, 100, 10),
(25, 'Egg Bomb', 1, 100, 75, 10),
(26, 'Ember', 2, 40, 100, 25),
(27, 'Explosion', 1, 250, 100, 5),
(28, 'Fire Blast', 2, 110, 85, 5),
(29, 'Fire Punch', 2, 75, 100, 15),
(30, 'Fire Spin', 2, 35, 85, 15),
(31, 'Fire Spin', 2, 35, 85, 15),
(32, 'Flamethrower', 2, 90, 100, 15),
(33, 'Fly', 16, 90, 95, 15),
(34, 'Fury Attack', 1, 15, 85, 20),
(35, 'Fury Swipes', 1, 18, 80, 15),
(36, 'Gust', 1, 40, 100, 35),
(37, 'Headbutt', 1, 70, 100, 15),
(38, 'High Jump Kick', 3, 130, 90, 10),
(39, 'Horn Attack', 1, 65, 100, 25),
(40, 'Hydro Pump', 4, 110, 80, 5),
(41, 'Hyper Beam', 1, 150, 90, 5),
(42, 'Hyper Fang', 1, 80, 90, 15),
(43, 'Ice Beam', 10, 90, 100, 10),
(44, 'Ice Punch', 10, 75, 100, 15),
(45, 'Jump Kick', 3, 100, 95, 10),
(46, 'Karate Chop', 3, 50, 100, 25),
(47, 'Leech Life', 11, 20, 100, 15),
(48, 'Lick', 13, 30, 100, 30),
(49, 'Mega Drain', 15, 40, 100, 15),
(50, 'Mega Kick', 1, 120, 75, 5),
(51, 'Mega Punch', 1, 80, 85, 20),
(52, 'Pay Day', 1, 40, 100, 20),
(53, 'Peck', 16, 35, 100, 35),
(54, 'Petal Dance', 15, 120, 100, 10),
(55, 'Pin Missile', 11, 25, 95, 20),
(56, 'Poison Sting', 5, 15, 100, 35),
(57, 'Pound', 1, 40, 100, 35),
(58, 'Psybeam', 8, 65, 100, 20),
(59, 'Quick Attack', 1, 40, 100, 30),
(60, 'Rage', 1, 20, 100, 20),
(61, 'Razor Leaf', 15, 55, 95, 25),
(62, 'Razor Wind', 1, 80, 100, 10),
(63, 'Rock Slide', 9, 50, 90, 10),
(64, 'Rock Throw', 9, 50, 90, 15),
(65, 'Rolling Kick', 3, 60, 85, 15),
(66, 'Scratch', 1, 40, 100, 35),
(67, 'Self-Destruct', 1, 200, 100, 5),
(68, 'Skull Bash', 1, 130, 100, 10),
(69, 'Sky Attack', 16, 140, 90, 5),
(70, 'Slam', 1, 80, 75, 20),
(71, 'Slash', 1, 70, 100, 20),
(72, 'Sludge', 5, 65, 100, 20),
(73, 'Smog', 5, 30, 70, 20),
(74, 'Solar Beam', 15, 120, 100, 10),
(75, 'Spike Cannon', 1, 20, 100, 15),
(76, 'Stomp', 1, 65, 100, 20),
(77, 'Strenght', 1, 80, 100, 15),
(78, 'Struggle', 1, 50, 100, 100),
(79, 'Submission', 3, 80, 80, 25),
(80, 'Surf', 4, 90, 100, 15),
(81, 'Tackle', 1, 50, 100, 35),
(82, 'Take Down', 1, 90, 85, 20),
(83, 'Thrash', 1, 120, 100, 10),
(84, 'Thunder', 6, 110, 70, 10),
(85, 'Thunder Punch', 6, 75, 100, 15),
(86, 'Thunder Shock', 6, 40, 100, 30),
(87, 'Thunderbolt', 6, 90, 100, 15),
(88, 'Tri Attack', 1, 80, 100, 10),
(89, 'Twineedle', 11, 25, 100, 20),
(90, 'Vice Grip', 1, 55, 100, 30),
(91, 'Vine Whip', 15, 45, 100, 25),
(92, 'Water Gun', 4, 40, 100, 25),
(93, 'Waterfall', 4, 80, 100, 15),
(94, 'Wing Attack', 16, 60, 100, 35),
(95, 'Wrap', 1, 15, 90, 20),
(96, 'Growl', 1, 0, 100, 25);

-- --------------------------------------------------------

--
-- Estrutura da tabela `player`
--

CREATE TABLE `player` (
  `Id` int(11) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `Nome` varchar(50) NOT NULL,
  `Player` int(11) NOT NULL,
  `LvlMax` int(11) NOT NULL,
  `Dinheiro` int(11) NOT NULL,
  `Gym` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estrutura da tabela `pokemon`
--

CREATE TABLE `pokemon` (
  `Id` int(11) NOT NULL,
  `IdPlayer` int(11) NOT NULL,
  `Pokemon` int(11) NOT NULL,
  `Lvl` int(11) NOT NULL,
  `At1` int(11) NOT NULL,
  `At2` int(11) NOT NULL,
  `At3` int(11) NOT NULL,
  `At4` int(11) NOT NULL,
  `PP1` int(11) NOT NULL,
  `PP2` int(11) NOT NULL,
  `PP3` int(11) NOT NULL,
  `PP4` int(11) NOT NULL,
  `HP` int(11) NOT NULL,
  `XP` int(11) NOT NULL,
  `Slot` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estrutura da tabela `stats`
--

CREATE TABLE `stats` (
  `Number` int(11) NOT NULL,
  `Name` varchar(250) NOT NULL,
  `Type1` int(11) NOT NULL,
  `Type2` int(11) NOT NULL,
  `HP` int(11) NOT NULL,
  `Attack` int(11) NOT NULL,
  `Defense` int(11) NOT NULL,
  `SpAtk` int(11) NOT NULL,
  `SpDef` int(11) NOT NULL,
  `Speed` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Extraindo dados da tabela `stats`
--

INSERT INTO `stats` (`Number`, `Name`, `Type1`, `Type2`, `HP`, `Attack`, `Defense`, `SpAtk`, `SpDef`, `Speed`) VALUES
(1, 'Bulbasaur', 5, 15, 45, 49, 49, 65, 65, 45),
(2, 'Ivysaur', 5, 15, 60, 62, 63, 80, 80, 60),
(3, 'Venusaur', 5, 15, 80, 82, 83, 100, 100, 80),
(4, 'Charmander', 1, 2, 39, 52, 43, 60, 50, 65),
(5, 'Charmeleon', 1, 2, 58, 64, 58, 80, 65, 80),
(6, 'Charizard', 16, 2, 78, 84, 78, 109, 85, 100),
(7, 'Squirtle', 1, 4, 44, 48, 65, 50, 64, 43),
(8, 'Wartortle', 1, 4, 59, 63, 80, 65, 80, 58),
(9, 'Blastoise', 1, 4, 79, 83, 100, 85, 105, 78),
(10, 'Caterpie', 1, 11, 45, 30, 35, 20, 20, 45),
(11, 'Metapod', 1, 11, 50, 20, 55, 25, 25, 30),
(12, 'Butterfree', 16, 11, 60, 45, 50, 80, 80, 70),
(13, 'Weedle', 5, 11, 40, 35, 30, 20, 20, 50),
(14, 'Kakuna', 5, 11, 45, 25, 50, 25, 25, 35),
(15, 'Beedrill', 5, 11, 65, 80, 40, 45, 80, 75),
(16, 'Pidgey', 1, 16, 40, 45, 40, 35, 35, 56),
(17, 'Pidgeotto', 1, 16, 63, 60, 55, 50, 50, 71),
(18, 'Pidgeot', 1, 16, 83, 80, 75, 70, 70, 91),
(19, 'Rattata', 1, 1, 30, 56, 35, 25, 35, 72),
(20, 'Raticate', 1, 1, 55, 81, 60, 50, 70, 97),
(21, 'Spearow', 1, 16, 40, 60, 30, 31, 31, 70),
(22, 'Fearow', 1, 16, 65, 90, 65, 61, 61, 100),
(23, 'Ekans', 1, 5, 35, 60, 44, 40, 54, 55),
(24, 'Arbok', 1, 5, 60, 85, 69, 65, 79, 80),
(25, 'Pikachu', 1, 6, 35, 55, 30, 50, 40, 90),
(26, 'Raichu', 1, 6, 60, 90, 55, 90, 80, 100),
(27, 'Sandshrew', 1, 7, 50, 75, 85, 20, 30, 40),
(28, 'Sandslash', 1, 7, 75, 100, 110, 45, 55, 65),
(29, 'Nidoran-F', 1, 5, 55, 47, 52, 40, 40, 41),
(30, 'Nidorina', 1, 5, 70, 62, 67, 55, 55, 56),
(31, 'Nidoqueen', 5, 7, 90, 82, 87, 75, 85, 76),
(32, 'Nidoran-M', 1, 5, 46, 57, 40, 40, 40, 50),
(33, 'Nidorino', 1, 5, 61, 72, 57, 55, 55, 65),
(34, 'Nidoking', 5, 7, 81, 92, 77, 85, 75, 85),
(35, 'Clefairy', 1, 1, 70, 45, 48, 60, 65, 35),
(36, 'Clefable', 1, 1, 95, 70, 73, 85, 90, 60),
(37, 'Vulpix', 1, 2, 38, 41, 40, 50, 65, 65),
(38, 'Ninetales', 1, 2, 73, 76, 75, 81, 100, 100),
(39, 'Jigglypuff', 1, 1, 115, 45, 20, 45, 25, 20),
(40, 'Wigglytuff', 1, 1, 140, 70, 45, 75, 50, 45),
(41, 'Zubat', 5, 16, 40, 45, 35, 30, 40, 55),
(42, 'Golbat', 5, 16, 75, 80, 70, 65, 75, 90),
(43, 'Oddish', 15, 5, 45, 50, 55, 75, 65, 30),
(44, 'Gloom', 15, 5, 60, 65, 70, 85, 75, 40),
(46, 'Paras', 11, 5, 35, 70, 55, 45, 55, 25),
(47, 'Parasect', 11, 5, 60, 95, 80, 60, 80, 30),
(48, 'Venonat', 11, 5, 60, 55, 50, 40, 55, 45),
(49, 'Venomoth', 11, 5, 70, 65, 60, 90, 75, 90),
(50, 'Diglett', 1, 7, 10, 55, 25, 35, 45, 95),
(51, 'Dugtrio', 1, 7, 35, 80, 50, 50, 70, 120),
(52, 'Meowth', 1, 1, 40, 45, 35, 40, 40, 90),
(53, 'Persian', 1, 1, 65, 70, 60, 65, 65, 115),
(54, 'Psyduck', 1, 4, 50, 52, 48, 65, 50, 55),
(55, 'Golduck', 1, 4, 80, 82, 78, 95, 80, 85),
(56, 'Mankey', 1, 3, 40, 80, 35, 35, 45, 70),
(57, 'Primeape', 1, 3, 65, 105, 60, 60, 70, 95),
(58, 'Growlithe', 1, 2, 55, 70, 45, 70, 50, 60),
(59, 'Arcanine', 1, 2, 90, 110, 80, 100, 80, 95),
(60, 'Poliwag', 1, 4, 40, 50, 40, 40, 40, 90),
(61, 'Poliwhirl', 1, 4, 65, 65, 65, 50, 50, 90),
(62, 'Poliwrath', 4, 3, 90, 95, 95, 70, 90, 70),
(63, 'Abra', 1, 8, 25, 20, 15, 105, 55, 90),
(64, 'Kadabra', 1, 8, 40, 35, 30, 120, 70, 105),
(65, 'Alakazam', 1, 8, 55, 50, 45, 135, 95, 120),
(66, 'Machop', 1, 3, 70, 80, 50, 35, 35, 35),
(67, 'Machoke', 1, 3, 90, 130, 80, 65, 85, 55),
(68, 'Machamp', 1, 3, 80, 100, 70, 50, 60, 45),
(69, 'Bellsprout', 15, 5, 90, 130, 80, 65, 85, 55),
(70, 'Weepinbell', 15, 5, 65, 90, 50, 85, 45, 55),
(71, 'Victreebel', 15, 5, 80, 105, 65, 100, 70, 70),
(72, 'Tentacool', 4, 5, 40, 40, 35, 50, 100, 70),
(73, 'Tentacruel', 4, 5, 80, 70, 65, 80, 120, 100),
(74, 'Geodude', 9, 7, 40, 80, 100, 30, 30, 20),
(75, 'Graveler', 9, 7, 55, 95, 115, 45, 45, 45),
(76, 'Golem', 9, 7, 80, 120, 130, 55, 65, 45),
(77, 'Ponyta', 1, 2, 50, 85, 55, 65, 65, 90),
(78, 'Rapidash', 1, 2, 65, 100, 70, 80, 80, 105),
(79, 'Slowpoke', 4, 8, 90, 65, 65, 40, 40, 15),
(80, 'Slowbro', 4, 8, 95, 75, 110, 100, 80, 30),
(81, 'Magnemite', 1, 6, 25, 35, 70, 95, 55, 45),
(82, 'Magneton', 1, 6, 50, 60, 95, 120, 70, 70),
(83, 'Farfetch''d', 1, 16, 52, 65, 55, 58, 62, 60),
(84, 'Doduo', 1, 16, 52, 65, 55, 58, 62, 60),
(85, 'Dodrio', 1, 16, 60, 110, 70, 60, 60, 100),
(86, 'Seel', 4, 10, 65, 45, 55, 45, 70, 45),
(87, 'Dewgong', 4, 10, 90, 70, 80, 70, 95, 70),
(88, 'Grimer', 1, 5, 80, 80, 50, 40, 50, 25),
(90, 'Muk', 1, 5, 105, 105, 75, 65, 100, 50),
(90, 'Shellder', 1, 4, 30, 65, 100, 45, 25, 40),
(91, 'Cloyster', 4, 10, 50, 95, 180, 85, 45, 70),
(92, 'Gastly', 13, 5, 30, 35, 30, 100, 35, 80),
(93, 'Haunter', 13, 5, 45, 50, 45, 115, 55, 95),
(94, 'Gengar', 13, 5, 60, 65, 60, 130, 75, 110),
(95, 'Onix', 9, 7, 35, 45, 160, 30, 45, 70),
(96, 'Drowzee', 1, 8, 60, 48, 45, 43, 90, 42),
(97, 'Hypno', 1, 8, 85, 73, 70, 74, 115, 67),
(98, 'Krabby', 1, 4, 30, 105, 90, 25, 25, 50),
(99, 'Kingler', 1, 4, 55, 130, 115, 50, 50, 75),
(100, 'Voltorb', 1, 6, 40, 30, 50, 55, 55, 100),
(101, 'Electrode', 1, 6, 60, 50, 70, 80, 80, 140),
(102, 'Exeggcute', 15, 8, 60, 40, 80, 60, 45, 40),
(103, 'Exeggutor', 15, 8, 60, 40, 80, 60, 45, 40),
(104, 'Cubone', 1, 7, 50, 50, 95, 40, 50, 35),
(105, 'Marowak', 1, 7, 60, 80, 110, 50, 80, 45),
(107, 'Hitmonlee', 1, 3, 50, 120, 53, 35, 110, 87),
(106, 'Hitmonchan', 1, 3, 50, 105, 79, 35, 110, 87),
(108, 'Lickitung', 1, 1, 90, 55, 75, 69, 75, 30),
(109, 'Koffing', 1, 5, 40, 65, 95, 60, 45, 35),
(110, 'Weezing', 1, 5, 65, 90, 120, 85, 70, 60),
(111, 'Rhyhorn', 9, 7, 80, 85, 95, 30, 30, 25),
(112, 'Rhydon', 9, 7, 195, 139, 129, 45, 45, 40),
(113, 'Chansey', 1, 1, 250, 5, 5, 35, 105, 50),
(114, 'Tangela', 1, 15, 65, 55, 115, 100, 40, 60),
(115, 'Kangashkan', 1, 1, 105, 95, 80, 40, 80, 90),
(116, 'Horsea', 1, 4, 30, 40, 70, 70, 25, 60),
(117, 'Seadra', 1, 4, 55, 65, 95, 95, 45, 85),
(118, 'Goldeen', 1, 4, 45, 67, 60, 35, 50, 63),
(119, 'Seaking', 1, 4, 80, 92, 65, 65, 80, 68),
(120, 'Staryu', 1, 4, 30, 45, 55, 70, 55, 85),
(121, 'Starmie', 4, 8, 60, 75, 85, 100, 85, 115),
(122, 'Mr. Mime', 1, 8, 40, 45, 65, 100, 120, 90),
(123, 'Scyther', 11, 16, 70, 110, 80, 55, 80, 105),
(125, 'Electrabuzz', 1, 6, 65, 83, 57, 95, 85, 105),
(126, 'Magmar', 1, 2, 65, 95, 57, 100, 85, 105),
(127, 'Pinsir', 1, 11, 65, 125, 100, 55, 70, 85),
(128, 'Tauros', 1, 1, 75, 100, 95, 40, 70, 110),
(129, 'Magikarp', 1, 4, 20, 10, 55, 15, 20, 80),
(130, 'Gyarados', 1, 4, 95, 125, 79, 60, 100, 81),
(131, 'Lapras', 4, 10, 130, 85, 80, 85, 95, 60),
(132, 'Ditto', 1, 1, 48, 48, 48, 48, 48, 48),
(133, 'Eevee', 1, 1, 55, 55, 50, 45, 65, 55),
(134, 'Vaporeon', 1, 4, 130, 65, 60, 110, 95, 65),
(135, 'Jolteon', 1, 6, 65, 65, 60, 110, 95, 130),
(136, 'Flareon', 1, 4, 65, 130, 60, 95, 110, 65),
(137, 'Porygon', 1, 1, 65, 60, 70, 85, 75, 40),
(138, 'Omanyte', 9, 4, 35, 40, 100, 90, 55, 35),
(139, 'Omastar', 9, 4, 70, 60, 125, 115, 70, 55),
(140, 'Kabuto', 9, 4, 30, 80, 90, 55, 45, 55),
(141, 'Kabutops', 9, 4, 70, 60, 125, 115, 70, 55),
(142, 'Aerodactyl', 9, 16, 80, 105, 65, 60, 75, 130),
(143, 'Snorlax', 1, 1, 160, 110, 65, 65, 110, 30),
(144, 'Articuno', 10, 16, 90, 85, 100, 95, 125, 85),
(145, 'Zapdos', 6, 16, 90, 90, 85, 125, 90, 100),
(146, 'Moltres', 2, 16, 90, 100, 90, 125, 95, 90),
(147, 'Dratini', 1, 12, 41, 64, 45, 50, 50, 50),
(148, 'Dragonair', 1, 12, 61, 84, 65, 70, 70, 70),
(149, 'Dragonite', 12, 16, 91, 134, 95, 100, 100, 80),
(150, 'Mewtwo', 1, 8, 106, 110, 90, 154, 90, 130),
(151, 'Mew', 1, 8, 100, 100, 100, 100, 100, 100),
(45, 'Vileplume', 15, 5, 75, 80, 85, 110, 90, 50),
(124, 'Jynx', 10, 8, 65, 50, 35, 115, 95, 95),
(89, 'Muk', 1, 5, 105, 105, 75, 65, 100, 50);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ataques`
--
ALTER TABLE `ataques`
  ADD PRIMARY KEY (`Id`);

--
-- Indexes for table `player`
--
ALTER TABLE `player`
  ADD PRIMARY KEY (`Id`);

--
-- Indexes for table `pokemon`
--
ALTER TABLE `pokemon`
  ADD PRIMARY KEY (`Id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `player`
--
ALTER TABLE `player`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;
--
-- AUTO_INCREMENT for table `pokemon`
--
ALTER TABLE `pokemon`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
