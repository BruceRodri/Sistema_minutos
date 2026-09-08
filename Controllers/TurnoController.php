<?php
// Controllers/TurnoController.php
session_start();
require_once '../Config/conexion.php';
require_once '../Dao/TurnoDao.php';

header('Content-Type: application/json');

function responderErrorTurno($turnoDao, $usuarioId, $mensaje, $discoEscaneado = '', $busId = null) {
    $turnoDao->registrarIntentoFallido($usuarioId, $busId, $discoEscaneado, $mensaje);
    echo json_encode(['status' => 'error', 'message' => $mensaje]);
    exit;
}

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
        $turnoDao = new TurnoDao($conexion);

        if ($disco === '') {
            responderErrorTurno($turnoDao, $_SESSION['usuario_id'], 'Código QR no válido.');
        }

        $bus = $turnoDao->obtenerBusPorDisco($disco);

        if (!$bus) {
            responderErrorTurno($turnoDao, $_SESSION['usuario_id'], 'Bus no encontrado. Verifique el código QR.', $disco);
        }

        if ($bus['activo'] != 1) {
            responderErrorTurno(
                $turnoDao,
                $_SESSION['usuario_id'],
                'Este bus está deshabilitado. Contacte al personal administrativo.',
                $disco,
                $bus['id']
            );
        }

        $resultado = $turnoDao->abrirTurno($_SESSION['usuario_id'], $bus['id']);

        if (isset($resultado['conductor_duplicado'])) {
            responderErrorTurno(
                $turnoDao,
                $_SESSION['usuario_id'],
                'Usted ya abrió un turno el día de hoy, por favor comunicarse con su jefe de ruta.',
                $disco,
                $bus['id']
            );
        }

        if (isset($resultado['bus_duplicado'])) {
            responderErrorTurno(
                $turnoDao,
                $_SESSION['usuario_id'],
                'Este bus ya abrió un turno el día de hoy, comunicarse con su jefe de ruta.',
                $disco,
                $bus['id']
            );
        }

        if (isset($resultado['conductor_invalido'])) {
            responderErrorTurno(
                $turnoDao,
                $_SESSION['usuario_id'],
                'El conductor no está habilitado o no tiene un código asignado.',
                $disco,
                $bus['id']
            );
        }

        if (!$resultado) {
            responderErrorTurno(
                $turnoDao,
                $_SESSION['usuario_id'],
                'No se pudo abrir el turno. Intente nuevamente.',
                $disco,
                $bus['id']
            );
        }

        $_SESSION['turno_disco'] = $bus['disco'];
        $_SESSION['turno_bus_id'] = $bus['id'];

        echo json_encode([
            'status' => 'success',
            'message' => 'Que tengas un excelente día.',
            'disco' => $bus['disco'],
            'fecha' => $resultado['fecha'],
            'hora' => $resultado['hora'],
            'codigo_conductor' => $resultado['codigo_conductor']
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
