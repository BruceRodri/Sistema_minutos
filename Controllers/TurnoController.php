<?php
// Controllers/TurnoController.php
session_start();
require_once '../Config/conexion.php';
require_once '../Dao/TurnoDao.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'abrir_turno') {
        if ($_SESSION['rol'] !== 'conductor') {
            echo json_encode(['status' => 'error', 'message' => 'Solo los conductores pueden abrir turnos.']);
            exit;
        }

        $disco = trim($_POST['disco'] ?? '');
        if ($disco === '') {
            echo json_encode(['status' => 'error', 'message' => 'Código QR no válido.']);
            exit;
        }

        $turnoDao = new TurnoDao($conexion);
        $bus = $turnoDao->obtenerBusPorDisco($disco);

        if (!$bus) {
            echo json_encode(['status' => 'error', 'message' => 'Bus no encontrado. Verifique el código QR.']);
            exit;
        }

        if ($bus['activo'] != 1) {
            echo json_encode(['status' => 'error', 'message' => 'Este bus está deshabilitado. Contacte al personal administrativo.']);
            exit;
        }

        $resultado = $turnoDao->abrirTurno($_SESSION['usuario_id'], $bus['id']);

        if (isset($resultado['duplicado'])) {
            echo json_encode(['status' => 'error', 'message' => 'Este bus ya abrió turno hoy. Para reabrirlo, debe deshabilitarlo desde el panel administrativo.']);
            exit;
        }

        if (!$resultado) {
            echo json_encode(['status' => 'error', 'message' => 'No se pudo abrir el turno. Intente nuevamente.']);
            exit;
        }

        echo json_encode([
            'status' => 'success',
            'message' => 'Bienvenido. Su turno fue abierto correctamente.',
            'disco' => $bus['disco'],
            'hora' => $resultado['hora']
        ]);
        exit;
    }

    if ($accion === 'cambiar_estado_bus') {
        if ($_SESSION['rol'] !== 'admin') {
            echo json_encode(['status' => 'error', 'message' => 'Acceso denegado.']);
            exit;
        }

        $bus_id = $_POST['bus_id'] ?? '';
        $estado = $_POST['estado'] ?? '';

        if ($bus_id === '' || ($estado !== '0' && $estado !== '1')) {
            echo json_encode(['status' => 'error', 'message' => 'Faltan datos del bus.']);
            exit;
        }

        $turnoDao = new TurnoDao($conexion);
        if ($turnoDao->cambiarEstadoBus($bus_id, (int)$estado)) {
            $msg = ($estado == 1)
                ? 'Bus habilitado correctamente.'
                : 'Bus deshabilitado. Ya no podrá abrir turnos hasta ser habilitado nuevamente.';
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