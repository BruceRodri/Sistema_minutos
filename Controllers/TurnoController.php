<?php
// Controllers/TurnoController.php
session_start();
require_once '../Config/conexion.php';
require_once '../Dao/TurnoDao.php';
require_once '../Dao/ValoresDao.php';

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

        $valoresDao = new ValoresDao($conexion);
        $valorHoy = $valoresDao->obtenerDiscoHoy($bus['disco']);

        if (!$valorHoy) {
            echo json_encode(['status' => 'error', 'message' => 'No existe un valor definido para el disco ' . $bus['disco'] . ' en la fecha de hoy. Verifique que el archivo de valores diarios esté actualizado.']);
            exit;
        }

        $resultado = $turnoDao->abrirTurno($_SESSION['usuario_id'], $bus['id'], $valorHoy['valor'], $valorHoy['ruta']);

        if (isset($resultado['conductor_duplicado'])) {
            echo json_encode(['status' => 'error', 'message' => 'Usted ya abrió un turno hoy. Podrá abrir otro mañana.']);
            exit;
        }

        if (isset($resultado['bus_duplicado'])) {
            echo json_encode(['status' => 'error', 'message' => 'Este bus ya abrió un turno hoy. Podrá abrir un nuevo turno mañana.']);
            exit;
        }

        if (isset($resultado['conductor_invalido'])) {
            echo json_encode(['status' => 'error', 'message' => 'El conductor no está habilitado o no tiene un código asignado.']);
            exit;
        }

        if (!$resultado) {
            echo json_encode(['status' => 'error', 'message' => 'No se pudo abrir el turno. Intente nuevamente.']);
            exit;
        }

        $_SESSION['turno_disco'] = $bus['disco'];
        $_SESSION['turno_bus_id'] = $bus['id'];

        echo json_encode([
            'status' => 'success',
            'message' => 'Bienvenido. Su turno fue abierto correctamente.',
            'disco' => $bus['disco'],
            'fecha' => $resultado['fecha'],
            'hora' => $resultado['hora'],
            'codigo_conductor' => $resultado['codigo_conductor'],
            'valor' => $resultado['valor'],
            'ruta' => $valorHoy['ruta']
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
