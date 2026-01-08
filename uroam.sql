/*
SQLyog Community v13.3.0 (64 bit)
MySQL - 10.4.32-MariaDB : Database - uroam
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
CREATE DATABASE /*!32312 IF NOT EXISTS*/`uroam` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;

USE `uroam`;

/*Table structure for table `admin` */

DROP TABLE IF EXISTS `admin`;

CREATE TABLE `admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(150) DEFAULT NULL,
  `first_name` varchar(150) DEFAULT NULL,
  `last_name` varchar(150) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `job` varchar(150) DEFAULT NULL,
  `phone` varchar(150) DEFAULT NULL,
  `role` enum('owner','admin') DEFAULT 'admin',
  `profile_picture` varchar(150) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `admin` */

insert  into `admin`(`id`,`full_name`,`first_name`,`last_name`,`email`,`password`,`job`,`phone`,`role`,`profile_picture`) values 
(1,'Owner','Owner','Rillll','owner@uroam.com','$2y$10$naFgchuI92Tkf6P0pzDwGOIq2czOpfsBrZjQGFVq4q2k6JqSsi59S','Owner','Owner','owner',NULL),
(5,'Komang Suyanta','Komang','Suyanta','komangsuyanta@uroam.com','$2y$10$oAK6bxH/kdMLOg6Sv2Cw7O/tcdbdv9hmCUu6tIlW2SixauaBcjpPG','Operations','08123456789','admin','/PROGNET/database/uploads/admin/admin_694bb7c8304922.82063685.jpg'),
(6,'Dharma Yoga','Dharma','Yoga','iprdy@uroam.com','$2y$10$rMqQ.Q4T9AIzekQy7tvSWO3dv83J/w9fxLyLOLgsNG7UfRLs0FE4a','Operations','08123456789','admin','/PROGNET/database/uploads/admin/admin_694bb7f435f6e8.33561968.jpg');

/*Table structure for table `booking_sequence` */

DROP TABLE IF EXISTS `booking_sequence`;

CREATE TABLE `booking_sequence` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `booking_sequence` */

insert  into `booking_sequence`(`id`) values 
(5),
(6),
(7),
(8),
(9),
(10),
(11),
(12),
(13),
(14);

/*Table structure for table `bookings` */

DROP TABLE IF EXISTS `bookings`;

CREATE TABLE `bookings` (
  `booking_code` varchar(10) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `product_id` int(11) NOT NULL,
  `option_name` enum('Private','Group') NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `total_adult` int(11) NOT NULL DEFAULT 0,
  `total_child` int(11) NOT NULL DEFAULT 0,
  `gross_rate` decimal(12,2) NOT NULL,
  `discount_rate` decimal(12,2) NOT NULL DEFAULT 0.00,
  `net_rate` decimal(12,2) NOT NULL,
  `duration` int(11) NOT NULL,
  `meeting_point` varchar(255) NOT NULL,
  `purchase_date` datetime NOT NULL,
  `activity_date` datetime NOT NULL,
  `reviewed` tinyint(1) DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`booking_code`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_activity_date` (`activity_date`),
  KEY `idx_purchase_date` (`purchase_date`),
  KEY `idx_customer_id` (`customer_id`),
  CONSTRAINT `fk_bookings_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_bookings_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `bookings` */

insert  into `bookings`(`booking_code`,`customer_id`,`product_id`,`option_name`,`customer_name`,`phone`,`email`,`total_adult`,`total_child`,`gross_rate`,`discount_rate`,`net_rate`,`duration`,`meeting_point`,`purchase_date`,`activity_date`,`reviewed`,`created_at`) values 
('BK00001',1,55,'Private','John Anderson','081234567890','john.anderson@mail.com',2,1,3000000.00,500000.00,2500000.00,8,'Sanur Harbour','2025-11-28 00:00:00','2025-12-02 00:00:00',0,'2025-11-28 09:15:00'),
('BK00002',1,54,'Group','Emily Watson','082198765432','emily.watson@mail.com',1,0,1500000.00,0.00,1500000.00,6,'Hotel Pickup - Ubud','2025-11-29 00:00:00','2025-12-03 00:00:00',0,'2025-11-29 14:40:00'),
('BK00003',1,55,'Private','Michael Brown','085612345678','michael.brown@mail.com',3,2,4200000.00,700000.00,3500000.00,9,'Sanur Harbour','2025-11-30 00:00:00','2025-12-04 00:00:00',0,'2025-11-30 18:05:00'),
('BK00004',1,54,'Private','Sophia Martinez','087812345999','sophia.martinez@mail.com',2,0,2800000.00,300000.00,2500000.00,7,'Hotel Pickup - Seminyak','2025-12-01 00:00:00','2025-12-05 00:00:00',0,'2025-12-01 10:20:00'),
('BK00005',11,54,'Private','Dharma Yoga','6285960185215','iputuradityadharmayoga@gmail.com',8,45,39500000.00,0.00,39500000.00,10,'T','2026-01-04 00:00:00','2026-01-30 00:00:00',0,'2026-01-05 03:59:10'),
('BK00006',11,54,'Private','Dharma Yoga','6285960185215','iputuradityadharmayoga@gmail.com',1,1,1700000.00,1699000.00,1000.00,10,'T','2026-01-04 00:00:00','2026-01-06 00:00:00',1,'2026-01-05 04:00:23'),
('BK00007',11,54,'Private','Komang Suyanta','081234567890','john.smith@gmail.com',2,1,3000000.00,2999000.00,1000.00,10,'Test','2025-12-26 00:00:00','2025-12-29 00:00:00',1,'2026-01-05 04:04:42'),
('BK00008',11,54,'Private','Dharma Yoga','6285960185215','iputuradityadharmayoga@gmail.com',34,0,34000000.00,33999000.00,1000.00,10,'Test','2026-01-04 00:00:00','2026-01-10 00:00:00',0,'2026-01-05 04:20:23'),
('BK00009',11,55,'Private','Dharma Yoga','6285960185215','iputuradityadharmayoga@gmail.com',20,0,200000000.00,0.00,200000000.00,6,'Hotel Ubud','2026-01-05 00:00:00','2026-01-09 00:00:00',0,'2026-01-05 21:53:51'),
('BK00010',11,55,'Private','Dharma Yoga','6285960185215','iputuradityadharmayoga@gmail.com',18,1,19000000.00,0.00,19000000.00,6,'Test','2026-01-04 00:00:00','2026-01-15 00:00:00',0,'2026-01-06 15:22:03'),
('BK00011',11,54,'Private','Dharma Yoga','6285960185215','iputuradityadharmayoga@gmail.com',62,0,62000000.00,0.00,62000000.00,10,'Hotel Ubud','2026-01-06 00:00:00','2027-11-11 11:11:00',0,'2026-01-06 15:34:18'),
('BK00012',11,54,'Private','Dharma Yoga','6285960185215','iputuradityadharmayoga@gmail.com',1,0,1000000.00,999000.00,1000.00,10,'Hotel Ubud','2026-01-06 00:00:00','2026-01-20 10:00:00',0,'2026-01-06 20:45:07'),
('BK00013',11,55,'Private','Dharma Yoga','6285960185215','iputuradityadharmayoga@gmail.com',200,0,2000000000.00,1009492432.00,990507568.00,6,'Hotel Ubud','2026-01-06 16:56:01','2026-01-22 10:00:00',0,'2026-01-06 23:57:57'),
('BK00014',11,55,'Private','Dharma Yoga','6285960185215','iputuradityadharmayoga@gmail.com',1,0,10000000.00,9999000.00,1000.00,6,'Hotel Ubud','2026-01-06 20:26:03','2026-01-09 03:27:00',0,'2026-01-07 16:27:01');

/*Table structure for table `categories` */

DROP TABLE IF EXISTS `categories`;

CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `slug` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `categories` */

insert  into `categories`(`id`,`name`,`slug`) values 
(1,'Nature','nature'),
(2,'Culture','culture'),
(3,'Attraction','attraction'),
(4,'Spiritual','spiritual'),
(5,'Adventure','adventure'),
(6,'Animal','animal'),
(7,'Botanical','botanical'),
(8,'Education','education'),
(9,'Tasting','tasting'),
(10,'Journalist','journalist');

/*Table structure for table `company_profile` */

DROP TABLE IF EXISTS `company_profile`;

CREATE TABLE `company_profile` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `total_employees` int(11) NOT NULL,
  `customer_service_phone` varchar(30) NOT NULL,
  `customer_service_email` varchar(100) NOT NULL,
  `managing_director` varchar(100) NOT NULL,
  `about_uroam` text DEFAULT NULL,
  `policy_uroam` text DEFAULT NULL,
  `terms_uroam` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `company_profile` */

insert  into `company_profile`(`id`,`total_employees`,`customer_service_phone`,`customer_service_email`,`managing_director`,`about_uroam`,`policy_uroam`,`terms_uroam`,`updated_at`) values 
(1,10,'+621234567890','cs@uroam.com','Komang Suyanta','Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec sodales metus vulputate justo luctus scelerisque. Sed rhoncus et arcu vel aliquet. Vivamus eget tempus lacus. In accumsan ac metus eu efficitur. Aenean imperdiet aliquam nibh sit amet feugiat. Integer sed metus efficitur, pulvinar nunc rutrum, luctus urna. Sed ornare aliquam felis in pharetra. Fusce varius risus ac erat convallis volutpat. Integer gravida sit amet purus id consequat.\n\nNunc tempus arcu ut felis posuere, sed scelerisque ipsum facilisis. Interdum et malesuada fames ac ante ipsum primis in faucibus. Nam leo dui, sollicitudin in sapien sed, blandit mattis dolor. Sed nec tincidunt mauris. Integer non ligula vel nunc gravida varius id condimentum orci. Curabitur porta urna eu pulvinar scelerisque. Etiam nec vehicula felis. Mauris vestibulum magna ante, quis posuere felis euismod eu. Nulla tempus leo urna, sed ultricies mauris hendrerit eu. Suspendisse lacinia varius tristique. Cras in massa imperdiet, semper libero at, hendrerit nunc. Pellentesque pellentesque nisi sem, vel tincidunt quam mollis in.\n\nNullam congue scelerisque faucibus. Etiam quis velit eget tellus mollis viverra. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia curae; Nunc laoreet vulputate imperdiet. Morbi lacinia dictum mi, id dictum ligula luctus nec. Mauris nec luctus risus. Phasellus eget ex vitae nulla blandit elementum. Nunc lacus enim, elementum tristique lobortis ac, ultricies a nisl. Cras fringilla scelerisque metus, eu dictum mi vulputate sed. Mauris interdum dictum mi, sit amet semper nisl faucibus id. Sed consequat, turpis a varius interdum, enim nulla vulputate augue, non pharetra urna erat at dui. Cras in purus quam.\n\nSuspendisse in faucibus ipsum. Sed mollis nisl ac massa sodales, id accumsan dui ultrices. Aliquam tempus massa metus. Fusce venenatis eget risus sit amet fringilla. Quisque in dolor blandit, sollicitudin nibh et, porta nulla. Morbi eleifend purus a ante hendrerit condimentum. Mauris et molestie mi. Sed rutrum est velit, sed facilisis neque facilisis sed. Morbi quis metus feugiat libero viverra bibendum. Fusce finibus, tellus nec tempor aliquet, eros arcu sollicitudin felis, sed tincidunt justo ipsum nec purus.\n\nAliquam tincidunt quam nulla, id pharetra est finibus ac. Etiam finibus pharetra volutpat. Ut pretium porta augue tempus accumsan. Nullam quis est ut ex bibendum varius vitae non urna. Vestibulum bibendum lacus sit amet mauris imperdiet facilisis. Vivamus blandit est non arcu luctus aliquet. Nam eget libero vehicula, condimentum dui vitae, sodales lorem. Orci varius natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus.','Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec sodales metus vulputate justo luctus scelerisque. Sed rhoncus et arcu vel aliquet. Vivamus eget tempus lacus. In accumsan ac metus eu efficitur. Aenean imperdiet aliquam nibh sit amet feugiat. Integer sed metus efficitur, pulvinar nunc rutrum, luctus urna. Sed ornare aliquam felis in pharetra. Fusce varius risus ac erat convallis volutpat. Integer gravida sit amet purus id consequat.\n\nNunc tempus arcu ut felis posuere, sed scelerisque ipsum facilisis. Interdum et malesuada fames ac ante ipsum primis in faucibus. Nam leo dui, sollicitudin in sapien sed, blandit mattis dolor. Sed nec tincidunt mauris. Integer non ligula vel nunc gravida varius id condimentum orci. Curabitur porta urna eu pulvinar scelerisque. Etiam nec vehicula felis. Mauris vestibulum magna ante, quis posuere felis euismod eu. Nulla tempus leo urna, sed ultricies mauris hendrerit eu. Suspendisse lacinia varius tristique. Cras in massa imperdiet, semper libero at, hendrerit nunc. Pellentesque pellentesque nisi sem, vel tincidunt quam mollis in.\n\nNullam congue scelerisque faucibus. Etiam quis velit eget tellus mollis viverra. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia curae; Nunc laoreet vulputate imperdiet. Morbi lacinia dictum mi, id dictum ligula luctus nec. Mauris nec luctus risus. Phasellus eget ex vitae nulla blandit elementum. Nunc lacus enim, elementum tristique lobortis ac, ultricies a nisl. Cras fringilla scelerisque metus, eu dictum mi vulputate sed. Mauris interdum dictum mi, sit amet semper nisl faucibus id. Sed consequat, turpis a varius interdum, enim nulla vulputate augue, non pharetra urna erat at dui. Cras in purus quam.\n\nSuspendisse in faucibus ipsum. Sed mollis nisl ac massa sodales, id accumsan dui ultrices. Aliquam tempus massa metus. Fusce venenatis eget risus sit amet fringilla. Quisque in dolor blandit, sollicitudin nibh et, porta nulla. Morbi eleifend purus a ante hendrerit condimentum. Mauris et molestie mi. Sed rutrum est velit, sed facilisis neque facilisis sed. Morbi quis metus feugiat libero viverra bibendum. Fusce finibus, tellus nec tempor aliquet, eros arcu sollicitudin felis, sed tincidunt justo ipsum nec purus.\n\nAliquam tincidunt quam nulla, id pharetra est finibus ac. Etiam finibus pharetra volutpat. Ut pretium porta augue tempus accumsan. Nullam quis est ut ex bibendum varius vitae non urna. Vestibulum bibendum lacus sit amet mauris imperdiet facilisis. Vivamus blandit est non arcu luctus aliquet. Nam eget libero vehicula, condimentum dui vitae, sodales lorem. Orci varius natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus.','Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec sodales metus vulputate justo luctus scelerisque. Sed rhoncus et arcu vel aliquet. Vivamus eget tempus lacus. In accumsan ac metus eu efficitur. Aenean imperdiet aliquam nibh sit amet feugiat. Integer sed metus efficitur, pulvinar nunc rutrum, luctus urna. Sed ornare aliquam felis in pharetra. Fusce varius risus ac erat convallis volutpat. Integer gravida sit amet purus id consequat.\n\nNunc tempus arcu ut felis posuere, sed scelerisque ipsum facilisis. Interdum et malesuada fames ac ante ipsum primis in faucibus. Nam leo dui, sollicitudin in sapien sed, blandit mattis dolor. Sed nec tincidunt mauris. Integer non ligula vel nunc gravida varius id condimentum orci. Curabitur porta urna eu pulvinar scelerisque. Etiam nec vehicula felis. Mauris vestibulum magna ante, quis posuere felis euismod eu. Nulla tempus leo urna, sed ultricies mauris hendrerit eu. Suspendisse lacinia varius tristique. Cras in massa imperdiet, semper libero at, hendrerit nunc. Pellentesque pellentesque nisi sem, vel tincidunt quam mollis in.\n\nNullam congue scelerisque faucibus. Etiam quis velit eget tellus mollis viverra. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia curae; Nunc laoreet vulputate imperdiet. Morbi lacinia dictum mi, id dictum ligula luctus nec. Mauris nec luctus risus. Phasellus eget ex vitae nulla blandit elementum. Nunc lacus enim, elementum tristique lobortis ac, ultricies a nisl. Cras fringilla scelerisque metus, eu dictum mi vulputate sed. Mauris interdum dictum mi, sit amet semper nisl faucibus id. Sed consequat, turpis a varius interdum, enim nulla vulputate augue, non pharetra urna erat at dui. Cras in purus quam.\n\nSuspendisse in faucibus ipsum. Sed mollis nisl ac massa sodales, id accumsan dui ultrices. Aliquam tempus massa metus. Fusce venenatis eget risus sit amet fringilla. Quisque in dolor blandit, sollicitudin nibh et, porta nulla. Morbi eleifend purus a ante hendrerit condimentum. Mauris et molestie mi. Sed rutrum est velit, sed facilisis neque facilisis sed. Morbi quis metus feugiat libero viverra bibendum. Fusce finibus, tellus nec tempor aliquet, eros arcu sollicitudin felis, sed tincidunt justo ipsum nec purus.\n\nAliquam tincidunt quam nulla, id pharetra est finibus ac. Etiam finibus pharetra volutpat. Ut pretium porta augue tempus accumsan. Nullam quis est ut ex bibendum varius vitae non urna. Vestibulum bibendum lacus sit amet mauris imperdiet facilisis. Vivamus blandit est non arcu luctus aliquet. Nam eget libero vehicula, condimentum dui vitae, sodales lorem. Orci varius natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus.','2025-12-31 23:57:26');

/*Table structure for table `customers` */

DROP TABLE IF EXISTS `customers`;

CREATE TABLE `customers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `country` varchar(50) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `point` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `customers` */

insert  into `customers`(`id`,`full_name`,`first_name`,`last_name`,`email`,`password`,`phone`,`country`,`profile_picture`,`point`,`created_at`,`updated_at`) values 
(1,'Radit Dharma','Radit','Dharma','radit@gmail.com','$2y$10$Zm9lxWw67iHRGDqvxeumXO/wJbuWbEk8oikJSeIlgBToqFPioOGqK','6281219251920','Indonesia',NULL,0,'2025-12-26 18:31:24','2026-01-01 20:42:17'),
(11,'Radit Yoga','Radit','Yoga','iputuradityadharmayoga@gmail.com','$2y$10$VzVQA453AuaK9hXAJ9E6/O888w9GTlkqJTBdbErE8xgHMkApUfOcC','6285960185215','Indonesia','/PROGNET/database/uploads/customers/customer_11_695d35f9eb1bb.jpg',118767113,'2026-01-01 14:58:32','2026-01-07 16:32:01');

/*Table structure for table `invoices` */

DROP TABLE IF EXISTS `invoices`;

CREATE TABLE `invoices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_code` varchar(50) NOT NULL,
  `invoice_path` varchar(255) DEFAULT NULL,
  `generated_at` datetime DEFAULT current_timestamp(),
  `status` enum('draft','issued','paid','refunded') DEFAULT 'issued',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_booking_invoice` (`booking_code`),
  KEY `idx_status` (`status`),
  KEY `idx_generated_at` (`generated_at`),
  CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`booking_code`) REFERENCES `bookings` (`booking_code`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `invoices` */

insert  into `invoices`(`id`,`booking_code`,`invoice_path`,`generated_at`,`status`,`created_at`,`updated_at`) values 
(6,'BK00011','/PROGNET/database/uploads/invoices/invoice_BK00011_1767714197.pdf','2026-01-06 23:43:17','','2026-01-06 23:43:17','2026-01-06 23:43:17'),
(7,'BK00012','/PROGNET/database/uploads/invoices/invoice_BK00012_1767714695.pdf','2026-01-06 23:51:35','','2026-01-06 23:51:35','2026-01-06 23:51:35'),
(8,'BK00013','/PROGNET/database/uploads/invoices/invoice_BK00013_1767728619.pdf','2026-01-07 03:43:39','','2026-01-07 03:43:39','2026-01-07 03:43:39');

/*Table structure for table `order_cancellations` */

DROP TABLE IF EXISTS `order_cancellations`;

CREATE TABLE `order_cancellations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_code` varchar(10) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `reason` text NOT NULL,
  `canceled_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_booking_code` (`booking_code`),
  KEY `idx_admin_id` (`admin_id`),
  CONSTRAINT `fk_cancel_admin` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_cancel_booking` FOREIGN KEY (`booking_code`) REFERENCES `order_request` (`booking_code`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `order_cancellations` */

insert  into `order_cancellations`(`id`,`booking_code`,`admin_id`,`reason`,`canceled_at`) values 
(1,'ORD00001',5,'Aku tidak suka pergi ke Bekasi','2025-12-25 23:32:18'),
(2,'ORD00002',1,'Kamu ga boleh ke Bekasi','2026-01-05 21:45:09'),
(3,'ORD00023',1,'Gausah ke bekasi','2026-01-06 20:38:09');

/*Table structure for table `order_request` */

DROP TABLE IF EXISTS `order_request`;

CREATE TABLE `order_request` (
  `booking_code` varchar(10) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `product_id` int(11) NOT NULL,
  `option_name` enum('Private','Group') NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `total_adult` int(11) DEFAULT 0,
  `total_child` int(11) DEFAULT 0,
  `gross_rate` decimal(12,2) NOT NULL,
  `duration` varchar(50) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `meeting_point` varchar(255) DEFAULT NULL,
  `points_used` int(11) DEFAULT 0,
  `transaction_id` varchar(100) DEFAULT NULL,
  `purchase_date` datetime NOT NULL,
  `activity_date` datetime NOT NULL,
  `status` enum('request','payment','reject') DEFAULT 'request',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`booking_code`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_status` (`status`),
  KEY `idx_activity_date` (`activity_date`),
  KEY `idx_customer_id` (`customer_id`),
  CONSTRAINT `fk_orderreq_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `order_request` */

insert  into `order_request`(`booking_code`,`customer_id`,`product_id`,`option_name`,`customer_name`,`total_adult`,`total_child`,`gross_rate`,`duration`,`phone`,`email`,`meeting_point`,`points_used`,`transaction_id`,`purchase_date`,`activity_date`,`status`,`created_at`,`updated_at`) values 
('ORD00001',11,54,'Private','John Smith',2,1,3000000.00,'10','081234567890','john.smith@gmail.com',NULL,0,NULL,'2025-12-25 22:41:55','2025-12-28 00:00:00','reject','2025-12-25 22:41:55','2026-01-04 03:36:35'),
('ORD00002',11,54,'Private','Hary Susila',2,1,3000000.00,'10','081234567890','john.smith@gmail.com',NULL,0,NULL,'2025-12-25 23:34:16','2025-12-28 00:00:00','reject','2025-12-25 23:34:16','2026-01-05 21:45:09'),
('ORD00023',11,54,'Private','Dharma Yoga',17,0,17000000.00,'10 hours','6285960185215','iputuradityadharmayoga@gmail.com',NULL,0,NULL,'2026-01-06 13:34:23','2026-11-11 11:11:00','reject','2026-01-06 20:34:23','2026-01-06 20:38:09'),
('ORD00026',11,55,'Private','Dharma Yoga',1,1,20000000.00,'6 hours','6285960185215','iputuradityadharmayoga@gmail.com',NULL,0,NULL,'2026-01-06 20:28:50','2026-01-08 11:11:00','request','2026-01-07 03:28:50','2026-01-07 03:28:50');

/*Table structure for table `order_sequence` */

DROP TABLE IF EXISTS `order_sequence`;

CREATE TABLE `order_sequence` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `order_sequence` */

insert  into `order_sequence`(`id`) values 
(1),
(2),
(3),
(4),
(5),
(6),
(19),
(20),
(21),
(22),
(23),
(24),
(25),
(26),
(27),
(28),
(29);

/*Table structure for table `otp_verifications` */

DROP TABLE IF EXISTS `otp_verifications`;

CREATE TABLE `otp_verifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `type` enum('phone','email') NOT NULL,
  `target` varchar(100) NOT NULL,
  `otp_hash` varchar(255) NOT NULL,
  `expired_at` datetime NOT NULL,
  `verified_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_target` (`target`),
  KEY `idx_customer` (`customer_id`),
  KEY `idx_expired` (`expired_at`)
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `otp_verifications` */

insert  into `otp_verifications`(`id`,`customer_id`,`type`,`target`,`otp_hash`,`expired_at`,`verified_at`,`created_at`) values 
(2,NULL,'phone','+6281219251920','$2y$10$Eirq.m9mCAkSmteGqyIRxeMoVjGOvmOrP7T.3laRQJbMeyF1/upeW','2026-01-01 12:54:25',NULL,'2026-01-01 12:44:25'),
(4,NULL,'phone','+626285960185215','$2y$10$ErtCLwsucSnyUJSXoYYkjuSMiczTqkjUUr8TnRcyERhBVk9ey4LZu','2026-01-01 13:00:24',NULL,'2026-01-01 12:50:24'),
(5,NULL,'phone','+6285960185215','$2y$10$e9Awia0qwEjmIbYMIxekbO/V4d1c8kKZc6Sf3Orzt2ed5I2aC6yRq','2026-01-01 13:02:37',NULL,'2026-01-01 12:52:37'),
(8,NULL,'phone','+62895628777771','$2y$10$b/rVx8NG9tjv3bBcJ4.U8uY9qXWJyyQkK.wRtvK1i2mQxTlfKxpEu','2026-01-01 13:08:49',NULL,'2026-01-01 12:58:49'),
(14,11,'phone','6285960185215','$2y$10$dW13Mr3dNelxV8k4xBJbguxp6tWhxZQLE0vIL0cI./TaydJyKZpbO','2026-01-01 15:05:17','2026-01-01 14:58:32','2026-01-01 14:55:17'),
(15,NULL,'phone','6282173004883','$2y$10$jHK4cXJIwrozreZI8Wy08eJRic./ffLSDQP0d10Sq7Q8DauLdSX7W','2026-01-01 15:51:46',NULL,'2026-01-01 15:41:46'),
(50,NULL,'phone','62895628777771','$2y$10$kWASMJZb6FRf5H3UTZStOe6v92K8UgUUpgnQPjAhAN8AvjAFrF9Z6','2026-01-05 14:34:18',NULL,'2026-01-05 14:24:18'),
(52,11,'email','iputuradityadharmayoga@gmail.com','$2y$10$ycf.EbDdXYpXJo4AJAlHGeVn4bAXzi9IWFwk3brqu8tu3kIvPC67e','2026-01-07 08:51:02',NULL,'2026-01-07 08:41:02');

/*Table structure for table `product_categories` */

DROP TABLE IF EXISTS `product_categories`;

CREATE TABLE `product_categories` (
  `product_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  PRIMARY KEY (`product_id`,`category_id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `product_categories_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_categories_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `product_categories` */

insert  into `product_categories`(`product_id`,`category_id`) values 
(54,5),
(55,1),
(55,2),
(55,3),
(55,4),
(55,5),
(55,6),
(55,7),
(55,8),
(55,9),
(55,10),
(57,1),
(57,2),
(57,3),
(57,4),
(57,5),
(57,6),
(57,7),
(57,8),
(57,9),
(57,10);

/*Table structure for table `product_photos` */

DROP TABLE IF EXISTS `product_photos`;

CREATE TABLE `product_photos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `product_photos_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `product_photos` */

insert  into `product_photos`(`id`,`product_id`,`photo_path`) values 
(31,55,'/PROGNET/database/uploads/photos/1766748469_1_IMG20250912143307.jpg'),
(34,55,'/PROGNET/database/uploads/photos/1766748624_0_IMG20250912142842.jpg'),
(42,57,'/PROGNET/database/uploads/photos/1766759703_0_IMG20250912142842.jpg'),
(43,57,'/PROGNET/database/uploads/photos/1766759703_1_IMG20250912143307.jpg'),
(44,54,'/PROGNET/database/uploads/photos/1767412352_0_98cd24c3-938e-4af4-8324-4cd3e51a6e0e.webp'),
(46,55,'/PROGNET/database/uploads/photos/1767773561_0_ORD00019_1767557927-qris.png'),
(47,55,'/PROGNET/database/uploads/photos/1767773561_1_Programming Languages and Coding.png');

/*Table structure for table `product_prices` */

DROP TABLE IF EXISTS `product_prices`;

CREATE TABLE `product_prices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) DEFAULT NULL,
  `category` enum('private','group') DEFAULT NULL,
  `adult_price` int(11) DEFAULT NULL,
  `child_price` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `product_prices_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=185 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `product_prices` */

insert  into `product_prices`(`id`,`product_id`,`category`,`adult_price`,`child_price`) values 
(171,57,'private',1000000,1000000),
(172,57,'group',1000000,1000000),
(177,54,'private',1000000,700000),
(178,54,'group',500000,200000),
(183,55,'private',10000000,10000000),
(184,55,'group',1200000,1200000);

/*Table structure for table `product_reviews` */

DROP TABLE IF EXISTS `product_reviews`;

CREATE TABLE `product_reviews` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `booking_code` varchar(20) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_country` varchar(50) DEFAULT NULL,
  `customer_avatar` varchar(255) DEFAULT NULL,
  `rating` tinyint(3) unsigned NOT NULL CHECK (`rating` between 1 and 5),
  `review_message` text DEFAULT NULL,
  `is_published` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_product` (`product_id`),
  KEY `idx_booking` (`booking_code`),
  KEY `idx_rating` (`rating`),
  KEY `idx_customer_id` (`customer_id`),
  CONSTRAINT `fk_review_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `product_reviews` */

insert  into `product_reviews`(`id`,`booking_code`,`customer_id`,`product_id`,`customer_name`,`customer_country`,`customer_avatar`,`rating`,`review_message`,`is_published`,`created_at`) values 
(1,'BK00001',11,55,'Daniel Thompson','United States','/PROGNET/database/uploads/customers/customer_11_695d35f9eb1bb.jpg',5,'Amazing experience! The guide was very friendly and professional. Everything was well organized and on time.',1,'2025-11-01 14:30:00'),
(2,'BK00001',NULL,55,'Sophie Laurent','France',NULL,4,'Beautiful places and great atmosphere. Slight delay at the beginning but overall very enjoyable.',1,'2025-11-03 10:15:00'),
(3,'BK00001',NULL,55,'Michael Chen','Singapore',NULL,5,'Highly recommended! Smooth trip, clear communication, and the destinations were stunning.',1,'2025-11-05 18:45:00'),
(4,'BK00001',NULL,55,'Emma Rodriguez','Spain',NULL,3,'The tour was okay, but some locations felt a bit rushed. Still worth the experience.',1,'2025-11-06 09:20:00'),
(6,'BK00007',11,54,'Dharma Yoga','Indonesia','/PROGNET/database/uploads/customers/customer_11_695d35f9eb1bb.jpg',5,'Test',1,'2026-01-06 16:02:35'),
(7,'BK00006',11,54,'Dharma Yoga','Indonesia','/PROGNET/database/uploads/customers/customer_11_695d35f9eb1bb.jpg',3,'Gaseru ke bekasi',1,'2026-01-06 20:46:30');

/*Table structure for table `products` */

DROP TABLE IF EXISTS `products`;

CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `full_description` text DEFAULT NULL,
  `include` text DEFAULT NULL,
  `exclude` text DEFAULT NULL,
  `duration_hours` int(11) DEFAULT NULL,
  `reference_code` varchar(100) DEFAULT NULL,
  `itinerary_file` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `thumbnail` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=59 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `products` */

insert  into `products`(`id`,`title`,`full_description`,`include`,`exclude`,`duration_hours`,`reference_code`,`itinerary_file`,`created_at`,`thumbnail`,`is_active`,`updated_at`) values 
(54,'Pergi Ke Bekasi','Pokoknya pergi ke bekasi','Dapet bekasi','Gaada',10,'Bekasi-1','/PROGNET/database/uploads/itinerary/1766585268_itinerary_EXPERIENCE BALI.pdf','2025-12-24 22:07:48','/PROGNET/database/uploads/photos/1767412352_0_98cd24c3-938e-4af4-8324-4cd3e51a6e0e.webp',1,'2026-01-03 11:52:32'),
(55,'Bali: Lempuyang Temple, Lahangan & Tirta Gangga Private Tour','Bali: Lempuyang Temple, Lahangan & Tirta Gangga Private Tour','Bali: Lempuyang Temple, Lahangan & Tirta Gangga Private Tour','Bali: Lempuyang Temple, Lahangan & Tirta Gangga Private Tour',6,'Bali-1','/PROGNET/database/uploads/itinerary/1766717812_itinerary_EXPERIENCE BALI.pdf','2025-12-26 10:56:52','/PROGNET/database/uploads/photos/1766748469_1_IMG20250912143307.jpg',0,'2026-01-07 16:12:41'),
(57,'Bali: ATV, Coffee Plantation, Temple & Monkey Forest Tour','Bali: ATV, Coffee Plantation, Temple & Monkey Forest Tour','Bali: ATV, Coffee Plantation, Temple & Monkey Forest Tour','Bali: ATV, Coffee Plantation, Temple & Monkey Forest Tour',24,'Bali-2','/PROGNET/database/uploads/itinerary/1766749966_Buku Diktat (Pangkalan Data).docx','2025-12-26 19:52:46','/PROGNET/database/uploads/photos/1766759703_0_IMG20250912142842.jpg',1,'2025-12-26 22:35:03');

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
