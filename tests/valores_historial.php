<?php
// Integración MySQL y exportación real. Nunca modifica tablas de la base de la aplicación.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

if (($argv[1] ?? '') === 'exportar') {
    $_GET['exportar'] = '1';
    $_SERVER['SCRIPT_NAME'] = '/Web/admin/valores.php';
    $base = ['ruta' => 'Ruta de prueba', 'pagado' => 0, 'activo' => 1];
    $filas = [
        ['id' => 4, 'disco' => '02', 'fecha' => '2026-09-25', 'valor' => '5.00'] + $base,
        ['id' => 3, 'disco' => '02', 'fecha' => '2026-09-24', 'valor' => '4.00'] + $base,
        ['id' => 1, 'disco' => '10', 'fecha' => '2026-09-23', 'valor' => '1.00'] + $base,
        ['id' => 2, 'disco' => '02', 'fecha' => '2026-09-24', 'valor' => '2.50', 'activo' => 0] + $base,
    ];
    require __DIR__ . '/../Config/exportar_modulo.php';
    exit;
}

require_once __DIR__ . '/../Config/conexion.php';
require_once __DIR__ . '/../Config/historial_valores.php';
require_once __DIR__ . '/../Dao/ValoresDao.php';

$comprobaciones = 0;
function comprobar(bool $condicion, string $mensaje): void {
    global $comprobaciones;
    if (!$condicion) throw new RuntimeException($mensaje);
    $comprobaciones++;
}
function debeFallar(callable $operacion): void {
    try { $operacion(); } catch (RuntimeException $e) { comprobar(true, 'Rechazo esperado'); return; }
    throw new RuntimeException('La operación debía rechazarse.');
}

$baseOriginal = $conexion->query('SELECT DATABASE()')->fetchColumn();
$basePrueba = 'codex_test_valores_' . bin2hex(random_bytes(8));
if (!preg_match('/^codex_test_valores_[a-f0-9]{16}$/D', $basePrueba)) throw new RuntimeException('Nombre de prueba inseguro.');
$conexion->exec("CREATE DATABASE `$basePrueba` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$rutaExcel = null;
try {
    $conexion->exec("USE `$basePrueba`");
    $conexion->exec('CREATE TABLE pago (id INT PRIMARY KEY) ENGINE=InnoDB');
    $conexion->exec('INSERT INTO pago VALUES (1)');
    // Esquema previo: la migración debe liberar la clave de registros deshabilitados.
    $conexion->exec('CREATE TABLE obligacion_pago (
        id INT AUTO_INCREMENT PRIMARY KEY, disco VARCHAR(20) NOT NULL, fecha DATE NOT NULL,
        valor DECIMAL(10,2) NOT NULL, ruta VARCHAR(100), pago_id INT NULL,
        pagado TINYINT(1) NOT NULL DEFAULT 0, activo TINYINT(1) NOT NULL DEFAULT 1,
        creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unq_obligacion_disco_fecha (disco, fecha),
        FOREIGN KEY (pago_id) REFERENCES pago(id)
    ) ENGINE=InnoDB');
    $conexion->exec("INSERT INTO obligacion_pago (disco,fecha,valor,ruta,pago_id,pagado)
        VALUES ('01','2026-09-24',2.50,'Ruta original',1,1)");
    actualizarEsquemaHistorialValores($conexion);
    actualizarEsquemaHistorialValores($conexion);
    comprobar((int)$conexion->query('SELECT COUNT(*) FROM obligacion_pago')->fetchColumn() === 1, 'Migración conserva registros');
    $dao = new ValoresDao($conexion);
    $conexion->exec("INSERT INTO archivo_valores (nombre,contenido,tamano) VALUES ('prueba.xlsx','original',8)");
    $archivoId = (int)$conexion->lastInsertId();
    $conexion->exec("INSERT INTO archivo_valores_registro VALUES ($archivoId,1)");
    $firmaAntes = $dao->firmaArchivo();
    $original = $dao->deshabilitarRegistro(1);
    comprobar((int)$original['id'] === 1 && (int)$original['archivo_id'] === $archivoId, 'Devuelve referencia original');
    comprobar($firmaAntes !== $dao->firmaArchivo(), 'Deshabilitación actualiza firma SSE');
    $fila = $conexion->query('SELECT * FROM obligacion_pago WHERE id=1')->fetch();
    comprobar($fila['valor'] === '2.50' && (int)$fila['pago_id'] === 1 && (int)$fila['pagado'] === 1, 'Conserva valor y pago');
    comprobar((int)$fila['activo'] === 0 && $fila['deshabilitado_en'] !== null, 'Guarda deshabilitación');
    debeFallar(fn() => $dao->deshabilitarRegistro(1));
    comprobar($dao->obtenerParaDiscoFecha('01', '2026-09-24') === null, 'Deshabilitado no es deuda activa');
    $nuevo = $dao->crearRegistro('01','2026-09-24','4.75','Ruta nueva',0,1);
    $fila = $conexion->query("SELECT * FROM obligacion_pago WHERE id=$nuevo")->fetch();
    comprobar((int)$fila['registro_anterior_id'] === 1 && $nuevo !== 1, 'Nueva versión vinculada');
    comprobar((int)$fila['pagado'] === 0 && $fila['pago_id'] === null, 'Nueva versión no reutiliza pago anterior');
    comprobar((int)$conexion->query("SELECT archivo_id FROM archivo_valores_registro WHERE obligacion_id=$nuevo")->fetchColumn() === $archivoId, 'Conserva procedencia del Excel');
    comprobar(count($dao->obtenerFilasFiltradas()) === 1, 'Solo activos por defecto');
    comprobar(count($dao->obtenerFilasFiltradas('', '', '', '', 'inactivos')) === 1, 'Filtro historial');
    comprobar(count($dao->obtenerFilasFiltradas('', '', '', '', 'todos')) === 2, 'Filtro todos');
    $filtradas = $dao->obtenerFilasFiltradas('01','2026-09-24','4,75','nueva');
    comprobar(count($filtradas) === 1 && (int)$filtradas[0]['registro_anterior_id'] === 1, 'Filtros combinados y versión activa');
    debeFallar(fn() => $dao->crearRegistro('01','2026-09-24','8.00','',0));
    debeFallar(fn() => $dao->crearRegistro('02','2026-09-25','8.00','',0,1));
    debeFallar(fn() => $dao->crearRegistro('02','2026-09-25','8.00','',0,$nuevo));
    debeFallar(fn() => $dao->crearRegistro('02','2026-09-25','8.00','',999999));
    comprobar((int)$conexion->query('SELECT COUNT(*) FROM obligacion_pago')->fetchColumn() === 2, 'Errores no dejan registros parciales');
    $dao->deshabilitarRegistro($nuevo);
    $tercero = $dao->crearRegistro('01','2026-09-24','6.00','Ruta tercera',0,$nuevo);
    comprobar(count($dao->obtenerFilasFiltradas('', '', '', '', 'todos')) === 3, 'Conserva múltiples versiones');
    $importadas = [['disco'=>'01','fecha'=>'2026-09-24','valor'=>'9.00','ruta'=>'Ruta importada']];
    comprobar($dao->sincronizarObligacionesDesdeFilas($importadas, $archivoId)['omitidas'] === 1, 'Importación no duplica activo');
    $dao->deshabilitarRegistro($tercero);
    comprobar($dao->sincronizarObligacionesDesdeFilas($importadas, $archivoId)['insertadas'] === 1, 'Reimportación permite nuevo activo sin perder historial');
    // Mismo SQL que la acción de gestión de archivos: el archivo y todos sus vínculos sobreviven.
    $q = $conexion->prepare('UPDATE obligacion_pago o INNER JOIN archivo_valores_registro r ON r.obligacion_id=o.id SET o.activo=0, o.deshabilitado_en=NOW() WHERE r.archivo_id=? AND o.activo=1');
    $q->execute([$archivoId]);
    comprobar($q->rowCount() === 1 && count($dao->obtenerFilasFiltradas()) === 0, 'Deshabilitación por Excel');
    comprobar((int)$conexion->query('SELECT COUNT(*) FROM archivo_valores_registro')->fetchColumn() === 4, 'Conserva todas las asociaciones');
    comprobar($conexion->query('SELECT contenido FROM archivo_valores')->fetchColumn() === 'original', 'Conserva archivo original');
    $manual = $dao->crearRegistro('02','2026-09-24','2.00','Manual',0);
    $q = $conexion->prepare('UPDATE obligacion_pago o LEFT JOIN archivo_valores_registro r ON r.obligacion_id=o.id SET o.activo=0, o.deshabilitado_en=NOW() WHERE o.id IN (?) AND o.activo=1 AND r.obligacion_id IS NULL');
    $q->execute([$manual]);
    comprobar($q->rowCount() === 1 && count($dao->obtenerFilasFiltradas('', '', '', '', 'inactivos')) === 5, 'Deshabilitación sin archivo conserva historial');

    $proceso = proc_open([PHP_BINARY, __FILE__, 'exportar'], [1=>['pipe','w'], 2=>['pipe','w']], $pipes);
    if (!is_resource($proceso)) throw new RuntimeException('No se pudo ejecutar exportación.');
    $binario = stream_get_contents($pipes[1]);
    $errores = stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]);
    comprobar(proc_close($proceso) === 0 && $errores === '', 'Exportador sin errores: ' . $errores);
    $rutaExcel = tempnam(sys_get_temp_dir(), 'test_valores_');
    file_put_contents($rutaExcel, $binario);
    $zip = new ZipArchive();
    comprobar($zip->open($rutaExcel) === true, 'Exportación XLSX válida');
    $xml = new SimpleXMLElement($zip->getFromName('xl/worksheets/sheet1.xml'));
    $zip->close();
    $xml->registerXPathNamespace('s','http://schemas.openxmlformats.org/spreadsheetml/2006/main');
    $celda = static function(string $ref) use ($xml): string {
        $nodos = $xml->xpath('//s:c[@r="'.$ref.'"]/s:v | //s:c[@r="'.$ref.'"]/s:is/s:t');
        return (string)($nodos[0] ?? '');
    };
    comprobar([$celda('C2'),$celda('C3'),$celda('C4'),$celda('C5')] === ['1','2.5','4','5'], 'Excel ordenado por fecha e ID');
    comprobar((float)$celda('B2') < (float)$celda('B3') && (float)$celda('B4') < (float)$celda('B5'), 'Fechas reales ascendentes');
    comprobar($celda('E1') === 'Pago' && $celda('F1') === 'Estado' && !isset($xml->xpath('//s:c[@r="G1"]')[0]), 'Excel sin columnas de registro anterior');
    comprobar($celda('F3') === 'Deshabilitado' && $celda('F4') === 'Activo', 'Excel evidencia historial');
    echo "OK: $comprobaciones comprobaciones de historial, pagos, filtros, importación y Excel.\n";
} finally {
    if ($conexion->inTransaction()) $conexion->rollBack();
    $conexion->exec('USE `' . str_replace('`','``',$baseOriginal) . '`');
    // Solo se elimina la base aleatoria creada arriba, nunca la base de la aplicación.
    $conexion->exec("DROP DATABASE `$basePrueba`");
    if ($rutaExcel !== null && is_file($rutaExcel)) unlink($rutaExcel);
}
