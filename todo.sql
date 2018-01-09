-- phpMyAdmin SQL Dump
-- version 4.5.4.1deb2ubuntu2
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Dec 20, 2017 at 01:28 AM
-- Server version: 5.7.20-0ubuntu0.16.04.1
-- PHP Version: 7.0.22-0ubuntu0.16.04.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `todo`
--

-- --------------------------------------------------------

--
-- Table structure for table `todo_list`
--

CREATE TABLE `todo_list` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text,
  `member_id` varchar(20) CHARACTER SET latin2 COLLATE latin2_bin NOT NULL,
  `status` enum('new','completed','deleted') NOT NULL DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deadline` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `completed_by` varchar(20) CHARACTER SET latin2 COLLATE latin2_bin DEFAULT NULL,
  `deleted_by` varchar(20) CHARACTER SET latin2 COLLATE latin2_bin DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `todo_list`
--

INSERT INTO `todo_list` (`id`, `title`, `content`, `member_id`, `status`, `created_at`, `deadline`, `completed_at`, `deleted_at`, `completed_by`, `deleted_by`) VALUES
(1, 'Test', 'hey test here', 'admin', 'deleted', '2017-12-19 15:28:33', '2017-12-19 20:57:00', '2017-12-19 21:05:25', '2017-12-19 21:05:28', 'royb2300', 'royb2300'),
(2, 'test', 'dkfwrkjh', 'admin', 'deleted', '2017-12-19 19:33:50', '2017-12-20 01:03:50', '2017-12-20 01:21:54', '2017-12-20 01:22:41', 'royb2300', 'royb2300'),
(3, 'test', 'err', 'admin', 'new', '2017-12-19 19:57:26', '2017-12-20 01:27:00', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `todo_list_notes`
--

CREATE TABLE `todo_list_notes` (
  `id` int(11) NOT NULL,
  `todo_list_id` int(11) NOT NULL,
  `note` text NOT NULL,
  `author_id` varchar(20) CHARACTER SET latin2 COLLATE latin2_bin NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `todo_list_notes`
--

INSERT INTO `todo_list_notes` (`id`, `todo_list_id`, `note`, `author_id`, `created_at`) VALUES
(1, 1, 'testing', 'royb2300', '2017-12-19 15:32:33');

-- --------------------------------------------------------

--
-- Table structure for table `user_master`
--

CREATE TABLE `user_master` (
  `username` varchar(20) CHARACTER SET latin2 COLLATE latin2_bin NOT NULL,
  `Password` blob NOT NULL,
  `Email_ID` varchar(50) NOT NULL,
  `FirstName` varchar(100) NOT NULL,
  `LastName` varchar(100) NOT NULL,
  `Address` varchar(100) DEFAULT NULL,
  `City` varchar(50) DEFAULT NULL,
  `State` varchar(20) DEFAULT NULL,
  `Zipcode` varchar(11) DEFAULT NULL,
  `Phone` varchar(20) DEFAULT NULL,
  `file_name` varchar(100) DEFAULT NULL,
  `email_status` char(1) DEFAULT 'N',
  `office_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `user_master`
--

INSERT INTO `user_master` (`username`, `Password`, `Email_ID`, `FirstName`, `LastName`, `Address`, `City`, `State`, `Zipcode`, `Phone`, `file_name`, `email_status`, `office_id`) VALUES
('Ashley', 0x416e67656c31303037, '', 'Ashley', 'Rosario', '', '', '', '', '', '', 'N', NULL),
('Erika', 0x303231313935, '', 'Erika', 'Rivera', '', '', '', '', '', '', 'N', NULL),
('Erikar', 0x303231313935, '', 'Erika', 'Rivera', '', '', '', '', '', '', 'N', NULL),
('Leonny', 0x426321313039383034, '', 'Leonny', 'Rosario', '', '', '', '', '', '', 'N', NULL),
('Pstrauchn', 0x4361736831353231, '', 'Trish ', '', '', '', '', '', '', '', 'N', NULL),
('Rafael', 0x706f6a6174613132, '', 'Rafael', 'Lelcaj', '', '', '', '', '', '', 'N', NULL),
('Sandra', 0x6170706c653132, 'sandra@plumbersland.info', 'Sandra', 'Sanabia', '', '', '', '', '', '', 'N', NULL),
('admin', 0x313233343536, 'isaackl@plumbersland.info', 'Issac', 'Klein', '', '', '', '', '7189897575', NULL, 'N', 34),
('admindev', 0x6465762e646576, 'buiss.senn@googlemail.com', 'Test Company', '', '', 'New York', 'New York', '10018', '2019207186', NULL, 'N', NULL),
('anyxia', 0x30373032, 'lopez@plumbersland.info', 'anyxia', 'lopez', '', '', '', '', '', '', 'N', NULL),
('cecellia', 0x6368696e78783930, '', 'Cecellia', 'Burl', '', '', '', '', '', '', 'N', NULL),
('charlot', 0x6a757374696e313233, '', 'Charlot', '', '', '', '', '', '', '', 'N', NULL),
('connie', 0x5273637439313431, '', 'Connie', '', '', '', '', '', '', '', 'N', NULL),
('elibe', 0x313233343536, 'elibenyamin@optonline.net', 'Eli', 'Benyamin', '', '', '', '', '9144031004', NULL, 'N', 34),
('imani', 0x7269636861726473, '', 'imani', 'richards', '', '', '', '', '', '', 'N', NULL),
('latlayneh', 0x6c65616864656d6931, 'tifah@plimbersland.info', 'Latifah', 'Harrison', '', '', '', '', '', '', 'N', NULL),
('latoya', 0x616d617961, '', 'Latoya', 'Dandrade', '', '', '', '', '', '', 'N', NULL),
('royb2300', 0x42656e6a616d696e, '', 'Roy', '', '', '', '', '', '', '', 'N', NULL),
('sasha', 0x303631363136, '', 'sasha', '', '', '', 'New York', '', '', '', 'N', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `todo_list`
--
ALTER TABLE `todo_list`
  ADD PRIMARY KEY (`id`),
  ADD KEY `member_id` (`member_id`),
  ADD KEY `completed_by` (`completed_by`),
  ADD KEY `deleted_by` (`deleted_by`);

--
-- Indexes for table `todo_list_notes`
--
ALTER TABLE `todo_list_notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `todo_list_id` (`todo_list_id`),
  ADD KEY `author_id` (`author_id`);

--
-- Indexes for table `user_master`
--
ALTER TABLE `user_master`
  ADD PRIMARY KEY (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `todo_list`
--
ALTER TABLE `todo_list`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT for table `todo_list_notes`
--
ALTER TABLE `todo_list_notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
--
-- Constraints for dumped tables
--

--
-- Constraints for table `todo_list`
--
ALTER TABLE `todo_list`
  ADD CONSTRAINT `for_get_completed_by_member_id` FOREIGN KEY (`completed_by`) REFERENCES `user_master` (`username`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `for_get_deeted_by_member_id` FOREIGN KEY (`deleted_by`) REFERENCES `user_master` (`username`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `for_get_member_id` FOREIGN KEY (`member_id`) REFERENCES `user_master` (`username`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `todo_list_notes`
--
ALTER TABLE `todo_list_notes`
  ADD CONSTRAINT `for_get_author_id` FOREIGN KEY (`author_id`) REFERENCES `user_master` (`username`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `get_todi_list_id` FOREIGN KEY (`todo_list_id`) REFERENCES `todo_list` (`id`);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
