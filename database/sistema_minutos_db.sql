-- ============================================================
-- SISTEMA DE MINUTOS - ESQUEMA DE BASE DE DATOS (TABLAS 1-6)
-- MySQL 8.4
-- ============================================================

CREATE DATABASE IF NOT EXISTS sistema_minutos_db;
USE sistema_minutos_db;
SET time_zone = '-05:00';

-- 1. TABLA ROL
CREATE TABLE rol (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    activo TINYINT(1) DEFAULT 1
);

-- Roles usados por el sistema:
-- admin, operativo, secretaria, socio, conductor

-- 2. TABLA ESTADO DE USUARIO
CREATE TABLE estado_usuario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    activo TINYINT(1) DEFAULT 1
);

-- Estados sugeridos: habilitado (1), deshabilitado (2)

-- 3. TABLA USUARIO (PRINCIPAL)
CREATE TABLE usuario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombres VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    fecha_nacimiento DATE NOT NULL,
    cedula VARCHAR(20) UNIQUE NOT NULL,
    codigo_conductor VARCHAR(10) NULL UNIQUE COMMENT 'Codigo correlativo 001, 002... solo para conductores',
    codigo_socio VARCHAR(10) NULL UNIQUE COMMENT 'Codigo correlativo 001, 002... solo para socios',
    rol_id INT NOT NULL,
    estado_usuario_id INT NOT NULL,
    activo TINYINT(1) DEFAULT 1,
    FOREIGN KEY (rol_id) REFERENCES rol(id),
    FOREIGN KEY (estado_usuario_id) REFERENCES estado_usuario(id)
);

-- 4. TABLA BUS
CREATE TABLE bus (
    id INT AUTO_INCREMENT PRIMARY KEY,
    placa VARCHAR(20) UNIQUE NOT NULL,
    disco VARCHAR(20) NOT NULL,
    activo TINYINT(1) DEFAULT 1
);

-- 5. TABLA INTERMEDIA USUARIO_BUS (RELACION N:M: SOCIOS CON VARIOS BUSES)
CREATE TABLE usuario_bus (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    bus_id INT NOT NULL,
    activo TINYINT(1) DEFAULT 1,
    FOREIGN KEY (usuario_id) REFERENCES usuario(id),
    FOREIGN KEY (bus_id) REFERENCES bus(id)
);

-- 6. TABLA PAGO (REGISTRO DE UNO O VARIOS TURNOS PAGADOS)
CREATE TABLE pago (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    monto_total DECIMAL(10,2) NOT NULL,
    fecha_pago DATE NOT NULL,
    comprobante VARCHAR(255) NULL,
    activo TINYINT(1) DEFAULT 1,
    FOREIGN KEY (usuario_id) REFERENCES usuario(id)
);

-- 7. OBLIGACIONES DE PAGO (IMPORTADAS DESDE EXCEL, INDEPENDIENTES DE TURNOS)
CREATE TABLE obligacion_pago (
    id INT AUTO_INCREMENT PRIMARY KEY,
    disco VARCHAR(20) NOT NULL,
    fecha DATE NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    ruta VARCHAR(100) NULL,
    pago_id INT NULL,
    pagado TINYINT(1) NOT NULL DEFAULT 0,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY unq_obligacion_disco_fecha (disco, fecha),
    KEY idx_obligacion_pendiente (disco, pagado, activo),
    FOREIGN KEY (pago_id) REFERENCES pago(id)
);

-- 8. TABLA TURNO (UNO POR BUS Y UNO POR CONDUCTOR AL DIA)
CREATE TABLE turno (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    bus_id INT NOT NULL,
    pago_id INT NULL,
    fecha DATE NOT NULL,
    hora_apertura TIME NOT NULL,
    hora_cierre TIME NOT NULL DEFAULT '23:59:00',
    valor DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Valor tomado del archivo diario al abrir el turno',
    ruta VARCHAR(100) NULL COMMENT 'Ruta tomada del archivo diario al abrir el turno',
    activo TINYINT(1) DEFAULT 1,
    pagado TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0 = pendiente, 1 = pagado',
    FOREIGN KEY (usuario_id) REFERENCES usuario(id),
    FOREIGN KEY (bus_id) REFERENCES bus(id),
    FOREIGN KEY (pago_id) REFERENCES pago(id),
    UNIQUE KEY unq_bus_fecha (bus_id, fecha),
    UNIQUE KEY unq_conductor_fecha (usuario_id, fecha)
);

-- 9. INTENTOS FALLIDOS DE APERTURA DE TURNO
CREATE TABLE intento_turno (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    bus_id INT NULL,
    disco_escaneado VARCHAR(30) NOT NULL DEFAULT '',
    fecha DATE NOT NULL,
    hora_intento TIME NOT NULL,
    motivo VARCHAR(255) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    KEY idx_intento_turno_fecha (fecha, hora_intento),
    KEY idx_intento_turno_usuario (usuario_id, fecha),
    KEY idx_intento_turno_bus (bus_id, fecha),
    FOREIGN KEY (usuario_id) REFERENCES usuario(id),
    FOREIGN KEY (bus_id) REFERENCES bus(id)
);

-- Cierra físicamente los turnos al terminar el día en horario de Ecuador.
DROP EVENT IF EXISTS cerrar_turnos_diarios;
CREATE EVENT cerrar_turnos_diarios
    ON SCHEDULE EVERY 1 MINUTE
    ON COMPLETION PRESERVE
    ENABLE
    DO UPDATE turno
       SET activo = 0
     WHERE activo = 1
       AND TIMESTAMP(fecha, hora_cierre) <= NOW();

-- ============================================================
-- DATOS INICIALES RECOMENDADOS
-- ============================================================

INSERT INTO rol (nombre) VALUES
('admin'),
('operativo'),
('secretaria'),
('socio'),
('conductor');

INSERT INTO estado_usuario (nombre) VALUES
('habilitado'),
('deshabilitado');
