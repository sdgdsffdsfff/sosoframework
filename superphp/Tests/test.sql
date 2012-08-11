-- MySQL dump 10.13  Distrib 5.5.16, for osx10.6 (i386)
--
-- Host: localhost    Database: for_test
-- ------------------------------------------------------
-- Server version	5.5.16

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
-- Table structure for table `user_feed`
--

DROP TABLE IF EXISTS `user_feed`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_feed` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `qq` int(10) unsigned NOT NULL,
  `username` varchar(15) NOT NULL DEFAULT '',
  `dateline` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `hash_template` varchar(32) NOT NULL DEFAULT '',
  `hash_data` varchar(32) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `uid` (`qq`,`dateline`),
  KEY `dateline` (`dateline`)
) ENGINE=MyISAM AUTO_INCREMENT=11 DEFAULT CHARSET=gbk COMMENT='用户动态表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_feed`
--

LOCK TABLES `user_feed` WRITE;
/*!40000 ALTER TABLE `user_feed` DISABLE KEYS */;
INSERT INTO `user_feed` VALUES (1,123456,'moonzhang','2012-02-28 09:47:25','test template','test data'),(2,123456,'moonzhang','2012-02-28 09:47:25','test2 template','test2 data'),(3,123456,'moonzhang','2012-02-28 09:47:25','test template','test data'),(4,223456,'moonzhang','2012-02-28 09:47:25','test template','test data'),(5,323456,'moonzhang','2012-02-28 09:47:25','test template','test data'),(6,423456,'moonzhang','2012-02-28 09:47:25','test template','test data'),(7,523456,'moonzhang','2012-02-28 09:47:25','test template','test data'),(8,623456,'moonzhang','2012-02-28 09:47:25','test template','test data'),(9,723456,'moonzhang','2012-02-28 09:47:25','test template','test data'),(10,123456,'moonzhang','2012-02-28 09:47:25','test template','test data');
/*!40000 ALTER TABLE `user_feed` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_feed_orig`
--

DROP TABLE IF EXISTS `user_feed_orig`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_feed_orig` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `qq` int(10) unsigned NOT NULL,
  `username` varchar(15) NOT NULL DEFAULT '',
  `dateline` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `hash_template` varchar(32) NOT NULL DEFAULT '',
  `hash_data` varchar(32) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `uid` (`qq`,`dateline`),
  KEY `dateline` (`dateline`)
) ENGINE=MyISAM AUTO_INCREMENT=11 DEFAULT CHARSET=gbk COMMENT='用户动态表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_feed_orig`
--

LOCK TABLES `user_feed_orig` WRITE;
/*!40000 ALTER TABLE `user_feed_orig` DISABLE KEYS */;
INSERT INTO `user_feed_orig` VALUES (1,123456,'moonzhang','2012-02-28 09:47:25','test template','test data'),(2,123456,'moonzhang','2012-02-28 09:47:25','test2 template','test2 data'),(3,123456,'moonzhang','2012-02-28 09:47:25','test template','test data'),(4,223456,'moonzhang','2012-02-28 09:47:25','test template','test data'),(5,323456,'moonzhang','2012-02-28 09:47:25','test template','test data'),(6,423456,'moonzhang','2012-02-28 09:47:25','test template','test data'),(7,523456,'moonzhang','2012-02-28 09:47:25','test template','test data'),(8,623456,'moonzhang','2012-02-28 09:47:25','test template','test data'),(9,723456,'moonzhang','2012-02-28 09:47:25','test template','test data'),(10,123456,'moonzhang','2012-02-28 09:47:25','test template','test data');
/*!40000 ALTER TABLE `user_feed_orig` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_items`
--

DROP TABLE IF EXISTS `user_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_items` (
  `id` int(4) NOT NULL AUTO_INCREMENT,
  `qq` bigint(4) unsigned NOT NULL,
  `item_id` int(4) unsigned NOT NULL,
  `cost` int(4) unsigned DEFAULT NULL,
  `discount` decimal(3,2) DEFAULT NULL,
  `ctime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `gettype` tinyint(1) unsigned DEFAULT NULL COMMENT '0:drawItem, 1:buyItem',
  `send_date` date DEFAULT NULL,
  `send_type` tinyint(1) DEFAULT NULL,
  `expire` datetime DEFAULT NULL,
  `status` int(4) DEFAULT '1',
  `cdkey` varchar(50) NOT NULL,
  `item_sync_flag` tinyint(1) DEFAULT '0' COMMENT 'where cache is down, the flag to restore items total_left',
  PRIMARY KEY (`id`),
  KEY `qq` (`qq`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_items`
--

LOCK TABLES `user_items` WRITE;
/*!40000 ALTER TABLE `user_items` DISABLE KEYS */;
INSERT INTO `user_items` VALUES (1,123456,1,88,0.80,'2012-03-14 03:21:08',1,'2012-03-14',1,NULL,1,'112233',0),(2,66666,1,88,0.80,'2012-03-14 03:21:17',1,'2012-03-14',1,NULL,1,'112233',0),(3,777777,1,88,0.80,'2012-03-14 03:21:26',1,'2012-03-14',1,NULL,1,'112233',0),(4,103456,1,100,0.90,'2012-03-14 03:21:41',1,'2012-03-14',1,NULL,1,'112233',0),(5,223456,1,100,0.85,'2012-03-14 03:21:56',1,'2012-03-14',1,NULL,1,'112233',0);
/*!40000 ALTER TABLE `user_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_items_orig`
--

DROP TABLE IF EXISTS `user_items_orig`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_items_orig` (
  `id` int(4) NOT NULL AUTO_INCREMENT,
  `qq` bigint(4) unsigned NOT NULL,
  `item_id` int(4) unsigned NOT NULL,
  `cost` int(4) unsigned DEFAULT NULL,
  `discount` decimal(3,2) DEFAULT NULL,
  `ctime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `gettype` tinyint(1) unsigned DEFAULT NULL COMMENT '0:drawItem, 1:buyItem',
  `send_date` date DEFAULT NULL,
  `send_type` tinyint(1) DEFAULT NULL,
  `expire` datetime DEFAULT NULL,
  `status` int(4) DEFAULT '1',
  `cdkey` varchar(50) NOT NULL,
  `item_sync_flag` tinyint(1) DEFAULT '0' COMMENT 'where cache is down, the flag to restore items total_left',
  PRIMARY KEY (`id`),
  KEY `qq` (`qq`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_items_orig`
--

LOCK TABLES `user_items_orig` WRITE;
/*!40000 ALTER TABLE `user_items_orig` DISABLE KEYS */;
INSERT INTO `user_items_orig` VALUES (1,123456,1,88,0.80,'2012-03-14 03:21:08',1,'2012-03-14',1,NULL,1,'112233',0),(2,66666,1,88,0.80,'2012-03-14 03:21:17',1,'2012-03-14',1,NULL,1,'112233',0),(3,777777,1,88,0.80,'2012-03-14 03:21:26',1,'2012-03-14',1,NULL,1,'112233',0),(4,103456,1,100,0.90,'2012-03-14 03:21:41',1,'2012-03-14',1,NULL,1,'112233',0),(5,223456,1,100,0.85,'2012-03-14 03:21:56',1,'2012-03-14',1,NULL,1,'112233',0);
/*!40000 ALTER TABLE `user_items_orig` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `qq` bigint(4) unsigned NOT NULL,
  `golds` int(4) unsigned NOT NULL DEFAULT '0' COMMENT '当前金币数',
  `maxgolds` int(4) unsigned NOT NULL DEFAULT '0',
  `buytimes` int(4) unsigned NOT NULL DEFAULT '0' COMMENT '兑换次数',
  `drawtimes` int(4) unsigned NOT NULL DEFAULT '0' COMMENT '抽奖次数',
  `pclose` tinyint(1) NOT NULL DEFAULT '0' COMMENT '永久关闭彩蛋:０否,１是;',
  `lighted` tinyint(1) NOT NULL DEFAULT '0' COMMENT '点亮图票:０未,１已点亮',
  `source` char(10) DEFAULT NULL COMMENT '上次来路 toolbar,web,im,nav etc',
  `daily_date` date NOT NULL DEFAULT '0000-00-00',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '０默认值,供扩展使用',
  PRIMARY KEY (`qq`),
  UNIQUE KEY `qq` (`qq`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (223456,100,500,0,0,0,0,'hao','2012-02-28',1),(323456,100,500,0,0,0,0,'hao','2012-02-28',0),(423456,100,500,0,0,0,0,'hao','2012-02-28',0),(723456,100,500,0,0,0,0,'hao','2012-02-28',0),(623456,100,500,0,0,0,0,'hao','2012-02-28',0),(523456,100,500,0,0,0,0,'hao','2012-02-28',0),(823456,100,500,0,0,0,0,'hao','2012-02-28',0),(923456,100,500,0,0,0,0,'hao','2012-02-28',0),(103456,101,500,0,0,0,0,'hao','2012-02-28',1),(123456,100,500,0,0,0,0,NULL,'0000-00-00',0);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users_orig`
--

DROP TABLE IF EXISTS `users_orig`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users_orig` (
  `qq` bigint(4) unsigned NOT NULL,
  `golds` int(4) unsigned NOT NULL DEFAULT '0' COMMENT '当前金币数',
  `maxgolds` int(4) unsigned NOT NULL DEFAULT '0',
  `buytimes` int(4) unsigned NOT NULL DEFAULT '0' COMMENT '兑换次数',
  `drawtimes` int(4) unsigned NOT NULL DEFAULT '0' COMMENT '抽奖次数',
  `pclose` tinyint(1) NOT NULL DEFAULT '0' COMMENT '永久关闭彩蛋:０否,１是;',
  `lighted` tinyint(1) NOT NULL DEFAULT '0' COMMENT '点亮图票:０未,１已点亮',
  `source` char(10) DEFAULT NULL COMMENT '上次来路 toolbar,web,im,nav etc',
  `daily_date` date NOT NULL COMMENT '完成日常任务的日期,任务按此字段进行bit重置;',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '０默认值,供扩展使用',
  PRIMARY KEY (`qq`),
  UNIQUE KEY `qq` (`qq`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users_orig`
--

LOCK TABLES `users_orig` WRITE;
/*!40000 ALTER TABLE `users_orig` DISABLE KEYS */;
INSERT INTO `users_orig` VALUES (123456,100,500,0,0,0,0,NULL,'2012-02-28',0),(223456,101,500,0,0,0,0,'hao','2012-02-28',0),(323456,102,500,0,0,0,0,'hao','2012-02-28',0),(423456,103,500,0,0,0,0,'hao','2012-02-28',0),(523456,104,500,0,0,0,0,'hao','2012-02-28',0),(623456,105,500,0,0,0,0,'hao','2012-02-28',0),(723456,106,500,0,0,0,0,'hao','2012-02-28',0),(823456,107,500,0,0,0,0,'hao','2012-02-28',0),(923456,108,500,0,0,0,0,'hao','2012-02-28',0),(103456,100,500,0,0,0,0,'hao','2012-02-28',0);
/*!40000 ALTER TABLE `users_orig` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2012-03-19 16:25:42
