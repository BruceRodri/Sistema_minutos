<?php
// Controllers/AuthController.php
session_start();

require_once '../Config/conexion.php';
require_once '../Config/rutas.php';
require_once '../Dao/UsuarioDao.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cedula = trim($_POST['cedula'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($cedula) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'Por favor complete todos los campos.']);
        exit;
    }

    $usuarioDao = new UsuarioDao($conexion);
    $usuario = $usuarioDao->obtenerPorCedula($cedula);

    // Login actual: usuario y contraseña = cédula (sin columna password en la BD nueva)
    if ($usuario && $password === $cedula) {
        $redirect = obtenerRutaInicio($usuario['rol']);

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
        $_SESSION['permisos'] = [];

        echo json_encode(['status' => 'success', 'redirect' => $redirect]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Cédula o contraseña incorrectos.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
}
?>
