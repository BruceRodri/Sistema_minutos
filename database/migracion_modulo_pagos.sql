-- Ejecutar una sola vez después de las migraciones de turnos.
USE sistema_minutos_db;

CREATE TABLE pago (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    monto_total DECIMAL(10,2) NOT NULL,
    fecha_pago DATE NOT NULL,
    comprobante VARCHAR(255) NULL,
    activo TINYINT(1) DEFAULT 1,
    CONSTRAINT fk_pago_usuario FOREIGN KEY (usuario_id) REFERENCES usuario(id)
);

ALTER TABLE turno
    ADD COLUMN pago_id INT NULL AFTER bus_id,
    ADD COLUMN valor DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER hora_cierre,
    ADD COLUMN ruta VARCHAR(100) NULL AFTER valor,
    ADD COLUMN pagado TINYINT(1) NOT NULL DEFAULT 0 AFTER activo,
    ADD CONSTRAINT fk_turno_pago FOREIGN KEY (pago_id) REFERENCES pago(id);
