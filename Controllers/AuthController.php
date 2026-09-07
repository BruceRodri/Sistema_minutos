<?php
// Controllers/AuthController.php
session_start();

require_once '../Config/conexion.php';
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
        session_regenerate_id(true);

        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['rol'] = $usuario['rol'];
        $_SESSION['nombre'] = $usuario['nombre'];
        $_SESSION['permisos'] = [];

        $redirect = match ($usuario['rol']) {
            'admin' => 'admin/turnos.php',
            'secretaria' => 'secretaria/gestion_resoluciones.php',
            'operativo' => 'operador/ingreso_resolucion.php',
            'socio' => 'admin/socios.php',
            default => 'App/conductor/dashboard.php',
        };

        echo json_encode(['status' => 'success', 'redirect' => $redirect]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Cédula o contraseña incorrectos.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
}
?>