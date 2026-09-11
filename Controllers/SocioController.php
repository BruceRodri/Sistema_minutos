<?php
// Controllers/SocioController.php
session_start();
require_once '../Config/conexion.php';
require_once '../Config/permisos.php';
require_once '../Dao/SocioDao.php';
require_once '../Dao/BusDao.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id']) || !usuarioPuedeVerModulo($conexion, 'web_socios')) {
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'buscar_discos') {
        $q = trim((string)($_POST['q'] ?? ''));
        if ($q === '') {
            echo json_encode(['status' => 'success', 'discos' => []]);
            exit;
        }
        $socioDao = new SocioDao($conexion);
        echo json_encode(['status' => 'success', 'discos' => $socioDao->buscarDiscosDisponibles($q)]);
        exit;
    }

    if ($accion === 'asignar_discos') {
        $usuario_id = filter_var($_POST['usuario_id'] ?? null, FILTER_VALIDATE_INT);
        $busIds = array_values(array_unique(array_filter(array_map(
            'intval',
            (array)($_POST['bus_ids'] ?? [])
        ))));

        if (!$usuario_id) {
            echo json_encode(['status' => 'error', 'message' => 'Seleccione un socio válido.']);
            exit;
        }

        if (empty($busIds)) {
            echo json_encode(['status' => 'error', 'message' => 'Agregue al menos un disco a la lista.']);
            exit;
        }

        $socioDao = new SocioDao($conexion);
        $resultado = $socioDao->asignarDiscos($busIds, $usuario_id);

        if ($resultado['status'] === 'socio_invalido') {
            echo json_encode(['status' => 'error', 'message' => 'El socio no existe o está deshabilitado.']);
        } elseif ($resultado['status'] === 'error' && empty($resultado['asignados'])) {
            echo json_encode(['status' => 'error', 'message' => 'Ninguno de los discos seleccionados pudo asignarse.']);
        } elseif ($resultado['status'] === 'success') {
            $total = count($resultado['asignados']);
            $errores = count($resultado['errores']);
            $mensaje = $total . ($total === 1 ? ' disco asignado' : ' discos asignados') . ' correctamente.';
            if ($errores > 0) {
                $mensaje .= ' ' . $errores . ($errores === 1 ? ' disco no estaba disponible' : ' discos no estaban disponibles') . '.';
            }
            echo json_encode(['status' => 'success', 'message' => $mensaje]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No se pudieron asignar los discos.']);
        }
        exit;
    }

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
