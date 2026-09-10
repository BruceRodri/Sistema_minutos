<?php
session_start();
require_once __DIR__ . '/../Config/conexion.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
if (empty($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Acceso denegado']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}
$token = $_POST['csrf_token'] ?? null;
if (!is_string($token) || empty($_SESSION['csrf_cumpleanos']) || !hash_equals($_SESSION['csrf_cumpleanos'], $token)) {
    http_response_code(419);
    echo json_encode(['error' => 'Recarga la página']);
    exit;
}
$usuarioId = (int)$_SESSION['usuario_id'];
session_write_close();
$hoy = new DateTimeImmutable('now', new DateTimeZone('America/Guayaquil'));
$stmt = $conexion->prepare('SELECT id, nombres, apellidos FROM usuario WHERE activo = 1 AND MONTH(fecha_nacimiento) = ? AND DAY(fecha_nacimiento) = ? ORDER BY nombres, apellidos');
$stmt->execute([(int)$hoy->format('m'), (int)$hoy->format('d')]);
$cumpleanos = $stmt->fetchAll();
if ($cumpleanos) {
    // Reclamo atómico: una vez al día por usuario, incluso entre dispositivos.
    $marcar = $conexion->prepare('UPDATE usuario SET ultimo_aviso_cumpleanos = ? WHERE id = ? AND activo = 1 AND (ultimo_aviso_cumpleanos IS NULL OR ultimo_aviso_cumpleanos <> ?)');
    $fecha = $hoy->format('Y-m-d');
    $marcar->execute([$fecha, $usuarioId, $fecha]);
    if ($marcar->rowCount() !== 1) $cumpleanos = [];
}
echo json_encode([
    'fecha' => $hoy->format('Y-m-d'),
    'cumpleanos' => array_map(static fn($u) => [
        'nombre' => trim($u['nombres'] . ' ' . $u['apellidos']),
        'propio' => (int)$u['id'] === $usuarioId,
    ], $cumpleanos),
], JSON_UNESCAPED_UNICODE);
