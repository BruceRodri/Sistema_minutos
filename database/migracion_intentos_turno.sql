-- Registra los intentos fallidos de apertura de turno para auditoría administrativa.
USE sistema_minutos_db;

CREATE TABLE IF NOT EXISTS intento_turno (
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
    CONSTRAINT fk_intento_turno_usuario FOREIGN KEY (usuario_id) REFERENCES usuario(id),
    CONSTRAINT fk_intento_turno_bus FOREIGN KEY (bus_id) REFERENCES bus(id)
);
