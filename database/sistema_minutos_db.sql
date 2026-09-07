-- ============================================================
-- SISTEMA DE MINUTOS - ESQUEMA DE BASE DE DATOS (TABLAS 1-6)
-- MySQL 8.4
-- ============================================================

CREATE DATABASE IF NOT EXISTS sistema_minutos_db;
USE sistema_minutos_db;

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

-- 6. TABLA TURNO (1 TURNO POR BUS AL DIA)
CREATE TABLE turno (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    bus_id INT NOT NULL,
    fecha DATE NOT NULL,
    hora_apertura TIME NOT NULL,
    activo TINYINT(1) DEFAULT 1,
    FOREIGN KEY (usuario_id) REFERENCES usuario(id),
    FOREIGN KEY (bus_id) REFERENCES bus(id),
    UNIQUE KEY unq_bus_fecha (bus_id, fecha)
);

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