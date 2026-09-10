<?php
session_start();
require_once __DIR__ . '/../Config/conexion.php';
require_once __DIR__ . '/../Config/credenciales.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
function responderPerfil($status, $message, $codigo = 200) {
    http_response_code($codigo);
    echo json_encode(compact('status', 'message'), JSON_UNESCAPED_UNICODE);
    exit;
}
if (empty($_SESSION['usuario_id'])) responderPerfil('error', 'Inicia sesión nuevamente.', 401);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') responderPerfil('error', 'Método no permitido.', 405);
$token = $_POST['csrf_token'] ?? null;
if (!is_string($token) || empty($_SESSION['csrf_perfil']) || !hash_equals($_SESSION['csrf_perfil'], $token)) {
    responderPerfil('error', 'Recarga el perfil e intenta nuevamente.', 419);
}
$actual = $_POST['actual'] ?? null;
$nueva = $_POST['nueva'] ?? null;
$confirmacion = $_POST['confirmacion'] ?? null;
if (!is_string($actual) || !is_string($nueva) || !is_string($confirmacion)) responderPerfil('error', 'Completa todos los campos.');
if (mb_strlen($nueva, 'UTF-8') < 8) responderPerfil('error', 'La nueva contraseña debe tener al menos 8 caracteres.');
if (strlen($nueva) > 72) responderPerfil('error', 'La contraseña es demasiado larga. Usa una más corta.');
if ($nueva !== $confirmacion) responderPerfil('error', 'Las contraseñas nuevas no coinciden.');
try {
    $conexion->beginTransaction();
    $stmt = $conexion->prepare('SELECT cedula, password_hash FROM usuario WHERE id = ? AND activo = 1 FOR UPDATE');
    $stmt->execute([(int)$_SESSION['usuario_id']]);
    $usuario = $stmt->fetch();
    if (!$usuario || !verificarClaveUsuario($usuario, $actual)) {
        $conexion->rollBack();
        responderPerfil('error', 'La contraseña actual es incorrecta.');
    }
    $stmt = $conexion->prepare('UPDATE usuario SET password_hash = ? WHERE id = ?');
    $stmt->execute([password_hash($nueva, PASSWORD_DEFAULT), (int)$_SESSION['usuario_id']]);
    $conexion->commit();
    session_regenerate_id(true);
    responderPerfil('success', 'Contraseña actualizada. Tu usuario sigue siendo tu cédula.');
} catch (Throwable $e) {
    if ($conexion->inTransaction()) $conexion->rollBack();
    responderPerfil('error', 'No se pudo cambiar la contraseña. Intenta nuevamente.', 500);
}
