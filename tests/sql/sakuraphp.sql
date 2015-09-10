-- MySQL dump 10.15  Distrib 10.0.19-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: sakuraphp
-- ------------------------------------------------------
-- Server version	10.0.19-MariaDB-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `level_example_1`
--

DROP TABLE IF EXISTS `level_example_1`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `level_example_1` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `L1` tinyint(3) unsigned NOT NULL,
  `L2` tinyint(3) unsigned NOT NULL,
  `L3` tinyint(3) unsigned NOT NULL,
  `L4` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `level_example_1`
--

LOCK TABLES `level_example_1` WRITE;
/*!40000 ALTER TABLE `level_example_1` DISABLE KEYS */;
INSERT INTO `level_example_1` VALUES (1,1,0,0,0),(2,1,1,0,0),(3,1,2,0,0),(4,1,3,0,0),(5,1,1,1,0),(6,1,1,2,0),(7,1,1,3,0),(8,1,1,4,0),(9,1,1,5,0),(10,1,2,1,0),(11,1,2,2,0),(12,1,2,3,0),(13,1,2,4,0),(14,1,3,1,0),(15,1,3,2,0),(16,1,3,3,0),(17,1,3,4,0),(18,1,3,5,0);
/*!40000 ALTER TABLE `level_example_1` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `level_example_2`
--

DROP TABLE IF EXISTS `level_example_2`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `level_example_2` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `L1` tinyint(3) unsigned NOT NULL,
  `L2` tinyint(3) unsigned NOT NULL,
  `L3` tinyint(3) unsigned NOT NULL,
  `L4` tinyint(3) unsigned NOT NULL,
  `L5` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `L6` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `L7` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `name` varchar(20) COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `level_example_2`
--

LOCK TABLES `level_example_2` WRITE;
/*!40000 ALTER TABLE `level_example_2` DISABLE KEYS */;
INSERT INTO `level_example_2` VALUES (1,1,0,0,0,0,0,0,'R'),(2,1,1,0,0,0,0,0,'X'),(3,1,2,0,0,0,0,0,'J'),(4,1,3,0,0,0,0,0,'S'),(5,1,1,1,0,0,0,0,'G'),(6,1,1,2,0,0,0,0,'L'),(7,1,1,3,0,0,0,0,'V'),(8,1,1,4,0,0,0,0,'7'),(9,1,1,5,0,0,0,0,'6'),(10,1,2,1,0,0,0,0,'E'),(11,1,2,2,0,0,0,0,'I'),(12,1,2,3,0,0,0,0,'C'),(13,1,2,4,0,0,0,0,'N'),(14,1,2,5,0,0,0,0,'Q'),(15,1,3,1,0,0,0,0,'3'),(16,1,3,2,0,0,0,0,'M'),(17,1,3,3,0,0,0,0,'B'),(18,1,3,4,0,0,0,0,'K'),(19,1,3,5,0,0,0,0,'0');
/*!40000 ALTER TABLE `level_example_2` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_example_1`
--

DROP TABLE IF EXISTS `order_example_1`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_example_1` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `parent` smallint(5) unsigned NOT NULL,
  `depth` tinyint(3) unsigned NOT NULL,
  `order` smallint(5) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_example_1`
--

LOCK TABLES `order_example_1` WRITE;
/*!40000 ALTER TABLE `order_example_1` DISABLE KEYS */;
INSERT INTO `order_example_1` VALUES (1,0,0,1),(2,1,1,2),(3,1,1,8),(4,1,1,13),(5,2,2,3),(6,2,2,4),(7,2,2,5),(8,2,2,6),(9,2,2,7),(10,3,2,9),(11,3,2,10),(12,3,2,11),(13,3,2,12),(14,4,2,14),(15,4,2,15),(16,4,2,16),(17,4,2,17),(18,4,2,18);
/*!40000 ALTER TABLE `order_example_1` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_example_2`
--

DROP TABLE IF EXISTS `order_example_2`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_example_2` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `parent` smallint(5) unsigned NOT NULL,
  `depth` tinyint(3) unsigned NOT NULL,
  `order` smallint(5) unsigned NOT NULL,
  `name` varchar(20) COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_example_2`
--

LOCK TABLES `order_example_2` WRITE;
/*!40000 ALTER TABLE `order_example_2` DISABLE KEYS */;
INSERT INTO `order_example_2` VALUES (1,0,0,1,'R'),(2,1,1,2,'X'),(3,1,1,8,'J'),(4,1,1,14,'S'),(5,2,2,3,'G'),(6,2,2,4,'L'),(7,2,2,5,'V'),(8,2,2,6,'7'),(9,2,2,7,'6'),(10,3,2,9,'E'),(11,3,2,10,'I'),(12,3,2,11,'C'),(13,3,2,12,'N'),(14,3,2,13,'Q'),(15,4,2,15,'3'),(16,4,2,16,'M'),(17,4,2,17,'B'),(18,4,2,18,'K'),(19,4,2,19,'0');
/*!40000 ALTER TABLE `order_example_2` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `parent_example_1`
--

DROP TABLE IF EXISTS `parent_example_1`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `parent_example_1` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `parent` smallint(5) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parent_example_1`
--

LOCK TABLES `parent_example_1` WRITE;
/*!40000 ALTER TABLE `parent_example_1` DISABLE KEYS */;
INSERT INTO `parent_example_1` VALUES (1,0),(2,1),(3,1),(4,1),(5,2),(6,2),(7,2),(8,2),(9,2),(10,3),(11,3),(12,3),(13,3),(14,4),(15,4),(16,4),(17,4),(18,4);
/*!40000 ALTER TABLE `parent_example_1` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `parent_example_2`
--

DROP TABLE IF EXISTS `parent_example_2`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `parent_example_2` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `parent` smallint(5) unsigned NOT NULL,
  `name` varchar(20) COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parent_example_2`
--

LOCK TABLES `parent_example_2` WRITE;
/*!40000 ALTER TABLE `parent_example_2` DISABLE KEYS */;
INSERT INTO `parent_example_2` VALUES (1,0,'R'),(2,1,'X'),(3,1,'J'),(4,1,'S'),(5,2,'G'),(6,2,'L'),(7,2,'V'),(8,2,'7'),(9,2,'6'),(10,3,'E'),(11,3,'I'),(12,3,'C'),(13,3,'N'),(14,3,'Q'),(15,4,'3'),(16,4,'M'),(17,4,'B'),(18,4,'K'),(19,4,'0');
/*!40000 ALTER TABLE `parent_example_2` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `traversal_example_1`
--

DROP TABLE IF EXISTS `traversal_example_1`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `traversal_example_1` (
  `id` smallint(6) NOT NULL AUTO_INCREMENT,
  `parent` smallint(6) NOT NULL,
  `left` smallint(5) unsigned NOT NULL,
  `right` smallint(5) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `traversal_example_1`
--

LOCK TABLES `traversal_example_1` WRITE;
/*!40000 ALTER TABLE `traversal_example_1` DISABLE KEYS */;
INSERT INTO `traversal_example_1` VALUES (1,0,1,36),(2,1,2,13),(3,1,14,23),(4,1,24,35),(5,2,3,4),(6,2,5,6),(7,2,7,8),(8,2,9,10),(9,2,11,12),(10,3,15,16),(11,3,17,18),(12,3,19,20),(13,3,21,22),(14,4,25,26),(15,4,27,28),(16,4,29,30),(17,4,31,32),(18,4,33,34);
/*!40000 ALTER TABLE `traversal_example_1` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `traversal_example_2`
--

DROP TABLE IF EXISTS `traversal_example_2`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `traversal_example_2` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `parent` smallint(5) unsigned NOT NULL,
  `left` smallint(5) unsigned NOT NULL,
  `right` smallint(5) unsigned NOT NULL,
  `name` varchar(20) COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `traversal_example_2`
--

LOCK TABLES `traversal_example_2` WRITE;
/*!40000 ALTER TABLE `traversal_example_2` DISABLE KEYS */;
INSERT INTO `traversal_example_2` VALUES (1,0,1,38,'R'),(2,1,2,13,'X'),(3,1,14,25,'J'),(4,1,26,37,'S'),(5,2,3,4,'G'),(6,2,5,6,'L'),(7,2,7,8,'V'),(8,2,9,10,'7'),(9,2,11,12,'6'),(10,3,15,16,'E'),(11,3,17,18,'I'),(12,3,19,20,'C'),(13,3,21,22,'N'),(14,3,23,24,'Q'),(15,4,27,28,'3'),(16,4,29,30,'M'),(17,4,31,32,'B'),(18,4,33,34,'K'),(19,4,35,36,'0');
/*!40000 ALTER TABLE `traversal_example_2` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2015-09-09 17:17:36
