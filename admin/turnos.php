<?php
// admin/turnos.php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

require_once '../Config/conexion.php';
require_once '../Dao/TurnoDao.php';

$turnoDao = new TurnoDao($conexion);
$listaTurnos = $turnoDao->obtenerTurnosHoy();
$totalTurnos = count($listaTurnos);
$fechaHoy = date('d/m/Y');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Turnos del Día - Ejecuttrans</title>
    <link rel="icon" href="../Assets/icons/icon-192x192.png" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 flex h-screen overflow-hidden">

    <?php include 'components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col overflow-y-auto mt-16 md:mt-0 w-full">
        <header class="h-16 bg-white shadow-sm flex items-center px-4 md:px-8 justify-between border-b border-gray-200">
            <div>
                <h2 class="text-xl md:text-2xl font-bold text-gray-800 text-transparent bg-clip-text bg-gradient-to-r from-blue-700 to-gray-800">
                    Turnos del Día
                </h2>
                <p class="text-xs text-gray-500"><?php echo $fechaHoy; ?></p>
            </div>
            <button onclick="window.location.reload()" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg shadow-lg transition-all text-sm font-bold flex items-center">
                <i class="fas fa-sync-alt mr-2"></i> Recargar
            </button>
        </header>

        <div class="p-4 md:p-8 w-full max-w-7xl mx-auto">

            <!-- Resumen -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
                    <p class="text-sm text-gray-500">Turnos abiertos hoy</p>
                    <p class="text-3xl font-bold text-blue-700"><?php echo $totalTurnos; ?></p>
                </div>
                <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
                    <p class="text-sm text-gray-500">Última apertura</p>
                    <p class="text-xl font-bold text-gray-800">
                        <?php echo $totalTurnos > 0 ? htmlspecialchars($listaTurnos[0]['hora_apertura']) : '--:--'; ?>
                    </p>
                </div>
            </div>

            <!-- Tabla de Turnos -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-100 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Disco</th>
                            <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Placa</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Conductor</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Hora Apertura</th>
                            <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Estado Bus</th>
                            <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if ($totalTurnos === 0): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-gray-500">
                                <i class="fas fa-clock text-3xl mb-3 text-gray-300"></i>
                                <p>Aún no se han abierto turnos hoy.</p>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($listaTurnos as $turno): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold bg-blue-100 text-blue-800 border border-blue-200">
                                        <?php echo htmlspecialchars($turno['disco']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-mono text-gray-700">
                                    <?php echo htmlspecialchars($turno['placa']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                    <?php echo htmlspecialchars($turno['nombres'] . ' ' . $turno['apellidos']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-800">
                                    <?php echo htmlspecialchars($turno['hora_apertura']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <?php if ($turno['bus_activo'] == 1): ?>
                                        <span class="inline-flex px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-800 border border-green-200">Habilitado</span>
                                    <?php else: ?>
                                        <span class="inline-flex px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-red-800 border border-red-200">Deshabilitado</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <button class="btnToggleBus text-xs font-bold px-4 py-2 rounded-lg transition-all <?php echo ($turno['bus_activo'] == 1) ? 'bg-red-600 hover:bg-red-700 text-white' : 'bg-green-600 hover:bg-green-700 text-white'; ?>"
                                            data-bus-id="<?php echo $turno['bus_id']; ?>"
                                            data-estado="<?php echo $turno['bus_activo']; ?>"
                                            data-disco="<?php echo htmlspecialchars($turno['disco']); ?>">
                                        <?php echo ($turno['bus_activo'] == 1) ? '<i class="fas fa-ban mr-1"></i> Deshabilitar' : '<i class="fas fa-check-circle mr-1"></i> Habilitar'; ?>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <p class="text-xs text-gray-500 mt-4">
                <i class="fas fa-info-circle mr-1"></i>
                Un bus solo puede abrir un turno por día. Si necesita reabrirlo, deshabilítelo desde aquí y el conductor podrá escanearlo nuevamente.
            </p>
        </div>
    </main>

    <script src="../Assets/js/turnos.js"></script>
</body>
</html>
