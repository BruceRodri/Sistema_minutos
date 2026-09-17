<?php
// Controllers/AdminPagoController.php
session_start();
require_once '../Config/conexion.php';
require_once '../Config/permisos.php';
require_once '../Dao/PagoDao.php';
require_once '../Dao/ReporteDiferenciaDao.php';
require_once '../Dao/SaldoPagoDao.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id']) || !usuarioPuedeVerModulo($conexion, 'web_pagos')) {
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Solicitud no válida.']);
    exit;
}

$accion = $_POST['accion'] ?? '';
$pagoId = (int)($_POST['pago_id'] ?? 0);

if ($pagoId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Pago no válido.']);
    exit;
}

$pagoDao = new PagoDao($conexion);
new SaldoPagoDao($conexion);

if ($accion === 'registrar_excedente') {
    if (!in_array($_SESSION['rol'] ?? '', ['admin','secretaria'], true) ||
        empty($_SESSION['csrf_saldo']) || !is_string($_POST['csrf_token'] ?? null) ||
        !hash_equals($_SESSION['csrf_saldo'], $_POST['csrf_token'])) {
        echo json_encode(['status'=>'error','message'=>'Acceso denegado.']); exit;
    }
    $monto = (string)($_POST['saldo_favor'] ?? '');
    $nota = trim((string)($_POST['nota'] ?? ''));
    if (!preg_match('/^\d{1,7}(\.\d{1,2})?$/D', $monto) || (float)$monto <= 0 || !$nota || mb_strlen($nota)>255) {
        echo json_encode(['status'=>'error','message'=>'Ingresa un excedente positivo y una nota de hasta 255 caracteres.']); exit;
    }
    try {
        $conexion->beginTransaction();
        $q = $conexion->prepare('SELECT * FROM pago WHERE id=? AND activo=1 FOR UPDATE');
        $q->execute([$pagoId]);
        $pagoExcedente = $q->fetch();
        if (!$pagoExcedente || $pagoExcedente['estado'] === 'anulado') throw new RuntimeException('El pago no está disponible.');
        if (!PagoDao::normalizarComprobantes($pagoExcedente['comprobante'])) throw new RuntimeException('El pago debe tener un comprobante para registrar un excedente.');
        if ($pagoExcedente['estado'] !== 'aprobado') {
            $codigos = array_values(array_filter(array_map('strval', (array)($_POST['codigos'] ?? [])), static fn($c) => trim($c) !== ''));
            $validacion = $pagoDao->guardarNroComprobantes($pagoId, $codigos);
            if ($validacion['status'] !== 'success') throw new RuntimeException('Revisa los códigos de comprobante: ingresa uno por archivo, solo números y sin duplicados.');
            $aprobacion = $pagoDao->actualizarEstadoPago($pagoId, 'aprobado', null, $codigos);
            if ($aprobacion['status'] !== 'success') throw new RuntimeException('No se pudo aprobar el pago. Revisa sus comprobantes.');
        }
        $q = $conexion->prepare("INSERT INTO reporte_diferencia_pago(pago_id,usuario_id,monto_depositado,saldo_favor,estado,nota_admin,revisado_por)
            SELECT id,usuario_id,monto_total+?,?,'confirmado',?,? FROM pago WHERE id=? AND activo=1 AND estado='aprobado'");
        $q->execute([$monto,$monto,$nota,$_SESSION['usuario_id'],$pagoId]);
        if (!$q->rowCount()) throw new RuntimeException('No se pudo registrar el excedente.');
        $conexion->commit();
        echo json_encode(['status'=>'success','message'=>'Excedente registrado y pago aprobado.']);
    } catch (Throwable $e) {
        if ($conexion->inTransaction()) $conexion->rollBack();
        echo json_encode(['status'=>'error','message'=>$e instanceof RuntimeException ? $e->getMessage() : 'El pago ya tiene un excedente o no pudo guardarse.']);
    }
    exit;
}

// Los pagos vinculados a saldos conservan su estado para no duplicar créditos ni liberar deudas cubiertas.
if (in_array($accion, ['aprobar','en_espera','desaprobar','incompleto'], true)) {
    $q = $conexion->prepare("SELECT 1 FROM reporte_diferencia_pago WHERE pago_id=? AND estado='confirmado'
        UNION SELECT 1 FROM uso_saldo_pago WHERE pago_id=? AND devuelto=0");
    $q->execute([$pagoId,$pagoId]);
    if ($q->fetchColumn() && $accion !== 'aprobar') {
        echo json_encode(['status'=>'error','message'=>'Este pago tiene saldo vinculado. No puede anularse ni cambiarse a incompleto o en espera.']); exit;
    }
}

$estadoPago = $pagoDao->obtenerEstadoPago($pagoId);
if (!$estadoPago || (int)$estadoPago['activo'] !== 1) {
    echo json_encode(['status' => 'error', 'message' => 'El pago ya no está disponible.']);
    exit;
}
if ($estadoPago['estado'] === 'anulado') {
    echo json_encode(['status' => 'error', 'message' => 'Este pago está anulado y no puede editarse.']);
    exit;
}

if ($accion === 'resolver_diferencia') {
    $saldo = filter_var($_POST['saldo_favor'] ?? null, FILTER_VALIDATE_FLOAT);
    $nota = trim((string)($_POST['nota'] ?? ''));
    if ($saldo === false || $saldo < 0 || $saldo > 99999999) {
        echo json_encode(['status' => 'error', 'message' => 'Ingresa un saldo válido.']);
        exit;
    }
    if ($nota === '' || mb_strlen($nota) > 255) {
        echo json_encode(['status' => 'error', 'message' => 'Escribe una nota de hasta 255 caracteres para el usuario.']);
        exit;
    }
    if (!in_array($_SESSION['rol'] ?? '', ['admin','secretaria'], true) || empty($_SESSION['csrf_saldo']) ||
        !is_string($_POST['csrf_token'] ?? null) || !hash_equals($_SESSION['csrf_saldo'], $_POST['csrf_token'])) {
        echo json_encode(['status'=>'error','message'=>'Acceso denegado.']); exit;
    }
    try { $guardado = (new ReporteDiferenciaDao($conexion))->resolver($pagoId, (float)$saldo, $nota, (int)$_SESSION['usuario_id']); }
    catch (RuntimeException $e) { echo json_encode(['status'=>'error','message'=>$e->getMessage()]); exit; }
    echo json_encode(['status' => $guardado ? 'success' : 'error', 'message' => $guardado ? 'Diferencia revisada correctamente.' : 'No se encontró el reporte.']);
    exit;
}

if ($accion === 'guardar_comprobantes') {
    $codigos = [];
    foreach ((array)($_POST['codigos'] ?? []) as $codigo) {
        $codigo = trim((string)$codigo);
        if ($codigo !== '') {
            $codigos[] = $codigo;
        }
    }

    $resultado = $pagoDao->guardarNroComprobantes($pagoId, $codigos);
    if ($resultado['status'] === 'success') {
        echo json_encode([
            'status' => 'success',
            'message' => 'Códigos validados. Se guardarán al aprobar el pago.',
            'codigos' => $resultado['codigos']
        ]);
    } else {
        $mensajes = [
            'no_encontrado' => 'El pago ya no está disponible.',
            'comprobante_obligatorio' => 'Ingresa al menos un número de comprobante.',
            'comprobante_invalido' => 'El número de comprobante solo puede contener dígitos.',
            'muy_largo' => 'El código no puede superar los 255 caracteres en total.',
            'sin_comprobantes' => 'Este pago no tiene comprobantes subidos para asociar.',
            'cantidad_invalida' => 'Debe ingresar un número de comprobante para cada uno de los ' . ($resultado['esperado'] ?? 0) . ' archivos subidos.',
            'codigo_duplicado_sistema' => 'El número de comprobante ' . ($resultado['codigo'] ?? '') . ' ya ha sido utilizado en otro pago.',
            'codigo_duplicado' => 'El número de comprobante ' . ($resultado['codigo'] ?? '') . ' está repetido dentro del mismo pago.'
        ];
        echo json_encode(['status' => 'error', 'message' => $mensajes[$resultado['status']] ?? 'No se pudieron guardar los códigos.']);
    }
    exit;
}

if (in_array($accion, ['aprobar', 'en_espera'], true)) {
    if ($accion === 'aprobar') {
        $numero = array_values(array_filter(
            array_map(static fn($c) => trim((string)$c), (array)($_POST['codigos'] ?? [])),
            static fn($c) => $c !== ''
        ));
    } else {
        $numero = isset($_POST['codigos']) && is_string($_POST['codigos']) ? trim($_POST['codigos']) : null;
    }
    $resultado = $pagoDao->actualizarEstadoPago($pagoId, $accion === 'aprobar' ? 'aprobado' : 'en_espera', null, $numero);
    if ($resultado['status'] === 'success') {
        echo json_encode(['status' => 'success', 'message' => 'Estado del pago actualizado correctamente.']);
    } elseif ($resultado['status'] === 'no_encontrado') {
        echo json_encode(['status' => 'error', 'message' => 'El pago ya no está disponible.']);
    } else {
        $mensajes = [
            'comprobante_obligatorio' => 'Ingresa el número de comprobante antes de aprobar.',
            'comprobante_invalido' => 'El número de comprobante solo puede contener dígitos.',
            'muy_largo' => 'Los números de comprobante no pueden superar 255 caracteres.',
            'detalle_no_disponible' => 'No se pueden identificar las deudas originales de este pago. Revisa su detalle antes de cambiarlo.',
            'deuda_pagada' => 'Una deuda de este comprobante ya tiene otro pago. No se puede cobrar dos veces.',
            'sin_comprobantes' => 'Este pago no tiene comprobantes subidos para asociar.',
            'cantidad_invalida' => 'Debe ingresar un número de comprobante para cada uno de los ' . $pagoDao->contarComprobantesPago($pagoId) . ' archivos subidos.',
            'codigo_duplicado_sistema' => 'El número de comprobante ' . ($pagoDao->obtenerUltimoCodigoDuplicado() ?? '') . ' ya ha sido utilizado en otro pago.',
            'codigo_duplicado' => 'El número de comprobante ' . ($pagoDao->obtenerUltimoCodigoDuplicado() ?? '') . ' está repetido dentro del mismo pago.'
        ];
        echo json_encode(['status' => 'error', 'message' => $mensajes[$resultado['status']] ?? 'No se pudo actualizar el pago.']);
    }
    exit;
}

if ($accion === 'desaprobar') {
    $motivo = trim((string)($_POST['motivo'] ?? ''));
    if ($motivo === '') {
        echo json_encode(['status' => 'error', 'message' => 'El motivo del rechazo es obligatorio.']);
        exit;
    }
    if (mb_strlen($motivo) > 255) {
        echo json_encode(['status' => 'error', 'message' => 'El motivo no puede superar los 255 caracteres.']);
        exit;
    }

    $resultado = $pagoDao->actualizarEstadoPago($pagoId, 'anulado', $motivo);
    if ($resultado['status'] === 'success') {
        echo json_encode(['status' => 'success', 'message' => 'Pago anulado correctamente.']);
    } elseif ($resultado['status'] === 'no_encontrado') {
        echo json_encode(['status' => 'error', 'message' => 'El pago ya no está disponible.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No se pudo anular el pago.']);
    }
    exit;
}

if ($accion === 'incompleto') {
    $motivo = trim((string)($_POST['motivo'] ?? ''));
    if ($motivo === '') {
        echo json_encode(['status' => 'error', 'message' => 'El motivo del pago incompleto es obligatorio.']);
        exit;
    }
    if (mb_strlen($motivo) > 255) {
        echo json_encode(['status' => 'error', 'message' => 'El motivo no puede superar los 255 caracteres.']);
        exit;
    }

    $resultado = $pagoDao->actualizarEstadoPago($pagoId, 'incompleto', $motivo);
    if ($resultado['status'] === 'success') {
        echo json_encode(['status' => 'success', 'message' => 'Pago marcado como incompleto.']);
    } elseif ($resultado['status'] === 'no_encontrado') {
        echo json_encode(['status' => 'error', 'message' => 'El pago ya no está disponible.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No se pudo marcar el pago como incompleto.']);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Solicitud no válida.']);
