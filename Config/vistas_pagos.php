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
