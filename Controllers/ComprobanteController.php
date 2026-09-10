<?php
session_start();
$uid = $_SESSION['usuario_id'] ?? null;
$rol = $_SESSION['rol'] ?? '';
if (!$uid) { http_response_code(403); exit; }
session_write_close();
require_once __DIR__ . '/../Config/conexion.php';
require_once __DIR__ . '/../Dao/PagoDao.php';
$stmt = $conexion->prepare('SELECT usuario_id, comprobante, estado FROM pago WHERE id=? AND activo=1');
$stmt->execute([(int)($_GET['pago_id'] ?? 0)]);
$pago = $stmt->fetch();
if (!$pago) {
    http_response_code(404); exit;
}
// Un conductor o socio solo puede ver sus propios comprobantes; el personal web gestiona todos.
if ((int)$pago['usuario_id'] !== (int)$uid && in_array($rol, ['socio', 'conductor'], true)) {
    http_response_code(403); exit;
}
// Los pagos anulados no exponen el comprobante (archivo inválido / basura).
if ($pago['estado'] === 'anulado') {
    http_response_code(404); exit('Comprobante no disponible.');
}
$comprobantes = PagoDao::normalizarComprobantes($pago['comprobante'] ?? null);
if (!$comprobantes) {
    http_response_code(404); exit('Comprobante no disponible.');
}
$rutaRelativa = $comprobantes[0];
if (isset($_GET['archivo']) && $_GET['archivo'] !== '') {
    $archivoBuscado = basename((string)$_GET['archivo']);
    foreach ($comprobantes as $ruta) {
        if (basename($ruta) === $archivoBuscado) {
            $rutaRelativa = $ruta;
            break;
        }
    }
}
$base = realpath(__DIR__ . '/../App/conductor/comprobantes');
$ruta = realpath(__DIR__ . '/../App/conductor/' . $rutaRelativa);
if (!$base || !$ruta || !str_starts_with($ruta, $base . DIRECTORY_SEPARATOR) || !is_file($ruta)) {
    http_response_code(404); exit('Comprobante no disponible.');
}
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($ruta);
$tipos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'application/pdf' => 'pdf'];
if (!isset($tipos[$mime])) { http_response_code(415); exit; }
header('Content-Type: ' . $mime);
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
header('Content-Length: ' . filesize($ruta));
$modo = isset($_GET['descargar']) ? 'attachment' : 'inline';
header('Content-Disposition: ' . $modo . '; filename="comprobante_' . (int)$_GET['pago_id'] . '.' . $tipos[$mime] . '"');
readfile($ruta);
