-- Ejecutar una sola vez. Agrega el flujo de validación de pagos del administrador.
USE sistema_minutos_db;

ALTER TABLE pago
    ADD COLUMN estado ENUM('en_espera', 'aprobado', 'anulado') NOT NULL DEFAULT 'en_espera' AFTER comprobante,
    ADD COLUMN motivo_rechazo VARCHAR(255) NULL AFTER estado;

-- Los pagos existentes (anteriores al flujo de validación) quedan como aprobados.
UPDATE pago SET estado = 'aprobado' WHERE estado = 'en_espera' AND activo = 1;