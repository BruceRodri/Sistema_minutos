<?php
// Actualización no destructiva de bases existentes. Solo desde la consola.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../Config/conexion.php';
$stmt = $conexion->query("SHOW COLUMNS FROM usuario LIKE 'password_hash'");
if (!$stmt->fetch()) {
    $conexion->exec('ALTER TABLE usuario ADD COLUMN password_hash VARCHAR(255) NULL DEFAULT NULL AFTER cedula');
    echo "Columna de contraseñas agregada. Usuarios existentes conservan el acceso cédula/cédula.\n";
} else {
    echo "La columna ya existe; no se modificaron contraseñas.\n";
}
