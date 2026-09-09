-- Respaldo completo de la base local: estructura, datos, índices, relaciones y eventos.
-- Importar en una base VACÍA seleccionada previamente (por ejemplo sistema_minutos_db).
-- No ejecutar sobre una instalación existente: este archivo no es una migración.
-- El cierre automático requiere event_scheduler=ON en el servidor.

-- MySQL dump 10.13  Distrib 8.4.10, for Linux (aarch64)
--
-- Host: localhost    Database: sistema_minutos_db
-- ------------------------------------------------------
-- Server version	8.4.10

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

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bus` (
  `id` int NOT NULL AUTO_INCREMENT,
  `placa` varchar(20) NOT NULL,
  `disco` varchar(20) NOT NULL,
  `activo` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `placa` (`placa`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bus`
--

LOCK TABLES `bus` WRITE;
/*!40000 ALTER TABLE `bus` DISABLE KEYS */;
INSERT INTO `bus` VALUES (1,'GHA-4587','01',1),(2,'GCZ-1290','02',1),(3,'GNA-7431','03',1),(4,'GER-0215','04',1),(5,'GBC-8830','05',1),(6,'ASD-4357','80',1),(7,'GSF-8230','81',1),(8,'CBS-7316','91',1),(9,'GHS-8732','96',0);
/*!40000 ALTER TABLE `bus` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `estado_usuario`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `intento_turno`
--

LOCK TABLES `intento_turno` WRITE;
/*!40000 ALTER TABLE `intento_turno` DISABLE KEYS */;
INSERT INTO `intento_turno` VALUES (2,9,6,'80','2026-09-08','16:34:56','Este bus ya abrió un turno el día de hoy, comunicarse con su jefe de ruta.',1),(3,1,6,'80','2026-09-08','16:37:04','Usted ya abrió un turno el día de hoy, por favor comunicarse con su jefe de ruta.',1),(4,7,NULL,'005','2026-09-09','08:19:17','Bus no encontrado. Verifique el código QR.',1),(5,7,NULL,'005','2026-09-09','08:19:19','Bus no encontrado. Verifique el código QR.',1),(6,7,NULL,'002','2026-09-09','08:19:26','Bus no encontrado. Verifique el código QR.',1);
/*!40000 ALTER TABLE `intento_turno` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `obligacion_pago`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=2637 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `obligacion_pago`
--

LOCK TABLES `obligacion_pago` WRITE;
/*!40000 ALTER TABLE `obligacion_pago` DISABLE KEYS */;
INSERT INTO `obligacion_pago` VALUES (1,'77','2026-09-06',1.00,'LINEA 23B',NULL,0,1),(2,'84','2026-09-06',3.00,'LINEA 23B',NULL,0,1),(3,'93','2026-09-06',2.00,'LINEA 23B',NULL,0,1),(4,'12','2026-09-06',1.00,'LINEA 23B',NULL,0,1),(5,'77','2026-09-12',3.00,'LINEA 23B',NULL,0,1),(6,'77','2026-09-30',5.00,'LINEA 23B',NULL,0,1),(7,'84','2026-09-07',2.00,'LINEA 23B',NULL,0,1),(484,'80','2026-09-14',4.00,'LINEA 23B',2,1,1),(485,'80','2026-09-02',6.00,'LINEA 23B',NULL,0,1),(486,'80','2026-09-03',2.00,'LINEA 23B',2,1,1),(487,'80','2026-09-04',3.00,'LINEA 23B',NULL,0,1),(488,'2','2026-09-05',2.00,'LINEA 23B',4,1,1),(489,'2','2026-09-06',1.00,'LINEA 23B',3,1,1),(490,'2','2026-09-07',3.00,'LINEA 23B',3,1,1),(491,'47','2026-09-08',5.00,'LINEA 23B',NULL,0,1);
/*!40000 ALTER TABLE `obligacion_pago` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pago`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pago` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int NOT NULL,
  `monto_total` decimal(10,2) NOT NULL,
  `fecha_pago` date NOT NULL,
  `comprobante` varchar(255) DEFAULT NULL,
  `estado` enum('en_espera','aprobado','anulado') NOT NULL DEFAULT 'en_espera',
  `motivo_rechazo` varchar(255) DEFAULT NULL,
  `nro_comprobante` varchar(255) DEFAULT NULL,
  `detalle_pagos` text,
  `activo` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `fk_pago_usuario` (`usuario_id`),
  CONSTRAINT `fk_pago_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pago`
--

LOCK TABLES `pago` WRITE;
/*!40000 ALTER TABLE `pago` DISABLE KEYS */;
INSERT INTO `pago` VALUES (2,7,6.00,'2026-09-08','comprobantes/comprobante_7_20260908_165202_a0478e2a082c.png','aprobado',NULL,NULL,NULL,1),(3,1,4.00,'2026-09-09','comprobantes/comprobante_1_20260909_084820_4f39580f5db6.png','aprobado',NULL,NULL,NULL,1),(4,1,2.00,'2026-09-09','comprobantes/comprobante_1_20260909_084917_dc371a48b297.png','aprobado',NULL,'999 | 99 | 88 | 666',NULL,1);
/*!40000 ALTER TABLE `pago` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rol`
--

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
  `pagado` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unq_bus_fecha` (`bus_id`,`fecha`),
  UNIQUE KEY `unq_conductor_fecha` (`usuario_id`,`fecha`),
  KEY `fk_turno_pago` (`pago_id`),
  CONSTRAINT `fk_turno_pago` FOREIGN KEY (`pago_id`) REFERENCES `pago` (`id`),
  CONSTRAINT `turno_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`),
  CONSTRAINT `turno_ibfk_2` FOREIGN KEY (`bus_id`) REFERENCES `bus` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `turno`
--

LOCK TABLES `turno` WRITE;
/*!40000 ALTER TABLE `turno` DISABLE KEYS */;
INSERT INTO `turno` VALUES (28,8,2,NULL,'2026-09-08','12:20:53','23:59:00',0.00,NULL,0,0),(29,1,6,NULL,'2026-09-08','14:54:13','23:59:00',0.00,NULL,0,0),(31,7,5,NULL,'2026-09-08','16:39:12','23:59:00',0.00,NULL,0,0),(32,7,5,NULL,'2026-09-09','08:19:39','23:59:00',0.00,NULL,1,0);
/*!40000 ALTER TABLE `turno` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuario`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuario`
--

LOCK TABLES `usuario` WRITE;
/*!40000 ALTER TABLE `usuario` DISABLE KEYS */;
INSERT INTO `usuario` VALUES (1,'Bruce Leroy','Rodriguez Montalvan','1990-05-14','1234567890','001',NULL,5,1,1),(2,'Adonis Vladimir','Alegria Valle','1985-02-20','2300446305',NULL,NULL,1,1,1),(3,'María Fernanda','López Cedeño','1992-11-03','0912345678',NULL,'001',4,1,1),(4,'Carlos Eduardo','Vera Pinargote','1980-07-18','0923456789',NULL,'002',4,1,1),(5,'Jorge Luis','Mendoza Bravo','1988-09-23','1312345678',NULL,NULL,2,1,1),(6,'Gabriela Michelle','Salazar Ordoñez','1995-04-12','1301234567',NULL,NULL,3,1,1),(7,'Luis Alberto','Cedeño Anchundia','1990-01-15','0987654321','002',NULL,5,1,1),(8,'Pedro Vicente','Guamán Delgado','1985-06-30','0967123456','003',NULL,5,1,1),(9,'José Manuel','Cobeña Garcés','1992-11-08','0998765432','004',NULL,5,1,1),(10,'Felipe Andrés','Morales Zambrano','1987-03-19','2312345678','005',NULL,5,1,1),(11,'Rosa Elena','Paredes Blum','1998-02-28','0965432109',NULL,'003',4,1,1),(12,'Marco Antonio','Delgado Vinces','1983-12-05','0982345671',NULL,'004',4,1,1),(13,'Carmen Luisa','Franco Tapia','1994-08-17','0985432167',NULL,'005',4,1,1),(16,'Pedro Santos','Ordoñez Rodrigo','1994-08-17','1722656244',NULL,'006',4,1,1),(18,'PRUEBA','PRUEBA','2026-09-08','1234567891',NULL,'007',4,1,1);
/*!40000 ALTER TABLE `usuario` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuario_bus`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuario_bus`
--

LOCK TABLES `usuario_bus` WRITE;
/*!40000 ALTER TABLE `usuario_bus` DISABLE KEYS */;
INSERT INTO `usuario_bus` VALUES (1,3,1,1),(2,12,2,1),(3,11,3,1),(4,13,4,1),(5,13,5,1),(7,13,6,1),(8,16,7,1),(9,18,8,1),(10,4,9,1);
/*!40000 ALTER TABLE `usuario_bus` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'sistema_minutos_db'
--
/*!50106 SET @save_time_zone= @@TIME_ZONE */ ;
DELIMITER ;;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;;
/*!50003 SET character_set_client  = latin1 */ ;;
/*!50003 SET character_set_results = latin1 */ ;;
/*!50003 SET collation_connection  = latin1_swedish_ci */ ;;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;;
/*!50003 SET @saved_time_zone      = @@time_zone */ ;;
/*!50003 SET time_zone             = '-05:00' */ ;;
/*!50106 CREATE*/ /*!50106 EVENT `cerrar_turnos_diarios` ON SCHEDULE EVERY 1 MINUTE STARTS '2026-09-08 09:45:11' ON COMPLETION PRESERVE ENABLE DO UPDATE turno
       SET activo = 0
     WHERE activo = 1
       AND TIMESTAMP(fecha, hora_cierre) <= NOW() */ ;;
/*!50003 SET time_zone             = @saved_time_zone */ ;;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;;
/*!50003 SET character_set_client  = @saved_cs_client */ ;;
/*!50003 SET character_set_results = @saved_cs_results */ ;;
/*!50003 SET collation_connection  = @saved_col_connection */ ;;
DELIMITER ;
/*!50106 SET TIME_ZONE= @save_time_zone */ ;

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

-- Dump completed on 2026-09-09 14:32:54
