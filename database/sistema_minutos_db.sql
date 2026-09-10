-- MySQL dump 10.13  Distrib 8.4.11, for Linux (x86_64)
--
-- Host: localhost    Database: sistema_minutos_db
-- ------------------------------------------------------
-- Server version	8.4.11

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `bus`
--

DROP TABLE IF EXISTS `bus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bus` (
  `id` int NOT NULL AUTO_INCREMENT,
  `placa` varchar(20) NOT NULL,
  `disco` varchar(20) NOT NULL,
  `activo` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `placa` (`placa`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bus`
--

LOCK TABLES `bus` WRITE;
/*!40000 ALTER TABLE `bus` DISABLE KEYS */;
INSERT INTO `bus` VALUES (1,'PBA-1001','01',1),(2,'PBA-1002','02',1),(3,'PBA-1003','03',1),(4,'PBA-1004','04',1),(5,'PBA-1005','05',1),(6,'PBA-1006','06',1),(7,'PBA-1007','07',1),(8,'PBA-1008','08',1),(9,'PBA-1009','09',1),(10,'PBA-1010','10',1);
/*!40000 ALTER TABLE `bus` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `estado_usuario`
--

DROP TABLE IF EXISTS `estado_usuario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `estado_usuario` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `activo` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `estado_usuario`
--

LOCK TABLES `estado_usuario` WRITE;
/*!40000 ALTER TABLE `estado_usuario` DISABLE KEYS */;
INSERT INTO `estado_usuario` VALUES (1,'habilitado',1),(2,'deshabilitado',1);
/*!40000 ALTER TABLE `estado_usuario` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `intento_turno`
--

DROP TABLE IF EXISTS `intento_turno`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `intento_turno` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int NOT NULL,
  `bus_id` int DEFAULT NULL,
  `disco_escaneado` varchar(30) NOT NULL DEFAULT '',
  `fecha` date NOT NULL,
  `hora_intento` time NOT NULL,
  `motivo` varchar(255) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `idx_intento_turno_fecha` (`fecha`,`hora_intento`),
  KEY `idx_intento_turno_usuario` (`usuario_id`,`fecha`),
  KEY `idx_intento_turno_bus` (`bus_id`,`fecha`),
  CONSTRAINT `fk_intento_turno_bus` FOREIGN KEY (`bus_id`) REFERENCES `bus` (`id`),
  CONSTRAINT `fk_intento_turno_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `intento_turno`
--

LOCK TABLES `intento_turno` WRITE;
/*!40000 ALTER TABLE `intento_turno` DISABLE KEYS */;
/*!40000 ALTER TABLE `intento_turno` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `obligacion_pago`
--

DROP TABLE IF EXISTS `obligacion_pago`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `obligacion_pago` (
  `id` int NOT NULL AUTO_INCREMENT,
  `disco` varchar(20) NOT NULL,
  `fecha` date NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `ruta` varchar(100) DEFAULT NULL,
  `pago_id` int DEFAULT NULL,
  `pagado` tinyint(1) NOT NULL DEFAULT '0',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unq_obligacion_disco_fecha` (`disco`,`fecha`),
  KEY `idx_obligacion_pendiente` (`disco`,`pagado`,`activo`),
  KEY `fk_obligacion_pago` (`pago_id`),
  CONSTRAINT `fk_obligacion_pago` FOREIGN KEY (`pago_id`) REFERENCES `pago` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=228 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `obligacion_pago`
--

LOCK TABLES `obligacion_pago` WRITE;
/*!40000 ALTER TABLE `obligacion_pago` DISABLE KEYS */;
INSERT INTO `obligacion_pago` VALUES (1,'01','2026-09-10',4.50,'LINEA 23B',NULL,0,1),(2,'01','2026-09-09',4.50,'LINEA 23B',NULL,0,1),(3,'02','2026-09-10',6.50,'LINEA 16',NULL,0,1),(4,'02','2026-09-09',6.50,'LINEA 16',NULL,0,1),(5,'03','2026-09-10',3.50,'RUTA 4A',NULL,0,1),(6,'03','2026-09-09',3.50,'RUTA 4A',NULL,0,1),(7,'04','2026-09-10',5.50,'LINEA 23B',NULL,0,1),(8,'04','2026-09-09',5.50,'LINEA 23B',NULL,0,1),(9,'05','2026-09-10',2.50,'LINEA 16',NULL,0,1),(10,'05','2026-09-09',2.50,'LINEA 16',NULL,0,1),(11,'06','2026-09-10',4.50,'RUTA 4A',NULL,0,1),(12,'06','2026-09-09',4.50,'RUTA 4A',NULL,0,1),(13,'07','2026-09-10',6.50,'LINEA 23B',NULL,0,1),(14,'07','2026-09-09',6.50,'LINEA 23B',NULL,0,1),(15,'08','2026-09-10',3.50,'LINEA 16',NULL,0,1),(16,'08','2026-09-09',3.50,'LINEA 16',NULL,0,1),(17,'09','2026-09-10',5.50,'RUTA 4A',NULL,0,1),(18,'09','2026-09-09',5.50,'RUTA 4A',NULL,0,1),(19,'10','2026-09-10',2.50,'LINEA 23B',NULL,0,1),(20,'10','2026-09-09',2.50,'LINEA 23B',NULL,0,1),(21,'77','2026-09-06',1.00,'LINEA 23B',NULL,0,1),(22,'84','2026-09-06',3.00,'LINEA 23B',NULL,0,1),(23,'93','2026-09-06',2.00,'LINEA 23B',NULL,0,1),(24,'12','2026-09-06',1.00,'LINEA 23B',3,1,1),(25,'77','2026-09-12',3.00,'LINEA 23B',1,1,1),(26,'77','2026-09-30',5.00,'LINEA 23B',2,1,1),(27,'84','2026-09-07',2.00,'LINEA 23B',NULL,0,1),(28,'80','2026-09-14',4.00,'LINEA 23B',NULL,0,1),(29,'80','2026-09-02',6.00,'LINEA 23B',NULL,0,1),(30,'80','2026-09-03',2.00,'LINEA 23B',NULL,0,1),(31,'80','2026-09-04',3.00,'LINEA 23B',NULL,0,1),(32,'02','2026-09-05',2.00,'LINEA 23B',NULL,0,1),(33,'02','2026-09-06',1.00,'LINEA 23B',NULL,0,1),(34,'02','2026-09-07',3.00,'LINEA 23B',NULL,0,1),(35,'47','2026-09-08',5.00,'LINEA 23B',NULL,0,1),(36,'93','2026-09-09',2.00,'LINEA 23B',NULL,0,1),(37,'93','2026-09-10',1.00,'LINEA 23B',NULL,0,1),(38,'93','2026-09-11',4.00,'LINEA 23B',NULL,0,1),(39,'65','2026-09-12',2.00,'LINEA 23B',NULL,0,1),(40,'65','2026-09-13',1.00,'LINEA 23B',NULL,0,1),(41,'65','2026-09-14',3.00,'LINEA 23B',NULL,0,1),(42,'24','2026-09-15',1.00,'LINEA 23B',NULL,0,1),(43,'24','2026-09-16',2.00,'LINEA 23B',NULL,0,1);
/*!40000 ALTER TABLE `obligacion_pago` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pago`
--

DROP TABLE IF EXISTS `pago`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pago` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int NOT NULL,
  `monto_total` decimal(10,2) NOT NULL,
  `fecha_pago` date NOT NULL,
  `comprobante` text,
  `estado` enum('en_espera','aprobado','anulado','incompleto') NOT NULL DEFAULT 'en_espera',
  `motivo_rechazo` varchar(255) DEFAULT NULL,
  `nro_comprobante` varchar(255) DEFAULT NULL,
  `detalle_pagos` text,
  `activo` tinyint(1) DEFAULT '1',
  `tipo` enum('app','manual') NOT NULL DEFAULT 'app',
  `codigo_ingreso` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pago_codigo_ingreso` (`codigo_ingreso`),
  KEY `fk_pago_usuario` (`usuario_id`),
  CONSTRAINT `fk_pago_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pago`
--

LOCK TABLES `pago` WRITE;
/*!40000 ALTER TABLE `pago` DISABLE KEYS */;
INSERT INTO `pago` VALUES (1,14,3.00,'2026-09-09','comprobantes/comprobante_14_20260909_161618_0da6fad77bbe.png','aprobado',NULL,'56565656565656','[{\"obligacion_id\":25,\"fecha\":\"2026-09-12\",\"disco\":\"77\",\"ruta\":\"LINEA 23B\"}]',1,'app',NULL),(2,1,5.00,'2026-09-09','comprobantes/comprobante_1_20260909_184435_7ae01b04eef7.png','aprobado',NULL,'1234','[{\"obligacion_id\":26,\"fecha\":\"2026-09-30\",\"disco\":\"77\",\"ruta\":\"LINEA 23B\"}]',1,'app',NULL),(3,6,1.00,'2026-09-09','comprobantes/comprobante_6_20260909_185723_b10c1c7e285d.png','aprobado',NULL,'322','[{\"obligacion_id\":24,\"fecha\":\"2026-09-06\",\"disco\":\"12\",\"ruta\":\"LINEA 23B\"}]',1,'app',NULL),(4,1,22.00,'2026-09-09','comprobantes/comprobante_1_20260909_213603_604f7d085440.png','anulado','ss','1234','[{\"obligacion_id\":3,\"fecha\":\"2026-09-10\",\"disco\":\"02\",\"ruta\":\"LINEA 16\"},{\"obligacion_id\":4,\"fecha\":\"2026-09-09\",\"disco\":\"02\",\"ruta\":\"LINEA 16\"},{\"obligacion_id\":32,\"fecha\":\"2026-09-05\",\"disco\":\"02\",\"ruta\":\"LINEA 23B\"},{\"obligacion_id\":33,\"fecha\":\"2026-09-06\",\"disco\":\"02\",\"ruta\":\"LINEA 23B\"},{\"obligacion_id\":34,\"fecha\":\"2026-09-07\",\"disco\":\"02\",\"ruta\":\"LINEA 23B\"},{\"obligacion_id\":42,\"fecha\":\"2026-09-15\",\"disco\":\"24\",\"ruta\":\"LINEA 23B\"},{\"obligacion_id\":43,\"fecha\":\"2026-09-16\",\"disco\":\"24\",\"ruta\":\"LINEA 23B\"}]',1,'app',NULL);
/*!40000 ALTER TABLE `pago` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rol`
--

DROP TABLE IF EXISTS `rol`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rol` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `activo` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rol`
--

LOCK TABLES `rol` WRITE;
/*!40000 ALTER TABLE `rol` DISABLE KEYS */;
INSERT INTO `rol` VALUES (1,'admin',1),(2,'operativo',1),(3,'secretaria',1),(4,'socio',1),(5,'conductor',1);
/*!40000 ALTER TABLE `rol` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `turno`
--

DROP TABLE IF EXISTS `turno`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `turno` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int NOT NULL,
  `bus_id` int NOT NULL,
  `pago_id` int DEFAULT NULL,
  `fecha` date NOT NULL,
  `hora_apertura` time NOT NULL,
  `hora_cierre` time NOT NULL DEFAULT '23:59:00',
  `valor` decimal(10,2) NOT NULL DEFAULT '0.00',
  `ruta` varchar(100) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT '1',
  `cancelado_en` datetime DEFAULT NULL,
  `cancelado_por` int DEFAULT NULL,
  `comentario_cancelacion` varchar(500) DEFAULT NULL,
  `rehabilitado_en` datetime DEFAULT NULL,
  `rehabilitado_por` int DEFAULT NULL,
  `turno_rehabilitado_id` int DEFAULT NULL,
  `pagado` tinyint(1) NOT NULL DEFAULT '0',
  `bus_id_turno_activo` int GENERATED ALWAYS AS ((case when (`activo` = 1) then `bus_id` else NULL end)) STORED,
  `usuario_id_turno_activo` int GENERATED ALWAYS AS ((case when (`activo` = 1) then `usuario_id` else NULL end)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unq_bus_fecha_activo` (`bus_id_turno_activo`,`fecha`),
  UNIQUE KEY `unq_conductor_fecha_activo` (`usuario_id_turno_activo`,`fecha`),
  KEY `idx_turno_bus` (`bus_id`),
  KEY `idx_turno_usuario` (`usuario_id`),
  KEY `fk_turno_pago` (`pago_id`),
  CONSTRAINT `fk_turno_pago` FOREIGN KEY (`pago_id`) REFERENCES `pago` (`id`),
  CONSTRAINT `turno_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`),
  CONSTRAINT `turno_ibfk_2` FOREIGN KEY (`bus_id`) REFERENCES `bus` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `turno`
--

LOCK TABLES `turno` WRITE;
/*!40000 ALTER TABLE `turno` DISABLE KEYS */;
INSERT INTO `turno` (`id`,`usuario_id`,`bus_id`,`pago_id`,`fecha`,`hora_apertura`,`hora_cierre`,`valor`,`ruta`,`activo`,`cancelado_en`,`cancelado_por`,`comentario_cancelacion`,`rehabilitado_en`,`rehabilitado_por`,`turno_rehabilitado_id`,`pagado`) VALUES (1,6,2,NULL,'2026-09-09','19:15:44','23:59:00',0.00,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,0);
/*!40000 ALTER TABLE `turno` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuario`
--

DROP TABLE IF EXISTS `usuario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuario` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombres` varchar(100) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `fecha_nacimiento` date NOT NULL,
  `cedula` varchar(20) NOT NULL,
  `codigo_conductor` varchar(10) DEFAULT NULL COMMENT 'Codigo correlativo 001, 002... solo para conductores',
  `codigo_socio` varchar(10) DEFAULT NULL COMMENT 'Codigo correlativo 001, 002... solo para socios',
  `rol_id` int NOT NULL,
  `estado_usuario_id` int NOT NULL,
  `activo` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `cedula` (`cedula`),
  UNIQUE KEY `codigo_conductor` (`codigo_conductor`),
  UNIQUE KEY `codigo_socio` (`codigo_socio`),
  KEY `rol_id` (`rol_id`),
  KEY `estado_usuario_id` (`estado_usuario_id`),
  CONSTRAINT `usuario_ibfk_1` FOREIGN KEY (`rol_id`) REFERENCES `rol` (`id`),
  CONSTRAINT `usuario_ibfk_2` FOREIGN KEY (`estado_usuario_id`) REFERENCES `estado_usuario` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuario`
--

LOCK TABLES `usuario` WRITE;
/*!40000 ALTER TABLE `usuario` DISABLE KEYS */;
INSERT INTO `usuario` VALUES (1,'Carlos','Andrade Vera','1985-03-12','1000000001',NULL,NULL,1,1,1),(2,'María','Fernández Salazar','1990-07-24','1000000002',NULL,NULL,3,1,1),(3,'Lucía','Reyes Mendoza','1992-01-18','1000000003',NULL,NULL,3,1,1),(4,'Paola','Cárdenas Ruiz','1988-10-03','1000000004',NULL,NULL,3,1,1),(5,'Pedro','Gómez Torres','1991-05-08','1000001001','001',NULL,5,1,1),(6,'Luis','Ramírez Paredes','1988-09-17','1000001002','002',NULL,5,1,1),(7,'Ana','Cruz Medina','1994-02-21','1000001003','003',NULL,5,1,1),(8,'Diego','Pérez Guerrero','1986-12-05','1000001004','004',NULL,5,1,1),(9,'Sofía','López Naranjo','1992-04-10','1000001005','005',NULL,5,1,1),(10,'Andrés','Silva Almeida','1989-08-19','1000001006','006',NULL,5,1,1),(11,'Nicole','Ríos Castro','1995-01-27','1000001007','007',NULL,5,1,1),(12,'Kevin','Vélez Zambrano','1993-06-14','1000001008','008',NULL,5,1,1),(13,'Paola','Álvarez Mendoza','1990-10-02','1000001009','009',NULL,5,1,1),(14,'David','Ortega Cevallos','1987-03-23','1000001010','010',NULL,5,1,1),(15,'Jorge','Mendoza Ruiz','1978-01-15','1000000011',NULL,'001',4,1,1),(16,'Luz','Vega Campos','1982-11-30','1000000012',NULL,'002',4,1,1),(17,'Rosa','Vera Pineda','1975-06-22','1000000013',NULL,'003',4,1,1),(18,'Hugo','Salazar León','1980-04-09','1000000014',NULL,'004',4,1,1),(19,'Inés','Delgado Ortiz','1986-08-14','1000000015',NULL,'005',4,1,1);
/*!40000 ALTER TABLE `usuario` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuario_bus`
--

DROP TABLE IF EXISTS `usuario_bus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuario_bus` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int NOT NULL,
  `bus_id` int NOT NULL,
  `activo` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `bus_id` (`bus_id`),
  CONSTRAINT `usuario_bus_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`),
  CONSTRAINT `usuario_bus_ibfk_2` FOREIGN KEY (`bus_id`) REFERENCES `bus` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuario_bus`
--

LOCK TABLES `usuario_bus` WRITE;
/*!40000 ALTER TABLE `usuario_bus` DISABLE KEYS */;
INSERT INTO `usuario_bus` VALUES (1,5,1,1),(2,6,2,1),(3,7,3,1),(4,8,4,1),(5,9,5,1),(6,10,6,1),(7,11,7,1),(8,12,8,1),(9,13,9,1),(10,14,10,1),(11,15,1,1),(12,16,2,1);
/*!40000 ALTER TABLE `usuario_bus` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuario_permiso_modulo`
--

DROP TABLE IF EXISTS `usuario_permiso_modulo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuario_permiso_modulo` (
  `usuario_id` int NOT NULL,
  `modulo` varchar(40) NOT NULL,
  `habilitado` tinyint(1) NOT NULL DEFAULT '1',
  `actualizado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`usuario_id`,`modulo`),
  CONSTRAINT `fk_permiso_modulo_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuario_permiso_modulo`
--

LOCK TABLES `usuario_permiso_modulo` WRITE;
/*!40000 ALTER TABLE `usuario_permiso_modulo` DISABLE KEYS */;
INSERT INTO `usuario_permiso_modulo` VALUES (1,'app_pagos',1,'2026-09-10 00:20:26'),(1,'app_qr',1,'2026-09-10 00:20:26'),(1,'web_buses',1,'2026-09-10 00:20:26'),(1,'web_dashboard',1,'2026-09-10 00:20:26'),(1,'web_pagos',1,'2026-09-10 00:20:26'),(1,'web_socios',1,'2026-09-10 00:20:26'),(1,'web_turnos',1,'2026-09-10 00:20:26'),(1,'web_valores',1,'2026-09-10 00:20:26'),(2,'app_pagos',0,'2026-09-10 00:21:05'),(2,'app_qr',0,'2026-09-10 00:21:05'),(2,'web_buses',1,'2026-09-10 00:21:05'),(2,'web_dashboard',1,'2026-09-10 00:21:05'),(2,'web_pagos',1,'2026-09-10 00:21:05'),(2,'web_socios',1,'2026-09-10 00:21:05'),(2,'web_turnos',1,'2026-09-10 00:21:05'),(2,'web_valores',1,'2026-09-10 00:21:05'),(3,'app_pagos',0,'2026-09-10 00:20:26'),(3,'app_qr',0,'2026-09-10 00:20:26'),(3,'web_buses',1,'2026-09-10 00:20:26'),(3,'web_dashboard',1,'2026-09-10 00:20:26'),(3,'web_pagos',1,'2026-09-10 00:20:26'),(3,'web_socios',1,'2026-09-10 00:20:26'),(3,'web_turnos',1,'2026-09-10 00:20:26'),(3,'web_valores',1,'2026-09-10 00:20:26'),(4,'app_pagos',0,'2026-09-10 00:20:26'),(4,'app_qr',0,'2026-09-10 00:20:26'),(4,'web_buses',1,'2026-09-10 00:20:26'),(4,'web_dashboard',1,'2026-09-10 00:20:26'),(4,'web_pagos',1,'2026-09-10 00:20:26'),(4,'web_socios',1,'2026-09-10 00:20:26'),(4,'web_turnos',1,'2026-09-10 00:20:26'),(4,'web_valores',1,'2026-09-10 00:20:26'),(5,'app_pagos',1,'2026-09-10 00:21:05'),(5,'app_qr',1,'2026-09-10 00:21:05'),(5,'web_buses',0,'2026-09-10 00:21:05'),(5,'web_dashboard',0,'2026-09-10 00:21:05'),(5,'web_pagos',0,'2026-09-10 00:21:05'),(5,'web_socios',0,'2026-09-10 00:21:05'),(5,'web_turnos',0,'2026-09-10 00:21:05'),(5,'web_valores',0,'2026-09-10 00:21:05'),(6,'app_pagos',1,'2026-09-10 02:36:36'),(6,'app_qr',1,'2026-09-10 02:36:36'),(6,'web_buses',0,'2026-09-10 02:36:36'),(6,'web_dashboard',0,'2026-09-10 02:36:36'),(6,'web_pagos',0,'2026-09-10 02:36:36'),(6,'web_socios',1,'2026-09-10 02:36:36'),(6,'web_turnos',0,'2026-09-10 02:36:36'),(6,'web_valores',0,'2026-09-10 02:36:36'),(7,'app_pagos',1,'2026-09-10 00:20:26'),(7,'app_qr',1,'2026-09-10 00:20:26'),(7,'web_buses',0,'2026-09-10 00:20:26'),(7,'web_dashboard',0,'2026-09-10 00:20:26'),(7,'web_pagos',0,'2026-09-10 00:20:26'),(7,'web_socios',0,'2026-09-10 00:20:26'),(7,'web_turnos',0,'2026-09-10 00:20:26'),(7,'web_valores',0,'2026-09-10 00:20:26'),(8,'app_pagos',1,'2026-09-10 00:20:26'),(8,'app_qr',1,'2026-09-10 00:20:26'),(8,'web_buses',0,'2026-09-10 00:20:26'),(8,'web_dashboard',0,'2026-09-10 00:20:26'),(8,'web_pagos',0,'2026-09-10 00:20:26'),(8,'web_socios',0,'2026-09-10 00:20:26'),(8,'web_turnos',0,'2026-09-10 00:20:26'),(8,'web_valores',0,'2026-09-10 00:20:26'),(9,'app_pagos',1,'2026-09-10 00:20:26'),(9,'app_qr',1,'2026-09-10 00:20:26'),(9,'web_buses',0,'2026-09-10 00:20:26'),(9,'web_dashboard',0,'2026-09-10 00:20:26'),(9,'web_pagos',0,'2026-09-10 00:20:26'),(9,'web_socios',0,'2026-09-10 00:20:26'),(9,'web_turnos',0,'2026-09-10 00:20:26'),(9,'web_valores',0,'2026-09-10 00:20:26'),(10,'app_pagos',1,'2026-09-10 00:20:26'),(10,'app_qr',1,'2026-09-10 00:20:26'),(10,'web_buses',0,'2026-09-10 00:20:26'),(10,'web_dashboard',0,'2026-09-10 00:20:26'),(10,'web_pagos',0,'2026-09-10 00:20:26'),(10,'web_socios',0,'2026-09-10 00:20:26'),(10,'web_turnos',0,'2026-09-10 00:20:26'),(10,'web_valores',0,'2026-09-10 00:20:26'),(11,'app_pagos',1,'2026-09-10 00:20:26'),(11,'app_qr',1,'2026-09-10 00:20:26'),(11,'web_buses',0,'2026-09-10 00:20:26'),(11,'web_dashboard',0,'2026-09-10 00:20:26'),(11,'web_pagos',0,'2026-09-10 00:20:26'),(11,'web_socios',0,'2026-09-10 00:20:26'),(11,'web_turnos',0,'2026-09-10 00:20:26'),(11,'web_valores',0,'2026-09-10 00:20:26'),(12,'app_pagos',1,'2026-09-10 00:20:26'),(12,'app_qr',1,'2026-09-10 00:20:26'),(12,'web_buses',0,'2026-09-10 00:20:26'),(12,'web_dashboard',0,'2026-09-10 00:20:26'),(12,'web_pagos',0,'2026-09-10 00:20:26'),(12,'web_socios',0,'2026-09-10 00:20:26'),(12,'web_turnos',0,'2026-09-10 00:20:26'),(12,'web_valores',0,'2026-09-10 00:20:26'),(13,'app_pagos',1,'2026-09-10 00:20:26'),(13,'app_qr',1,'2026-09-10 00:20:26'),(13,'web_buses',0,'2026-09-10 00:20:26'),(13,'web_dashboard',0,'2026-09-10 00:20:26'),(13,'web_pagos',0,'2026-09-10 00:20:26'),(13,'web_socios',0,'2026-09-10 00:20:26'),(13,'web_turnos',0,'2026-09-10 00:20:26'),(13,'web_valores',0,'2026-09-10 00:20:26'),(14,'app_pagos',1,'2026-09-10 00:20:26'),(14,'app_qr',1,'2026-09-10 00:20:26'),(14,'web_buses',0,'2026-09-10 00:20:26'),(14,'web_dashboard',0,'2026-09-10 00:20:26'),(14,'web_pagos',0,'2026-09-10 00:20:26'),(14,'web_socios',0,'2026-09-10 00:20:26'),(14,'web_turnos',0,'2026-09-10 00:20:26'),(14,'web_valores',0,'2026-09-10 00:20:26'),(15,'app_pagos',1,'2026-09-10 00:20:26'),(15,'app_qr',1,'2026-09-10 00:20:26'),(15,'web_buses',0,'2026-09-10 00:20:26'),(15,'web_dashboard',0,'2026-09-10 00:20:26'),(15,'web_pagos',0,'2026-09-10 00:20:26'),(15,'web_socios',0,'2026-09-10 00:20:26'),(15,'web_turnos',0,'2026-09-10 00:20:26'),(15,'web_valores',0,'2026-09-10 00:20:26'),(16,'app_pagos',1,'2026-09-10 00:20:26'),(16,'app_qr',1,'2026-09-10 00:20:26'),(16,'web_buses',0,'2026-09-10 00:20:26'),(16,'web_dashboard',0,'2026-09-10 00:20:26'),(16,'web_pagos',0,'2026-09-10 00:20:26'),(16,'web_socios',0,'2026-09-10 00:20:26'),(16,'web_turnos',0,'2026-09-10 00:20:26'),(16,'web_valores',0,'2026-09-10 00:20:26'),(17,'app_pagos',1,'2026-09-10 00:20:26'),(17,'app_qr',1,'2026-09-10 00:20:26'),(17,'web_buses',0,'2026-09-10 00:20:26'),(17,'web_dashboard',0,'2026-09-10 00:20:26'),(17,'web_pagos',0,'2026-09-10 00:20:26'),(17,'web_socios',0,'2026-09-10 00:20:26'),(17,'web_turnos',0,'2026-09-10 00:20:26'),(17,'web_valores',0,'2026-09-10 00:20:26'),(18,'app_pagos',1,'2026-09-10 00:20:26'),(18,'app_qr',1,'2026-09-10 00:20:26'),(18,'web_buses',0,'2026-09-10 00:20:26'),(18,'web_dashboard',0,'2026-09-10 00:20:26'),(18,'web_pagos',0,'2026-09-10 00:20:26'),(18,'web_socios',0,'2026-09-10 00:20:26'),(18,'web_turnos',0,'2026-09-10 00:20:26'),(18,'web_valores',0,'2026-09-10 00:20:26'),(19,'app_pagos',1,'2026-09-10 00:20:26'),(19,'app_qr',1,'2026-09-10 00:20:26'),(19,'web_buses',0,'2026-09-10 00:20:26'),(19,'web_dashboard',0,'2026-09-10 00:20:26'),(19,'web_pagos',0,'2026-09-10 00:20:26'),(19,'web_socios',0,'2026-09-10 00:20:26'),(19,'web_turnos',0,'2026-09-10 00:20:26'),(19,'web_valores',0,'2026-09-10 00:20:26');
/*!40000 ALTER TABLE `usuario_permiso_modulo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'sistema_minutos_db'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-10 13:33:51
