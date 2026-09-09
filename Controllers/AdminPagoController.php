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
    } elseif ($resultado['status'] === 'no_encontrado') {
        echo json_encode(['status' => 'error', 'message' => 'El pago ya no está disponible.']);
    } elseif ($resultado['status'] === 'comprobante_obligatorio') {
        echo json_encode(['status' => 'error', 'message' => 'Ingresa al menos un número de comprobante.']);
    } elseif ($resultado['status'] === 'comprobante_invalido') {
        echo json_encode(['status' => 'error', 'message' => 'El número de comprobante solo puede contener dígitos.']);
    } elseif ($resultado['status'] === 'muy_largo') {
        echo json_encode(['status' => 'error', 'message' => 'El código no puede superar los 255 caracteres en total.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No se pudieron guardar los códigos.']);
    }
    exit;
}

if (in_array($accion, ['aprobar', 'en_espera'], true)) {
    $numero = isset($_POST['codigos']) && is_string($_POST['codigos']) ? trim($_POST['codigos']) : null;
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
            'deuda_pagada' => 'Una deuda de este comprobante ya tiene otro pago. No se puede cobrar dos veces.'
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

echo json_encode(['status' => 'error', 'message' => 'Solicitud no válida.']);
