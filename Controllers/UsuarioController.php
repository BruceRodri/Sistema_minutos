<?php
// Controllers/UsuarioController.php
session_start();
require_once '../Config/conexion.php';
require_once '../Dao/UsuarioDao.php';

header('Content-Type: application/json');

// Validar que solo el admin pueda hacer esto
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['accion']) && $_GET['accion'] === 'obtener_permisos') {
        $id = $_GET['id'] ?? 0;
        $usuarioDao = new UsuarioDao($conexion);
        $permisos = $usuarioDao->obtenerIdsPermisosUsuario($id);
        // Convierte los strings de la BD a enteros para el JS
        echo json_encode(array_map('intval', $permisos));
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'crear') {
        $cedula = trim($_POST['cedula'] ?? '');
        $nombre = trim($_POST['nombre'] ?? '');
        $rol = trim($_POST['rol'] ?? '');
        // NUEVO: Capturar el array de permisos marcados
        $permisos = $_POST['permisos'] ?? [];

        if (empty($cedula) || empty($nombre) || empty($rol)) {
            echo json_encode(['status' => 'error', 'message' => 'Todos los campos principales son obligatorios.']);
            exit;
        }

        $passwordHash = password_hash($cedula, PASSWORD_DEFAULT);
        $usuarioDao = new UsuarioDao($conexion);
        
        // Modificamos el DAO para que devuelva el ID del usuario insertado
        $nuevoUsuarioId = $usuarioDao->crearUsuarioYDevolverId($cedula, $nombre, $passwordHash, $rol);

        if ($nuevoUsuarioId) {
            // Asignar permisos si seleccionó módulos y no es admin (el admin ve todo)
            if ($rol !== 'admin' && !empty($permisos)) {
                $usuarioDao->asignarPermisosModulos($nuevoUsuarioId, $permisos);
            }
            echo json_encode(['status' => 'success', 'message' => 'Usuario y permisos creados exitosamente.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No se pudo crear el usuario. Verifique que la cédula no esté duplicada.']);
        }
    } elseif ($accion === 'editar') {
        $id = $_POST['id_usuario'] ?? '';
        $cedula = trim($_POST['cedula'] ?? '');
        $nombre = trim($_POST['nombre'] ?? '');
        $rol = trim($_POST['rol'] ?? '');
        $permisos = $_POST['permisos'] ?? [];

        if (empty($id) || empty($cedula) || empty($nombre) || empty($rol)) {
            echo json_encode(['status' => 'error', 'message' => 'Todos los campos principales son obligatorios.']);
            exit;
        }

        $usuarioDao = new UsuarioDao($conexion);
        
        if ($usuarioDao->editarUsuario($id, $cedula, $nombre, $rol)) {
            // Limpiamos los permisos antiguos y asignamos los nuevos
            $usuarioDao->limpiarPermisos($id);
            if ($rol !== 'admin' && !empty($permisos)) {
                $usuarioDao->asignarPermisosModulos($id, $permisos);
            }
            echo json_encode(['status' => 'success', 'message' => 'Usuario actualizado exitosamente.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al actualizar. Verifique que la cédula no pertenezca a otro usuario.']);
        }
    } elseif ($accion === 'resetear_clave') {
        $id = $_POST['id'] ?? '';
        $cedula = $_POST['cedula'] ?? '';

        if (empty($id) || empty($cedula)) {
            echo json_encode(['status' => 'error', 'message' => 'Faltan datos de identificación.']);
            exit;
        }

        // El hash temporal es la misma cédula
        $passwordHash = password_hash($cedula, PASSWORD_DEFAULT);
        $usuarioDao = new UsuarioDao($conexion);
        
        if ($usuarioDao->resetearClave($id, $passwordHash)) {
            echo json_encode(['status' => 'success', 'message' => 'Clave reseteada. El usuario usará su cédula y deberá cambiarla al ingresar.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al actualizar la base de datos.']);
        }

    } elseif ($accion === 'cambiar_estado') {
        $id = $_POST['id'] ?? '';
        $estadoActual = $_POST['estado'] ?? ''; 

        if ($id === '') {
            echo json_encode(['status' => 'error', 'message' => 'ID de usuario no proporcionado.']);
            exit;
        }

        // Si estaba en 1 pasa a 0, y viceversa
        $nuevoEstado = ($estadoActual == 1) ? 0 : 1;
        $usuarioDao = new UsuarioDao($conexion);

        if ($usuarioDao->cambiarEstado($id, $nuevoEstado)) {
            $msg = ($nuevoEstado == 1) ? 'Usuario habilitado correctamente.' : 'Usuario deshabilitado. No podrá iniciar sesión.';
            echo json_encode(['status' => 'success', 'message' => $msg]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al cambiar el estado.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Acción no válida.']);
    }
}
?>