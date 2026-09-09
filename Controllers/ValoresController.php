<?php
// Controllers/ValoresController.php
session_start();
require_once '../Config/conexion.php';
require_once '../Config/permisos.php';
require_once '../Dao/ValoresDao.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id']) || !usuarioPuedeVerModulo($conexion, 'web_valores')) {
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado.']);
    exit;
}

session_write_close();

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

    try {
        $conexion->beginTransaction();
        $resultadoImportacion = $valoresDao->sincronizarObligacionesDesdeFilas($filas);
        $conexion->commit();
    } catch (Throwable $e) {
        if ($conexion->inTransaction()) {
            $conexion->rollBack();
        }
        error_log('Error al importar valores diarios: ' . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'No se pudieron guardar los valores en la base de datos.']);
        exit;
    }

    echo json_encode([
        'status' => 'success',
        'message' => $resultadoImportacion['insertadas'] . ' dato(s) nuevo(s) guardado(s). ' . $resultadoImportacion['omitidas'] . ' repetido(s) omitido(s).',
        'total_filas' => count($filas),
        'insertadas' => $resultadoImportacion['insertadas'],
        'omitidas' => $resultadoImportacion['omitidas'],
        'fecha_subida' => date('d/m/Y H:i', filemtime($destino)),
        'vista_previa' => array_slice($filas, 0, 5)
    ]);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Solicitud no válida.']);
?>
