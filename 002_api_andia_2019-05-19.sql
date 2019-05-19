# ************************************************************
# Sequel Pro SQL dump
# Version 4541
#
# http://www.sequelpro.com/
# https://github.com/sequelpro/sequelpro
#
# Host: 127.0.0.1 (MySQL 5.7.25)
# Database: 002_api_andia
# Generation Time: 2019-05-19 20:15:43 +0000
# ************************************************************


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Dump of table promociones
# ------------------------------------------------------------

DROP TABLE IF EXISTS `promociones`;

CREATE TABLE `promociones` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `zona` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

LOCK TABLES `promociones` WRITE;
/*!40000 ALTER TABLE `promociones` DISABLE KEYS */;

INSERT INTO `promociones` (`id`, `name`, `zona`, `created_at`, `active`, `deleted`)
VALUES
	(1,'Promocion I','Soto Lezkairu','2019-05-19 21:26:35',1,0),
	(2,'Promocion II','Ardoi','2019-05-19 21:26:45',1,0),
	(3,'Promocion III','Mutilva','2019-05-19 21:26:56',1,0),
	(4,'Promocion IV','Mutilva','2019-05-19 21:27:04',1,0),
	(5,'Promocion V','Obanos','2019-05-19 21:27:11',1,0),
	(6,'Promocion VI','Pamplona','2019-05-19 21:27:19',1,0);

/*!40000 ALTER TABLE `promociones` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table promociones_tipos_inmuebles
# ------------------------------------------------------------

DROP TABLE IF EXISTS `promociones_tipos_inmuebles`;

CREATE TABLE `promociones_tipos_inmuebles` (
  `promociones_id` int(11) unsigned NOT NULL,
  `tipos_inmuebles_id` int(11) unsigned NOT NULL,
  KEY `promociones_id` (`promociones_id`,`tipos_inmuebles_id`),
  KEY `tipos_inmuebles_id` (`tipos_inmuebles_id`),
  CONSTRAINT `promociones_id` FOREIGN KEY (`promociones_id`) REFERENCES `promociones` (`id`),
  CONSTRAINT `tipos_inmuebles_id` FOREIGN KEY (`tipos_inmuebles_id`) REFERENCES `tipos_inmuebles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `promociones_tipos_inmuebles` WRITE;
/*!40000 ALTER TABLE `promociones_tipos_inmuebles` DISABLE KEYS */;

INSERT INTO `promociones_tipos_inmuebles` (`promociones_id`, `tipos_inmuebles_id`)
VALUES
	(1,1);

/*!40000 ALTER TABLE `promociones_tipos_inmuebles` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table tipos_inmuebles
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tipos_inmuebles`;

CREATE TABLE `tipos_inmuebles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

LOCK TABLES `tipos_inmuebles` WRITE;
/*!40000 ALTER TABLE `tipos_inmuebles` DISABLE KEYS */;

INSERT INTO `tipos_inmuebles` (`id`, `name`, `created_at`, `active`, `deleted`)
VALUES
	(1,'Hola','2019-05-19 21:59:31',1,1);

/*!40000 ALTER TABLE `tipos_inmuebles` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table users
# ------------------------------------------------------------

DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` timestamp NULL DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  `superuser` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;

INSERT INTO `users` (`id`, `name`, `email`, `password`, `created_at`, `updated_at`, `last_login`, `active`, `deleted`, `superuser`)
VALUES
	(2,'hola','mail@gmail.com','$2y$10$exwIyWuIaG2Wvwd/VYyxbetQi2AKfGwBIoydw0syu.uFM0G/cKdjC','2019-05-18 16:18:32','2019-05-18 16:18:32','2019-05-19 16:39:39',1,0,1),
	(8,'hola mundo','mail@mail.com','$2y$10$vXCIb2F2IVIv2vLH9DTvFerm90lHYKRG6yARuWY5OJaXGraaK4Cp.','2019-05-19 15:55:18','2019-05-19 15:55:18','2019-05-19 16:09:53',1,0,0);

/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table visitas
# ------------------------------------------------------------

DROP TABLE IF EXISTS `visitas`;

CREATE TABLE `visitas` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `telefono` varchar(11) COLLATE utf8_unicode_ci DEFAULT NULL,
  `promociones_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` timestamp NULL DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  `observaciones` text COLLATE utf8_unicode_ci,
  `fecha_visita` date NOT NULL,
  `conociste` text COLLATE utf8_unicode_ci,
  `status` set('primera','reserva','anulacion','compra') COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;




/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
