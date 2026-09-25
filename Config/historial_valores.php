<?php

// Migración explícita: se ejecuta desde scripts/actualizar_historial_valores.php.
function actualizarEsquemaHistorialValores(PDO $conexion): void {
    $columnas = $conexion->query('SHOW COLUMNS FROM obligacion_pago')->fetchAll(PDO::FETCH_COLUMN);
    $indices = $conexion->query('SHOW INDEX FROM obligacion_pago')->fetchAll(PDO::FETCH_ASSOC);
    $nombresIndices = array_column($indices, 'Key_name');
    $cambios = [];
    if (!in_array('registro_anterior_id', $columnas, true)) {
        $cambios[] = 'ADD COLUMN registro_anterior_id INT NULL';
    }
    if (!in_array('deshabilitado_en', $columnas, true)) {
        $cambios[] = 'ADD COLUMN deshabilitado_en DATETIME NULL';
    }
    if (!in_array('disco_activo', $columnas, true)) {
        $cambios[] = 'ADD COLUMN disco_activo VARCHAR(20) GENERATED ALWAYS AS (CASE WHEN activo = 1 THEN disco ELSE NULL END) STORED';
    }
    if (in_array('unq_obligacion_disco_fecha', $nombresIndices, true)) {
        $cambios[] = 'DROP INDEX unq_obligacion_disco_fecha';
    }
    if (!in_array('unq_obligacion_activa', $nombresIndices, true)) {
        $cambios[] = 'ADD UNIQUE KEY unq_obligacion_activa (disco_activo, fecha)';
    }
    if (!in_array('unq_obligacion_anterior', $nombresIndices, true)) {
        $cambios[] = 'ADD UNIQUE KEY unq_obligacion_anterior (registro_anterior_id)';
    }
    if (!in_array('idx_obligacion_disco_fecha', $nombresIndices, true)) {
        $cambios[] = 'ADD KEY idx_obligacion_disco_fecha (disco, fecha, activo)';
    }
    if ($cambios) $conexion->exec('ALTER TABLE obligacion_pago ' . implode(', ', $cambios));
}
