<?php
// Controllers/AdminPagoController.php
session_start();
require_once '../Config/conexion.php';
require_once '../Dao/PagoDao.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['rol'] ?? '', ['admin', 'secretaria', 'operativo'], true)) {
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
    } elseif ($resultado['status'] === 'muy_largo') {
        echo json_encode(['status' => 'error', 'message' => 'El código no puede superar los 255 caracteres en total.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No se pudieron guardar los códigos.']);
    }
    exit;
}

if ($accion === 'aprobar') {
    $resultado = $pagoDao->actualizarEstadoPago($pagoId, 'aprobado');
    if ($resultado['status'] === 'success') {
        echo json_encode(['status' => 'success', 'message' => 'Pago aprobado correctamente.']);
    } elseif ($resultado['status'] === 'no_encontrado') {
        echo json_encode(['status' => 'error', 'message' => 'El pago ya no está disponible.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No se pudo aprobar el pago.']);
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