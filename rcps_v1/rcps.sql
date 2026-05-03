-- MySQL dump 10.13  Distrib 8.0.38, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: helper
-- ------------------------------------------------------
-- Server version	8.0.30

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `activities`
--

DROP TABLE IF EXISTS `activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activities` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activities`
--

LOCK TABLES `activities` WRITE;
/*!40000 ALTER TABLE `activities` DISABLE KEYS */;
INSERT INTO `activities` VALUES (1,'Programming','Programming related activities','2025-04-20 07:15:17','2025-04-20 07:15:17',NULL),(2,'Testing','Testing related activities','2025-04-20 07:15:17','2025-04-20 07:15:17',NULL),(3,'Learning','Activities related to learning and training','2025-04-20 07:15:17','2025-04-20 07:15:17',NULL),(4,'Research','Activities related to research','2025-04-20 07:15:17','2025-04-20 07:15:17',NULL),(5,'Other','Other activities','2025-04-20 07:15:17','2025-04-20 07:15:17',NULL);
/*!40000 ALTER TABLE `activities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `epics`
--

DROP TABLE IF EXISTS `epics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `epics` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `starts_at` date NOT NULL,
  `ends_at` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `parent_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `epics_project_id_foreign` (`project_id`),
  KEY `epics_parent_id_foreign` (`parent_id`),
  CONSTRAINT `epics_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `epics` (`id`),
  CONSTRAINT `epics_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `epics`
--

LOCK TABLES `epics` WRITE;
/*!40000 ALTER TABLE `epics` DISABLE KEYS */;
INSERT INTO `epics` VALUES (1,1,'Laying the Foundation','2025-04-22','2025-05-05','2025-04-20 07:31:20','2025-04-20 07:50:46','2025-04-20 07:50:46',NULL),(2,1,'Sprint 2: Meet the Employees','2025-05-06','2025-05-19','2025-04-20 07:32:05','2025-04-20 07:50:58',NULL,NULL),(3,1,'Sprint 1: Laying the Foundation	','2025-04-22','2025-05-05','2025-04-20 07:42:10','2025-04-20 07:42:10',NULL,NULL),(4,1,'Sprint 2','2025-04-22','2025-05-31','2025-04-22 07:05:34','2025-04-22 07:05:34',NULL,NULL);
/*!40000 ALTER TABLE `epics` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
INSERT INTO `failed_jobs` VALUES (1,'df7a4917-4fec-4073-ad9e-45e3c6118e0c','database','default','{\"uuid\":\"df7a4917-4fec-4073-ad9e-45e3c6118e0c\",\"displayName\":\"App\\\\Notifications\\\\TicketStatusUpdated\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Notifications\\\\SendQueuedNotifications\",\"command\":\"O:48:\\\"Illuminate\\\\Notifications\\\\SendQueuedNotifications\\\":3:{s:11:\\\"notifiables\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:15:\\\"App\\\\Models\\\\User\\\";s:2:\\\"id\\\";a:1:{i:0;i:1;}s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:12:\\\"notification\\\";O:37:\\\"App\\\\Notifications\\\\TicketStatusUpdated\\\":3:{s:45:\\\"\\u0000App\\\\Notifications\\\\TicketStatusUpdated\\u0000ticket\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:17:\\\"App\\\\Models\\\\Ticket\\\";s:2:\\\"id\\\";i:4;s:9:\\\"relations\\\";a:9:{i:0;s:6:\\\"status\\\";i:1;s:9:\\\"relations\\\";i:2;s:18:\\\"relations.relation\\\";i:3;s:8:\\\"timeLogs\\\";i:4;s:7:\\\"project\\\";i:5;s:13:\\\"project.users\\\";i:6;s:5:\\\"owner\\\";i:7;s:11:\\\"responsible\\\";i:8;s:10:\\\"activities\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:47:\\\"\\u0000App\\\\Notifications\\\\TicketStatusUpdated\\u0000activity\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:25:\\\"App\\\\Models\\\\TicketActivity\\\";s:2:\\\"id\\\";i:65;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"id\\\";s:36:\\\"e048e1a3-9b30-4ac5-ad66-3ed8cb9ebff7\\\";}s:8:\\\"channels\\\";a:1:{i:0;s:4:\\\"mail\\\";}}\"}}','Illuminate\\Queue\\MaxAttemptsExceededException: App\\Notifications\\TicketStatusUpdated has been attempted too many times or run too long. The job may have previously timed out. in E:\\laragon\\www\\rcps_v1\\vendor\\laravel\\framework\\src\\Illuminate\\Queue\\Worker.php:746\nStack trace:\n#0 E:\\laragon\\www\\rcps_v1\\vendor\\laravel\\framework\\src\\Illuminate\\Queue\\Worker.php(505): Illuminate\\Queue\\Worker->maxAttemptsExceededException(Object(Illuminate\\Queue\\Jobs\\DatabaseJob))\n#1 E:\\laragon\\www\\rcps_v1\\vendor\\laravel\\framework\\src\\Illuminate\\Queue\\Worker.php(415): Illuminate\\Queue\\Worker->markJobAsFailedIfAlreadyExceedsMaxAttempts(\'database\', Object(Illuminate\\Queue\\Jobs\\DatabaseJob), 1)\n#2 E:\\laragon\\www\\rcps_v1\\vendor\\laravel\\framework\\src\\Illuminate\\Queue\\Worker.php(375): Illuminate\\Queue\\Worker->process(\'database\', Object(Illuminate\\Queue\\Jobs\\DatabaseJob), Object(Illuminate\\Queue\\WorkerOptions))\n#3 E:\\laragon\\www\\rcps_v1\\vendor\\laravel\\framework\\src\\Illuminate\\Queue\\Worker.php(173): Illuminate\\Queue\\Worker->runJob(Object(Illuminate\\Queue\\Jobs\\DatabaseJob), \'database\', Object(Illuminate\\Queue\\WorkerOptions))\n#4 E:\\laragon\\www\\rcps_v1\\vendor\\laravel\\framework\\src\\Illuminate\\Queue\\Console\\WorkCommand.php(147): Illuminate\\Queue\\Worker->daemon(\'database\', \'default\', Object(Illuminate\\Queue\\WorkerOptions))\n#5 E:\\laragon\\www\\rcps_v1\\vendor\\laravel\\framework\\src\\Illuminate\\Queue\\Console\\WorkCommand.php(130): Illuminate\\Queue\\Console\\WorkCommand->runWorker(\'database\', \'default\')\n#6 E:\\laragon\\www\\rcps_v1\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\BoundMethod.php(36): Illuminate\\Queue\\Console\\WorkCommand->handle()\n#7 E:\\laragon\\www\\rcps_v1\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\Util.php(41): Illuminate\\Container\\BoundMethod::Illuminate\\Container\\{closure}()\n#8 E:\\laragon\\www\\rcps_v1\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\BoundMethod.php(93): Illuminate\\Container\\Util::unwrapIfClosure(Object(Closure))\n#9 E:\\laragon\\www\\rcps_v1\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\BoundMethod.php(37): Illuminate\\Container\\BoundMethod::callBoundMethod(Object(Illuminate\\Foundation\\Application), Array, Object(Closure))\n#10 E:\\laragon\\www\\rcps_v1\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\Container.php(651): Illuminate\\Container\\BoundMethod::call(Object(Illuminate\\Foundation\\Application), Array, Array, NULL)\n#11 E:\\laragon\\www\\rcps_v1\\vendor\\laravel\\framework\\src\\Illuminate\\Console\\Command.php(182): Illuminate\\Container\\Container->call(Array)\n#12 E:\\laragon\\www\\rcps_v1\\vendor\\symfony\\console\\Command\\Command.php(312): Illuminate\\Console\\Command->execute(Object(Symfony\\Component\\Console\\Input\\ArgvInput), Object(Illuminate\\Console\\OutputStyle))\n#13 E:\\laragon\\www\\rcps_v1\\vendor\\laravel\\framework\\src\\Illuminate\\Console\\Command.php(152): Symfony\\Component\\Console\\Command\\Command->run(Object(Symfony\\Component\\Console\\Input\\ArgvInput), Object(Illuminate\\Console\\OutputStyle))\n#14 E:\\laragon\\www\\rcps_v1\\vendor\\symfony\\console\\Application.php(1022): Illuminate\\Console\\Command->run(Object(Symfony\\Component\\Console\\Input\\ArgvInput), Object(Symfony\\Component\\Console\\Output\\ConsoleOutput))\n#15 E:\\laragon\\www\\rcps_v1\\vendor\\symfony\\console\\Application.php(314): Symfony\\Component\\Console\\Application->doRunCommand(Object(Illuminate\\Queue\\Console\\WorkCommand), Object(Symfony\\Component\\Console\\Input\\ArgvInput), Object(Symfony\\Component\\Console\\Output\\ConsoleOutput))\n#16 E:\\laragon\\www\\rcps_v1\\vendor\\symfony\\console\\Application.php(168): Symfony\\Component\\Console\\Application->doRun(Object(Symfony\\Component\\Console\\Input\\ArgvInput), Object(Symfony\\Component\\Console\\Output\\ConsoleOutput))\n#17 E:\\laragon\\www\\rcps_v1\\vendor\\laravel\\framework\\src\\Illuminate\\Console\\Application.php(102): Symfony\\Component\\Console\\Application->run(Object(Symfony\\Component\\Console\\Input\\ArgvInput), Object(Symfony\\Component\\Console\\Output\\ConsoleOutput))\n#18 E:\\laragon\\www\\rcps_v1\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Console\\Kernel.php(155): Illuminate\\Console\\Application->run(Object(Symfony\\Component\\Console\\Input\\ArgvInput), Object(Symfony\\Component\\Console\\Output\\ConsoleOutput))\n#19 E:\\laragon\\www\\rcps_v1\\artisan(37): Illuminate\\Foundation\\Console\\Kernel->handle(Object(Symfony\\Component\\Console\\Input\\ArgvInput), Object(Symfony\\Component\\Console\\Output\\ConsoleOutput))\n#20 {main}','2025-05-14 07:10:50'),(2,'b1bd2d0a-1d89-4d56-bfbb-406039f17012','database','default','{\"uuid\":\"b1bd2d0a-1d89-4d56-bfbb-406039f17012\",\"displayName\":\"App\\\\Notifications\\\\TicketStatusUpdated\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Notifications\\\\SendQueuedNotifications\",\"command\":\"O:48:\\\"Illuminate\\\\Notifications\\\\SendQueuedNotifications\\\":3:{s:11:\\\"notifiables\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:15:\\\"App\\\\Models\\\\User\\\";s:2:\\\"id\\\";a:1:{i:0;i:2;}s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:12:\\\"notification\\\";O:37:\\\"App\\\\Notifications\\\\TicketStatusUpdated\\\":3:{s:45:\\\"\\u0000App\\\\Notifications\\\\TicketStatusUpdated\\u0000ticket\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:17:\\\"App\\\\Models\\\\Ticket\\\";s:2:\\\"id\\\";i:5;s:9:\\\"relations\\\";a:8:{i:0;s:6:\\\"status\\\";i:1;s:9:\\\"relations\\\";i:2;s:8:\\\"timeLogs\\\";i:3;s:7:\\\"project\\\";i:4;s:13:\\\"project.users\\\";i:5;s:5:\\\"owner\\\";i:6;s:11:\\\"responsible\\\";i:7;s:10:\\\"activities\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:47:\\\"\\u0000App\\\\Notifications\\\\TicketStatusUpdated\\u0000activity\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:25:\\\"App\\\\Models\\\\TicketActivity\\\";s:2:\\\"id\\\";i:111;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"id\\\";s:36:\\\"3cd2dc9e-1e9f-4e37-91b3-719f5b1aee2f\\\";}s:8:\\\"channels\\\";a:1:{i:0;s:4:\\\"mail\\\";}}\"}}','Symfony\\Component\\Mailer\\Exception\\TransportException: Expected response code \"250\" but got code \"421\", with message \"421 4.4.2 smtp.hostinger.com Error: timeout exceeded\". in E:\\laragon\\www\\rcps_v1\\vendor\\symfony\\mailer\\Transport\\Smtp\\SmtpTransport.php:337\nStack trace:\n#0 E:\\laragon\\www\\rcps_v1\\vendor\\symfony\\mailer\\Transport\\Smtp\\SmtpTransport.php(201): Symfony\\Component\\Mailer\\Transport\\Smtp\\SmtpTransport->assertResponseCode(\'421 4.4.2 smtp....\', Array)\n#1 E:\\laragon\\www\\rcps_v1\\vendor\\symfony\\mailer\\Transport\\Smtp\\EsmtpTransport.php(105): Symfony\\Component\\Mailer\\Transport\\Smtp\\SmtpTransport->executeCommand(\'MAIL FROM:<hell...\', Array)\n#2 E:\\laragon\\www\\rcps_v1\\vendor\\symfony\\mailer\\Transport\\Smtp\\SmtpTransport.php(258): Symfony\\Component\\Mailer\\Transport\\Smtp\\EsmtpTransport->executeCommand(\'MAIL FROM:<hell...\', Array)\n#3 E:\\laragon\\www\\rcps_v1\\vendor\\symfony\\mailer\\Transport\\Smtp\\SmtpTransport.php(218): Symfony\\Component\\Mailer\\Transport\\Smtp\\SmtpTransport->doMailFromCommand(\'hello@lorenzown...\')\n#4 E:\\laragon\\www\\rcps_v1\\vendor\\symfony\\mailer\\Transport\\AbstractTransport.php(69): Symfony\\Component\\Mailer\\Transport\\Smtp\\SmtpTransport->doSend(Object(Symfony\\Component\\Mailer\\SentMessage))\n#5 E:\\laragon\\www\\rcps_v1\\vendor\\symfony\\mailer\\Transport\\Smtp\\SmtpTransport.php(137): Symfony\\Component\\Mailer\\Transport\\AbstractTransport->send(Object(Symfony\\Component\\Mime\\Email), Object(Symfony\\Component\\Mailer\\DelayedEnvelope))\n#6 E:\\laragon\\www\\rcps_v1\\vendor\\laravel\\framework\\src\\Illuminate\\Mail\\Mailer.php(521): Symfony\\Component\\Mailer\\Transport\\Smtp\\SmtpTransport->send(Object(Symfony\\Component\\Mime\\Email), Object(Symfony\\Component\\Mailer\\DelayedEnvelope))\n#7 E:\\laragon\\www\\rcps_v1\\vendor\\laravel\\framework\\src\\Illuminate\\Mail\\Mailer.php(285): Illuminate\\Mail\\Mailer->sendSymfonyMessage(Object(Symfony\\Component\\Mime\\Email))\n#8 E:\\laragon\\www\\rcps_v1\\vendor\\laravel\\framework\\src\\Illuminate\\Notifications\\Channels\\MailChannel.php(67): Illuminate\\Mail\\Mailer->send(Object(Illuminate\\Support\\HtmlString), Array, Object(Closure))\n#9 E:\\laragon\\www\\rcps_v1\\vendor\\laravel\\framework\\src\\Illuminate\\Notifications\\NotificationSender.php(148): Illuminate\\Notifications\\Channels\\MailChannel->send(Object(App\\Models\\User), Object(App\\Notifications\\TicketStatusUpdated))\n#10 E:\\laragon\\www\\rcps_v1\\vendor\\laravel\\framework\\src\\Illuminate\\Notifications\\NotificationSender.php(106): Illuminate\\Notifications\\NotificationSender->sendToNotifiable(Object(App\\Models\\User), \'ead8a558-c333-4...\', Object(App\\Notifications\\TicketStatusUpdated), \'mail\')\n#11 E:\\laragon\\www\\rcps_v1\\vendor\\laravel\\framework\\src\\Illuminate\\Support\\Traits\\Localizable.php(19): Illuminate\\Notifications\\NotificationSender->Illuminate\\Notifications\\{closure}()\n#12 E:\\laragon\\www\\rcps_v1\\vendor\\laravel\\framework\\src\\Illuminate\\Notifications\\NotificationSender.php(109): Illuminate\\Notifications\\NotificationSender->withLocale(NULL, Object(Closure))\n#13 E:\\laragon\\www\\rcps_v1\\vendor\\laravel\\framework\\src\\Illuminate\\Notifications\\ChannelManager.php(54): Illuminate\\Notifications\\NotificationSender->sendNow(Object(Illuminate\\Database\\Eloquent\\Collection), Object(App\\Notifications\\TicketStatusUpdated), Array)\n#14 E:\\laragon\\www\\rcps_v1\\vendor\\laravel\\framework\\src\\Illuminate\\Notifications\\SendQueuedNotifications.php(112): Illuminate\\Notifications\\ChannelManager->sendNow(Object(Illuminate\\Database\\Eloquent\\Collection), Object(App\\Notifications\\TicketStatusUpdated), Array)\n#15 E:\\laragon\\www\\rcps_v1\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\BoundMethod.php(36): Illuminate\\Notifications\\SendQueuedNotifications->handle(Object(Illuminate\\Notifications\\ChannelManager))\n#16 E:\\laragon\\www\\rcps_v1\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\Util.php(41): Illuminate\\Container\\BoundMethod::Illuminate\\Container\\{closure}()\n#17 E:\\laragon\\www\\rcps_v1\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\BoundMethod.php(93): Illuminate\\Container\\Util::unwrapIfClosure(Object(Closure))\n#18 E:\\laragon\\www\\rcps_v1\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\BoundMethod.php(37): Illuminate\\Container\\BoundMethod::callBoundMethod(Object(Illuminate\\Foundation\\Application), Array, Object(Closure))\n#19 E:\\laragon\\www\\rcps_v1\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\Container.php(651): Illuminate\\Container\\BoundMethod::call(Object(Illuminate\\Foundation\\Application), Array, Array, NULL)\n#20 E:\\laragon\\www\\rcps_v1\\vendor\\laravel\\framework\\src\\Illuminate\\Bus\\Dispatcher.php(128): Illuminate\\Container\\Container->call(Array)\n#21 E:\\laragon\\www\\rcps_v1\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(141): Illuminate\\Bus\\Dispatcher->Illuminate\\Bus\\{closure}(Object(Illuminate\\Notifications\\SendQueuedNotifications))\n#22 E:\\laragon\\www\\rcps_v1\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(116): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Notifications\\SendQueuedNotifications))\n#23 E:\\laragon\\www\\rcps_v1\\vendor\\laravel\\framework\\src\\Illuminate\\Bus\\Dispatcher.php(132): Illuminate\\Pipeline\\Pipeline->then(Object(Closure))\n#24 E:\\laragon\\www\\rcps_v1\\vendor\\laravel\\framework\\src\\Illuminate\\Queue\\CallQueuedHandler.php(124): Illuminate\\Bus\\Dispatcher->dispatchNow(Object(Illuminate\\Notifications\\SendQueuedNotifications), false)\n#25 E:\\laragon\\www\\rcps_v1\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(141): Illuminate\\Queue\\CallQueuedHandler->Illuminate\\Queue\\{closure}(Object(Illuminate\\Notifications\\SendQueuedNotifications))\n#26 E:\\laragon\\www\\rcps_v1\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(116): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Notifications\\SendQueuedNotifications))\n#27 E:\\laragon\\www\\rcps_v1\\vendor\\laravel\\framework\\src\\Illuminate\\Queue\\CallQueuedHandler.php(126): Illuminate\\Pipeline\\Pipeline->then(Object(Closure))\n#28 E:\\laragon\\www\\rcps_v1\\vendor\\laravel\\framework\\src\\Illuminate\\Queue\\CallQueuedHandler.php(70): Illuminate\\Queue\\CallQueuedHandler->dispatchThroughMiddleware(Object(Illuminate\\Queue\\Jobs\\DatabaseJob), Object(Illuminate\\Notifications\\SendQueuedNotifications))\n#29 E:\\laragon\\www\\rcps_v1\\vendor\\laravel\\framework\\src\\Illuminate\\Queue\\Jobs\\Job.php(98): Illuminate\\Queue\\CallQueuedHandler->call(Object(Illuminate\\Queue\\Jobs\\DatabaseJob), Array)\n#30 E:\\laragon\\www\\rcps_v1\\vendor\\laravel\\framework\\src\\Illuminate\\Queue\\Worker.php(425): Illuminate\\Queue\\Jobs\\Job->fire()\n#31 E:\\laragon\\www\\rcps_v1\\vendor\\laravel\\framework\\src\\Illuminate\\Queue\\Worker.php(375): Illuminate\\Queue\\Worker->process(\'database\', Object(Illuminate\\Queue\\Jobs\\DatabaseJob), Object(Illuminate\\Queue\\WorkerOptions))\n#32 E:\\laragon\\www\\rcps_v1\\vendor\\laravel\\framework\\src\\Illuminate\\Queue\\Worker.php(173): Illuminate\\Queue\\Worker->runJob(Object(Illuminate\\Queue\\Jobs\\DatabaseJob), \'database\', Object(Illuminate\\Queue\\WorkerOptions))\n#33 E:\\laragon\\www\\rcps_v1\\vendor\\laravel\\framework\\src\\Illuminate\\Queue\\Console\\WorkCommand.php(147): Illuminate\\Queue\\Worker->daemon(\'database\', \'default\', Object(Illuminate\\Queue\\WorkerOptions))\n#34 E:\\laragon\\www\\rcps_v1\\vendor\\laravel\\framework\\src\\Illuminate\\Queue\\Console\\WorkCommand.php(130): Illuminate\\Queue\\Console\\WorkCommand->runWorker(\'database\', \'default\')\n#35 E:\\laragon\\www\\rcps_v1\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\BoundMethod.php(36): Illuminate\\Queue\\Console\\WorkCommand->handle()\n#36 E:\\laragon\\www\\rcps_v1\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\Util.php(41): Illuminate\\Container\\BoundMethod::Illuminate\\Container\\{closure}()\n#37 E:\\laragon\\www\\rcps_v1\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\BoundMethod.php(93): Illuminate\\Container\\Util::unwrapIfClosure(Object(Closure))\n#38 E:\\laragon\\www\\rcps_v1\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\BoundMethod.php(37): Illuminate\\Container\\BoundMethod::callBoundMethod(Object(Illuminate\\Foundation\\Application), Array, Object(Closure))\n#39 E:\\laragon\\www\\rcps_v1\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\Container.php(651): Illuminate\\Container\\BoundMethod::call(Object(Illuminate\\Foundation\\Application), Array, Array, NULL)\n#40 E:\\laragon\\www\\rcps_v1\\vendor\\laravel\\framework\\src\\Illuminate\\Console\\Command.php(182): Illuminate\\Container\\Container->call(Array)\n#41 E:\\laragon\\www\\rcps_v1\\vendor\\symfony\\console\\Command\\Command.php(312): Illuminate\\Console\\Command->execute(Object(Symfony\\Component\\Console\\Input\\ArgvInput), Object(Illuminate\\Console\\OutputStyle))\n#42 E:\\laragon\\www\\rcps_v1\\vendor\\laravel\\framework\\src\\Illuminate\\Console\\Command.php(152): Symfony\\Component\\Console\\Command\\Command->run(Object(Symfony\\Component\\Console\\Input\\ArgvInput), Object(Illuminate\\Console\\OutputStyle))\n#43 E:\\laragon\\www\\rcps_v1\\vendor\\symfony\\console\\Application.php(1022): Illuminate\\Console\\Command->run(Object(Symfony\\Component\\Console\\Input\\ArgvInput), Object(Symfony\\Component\\Console\\Output\\ConsoleOutput))\n#44 E:\\laragon\\www\\rcps_v1\\vendor\\symfony\\console\\Application.php(314): Symfony\\Component\\Console\\Application->doRunCommand(Object(Illuminate\\Queue\\Console\\WorkCommand), Object(Symfony\\Component\\Console\\Input\\ArgvInput), Object(Symfony\\Component\\Console\\Output\\ConsoleOutput))\n#45 E:\\laragon\\www\\rcps_v1\\vendor\\symfony\\console\\Application.php(168): Symfony\\Component\\Console\\Application->doRun(Object(Symfony\\Component\\Console\\Input\\ArgvInput), Object(Symfony\\Component\\Console\\Output\\ConsoleOutput))\n#46 E:\\laragon\\www\\rcps_v1\\vendor\\laravel\\framework\\src\\Illuminate\\Console\\Application.php(102): Symfony\\Component\\Console\\Application->run(Object(Symfony\\Component\\Console\\Input\\ArgvInput), Object(Symfony\\Component\\Console\\Output\\ConsoleOutput))\n#47 E:\\laragon\\www\\rcps_v1\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Console\\Kernel.php(155): Illuminate\\Console\\Application->run(Object(Symfony\\Component\\Console\\Input\\ArgvInput), Object(Symfony\\Component\\Console\\Output\\ConsoleOutput))\n#48 E:\\laragon\\www\\rcps_v1\\artisan(37): Illuminate\\Foundation\\Console\\Kernel->handle(Object(Symfony\\Component\\Console\\Input\\ArgvInput), Object(Symfony\\Component\\Console\\Output\\ConsoleOutput))\n#49 {main}','2025-05-14 07:16:37');
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB AUTO_INCREMENT=487 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `media`
--

DROP TABLE IF EXISTS `media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `media` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  `uuid` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `collection_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `disk` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `conversions_disk` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `size` bigint unsigned NOT NULL,
  `manipulations` json NOT NULL,
  `custom_properties` json NOT NULL,
  `generated_conversions` json NOT NULL,
  `responsive_images` json NOT NULL,
  `order_column` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `media_uuid_unique` (`uuid`),
  KEY `media_model_type_model_id_index` (`model_type`,`model_id`),
  KEY `media_order_column_index` (`order_column`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `media`
--

LOCK TABLES `media` WRITE;
/*!40000 ALTER TABLE `media` DISABLE KEYS */;
/*!40000 ALTER TABLE `media` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=62 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'2014_10_12_000000_create_users_table',1),(2,'2014_10_12_100000_create_password_resets_table',1),(3,'2019_08_19_000000_create_failed_jobs_table',1),(4,'2019_12_14_000001_create_personal_access_tokens_table',1),(5,'2022_11_02_111430_add_two_factor_columns_to_table',1),(6,'2022_11_02_113007_create_permission_tables',1),(7,'2022_11_02_124027_create_project_statuses_table',1),(8,'2022_11_02_124028_create_projects_table',1),(9,'2022_11_02_131753_create_project_users_table',1),(10,'2022_11_02_134510_create_media_table',1),(11,'2022_11_02_152359_create_project_favorites_table',1),(12,'2022_11_02_193241_create_ticket_statuses_table',1),(13,'2022_11_02_193242_create_tickets_table',1),(14,'2022_11_06_155109_add_tickets_prefix_to_projects',1),(15,'2022_11_06_163226_add_code_to_tickets',1),(16,'2022_11_06_164004_create_ticket_types_table',1),(17,'2022_11_06_165400_add_type_to_ticket',1),(18,'2022_11_06_173220_add_order_to_tickets',1),(19,'2022_11_06_184448_add_order_to_ticket_statuses',1),(20,'2022_11_06_193051_create_ticket_activities_table',1),(21,'2022_11_06_194000_create_ticket_priorities_table',1),(22,'2022_11_06_194728_add_priority_to_tickets',1),(23,'2022_11_06_203702_add_status_type_to_project',1),(24,'2022_11_06_204227_add_project_to_ticket_statuses',1),(25,'2022_11_07_064347_create_ticket_comments_table',1),(26,'2022_11_08_084509_create_ticket_subscribers_table',1),(27,'2022_11_08_144611_create_notifications_table',1),(28,'2022_11_08_150309_create_jobs_table',1),(29,'2022_11_08_163244_create_ticket_relations_table',1),(30,'2022_11_08_172846_create_settings_table',1),(31,'2022_11_08_173004_general_settings',1),(32,'2022_11_08_173852_create_general_settings',1),(33,'2022_11_09_085506_create_socialite_users_table',1),(34,'2022_11_09_085638_make_user_password_nullable',1),(35,'2022_11_09_110740_remove_unique_from_users',1),(36,'2022_11_09_110955_add_soft_deletes_to_users',1),(37,'2022_11_09_173852_add_social_login_to_general_settings',1),(38,'2022_11_10_193214_create_ticket_hours_table',1),(39,'2022_11_10_200608_add_estimation_to_tickets',1),(40,'2022_11_12_134201_add_creation_token_to_users',1),(41,'2022_11_12_142644_create_pending_user_emails_table',1),(42,'2022_11_12_173852_add_default_role_to_general_settings',1),(43,'2022_11_12_173852_add_login_form_oidc_enabled_flags_to_general_settings',1),(44,'2022_11_12_173852_add_site_language_to_general_settings',1),(45,'2022_12_15_100852_create_epics_table',1),(46,'2022_12_15_101035_add_epic_to_ticket',1),(47,'2022_12_16_133836_add_parent_to_epics',1),(48,'2022_12_27_082239_add_comment_to_ticket_hours',1),(49,'2023_01_05_182946_add_attachments_to_tickets',1),(50,'2023_01_09_113159_create_activities_table',1),(51,'2023_01_09_113847_add_activity_to_ticket_hours_table',1),(52,'2023_01_12_203211_remove_unique_constraint_from_users',1),(53,'2023_01_12_204221_drop_attachments',1),(54,'2023_01_15_201358_add_type_to_projects',1),(55,'2023_01_15_202225_create_sprints_table',1),(56,'2023_01_15_204606_add_sprint_to_tickets',1),(57,'2023_01_15_214849_add_epic_to_sprints',1),(58,'2023_01_16_085329_add_started_ended_at_to_sprints',1),(59,'2023_01_24_084637_update_users_for_oidc',1),(60,'2023_04_10_123922_add_unique_ticket_prefix_to_projects_table',1),(61,'2025_05_03_171310_create_time_logs',2);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model_has_permissions`
--

DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_permissions`
--

LOCK TABLES `model_has_permissions` WRITE;
/*!40000 ALTER TABLE `model_has_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `model_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model_has_roles`
--

DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_roles`
--

LOCK TABLES `model_has_roles` WRITE;
/*!40000 ALTER TABLE `model_has_roles` DISABLE KEYS */;
INSERT INTO `model_has_roles` VALUES (2,'App\\Models\\User',1),(1,'App\\Models\\User',2),(1,'App\\Models\\User',3),(3,'App\\Models\\User',4),(1,'App\\Models\\User',5);
/*!40000 ALTER TABLE `model_has_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_id` bigint unsigned NOT NULL,
  `data` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES ('01181072-2699-493d-9a01-7bf46e0716ac','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-2\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: In progress - New status: Todo\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:11:18','2025-05-14 07:11:18'),('07b91fb5-c52a-4d0f-9386-ccd6f556035c','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-2\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: Todo - New status: In progress\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:02:20','2025-05-14 07:02:20'),('08c92593-e67b-442b-ab79-96b6a9b6563d','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',1,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-3\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: In progress - New status: Done\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:16:42','2025-05-14 07:16:42'),('0a887258-4fef-4091-b269-b6b03ec27ab5','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-3\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: Done - New status: In progress\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 09:16:54','2025-05-14 09:16:54'),('0c704515-3af4-4ac0-ab4e-2469c92edc55','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-1\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: In progress - New status: Done\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:12:09','2025-05-14 07:12:09'),('0f625824-5368-4546-a317-eae69f7d1635','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',4,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-3\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: In progress - New status: Done\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:16:40','2025-05-14 07:16:40'),('17227ed8-cff2-4fb8-9f50-a57a59035709','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-1\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: Done - New status: In progress\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:01:39','2025-05-14 07:01:39'),('172e8ba9-ac6d-4535-aa2b-58ff5892f21d','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-1\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: In progress - New status: Todo\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:01:05','2025-05-14 07:01:05'),('1b352374-2eb5-41f2-8c1f-cc0680c20317','App\\Notifications\\TicketCreated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-1\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Ticket 1\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"New ticket created\",\"format\":\"filament\"}',NULL,'2025-05-14 07:00:53','2025-05-14 07:00:53'),('2128ada5-fa01-4436-a904-ea3ca44f77fb','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-2\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: In progress - New status: Todo\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:11:59','2025-05-14 07:11:59'),('21a6ca06-2e3a-40fa-8f33-e9026908845a','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-2\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: Done - New status: In progress\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:02:10','2025-05-14 07:02:10'),('22c4762a-c251-47d9-ab54-8c7ba7b33da4','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-2\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: Todo - New status: In progress\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:13:33','2025-05-14 07:13:33'),('2334f777-37a9-4b20-9aaf-eaee3ce01c3d','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-2\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: In progress - New status: Done\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:14:09','2025-05-14 07:14:09'),('25f82f10-fa5d-460b-b34c-0203f6e608a3','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-1\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: Done - New status: In progress\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:13:19','2025-05-14 07:13:19'),('272071ac-a040-491e-95cf-6e1d89934565','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-1\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: Todo - New status: In progress\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:01:29','2025-05-14 07:01:29'),('2c2fff53-c76f-4e81-b5a9-8c39acaec7bf','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-2\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: In progress - New status: Done\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:02:07','2025-05-14 07:02:07'),('2cd50fe3-eaf4-4ff1-b8a4-dfad8aaa544a','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-3\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: Todo - New status: In progress\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:14:52','2025-05-14 07:14:52'),('2e3790d5-1faf-473c-8e02-972f21b79f31','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-2\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: Archived - New status: Todo\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:13:54','2025-05-14 07:13:54'),('2e7c17ec-6f11-4320-956c-70455cababd6','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-3\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: In progress - New status: Todo\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:14:41','2025-05-14 07:14:41'),('2ffa65a7-63bd-4f16-8453-9bf5c3c45c58','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-3\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: Todo - New status: Done\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:15:00','2025-05-14 07:15:00'),('33b497e3-a34b-4c21-8309-e51868f836ea','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-1\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: In progress - New status: Done\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:13:01','2025-05-14 07:13:01'),('33e11d85-4f33-4b87-ade8-2272d6a5cf00','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-1\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: Done - New status: Todo\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:01:10','2025-05-14 07:01:10'),('3839ba8a-9c73-42b1-ab5d-d05a33d32157','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-1\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: Todo - New status: In progress\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:01:14','2025-05-14 07:01:14'),('38b632e4-12d8-487f-9df8-64fe905c7cd9','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-2\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: In progress - New status: Done\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:13:35','2025-05-14 07:13:35'),('3a0ab34e-5998-4be4-b417-19344670e1bb','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-1\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: Todo - New status: In progress\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:00:55','2025-05-14 07:00:55'),('3a32e23c-541b-4eb0-ba3e-b001894b3b64','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-2\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: Done - New status: Archived\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:14:48','2025-05-14 07:14:48'),('3a9767ab-6025-4b9e-992d-9de586c40406','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-2\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: Todo - New status: In progress\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:01:17','2025-05-14 07:01:17'),('3bfb45b9-9653-454f-86e0-c56040ce9b8b','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-2\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: In progress - New status: Done\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:02:12','2025-05-14 07:02:12'),('3cd2dc9e-1e9f-4e37-91b3-719f5b1aee2f','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-3\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: In progress - New status: Done\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:16:37','2025-05-14 07:16:37'),('42bfdfd0-6036-47f4-b5d9-9e3f69178c1b','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-1\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: Archived - New status: Review\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:14:07','2025-05-14 07:14:07'),('43ae6065-a644-4d48-864f-cce0a09fdc44','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-1\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: Done - New status: Archived\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:13:38','2025-05-14 07:13:38'),('440e93c6-b23b-4412-b4d1-3fb4054ee39b','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-2\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: Done - New status: Archived\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:13:40','2025-05-14 07:13:40'),('4ec28234-6ab5-47cb-9f2b-d6b17395c1e1','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-1\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: In progress - New status: Done\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:01:46','2025-05-14 07:01:46'),('4f364edf-ab2c-49fa-9ddf-f7db99869dda','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-1\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: In progress - New status: Done\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:02:02','2025-05-14 07:02:02'),('504590df-0082-4878-9f35-de6648686cc2','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-1\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: In progress - New status: Review\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:13:59','2025-05-14 07:13:59'),('524ae38a-39e0-4a96-b34a-a89d9425026a','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-2\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: Todo - New status: In progress\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:02:25','2025-05-14 07:02:25'),('569e1ea9-e506-4815-80df-e571bf4ab55e','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',4,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-3\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: Done - New status: In progress\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 09:16:55','2025-05-14 09:16:55'),('5b7c1afe-16bf-4024-8e7b-fd91cde860d5','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-1\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: In progress - New status: Done\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:13:22','2025-05-14 07:13:22'),('5ceb8adb-ec18-4004-a2a3-53011b59582b','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-2\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: Archived - New status: Done\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:14:45','2025-05-14 07:14:45'),('62e94fe4-177b-4bb1-bf60-393d881691c5','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-2\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: In progress - New status: Done\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:01:48','2025-05-14 07:01:48'),('636ae8b2-e133-4835-a01d-e258330814c3','App\\Notifications\\TicketCreated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-3\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Ticket 3\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"New ticket created\",\"format\":\"filament\"}',NULL,'2025-05-14 07:14:14','2025-05-14 07:14:14'),('63994e58-2d34-484d-9ff4-24c280ff617e','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-3\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: In progress - New status: Todo\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:14:56','2025-05-14 07:14:56'),('650c02bf-638a-48e4-be02-2e0e4800dd31','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-1\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: Archived - New status: Done\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:01:53','2025-05-14 07:01:53'),('69af1014-601c-422b-a564-ae26a2f52356','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-2\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: Done - New status: Todo\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:13:17','2025-05-14 07:13:17'),('6dab53c4-c533-4ba9-91f2-f3117dde6424','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-1\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: Done - New status: In progress\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:12:51','2025-05-14 07:12:51'),('6ed17741-ff47-4013-8a23-9a020e2acb7f','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-2\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: Done - New status: Todo\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:02:17','2025-05-14 07:02:17'),('701dd444-2e63-4bb9-8b9c-4c789045da93','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-2\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: Todo - New status: In progress\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:02:05','2025-05-14 07:02:05'),('709fe34f-df50-4d45-b2e9-9e5caa49e204','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-2\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: Todo - New status: In progress\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:14:04','2025-05-14 07:14:04'),('714bf4c2-d155-4cc7-a0ba-ba999a4ed3f4','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-2\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: In progress - New status: Todo\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:11:39','2025-05-14 07:11:39'),('77417c85-d746-40b4-bab2-042c49b73d95','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-2\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: Todo - New status: In progress\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:01:26','2025-05-14 07:01:26'),('83f9874b-d028-4a0c-8531-ec8208b697d6','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-1\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: In progress - New status: Todo\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:01:21','2025-05-14 07:01:21'),('88500bcd-a49b-4361-a05f-e42d63ee4291','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-2\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: Todo - New status: In progress\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:11:28','2025-05-14 07:11:28'),('89e4adf6-d80d-40b1-9ff1-5e4fd003ae17','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-1\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: Archived - New status: Done\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:13:52','2025-05-14 07:13:52'),('8c7eb56f-70ed-44b9-a6d5-327200afbc25','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-1\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: In progress - New status: Archived\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:13:47','2025-05-14 07:13:47'),('933b02f7-391c-4890-8986-c7fa92d8b86e','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-1\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: Review - New status: Archived\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:14:01','2025-05-14 07:14:01'),('9914b2f3-a71a-43dc-a1ad-969b02157b93','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-1\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: In progress - New status: Done\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:01:31','2025-05-14 07:01:31'),('9ff2cbb7-8429-4d36-b74a-431747879965','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-2\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: Todo - New status: In progress\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:13:12','2025-05-14 07:13:12'),('a2600fb0-deb7-47e5-8bb7-6a3a80c382bc','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-1\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: Todo - New status: Done\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:01:07','2025-05-14 07:01:07'),('a6eca4c4-2462-4130-a0c9-c0fed04bba0a','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-1\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: In progress - New status: Todo\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:01:41','2025-05-14 07:01:41'),('a9b02249-dae1-4ce2-8dda-71b2e1e1cfdb','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-3\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: Todo - New status: In progress\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:15:07','2025-05-14 07:15:07'),('abb53ed3-3bec-4f69-ad09-544e1359c235','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-1\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: Done - New status: Archived\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:01:55','2025-05-14 07:01:55'),('ac4202fd-c4d5-4159-a72e-ddae31ea17a6','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-2\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: In progress - New status: Todo\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:01:19','2025-05-14 07:01:19'),('af512e81-5a1a-43a6-adee-1a65f5042721','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-1\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: Done - New status: In progress\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:02:00','2025-05-14 07:02:00'),('b94ffa20-126b-4645-a4cb-227002a09a66','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-2\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: In progress - New status: Done\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:12:30','2025-05-14 07:12:30'),('b9a52a8c-4e7f-43f8-a2e6-01a672ff8ac4','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-2\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: Todo - New status: In progress\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:11:49','2025-05-14 07:11:49'),('bc79c9cf-53de-424a-966a-b04eaba7adc6','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-1\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: Todo - New status: In progress\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:01:44','2025-05-14 07:01:44'),('bf5be1e3-cdf3-4e7a-9357-da8f89f33294','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-1\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: Done - New status: In progress\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:02:15','2025-05-14 07:02:15'),('c0d20076-4893-4f22-8af5-787bae47c2a1','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-1\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: Todo - New status: In progress\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:01:02','2025-05-14 07:01:02'),('c1ac909c-1151-4d04-887e-7b34f28761ae','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-2\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: In progress - New status: Done\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:13:15','2025-05-14 07:13:15'),('cc9e0201-b352-4afb-ba2e-0859798bb9f4','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-1\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: Done - New status: In progress\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:13:57','2025-05-14 07:13:57'),('ccf2c9fc-12cc-4d38-925c-436bbbd6e8f1','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-2\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: Done - New status: Todo\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:12:40','2025-05-14 07:12:40'),('cd3e0041-0592-4309-9dd8-6c32ca5c5abe','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-2\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: Done - New status: Archived\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:14:11','2025-05-14 07:14:11'),('cdf74764-9f41-4357-b319-3e89e74ccc6b','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-3\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: Todo - New status: In progress\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:14:38','2025-05-14 07:14:38'),('cf9d1939-f1fb-4a2e-a18a-0c2cb41209e0','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',1,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-3\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: Done - New status: In progress\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 09:16:56','2025-05-14 09:16:56'),('d1fab691-d73a-431a-a355-6c915284d278','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-2\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: In progress - New status: Todo\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:02:22','2025-05-14 07:02:22'),('d608129c-ff51-4b37-bca9-ded79539bc8d','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-1\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: Todo - New status: In progress\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:01:50','2025-05-14 07:01:50'),('dce098f9-1145-4f94-9778-2034b2c4c2ed','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-3\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: Done - New status: Todo\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:15:04','2025-05-14 07:15:04'),('e6fcd388-5571-43f7-ae20-7168fb82a5d5','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-2\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: Todo - New status: In progress\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:12:20','2025-05-14 07:12:20'),('f09db12b-04f9-4278-a5d3-32a678c2e33b','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-1\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: Archived - New status: Done\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:01:58','2025-05-14 07:01:58'),('f1287aec-0dc8-474d-b0a4-cffc3ba195e0','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-2\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: Todo - New status: In progress\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:01:24','2025-05-14 07:01:24'),('f435f49f-1dcc-42c4-8ddb-eacb02e136b0','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-1\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: Archived - New status: In progress\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:13:45','2025-05-14 07:13:45'),('f735a2cf-31bd-4ebb-adeb-1873c231196f','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-2\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: Todo - New status: Archived\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:13:50','2025-05-14 07:13:50'),('fb077e16-60dc-44dc-8822-782727adfc2a','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-2\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: Todo - New status: In progress\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:01:36','2025-05-14 07:01:36'),('fb44900b-f1c4-4aa7-aa57-64b1cd391547','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-2\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: Archived - New status: Todo\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:13:43','2025-05-14 07:13:43'),('fdbad283-c062-4025-93a3-e743bc051651','App\\Notifications\\TicketCreated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-2\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Ticket 2\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"New ticket created\",\"format\":\"filament\"}',NULL,'2025-05-14 07:01:12','2025-05-14 07:01:12'),('ff062b69-86f1-4169-8360-6ff655bc0efe','App\\Notifications\\TicketStatusUpdated','App\\Models\\User',2,'{\"actions\":[{\"name\":\"view\",\"color\":null,\"event\":null,\"eventData\":[],\"extraAttributes\":[],\"icon\":\"heroicon-s-eye\",\"iconPosition\":null,\"isOutlined\":false,\"isDisabled\":false,\"label\":\"View\",\"shouldCloseNotification\":false,\"shouldOpenUrlInNewTab\":false,\"size\":null,\"url\":\"http:\\/\\/localhost:8000\\/tickets\\/share\\/111-1\",\"view\":\"notifications::actions.link-action\"}],\"body\":\"Old status: In progress - New status: Todo\",\"duration\":\"persistent\",\"icon\":\"heroicon-o-ticket\",\"iconColor\":\"secondary\",\"title\":\"Ticket status updated\",\"format\":\"filament\"}',NULL,'2025-05-14 07:01:00','2025-05-14 07:01:00');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_resets` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  KEY `password_resets_email_index` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_resets`
--

LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pending_user_emails`
--

DROP TABLE IF EXISTS `pending_user_emails`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pending_user_emails` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pending_user_emails_user_type_user_id_index` (`user_type`,`user_id`),
  KEY `pending_user_emails_email_index` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pending_user_emails`
--

LOCK TABLES `pending_user_emails` WRITE;
/*!40000 ALTER TABLE `pending_user_emails` DISABLE KEYS */;
/*!40000 ALTER TABLE `pending_user_emails` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=60 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES (1,'List permissions','web','2025-04-20 07:15:16','2025-04-20 07:15:16'),(2,'View permission','web','2025-04-20 07:15:16','2025-04-20 07:15:16'),(3,'Create permission','web','2025-04-20 07:15:16','2025-04-20 07:15:16'),(4,'Update permission','web','2025-04-20 07:15:16','2025-04-20 07:15:16'),(5,'Delete permission','web','2025-04-20 07:15:16','2025-04-20 07:15:16'),(6,'List projects','web','2025-04-20 07:15:16','2025-04-20 07:15:16'),(7,'View project','web','2025-04-20 07:15:16','2025-04-20 07:15:16'),(8,'Create project','web','2025-04-20 07:15:16','2025-04-20 07:15:16'),(9,'Update project','web','2025-04-20 07:15:16','2025-04-20 07:15:16'),(10,'Delete project','web','2025-04-20 07:15:16','2025-04-20 07:15:16'),(11,'List project statuses','web','2025-04-20 07:15:16','2025-04-20 07:15:16'),(12,'View project status','web','2025-04-20 07:15:16','2025-04-20 07:15:16'),(16,'List roles','web','2025-04-20 07:15:16','2025-04-20 07:15:16'),(17,'View role','web','2025-04-20 07:15:16','2025-04-20 07:15:16'),(18,'Create role','web','2025-04-20 07:15:16','2025-04-20 07:15:16'),(19,'Update role','web','2025-04-20 07:15:16','2025-04-20 07:15:16'),(20,'Delete role','web','2025-04-20 07:15:16','2025-04-20 07:15:16'),(21,'List tickets','web','2025-04-20 07:15:16','2025-04-20 07:15:16'),(22,'View ticket','web','2025-04-20 07:15:16','2025-04-20 07:15:16'),(23,'Create ticket','web','2025-04-20 07:15:16','2025-04-20 07:15:16'),(24,'Update ticket','web','2025-04-20 07:15:16','2025-04-20 07:15:16'),(25,'Delete ticket','web','2025-04-20 07:15:16','2025-04-20 07:15:16'),(26,'List ticket priorities','web','2025-04-20 07:15:16','2025-04-20 07:15:16'),(27,'View ticket priority','web','2025-04-20 07:15:16','2025-04-20 07:15:16'),(28,'Create ticket priority','web','2025-04-20 07:15:16','2025-04-20 07:15:16'),(29,'Update ticket priority','web','2025-04-20 07:15:16','2025-04-20 07:15:16'),(30,'Delete ticket priority','web','2025-04-20 07:15:16','2025-04-20 07:15:16'),(31,'List ticket statuses','web','2025-04-20 07:15:16','2025-04-20 07:15:16'),(32,'View ticket status','web','2025-04-20 07:15:16','2025-04-20 07:15:16'),(33,'Create ticket status','web','2025-04-20 07:15:16','2025-04-20 07:15:16'),(34,'Update ticket status','web','2025-04-20 07:15:16','2025-04-20 07:15:16'),(35,'Delete ticket status','web','2025-04-20 07:15:16','2025-04-20 07:15:16'),(36,'List ticket types','web','2025-04-20 07:15:16','2025-04-20 07:15:16'),(37,'View ticket type','web','2025-04-20 07:15:16','2025-04-20 07:15:16'),(38,'Create ticket type','web','2025-04-20 07:15:16','2025-04-20 07:15:16'),(39,'Update ticket type','web','2025-04-20 07:15:16','2025-04-20 07:15:16'),(40,'Delete ticket type','web','2025-04-20 07:15:16','2025-04-20 07:15:16'),(41,'List users','web','2025-04-20 07:15:16','2025-04-20 07:15:16'),(42,'View user','web','2025-04-20 07:15:16','2025-04-20 07:15:16'),(43,'Create user','web','2025-04-20 07:15:16','2025-04-20 07:15:16'),(44,'Update user','web','2025-04-20 07:15:16','2025-04-20 07:15:16'),(45,'Delete user','web','2025-04-20 07:15:16','2025-04-20 07:15:16'),(46,'List activities','web','2025-04-20 07:15:16','2025-04-20 07:15:16'),(47,'View activity','web','2025-04-20 07:15:16','2025-04-20 07:15:16'),(48,'Create activity','web','2025-04-20 07:15:16','2025-04-20 07:15:16'),(49,'Update activity','web','2025-04-20 07:15:16','2025-04-20 07:15:16'),(50,'Delete activity','web','2025-04-20 07:15:16','2025-04-20 07:15:16'),(51,'List sprints','web','2025-04-20 07:15:16','2025-04-20 07:15:16'),(52,'View sprint','web','2025-04-20 07:15:16','2025-04-20 07:15:16'),(53,'Create sprint','web','2025-04-20 07:15:16','2025-04-20 07:15:16'),(54,'Update sprint','web','2025-04-20 07:15:16','2025-04-20 07:15:16'),(55,'Delete sprint','web','2025-04-20 07:15:16','2025-04-20 07:15:16'),(56,'Manage general settings','web','2025-04-20 07:15:16','2025-04-20 07:15:16'),(57,'Import from Jira','web','2025-04-20 07:15:16','2025-04-20 07:15:16'),(58,'List timesheet data','web','2025-04-20 07:15:16','2025-04-20 07:15:16'),(59,'View timesheet dashboard','web','2025-04-20 07:15:16','2025-04-20 07:15:16');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_favorites`
--

DROP TABLE IF EXISTS `project_favorites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_favorites` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `project_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `project_favorites_user_id_foreign` (`user_id`),
  KEY `project_favorites_project_id_foreign` (`project_id`),
  CONSTRAINT `project_favorites_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`),
  CONSTRAINT `project_favorites_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_favorites`
--

LOCK TABLES `project_favorites` WRITE;
/*!40000 ALTER TABLE `project_favorites` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_favorites` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_statuses`
--

DROP TABLE IF EXISTS `project_statuses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_statuses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `color` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#cecece',
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_statuses`
--

LOCK TABLES `project_statuses` WRITE;
/*!40000 ALTER TABLE `project_statuses` DISABLE KEYS */;
INSERT INTO `project_statuses` VALUES (1,'Not Started','#9CA3AF',1,NULL,'2025-04-20 07:25:00','2025-04-20 07:33:48'),(2,'In Progress','#3B82F6',0,NULL,'2025-04-20 07:25:52','2025-04-20 07:25:52'),(3,'On Hold','#FACC15',0,NULL,'2025-04-20 07:26:11','2025-04-20 07:26:11'),(4,'Waiting for Approval','#6366F1',0,NULL,'2025-04-20 07:26:27','2025-04-20 07:26:27'),(5,'Completed ','#10B981',0,NULL,'2025-04-20 07:26:37','2025-04-20 07:26:37'),(6,'Cancelled ','#EF4444',0,NULL,'2025-04-20 07:26:50','2025-04-20 07:26:50');
/*!40000 ALTER TABLE `project_statuses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_users`
--

DROP TABLE IF EXISTS `project_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `project_id` bigint unsigned NOT NULL,
  `role` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `project_users_user_id_foreign` (`user_id`),
  KEY `project_users_project_id_foreign` (`project_id`),
  CONSTRAINT `project_users_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`),
  CONSTRAINT `project_users_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_users`
--

LOCK TABLES `project_users` WRITE;
/*!40000 ALTER TABLE `project_users` DISABLE KEYS */;
INSERT INTO `project_users` VALUES (1,1,1,'employee',NULL,NULL),(2,2,3,'employee',NULL,NULL),(3,2,4,'front_end',NULL,NULL),(5,4,4,'front_end',NULL,NULL);
/*!40000 ALTER TABLE `project_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `projects`
--

DROP TABLE IF EXISTS `projects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `projects` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci,
  `owner_id` bigint unsigned NOT NULL,
  `status_id` bigint unsigned NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `ticket_prefix` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'default',
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'kanban',
  PRIMARY KEY (`id`),
  UNIQUE KEY `projects_ticket_prefix_unique` (`ticket_prefix`),
  KEY `projects_owner_id_foreign` (`owner_id`),
  KEY `projects_status_id_foreign` (`status_id`),
  CONSTRAINT `projects_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`),
  CONSTRAINT `projects_status_id_foreign` FOREIGN KEY (`status_id`) REFERENCES `project_statuses` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `projects`
--

LOCK TABLES `projects` WRITE;
/*!40000 ALTER TABLE `projects` DISABLE KEYS */;
INSERT INTO `projects` VALUES (1,'HRIS (Human Resource Information System)','<p>&nbsp;A centralized platform to manage employee information, payroll, leave management, recruitment, and more — aimed at improving HR operations and employee experience&nbsp;</p>',1,2,NULL,'2025-04-20 07:29:14','2025-05-18 01:09:21','HR','default','scrum'),(2,'Project Sample','<p>Sample</p>',2,1,NULL,'2025-04-22 06:53:16','2025-04-22 06:53:16','PS','default','kanban'),(3,'Project Sample 101','<p>sample</p>',1,1,NULL,'2025-04-22 07:04:07','2025-04-22 07:04:07','PR','default','kanban'),(4,'Project 1','<p>dasdasdas</p>',1,1,NULL,'2025-05-02 09:06:40','2025-05-02 09:06:40','111','default','kanban');
/*!40000 ALTER TABLE `projects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_has_permissions`
--

DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_has_permissions`
--

LOCK TABLES `role_has_permissions` WRITE;
/*!40000 ALTER TABLE `role_has_permissions` DISABLE KEYS */;
INSERT INTO `role_has_permissions` VALUES (6,1),(7,1),(8,1),(9,1),(10,1),(21,1),(22,1),(23,1),(24,1),(25,1),(51,1),(52,1),(53,1),(54,1),(55,1),(1,2),(2,2),(3,2),(4,2),(5,2),(6,2),(7,2),(8,2),(9,2),(10,2),(11,2),(12,2),(16,2),(17,2),(18,2),(19,2),(20,2),(21,2),(22,2),(23,2),(24,2),(25,2),(26,2),(27,2),(28,2),(29,2),(30,2),(31,2),(32,2),(33,2),(34,2),(35,2),(36,2),(37,2),(38,2),(39,2),(40,2),(41,2),(42,2),(43,2),(44,2),(45,2),(46,2),(47,2),(48,2),(49,2),(50,2),(51,2),(52,2),(53,2),(54,2),(55,2),(56,2),(57,2),(58,2),(59,2),(6,3),(7,3),(21,3),(22,3),(51,3),(52,3),(58,3),(59,3);
/*!40000 ALTER TABLE `role_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role_type` ENUM('CORE','MANAGER','STAFF') COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'SUPER ADMIN','CORE','web','2025-04-20 07:15:16','2025-04-21 08:30:22'),(2,'Project Manager', 'MANAGER','web','2025-04-21 08:23:29','2025-04-21 08:27:24'),(3,'Front End', 'STAFF', 'web','2025-05-14 06:11:50','2025-05-14 06:11:50');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `group` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `locked` tinyint(1) NOT NULL DEFAULT '0',
  `payload` json NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `settings_group_index` (`group`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES (1,'general','site_name',0,'\"RCPS\"','2025-04-20 07:14:14','2025-04-21 08:32:12'),(2,'general','site_logo',0,'null','2025-04-20 07:14:14','2025-04-21 08:32:12'),(3,'general','enable_registration',0,'false','2025-04-20 07:14:14','2025-04-21 08:32:12'),(4,'general','enable_social_login',0,'\"0\"','2025-04-20 07:14:14','2025-04-21 08:32:12'),(5,'general','default_role',0,'\"2\"','2025-04-20 07:14:14','2025-04-21 08:32:12'),(6,'general','enable_login_form',0,'\"1\"','2025-04-20 07:14:14','2025-04-21 08:32:12'),(7,'general','enable_oidc_login',0,'\"0\"','2025-04-20 07:14:14','2025-04-21 08:32:12'),(8,'general','site_language',0,'\"en\"','2025-04-20 07:14:14','2025-04-21 08:32:12');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `socialite_users`
--

DROP TABLE IF EXISTS `socialite_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `socialite_users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `provider` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `provider_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `socialite_users_provider_provider_id_unique` (`provider`,`provider_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `socialite_users`
--

LOCK TABLES `socialite_users` WRITE;
/*!40000 ALTER TABLE `socialite_users` DISABLE KEYS */;
/*!40000 ALTER TABLE `socialite_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sprints`
--

DROP TABLE IF EXISTS `sprints`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sprints` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `starts_at` date NOT NULL,
  `ends_at` date NOT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci,
  `project_id` bigint unsigned NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `epic_id` bigint unsigned DEFAULT NULL,
  `started_at` datetime DEFAULT NULL,
  `ended_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sprints_project_id_foreign` (`project_id`),
  KEY `sprints_epic_id_foreign` (`epic_id`),
  CONSTRAINT `sprints_epic_id_foreign` FOREIGN KEY (`epic_id`) REFERENCES `epics` (`id`),
  CONSTRAINT `sprints_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sprints`
--

LOCK TABLES `sprints` WRITE;
/*!40000 ALTER TABLE `sprints` DISABLE KEYS */;
INSERT INTO `sprints` VALUES (1,'Sprint 1: Laying the Foundation','2025-04-22','2025-05-05','<p>&nbsp;Set up the project environment, database, basic file structure, and user authentication (login, registration, access control).&nbsp;</p>',1,'2025-04-20 07:41:25','2025-04-20 07:31:20','2025-04-20 07:41:25',1,'2025-04-20 15:39:50','2025-04-20 15:39:54'),(2,'Sprint 2: Meet the Employees','2025-05-06','2025-05-19','<p>&nbsp;Develop the Employee Profile module to manage personal data, job info, contact details, and document uploads.&nbsp;</p>',1,'2025-04-20 07:41:30','2025-04-20 07:32:05','2025-04-20 07:41:30',2,'2025-04-20 15:39:54','2025-04-20 15:40:33'),(3,'Sprint 1: Laying the Foundation	','2025-04-22','2025-05-05','<p>&nbsp;Set up the project environment, database, basic file structure, and user authentication (login, registration, access control).&nbsp;</p>',1,NULL,'2025-04-20 07:42:10','2025-04-20 07:45:11',3,'2025-04-20 15:45:11',NULL),(4,'Sprint 2','2025-04-22','2025-05-31',NULL,1,NULL,'2025-04-22 07:05:34','2025-04-22 07:05:34',4,NULL,NULL);
/*!40000 ALTER TABLE `sprints` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ticket_activities`
--

DROP TABLE IF EXISTS `ticket_activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ticket_activities` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ticket_id` bigint unsigned NOT NULL,
  `old_status_id` bigint unsigned NOT NULL,
  `new_status_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ticket_activities_ticket_id_foreign` (`ticket_id`),
  KEY `ticket_activities_old_status_id_foreign` (`old_status_id`),
  KEY `ticket_activities_new_status_id_foreign` (`new_status_id`),
  KEY `ticket_activities_user_id_foreign` (`user_id`),
  CONSTRAINT `ticket_activities_new_status_id_foreign` FOREIGN KEY (`new_status_id`) REFERENCES `ticket_statuses` (`id`),
  CONSTRAINT `ticket_activities_old_status_id_foreign` FOREIGN KEY (`old_status_id`) REFERENCES `ticket_statuses` (`id`),
  CONSTRAINT `ticket_activities_ticket_id_foreign` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`),
  CONSTRAINT `ticket_activities_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=113 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ticket_activities`
--

LOCK TABLES `ticket_activities` WRITE;
/*!40000 ALTER TABLE `ticket_activities` DISABLE KEYS */;
INSERT INTO `ticket_activities` VALUES (1,1,1,2,1,'2025-04-20 07:45:29','2025-04-20 07:45:29'),(2,1,2,1,1,'2025-04-20 07:45:31','2025-04-20 07:45:31'),(3,3,1,2,1,'2025-05-02 09:12:54','2025-05-02 09:12:54'),(4,3,2,1,1,'2025-05-02 09:12:55','2025-05-02 09:12:55'),(5,3,1,2,1,'2025-05-02 09:13:06','2025-05-02 09:13:06'),(6,3,2,1,1,'2025-05-02 09:13:12','2025-05-02 09:13:12'),(7,3,1,3,1,'2025-05-02 09:15:12','2025-05-02 09:15:12'),(8,3,3,1,1,'2025-05-03 08:04:38','2025-05-03 08:04:38'),(9,3,1,2,1,'2025-05-03 08:13:47','2025-05-03 08:13:47'),(10,4,1,2,1,'2025-05-03 08:13:48','2025-05-03 08:13:48'),(11,4,2,1,1,'2025-05-03 08:13:50','2025-05-03 08:13:50'),(12,3,2,1,1,'2025-05-03 08:13:51','2025-05-03 08:13:51'),(13,4,1,2,1,'2025-05-03 08:22:27','2025-05-03 08:22:27'),(14,4,1,2,1,'2025-05-03 08:30:01','2025-05-03 08:30:01'),(15,3,1,2,1,'2025-05-03 08:57:56','2025-05-03 08:57:56'),(16,3,2,3,1,'2025-05-03 08:58:45','2025-05-03 08:58:45'),(17,4,1,2,1,'2025-05-03 08:58:47','2025-05-03 08:58:47'),(18,3,3,2,1,'2025-05-03 08:58:51','2025-05-03 08:58:51'),(19,3,2,1,1,'2025-05-03 08:59:03','2025-05-03 08:59:03'),(20,3,1,2,1,'2025-05-03 09:08:26','2025-05-03 09:08:26'),(21,3,2,3,1,'2025-05-03 09:09:05','2025-05-03 09:09:05'),(22,4,2,3,1,'2025-05-03 09:09:28','2025-05-03 09:09:28'),(23,3,1,2,1,'2025-05-03 09:30:51','2025-05-03 09:30:51'),(48,3,4,3,1,'2025-05-03 10:14:52','2025-05-03 10:14:52'),(53,3,3,4,1,'2025-05-03 10:18:04','2025-05-03 10:18:04'),(54,3,4,3,1,'2025-05-03 10:18:55','2025-05-03 10:18:55'),(55,3,3,2,1,'2025-05-03 10:19:11','2025-05-03 10:19:11'),(56,3,2,3,1,'2025-05-03 10:19:18','2025-05-03 10:19:18'),(57,4,1,2,1,'2025-05-03 10:24:01','2025-05-03 10:24:01'),(58,4,2,3,1,'2025-05-03 10:24:11','2025-05-03 10:24:11'),(59,4,3,2,1,'2025-05-03 10:53:39','2025-05-03 10:53:39'),(60,4,2,3,1,'2025-05-03 10:57:50','2025-05-03 10:57:50'),(61,3,3,2,1,'2025-05-03 10:58:01','2025-05-03 10:58:01'),(62,4,3,1,1,'2025-05-03 10:58:13','2025-05-03 10:58:13'),(63,4,1,2,1,'2025-05-03 11:00:55','2025-05-03 11:00:55'),(64,4,2,1,1,'2025-05-03 11:07:26','2025-05-03 11:07:26'),(65,4,1,2,1,'2025-05-03 11:07:31','2025-05-03 11:07:31'),(66,4,2,1,1,'2025-05-03 11:09:03','2025-05-03 11:09:03'),(67,4,1,2,1,'2025-05-03 11:09:06','2025-05-03 11:09:06'),(68,4,2,1,1,'2025-05-03 11:10:09','2025-05-03 11:10:09'),(69,4,1,2,1,'2025-05-03 11:10:11','2025-05-03 11:10:11'),(70,4,2,1,1,'2025-05-03 11:10:12','2025-05-03 11:10:12'),(71,3,2,3,1,'2025-05-03 11:12:55','2025-05-03 11:12:55'),(72,4,1,2,1,'2025-05-03 11:12:57','2025-05-03 11:12:57'),(73,4,2,3,1,'2025-05-03 11:13:01','2025-05-03 11:13:01'),(77,4,3,1,1,'2025-05-03 11:20:42','2025-05-03 11:20:42'),(78,3,3,2,1,'2025-05-03 11:20:51','2025-05-03 11:20:51'),(79,3,2,3,1,'2025-05-03 11:22:08','2025-05-03 11:22:08'),(80,4,1,2,1,'2025-05-03 11:22:09','2025-05-03 11:22:09'),(81,4,2,3,1,'2025-05-03 11:22:11','2025-05-03 11:22:11'),(82,4,3,1,1,'2025-05-03 11:22:42','2025-05-03 11:22:42'),(83,3,3,2,1,'2025-05-03 11:22:42','2025-05-03 11:22:42'),(84,3,2,3,1,'2025-05-03 11:22:49','2025-05-03 11:22:49'),(85,4,1,2,1,'2025-05-03 11:22:50','2025-05-03 11:22:50'),(86,4,2,3,1,'2025-05-03 11:22:53','2025-05-03 11:22:53'),(87,3,3,4,1,'2025-05-03 11:22:55','2025-05-03 11:22:55'),(88,4,3,4,1,'2025-05-03 11:22:57','2025-05-03 11:22:57'),(89,4,4,1,1,'2025-05-03 11:23:01','2025-05-03 11:23:01'),(90,3,4,2,1,'2025-05-03 11:23:01','2025-05-03 11:23:01'),(91,3,2,4,1,'2025-05-03 11:23:07','2025-05-03 11:23:07'),(92,4,1,4,1,'2025-05-03 11:23:09','2025-05-03 11:23:09'),(93,3,4,3,1,'2025-05-03 11:23:11','2025-05-03 11:23:11'),(94,4,4,1,1,'2025-05-03 11:23:14','2025-05-03 11:23:14'),(95,3,3,2,1,'2025-05-03 11:23:14','2025-05-03 11:23:14'),(96,3,2,5,1,'2025-05-03 11:23:20','2025-05-03 11:23:20'),(97,3,5,4,1,'2025-05-03 11:23:54','2025-05-03 11:23:54'),(98,4,1,2,1,'2025-05-03 11:23:55','2025-05-03 11:23:55'),(99,3,4,5,1,'2025-05-03 11:23:58','2025-05-03 11:23:58'),(100,4,2,3,1,'2025-05-03 11:24:02','2025-05-03 11:24:02'),(101,4,3,4,1,'2025-05-03 11:24:06','2025-05-03 11:24:06'),(102,5,1,2,4,'2025-05-14 06:17:20','2025-05-14 06:17:20'),(103,5,2,1,4,'2025-05-14 06:17:37','2025-05-14 06:17:37'),(104,4,4,3,4,'2025-05-14 06:18:13','2025-05-14 06:18:13'),(105,4,3,4,4,'2025-05-14 06:18:28','2025-05-14 06:18:28'),(106,5,1,2,1,'2025-05-14 06:59:41','2025-05-14 06:59:41'),(107,5,2,1,1,'2025-05-14 06:59:45','2025-05-14 06:59:45'),(108,5,1,3,1,'2025-05-14 07:00:17','2025-05-14 07:00:17'),(109,5,3,1,1,'2025-05-14 07:00:19','2025-05-14 07:00:19'),(110,5,1,2,1,'2025-05-14 07:11:16','2025-05-14 07:11:16'),(111,5,2,3,1,'2025-05-14 07:16:34','2025-05-14 07:16:34'),(112,5,3,2,1,'2025-05-14 09:16:23','2025-05-14 09:16:23');
/*!40000 ALTER TABLE `ticket_activities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ticket_algorithm_metrics`
--

DROP TABLE IF EXISTS `ticket_algorithm_metrics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ticket_algorithm_metrics` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `ticket_id` bigint unsigned NOT NULL,
  `dependency_mode` int DEFAULT NULL,
  `execution_time` double(8,2) DEFAULT NULL,
  `resource_utilization` double(5,2) DEFAULT NULL,
  `scheduling_accuracy` double(5,2) DEFAULT NULL,
  `metric_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ticket_id` (`ticket_id`),
  CONSTRAINT `ticket_algorithm_metrics_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ticket_algorithm_metrics`
--

LOCK TABLES `ticket_algorithm_metrics` WRITE;
/*!40000 ALTER TABLE `ticket_algorithm_metrics` DISABLE KEYS */;
INSERT INTO `ticket_algorithm_metrics` VALUES (1,1,1,0.85,72.50,88.00,'2025-05-01 02:00:00',NULL),(2,1,2,1.10,68.30,91.20,'2025-04-30 02:01:00',NULL),(3,2,1,0.95,70.10,85.60,'2025-04-30 02:02:00',NULL);
/*!40000 ALTER TABLE `ticket_algorithm_metrics` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ticket_comments`
--

DROP TABLE IF EXISTS `ticket_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ticket_comments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ticket_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ticket_comments_ticket_id_foreign` (`ticket_id`),
  KEY `ticket_comments_user_id_foreign` (`user_id`),
  CONSTRAINT `ticket_comments_ticket_id_foreign` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`),
  CONSTRAINT `ticket_comments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ticket_comments`
--

LOCK TABLES `ticket_comments` WRITE;
/*!40000 ALTER TABLE `ticket_comments` DISABLE KEYS */;
INSERT INTO `ticket_comments` VALUES (1,1,1,'<p>hello</p>',NULL,'2025-04-22 07:07:06','2025-04-22 07:07:06');
/*!40000 ALTER TABLE `ticket_comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ticket_hours`
--

DROP TABLE IF EXISTS `ticket_hours`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ticket_hours` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ticket_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `value` double(8,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `comment` longtext COLLATE utf8mb4_unicode_ci,
  `activity_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ticket_hours_ticket_id_foreign` (`ticket_id`),
  KEY `ticket_hours_user_id_foreign` (`user_id`),
  KEY `ticket_hours_activity_id_foreign` (`activity_id`),
  CONSTRAINT `ticket_hours_activity_id_foreign` FOREIGN KEY (`activity_id`) REFERENCES `activities` (`id`),
  CONSTRAINT `ticket_hours_ticket_id_foreign` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`),
  CONSTRAINT `ticket_hours_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ticket_hours`
--

LOCK TABLES `ticket_hours` WRITE;
/*!40000 ALTER TABLE `ticket_hours` DISABLE KEYS */;
INSERT INTO `ticket_hours` VALUES (1,1,1,2.00,'2025-04-22 07:06:55','2025-04-22 07:06:55','sample',1),(2,2,1,5.00,'2025-05-03 09:37:29','2025-05-03 09:37:29',NULL,1);
/*!40000 ALTER TABLE `ticket_hours` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ticket_priorities`
--

DROP TABLE IF EXISTS `ticket_priorities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ticket_priorities` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `color` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#cecece',
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ticket_priorities`
--

LOCK TABLES `ticket_priorities` WRITE;
/*!40000 ALTER TABLE `ticket_priorities` DISABLE KEYS */;
INSERT INTO `ticket_priorities` VALUES (1,'Low','#008000',0,NULL,'2025-04-20 07:15:17','2025-04-20 07:15:17'),(2,'Normal','#CECECE',1,NULL,'2025-04-20 07:15:17','2025-04-20 07:15:17'),(3,'High','#ff0000',0,NULL,'2025-04-20 07:15:17','2025-04-20 07:15:17');
/*!40000 ALTER TABLE `ticket_priorities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ticket_relations`
--

DROP TABLE IF EXISTS `ticket_relations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ticket_relations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ticket_id` bigint unsigned NOT NULL,
  `relation_id` bigint unsigned NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort` int NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ticket_relations_ticket_id_foreign` (`ticket_id`),
  KEY `ticket_relations_relation_id_foreign` (`relation_id`),
  CONSTRAINT `ticket_relations_relation_id_foreign` FOREIGN KEY (`relation_id`) REFERENCES `tickets` (`id`),
  CONSTRAINT `ticket_relations_ticket_id_foreign` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ticket_relations`
--

LOCK TABLES `ticket_relations` WRITE;
/*!40000 ALTER TABLE `ticket_relations` DISABLE KEYS */;
INSERT INTO `ticket_relations` VALUES (1,4,3,'depends_on',1,'2025-05-03 08:12:54','2025-05-03 08:12:54');
/*!40000 ALTER TABLE `ticket_relations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ticket_statuses`
--

DROP TABLE IF EXISTS `ticket_statuses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ticket_statuses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `color` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#cecece',
  `type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `order` int NOT NULL DEFAULT '1',
  `project_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ticket_statuses_project_id_foreign` (`project_id`),
  CONSTRAINT `ticket_statuses_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ticket_statuses`
--

LOCK TABLES `ticket_statuses` WRITE;
/*!40000 ALTER TABLE `ticket_statuses` DISABLE KEYS */;
INSERT INTO `ticket_statuses` VALUES (1,'Todo','#cecece','pending',1,NULL,'2025-04-20 07:15:17','2025-05-03 10:44:27',1,NULL),(2,'In progress','#ff7f00','active',0,NULL,'2025-04-20 07:15:17','2025-05-03 11:12:02',2,NULL),(3,'Done','#008000','completed',0,NULL,'2025-04-20 07:15:17','2025-05-03 11:12:02',4,NULL),(4,'Archived','#ff0000','completed',0,NULL,'2025-04-20 07:15:17','2025-05-03 11:12:02',5,NULL),(5,'Review','#00ffe8','pending',0,NULL,'2025-05-03 10:43:28','2025-05-03 11:25:31',6,NULL),(6,'Completed','#f7e70f','completed',0,NULL,'2025-05-03 11:25:13','2025-05-03 11:25:23',8,NULL);
/*!40000 ALTER TABLE `ticket_statuses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ticket_subscribers`
--

DROP TABLE IF EXISTS `ticket_subscribers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ticket_subscribers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `ticket_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ticket_subscribers_user_id_foreign` (`user_id`),
  KEY `ticket_subscribers_ticket_id_foreign` (`ticket_id`),
  CONSTRAINT `ticket_subscribers_ticket_id_foreign` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`),
  CONSTRAINT `ticket_subscribers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ticket_subscribers`
--

LOCK TABLES `ticket_subscribers` WRITE;
/*!40000 ALTER TABLE `ticket_subscribers` DISABLE KEYS */;
INSERT INTO `ticket_subscribers` VALUES (3,1,1,'2025-04-22 07:06:23','2025-04-22 07:06:23');
/*!40000 ALTER TABLE `ticket_subscribers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ticket_types`
--

DROP TABLE IF EXISTS `ticket_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ticket_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `icon` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `color` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#cecece',
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ticket_types`
--

LOCK TABLES `ticket_types` WRITE;
/*!40000 ALTER TABLE `ticket_types` DISABLE KEYS */;
INSERT INTO `ticket_types` VALUES (1,'Task','heroicon-o-check-circle','#00FFFF',1,NULL,'2025-04-20 07:15:17','2025-04-20 07:15:17'),(2,'Evolution','heroicon-o-clipboard-list','#008000',0,NULL,'2025-04-20 07:15:17','2025-04-20 07:15:17'),(3,'Bug','heroicon-o-x','#ff0000',0,NULL,'2025-04-20 07:15:17','2025-04-20 07:15:17');
/*!40000 ALTER TABLE `ticket_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tickets`
--

DROP TABLE IF EXISTS `tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tickets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner_id` bigint unsigned NOT NULL,
  `responsible_id` bigint unsigned DEFAULT NULL,
  `status_id` bigint unsigned NOT NULL,
  `project_id` bigint unsigned NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `type_id` bigint unsigned NOT NULL,
  `order` int NOT NULL DEFAULT '0',
  `priority_id` bigint unsigned NOT NULL,
  `estimation` double(8,2) DEFAULT NULL,
  `epic_id` bigint unsigned DEFAULT NULL,
  `sprint_id` bigint unsigned DEFAULT NULL,
  `execution_time` double(8,2) DEFAULT NULL,
  `resource_utilization` double(5,2) DEFAULT NULL,
  `scheduling_accuracy` double(5,2) DEFAULT NULL,
  `dependency_mode` int DEFAULT NULL,
  `metrics_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `tickets_owner_id_foreign` (`owner_id`),
  KEY `tickets_responsible_id_foreign` (`responsible_id`),
  KEY `tickets_status_id_foreign` (`status_id`),
  KEY `tickets_project_id_foreign` (`project_id`),
  KEY `tickets_type_id_foreign` (`type_id`),
  KEY `tickets_priority_id_foreign` (`priority_id`),
  KEY `tickets_epic_id_foreign` (`epic_id`),
  KEY `tickets_sprint_id_foreign` (`sprint_id`),
  CONSTRAINT `tickets_epic_id_foreign` FOREIGN KEY (`epic_id`) REFERENCES `epics` (`id`),
  CONSTRAINT `tickets_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`),
  CONSTRAINT `tickets_priority_id_foreign` FOREIGN KEY (`priority_id`) REFERENCES `ticket_priorities` (`id`),
  CONSTRAINT `tickets_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`),
  CONSTRAINT `tickets_responsible_id_foreign` FOREIGN KEY (`responsible_id`) REFERENCES `users` (`id`),
  CONSTRAINT `tickets_sprint_id_foreign` FOREIGN KEY (`sprint_id`) REFERENCES `sprints` (`id`),
  CONSTRAINT `tickets_status_id_foreign` FOREIGN KEY (`status_id`) REFERENCES `ticket_statuses` (`id`),
  CONSTRAINT `tickets_type_id_foreign` FOREIGN KEY (`type_id`) REFERENCES `ticket_types` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tickets`
--

LOCK TABLES `tickets` WRITE;
/*!40000 ALTER TABLE `tickets` DISABLE KEYS */;
INSERT INTO `tickets` VALUES (1,'Task 1: Initialize project repository on GitHub/GitLab','<p>&nbsp;<strong>Description:</strong> Set up a new GitHub/GitLab repository for the HRIS project. The repository will serve as the central version control system for the project, enabling collaboration and version tracking.&nbsp;</p><p><strong>Acceptance Criteria:</strong>&nbsp;</p><ul><li>A new repository is created on GitHub/GitLab.</li><li>A .gitignore file is added to exclude unnecessary files (e.g., node_modules, vendor, etc.).</li><li>The initial commit is pushed to the repository with the following folder structure:<ul><li>src/ – main project files</li><li>docs/ – project documentation</li><li>README.md – a basic readme file describing the project and setup instructions</li><li>.gitignore – ignore unnecessary files</li><li>LICENSE – add a standard open-source license (e.g., MIT or Apache)</li></ul></li></ul><p>&nbsp;</p>',1,2,1,1,NULL,'2025-04-20 07:38:47','2025-05-02 08:54:00','HR-1',1,0,2,3.00,4,4,NULL,NULL,NULL,1,'2025-04-30 16:16:06'),(2,'Set up development environment (local + staging)','<p>sample</p>',1,1,1,1,NULL,'2025-04-20 07:49:40','2025-04-22 07:05:52','HR-2',1,1,2,2.00,3,4,NULL,NULL,NULL,NULL,'2025-04-30 16:16:06'),(3,'Ticket 1','<p>dasdasd</p>',1,1,5,4,NULL,'2025-05-02 09:12:32','2025-05-03 11:23:58','111-1',1,0,3,10.00,NULL,NULL,7.90,20.75,100.00,2,'2025-05-02 17:12:32'),(4,'Ticket 2','<p>asdasdas</p>',1,1,4,4,NULL,'2025-05-03 08:12:54','2025-05-14 06:18:28','111-2',1,0,2,8.00,NULL,NULL,0.40,21.00,100.00,2,'2025-05-03 16:12:54'),(5,'Ticket 3','<p>dasdasd</p>',1,2,2,4,NULL,'2025-05-14 04:01:44','2025-05-14 09:16:23','111-3',1,0,2,10.00,NULL,NULL,0.20,0.00,100.00,1,'2025-05-14 12:01:44');
/*!40000 ALTER TABLE `tickets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `time_logs`
--

DROP TABLE IF EXISTS `time_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `time_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ticket_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime DEFAULT NULL,
  `hours` double(8,2) NOT NULL DEFAULT '0.00',
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `time_logs_user_id_foreign` (`user_id`),
  KEY `time_logs_ticket_id_user_id_index` (`ticket_id`,`user_id`),
  CONSTRAINT `time_logs_ticket_id_foreign` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`),
  CONSTRAINT `time_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `time_logs`
--

LOCK TABLES `time_logs` WRITE;
/*!40000 ALTER TABLE `time_logs` DISABLE KEYS */;
INSERT INTO `time_logs` VALUES (1,3,1,'2025-05-03 10:30:51','2025-05-03 18:19:18',7.80,'Work completed','2025-05-03 09:30:51','2025-05-03 10:19:18'),(2,4,1,'2025-05-03 18:24:01','2025-05-03 18:24:11',0.10,'Work completed','2025-05-03 10:24:01','2025-05-03 10:24:11'),(3,4,1,'2025-05-03 19:12:57','2025-05-03 19:13:01',0.10,'Work completed','2025-05-03 11:12:57','2025-05-03 11:13:01'),(4,3,1,'2025-05-03 19:20:51','2025-05-03 19:22:08',0.10,'Work completed','2025-05-03 11:20:51','2025-05-03 11:22:08'),(5,4,1,'2025-05-03 19:22:09','2025-05-03 19:22:11',0.10,'Work completed','2025-05-03 11:22:09','2025-05-03 11:22:11'),(6,4,1,'2025-05-03 19:22:50','2025-05-03 19:22:53',0.10,'Work completed','2025-05-03 11:22:50','2025-05-03 11:22:53'),(7,4,1,'2025-05-03 19:23:55','2025-05-03 19:24:02',0.10,'Work completed','2025-05-03 11:23:55','2025-05-03 11:24:02'),(8,5,4,'2025-05-14 14:17:20','2025-05-14 14:17:37',0.10,'Work completed','2025-05-14 06:17:20','2025-05-14 06:17:37'),(9,5,1,'2025-05-14 14:59:41','2025-05-14 14:59:45',0.10,'Work completed','2025-05-14 06:59:41','2025-05-14 06:59:45'),(10,5,1,'2025-05-14 15:11:16','2025-05-14 15:16:34',0.10,'Work completed','2025-05-14 07:11:16','2025-05-14 07:16:34'),(11,5,1,'2025-05-14 17:16:23',NULL,0.00,'Work started','2025-05-14 09:16:23','2025-05-14 09:16:23');
/*!40000 ALTER TABLE `time_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `two_factor_secret` text COLLATE utf8mb4_unicode_ci,
  `two_factor_recovery_codes` text COLLATE utf8mb4_unicode_ci,
  `two_factor_confirmed_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `creation_token` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'db',
  `oidc_username` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `oidc_sub` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Juan Dela Cruz','admintest2025@yopmail.com','2025-04-20 07:15:16','$2y$10$idTYKKgyvoD9HknmHi4na.m1SlKPOOG95ECQKozbxFx907QWFiGne',NULL,NULL,NULL,NULL,'2025-04-20 07:15:16','2025-04-20 07:15:16',NULL,NULL,'db',NULL,NULL),(2,'Angela Reyes','angela.santos@yopmail.com','2025-04-21 08:39:47','$2y$10$ZiUN/flLdyh3/oiTluBeAucqTuVcnwe.QS/gqSuCmJ3fabtD6oM.a',NULL,NULL,NULL,'EWjX9zgFgpbIcsYfPY0nDbEFlZU0ke6BFD8PvJnb2oJ4CyWRyPgtZ0Eanogd','2025-04-21 08:31:28','2025-04-21 08:39:47',NULL,NULL,'db',NULL,NULL),(3,'Kurapika','kurapika@yopmail.com',NULL,'$2y$10$LtxYgqfAocL9JknM1Y36AuZlwPWv9nwMn0of.p1K.DeIJoQy0DC0O',NULL,NULL,NULL,NULL,'2025-05-14 04:34:08','2025-05-14 04:40:31','2025-05-14 04:40:31','dcd7054c-3117-4d1e-8cf8-d823b4365934','db',NULL,NULL),(4,'Kurapika','mulicrutrale-5287@yopmail.com','2025-05-14 06:13:51','$2y$10$Z/bc1fuqhs5AK5OwkY//BeQIGfI1OMkJhFaXOdnfb8F5dEF5sUzSm',NULL,NULL,NULL,'evzLCLnORbkok32ynCydYuo2UhAH3ZWjOxwAaGbjMNhMNAABypvN76PRLMES','2025-05-14 04:40:58','2025-05-14 06:13:51',NULL,'6a06144f-04f9-44d8-be8c-9c2c9a7db030','db',NULL,NULL),(5,'Gon','jeizaummepigi-2619@yopmail.com',NULL,NULL,NULL,NULL,NULL,NULL,'2025-05-14 05:58:55','2025-05-14 05:58:55',NULL,NULL,'db',NULL,NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-05-20 21:52:06
