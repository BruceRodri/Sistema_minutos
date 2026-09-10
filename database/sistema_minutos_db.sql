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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bus`
--

LOCK TABLES `bus` WRITE;
/*!40000 ALTER TABLE `bus` DISABLE KEYS */;
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
  `creado_en` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unq_obligacion_disco_fecha` (`disco`,`fecha`),
  KEY `idx_obligacion_pendiente` (`disco`,`pagado`,`activo`),
  KEY `fk_obligacion_pago` (`pago_id`),
  CONSTRAINT `fk_obligacion_pago` FOREIGN KEY (`pago_id`) REFERENCES `pago` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `obligacion_pago`
--

LOCK TABLES `obligacion_pago` WRITE;
/*!40000 ALTER TABLE `obligacion_pago` DISABLE KEYS */;
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
  `comprobante` varchar(255) DEFAULT NULL,
  `estado` enum('en_espera','aprobado','anulado') NOT NULL DEFAULT 'en_espera',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pago`
--

LOCK TABLES `pago` WRITE;
/*!40000 ALTER TABLE `pago` DISABLE KEYS */;
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
  KEY `fk_turno_pago` (`pago_id`),
  KEY `idx_turno_bus` (`bus_id`),
  KEY `idx_turno_usuario` (`usuario_id`),
  CONSTRAINT `fk_turno_pago` FOREIGN KEY (`pago_id`) REFERENCES `pago` (`id`),
  CONSTRAINT `turno_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`),
  CONSTRAINT `turno_ibfk_2` FOREIGN KEY (`bus_id`) REFERENCES `bus` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `turno`
--

LOCK TABLES `turno` WRITE;
/*!40000 ALTER TABLE `turno` DISABLE KEYS */;
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
  `codigo_usuario` varchar(10) GENERATED ALWAYS AS (coalesce(`codigo_conductor`,`codigo_socio`)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cedula` (`cedula`),
  UNIQUE KEY `codigo_conductor` (`codigo_conductor`),
  UNIQUE KEY `codigo_socio` (`codigo_socio`),
  UNIQUE KEY `unq_codigo_usuario` (`codigo_usuario`),
  KEY `rol_id` (`rol_id`),
  KEY `estado_usuario_id` (`estado_usuario_id`),
  CONSTRAINT `usuario_ibfk_1` FOREIGN KEY (`rol_id`) REFERENCES `rol` (`id`),
  CONSTRAINT `usuario_ibfk_2` FOREIGN KEY (`estado_usuario_id`) REFERENCES `estado_usuario` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuario`
--

LOCK TABLES `usuario` WRITE;
/*!40000 ALTER TABLE `usuario` DISABLE KEYS */;
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`) VALUES (1,'Administrador','Sistema','1990-01-01','1710000017',NULL,NULL,1,1,1),(2,'Operativo','Sistema','1990-01-02','1710000025',NULL,NULL,2,1,1),(3,'Secretaria','Sistema','1990-01-03','1710000033',NULL,NULL,3,1,1),(4,'Socio','Sistema','1990-01-04','1710000041',NULL,'002',4,1,1),(5,'Conductor','Sistema','1990-01-05','1710000058','001',NULL,5,1,1);
/*!40000 ALTER TABLE `usuario` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = latin1 */ ;
/*!50003 SET character_set_results = latin1 */ ;
/*!50003 SET collation_connection  = latin1_swedish_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `validar_codigo_usuario_insert` BEFORE INSERT ON `usuario` FOR EACH ROW BEGIN
    IF (NEW.codigo_conductor IS NOT NULL AND EXISTS (
        SELECT 1 FROM usuario WHERE codigo_socio = NEW.codigo_conductor
    )) OR (NEW.codigo_socio IS NOT NULL AND EXISTS (
        SELECT 1 FROM usuario WHERE codigo_conductor = NEW.codigo_socio
    )) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El codigo de usuario ya existe en otro rol';
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = latin1 */ ;
/*!50003 SET character_set_results = latin1 */ ;
/*!50003 SET collation_connection  = latin1_swedish_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `validar_codigo_usuario_update` BEFORE UPDATE ON `usuario` FOR EACH ROW BEGIN
    IF (NEW.codigo_conductor IS NOT NULL AND EXISTS (
        SELECT 1 FROM usuario WHERE id <> NEW.id AND codigo_socio = NEW.codigo_conductor
    )) OR (NEW.codigo_socio IS NOT NULL AND EXISTS (
        SELECT 1 FROM usuario WHERE id <> NEW.id AND codigo_conductor = NEW.codigo_socio
    )) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El codigo de usuario ya existe en otro rol';
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuario_bus`
--

LOCK TABLES `usuario_bus` WRITE;
/*!40000 ALTER TABLE `usuario_bus` DISABLE KEYS */;
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
/*!40000 ALTER TABLE `usuario_permiso_modulo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'sistema_minutos_db'
--
/*!50106 SET @save_time_zone= @@TIME_ZONE */ ;
/*!50106 DROP EVENT IF EXISTS `cerrar_turnos_diarios` */;
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
/*!50106 CREATE*/ /*!50117 DEFINER=`root`@`localhost`*/ /*!50106 EVENT `cerrar_turnos_diarios` ON SCHEDULE EVERY 1 MINUTE STARTS '2026-09-08 09:45:11' ON COMPLETION PRESERVE ENABLE DO UPDATE turno
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

-- Dump completed on 2026-09-10 16:04:38
