<?php
// Controllers/AuthController.php
session_start();

require_once '../Config/conexion.php';
require_once '../Config/rutas.php';
require_once '../Config/credenciales.php';
require_once '../Dao/UsuarioDao.php';
require_once '../Config/permisos.php';
require_once '../Config/validacion_cedula.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cedula = trim($_POST['cedula'] ?? '');
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';

    if (empty($cedula) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'Por favor complete todos los campos.']);
        exit;
    }

    if (!validarCedulaEcuatoriana($cedula)) {
        echo json_encode(['status' => 'error', 'message' => 'Ingrese una cédula ecuatoriana válida de 10 dígitos.']);
        exit;
    }

    $usuarioDao = new UsuarioDao($conexion);
    $usuario = $usuarioDao->obtenerPorCedula($cedula);

    // La cédula es la clave inicial hasta que el usuario elija una propia.
    if ($usuario && verificarClaveUsuario($usuario, $password)) {
        $permisos = permisosEfectivosUsuario($conexion, (int)$usuario['id'], (string)$usuario['rol']);
        $interfazInicial = in_array($usuario['rol'], ['conductor', 'socio'], true) ? 'APP' : 'WEB';
        $interfazAlterna = $interfazInicial === 'APP' ? 'WEB' : 'APP';
        $redirect = rutaPrimeraInterfaz($permisos, $interfazInicial)
            ?? rutaPrimeraInterfaz($permisos, $interfazAlterna);
        if ($redirect !== null) $redirect = ltrim($redirect, '/');

        if ($redirect === null) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Este rol todavía no tiene un módulo de inicio habilitado.'
            ]);
            exit;
        }

        session_regenerate_id(true);

        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['rol'] = $usuario['rol'];
        $_SESSION['nombre'] = $usuario['nombre'];
        $_SESSION['codigo_conductor'] = $usuario['codigo_conductor'] ?? null;
        $_SESSION['permisos'] = $permisos;

        echo json_encode(['status' => 'success', 'redirect' => $redirect]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Cédula o contraseña incorrectos.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
}
?>
