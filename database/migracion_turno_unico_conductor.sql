-- Ejecutar una sola vez en instalaciones existentes.
USE sistema_minutos_db;

ALTER TABLE turno
    ADD UNIQUE KEY unq_conductor_fecha (usuario_id, fecha);
