<?php
// Controllers/AdminUsuarioController.php
session_start();
require_once '../Config/conexion.php';
require_once '../Dao/UsuarioDao.php';

header('Content-Type: application/json; charset=utf-8');

function responderUsuario($status, $message, $extra = []) {
    echo json_encode(array_merge([
        'status' => $status,
        'message' => $message
    ], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'admin') {
    http_response_code(403);
    responderUsuario('error', 'Acceso denegado. Solo el administrador puede gestionar usuarios.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    responderUsuario('error', 'Método no permitido.');
}

$token = $_POST['csrf_token'] ?? '';
if (!is_string($token) || !isset($_SESSION['csrf_admin_usuarios']) || !hash_equals($_SESSION['csrf_admin_usuarios'], $token)) {
    http_response_code(419);
    responderUsuario('error', 'La sesión del formulario venció. Recargue la página e intente nuevamente.');
}

$accion = $_POST['accion'] ?? '';
$usuarioDao = new UsuarioDao($conexion);

if ($accion === 'cambiar_estado') {
    $usuarioId = filter_var($_POST['usuario_id'] ?? null, FILTER_VALIDATE_INT);
    $nuevoEstado = $_POST['nuevo_estado'] ?? '';

    if (!$usuarioId || !in_array($nuevoEstado, ['0', '1'], true)) {
        responderUsuario('error', 'Los datos para cambiar el estado no son válidos.');
    }
    if ((int)$usuarioId === (int)$_SESSION['usuario_id']) {
        responderUsuario('error', 'No puede deshabilitar su propio usuario administrador.');
    }
    if (!$usuarioDao->obtenerUsuarioAdministrablePorId($usuarioId)) {
        responderUsuario('error', 'El usuario seleccionado no existe.');
    }

    $habilitado = $nuevoEstado === '1';
    if (!$usuarioDao->cambiarEstadoUsuarioAdministrable($usuarioId, $habilitado)) {
        responderUsuario('error', 'No se pudo cambiar el estado del usuario.');
    }

    responderUsuario('success', $habilitado
        ? 'Usuario habilitado correctamente.'
        : 'Usuario deshabilitado. Ya no podrá iniciar sesión.');
}

if (!in_array($accion, ['crear_usuario', 'editar_usuario'], true)) {
    responderUsuario('error', 'Acción no válida.');
}

$nombres = trim($_POST['nombres'] ?? '');
$apellidos = trim($_POST['apellidos'] ?? '');
$fechaNacimiento = trim($_POST['fecha_nacimiento'] ?? '');
$cedula = trim($_POST['cedula'] ?? '');
$rol = strtolower(trim($_POST['rol'] ?? ''));

if ($nombres === '' || $apellidos === '' || $fechaNacimiento === '' || $cedula === '' || $rol === '') {
    responderUsuario('error', 'Complete todos los campos obligatorios.');
}

if (mb_strlen($nombres) > 100 || mb_strlen($apellidos) > 100) {
    responderUsuario('error', 'Los nombres y apellidos no pueden superar los 100 caracteres.');
}

if (!preg_match('/^\d{10}$/', $cedula)) {
    responderUsuario('error', 'La cédula debe contener exactamente 10 números.');
}

$fecha = DateTime::createFromFormat('!Y-m-d', $fechaNacimiento);
if (!$fecha || $fecha->format('Y-m-d') !== $fechaNacimiento || $fecha > new DateTime('today')) {
    responderUsuario('error', 'Ingrese una fecha de nacimiento válida.');
}

if (!$usuarioDao->rolActivoExiste($rol)) {
    responderUsuario('error', 'El rol seleccionado no es válido o está deshabilitado.');
}

$usuarioId = null;
if ($accion === 'editar_usuario') {
    $usuarioId = filter_var($_POST['usuario_id'] ?? null, FILTER_VALIDATE_INT);
    if (!$usuarioId) {
        responderUsuario('error', 'El usuario que desea editar no es válido.');
    }

    $usuarioActual = $usuarioDao->obtenerUsuarioAdministrablePorId($usuarioId);
    if (!$usuarioActual) {
        responderUsuario('error', 'El usuario seleccionado no existe.');
    }
    if ((int)$usuarioId === (int)$_SESSION['usuario_id'] && $rol !== 'admin') {
        responderUsuario('error', 'No puede quitarse su propio rol de administrador.');
    }

    $resultado = $usuarioDao->actualizarUsuarioAdministrable(
        $usuarioId,
        $nombres,
        $apellidos,
        $fechaNacimiento,
        $cedula,
        $rol
    );
} else {
    $resultado = $usuarioDao->registrarUsuario($nombres, $apellidos, $fechaNacimiento, $cedula, $rol);
}

if ($resultado === 'duplicado') {
    responderUsuario('error', 'Ya existe un usuario con esa cédula o con el código generado.');
}
if ($resultado === 'no_encontrado') {
    responderUsuario('error', 'El usuario seleccionado no existe.');
}
if (!$resultado) {
    responderUsuario('error', $accion === 'editar_usuario'
        ? 'No se pudo actualizar el usuario. Intente nuevamente.'
        : 'No se pudo registrar el usuario. Intente nuevamente.');
}

$codigo = $resultado['codigo_conductor'] ?: $resultado['codigo_socio'];
$detalleCodigo = $codigo
    ? ' Código asignado: ' . $codigo . '.'
    : '';

if ($accion === 'editar_usuario') {
    responderUsuario('success', 'Usuario actualizado correctamente.' . $detalleCodigo, [
        'usuario_id' => (int)$resultado['id'],
        'codigo' => $codigo
    ]);
}

responderUsuario('success', 'Usuario registrado correctamente.' . $detalleCodigo . ' Su clave inicial es su número de cédula.', [
    'usuario_id' => (int)$resultado['id'],
    'codigo' => $codigo
]);
