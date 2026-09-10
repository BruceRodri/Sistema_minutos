-- Ejecutar sobre la base seleccionada. Elimina datos operativos permanentemente.
-- Conserva usuarios, buses, asignaciones usuario_bus, permisos y catálogos
-- de roles/estados necesarios para mantener los accesos existentes.
START TRANSACTION;
DELETE FROM intento_turno;
DELETE FROM turno;
DELETE FROM obligacion_pago;
DELETE FROM pago;
COMMIT;
