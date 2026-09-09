-- Ejecutar una sola vez. Permite guardar el/los códigos de comprobante del admin
-- y conservar el detalle (disco, fechas, rutas) aunque el pago sea anulado.
USE sistema_minutos_db;

ALTER TABLE pago
    ADD COLUMN nro_comprobante VARCHAR(255) NULL AFTER motivo_rechazo,
    ADD COLUMN detalle_pagos TEXT NULL AFTER nro_comprobante;