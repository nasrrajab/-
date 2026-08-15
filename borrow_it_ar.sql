-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 15, 2026 at 01:02 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `borrow_it_ar`
--

-- --------------------------------------------------------

--
-- Table structure for table `borrowing`
--

CREATE TABLE `borrowing` (
  `Borrowing_ID` int(11) NOT NULL,
  `Item_ID` int(11) NOT NULL,
  `User_ID` int(11) NOT NULL,
  `StartDate` date NOT NULL,
  `EndDate` date NOT NULL,
  `BorrowingStatus` varchar(20) NOT NULL,
  `CreateDate` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `borrowing`
--

INSERT INTO `borrowing` (`Borrowing_ID`, `Item_ID`, `User_ID`, `StartDate`, `EndDate`, `BorrowingStatus`, `CreateDate`) VALUES
(3, 2, 2, '2026-08-08', '2026-09-05', 'Rejected', '2026-08-08'),
(4, 2, 2, '2026-08-08', '2026-08-09', 'Approve', '2026-08-08');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `Category_ID` int(11) NOT NULL,
  `category_Name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`Category_ID`, `category_Name`) VALUES
(1, 'كتب'),
(2, 'معدات بناء'),
(4, 'ادوات منزليه');

-- --------------------------------------------------------

--
-- Table structure for table `items`
--

CREATE TABLE `items` (
  `Item_ID` int(11) NOT NULL,
  `User_ID` int(11) NOT NULL,
  `Category_ID` int(11) NOT NULL,
  `Title` varchar(200) NOT NULL,
  `Description` text NOT NULL,
  `ItemCondition` varchar(20) NOT NULL,
  `MinBorrowDays` int(11) NOT NULL,
  `City` varchar(20) NOT NULL,
  `Status` varchar(20) NOT NULL,
  `AddedBy` int(11) NOT NULL,
  `AddedDate` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `items`
--

INSERT INTO `items` (`Item_ID`, `User_ID`, `Category_ID`, `Title`, `Description`, `ItemCondition`, `MinBorrowDays`, `City`, `Status`, `AddedBy`, `AddedDate`) VALUES
(2, 2, 1, 'كتاب', 'كتاب', 'New', 1, 'نابلس', 'Borrowed', 1, '2026-08-07');

-- --------------------------------------------------------

--
-- Table structure for table `item_availability`
--

CREATE TABLE `item_availability` (
  `ID` int(11) NOT NULL,
  `Item_ID` int(11) NOT NULL,
  `UnAvailableDate` date NOT NULL,
  `UnAvailableReason` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `item_images`
--

CREATE TABLE `item_images` (
  `Image_ID` int(11) NOT NULL,
  `Item_ID` int(11) NOT NULL,
  `ImageUrl` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `item_images`
--

INSERT INTO `item_images` (`Image_ID`, `Item_ID`, `ImageUrl`) VALUES
(5, 2, '\\استعارة\\uploads\\item_6a75e8de596ae9.65788494.webp');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `User_ID` int(11) NOT NULL,
  `FullName` varchar(200) NOT NULL,
  `Email` varchar(50) NOT NULL,
  `PhoneNumber` varchar(20) DEFAULT NULL,
  `Password` varchar(255) NOT NULL,
  `UserRole` varchar(10) NOT NULL,
  `CreateDate` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`User_ID`, `FullName`, `Email`, `PhoneNumber`, `Password`, `UserRole`, `CreateDate`) VALUES
(1, 'nasr', 'nasr@gmail.com', '566171655', '123456', 'Admin', '2026-07-18'),
(2, 'ali', 'ali@gmail.com', '566171666', '1234567', 'EndUser', '2026-07-18'),
(4, 'test', 'test@gmail.com', '0566991314', '123456', 'Admin', '2026-08-07'),
(6, 'ahmad', 'ahmad@gmail.com', '0599887676', '123456', 'EndUser', '2026-08-07');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `borrowing`
--
ALTER TABLE `borrowing`
  ADD PRIMARY KEY (`Borrowing_ID`),
  ADD KEY `Item_ID` (`Item_ID`),
  ADD KEY `User_ID` (`User_ID`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`Category_ID`);

--
-- Indexes for table `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`Item_ID`),
  ADD KEY `User_ID` (`User_ID`),
  ADD KEY `Category_ID` (`Category_ID`),
  ADD KEY `AddedBy` (`AddedBy`);

--
-- Indexes for table `item_availability`
--
ALTER TABLE `item_availability`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `Item_ID` (`Item_ID`);

--
-- Indexes for table `item_images`
--
ALTER TABLE `item_images`
  ADD PRIMARY KEY (`Image_ID`),
  ADD KEY `Item_ID` (`Item_ID`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`User_ID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `borrowing`
--
ALTER TABLE `borrowing`
  MODIFY `Borrowing_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `Category_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `items`
--
ALTER TABLE `items`
  MODIFY `Item_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `item_availability`
--
ALTER TABLE `item_availability`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `item_images`
--
ALTER TABLE `item_images`
  MODIFY `Image_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `User_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `borrowing`
--
ALTER TABLE `borrowing`
  ADD CONSTRAINT `borrowing_ibfk_1` FOREIGN KEY (`Item_ID`) REFERENCES `items` (`Item_ID`),
  ADD CONSTRAINT `borrowing_ibfk_2` FOREIGN KEY (`User_ID`) REFERENCES `users` (`User_ID`);

--
-- Constraints for table `items`
--
ALTER TABLE `items`
  ADD CONSTRAINT `items_ibfk_1` FOREIGN KEY (`User_ID`) REFERENCES `users` (`User_ID`),
  ADD CONSTRAINT `items_ibfk_2` FOREIGN KEY (`Category_ID`) REFERENCES `categories` (`Category_ID`),
  ADD CONSTRAINT `items_ibfk_3` FOREIGN KEY (`AddedBy`) REFERENCES `users` (`User_ID`);

--
-- Constraints for table `item_availability`
--
ALTER TABLE `item_availability`
  ADD CONSTRAINT `item_availability_ibfk_1` FOREIGN KEY (`Item_ID`) REFERENCES `items` (`Item_ID`);

--
-- Constraints for table `item_images`
--
ALTER TABLE `item_images`
  ADD CONSTRAINT `item_images_ibfk_1` FOREIGN KEY (`Item_ID`) REFERENCES `items` (`Item_ID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
