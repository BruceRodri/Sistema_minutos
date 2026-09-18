<?php
// Se incluye desde cada vista, después de sus permisos y consultas filtradas.
if (($_GET['exportar'] ?? '') !== '1') return;
require_once __DIR__ . '/exportar_excel.php';
$moduloExportacion = basename($_SERVER['SCRIPT_NAME'], '.php');
$celdasCombinadas = [];
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
        $listaNros = static function ($valor): array {
            $valor = trim((string)$valor);
            if ($valor === '') return [''];
            $decodificado = json_decode($valor, true);
            $nros = is_array($decodificado)
                ? array_map('strval', $decodificado)
                : preg_split('/\s*\|\s*/', $valor);
            $nros = array_values(array_filter(array_map('trim', $nros), static fn($c) => $c !== ''));
            return $nros ?: [''];
        };
        if ($manual) {
            $moduloExportacion = 'pagos-manuales';
            $cabecera = ['Código de ingreso','Disco','Fecha de obligación','Ruta','Valor del día','Valor total','Fecha de registro','Estado','Motivo'];
            foreach ($pagosManuales as $p) {
                $valoresDia = [];
                foreach ($p['valores_individuales'] ?? [] as $v) {
                    $f = (string)($v['fecha'] ?? '');
                    $valoresDia[$f] = round(($valoresDia[$f] ?? 0) + (float)($v['valor'] ?? 0), 2);
                }
                $filasDetalle = $p['detalle_filas'] ?? [];
                if (empty($filasDetalle)) $filasDetalle = [['fecha' => '', 'disco' => '', 'ruta' => '']];
                $primeraFilaExcel = count($datos) + 2;
                if (count($filasDetalle) > 1) {
                    $celdasCombinadas[] = 'F'.$primeraFilaExcel.':F'.($primeraFilaExcel + count($filasDetalle) - 1);
                }
                foreach ($filasDetalle as $i => $fila) {
                    $fecha = (string)($fila['fecha'] ?? '');
                    $valor = isset($valoresDia[$fecha]) ? $valoresDia[$fecha] : round((float)$p['monto_total'], 2);
                    $datos[] = [
                        $p['codigo_ingreso'],
                        (string)($fila['disco'] ?? ''),
                        $fecha,
                        (string)($fila['ruta'] ?? ''),
                        (float)$valor,
                        $i === 0 ? round((float)$p['monto_total'], 2) : '',
                        $p['fecha_pago'],
                        $estadoExcel($p['estado']),
                        $p['motivo_rechazo'] ?? ''
                    ];
                }
            }
        } else {
            $cabecera = ['Conductor','Código','Disco','Fecha de obligación','Ruta','Valor del día','Valor total','Fecha de registro','Estado','N.º de comprobante','Motivo'];
            foreach ($pagos as $p) {
                $valoresDia = [];
                foreach ($p['valores_individuales'] ?? [] as $v) {
                    $f = (string)($v['fecha'] ?? '');
                    $valoresDia[$f] = round(($valoresDia[$f] ?? 0) + (float)($v['valor'] ?? 0), 2);
                }
                $nros = $listaNros($p['nro_comprobante'] ?? '');
                $filasDetalle = $p['detalle_filas'] ?? [];
                if (empty($filasDetalle)) $filasDetalle = [['fecha' => '', 'disco' => '', 'ruta' => '']];
                $cantidadNros = count($nros);
                $cantidadFilas = count($filasDetalle);
                $primeraFilaExcel = count($datos) + 2;
                if ($cantidadFilas > 1) {
                    $celdasCombinadas[] = 'G'.$primeraFilaExcel.':G'.($primeraFilaExcel + $cantidadFilas - 1);
                }
                $conductor = trim(($p['nombres'] ?? '').' '.($p['apellidos'] ?? ''));
                foreach ($filasDetalle as $i => $fila) {
                    $fecha = (string)($fila['fecha'] ?? '');
                    $valor = isset($valoresDia[$fecha]) ? $valoresDia[$fecha] : round((float)$p['monto_total'], 2);
                    $indiceNro = $cantidadNros === 1 ? 0 : (int)floor($i * $cantidadNros / $cantidadFilas);
                    $nro = $nros[$indiceNro] ?? '';
                    $datos[] = [
                        $conductor,
                        $p['codigo_conductor'] ?? '',
                        (string)($fila['disco'] ?? ''),
                        $fecha,
                        (string)($fila['ruta'] ?? ''),
                        (float)$valor,
                        $i === 0 ? round((float)$p['monto_total'], 2) : '',
                        $p['fecha_pago'],
                        $estadoExcel($p['estado']),
                        $nro,
                        $p['motivo_rechazo'] ?? ''
                    ];
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
descargarExcel($moduloExportacion, $cabecera, $datos, $celdasCombinadas);
