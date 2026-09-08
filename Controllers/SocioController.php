<?php
// Controllers/SocioController.php
session_start();
require_once '../Config/conexion.php';
require_once '../Dao/SocioDao.php';
require_once '../Dao/BusDao.php';

header('Content-Type: application/json');

$rolesAdmin = ['admin', 'secretaria', 'operativo'];
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['rol'] ?? '', $rolesAdmin)) {
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'asignar_disco') {
        $bus_id = filter_var($_POST['bus_id'] ?? null, FILTER_VALIDATE_INT);
        $usuario_id = filter_var($_POST['usuario_id'] ?? null, FILTER_VALIDATE_INT);

        if (!$bus_id || !$usuario_id) {
            echo json_encode(['status' => 'error', 'message' => 'Seleccione un disco válido.']);
            exit;
        }

        $socioDao = new SocioDao($conexion);
        $resultado = $socioDao->asignarDiscoDisponible($bus_id, $usuario_id);

        if ($resultado === 'socio_invalido') {
            echo json_encode(['status' => 'error', 'message' => 'El socio no existe o está deshabilitado.']);
        } elseif ($resultado === 'socio_con_disco') {
            echo json_encode(['status' => 'error', 'message' => 'Este socio ya tiene un disco asignado.']);
        } elseif ($resultado === 'disco_invalido') {
            echo json_encode(['status' => 'error', 'message' => 'El disco no existe o está deshabilitado.']);
        } elseif ($resultado === 'no_disponible') {
            echo json_encode(['status' => 'error', 'message' => 'El disco seleccionado ya fue asignado a otro socio.']);
        } elseif ($resultado) {
            echo json_encode(['status' => 'success', 'message' => 'Disco asignado correctamente.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No se pudo asignar el disco.']);
        }
        exit;
    }

    if ($accion === 'cambiar_socio') {
        $bus_id = $_POST['bus_id'] ?? '';
        $usuario_id = $_POST['usuario_id'] ?? '';

        if ($bus_id === '' || $usuario_id === '') {
            echo json_encode(['status' => 'error', 'message' => 'Faltan datos para realizar el cambio.']);
            exit;
        }

        $socioDao = new SocioDao($conexion);
        if ($socioDao->cambiarSocioDeBus($bus_id, $usuario_id)) {
            echo json_encode(['status' => 'success', 'message' => 'Socio del disco actualizado correctamente.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No se pudo actualizar el socio del disco.']);
        }
        exit;
    }

    if ($accion === 'cambiar_estado_disco') {
        $bus_id = $_POST['bus_id'] ?? '';
        $estado = $_POST['estado'] ?? '';

        if ($bus_id === '' || ($estado !== '0' && $estado !== '1')) {
            echo json_encode(['status' => 'error', 'message' => 'Faltan datos del disco.']);
            exit;
        }

        $busDao = new BusDao($conexion);
        if ($busDao->cambiarEstado($bus_id, (int)$estado)) {
            $msg = ($estado == 1)
                ? 'Disco habilitado correctamente.'
                : 'Disco deshabilitado. No podrá abrir turnos hasta ser habilitado nuevamente.';
            echo json_encode(['status' => 'success', 'message' => $msg]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al actualizar el estado del disco.']);
        }
        exit;
    }

    echo json_encode(['status' => 'error', 'message' => 'Acción no válida.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
}
?>
