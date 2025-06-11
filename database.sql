-- SQL Dump for News Week Project
-- Generation Time: Jun 08, 2025 at 10:32 PM
--
-- This script will create the necessary database and tables
-- for the News Week project. It also includes sample data.
--

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `news_week_db`
--

USE `nnxogxrd_news_week_db`;

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--
DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--
-- The password for the default 'admin' user is 'adminpassword'
INSERT INTO `admins` (`id`, `username`, `password`, `email`, `created_at`) VALUES
(1, 'admin', '$2y$10$iGg1jM7E1bC9v8aWwXoZ.e.f6jI4oVqXoY2eU2sZp9s9gH0k7rW1m', 'admin@newsweek.example.com', '2025-06-08 14:32:00');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(120) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--
INSERT INTO `categories` (`id`, `name`, `slug`, `description`) VALUES
(1, 'Technology', 'technology', 'Latest news and updates in the tech world.'),
(2, 'Business', 'business', 'Insights into market trends, finance, and corporate news.'),
(3, 'Sports', 'sports', 'Scores, highlights, and analysis from the world of sports.'),
(4, 'Politics', 'politics', 'National and international political developments.'),
(5, 'Entertainment', 'entertainment', 'News from the world of movies, music, and pop culture.');

-- --------------------------------------------------------

--
-- Table structure for table `tags`
--
DROP TABLE IF EXISTS `tags`;
CREATE TABLE `tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `slug` varchar(60) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tags`
--
INSERT INTO `tags` (`id`, `name`, `slug`) VALUES
(1, 'Labor', 'labor'),
(2, 'Government', 'government'),
(3, 'Elon Musk', 'elon-musk'),
(4, 'SpaceX', 'spacex'),
(5, 'Finance', 'finance'),
(6, 'DOLE', 'dole');

-- --------------------------------------------------------

--
-- Table structure for table `articles`
--
DROP TABLE IF EXISTS `articles`;
CREATE TABLE `articles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(270) NOT NULL,
  `content` longtext NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `status` enum('draft','published','archived') NOT NULL DEFAULT 'draft',
  `publish_date` datetime DEFAULT NULL,
  `views` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `category_id` (`category_id`),
  KEY `admin_id` (`admin_id`),
  KEY `status` (`status`),
  KEY `publish_date` (`publish_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `articles`
--
INSERT INTO `articles` (`id`, `title`, `slug`, `content`, `category_id`, `admin_id`, `image_url`, `status`, `publish_date`) VALUES
(1, 'Labor group lauds PBBM for retaining DOLE chief Laguesma', 'labor-group-lauds-pbbm-for-retaining-dole-chief-laguesma', '<p>A prominent labor group has expressed its support for the decision of President Ferdinand &ldquo;Bongbong&rdquo; Marcos Jr. to keep Bienvenido Laguesma as the head of the Department of Labor and Employment (DOLE).</p><p>In a statement, the group highlighted Laguesma''s experience and dedication to worker''s rights as key reasons for their endorsement. &ldquo;We believe Secretary Laguesma is the right person to navigate the complex labor issues our country faces today,&rdquo; the statement read. This is a positive development for the labor sector.</p>', 4, 1, '../uploads/images/articles/sample-labor-article.jpg', 'published', '2025-06-08 10:00:00'),
(2, 'Elon Musk Reclaims Title of World''s Richest Person', 'elon-musk-reclaims-title-of-worlds-richest-person', '<h1>Elon Musk is on top again!</h1><p>According to the latest financial reports, <strong>Elon Musk</strong> has once again become the world''s wealthiest individual. His net worth surged following a significant rally in Tesla stock and new valuation milestones for SpaceX.</p><ul><li>Tesla Stock Performance</li><li>SpaceX Funding Rounds</li><li>Future Ventures</li></ul><p>Analysts point to continued innovation and market confidence as the primary drivers of this financial rebound. This development places him ahead of other top billionaires in a highly competitive race.</p>', 2, 1, '../uploads/images/articles/sample-musk-article.jpg', 'published', '2025-06-07 15:30:00');


-- --------------------------------------------------------

--
-- Table structure for table `article_tags`
--
DROP TABLE IF EXISTS `article_tags`;
CREATE TABLE `article_tags` (
  `article_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL,
  PRIMARY KEY (`article_id`,`tag_id`),
  KEY `tag_id` (`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `article_tags`
--
INSERT INTO `article_tags` (`article_id`, `tag_id`) VALUES
(1, 1),
(1, 2),
(1, 6),
(2, 3),
(2, 4),
(2, 5);


--
-- Constraints for dumped tables
--

--
-- Constraints for table `articles`
--
ALTER TABLE `articles`
  ADD CONSTRAINT `articles_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `articles_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `article_tags`
--
ALTER TABLE `article_tags`
  ADD CONSTRAINT `article_tags_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `article_tags_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;


COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
