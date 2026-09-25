-- Creación de la base de datos desde cero.

DROP DATABASE IF EXISTS sistema_minutos_db;
CREATE DATABASE sistema_minutos_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE sistema_minutos_db;

SET NAMES utf8mb4;
SET time_zone = '-05:00';

CREATE TABLE `estado_usuario` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `activo` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `rol` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `activo` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `bus` (
  `id` int NOT NULL AUTO_INCREMENT,
  `placa` varchar(20) NOT NULL,
  `disco` varchar(20) NOT NULL,
  `activo` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `placa` (`placa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `usuario` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombres` varchar(100) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `fecha_nacimiento` date NOT NULL,
  `cedula` varchar(20) NOT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `ultimo_aviso_cumpleanos` date DEFAULT NULL,
  `codigo_conductor` varchar(10) DEFAULT NULL,
  `codigo_socio` varchar(10) DEFAULT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `reporte_diferencia_pago` (
  `id` int NOT NULL AUTO_INCREMENT,
  `pago_id` int NOT NULL,
  `usuario_id` int NOT NULL,
  `monto_depositado` decimal(10,2) NOT NULL,
  `saldo_favor` decimal(10,2) NOT NULL DEFAULT '0.00',
  `estado` enum('pendiente','confirmado','rechazado') NOT NULL DEFAULT 'pendiente',
  `nota_admin` varchar(255) DEFAULT NULL,
  `revisado_por` int DEFAULT NULL,
  `creado_en` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_reporte_diferencia_pago` (`pago_id`),
  KEY `idx_reporte_diferencia_usuario` (`usuario_id`,`estado`),
  CONSTRAINT `fk_reporte_diferencia_pago` FOREIGN KEY (`pago_id`) REFERENCES `pago` (`id`),
  CONSTRAINT `fk_reporte_diferencia_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`),
  CONSTRAINT `fk_reporte_diferencia_revisor` FOREIGN KEY (`revisado_por`) REFERENCES `usuario` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `registro_anterior_id` int DEFAULT NULL,
  `deshabilitado_en` datetime DEFAULT NULL,
  `disco_activo` varchar(20) GENERATED ALWAYS AS (CASE WHEN activo = 1 THEN disco ELSE NULL END) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unq_obligacion_activa` (`disco_activo`,`fecha`),
  UNIQUE KEY `unq_obligacion_anterior` (`registro_anterior_id`),
  KEY `idx_obligacion_disco_fecha` (`disco`,`fecha`,`activo`),
  KEY `idx_obligacion_pendiente` (`disco`,`pagado`,`activo`),
  KEY `fk_obligacion_pago` (`pago_id`),
  CONSTRAINT `fk_obligacion_pago` FOREIGN KEY (`pago_id`) REFERENCES `pago` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `usuario_permiso_modulo` (
  `usuario_id` int NOT NULL,
  `modulo` varchar(40) NOT NULL,
  `habilitado` tinyint(1) NOT NULL DEFAULT '1',
  `actualizado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`usuario_id`,`modulo`),
  CONSTRAINT `fk_permiso_modulo_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `estado_usuario` (`id`, `nombre`, `activo`) VALUES ('1', 'habilitado', '1');
INSERT INTO `estado_usuario` (`id`, `nombre`, `activo`) VALUES ('2', 'deshabilitado', '1');
INSERT INTO `rol` (`id`, `nombre`, `activo`) VALUES ('1', 'admin', '1');
INSERT INTO `rol` (`id`, `nombre`, `activo`) VALUES ('2', 'operativo', '1');
INSERT INTO `rol` (`id`, `nombre`, `activo`) VALUES ('3', 'secretaria', '1');
INSERT INTO `rol` (`id`, `nombre`, `activo`) VALUES ('4', 'socio', '1');
INSERT INTO `rol` (`id`, `nombre`, `activo`) VALUES ('5', 'conductor', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('1', 'JAA2412', '37', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('2', 'JAA3706', '65', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('3', 'JAA1825', '94', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('4', 'JAA2843', '93', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('5', 'JAA1687', '92', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('6', 'JAA2671', '91', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('7', 'PUE0145', '90', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('8', 'JAA3506', '89', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('9', 'JAA2838', '88', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('10', 'JAA2844', '87', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('11', 'JAA3514', '85', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('12', 'JAA3523', '84', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('13', 'JAA2659', '82', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('14', 'JAA2712', '81', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('15', 'JAA3243', '80', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('16', 'JAA3498', '79', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('17', 'PUE0146', '78', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('18', 'JAA3540', '77', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('19', 'JAA1915', '76', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('20', 'JAA3588', '75', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('21', 'JAA3114', '74', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('22', 'JAA1649', '73', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('23', 'JAA2827', '72', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('24', 'JAA1686', '70', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('25', 'JAA1614', '69', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('26', 'JAA2765', '68', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('27', 'JAA3192', '67', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('28', 'JAA1564', '66', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('29', 'JAA3564', '63', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('30', 'JAA3489', '62', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('31', 'JAA1965', '61', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('32', 'JAA0199', '59', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('33', 'JAA3593', '58', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('34', 'JAA3408', '57', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('35', 'JAA2790', '56', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('36', 'JAA2758', '55', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('37', 'JAA2825', '53', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('38', 'JAA2571', '52', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('39', 'JAA2830', '35', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('40', 'JAA2683', '47', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('41', 'JAA1674', '46', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('42', 'JAA2649', '45', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('43', 'JAA3244', '44', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('44', 'JAA3592', '43', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('45', 'JAA3479', '42', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('46', 'JAA2612', '41', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('47', 'JAA2837', '40', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('48', 'JAA3319', '39', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('49', 'JAA3161', '36', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('50', 'JAA3331', '34', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('51', 'JAA3525', '33', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('52', 'JAA3516', '32', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('53', 'JAA3640', '30', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('54', 'JAA1658', '29', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('55', 'JAA1509', '28', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('56', 'JAA3363', '27', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('57', 'JAA2220', '26', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('58', 'JAA1866', '25', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('59', 'JAA3280', '23', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('60', 'JAA1688', '22', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('61', 'JAA2718', '21', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('62', 'JAA3104', '20', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('63', 'JAA3060', '19', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('64', 'JAA3527', '18', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('65', 'JAA3594', '16', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('66', 'JAA0322', '15', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('67', 'JAA2636', '14', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('68', 'JAA1855', '13', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('69', 'JAA3237', '12', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('70', 'JAA3122', '11', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('71', 'JAA2851', '10', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('72', 'JAA3577', '08', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('73', 'JAA2586', '07', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('74', 'JAA3543', '06', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('75', 'JAA2686', '05', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('76', 'JAA2673', '04', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('77', 'JAA2619', '02', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('78', 'JAA3162', '01', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('79', 'JAA2735', '83', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('80', 'JAA3542', '50', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('81', 'JAA2581', '54', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('82', 'JAA3632', '49', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('83', 'JAA2769', '31', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('84', 'JAA2597', '71', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('85', 'LBA1213', '51', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('86', 'STD1232', '64', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('87', 'STD1234', '17', '1');
INSERT INTO `bus` (`id`, `placa`, `disco`, `activo`) VALUES ('88', 'LBA1212', '09', '1');
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('1', 'Administrador', 'Sistema', '1990-01-01', '1710000017', NULL, NULL, NULL, '1', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('2', 'Operativo', 'Sistema', '1990-01-02', '1710000025', NULL, NULL, NULL, '2', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('3', 'María Viviana', 'Zambrano Granda', '1989-06-13', '1718739384', NULL, NULL, NULL, '3', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('4', 'Socio', 'Sistema', '1990-01-04', '1710000041', NULL, NULL, '002', '4', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('5', 'Isidro Fernando', 'Toapaxi Burgos', '1999-03-18', '2351048703', NULL, '001', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('6', 'Marcial Enrique', 'Pazmiño Quijije', '2000-04-23', '1719422402', NULL, '003', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('7', 'Jordan Enrique', 'Espinosa Vinueza', '2001-08-12', '2350637217', NULL, NULL, NULL, '1', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('8', 'Bryan Steven', 'Vega', '2026-09-11', '2350194037', NULL, '107', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('9', 'Angel Gustavo', 'Gonzalez Mera', '2026-09-03', '1717192593', NULL, '106', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('10', 'Elorgio Pablo', 'Torres Torres', '2026-08-17', '1706231725', NULL, NULL, '105', '4', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('11', 'Daniela Estefania', 'Guanoquiza Guanoquiza', '2026-09-01', '2300568090', NULL, NULL, '104', '4', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('12', 'Jefferson Santiago', 'Galindez Lopez', '2000-06-17', '1720650918', NULL, '103', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('13', 'Anderson Javier', 'Mero Ostaiza', '2000-02-16', '2300068190', NULL, '102', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('14', 'Danny Marcelo', 'Toledo Bravo', '2002-06-11', '0703431015', NULL, '101', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('15', 'Steven Javier', 'Burgos Velez', '1999-04-08', '1723055131', NULL, '100', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('16', 'Juan Pablo', 'Segarra Rodriguez', '1999-04-09', '1715942247', NULL, '099', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('17', 'Milton Desiderio', 'Lema Cardenas', '1999-04-06', '1721722211', NULL, '098', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('18', 'Julio Cesar', 'Loor Saltos', '1999-04-05', '2100146154', NULL, '097', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('19', 'Mario Alejandro', 'Mera Pacheco', '1999-04-04', '1720613494', NULL, '096', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('20', 'Angel Raul', 'Moposita Chipantiza', '1999-04-03', '1803671542', NULL, '095', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('21', 'Ismael Isacc', 'Fajardo Mazacon', '1999-04-02', '2350298382', NULL, '094', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('22', 'Moseis Paul', 'Cordero Galindez', '1999-04-01', '1722233234', NULL, '093', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('23', 'Jose Gilberto', 'Herrara Larcos', '1999-03-31', '0501267751', NULL, '092', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('24', 'Luis Alberto', 'Chicaiza Mindiola', '1999-03-30', '1725854168', NULL, '091', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('25', 'Holger Leornado', 'Villagran Cisneros', '1999-03-29', '1711559003', NULL, '090', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('26', 'Eduardo Cristobal', 'Peñafiel Naranjo', '1999-03-28', '1802810562', NULL, '089', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('27', 'Marco Antonio', 'Lopez Carrillo', '1999-03-27', '1714526884', NULL, '088', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('28', 'Henry Antonio', 'Delgado Vaca', '1999-03-26', '1723868632', NULL, '087', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('29', 'Jefferson Alexander', 'Soza Mena', '1999-03-25', '2350169427', NULL, '086', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('30', 'Carlos Jair', 'Cuenca Lema', '1999-03-24', '2300552243', NULL, '085', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('31', 'Jose Ricardo', 'Villaicencio Solorzano', '1999-03-23', '2350572976', NULL, '084', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('32', 'Patricio Jose', 'Ormaza Arias', '1999-03-22', '1719618900', NULL, '083', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('33', 'Mago Sebastian', 'Velez Delgado', '1999-03-21', '1306698042', NULL, '082', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('34', 'Milton David', 'Valencia Briones', '1999-03-20', '1724857105', NULL, '081', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('35', 'Italo Geovanny', 'Solorzano Parraga', '1999-03-19', '1305896332', NULL, '080', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('36', 'Luis Alberto', 'Bravo Palacios', '1999-03-17', '1718882325', NULL, '079', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('37', 'Jose Rafael', 'De La Cruz Fonseca', '1999-03-16', '1707010474', NULL, '078', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('38', 'Dario Javier', 'Rodriguez Jimenez', '1999-03-15', '1717958142', NULL, '077', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('39', 'Alex Mauricio', 'Roman Ramirez', '1999-03-14', '1724794407', NULL, '076', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('40', 'Oscar Fabian', 'Larcos Valencia', '1999-03-13', '1714309331', NULL, '075', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('41', 'Vicente Leonardo', 'Guaman Torres', '1999-03-12', '1722189410', NULL, '074', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('42', 'Franklin Javier', 'Barona Bravo', '1999-03-11', '1714843388', NULL, '073', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('43', 'Pablo Daniel', 'Ortega Iza', '1999-03-10', '1708428352', NULL, '072', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('44', 'Byron Alberto', 'Real Espinoza', '1999-03-09', '1309417879', NULL, '071', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('45', 'Edison Xavier', 'Ostaiza Pilco', '1999-03-08', '1719612150', NULL, '070', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('46', 'Jaime Oswaldo', 'Estrella Reina', '1999-03-07', '1716615966', NULL, '069', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('47', 'Francisco Javier', 'Andagoya Zambrano', '1999-03-06', '2300400351', NULL, '068', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('48', 'Kevin Alexander', 'Velez Bautista', '1999-03-05', '2350422115', NULL, '067', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('49', 'Kevin Josue', 'Aguiar Velez', '1999-03-04', '2350341703', NULL, '066', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('50', 'Alex Dario', 'Chugcho Tucta', '1999-03-03', '2350117939', NULL, '065', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('51', 'Jefferson Washinton', 'Del Valle Macias', '1999-03-02', '2300429848', NULL, '064', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('52', 'Segundo Francisco', 'Andagoya Gallegos', '1999-03-01', '1711087914', NULL, '063', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('53', 'Jefferson Jose', 'Paucar Vasquez', '1999-02-28', '1719916767', NULL, '062', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('54', 'Anival Patricio', 'Ruiz Cabrera', '1999-02-27', '1713681680', NULL, '061', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('55', 'Fredy Paul', 'Codena Serrano', '1999-02-26', '1713249629', NULL, '060', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('56', 'Franklin Mauricio', 'Conforme Garcia', '1999-02-25', '1724339344', NULL, '059', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('57', 'Jefferson Gabriel', 'Guaman Vera', '1999-02-24', '2300072408', NULL, '058', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('58', 'Edison Fabricio', 'Quevedo Espin', '1999-02-23', '1717301137', NULL, '057', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('59', 'Victor Alonso', 'Carrion Rosillo', '1999-02-22', '1715716443', NULL, '056', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('60', 'Sergio Antonio', 'Zambrano Loor', '1999-02-21', '1316295441', NULL, '055', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('61', 'Angel Steven', 'Freire Segovia', '1999-02-20', '2300428063', NULL, '054', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('62', 'Miguel Angel', 'Neira Troya', '1999-02-19', '1204886715', NULL, '053', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('63', 'Homero Antonio', 'Basurto Pinargote', '1999-02-18', '1714844535', NULL, '052', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('64', 'Milton Javier', 'Chamorro Sacon', '1999-02-17', '1719570838', NULL, '051', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('65', 'Felix Valentin', 'Cadena Montece', '1999-02-16', '1721387866', NULL, '050', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('66', 'Marcelo Lenin', 'Anchundia Sanchez', '1999-02-15', '2300035785', NULL, '049', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('67', 'Luis Freddy', 'Oña Oña', '1999-02-14', '2300071996', NULL, '048', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('68', 'Edwin Andres', 'Guanoquiza Guanoquiza', '1999-02-13', '1721176103', NULL, '047', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('69', 'Nelson Eduardo', 'Armijos Delgado', '1999-02-12', '1711923191', NULL, '046', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('70', 'Jose Miguel', 'Sanmartin Astudillo', '1999-02-11', '1720320355', NULL, '045', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('71', 'Angel Anibal', 'Jara Romero', '1999-02-10', '1713136503', NULL, '044', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('72', 'Jefferson Santiago', 'De La Cruz Guanoluisa', '1999-02-09', '2300226723', NULL, '043', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('73', 'Carlos Alberto', 'Cuenca Palma', '1999-02-08', '1718033960', NULL, '042', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('74', 'Vicente Fernado', 'Carrillo Novoa', '1999-02-07', '1716830458', NULL, '041', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('75', 'Carlos Ariel', 'Rivas Rosado', '1999-02-06', '2300598097', NULL, '040', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('76', 'Jorge Luis', 'Mero Lopez', '1999-02-05', '0802413021', NULL, '039', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('77', 'Wilman Patricio', 'Bermeo Rodriguez', '1999-02-04', '1715173280', NULL, '038', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('78', 'Luis Antonio', 'Mero Cabrera', '1999-02-03', '2300408198', NULL, '037', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('79', 'Luis Antonio', 'Mero Ostaiza', '1999-02-02', '1718101023', NULL, '036', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('80', 'Wilson Fabian', 'Toapan Oña', '1999-02-01', '1721667028', NULL, '035', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('81', 'Luis Alfredo', 'Ninasunta Toapanta', '1999-01-31', '0502419054', NULL, '034', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('82', 'Carlos Alfredo', 'Ibarra Lopez', '1999-01-30', '0801740168', NULL, '033', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('83', 'Jorgue Augusto', 'Chavez Quintanilla', '1999-01-29', '1707087241', NULL, '032', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('84', 'Damian Arnaldo', 'Mera Aveiga', '1999-01-28', '1311176562', NULL, '031', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('85', 'Jonathan Enrique', 'Garcia Cuzme', '1999-01-27', '1717461279', NULL, '030', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('86', 'Luis Alberto', 'Marcillo Cedeño', '1999-01-26', '0801673948', NULL, '029', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('87', 'Victor Andres', 'Pardo Toledo', '1999-01-25', '1724074529', NULL, '028', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('88', 'Wilson Misael', 'Orosco Moya', '1999-01-24', '1714053350', NULL, '027', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('89', 'Fabrizio Alexander', 'Collaguazo Guanca', '1999-01-23', '2350230278', NULL, '026', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('90', 'Rolando Patricio', 'Lara Haro', '1999-01-22', '0603982737', NULL, '025', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('91', 'Klever Alcides', 'Conforme Parrales', '1999-01-21', '1712108933', NULL, '024', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('92', 'Luis Javier', 'Villavicencio Sanchez', '1999-01-20', '2200439780', NULL, '023', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('93', 'Johao Cristhian', 'Zambrano Tacuri', '1999-01-19', '2300497183', NULL, '022', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('94', 'Miguel Eduardo', 'Paez Lema', '1999-01-18', '2300243504', NULL, '021', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('95', 'Antonio Jose', 'Quijije Hidalgo', '1999-01-17', '1310513864', NULL, '020', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('96', 'Edison Orlando', 'Carrera Gaibor', '1999-01-16', '1723319677', NULL, '019', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('97', 'Wilmer Fernando', 'Quiñonez Espinoza', '1999-01-15', '1718470444', NULL, '018', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('98', 'Eddison Leonardo', 'Mendoza Macias', '1999-01-14', '1311006553', NULL, '017', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('99', 'Roberto Estalin', 'Loor Valencia', '1999-01-13', '1310042708', NULL, '016', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('100', 'Manuel Mesias', 'Adagoya Gallegos', '1999-01-12', '1710808310', NULL, '015', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('101', 'Juan Wilmer', 'Cornejo Rodriguez', '1999-01-11', '1715092720', NULL, '014', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('102', 'Javier Alexander', 'Triviño Charcopa', '1999-01-10', '2300507346', NULL, '013', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('103', 'David Herna', 'Nieto Suarez', '1999-01-09', '0603184573', NULL, '012', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('104', 'William Arquimides', 'Fonseca Fonseca', '1999-01-08', '0501247464', NULL, '011', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('105', 'Angel Edwin', 'Andrade Bermeo', '1999-01-07', '0201700333', NULL, '010', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('106', 'Victor Hugo', 'Quevedo Guevara', '1999-01-06', '0602368854', NULL, '009', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('107', 'Carlos Eduardo', 'Aguilar Aguilar', '1999-01-05', '1709951741', NULL, '008', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('108', 'Memecio Bienvenido', 'Moreira Falcones', '1999-01-04', '1304279118', NULL, '007', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('109', 'Dyerik Joel', 'Ramirez Portilla', '1999-01-03', '2300061765', NULL, '006', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('110', 'Guido Mauricio', 'Espinoza Alava', '1999-01-02', '1717453763', NULL, '005', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('111', 'Xavier Leonardo', 'Mejia Rosas', '1999-01-01', '1708591274', NULL, '004', NULL, '5', '1', '1', NULL);

INSERT INTO `usuario_permiso_modulo` (`usuario_id`, `modulo`, `habilitado`, `actualizado_en`) VALUES ('3', 'app_pagos', '1', '2026-09-10 11:42:45');
INSERT INTO `usuario_permiso_modulo` (`usuario_id`, `modulo`, `habilitado`, `actualizado_en`) VALUES ('3', 'app_qr', '1', '2026-09-10 11:42:45');
INSERT INTO `usuario_permiso_modulo` (`usuario_id`, `modulo`, `habilitado`, `actualizado_en`) VALUES ('3', 'web_buses', '0', '2026-09-10 11:42:45');
INSERT INTO `usuario_permiso_modulo` (`usuario_id`, `modulo`, `habilitado`, `actualizado_en`) VALUES ('3', 'web_dashboard', '0', '2026-09-10 11:42:45');
INSERT INTO `usuario_permiso_modulo` (`usuario_id`, `modulo`, `habilitado`, `actualizado_en`) VALUES ('3', 'web_pagos', '1', '2026-09-10 11:42:45');
INSERT INTO `usuario_permiso_modulo` (`usuario_id`, `modulo`, `habilitado`, `actualizado_en`) VALUES ('3', 'web_socios', '0', '2026-09-10 11:42:45');
INSERT INTO `usuario_permiso_modulo` (`usuario_id`, `modulo`, `habilitado`, `actualizado_en`) VALUES ('3', 'web_turnos', '0', '2026-09-10 11:42:45');
INSERT INTO `usuario_permiso_modulo` (`usuario_id`, `modulo`, `habilitado`, `actualizado_en`) VALUES ('3', 'web_valores', '0', '2026-09-10 11:42:45');
INSERT INTO `usuario_permiso_modulo` (`usuario_id`, `modulo`, `habilitado`, `actualizado_en`) VALUES ('5', 'app_pagos', '1', '2026-09-10 12:27:13');
INSERT INTO `usuario_permiso_modulo` (`usuario_id`, `modulo`, `habilitado`, `actualizado_en`) VALUES ('5', 'app_qr', '1', '2026-09-10 12:27:13');
INSERT INTO `usuario_permiso_modulo` (`usuario_id`, `modulo`, `habilitado`, `actualizado_en`) VALUES ('5', 'web_buses', '0', '2026-09-10 12:27:13');
INSERT INTO `usuario_permiso_modulo` (`usuario_id`, `modulo`, `habilitado`, `actualizado_en`) VALUES ('5', 'web_dashboard', '0', '2026-09-10 12:27:13');
INSERT INTO `usuario_permiso_modulo` (`usuario_id`, `modulo`, `habilitado`, `actualizado_en`) VALUES ('5', 'web_pagos', '0', '2026-09-10 12:27:13');
INSERT INTO `usuario_permiso_modulo` (`usuario_id`, `modulo`, `habilitado`, `actualizado_en`) VALUES ('5', 'web_socios', '0', '2026-09-10 12:27:13');
INSERT INTO `usuario_permiso_modulo` (`usuario_id`, `modulo`, `habilitado`, `actualizado_en`) VALUES ('5', 'web_turnos', '0', '2026-09-10 12:27:13');
INSERT INTO `usuario_permiso_modulo` (`usuario_id`, `modulo`, `habilitado`, `actualizado_en`) VALUES ('5', 'web_valores', '0', '2026-09-10 12:27:13');

DELIMITER ;;

CREATE TRIGGER `validar_codigo_usuario_insert` BEFORE INSERT ON `usuario` FOR EACH ROW BEGIN
    IF (NEW.codigo_conductor IS NOT NULL AND EXISTS (
        SELECT 1 FROM usuario WHERE codigo_socio = NEW.codigo_conductor
    )) OR (NEW.codigo_socio IS NOT NULL AND EXISTS (
        SELECT 1 FROM usuario WHERE codigo_conductor = NEW.codigo_socio
    )) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El codigo de usuario ya existe en otro rol';
    END IF;
END ;;

CREATE TRIGGER `validar_codigo_usuario_update` BEFORE UPDATE ON `usuario` FOR EACH ROW BEGIN
    IF (NEW.codigo_conductor IS NOT NULL AND EXISTS (
        SELECT 1 FROM usuario WHERE id <> NEW.id AND codigo_socio = NEW.codigo_conductor
    )) OR (NEW.codigo_socio IS NOT NULL AND EXISTS (
        SELECT 1 FROM usuario WHERE id <> NEW.id AND codigo_conductor = NEW.codigo_socio
    )) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El codigo de usuario ya existe en otro rol';
    END IF;
END ;;

CREATE EVENT `cerrar_turnos_diarios` ON SCHEDULE EVERY 1 MINUTE STARTS CURRENT_TIMESTAMP ON COMPLETION PRESERVE ENABLE DO UPDATE turno
       SET activo = 0
     WHERE activo = 1
       AND TIMESTAMP(fecha, hora_cierre) <= NOW()  ;;

DELIMITER ;

-- Mensajes rápidos compartidos de pagos.
CREATE TABLE IF NOT EXISTS frase_pago (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    estado ENUM('incompleto', 'anulado') NOT NULL,
    texto VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO frase_pago (id, estado, texto) VALUES
(1, 'anulado', 'El comprobante no corresponde al disco o a la fecha del pago.'),
(2, 'anulado', 'El comprobante está duplicado y ya fue utilizado en otro pago.'),
(3, 'incompleto', 'El valor depositado es menor al total pendiente. Adjunte el comprobante del valor restante.'),
(4, 'incompleto', 'Falta adjuntar uno de los comprobantes para completar el pago.')
ON DUPLICATE KEY UPDATE id = frase_pago.id;
