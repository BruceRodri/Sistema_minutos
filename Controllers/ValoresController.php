<?php
// Responder siempre JSON, incluso si PHP genera avisos al leer un Excel.
ob_start();
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
$respuestaEnviada = false;

function responderValores(array $datos, int $codigo = 200): void {
    global $respuestaEnviada;
    $respuestaEnviada = true;
    while (ob_get_level() > 0) ob_end_clean();
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

register_shutdown_function(static function () use (&$respuestaEnviada) {
    if (!$respuestaEnviada) {
        responderValores(['status' => 'error', 'message' => 'El servidor no pudo completar la operación. Revise el registro de errores de PHP y la conexión a la base de datos.'], 500);
    }
});

try {
    session_start();
    require_once __DIR__ . '/../Config/conexion.php';
    require_once __DIR__ . '/../Config/permisos.php';
    require_once __DIR__ . '/../Dao/ValoresDao.php';
    if (!isset($_SESSION['usuario_id']) || !usuarioPuedeVerModulo($conexion, 'web_valores')) {
        responderValores(['status' => 'error', 'message' => 'Su sesión expiró o no tiene acceso a Valores Diarios. Inicie sesión nuevamente.'], 403);
    }
    $metodo = $_SERVER['REQUEST_METHOD'];
    if ($metodo === 'POST' && empty($_POST) && empty($_FILES) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        throw new RuntimeException('La subida supera el tamaño de petición admitido por el servidor. Revise post_max_size en PHP.');
    }
    if ($metodo === 'POST' && (!isset($_SESSION['csrf_valores']) || !is_string($_POST['csrf'] ?? null) || !hash_equals($_SESSION['csrf_valores'], $_POST['csrf']))) {
        responderValores(['status' => 'error', 'message' => 'La página expiró. Recárguela antes de continuar.'], 403);
    }
    session_write_close();
    $dao = new ValoresDao($conexion);
    $accion = $metodo === 'GET' ? ($_GET['accion'] ?? '') : ($_POST['accion'] ?? '');
    $id = $metodo === 'GET' ? ($_GET['id'] ?? '') : ($_POST['id'] ?? '');

    if ($metodo === 'GET' && $accion === 'archivos') {
        $archivos = $conexion->query('SELECT a.id, a.nombre, a.tamano, a.insertadas, a.omitidas,
            DATE_FORMAT(a.creado_en, "%d/%m/%Y %H:%i") AS fecha,
            (SELECT COUNT(*) FROM archivo_valores_registro r INNER JOIN obligacion_pago o ON o.id=r.obligacion_id WHERE r.archivo_id=a.id AND o.activo=1) AS registros
            FROM archivo_valores a ORDER BY a.id DESC')->fetchAll();
        foreach ($archivos as &$archivoListado) $archivoListado['color'] = ValoresDao::colorArchivo($archivoListado['id']);
        unset($archivoListado);
        if (is_file(ValoresDao::RUTA_XLSX)) {
            $archivos[] = ['id' => 'anterior', 'nombre' => 'valores_diarios.xlsx (archivo anterior)',
                'tamano' => filesize(ValoresDao::RUTA_XLSX), 'fecha' => date('d/m/Y H:i', filemtime(ValoresDao::RUTA_XLSX)),
                'registros' => 0, 'anterior' => true];
        }
        $sinArchivo = $conexion->query('SELECT o.id FROM obligacion_pago o
            WHERE o.activo=1 AND NOT EXISTS (SELECT 1 FROM archivo_valores_registro r WHERE r.obligacion_id=o.id)
            ORDER BY o.id')->fetchAll(PDO::FETCH_COLUMN);
        if ($sinArchivo) {
            $archivos[] = ['id' => 'sin_archivo', 'nombre' => 'Registros sin archivo asociado',
                'sin_archivo' => true, 'registros' => count($sinArchivo), 'ids' => array_map('intval', $sinArchivo)];
        }
        responderValores(['status' => 'success', 'archivos' => $archivos]);
    }

    if ($metodo === 'GET' && $accion === 'descargar') {
        if ($id === 'anterior' && is_file(ValoresDao::RUTA_XLSX)) {
            $archivo = ['nombre' => 'valores_diarios.xlsx', 'contenido' => file_get_contents(ValoresDao::RUTA_XLSX)];
        } else {
            $stmt = $conexion->prepare('SELECT nombre, contenido FROM archivo_valores WHERE id=?');
            $stmt->execute([(int)$id]);
            $archivo = $stmt->fetch();
        }
        if (!$archivo) throw new RuntimeException('El archivo ya no existe.');
        $respuestaEnviada = true;
        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"valores.xlsx\"; filename*=UTF-8''" . rawurlencode($archivo['nombre']));
        header('X-Content-Type-Options: nosniff');
        echo $archivo['contenido'];
        exit;
    }

    if ($metodo === 'POST' && $accion === 'subir') {
        $archivo = $_FILES['archivo'] ?? null;
        if (!$archivo || is_array($archivo['name']) || $archivo['error'] !== UPLOAD_ERR_OK) {
            $codigo = $archivo['error'] ?? UPLOAD_ERR_NO_FILE;
            throw new RuntimeException(in_array($codigo, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)
                ? 'El Excel supera el límite de subida configurado en el servidor.'
                : 'No se recibió el archivo completo. Vuelva a seleccionarlo e intente nuevamente.');
        }
        if (strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION)) !== 'xlsx') {
            throw new RuntimeException('Solo se permiten archivos Excel (.xlsx).');
        }
        if ($archivo['size'] > 10 * 1024 * 1024) throw new RuntimeException('El archivo supera el máximo de 10 MB.');
        if (strlen(basename($archivo['name'])) > 255) throw new RuntimeException('El nombre del archivo es demasiado largo. Acórtelo antes de subirlo.');
        if (!is_uploaded_file($archivo['tmp_name'])) throw new RuntimeException('La subida del archivo no es válida.');

        $erroresXmlAnteriores = libxml_use_internal_errors(true);
        try {
            $filas = $dao->leerFilas($archivo['tmp_name']);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($erroresXmlAnteriores);
        }
        $validas = array_filter($filas, static fn($fila) => !empty($fila['disco']) && !empty($fila['fecha']) && (float)$fila['valor'] > 0);
        if (!$validas) throw new RuntimeException('El Excel no contiene registros válidos. Verifique DISCO, FECHA, VALOR y RUTA.');
        $contenido = file_get_contents($archivo['tmp_name']);
        if ($contenido === false) throw new RuntimeException('No se pudo leer el archivo recibido.');

        // El Excel y los registros quedan guardados juntos, o se revierte toda la carga.
        $conexion->beginTransaction();
        $stmt = $conexion->prepare('INSERT INTO archivo_valores (nombre, contenido, tamano) VALUES (?, ?, ?)');
        $stmt->execute([basename($archivo['name']), $contenido, strlen($contenido)]);
        $archivoId = (int)$conexion->lastInsertId();
        $resultado = $dao->sincronizarObligacionesDesdeFilas($filas, $archivoId);
        $stmt = $conexion->prepare('UPDATE archivo_valores SET insertadas=?, omitidas=? WHERE id=?');
        $stmt->execute([$resultado['insertadas'], $resultado['omitidas'], $archivoId]);
        $conexion->commit();
        responderValores(['status' => 'success', 'message' => 'Excel guardado correctamente.',
            'archivo_id' => $archivoId, 'insertadas' => $resultado['insertadas'], 'omitidas' => $resultado['omitidas']]);
    }

    if ($metodo === 'POST' && $accion === 'eliminar_archivo') {
        if ($id === 'anterior') {
            // El sistema anterior no registraba procedencia: no atribuirle filas por coincidencia.
            if (is_file(ValoresDao::RUTA_XLSX) && !unlink(ValoresDao::RUTA_XLSX)) throw new RuntimeException('No se pudo eliminar el archivo anterior. Revise los permisos de la carpeta data.');
            if (is_file(ValoresDao::RUTA_XLSX . '.subido') && !unlink(ValoresDao::RUTA_XLSX . '.subido')) throw new RuntimeException('El archivo se eliminó, pero no se pudo quitar su fecha de carga.');
            responderValores(['status' => 'success', 'message' => 'Archivo anterior eliminado. Para borrar los datos antiguos, use Registros sin archivo asociado en Gestión Archivos.']);
        }
        $conexion->beginTransaction();
        $stmt = $conexion->prepare('SELECT id FROM archivo_valores WHERE id=? FOR UPDATE');
        $stmt->execute([(int)$id]);
        if (!$stmt->fetch()) throw new RuntimeException('El archivo ya no existe.');
        $stmt = $conexion->prepare('UPDATE obligacion_pago o INNER JOIN archivo_valores_registro r ON r.obligacion_id=o.id SET o.activo=0, o.deshabilitado_en=NOW() WHERE r.archivo_id=? AND o.activo=1');
        $stmt->execute([(int)$id]);
        $eliminadas = $stmt->rowCount();
        $conexion->commit();
        responderValores(['status' => 'success', 'message' => $eliminadas . ' registro(s) deshabilitado(s). El Excel y el historial se conservan.']);
    }

    if ($metodo === 'POST' && $accion === 'eliminar_sin_archivo') {
        $ids = json_decode(is_string($_POST['ids'] ?? null) ? $_POST['ids'] : '', true);
        if (!is_array($ids) || !$ids) throw new RuntimeException('Actualice Gestión Archivos antes de eliminar los registros.');
        foreach ($ids as $registroId) {
            if (!is_int($registroId) || $registroId <= 0) throw new RuntimeException('La selección de registros no es válida.');
        }
        $ids = array_values(array_unique($ids));
        $conexion->beginTransaction();
        $eliminadas = 0;
        // Solo los IDs mostrados en la confirmación; excluir los que hayan adquirido un vínculo.
        foreach (array_chunk($ids, 500) as $grupo) {
            $marcadores = implode(',', array_fill(0, count($grupo), '?'));
            $stmt = $conexion->prepare("UPDATE obligacion_pago o
                LEFT JOIN archivo_valores_registro r ON r.obligacion_id=o.id
                SET o.activo=0, o.deshabilitado_en=NOW()
                WHERE o.id IN ($marcadores) AND o.activo=1 AND r.obligacion_id IS NULL");
            $stmt->execute($grupo);
            $eliminadas += $stmt->rowCount();
        }
        $conexion->commit();
        responderValores(['status' => 'success', 'message' => $eliminadas . ' registro(s) sin archivo deshabilitado(s). Se conservan en el historial.']);
    }

    if ($metodo === 'POST' && $accion === 'eliminar_registro') {
        $registro = $dao->deshabilitarRegistro((int)$id);
        responderValores(['status' => 'success', 'message' => 'Registro deshabilitado. El valor original se conserva en el historial.', 'registro' => $registro]);
    }

    if ($metodo === 'POST' && $accion === 'crear_registro') {
        $campo = static fn($nombre) => is_string($_POST[$nombre] ?? null) ? trim($_POST[$nombre]) : '';
        $disco = $dao->normalizarDisco($campo('disco'));
        $fecha = $campo('fecha');
        $valor = $campo('valor');
        $ruta = $campo('ruta');
        $archivoId = filter_var($_POST['archivo_id'] ?? 0, FILTER_VALIDATE_INT);
        $anteriorId = filter_var($_POST['registro_anterior_id'] ?? 0, FILTER_VALIDATE_INT);
        $fechaValidada = DateTime::createFromFormat('!Y-m-d', $fecha);
        if ($campo('disco') === '' || strlen($disco) > 20) throw new RuntimeException('Ingrese un disco válido de hasta 20 caracteres.');
        if (!$fechaValidada || $fechaValidada->format('Y-m-d') !== $fecha) throw new RuntimeException('Ingrese una fecha válida.');
        if (!preg_match('/^\d{1,8}(\.\d{1,2})?$/', $valor) || (float)$valor <= 0) throw new RuntimeException('Ingrese un valor mayor que cero, con máximo dos decimales.');
        if (strlen($ruta) > 100) throw new RuntimeException('La ruta debe tener como máximo 100 caracteres.');
        if ($archivoId === false || $archivoId < 0) throw new RuntimeException('El archivo asociado no es válido.');
        if ($anteriorId === false || $anteriorId < 0) throw new RuntimeException('El registro anterior no es válido.');
        $dao->crearRegistro($disco, $fecha, $valor, $ruta, $archivoId, $anteriorId);
        responderValores(['status' => 'success', 'message' => $anteriorId > 0
            ? 'Nueva versión guardada como no pagada. El valor anterior permanece en el historial.'
            : 'Registro guardado como no pagado.']);
    }
    throw new RuntimeException('Solicitud no válida.');
} catch (Throwable $e) {
    if (isset($conexion) && $conexion->inTransaction()) $conexion->rollBack();
    error_log('Valores Diarios: ' . $e->getMessage());
    $mensaje = $e instanceof PDOException
        ? 'No se pudo guardar el cambio en la base de datos. Revise el registro del servidor.'
        : ($e instanceof RuntimeException ? $e->getMessage() : 'No se pudo leer el Excel o completar la operación. Verifique que el archivo no esté dañado.');
    responderValores(['status' => 'error', 'message' => $mensaje], $e instanceof PDOException ? 500 : 400);
}
