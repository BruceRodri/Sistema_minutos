<?php
session_start();
$uid = $_SESSION['usuario_id'] ?? null;
$rol = $_SESSION['rol'] ?? '';
if (!$uid) { http_response_code(403); exit; }
session_write_close();
require_once __DIR__ . '/../Config/conexion.php';
$stmt = $conexion->prepare('SELECT usuario_id, comprobante FROM pago WHERE id=? AND activo=1');
$stmt->execute([(int)($_GET['pago_id'] ?? 0)]);
$pago = $stmt->fetch();
if (!$pago || (!in_array($rol, ['admin', 'secretaria', 'operativo'], true) && !($rol === 'conductor' && (int)$pago['usuario_id'] === (int)$uid))) {
    http_response_code(404); exit;
}
$base = realpath(__DIR__ . '/../App/conductor/comprobantes');
$ruta = realpath(__DIR__ . '/../App/conductor/' . ($pago['comprobante'] ?? ''));
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
