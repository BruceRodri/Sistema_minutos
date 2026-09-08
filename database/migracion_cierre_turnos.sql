-- Ejecutar una sola vez en instalaciones existentes.
USE sistema_minutos_db;
SET time_zone = '-05:00';

ALTER TABLE turno
    ADD COLUMN hora_cierre TIME NOT NULL DEFAULT '23:59:00' AFTER hora_apertura;

UPDATE turno
SET activo = 0
WHERE activo = 1
  AND TIMESTAMP(fecha, hora_cierre) <= NOW();

DROP EVENT IF EXISTS cerrar_turnos_diarios;
CREATE EVENT cerrar_turnos_diarios
    ON SCHEDULE EVERY 1 MINUTE
    ON COMPLETION PRESERVE
    ENABLE
    DO UPDATE turno
       SET activo = 0
     WHERE activo = 1
       AND TIMESTAMP(fecha, hora_cierre) <= NOW();
