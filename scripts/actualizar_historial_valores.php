<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../Config/conexion.php';
require_once __DIR__ . '/../Config/historial_valores.php';
actualizarEsquemaHistorialValores($conexion);
echo "Historial de valores preparado. Los registros existentes se conservan.\n";
