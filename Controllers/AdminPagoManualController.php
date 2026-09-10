<?php
// Controllers/AdminPagoManualController.php
session_start();
require_once '../Config/conexion.php';
require_once '../Config/permisos.php';
require_once '../Dao/PagoDao.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id']) || !usuarioPuedeVerModulo($conexion, 'web_pagos')) {
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado.']);
    exit;
}

$accion = ($_SERVER['REQUEST_METHOD'] === 'GET' ? $_GET : $_POST)['accion'] ?? '';

if ($accion === 'verificar_codigo_ingreso') {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        echo json_encode(['status' => 'error', 'message' => 'Solicitud no válida.']);
        exit;
    }
    $codigo = trim((string)($_GET['codigo_ingreso'] ?? ''));
    if ($codigo === '') {
        echo json_encode(['status' => 'success', 'disponible' => true]);
        exit;
    }
    if (mb_strlen($codigo) > 50) {
        echo json_encode(['status' => 'error', 'message' => 'El código no puede superar los 50 caracteres.']);
        exit;
    }
    $stmt = $conexion->prepare("SELECT COUNT(*) FROM pago WHERE codigo_ingreso = :codigo AND activo = 1");
    $stmt->execute([':codigo' => $codigo]);
    $existe = (int)$stmt->fetchColumn() > 0;
    echo json_encode(['status' => 'success', 'disponible' => !$existe]);
    exit;
}

if ($accion !== 'registrar_pago_manual') {
    echo json_encode(['status' => 'error', 'message' => 'Solicitud no válida.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Solicitud no válida.']);
    exit;
}

$pagoDao = new PagoDao($conexion);

function responderPagoManual($estado, $mensaje) {
    echo json_encode(['status' => $estado, 'message' => $mensaje]);
    exit;
}

$codigoIngreso = trim((string)($_POST['codigo_ingreso'] ?? ''));
if ($codigoIngreso === '') {
    responderPagoManual('error', 'El código de ingreso es obligatorio.');
}
if (mb_strlen($codigoIngreso) > 50) {
    responderPagoManual('error', 'El código de ingreso no puede superar los 50 caracteres.');
}
if (preg_match('/[\r\n\|]/', $codigoIngreso)) {
    responderPagoManual('error', 'El código de ingreso contiene caracteres no válidos.');
}

$disco = preg_replace('/\D+/', '', (string)($_POST['disco'] ?? ''));
$disco = $disco === '' ? '' : str_pad((string)(int)$disco, 2, '0', STR_PAD_LEFT);
if ($disco === '') {
    responderPagoManual('error', 'El disco es obligatorio.');
}
$stmt = $conexion->prepare("SELECT id FROM bus WHERE disco = :disco AND activo = 1 LIMIT 1");
$stmt->execute([':disco' => $disco]);
if (!$stmt->fetch()) {
    responderPagoManual('error', 'El disco ingresado no corresponde a un bus activo.');
}

$obligacionesIds = array_values(array_filter(array_map('intval', (array)($_POST['obligaciones'] ?? []))));
if (empty($obligacionesIds)) {
    responderPagoManual('error', 'Selecciona al menos un día para pagar.');
}

$archivo = $_FILES['comprobante'] ?? ($_FILES['archivo'] ?? null);
if (!$archivo || $archivo['error'] !== UPLOAD_ERR_OK) {
    responderPagoManual('error', 'No se recibió el comprobante.');
}
if ($archivo['size'] > 5 * 1024 * 1024) {
    responderPagoManual('error', 'El archivo supera el tamaño máximo de 5 MB.');
}
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($archivo['tmp_name']);
$extensiones = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
    'application/pdf' => 'pdf'
];
if (!isset($extensiones[$mime])) {
    responderPagoManual('error', 'Solo se permiten imágenes JPG, PNG, WEBP y documentos PDF.');
}

$carpeta = __DIR__ . '/../App/conductor/comprobantes/';
if (!is_dir($carpeta) && !mkdir($carpeta, 0755, true) && !is_dir($carpeta)) {
    responderPagoManual('error', 'No se pudo guardar el comprobante.');
}
$nombre = 'comprobante_' . (int)$_SESSION['usuario_id'] . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6));
$nombre .= '.' . $extensiones[$mime];
$rutaAbsoluta = $carpeta . $nombre;
if (!move_uploaded_file($archivo['tmp_name'], $rutaAbsoluta)) {
    responderPagoManual('error', 'No se pudo guardar el comprobante.');
}

$resultado = $pagoDao->registrarPagoManual(
    [
        'obligaciones_ids' => $obligacionesIds,
        'codigo_ingreso' => $codigoIngreso
    ],
    'comprobantes/' . $nombre
);

if ($resultado['status'] !== 'success') {
    if (is_file($rutaAbsoluta)) {
        unlink($rutaAbsoluta);
    }
    $mensajes = [
        'codigo_duplicado' => 'El código de ingreso ya fue utilizado. Debe ser único y no repetirse.',
        'sin_obligaciones' => 'Selecciona al menos un día para pagar.',
        'obligaciones_invalidas' => 'Uno o más días ya no están disponibles para pagar. Recarga la página.',
        'conductor_no_encontrado' => 'El disco no tiene un conductor asignado. Asigna el bus a un conductor para registrar el pago.',
        'conductor_ambiguo' => 'El disco tiene más de un conductor asignado. Verifica la asignación del bus.'
    ];
    responderPagoManual('error', $mensajes[$resultado['status']] ?? 'No se pudo registrar el pago manual.');
}

$fechas = array_map(
    static fn($fecha) => date('d/m/Y', strtotime($fecha)),
    $resultado['fechas'] ?? []
);

$discos = array_values(array_map('strval', $resultado['discos'] ?? []));
if (!$discos) $discos = [$discos[0] ?? $disco];

echo json_encode([
    'status' => 'success',
    'message' => 'Pago manual registrado y aprobado automáticamente.',
    'pago_id' => (int)$resultado['pago_id'],
    'disco' => $discos[0] ?? $disco,
    'discos' => $discos,
    'fechas' => $fechas,
    'rutas' => array_values($resultado['rutas'] ?? []),
    'valor' => number_format((float)$resultado['monto'], 2, '.', ','),
    'estado' => 'aprobado',
    'codigo_ingreso' => $codigoIngreso,
    'obligaciones_ids' => $obligacionesIds
]);