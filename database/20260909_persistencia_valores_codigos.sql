-- Migración incremental para instalaciones existentes.
ALTER TABLE obligacion_pago
    ADD COLUMN creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER activo;

-- Los códigos se asignan desde una sola secuencia global, sin importar el rol.
-- Se corrigen los códigos históricos repetidos entre socios y conductores.
-- Primero se usan valores temporales únicos para no chocar con los índices actuales.
UPDATE usuario
SET codigo_conductor = CONCAT('C', LPAD(id, 8, '0'))
WHERE codigo_conductor IS NOT NULL;

UPDATE usuario
SET codigo_socio = CONCAT('S', LPAD(id, 8, '0'))
WHERE codigo_socio IS NOT NULL;

SET @codigo_global := 0;
UPDATE usuario
SET codigo_conductor = LPAD((@codigo_global := @codigo_global + 1), 3, '0')
WHERE codigo_conductor IS NOT NULL
ORDER BY id;

UPDATE usuario
SET codigo_socio = LPAD((@codigo_global := @codigo_global + 1), 3, '0')
WHERE codigo_socio IS NOT NULL
ORDER BY id;

-- Garantía física: una sola clave única cubre ambos roles, incluso con concurrencia.
ALTER TABLE usuario
    ADD COLUMN codigo_usuario VARCHAR(10)
        GENERATED ALWAYS AS (COALESCE(codigo_conductor, codigo_socio)) STORED,
    ADD UNIQUE KEY unq_codigo_usuario (codigo_usuario);

DROP TRIGGER IF EXISTS validar_codigo_usuario_insert;
DROP TRIGGER IF EXISTS validar_codigo_usuario_update;

DELIMITER $$
CREATE TRIGGER validar_codigo_usuario_insert
BEFORE INSERT ON usuario
FOR EACH ROW
BEGIN
    IF (NEW.codigo_conductor IS NOT NULL AND EXISTS (
        SELECT 1 FROM usuario WHERE codigo_socio = NEW.codigo_conductor
    )) OR (NEW.codigo_socio IS NOT NULL AND EXISTS (
        SELECT 1 FROM usuario WHERE codigo_conductor = NEW.codigo_socio
    )) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El codigo de usuario ya existe en otro rol';
    END IF;
END$$

CREATE TRIGGER validar_codigo_usuario_update
BEFORE UPDATE ON usuario
FOR EACH ROW
BEGIN
    IF (NEW.codigo_conductor IS NOT NULL AND EXISTS (
        SELECT 1 FROM usuario WHERE id <> NEW.id AND codigo_socio = NEW.codigo_conductor
    )) OR (NEW.codigo_socio IS NOT NULL AND EXISTS (
        SELECT 1 FROM usuario WHERE id <> NEW.id AND codigo_conductor = NEW.codigo_socio
    )) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El codigo de usuario ya existe en otro rol';
    END IF;
END$$
DELIMITER ;

-- El valor solo cuenta como pagado después de la aprobación administrativa.
UPDATE obligacion_pago o
INNER JOIN pago p ON p.id = o.pago_id
SET o.pagado = IF(p.estado = 'aprobado', 1, 0)
WHERE p.activo = 1;

UPDATE turno t
INNER JOIN pago p ON p.id = t.pago_id
SET t.pagado = IF(p.estado = 'aprobado', 1, 0)
WHERE p.activo = 1;
