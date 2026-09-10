<?php
// Controllers/BusController.php
session_start();
require_once '../Config/conexion.php';
require_once '../Config/permisos.php';
require_once '../Dao/BusDao.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id']) || !usuarioPuedeVerModulo($conexion, 'web_buses')) {
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'crear') {
        $disco = trim($_POST['disco'] ?? '');
        $placa = trim($_POST['placa'] ?? '');

        if ($disco === '') {
            echo json_encode(['status' => 'error', 'message' => 'El número de disco es obligatorio.']);
            exit;
        }

        $busDao = new BusDao($conexion);

        if ($busDao->existeDisco($disco)) {
            echo json_encode(['status' => 'error', 'message' => 'Ya existe un bus con ese disco.']);
            exit;
        }

        $resultado = $busDao->crearBus($disco, $placa);
        if ($resultado === 'duplicado') {
            echo json_encode(['status' => 'error', 'message' => 'Esa placa ya está registrada.']);
            exit;
        }
        if ($resultado) {
            echo json_encode(['status' => 'success', 'message' => 'Bus creado correctamente.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No se pudo crear el bus.']);
        }
        exit;
    }

    if ($accion === 'editar') {
        $id = $_POST['id'] ?? '';
        $disco = trim($_POST['disco'] ?? '');
        $placa = trim($_POST['placa'] ?? '');

        if ($id === '' || $disco === '') {
            echo json_encode(['status' => 'error', 'message' => 'Faltan datos del bus.']);
            exit;
        }

        $busDao = new BusDao($conexion);

        if ($busDao->existeDisco($disco, $id)) {
            echo json_encode(['status' => 'error', 'message' => 'Ya existe otro bus con ese disco.']);
            exit;
        }

        $resultado = $busDao->editarBus($id, $disco, $placa);
        if ($resultado === 'duplicado') {
            echo json_encode(['status' => 'error', 'message' => 'Esa placa ya está registrada.']);
            exit;
        }
        if ($resultado) {
            echo json_encode(['status' => 'success', 'message' => 'Bus actualizado correctamente.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No se pudo actualizar el bus.']);
        }
        exit;
    }

    if ($accion === 'eliminar') {
        $id = $_POST['id'] ?? '';
        if ($id === '') {
            echo json_encode(['status' => 'error', 'message' => 'ID de bus no proporcionado.']);
            exit;
        }

        $busDao = new BusDao($conexion);
        $resultado = $busDao->eliminarBus($id);

        if ($resultado === 'en_uso') {
            echo json_encode(['status' => 'error', 'message' => 'No se puede eliminar: el bus tiene turnos o socios vinculados.']);
            exit;
        }
        if ($resultado) {
            echo json_encode(['status' => 'success', 'message' => 'Bus eliminado correctamente.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No se pudo eliminar el bus.']);
        }
        exit;
    }

    if ($accion === 'cambiar_estado') {
        $id = $_POST['id'] ?? '';
        $estado = $_POST['estado'] ?? '';

        if ($id === '' || ($estado !== '0' && $estado !== '1')) {
            echo json_encode(['status' => 'error', 'message' => 'Faltan datos del bus.']);
            exit;
        }

        $busDao = new BusDao($conexion);
        if ($busDao->cambiarEstado($id, (int)$estado)) {
            $msg = ($estado == 1)
                ? 'Bus habilitado correctamente.'
                : 'Bus deshabilitado. No podrá abrir turnos hasta ser habilitado nuevamente.';
            echo json_encode(['status' => 'success', 'message' => $msg]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al actualizar el estado del bus.']);
        }
        exit;
    }

    echo json_encode(['status' => 'error', 'message' => 'Acción no válida.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
}
?>
