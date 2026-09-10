<?php
// Controllers/TurnosStreamController.php
session_start();
require_once '../Config/conexion.php';
require_once '../Config/permisos.php';

if (!isset($_SESSION['usuario_id']) || !usuarioPuedeVerModulo($conexion, 'web_turnos')) {
    http_response_code(403);
    exit;
}

// Evita mantener bloqueada la sesión durante toda la conexión SSE.
session_write_close();

require_once '../Dao/TurnoDao.php';

header('Content-Type: text/event-stream; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('X-Accel-Buffering: no');
header('Connection: keep-alive');

while (ob_get_level() > 0) {
    ob_end_clean();
}

set_time_limit(30);
ignore_user_abort(false);

$filtroDisco = trim($_GET['disco'] ?? '');
$filtroConductor = trim($_GET['conductor'] ?? '');
$filtroFecha = trim($_GET['fecha'] ?? '');
$filtroEstado = $_GET['estado'] ?? '';
if (!in_array($filtroEstado, ['abierto', 'fallido', 'deshabilitado'], true)) {
    $filtroEstado = '';
}
$paginaSolicitada = max(1, (int)($_GET['pagina'] ?? 1));
$registrosPorPagina = 20;

if ($filtroFecha !== '') {
    $fechaValida = DateTime::createFromFormat('Y-m-d', $filtroFecha);
    if (!$fechaValida || $fechaValida->format('Y-m-d') !== $filtroFecha) {
        $filtroFecha = '';
    }
}

$turnoDao = new TurnoDao($conexion);
$ultimoHash = null;
$inicio = time();

echo "retry: 2000\n\n";
flush();

do {
    $total = $turnoDao->contarTurnos($filtroDisco, $filtroConductor, $filtroFecha, $filtroEstado);
    $totalPaginas = max(1, (int)ceil($total / $registrosPorPagina));
    $pagina = min($paginaSolicitada, $totalPaginas);
    $offset = ($pagina - 1) * $registrosPorPagina;
    $turnos = $turnoDao->obtenerTurnos(
        $filtroDisco,
        $filtroConductor,
        $filtroFecha,
        $registrosPorPagina,
        $offset,
        $filtroEstado
    );
    $flota = array_map(static function ($bus) {
        return [
            'bus_id' => (int)$bus['bus_id'],
            'turno_id' => !empty($bus['turno_id']) ? (int)$bus['turno_id'] : null,
            'disco' => (string)$bus['disco'],
            'placa' => (string)$bus['placa'],
            'bus_activo' => (int)$bus['bus_activo'] === 1,
            'ocupado' => !empty($bus['turno_id']),
            'codigo_conductor' => $bus['codigo_conductor'] ?? '',
            'nombre_conductor' => trim(($bus['nombres'] ?? '') . ' ' . ($bus['apellidos'] ?? '')),
            'hora_apertura' => $bus['hora_apertura'] ?? ''
        ];
    }, $turnoDao->obtenerEstadoFlotaHoy());

    $filas = array_map(static function ($turno) {
        $fecha = DateTime::createFromFormat(
            'Y-m-d H:i:s',
            $turno['fecha'] . ' ' . $turno['hora_apertura']
        );

        return [
            'id' => (int)$turno['id'],
            'disco' => strlen((string)$turno['disco']) > 1 && (string)$turno['disco'][0] === '0'
                ? substr((string)$turno['disco'], 1)
                : (string)$turno['disco'],
            'codigo_conductor' => $turno['codigo_conductor'] ?? 'Sin código',
            'nombre_conductor' => $turno['nombre_conductor'] ?? '',
            'estado' => $turno['estado'] ?? 'abierto',
            'motivo' => $turno['motivo'] ?? '',
            'puede_habilitar' => ($turno['estado'] ?? '') === 'deshabilitado'
                && ($turno['fecha'] ?? '') === date('Y-m-d')
                && empty($turno['rehabilitado_en']),
            'fue_habilitado' => ($turno['estado'] ?? '') === 'deshabilitado'
                && !empty($turno['rehabilitado_en']),
            'conductor_duplicado' => TurnoDao::esConductorDuplicado($turno),
            'fecha_apertura' => $fecha
                ? $fecha->format('d/m/Y H:i:s')
                : $turno['fecha'] . ' ' . $turno['hora_apertura']
        ];
    }, $turnos);

    $respuesta = [
        'turnos' => $filas,
        'flota' => $flota,
        'total' => $total,
        'pagina' => $pagina,
        'total_paginas' => $totalPaginas,
        'primero' => $total > 0 ? $offset + 1 : 0,
        'ultimo' => min($offset + $registrosPorPagina, $total)
    ];
    $json = json_encode($respuesta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $hash = hash('sha256', $json);

    if ($hash !== $ultimoHash) {
        echo "event: turnos\n";
        echo 'data: ' . $json . "\n\n";
        $ultimoHash = $hash;
    } else {
        echo ": conectado\n\n";
    }

    flush();
    if (connection_aborted()) {
        break;
    }
    sleep(2);
} while ((time() - $inicio) < 25);
