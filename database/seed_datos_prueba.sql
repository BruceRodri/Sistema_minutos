-- ============================================================
-- SISTEMA DE MINUTOS - DATOS DE PRUEBA (SEED)
-- Complementa a database/sistema_minutos_db.sql (esquema 1-6)
-- MySQL 8.4
-- ============================================================

USE sistema_minutos_db;

-- ============================================================
-- USUARIOS
-- codigo_conductor: 001, 002... solo conductores
-- codigo_socio:     001, 002... solo socios
-- ============================================================
INSERT INTO usuario (id, nombres, apellidos, fecha_nacimiento, cedula, codigo_conductor, codigo_socio, rol_id, estado_usuario_id, activo) VALUES
(1, 'Bruce Leroy',        'Rodriguez Montalvan', '1990-05-14', '1234567890', '001', NULL, (SELECT id FROM rol WHERE nombre='conductor'), (SELECT id FROM estado_usuario WHERE nombre='habilitado'), 1),
(2, 'adonis vladimir',    'alegria valle',       '1985-02-20', '2300446305', NULL,  NULL, (SELECT id FROM rol WHERE nombre='admin'),       (SELECT id FROM estado_usuario WHERE nombre='habilitado'), 1),
(3, 'María Fernanda',     'López Cedeño',        '1992-11-03', '0912345678', NULL,  '001', (SELECT id FROM rol WHERE nombre='socio'),      (SELECT id FROM estado_usuario WHERE nombre='habilitado'), 1),
(4, 'Carlos Eduardo',     'Vera Pinargote',      '1980-07-18', '0923456789', NULL,  '002', (SELECT id FROM rol WHERE nombre='socio'),      (SELECT id FROM estado_usuario WHERE nombre='habilitado'), 1),
(5, 'Jorge Luis',         'Mendoza Bravo',       '1988-09-23', '1312345678', NULL,  NULL, (SELECT id FROM rol WHERE nombre='operativo'),    (SELECT id FROM estado_usuario WHERE nombre='habilitado'), 1),
(6, 'Gabriela Michelle',  'Salazar Ordoñez',     '1995-04-12', '1301234567', NULL,  NULL, (SELECT id FROM rol WHERE nombre='secretaria'),   (SELECT id FROM estado_usuario WHERE nombre='habilitado'), 1),
(7, 'Luis Alberto',       'Cedeño Anchundia',    '1990-01-15', '0987654321', '002', NULL, (SELECT id FROM rol WHERE nombre='conductor'), (SELECT id FROM estado_usuario WHERE nombre='habilitado'), 1),
(8, 'Pedro Vicente',      'Guamán Delgado',      '1985-06-30', '0967123456', '003', NULL, (SELECT id FROM rol WHERE nombre='conductor'), (SELECT id FROM estado_usuario WHERE nombre='habilitado'), 1),
(9, 'José Manuel',        'Cobeña Garcés',       '1992-11-08', '0998765432', '004', NULL, (SELECT id FROM rol WHERE nombre='conductor'), (SELECT id FROM estado_usuario WHERE nombre='habilitado'), 1),
(10, 'Felipe Andrés',     'Morales Zambrano',    '1987-03-19', '2312345678', '005', NULL, (SELECT id FROM rol WHERE nombre='conductor'), (SELECT id FROM estado_usuario WHERE nombre='habilitado'), 1),
(11, 'Rosa Elena',        'Paredes Blum',        '1998-02-28', '0965432109', NULL,  '003', (SELECT id FROM rol WHERE nombre='socio'),      (SELECT id FROM estado_usuario WHERE nombre='habilitado'), 1),
(12, 'Marco Antonio',     'Delgado Vinces',      '1983-12-05', '0982345671', NULL,  '004', (SELECT id FROM rol WHERE nombre='socio'),      (SELECT id FROM estado_usuario WHERE nombre='habilitado'), 1),
(13, 'Carmen Luisa',      'Franco Tapia',        '1994-08-17', '0985432167', NULL,  '005', (SELECT id FROM rol WHERE nombre='socio'),      (SELECT id FROM estado_usuario WHERE nombre='habilitado'), 1);

-- ============================================================
-- BUSES (5 discos)
-- ============================================================
INSERT INTO bus (placa, disco, activo) VALUES
('GHA-4587', '001', 1),
('GCZ-1290', '002', 1),
('GNA-7431', '003', 1),
('GER-0215', '004', 1),
('GBC-8830', '005', 1);

-- ============================================================
-- USUARIO_BUS (socios con sus discos)
-- ============================================================
INSERT INTO usuario_bus (usuario_id, bus_id, activo) VALUES
((SELECT id FROM usuario WHERE cedula='0912345678'), (SELECT id FROM bus WHERE disco='001'), 1),
((SELECT id FROM usuario WHERE cedula='0923456789'), (SELECT id FROM bus WHERE disco='002'), 1),
((SELECT id FROM usuario WHERE cedula='0965432109'), (SELECT id FROM bus WHERE disco='003'), 1),
((SELECT id FROM usuario WHERE cedula='0982345671'), (SELECT id FROM bus WHERE disco='004'), 1),
((SELECT id FROM usuario WHERE cedula='0985432167'), (SELECT id FROM bus WHERE disco='005'), 1);

-- ============================================================
-- TURNOS (1 por bus al dia - UNIQUE bus_id + fecha)
-- ============================================================
INSERT INTO turno (usuario_id, bus_id, fecha, hora_apertura, activo) VALUES
((SELECT id FROM usuario WHERE cedula='1234567890'), (SELECT id FROM bus WHERE disco='001'), '2026-09-08', '06:30:00', 1),
((SELECT id FROM usuario WHERE cedula='0987654321'), (SELECT id FROM bus WHERE disco='002'), '2026-09-08', '06:45:00', 1),
((SELECT id FROM usuario WHERE cedula='1234567890'), (SELECT id FROM bus WHERE disco='001'), '2026-09-07', '06:20:00', 1),
((SELECT id FROM usuario WHERE cedula='0987654321'), (SELECT id FROM bus WHERE disco='002'), '2026-09-07', '06:50:00', 1),
((SELECT id FROM usuario WHERE cedula='0967123456'), (SELECT id FROM bus WHERE disco='003'), '2026-09-05', '07:00:00', 1);