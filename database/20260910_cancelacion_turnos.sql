-- Permite cancelar un turno sin perder su historial y volver a liberar
-- tanto el bus como el conductor durante el mismo día.
ALTER TABLE turno
    ADD KEY idx_turno_bus (bus_id),
    ADD KEY idx_turno_usuario (usuario_id),
    DROP INDEX unq_bus_fecha,
    DROP INDEX unq_conductor_fecha,
    ADD COLUMN cancelado_en DATETIME NULL AFTER activo,
    ADD COLUMN cancelado_por INT NULL AFTER cancelado_en,
    ADD COLUMN comentario_cancelacion VARCHAR(500) NULL AFTER cancelado_por,
    ADD COLUMN rehabilitado_en DATETIME NULL AFTER comentario_cancelacion,
    ADD COLUMN rehabilitado_por INT NULL AFTER rehabilitado_en,
    ADD COLUMN turno_rehabilitado_id INT NULL AFTER rehabilitado_por,
    ADD COLUMN bus_id_turno_activo INT
        GENERATED ALWAYS AS (CASE WHEN activo = 1 THEN bus_id ELSE NULL END) STORED AFTER pagado,
    ADD COLUMN usuario_id_turno_activo INT
        GENERATED ALWAYS AS (CASE WHEN activo = 1 THEN usuario_id ELSE NULL END) STORED AFTER bus_id_turno_activo,
    ADD UNIQUE KEY unq_bus_fecha_activo (bus_id_turno_activo, fecha),
    ADD UNIQUE KEY unq_conductor_fecha_activo (usuario_id_turno_activo, fecha);
