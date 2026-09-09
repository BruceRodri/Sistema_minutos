<?php
function formatearFechaPago($fecha) {
    $dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
    $ts = strtotime($fecha);
    return $dias[(int)date('w', $ts)] . ' ' . date('d/m/Y', $ts);
}
function obtenerPagablesVista($dao) {
    return array_map(static function ($p) {
        return [
            'id' => (int)$p['id'], 'fecha' => $p['fecha'], 'valor' => (float)$p['valor'],
            'ruta' => $p['ruta'] ?: 'Sin ruta', 'disco' => $p['disco'],
            'hoy' => $p['fecha'] === date('Y-m-d'),
            'fechaLegible' => formatearFechaPago($p['fecha']),
            'valorFmt' => number_format($p['valor'], 2, '.', ',')
        ];
    }, $dao->obtenerObligacionesPendientes());
}

function pagosParaVista($dao, $usuarioId) {
    $resultado = [];
    foreach ($dao->obtenerPagosConductor($usuarioId) as $p) {
        $fechasPagos = !empty($p['fechas']) ? $p['fechas'] : [$p['fecha_pago']];
        $resultado[] = [
            'id' => (int)$p['id'],
            'estado' => $p['estado'] ?? 'aprobado',
            'motivo_rechazo' => (string)($p['motivo_rechazo'] ?? ''),
            'comprobante' => (string)($p['comprobante'] ?? ''),
            'monto' => (float)$p['monto'],
            'montoFmt' => number_format((float)$p['monto'], 2, '.', ','),
            'fecha_pago' => (string)$p['fecha_pago'],
            'fechaPagoLegible' => formatearFechaPago($p['fecha_pago']),
            'dias' => count($fechasPagos),
            'discos' => array_values(array_map('strval', $p['discos'] ?? [])),
            'fechas' => array_values(array_map('strval', $fechasPagos)),
        ];
    }
    return $resultado;
}

function snapshotConductor($dao, $usuarioId) {
    return [
        'pendientes' => obtenerPagablesVista($dao),
        'pagos' => pagosParaVista($dao, $usuarioId),
    ];
}

function obtenerFiltrosPagosAdmin() {
    $filtros = [
        'conductor' => isset($_GET['conductor']) && is_scalar($_GET['conductor']) ? trim((string)$_GET['conductor']) : '',
        'disco' => isset($_GET['disco']) && is_scalar($_GET['disco']) ? trim((string)$_GET['disco']) : '',
        'fecha_desde' => isset($_GET['fecha_desde']) && is_scalar($_GET['fecha_desde']) ? trim((string)$_GET['fecha_desde']) : '',
        'fecha_hasta' => isset($_GET['fecha_hasta']) && is_scalar($_GET['fecha_hasta']) ? trim((string)$_GET['fecha_hasta']) : '',
        'ruta' => isset($_GET['ruta']) && is_scalar($_GET['ruta']) ? trim((string)$_GET['ruta']) : '',
        'estado' => isset($_GET['estado']) && is_scalar($_GET['estado']) ? trim((string)$_GET['estado']) : '',
    ];
    if (!in_array($filtros['estado'], ['en_espera', 'aprobado', 'anulado'], true)) {
        $filtros['estado'] = '';
    }
    foreach (['fecha_desde', 'fecha_hasta'] as $clave) {
        if ($filtros[$clave] !== '') {
            $fechaValida = DateTime::createFromFormat('!Y-m-d', $filtros[$clave]);
            if (!$fechaValida || $fechaValida->format('Y-m-d') !== $filtros[$clave]) {
                $filtros[$clave] = '';
            }
        }
    }
    return $filtros;
}
