<?php
// Controllers/ValoresController.php
session_start();
require_once '../Config/conexion.php';
require_once '../Dao/ValoresDao.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['rol'] ?? '', ['admin', 'secretaria', 'operativo'])) {
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'subir') {
    if (empty($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['status' => 'error', 'message' => 'No se recibió ningún archivo.']);
        exit;
    }

    $archivo = $_FILES['archivo'];
    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

    if ($extension !== 'xlsx') {
        echo json_encode(['status' => 'error', 'message' => 'Solo se permiten archivos Excel (.xlsx).']);
        exit;
    }

    if ($archivo['size'] > 10 * 1024 * 1024) {
        echo json_encode(['status' => 'error', 'message' => 'El archivo supera el tamaño máximo de 10 MB.']);
        exit;
    }

    $destino = ValoresDao::RUTA_XLSX;
    if (!move_uploaded_file($archivo['tmp_name'], $destino)) {
        echo json_encode(['status' => 'error', 'message' => 'No se pudo guardar el archivo.']);
        exit;
    }

    $valoresDao = new ValoresDao($conexion);
    $filas = $valoresDao->leerFilas();

    if (empty($filas)) {
        echo json_encode(['status' => 'error', 'message' => 'El archivo se guardó, pero no se pudo leer ninguna fila. Verifique que las columnas sean DISCO, FECHA, VALOR y RUTA.']);
        exit;
    }

    $turnosActualizados = $valoresDao->actualizarTurnosDesdeFilas($filas);

    echo json_encode([
        'status' => 'success',
        'message' => 'Archivo de valores actualizado correctamente. ' . count($filas) . ' filas leídas y ' . $turnosActualizados . ' turno(s) actualizado(s).',
        'total_filas' => count($filas),
        'turnos_actualizados' => $turnosActualizados,
        'fecha_subida' => date('d/m/Y H:i', filemtime($destino)),
        'vista_previa' => array_slice($filas, 0, 5)
    ]);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Solicitud no válida.']);
?>
