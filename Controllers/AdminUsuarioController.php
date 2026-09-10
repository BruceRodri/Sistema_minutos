<?php
// Controllers/AdminUsuarioController.php
session_start();
require_once '../Config/conexion.php';
require_once '../Dao/UsuarioDao.php';
require_once '../Config/permisos.php';
require_once '../Config/validacion_cedula.php';

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

if ($accion === 'restablecer_clave') {
    $id = filter_var($_POST['usuario_id'] ?? null, FILTER_VALIDATE_INT);
    $admin = $usuarioDao->obtenerUsuarioAdministrablePorId((int)$_SESSION['usuario_id']);
    if (!$admin || !$admin['activo'] || $admin['rol'] !== 'admin') responderUsuario('error', 'Acceso denegado.', []);
    $usuario = $id ? $usuarioDao->obtenerUsuarioAdministrablePorId($id) : null;
    if (!$usuario) responderUsuario('error', 'El usuario seleccionado no existe.');
    try {
        // NULL representa la contraseña inicial: la cédula actual del registro.
        $stmt = $conexion->prepare('UPDATE usuario SET password_hash = NULL WHERE id = ?');
        $stmt->execute([$id]);
        responderUsuario('success', 'Contraseña restablecida. El usuario puede ingresar con su cédula como usuario y contraseña. Cambiarla desde Perfil es opcional.');
    } catch (Throwable $e) {
        responderUsuario('error', 'No se pudo restablecer la contraseña.');
    }
}

if ($accion === 'verificar_cedula') {
    $cedula = preg_replace('/\D+/', '', (string)($_POST['cedula'] ?? ''));
    $usuarioId = filter_var($_POST['usuario_id'] ?? null, FILTER_VALIDATE_INT) ?: null;
    $valida = validarCedulaEcuatoriana($cedula);
    $disponible = $valida && !$usuarioDao->cedulaExiste($cedula, $usuarioId);
    responderUsuario('success', $disponible
        ? 'Cédula válida y disponible.'
        : ($valida ? 'Ya existe un usuario con esta cédula.' : 'La cédula ecuatoriana no es válida.'), [
        'valida' => $valida,
        'disponible' => $disponible
    ]);
}

if ($accion === 'buscar_usuarios_permisos') {
    $termino = is_scalar($_POST['termino'] ?? '') ? trim((string)$_POST['termino']) : '';
    responderUsuario('success', 'Usuarios encontrados.', [
        'usuarios' => $usuarioDao->buscarUsuariosParaPermisos($termino, 5)
    ]);
}

if ($accion === 'obtener_permisos') {
    $usuarioId = filter_var($_POST['usuario_id'] ?? null, FILTER_VALIDATE_INT);
    $usuario = $usuarioId ? $usuarioDao->obtenerUsuarioAdministrablePorId($usuarioId) : null;
    if (!$usuario) responderUsuario('error', 'El usuario seleccionado no existe.');
    responderUsuario('success', 'Permisos cargados.', [
        'permisos' => permisosEfectivosUsuario($conexion, (int)$usuarioId, (string)$usuario['rol'])
    ]);
}

if ($accion === 'guardar_permisos') {
    $idsRecibidos = (array)($_POST['usuario_ids'] ?? []);
    if (!$idsRecibidos && isset($_POST['usuario_id'])) $idsRecibidos = [$_POST['usuario_id']];
    $usuariosIds = array_values(array_unique(array_filter(array_map('intval', $idsRecibidos))));
    if (!$usuariosIds) responderUsuario('error', 'Agregue al menos un usuario a la lista.');
    $catalogo = array_keys(catalogoModulosSistema());
    $permisos = array_values(array_intersect((array)($_POST['permisos'] ?? []), $catalogo));
    $conexion->beginTransaction();
    try {
        foreach ($usuariosIds as $usuarioId) {
            $usuario = $usuarioDao->obtenerUsuarioAdministrablePorId($usuarioId);
            if (!$usuario) throw new RuntimeException('Uno de los usuarios seleccionados ya no existe.');
            if (($usuario['rol'] ?? '') === 'admin') throw new RuntimeException('El administrador mantiene acceso completo y no necesita permisos individuales.');
            if (!$usuarioDao->guardarPermisosModulos($usuarioId, $permisos, $catalogo)) throw new RuntimeException('No se pudieron guardar los permisos.');
        }
        $conexion->commit();
    } catch (Throwable $e) {
        if ($conexion->inTransaction()) $conexion->rollBack();
        responderUsuario('error', $e->getMessage());
    }
    responderUsuario('success', 'Permisos actualizados para ' . count($usuariosIds) . ' usuario(s).');
}

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

if (!validarCedulaEcuatoriana($cedula)) {
    responderUsuario('error', 'Ingrese una cédula ecuatoriana válida de 10 dígitos.');
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
    if ($usuarioDao->cedulaExiste($cedula, $usuarioId)) {
        responderUsuario('error', 'Ya existe otro usuario con esta cédula.');
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
    if ($usuarioDao->cedulaExiste($cedula)) {
        responderUsuario('error', 'Ya existe un usuario con esta cédula.');
    }
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
