<?php
// Controllers/PagoController.php
session_start();
require_once '../Config/conexion.php';
require_once '../Config/permisos.php';
require_once '../Dao/PagoDao.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id']) || !usuarioPuedeVerModulo($conexion, 'app_pagos')) {
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Solicitud no válida.']);
    exit;
}

function guardarComprobante($usuarioId) {
    $archivo = $_FILES['archivo'] ?? null;
    if (!$archivo || $archivo['error'] !== UPLOAD_ERR_OK) {
        if ($archivo && $archivo['error'] === UPLOAD_ERR_INI_SIZE) {
            return ['status' => 'muy_grande'];
        }
        return ['status' => 'sin_archivo'];
    }
    if ($archivo['size'] > 5 * 1024 * 1024) {
        return ['status' => 'muy_grande'];
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($archivo['tmp_name']);
    $extensiones = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf'
    ];
    if (!isset($extensiones[$mime])) {
        return ['status' => 'tipo_invalido'];
    }

    $carpeta = __DIR__ . '/../App/conductor/comprobantes/';
    if (!is_dir($carpeta) && !mkdir($carpeta, 0755, true) && !is_dir($carpeta)) {
        return ['status' => 'error'];
    }

    $nombre = 'comprobante_' . (int)$usuarioId . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6));
    $nombre .= '.' . $extensiones[$mime];
    $rutaAbsoluta = $carpeta . $nombre;

    if (!move_uploaded_file($archivo['tmp_name'], $rutaAbsoluta)) {
        return ['status' => 'error'];
    }

    return [
        'status' => 'success',
        'ruta_relativa' => 'comprobantes/' . $nombre,
        'ruta_absoluta' => $rutaAbsoluta
    ];
}

function responderErrorComprobante($estado) {
    $mensajes = [
        'sin_archivo' => 'No se recibió el comprobante.',
        'muy_grande' => 'El archivo supera el tamaño máximo de 5 MB.',
        'tipo_invalido' => 'Solo se permiten imágenes JPG, PNG, WEBP y documentos PDF.',
        'error' => 'No se pudo guardar el comprobante.'
    ];
    echo json_encode([
        'status' => 'error',
        'message' => $mensajes[$estado] ?? 'No se pudo procesar el comprobante.'
    ]);
}

$accion = $_POST['accion'] ?? '';
$pagoDao = new PagoDao($conexion);
$obligacionesIds = [];

if ($accion === 'listar_discos') {
    $q = isset($_POST['q']) ? preg_replace('/\D+/', '', (string)$_POST['q']) : '';
    echo json_encode([
        'status' => 'success',
        'discos' => $pagoDao->obtenerTodosDiscos($q)
    ]);
    exit;
}

if ($accion === 'pagar_varios') {
    $obligacionesIds = array_values(array_unique(array_filter(array_map(
        'intval',
        (array)($_POST['obligaciones_ids'] ?? [])
    ))));
    if (empty($obligacionesIds)) {
        echo json_encode(['status' => 'error', 'message' => 'Selecciona al menos un pago pendiente.']);
        exit;
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Solicitud no válida.']);
    exit;
}

$comprobante = guardarComprobante($_SESSION['usuario_id']);
if ($comprobante['status'] !== 'success') {
    responderErrorComprobante($comprobante['status']);
    exit;
}

$resultado = $pagoDao->registrarPagoObligaciones(
    $_SESSION['usuario_id'],
    $obligacionesIds,
    $comprobante['ruta_relativa']
);

if ($resultado['status'] !== 'success') {
    if (is_file($comprobante['ruta_absoluta'])) {
        unlink($comprobante['ruta_absoluta']);
    }
    $mensaje = $resultado['status'] === 'obligaciones_invalidas'
        ? 'Uno o más valores ya fueron pagados o dejaron de estar disponibles.'
        : 'No se pudo registrar el pago.';
    echo json_encode(['status' => 'error', 'message' => $mensaje]);
    exit;
}

$descripcion = $resultado['cantidad'] === 1
    ? 'Pago registrado correctamente.'
    : 'Pago de ' . $resultado['cantidad'] . ' días registrado correctamente.';

echo json_encode([
    'status' => 'success',
    'message' => $descripcion . ' Total: $ ' . number_format($resultado['monto'], 2, '.', ',')
]);
