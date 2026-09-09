<?php
session_start();
require_once __DIR__ . '/../Config/conexion.php';
require_once __DIR__ . '/../Config/permisos.php';
if (!isset($_SESSION['usuario_id']) || !usuarioPuedeVerModulo($conexion, 'app_pagos')) {
    http_response_code(403); exit;
}
$uid = (int)$_SESSION['usuario_id'];
session_write_close();
require_once __DIR__ . '/../Dao/PagoDao.php';
require_once __DIR__ . '/../Config/vistas_pagos.php';
header('Content-Type: text/event-stream; charset=utf-8');
header('Cache-Control: no-cache, no-store');
header('X-Accel-Buffering: no');
while (ob_get_level() > 0) ob_end_clean();
set_time_limit(30);
$dao = new PagoDao($conexion);
$historial = ($_GET['vista'] ?? '') === 'historial';
$inicio = time();
$ultimo = null;
echo "retry: 2000\n\n";
do {
    if ($historial) {
        $pagos = $dao->obtenerPagosConductor($uid);
        $discos = $dao->obtenerTodosDiscos();
        $hash = hash('sha256', json_encode([$pagos, $discos]));
    } else {
        $pagables = obtenerPagablesVista($dao);
        $hash = hash('sha256', json_encode($pagables));
    }
    if ($hash !== $ultimo) {
        if ($historial) {
            ob_start();
            include __DIR__ . '/../App/conductor/components/tarjetas_pagos.php';
            $datos = ['hash' => $hash, 'html' => ob_get_clean(), 'discos' => $discos];
        } else {
            ob_start();
            include __DIR__ . '/../App/conductor/components/tarjetas_pagar.php';
            $tarjetas = ob_get_clean();
            ob_start();
            include __DIR__ . '/../App/conductor/components/dias_pagar.php';
            $datos = ['hash' => $hash, 'tarjetas' => $tarjetas, 'dias' => ob_get_clean()];
        }
        echo "event: pagos\n";
        echo 'data: ' . json_encode($datos, JSON_UNESCAPED_UNICODE) . "\n\n";
        $ultimo = $hash;
    } else { echo ": conectado\n\n"; }
    flush();
    if (connection_aborted()) break;
    sleep(2);
} while (time() - $inicio < 25);
