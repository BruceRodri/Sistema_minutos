<?php
// Controllers/AdminPagoController.php
session_start();
require_once '../Config/conexion.php';
require_once '../Config/permisos.php';
require_once '../Dao/PagoDao.php';

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

$estadoPago = $pagoDao->obtenerEstadoPago($pagoId);
if (!$estadoPago || (int)$estadoPago['activo'] !== 1) {
    echo json_encode(['status' => 'error', 'message' => 'El pago ya no está disponible.']);
    exit;
}
if ($estadoPago['estado'] === 'anulado') {
    echo json_encode(['status' => 'error', 'message' => 'Este pago está anulado y no puede editarse.']);
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
            'message' => 'Códigos de comprobante guardados.',
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
