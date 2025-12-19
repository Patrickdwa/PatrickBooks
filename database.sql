-- ================================================================
-- DATABASE SCHEMA FOR HYBRID LIBRARY SYSTEM
-- ================================================================
-- Description: Structure and Dummy Data for MariaDB/MySQL
-- Author: [Nama Anda]
-- Project: Cloud Computing Final Project
-- ================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+07:00";

-- ----------------------------------------------------------------
-- 1. DROP EXISTING TABLES (Reset)
-- ----------------------------------------------------------------
DROP TABLE IF EXISTS `loans`;
DROP TABLE IF EXISTS `books`;
DROP TABLE IF EXISTS `members`;

-- ----------------------------------------------------------------
-- 2. TABLE STRUCTURE: BOOKS
-- ----------------------------------------------------------------
CREATE TABLE `books` (
  `book_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `author` varchar(255) NOT NULL,
  `publisher` varchar(255) NOT NULL,
  `year_published` int(4) NOT NULL,
  `genre` varchar(100) NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`book_id`),
  KEY `idx_title` (`title`),
  KEY `idx_author` (`author`),
  KEY `idx_genre` (`genre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------------------------------------------
-- 3. TABLE STRUCTURE: MEMBERS
-- ----------------------------------------------------------------
CREATE TABLE `members` (
  `member_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL UNIQUE,
  `phone` varchar(20) NOT NULL,
  `address` text NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`member_id`),
  KEY `idx_email` (`email`),
  KEY `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------------------------------------------
-- 4. TABLE STRUCTURE: LOANS
-- ----------------------------------------------------------------
CREATE TABLE `loans` (
  `loan_id` int(11) NOT NULL AUTO_INCREMENT,
  `book_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `loan_date` date NOT NULL,
  `return_date` date NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`loan_id`),
  KEY `idx_status` (`return_date`),
  CONSTRAINT `fk_loans_book` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_loans_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`member_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------------------------------------------
-- 5. DUMMY DATA SEEDING
-- ----------------------------------------------------------------

-- Books
INSERT INTO `books` (`title`, `author`, `publisher`, `year_published`, `genre`) VALUES
('Laskar Pelangi', 'Andrea Hirata', 'Bentang Pustaka', 2005, 'Fiction'),
('Clean Code', 'Robert C. Martin', 'Prentice Hall', 2008, 'Technology'),
('Atomic Habits', 'James Clear', 'Penguin', 2018, 'Self-Help'),
('Harry Potter and the Sorcerers Stone', 'J.K. Rowling', 'Bloomsbury', 1997, 'Fantasy'),
('Filosofi Teras', 'Henry Manampiring', 'Kompas', 2018, 'Philosophy'),
('The Psychology of Money', 'Morgan Housel', 'Harriman House', 2020, 'Finance'),
('Bumi Manusia', 'Pramoedya Ananta Toer', 'Hasta Mitra', 1980, 'Historical Fiction'),
('Sherlock Holmes: A Study in Scarlet', 'Arthur Conan Doyle', 'Ward Lock & Co', 1887, 'Mystery'),
('Rich Dad Poor Dad', 'Robert Kiyosaki', 'Warner Books', 1997, 'Finance'),
('Laut Bercerita', 'Leila S. Chudori', 'KPG', 2017, 'Historical Fiction'),
('Introduction to Algorithms', 'Thomas H. Cormen', 'MIT Press', 2009, 'Technology'),
('Dune', 'Frank Herbert', 'Chilton Books', 1965, 'Sci-Fi');

-- Members
INSERT INTO `members` (`name`, `email`, `phone`, `address`) VALUES
('Budi Santoso', 'budi@example.com', '08123456789', 'Jl. Merdeka No. 1, Jakarta'),
('Siti Aminah', 'siti@example.com', '08129876543', 'Jl. Sudirman No. 45, Bandung'),
('Rizky Ramadhan', 'rizky@example.com', '08567891234', 'Jl. Diponegoro No. 10, Surabaya'),
('Dewi Lestari', 'dewi@example.com', '08134567890', 'Jl. Malioboro No. 99, Yogyakarta'),
('Andi Wijaya', 'andi@example.com', '08781234567', 'Jl. Gajah Mada No. 12, Medan');

-- Loans (History)
INSERT INTO `loans` (`book_id`, `member_id`, `loan_date`, `return_date`) VALUES
(2, 1, CURDATE() - INTERVAL 10 DAY, CURDATE() - INTERVAL 3 DAY), -- Overdue
(5, 2, CURDATE(), CURDATE() + INTERVAL 7 DAY),                   -- Active
(1, 3, CURDATE() - INTERVAL 2 DAY, CURDATE() + INTERVAL 5 DAY),  -- Active
(12, 4, CURDATE() - INTERVAL 20 DAY, CURDATE() - INTERVAL 13 DAY); -- Overdue

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;