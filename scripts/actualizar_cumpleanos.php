<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../Config/conexion.php';
if (!$conexion->query("SHOW COLUMNS FROM usuario LIKE 'ultimo_aviso_cumpleanos'")->fetch()) {
    $conexion->exec('ALTER TABLE usuario ADD COLUMN ultimo_aviso_cumpleanos DATE NULL DEFAULT NULL');
    echo "Control diario de cumpleaños agregado.\n";
} else {
    echo "El control diario de cumpleaños ya existe.\n";
}
