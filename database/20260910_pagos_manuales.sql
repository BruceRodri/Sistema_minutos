-- Compatibiliza una base existente con el módulo de pagos manuales.
-- Los pagos anteriores conservan su comportamiento y quedan marcados como "app".
ALTER TABLE pago
    ADD COLUMN tipo ENUM('app', 'manual') NOT NULL DEFAULT 'app' AFTER activo,
    ADD COLUMN codigo_ingreso VARCHAR(50) NULL AFTER tipo,
    ADD UNIQUE KEY uq_pago_codigo_ingreso (codigo_ingreso);
