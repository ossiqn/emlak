SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+03:00";

CREATE TABLE `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `admins` (`id`, `username`, `password`) VALUES
(1, 'admin', '123456');

CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(100) NOT NULL,
  `category_slug` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `category_slug` (`category_slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `categories` (`id`, `category_name`, `category_slug`) VALUES
(1, 'Lüks Daireler', 'luks-daireler'),
(2, 'Müstakil Villalar', 'mustakil-villalar'),
(3, 'Ofis / Dükkan', 'ofis-dukkan'),
(4, 'Arsa', 'arsa');

CREATE TABLE `properties` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `price` decimal(15,2) NOT NULL,
  `status` enum('satilik','kiralik') NOT NULL DEFAULT 'satilik',
  `property_type` varchar(100) NOT NULL,
  `room_count` varchar(50) DEFAULT NULL,
  `square_meters` int(11) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `property_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `property_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `is_main` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `property_id` (`property_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`) VALUES
(1, 'site_title', 'Ossiqn Emlak'),
(2, 'contact_phone', '0555 555 55 55'),
(3, 'contact_email', 'info@ossiqnemlak.com'),
(4, 'hero_title', 'Hayalinizdeki Yaşama Adım Atın'),
(5, 'hero_desc', 'En seçkin portföyümüzle size özel yaşam alanları sunuyoruz. Profesyonel ekibimizle her zaman yanınızdayız.'),
(6, 'footer_text', '© 2026 Ossiqn Emlak. Tüm Hakları Saklıdır.');

ALTER TABLE `property_images`
  ADD CONSTRAINT `property_images_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE;

COMMIT;