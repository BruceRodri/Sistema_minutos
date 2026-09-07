<?php
// Controllers/AuthController.php
session_start();

// Incluir dependencias
require_once '../Config/conexion.php'; // Esto expone la variable $conexion
require_once '../Dao/UsuarioDao.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cedula = $_POST['cedula'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($cedula) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'Por favor complete todos los campos.']);
        exit;
    }

    $usuarioDao = new UsuarioDao($conexion);
    $usuario = $usuarioDao->obtenerPorCedula($cedula);

    // Verificamos si el usuario existe y si la contraseña coincide con el hash
    if ($usuario && password_verify($password, $usuario['password'])) {
        
        // Verificamos si tú (el administrador) le reseteaste la clave
        if ($usuario['debe_cambiar_clave'] == 1) {
            $_SESSION['temp_usuario_id'] = $usuario['id'];
            echo json_encode(['status' => 'require_change', 'redirect' => 'cambiar_clave.php']);
            exit;
        }

        // Crear sesión
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['rol'] = $usuario['rol'];
        $_SESSION['nombre'] = $usuario['nombre'];
        
        // NUEVO: Guardar permisos en un array en la sesión
        $_SESSION['permisos'] = $usuarioDao->obtenerPermisosUsuario($usuario['id']);

        // Determinar a dónde enviarlo (Redirección dinámica)
        if ($usuario['rol'] === 'admin') {
            $redirect = 'admin/usuarios.php';
        } elseif (in_array('mod_socios', $_SESSION['permisos'])) {
            $redirect = 'admin/socios.php'; // Lo enviaremos a Socios si tiene permiso
        } else {
            $redirect = 'secretaria/gestion_resoluciones.php'; // Opción por defecto
        }
        
        echo json_encode(['status' => 'success', 'redirect' => $redirect]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Cédula o contraseña incorrectos.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
}
?>