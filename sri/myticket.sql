-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 09, 2025 at 10:18 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `myticket`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `CategoryID` int(11) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `Description` text DEFAULT NULL,
  `CreatedAt` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`CategoryID`, `Name`, `Description`, `CreatedAt`) VALUES
(1, 'Music', 'Concerts, festivals, and live music performances', '2025-12-09 10:03:17'),
(2, 'Sports', 'Athletic events, competitions, and matches', '2025-12-09 10:03:17'),
(3, 'Technology', 'Tech conferences, expos, and product launches', '2025-12-09 10:03:17'),
(4, 'Art', 'Exhibitions, gallery openings, and art shows', '2025-12-09 10:03:17'),
(5, 'Comedy', 'Stand-up shows and comedy performances', '2025-12-09 10:03:17'),
(6, 'Theater', 'Plays, musicals, and theatrical performances', '2025-12-09 10:03:17'),
(7, 'Food & Drink', 'Food festivals, tastings, and dining events', '2025-12-09 10:03:17'),
(8, 'Business', 'Conferences, seminars, and networking events', '2025-12-09 10:03:17');

-- --------------------------------------------------------

--
-- Table structure for table `eventpromotions`
--

CREATE TABLE `eventpromotions` (
  `EventID` int(11) NOT NULL,
  `PromoID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `EventID` int(11) NOT NULL,
  `OrganizerID` int(11) NOT NULL,
  `LocationID` int(11) NOT NULL,
  `CategoryID` int(11) DEFAULT NULL,
  `Title` varchar(255) NOT NULL,
  `Description` text DEFAULT NULL,
  `EventDateTime` datetime NOT NULL,
  `DurationMinutes` int(11) DEFAULT NULL,
  `ApprovalStatus` enum('Pending','Approved','Rejected') DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`EventID`, `OrganizerID`, `LocationID`, `CategoryID`, `Title`, `Description`, `EventDateTime`, `DurationMinutes`, `ApprovalStatus`) VALUES
(9, 4, 3, 1, 'bollymusic', 'music on bolly', '2025-12-16 15:03:00', 60, 'Approved'),
(10, 4, 1, 4, 'mandala arts', 'learn how to do mandala', '2025-12-12 15:05:00', 120, 'Approved');

-- --------------------------------------------------------

--
-- Table structure for table `locations`
--

CREATE TABLE `locations` (
  `LocationID` int(11) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `Address` varchar(255) NOT NULL,
  `City` varchar(100) NOT NULL,
  `State` varchar(100) NOT NULL,
  `PostalCode` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `locations`
--

INSERT INTO `locations` (`LocationID`, `Name`, `Address`, `City`, `State`, `PostalCode`) VALUES
(1, 'City Stadium', '123 Main St', 'New York', 'NY', '10001'),
(2, 'Jazz Club', '456 Beale St', 'Memphis', 'TN', '38103'),
(3, 'Convention Center', '789 Tech Blvd', 'San Francisco', 'CA', '94105');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `OrderID` int(11) NOT NULL,
  `CustomerID` int(11) NOT NULL,
  `OrderDate` datetime DEFAULT current_timestamp(),
  `TotalAmount` decimal(10,2) NOT NULL,
  `Status` enum('Pending','Paid','Cancelled') DEFAULT 'Pending',
  `PaymentMethod` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`OrderID`, `CustomerID`, `OrderDate`, `TotalAmount`, `Status`, `PaymentMethod`) VALUES
(4, 4, '2025-12-09 15:04:44', 500.00, 'Paid', NULL),
(5, 4, '2025-12-09 15:06:31', 30.00, 'Paid', NULL),
(6, 5, '2025-12-09 15:06:55', 50.00, 'Paid', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `promotions`
--

CREATE TABLE `promotions` (
  `PromoID` int(11) NOT NULL,
  `Code` varchar(50) NOT NULL,
  `Description` text DEFAULT NULL,
  `DiscountType` enum('Percentage','FixedAmount') NOT NULL,
  `DiscountValue` decimal(10,2) NOT NULL,
  `StartDate` datetime NOT NULL,
  `EndDate` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `promotions`
--

INSERT INTO `promotions` (`PromoID`, `Code`, `Description`, `DiscountType`, `DiscountValue`, `StartDate`, `EndDate`) VALUES
(1, 'EARLYBIRD', NULL, 'Percentage', 10.00, '2023-01-01 00:00:00', '2023-12-31 00:00:00'),
(2, 'SUMMERFUN', NULL, 'FixedAmount', 5.00, '2023-06-01 00:00:00', '2023-08-31 00:00:00'),
(3, 'VIP20', NULL, 'Percentage', 20.00, '2023-01-01 00:00:00', '2023-12-31 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `tickets`
--

CREATE TABLE `tickets` (
  `TicketID` int(11) NOT NULL,
  `OrderID` int(11) NOT NULL,
  `TicketTypeID` int(11) NOT NULL,
  `UniqueBarcode` varchar(255) NOT NULL,
  `PurchasePrice` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tickets`
--

INSERT INTO `tickets` (`TicketID`, `OrderID`, `TicketTypeID`, `UniqueBarcode`, `PurchasePrice`) VALUES
(4, 4, 4, 'ca85f59c724756b911f26b94c43fc243', 50.00),
(5, 4, 4, '57882ce0c440de2ae1c818afee91b9ef', 50.00),
(6, 4, 4, 'ce6cfb3e71dcd5dde42e83ebde54a8c1', 50.00),
(7, 4, 4, '51601c074dcd957f0a04bf7060bf00e3', 50.00),
(8, 4, 4, '35a54fa729c45f57d9ae7cc1cfc1c89a', 50.00),
(9, 4, 4, 'ed11b5e1ccf38e7ce51deac02f968372', 50.00),
(10, 4, 4, '7b68d8b04706f83bd7a970bd8ba25c7c', 50.00),
(11, 4, 4, 'c721d8f80e9fa628140c3a7dd92cb73b', 50.00),
(12, 4, 4, '7f6f2166e86b746257a90dbd0a69d4b0', 50.00),
(13, 4, 4, '792b7b930797f48cc6b19cddb0992654', 50.00),
(14, 5, 5, '43b61d397b7eb1e265f9813e2c0d914c', 10.00),
(15, 5, 6, '0e56e10eed8470b58f6de3a051d0bcc1', 20.00),
(16, 6, 5, '8e0110245aeb1e727b6741939e569769', 10.00),
(17, 6, 5, 'd3ec16b8ef2a081467e88be765542241', 10.00),
(18, 6, 5, '7650d738c1410f16d1609fef1c03f76b', 10.00),
(19, 6, 5, 'a8b58c31d0be5ecb2118f3f138d53327', 10.00),
(20, 6, 5, 'fbeb34e0e17c1205b57170f3d68c6a85', 10.00);

-- --------------------------------------------------------

--
-- Table structure for table `tickettypes`
--

CREATE TABLE `tickettypes` (
  `TicketTypeID` int(11) NOT NULL,
  `EventID` int(11) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `Price` decimal(10,2) NOT NULL,
  `TotalCapacity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tickettypes`
--

INSERT INTO `tickettypes` (`TicketTypeID`, `EventID`, `Name`, `Price`, `TotalCapacity`) VALUES
(4, 9, 'General admission', 50.00, 100),
(5, 10, 'General admission', 10.00, 100),
(6, 10, 'vip', 20.00, 100);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `UserID` int(11) NOT NULL,
  `Username` varchar(100) NOT NULL,
  `Email` varchar(150) NOT NULL,
  `PasswordHash` varchar(255) NOT NULL,
  `Role` enum('Admin','Organizer','Customer') NOT NULL,
  `IsVerified` tinyint(1) DEFAULT 0,
  `RegistrationDate` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`UserID`, `Username`, `Email`, `PasswordHash`, `Role`, `IsVerified`, `RegistrationDate`) VALUES
(4, 'sri', 'sri@gmail.com', '$2y$10$X5TZt22RuJvKseTvw0B5heroXd.T3WnHHANGL9o74tU3tdyZVeMSS', 'Admin', 1, '2025-12-09 14:02:40'),
(5, 'bob', 'bob@gmail.com', '$2y$10$yiORPc2rEQTM9cTfOGD0ueczfSPzgJ2usEaU/sJgTCXsUpWpdhGZa', 'Customer', 1, '2025-12-09 14:29:15'),
(6, 'ally ', 'ally@gmail.com', '$2y$10$6CRQ4Hkx7EZ2doI2o9FFtONWOv5jXZYqptR9LmSaAk/jROVVTDqVG', 'Organizer', 1, '2025-12-09 14:29:37');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`CategoryID`),
  ADD UNIQUE KEY `Name` (`Name`);

--
-- Indexes for table `eventpromotions`
--
ALTER TABLE `eventpromotions`
  ADD PRIMARY KEY (`EventID`,`PromoID`),
  ADD KEY `PromoID` (`PromoID`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`EventID`),
  ADD KEY `OrganizerID` (`OrganizerID`),
  ADD KEY `LocationID` (`LocationID`),
  ADD KEY `fk_events_category` (`CategoryID`);

--
-- Indexes for table `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`LocationID`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`OrderID`),
  ADD KEY `CustomerID` (`CustomerID`);

--
-- Indexes for table `promotions`
--
ALTER TABLE `promotions`
  ADD PRIMARY KEY (`PromoID`),
  ADD UNIQUE KEY `Code` (`Code`);

--
-- Indexes for table `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`TicketID`),
  ADD UNIQUE KEY `UniqueBarcode` (`UniqueBarcode`),
  ADD KEY `OrderID` (`OrderID`),
  ADD KEY `TicketTypeID` (`TicketTypeID`);

--
-- Indexes for table `tickettypes`
--
ALTER TABLE `tickettypes`
  ADD PRIMARY KEY (`TicketTypeID`),
  ADD KEY `EventID` (`EventID`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`UserID`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `CategoryID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `EventID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `locations`
--
ALTER TABLE `locations`
  MODIFY `LocationID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `OrderID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `promotions`
--
ALTER TABLE `promotions`
  MODIFY `PromoID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tickets`
--
ALTER TABLE `tickets`
  MODIFY `TicketID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `tickettypes`
--
ALTER TABLE `tickettypes`
  MODIFY `TicketTypeID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `UserID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `eventpromotions`
--
ALTER TABLE `eventpromotions`
  ADD CONSTRAINT `eventpromotions_ibfk_1` FOREIGN KEY (`EventID`) REFERENCES `events` (`EventID`) ON DELETE CASCADE,
  ADD CONSTRAINT `eventpromotions_ibfk_2` FOREIGN KEY (`PromoID`) REFERENCES `promotions` (`PromoID`) ON DELETE CASCADE;

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`OrganizerID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE,
  ADD CONSTRAINT `events_ibfk_2` FOREIGN KEY (`LocationID`) REFERENCES `locations` (`LocationID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_events_category` FOREIGN KEY (`CategoryID`) REFERENCES `categories` (`CategoryID`) ON DELETE SET NULL;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`CustomerID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE;

--
-- Constraints for table `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `tickets_ibfk_1` FOREIGN KEY (`OrderID`) REFERENCES `orders` (`OrderID`) ON DELETE CASCADE,
  ADD CONSTRAINT `tickets_ibfk_2` FOREIGN KEY (`TicketTypeID`) REFERENCES `tickettypes` (`TicketTypeID`) ON DELETE CASCADE;

--
-- Constraints for table `tickettypes`
--
ALTER TABLE `tickettypes`
  ADD CONSTRAINT `tickettypes_ibfk_1` FOREIGN KEY (`EventID`) REFERENCES `events` (`EventID`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
