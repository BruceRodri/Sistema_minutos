USE sistema_minutos_db;

-- Obligaciones financieras importadas desde el Excel del administrador.
-- Son independientes de la apertura y cierre de turnos.
CREATE TABLE IF NOT EXISTS obligacion_pago (
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
    CONSTRAINT fk_obligacion_pago FOREIGN KEY (pago_id) REFERENCES pago(id)
);
