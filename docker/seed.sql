-- Demo database for the Docker image (docker/start.sh loads it when no DB_HOST is set).
-- Tables are empty except: the store's books and categories, and one admin account.
-- Generated from the local database. It holds no customers, orders or private data.

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `books`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `books` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `author` varchar(255) NOT NULL,
  `category_id` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `category` varchar(100) DEFAULT 'General',
  `stock` int(11) DEFAULT 0,
  `isbn` varchar(20) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `seller_id` int(11) DEFAULT NULL,
  `publisher` varchar(255) DEFAULT NULL,
  `pages` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cart`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cart` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `book_id` (`book_id`),
  CONSTRAINT `fk_cart_book` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cart_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `icon` varchar(50) DEFAULT 'bi-book',
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `contact_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `topic` varchar(30) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_at` (`created_at`),
  KEY `fk_contact_user` (`user_id`),
  CONSTRAINT `fk_contact_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `book_id` (`book_id`),
  CONSTRAINT `fk_order_items_book` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `total` decimal(10,2) NOT NULL,
  `status` enum('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
  `shipping_address` text NOT NULL,
  `payment_method` varchar(50) DEFAULT 'cash_on_delivery',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `token_hash` (`token_hash`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_password_resets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `book_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` int(1) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `comment` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_book` (`user_id`,`book_id`),
  KEY `book_id` (`book_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_reviews_book` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reviews_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `role` enum('customer','admin') DEFAULT 'customer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `favorite_genres` text DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `wishlist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wishlist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_book` (`user_id`,`book_id`),
  KEY `book_id` (`book_id`),
  CONSTRAINT `fk_wishlist_book` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_wishlist_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

/*!40000 ALTER TABLE `books` DISABLE KEYS */;
INSERT INTO `books` VALUES (1,'The Midnight Library','Matt Haig',1,1599.00,'Fiction',25,'9780525559474','A dazzling novel about all the choices that go into a life well lived.','assets/images/books/1.jpg','2026-01-25 05:55:56','2026-11-07 14:29:25',NULL,'Viking',288),(2,'Atomic Habits','James Clear',2,1899.00,'Self-Help',30,'9780735211292','An Easy & Proven Way to Build Good Habits & Break Bad Ones.','assets/images/books/2.jpg','2026-01-25 05:55:56','2026-11-07 14:29:25',NULL,'Avery',320),(3,'The Silent Patient','Alex Michaelides',3,1699.00,'Mystery',20,'9781250301697','A psychological thriller about a woman who shoots her husband and then never speaks again.','assets/images/books/3.jpg','2026-01-25 05:55:56','2026-11-07 14:29:25',NULL,'Celadon Books',336),(4,'The Name of the Wind','Patrick Rothfuss',4,1999.00,'Fantasy',15,'9780756404741','The first book in the Kingkiller Chronicle series.','assets/images/books/4.jpg','2026-01-25 05:55:56','2026-11-07 14:29:25',NULL,'DAW Books',722),(5,'Educated','Tara Westover',5,1799.00,'Biography',18,'9780399590504','A memoir about a young woman who leaves her survivalist family and goes to university.','assets/images/books/5.jpg','2026-01-25 05:55:56','2026-11-07 14:29:25',NULL,'Random House',NULL),(6,'Sapiens','Yuval Noah Harari',6,2299.00,'History',22,'9780062316110','A Brief History of Humankind.','assets/images/books/6.jpg','2026-01-25 05:55:56','2026-11-07 14:29:25',NULL,'Harper Perennial',464),(7,'Thinking, Fast and Slow','Daniel Kahneman',2,2199.00,'Science',12,'9780374533557','Explores the two systems that drive the way we think.','assets/images/books/7.jpg','2026-01-25 05:55:56','2026-11-07 14:29:25',NULL,'Farrar, Straus and Giroux',499),(8,'The Alchemist','Paulo Coelho',1,1299.00,'Fiction',35,'9780061122415','A magical tale about following your dreams.','assets/images/books/8.jpg','2026-01-25 05:55:56','2026-11-07 14:29:25',NULL,'HarperCollins',208),(9,'1984','George Orwell',1,1499.00,'Fiction',28,'9780451524935','A dystopian social science fiction novel.','assets/images/books/9.jpg','2026-01-25 05:55:56','2026-11-07 14:29:25',NULL,'Signet Classic',NULL),(10,'Deep Work','Cal Newport',7,1699.00,'Business',20,'9781455586691','Rules for Focused Success in a Distracted World.','assets/images/books/10.jpg','2026-01-25 05:55:56','2026-11-07 14:29:25',NULL,'Grand Central Publishing',295),(11,'The Psychology of Money','Morgan Housel',7,1599.00,'Business',25,'9780857197689','Timeless lessons on wealth, greed, and happiness.','assets/images/books/11.jpg','2026-01-25 05:55:56','2026-11-07 14:29:25',NULL,'Harriman House',250),(12,'Steve Jobs','Walter Isaacson',5,2499.00,'Biography',10,'9781451648546','The exclusive biography of Steve Jobs.','assets/images/books/12.jpg','2026-01-25 05:55:56','2026-11-07 14:29:25',NULL,'Simon & Schuster',656),(19,'Read This If You Want to Take Great Photographs','Henry Carroll',0,2450.00,'Arts & Photography',0,'9781780674292','A practical guide to the ideas behind great photographs, with examples from the work of master photographers.','assets/images/books/19.jpg','2026-01-27 12:29:51','2026-11-07 14:29:25',NULL,'Laurence King Publishing',128),(20,'The Great Railway Bazaar','Paul Theroux',0,1890.00,'Travel',0,'9780618658947','A classic of travel writing: four months riding trains across Asia, from London to Japan and back on the Trans-Siberian.','assets/images/books/20.jpg','2026-01-27 12:29:51','2026-11-07 14:29:25',NULL,'Mariner Books',342),(21,'The Hidden Life of Trees','Peter Wohlleben',0,1550.00,'Nature',0,'9781771642484','A forester explains how trees feel, communicate and support each other, and what that means for the forests around us.','assets/images/books/21.jpg','2026-01-27 12:29:51','2026-11-07 14:29:25',NULL,'Greystone Books',272),(22,'The Story of Art','E. H. Gombrich',0,3200.00,'Arts & Photography',0,'9780714832470','The most widely read introduction to art history, from cave paintings to the modern age, written for the general reader.','assets/images/books/22.jpg','2026-01-27 15:39:09','2026-11-07 14:29:25',NULL,'Phaidon',688);
/*!40000 ALTER TABLE `books` ENABLE KEYS */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'Fiction','bi-book','Fictional novels and stories','2026-01-25 05:55:56'),(2,'Self-Help','bi-heart','Self-improvement and motivational books','2026-01-25 05:55:56'),(3,'Mystery','bi-search','Mystery and thriller novels','2026-01-25 05:55:56'),(4,'Fantasy','bi-stars','Fantasy and magical adventures','2026-01-25 05:55:56'),(5,'Biography','bi-person','Life stories and memoirs','2026-01-25 05:55:56'),(6,'Science','bi-globe','Scientific and educational books','2026-01-25 05:55:56'),(7,'Business','bi-briefcase','Business and entrepreneurship','2026-01-25 05:55:56'),(8,'Romance','bi-heart-fill','Romantic novels','2026-01-25 05:55:56'),(9,'History','bi-clock-history','Historical books and events','2026-01-25 05:55:56'),(10,'Technology','bi-cpu','Technology and programming books','2026-01-25 05:55:56'),(11,'Business','bi-book',NULL,'2026-01-27 12:21:23'),(12,'Fiction','bi-book',NULL,'2026-01-27 12:21:23'),(13,'History','bi-book',NULL,'2026-01-27 12:21:23'),(14,'Science','bi-book',NULL,'2026-01-27 12:21:23'),(15,'Self-Help','bi-book',NULL,'2026-01-27 12:21:23'),(16,'Tech','bi-book',NULL,'2026-01-27 12:21:23'),(17,'Biography','bi-book',NULL,'2026-01-27 12:21:23'),(18,'Mystery','bi-book',NULL,'2026-01-27 12:21:23'),(19,'Romance','bi-book',NULL,'2026-01-27 12:21:23'),(20,'Fantasy','bi-book',NULL,'2026-01-27 12:21:23'),(21,'Arts & Photography','bi-book',NULL,'2026-01-27 12:21:23'),(22,'Travel','bi-book',NULL,'2026-01-27 12:21:23'),(23,'Nature','bi-book',NULL,'2026-01-27 12:21:23');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

SET FOREIGN_KEY_CHECKS=0;
INSERT INTO users (name, email, password, role) VALUES ('Bishesh Waiba', 'bishesh@admin.com', '$2y$10$iHxMmBoWi4CabI7UviJe0OgvqHY4C38YqD.flD53VazbWC3xh14za', 'admin');
SET FOREIGN_KEY_CHECKS=1;
