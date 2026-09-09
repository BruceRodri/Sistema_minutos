<?php
session_start();
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['rol'] ?? '', ['admin', 'secretaria', 'operativo'], true)) {
    http_response_code(403);
    exit;
}
session_write_close();
require_once __DIR__ . '/../Config/conexion.php';
require_once __DIR__ . '/../Dao/PagoDao.php';
require_once __DIR__ . '/../Config/vistas_pagos.php';
header('Content-Type: text/event-stream; charset=utf-8');
header('Cache-Control: no-cache, no-store');
header('X-Accel-Buffering: no');
while (ob_get_level() > 0) ob_end_clean();
set_time_limit(30);
$dao = new PagoDao($conexion);
$filtros = obtenerFiltrosPagosAdmin();
$inicio = time();
$ultimo = null;
echo "retry: 2000\n\n";
do {
    $pagos = $dao->obtenerPagosParaAdmin($filtros);
    $hash = hash('sha256', json_encode($pagos));
    if ($hash !== $ultimo) {
        ob_start();
        include __DIR__ . '/../Web/admin/components/filas_pagos.php';
        $html = ob_get_clean();
        echo "event: pagos\n";
        echo 'data: ' . json_encode(['hash' => $hash, 'html' => $html], JSON_UNESCAPED_UNICODE) . "\n\n";
        $ultimo = $hash;
    } else {
        echo ": conectado\n\n";
    }
    flush();
    if (connection_aborted()) break;
    sleep(2);
} while (time() - $inicio < 25);
