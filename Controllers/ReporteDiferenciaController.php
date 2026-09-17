<?php
http_response_code(403);
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['status'=>'error','message'=>'Los excedentes los registra secretaría.']);
exit;
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../Config/conexion.php';
require_once __DIR__ . '/../Dao/ReporteDiferenciaDao.php';

$usuarioId = (int)($_SESSION['usuario_id'] ?? 0);
$token = is_scalar($_POST['csrf_token'] ?? null) ? (string)$_POST['csrf_token'] : '';
if ($usuarioId <= 0 || empty($_SESSION['csrf_reporte_diferencia']) || !hash_equals($_SESSION['csrf_reporte_diferencia'], $token)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Solicitud no válida.']);
    exit;
}
$pagoId = (int)($_POST['pago_id'] ?? 0);
$monto = filter_var($_POST['monto_depositado'] ?? null, FILTER_VALIDATE_FLOAT);
if ($pagoId <= 0 || $monto === false || $monto <= 0 || $monto > 99999999) {
    echo json_encode(['status' => 'error', 'message' => 'Ingresa un valor depositado válido.']);
    exit;
}
$resultado = (new ReporteDiferenciaDao($conexion))->reportar($pagoId, $usuarioId, (float)$monto);
$mensajes = [
    'success' => 'La diferencia fue reportada. Secretaría verificará el comprobante.',
    'no_disponible' => 'Este pago no está disponible para reportar.',
    'sin_excedente' => 'El valor depositado debe ser mayor que el valor del pago.',
    'ya_reportado' => 'Esta transacción ya tiene una diferencia reportada.',
    'error' => 'No se pudo registrar el reporte.'
];
echo json_encode(['status' => $resultado['status'] === 'success' ? 'success' : 'error', 'message' => $mensajes[$resultado['status']] ?? $mensajes['error']]);
