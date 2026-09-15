<?php
// Se incluye desde cada vista, después de sus permisos y consultas filtradas.
if (($_GET['exportar'] ?? '') !== '1') return;
require_once __DIR__ . '/exportar_excel.php';
$moduloExportacion = basename($_SERVER['SCRIPT_NAME'], '.php');
$estadoExcel = static fn($e) => ['en_espera'=>'En espera','aprobado'=>'Aprobado','anulado'=>'Anulado','incompleto'=>'Incompleto'][$e] ?? ucfirst((string)$e);
$seleccionExcel = static function ($id): bool {
    if (!isset($_POST['ids_filtrados'])) return true;
    static $ids = null;
    if ($ids === null) $ids = array_fill_keys(array_map('strval', (array)json_decode((string)$_POST['ids_filtrados'], true)), true);
    return isset($ids[(string)$id]);
};
switch ($moduloExportacion) {
    case 'buses':
        $cabecera = ['Disco','Placa','Estado'];
        $datos = array_map(static fn($b) => [$b['disco'],$b['placa'], $b['activo'] ? 'Activo':'Inactivo'], $listaBuses);
        break;
    case 'usuarios':
        $cabecera = ['Cédula','Nombres','Apellidos','Nacimiento','Código','Rol','Estado'];
        $datos = [];
        foreach ($usuarios as $u) if ($seleccionExcel($u['id'])) $datos[] = [$u['cedula'],$u['nombres'],$u['apellidos'],$u['fecha_nacimiento'],$u['codigo_conductor'] ?: $u['codigo_socio'],$u['rol'],$u['activo'] && $u['estado']==='habilitado' ? 'Habilitado':'Deshabilitado'];
        break;
    case 'socios':
        $cabecera = ['Cédula','Nombres','Apellidos','Disco','Placa','Estado del bus'];
        $datos = [];
        foreach ($sociosConBuses as $u) if ($seleccionExcel($u['usuario_id'])) $datos[] = [$u['cedula'],$u['nombres'],$u['apellidos'],$u['disco'],$u['placa'],$u['bus_id'] === null ? 'Sin asignación' : ($u['bus_activo'] ? 'Activo':'Inactivo')];
        break;
    case 'turnos':
        $cabecera = ['Fecha','Disco','Código conductor','Conductor','Apertura','Cierre','Estado','Motivo'];
        $datos = array_map(static fn($t) => [$t['fecha'],$t['disco'],$t['codigo_conductor'],$t['nombre_conductor'],$t['hora_apertura'],$t['hora_cierre'],$t['estado'],$t['motivo'] ?? ''], $turnos);
        break;
    case 'valores':
        $cabecera = ['Disco','Fecha','Valor','Ruta','Estado'];
        $datos = array_map(static fn($v) => [$v['disco'],$v['fecha'],(float)$v['valor'],$v['ruta'],$v['pagado'] ? 'Pagado':'Pendiente'], $filas);
        break;
    case 'pagos':
        $manual = ($_GET['seccion_exp'] ?? '') === 'manuales';
        $datos = [];
        if ($manual) {
            $moduloExportacion = 'pagos-manuales';
            $cabecera = ['Código de ingreso','Discos','Fechas de obligaciones','Rutas','Total','Fecha de registro','Estado','Motivo'];
            foreach ($pagosManuales as $p) {
                $fechas = $p['fechas'] ?? [];
                if (empty($fechas)) $fechas = [''];
                foreach ($fechas as $fecha) {
                    $datos[] = [$p['codigo_ingreso'],implode(' / ', $p['discos']),$fecha,implode(' / ', $p['rutas']),(float)$p['monto_total'],$p['fecha_pago'],$estadoExcel($p['estado']),$p['motivo_rechazo'] ?? ''];
                }
            }
        } else {
            $cabecera = ['Conductor','Código','Discos','Fechas de obligaciones','Rutas','Total','Fecha de registro','Estado','Números de comprobantes','Motivo'];
            foreach ($pagos as $p) {
                $fechas = $p['fechas'] ?? [];
                if (empty($fechas)) $fechas = [''];
                foreach ($fechas as $fecha) {
                    $conductor = trim(($p['nombres'] ?? '').' '.($p['apellidos'] ?? ''));
                    $datos[] = [$conductor,$p['codigo_conductor'] ?? '',implode(' / ', $p['discos'] ?? []),$fecha,implode(' / ', $p['rutas'] ?? []),(float)$p['monto_total'],$p['fecha_pago'],$estadoExcel($p['estado']),$p['nro_comprobante'] ?? '',$p['motivo_rechazo'] ?? ''];
                }
            }
        }
        break;
    case 'dashboard':
        $cabecera = ['Indicador','Cantidad o monto','Periodo'];
        $datos = [['Socios',$totalSocios,'Global'],['Conductores activos',$totalConductores,'Global'],['Usuarios',$totalUsuarios,'Global'],['Buses',$totalBuses,'Global'],['Buses activos',$busesActivos,'Global'],['Buses asignados',$busesAsignados,'Global'],['Socios con buses',$sociosConBuses,'Global'],['Historial de turnos',$turnosHistorial,$fechaSeleccionada],['Pagos registrados',$pagosFecha,$fechaSeleccionada],['Monto aprobado',$montoAprobadoFecha,$fechaSeleccionada]];
        foreach ($conteos as $estado => $cantidad) $datos[] = [$estadoExcel($estado),$cantidad,'Tabla principal de pagos sin filtros'];
        break;
    default: http_response_code(400); exit('Módulo no válido.');
}
descargarExcel($moduloExportacion, $cabecera, $datos);
