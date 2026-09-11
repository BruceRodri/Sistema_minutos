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
        'muy_grande' => 'El archivo es demasiado grande para el servidor.',
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

if ($accion === 'adjuntar_comprobante') {
    $pagoId = (int)($_POST['pago_id'] ?? 0);
    if ($pagoId <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Pago no válido.']);
        exit;
    }
    $comprobante = guardarComprobante($_SESSION['usuario_id']);
    if ($comprobante['status'] !== 'success') {
        responderErrorComprobante($comprobante['status']);
        exit;
    }

    $resultado = $pagoDao->adjuntarComprobantePago(
        $pagoId,
        $_SESSION['usuario_id'],
        $comprobante['ruta_relativa']
    );

    if ($resultado['status'] !== 'success') {
        if (is_file($comprobante['ruta_absoluta'])) {
            unlink($comprobante['ruta_absoluta']);
        }
        $mensajes = [
            'no_autorizado' => 'Este comprobante de pago no te pertenece.',
            'estado_invalido' => 'El pago ya no admite adjuntar más comprobantes.',
            'no_encontrado' => 'El pago ya no está disponible.'
        ];
        echo json_encode(['status' => 'error', 'message' => $mensajes[$resultado['status']] ?? 'No se pudo adjuntar el comprobante.']);
        exit;
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Comprobante adjuntado. El pago vuelve a estar en espera.'
    ]);
    exit;
}

if ($accion === 'completar_y_pagar') {
    $pagoIds = array_values(array_unique(array_filter(array_map(
        'intval',
        (array)($_POST['pago_ids'] ?? [])
    ))));
    $obligacionesIds = array_values(array_unique(array_filter(array_map(
        'intval',
        (array)($_POST['obligaciones_ids'] ?? [])
    ))));
    if (empty($pagoIds)) {
        echo json_encode(['status' => 'error', 'message' => 'Selecciona al menos un pago incompleto.']);
        exit;
    }
    if (empty($obligacionesIds)) {
        echo json_encode(['status' => 'error', 'message' => 'Selecciona al menos un pago pendiente.']);
        exit;
    }

    $comprobante = guardarComprobante($_SESSION['usuario_id']);
    if ($comprobante['status'] !== 'success') {
        responderErrorComprobante($comprobante['status']);
        exit;
    }

    $resultado = $pagoDao->completarPagosYRegistrar(
        $_SESSION['usuario_id'],
        $pagoIds,
        $obligacionesIds,
        $comprobante['ruta_relativa']
    );

    if ($resultado['status'] !== 'success') {
        if (is_file($comprobante['ruta_absoluta'])) {
            unlink($comprobante['ruta_absoluta']);
        }
        $mensajes = [
            'sin_pagos_incompletos' => 'Selecciona al menos un pago incompleto.',
            'pagos_invalidos' => 'Uno o más pagos ya no están disponibles.',
            'no_autorizado' => 'Uno de los pagos incompletos no te pertenece.',
            'estado_invalido' => 'Uno de los pagos ya no admite adjuntar comprobantes.',
            'obligaciones_invalidas' => 'Uno o más valores ya fueron pagados o dejaron de estar disponibles.',
            'error' => 'No se pudo registrar el pago.'
        ];
        echo json_encode(['status' => 'error', 'message' => $mensajes[$resultado['status']] ?? 'No se pudo registrar el pago.']);
        exit;
    }

    $descripcion = 'Pago incompleto completado y ' . $resultado['cantidad'] . ($resultado['cantidad'] === 1 ? ' día' : ' días') . ' agregados.';
    echo json_encode([
        'status' => 'success',
        'message' => $descripcion . ' Total: $ ' . number_format($resultado['monto'], 2, '.', ',')
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
