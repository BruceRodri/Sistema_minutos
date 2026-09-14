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
$fecha = $hoy->format('Y-m-d');
$stmtUsuario = $conexion->prepare('SELECT u.ultimo_aviso_cumpleanos, r.nombre AS rol FROM usuario u INNER JOIN rol r ON r.id = u.rol_id WHERE u.id = ? AND u.activo = 1 AND r.activo = 1');
$stmtUsuario->execute([$usuarioId]);
$usuario = $stmtUsuario->fetch();
if (!$usuario) {
    http_response_code(403);
    echo json_encode(['error' => 'Usuario no disponible']);
    exit;
}
$accion = $_POST['accion'] ?? 'consultar';
if ($accion === 'confirmar') {
    if (($_POST['fecha'] ?? '') !== $fecha) {
        http_response_code(422);
        echo json_encode(['error' => 'El aviso corresponde a otro día']);
        exit;
    }
    // Confirmar solo después de que el usuario cierre el aviso mostrado.
    $marcar = $conexion->prepare('UPDATE usuario SET ultimo_aviso_cumpleanos = ? WHERE id = ? AND activo = 1');
    $marcar->execute([$fecha, $usuarioId]);
    echo json_encode(['status' => 'success', 'fecha' => $fecha]);
    exit;
}
if ($accion !== 'consultar') {
    http_response_code(422);
    echo json_encode(['error' => 'Acción no válida']);
    exit;
}
$cumpleanos = [];
if ($usuario['ultimo_aviso_cumpleanos'] !== $fecha) {
    // Solo admin y secretaria reciben cumpleaños de otras personas.
    // La restricción se aplica en SQL, no únicamente al mostrar el aviso.
    $verTodos = in_array($usuario['rol'], ['admin', 'secretaria'], true);
    $sql = 'SELECT id, nombres, apellidos FROM usuario WHERE activo = 1 AND MONTH(fecha_nacimiento) = ? AND DAY(fecha_nacimiento) = ?';
    $parametros = [(int)$hoy->format('m'), (int)$hoy->format('d')];
    if (!$verTodos) {
        $sql .= ' AND id = ?';
        $parametros[] = $usuarioId;
    }
    $stmt = $conexion->prepare($sql . ' ORDER BY nombres, apellidos');
    $stmt->execute($parametros);
    $cumpleanos = $stmt->fetchAll();
}
echo json_encode([
    'fecha' => $fecha,
    'cumpleanos' => array_map(static fn($u) => [
        'nombre' => trim($u['nombres'] . ' ' . $u['apellidos']),
        'propio' => (int)$u['id'] === $usuarioId,
    ], $cumpleanos),
], JSON_UNESCAPED_UNICODE);
