<?php
// Controllers/ValoresStreamController.php
session_start();

if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['rol'] ?? '', ['admin', 'secretaria', 'operativo'], true)) {
    http_response_code(403);
    exit;
}

session_write_close();

require_once '../Config/conexion.php';
require_once '../Dao/ValoresDao.php';

header('Content-Type: text/event-stream; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('X-Accel-Buffering: no');
header('Connection: keep-alive');

while (ob_get_level() > 0) {
    ob_end_clean();
}

set_time_limit(30);
ignore_user_abort(false);

function parametroValores($nombre) {
    $valor = $_GET[$nombre] ?? '';
    return is_scalar($valor) ? trim((string)$valor) : '';
}

$filtroDisco = parametroValores('disco');
$filtroFecha = parametroValores('fecha');
$filtroValor = parametroValores('valor');
$filtroRuta = parametroValores('ruta');
$paginaSolicitada = max(1, (int)parametroValores('pagina'));
$registrosPorPagina = 20;

if ($filtroFecha !== '') {
    $fechaValida = DateTime::createFromFormat('!Y-m-d', $filtroFecha);
    if (!$fechaValida || $fechaValida->format('Y-m-d') !== $filtroFecha) {
        $filtroFecha = '';
    }
}

$valoresDao = new ValoresDao($conexion);
$ultimaFirma = null;
$inicio = time();

echo "retry: 2000\n\n";
flush();

do {
    $firma = $valoresDao->firmaArchivo();

    if ($firma !== $ultimaFirma) {
        $filas = $valoresDao->obtenerFilasFiltradas($filtroDisco, $filtroFecha, $filtroValor, $filtroRuta);
        $total = count($filas);
        $totalPaginas = max(1, (int)ceil($total / $registrosPorPagina));
        $pagina = min($paginaSolicitada, $totalPaginas);
        $offset = ($pagina - 1) * $registrosPorPagina;
        $filasPagina = array_slice($filas, $offset, $registrosPorPagina);

        $respuesta = [
            'filas' => $filasPagina,
            'total' => $total,
            'pagina' => $pagina,
            'total_paginas' => $totalPaginas,
            'primero' => $total > 0 ? $offset + 1 : 0,
            'ultimo' => min($offset + $registrosPorPagina, $total),
            'archivo_existe' => $valoresDao->archivoExiste(),
            'fecha_subida' => $valoresDao->fechaSubida()
        ];

        echo "event: valores\n";
        echo 'data: ' . json_encode($respuesta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";
        $ultimaFirma = $firma;
    } else {
        echo ": conectado\n\n";
    }

    flush();
    if (connection_aborted()) {
        break;
    }
    sleep(2);
} while ((time() - $inicio) < 25);
