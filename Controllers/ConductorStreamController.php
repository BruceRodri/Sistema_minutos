<?php
// Controllers/ConductorStreamController.php
// SSE para la app del conductor: emite un snapshot (pendientes + historial)
// cada vez que cambia, calculando un hash SHA-256 del JSON completo.
session_start();

require_once __DIR__ . '/../Config/conexion.php';
require_once __DIR__ . '/../Config/permisos.php';

$usuarioId = (int)($_SESSION['usuario_id'] ?? 0);
if ($usuarioId <= 0 || !usuarioPuedeVerModulo($conexion, 'app_pagos')) {
    http_response_code(403);
    exit;
}
session_write_close();

require_once __DIR__ . '/../Dao/PagoDao.php';
require_once __DIR__ . '/../Config/vistas_pagos.php';

@set_time_limit(30);

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('X-Accel-Buffering: no');
header('Connection: keep-alive');

@ini_set('zlib.output_compression', '0');
@ini_set('output_buffering', 'off');
@ini_set('implicit_flush', '1');

while (ob_get_level() > 0) {
    ob_end_flush();
}

$pagoDao = new PagoDao($conexion);

function sseEmitir(array $datos): void {
    echo 'id: ' . time() . "\n";
    echo "event: snapshot\n";
    echo 'data: ' . json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";
    if (ob_get_level() > 0) {
        ob_flush();
    }
    flush();
}

echo "retry: 3000\n\n";

$ultimoHash = null;
$inicio = time();

do {
    if (connection_aborted()) {
        break;
    }

    $snapshot = snapshotConductor($pagoDao, $usuarioId);
    $hash = hash('sha256', json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

    if ($hash !== $ultimoHash) {
        $ultimoHash = $hash;
        sseEmitir([
            'tipo' => 'snapshot',
            'hash' => $hash,
            'pendientes' => $snapshot['pendientes'],
            'pagos' => $snapshot['pagos'],
            'discos' => $pagoDao->obtenerTodosDiscos(),
        ]);
    } else {
        echo ": ping\n\n";
        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }

    sleep(2);
} while (time() - $inicio < 25);
