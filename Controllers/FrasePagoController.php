<?php
session_start();
require_once __DIR__ . '/../Config/conexion.php';
require_once __DIR__ . '/../Dao/FrasePagoDao.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
function responderFrases(array $datos, int $codigo = 200): never {
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}
if (empty($_SESSION['usuario_id'])) responderFrases(['status' => 'error', 'message' => 'Inicia sesión nuevamente.'], 401);
// Comprobar el rol vigente en la base de datos, además de ocultar el botón en la vista.
$stmt = $conexion->prepare("SELECT u.id FROM usuario u JOIN rol r ON r.id = u.rol_id
    WHERE u.id = ? AND u.activo = 1 AND r.activo = 1 AND r.nombre IN ('admin', 'secretaria')");
$stmt->execute([(int)$_SESSION['usuario_id']]);
if (!$stmt->fetchColumn()) responderFrases(['status' => 'error', 'message' => 'Acceso denegado.'], 403);
$metodo = $_SERVER['REQUEST_METHOD'];
if (!in_array($metodo, ['GET', 'POST'], true)) responderFrases(['status' => 'error', 'message' => 'Método no permitido.'], 405);
try {
    $dao = new FrasePagoDao($conexion);
    $mensaje = 'Frases cargadas.';
    if ($metodo === 'POST') {
        $token = $_POST['csrf_token'] ?? null;
        if (!is_string($token) || empty($_SESSION['csrf_perfil']) || !hash_equals($_SESSION['csrf_perfil'], $token)) {
            responderFrases(['status' => 'error', 'message' => 'Recarga el perfil e intenta nuevamente.'], 419);
        }
        $id = filter_var($_POST['id'] ?? '0', FILTER_VALIDATE_INT);
        if ($id === false) throw new InvalidArgumentException('Datos de frase no válidos.');
        $accion = $_POST['accion'] ?? 'guardar';
        if ($accion === 'eliminar') {
            $dao->eliminar($id);
            $mensaje = 'Frase eliminada.';
        } elseif ($accion === 'guardar') {
            $estado = $_POST['estado'] ?? null;
            $texto = $_POST['texto'] ?? null;
            if (!is_string($estado) || !is_string($texto)) throw new InvalidArgumentException('Datos de frase no válidos.');
            $dao->guardar($id, $estado, $texto);
            $mensaje = 'Frase guardada.';
        } else {
            throw new InvalidArgumentException('Acción no válida.');
        }
    }
    responderFrases(['status' => 'success', 'message' => $mensaje, 'frases' => $dao->obtenerTodas()]);
} catch (InvalidArgumentException $e) {
    responderFrases(['status' => 'error', 'message' => $e->getMessage()], 422);
} catch (Throwable $e) {
    error_log('Frases de pagos: ' . $e->getMessage());
    responderFrases(['status' => 'error', 'message' => 'No se pudo completar la operación con las frases. Intenta nuevamente.'], 500);
}
