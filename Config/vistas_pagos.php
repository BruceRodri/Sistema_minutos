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
