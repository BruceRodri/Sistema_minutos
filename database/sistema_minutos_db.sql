-- Creación de la base de datos desde cero.

DROP DATABASE IF EXISTS sistema_minutos_db;
CREATE DATABASE sistema_minutos_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_0900_ai_ci;

USE sistema_minutos_db;

SET NAMES utf8mb4;
SET time_zone = '-05:00';

CREATE TABLE `estado_usuario` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `activo` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `rol` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `activo` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `bus` (
  `id` int NOT NULL AUTO_INCREMENT,
  `placa` varchar(20) NOT NULL,
  `disco` varchar(20) NOT NULL,
  `activo` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `placa` (`placa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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

CREATE TABLE `usuario_permiso_modulo` (
  `usuario_id` int NOT NULL,
  `modulo` varchar(40) NOT NULL,
  `habilitado` tinyint(1) NOT NULL DEFAULT '1',
  `actualizado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`usuario_id`,`modulo`),
  CONSTRAINT `fk_permiso_modulo_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('5', 'Isidro Fernando', 'Toapaxi Burgos', '2001-08-10', '2351048703', NULL, '001', NULL, '5', '1', '1', '2026-09-10');
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('6', 'Marcial Enrique', 'Pazmiño Quijije', '2000-04-23', '1719422402', NULL, '003', NULL, '5', '1', '1', NULL);
INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `fecha_nacimiento`, `cedula`, `password_hash`, `codigo_conductor`, `codigo_socio`, `rol_id`, `estado_usuario_id`, `activo`, `ultimo_aviso_cumpleanos`) VALUES ('7', 'Jordan Enrique', 'Espinosa Vinueza', '2001-08-12', '2350637217', NULL, NULL, NULL, '1', '1', '1', '2026-09-10');

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
