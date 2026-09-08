<?php
// Web/admin/turnos.php
session_start();
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['rol'] ?? '', ['admin', 'secretaria', 'operativo'])) {
    header("Location: ../../index.php");
    exit;
}

require_once '../../Config/conexion.php';
require_once '../../Dao/TurnoDao.php';

$turnoDao = new TurnoDao($conexion);
$turnos = $turnoDao->obtenerTurnosHoy();
$totalTurnos = count($turnos);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Turnos del día - Ejecuttrans</title>
    <link rel="icon" href="../../Assets/icons/icon-192x192.png" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 flex h-screen overflow-hidden">

    <?php include 'components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col overflow-y-auto mt-16 md:mt-0 w-full">
        <header class="h-16 bg-white shadow-sm flex items-center px-4 md:px-8 justify-between border-b border-gray-200">
            <div>
                <h2 class="text-xl md:text-2xl font-bold text-gray-800 text-transparent bg-clip-text bg-gradient-to-r from-blue-700 to-gray-800">
                    Turnos del día
                </h2>
                <p class="text-xs text-gray-500">Registro de aperturas realizadas por los conductores</p>
            </div>
            <button type="button" onclick="window.location.reload()" class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-bold text-white shadow hover:bg-blue-700 transition-colors">
                <i class="fas fa-rotate-right mr-2"></i>
                Actualizar
            </button>
        </header>

        <div class="p-4 md:p-8 w-full max-w-7xl mx-auto">
            <div class="mb-6 bg-white rounded-xl border border-gray-100 shadow-sm p-4">
                <p class="text-sm text-gray-500">Turnos abiertos hoy</p>
                <p class="text-3xl font-bold text-blue-700"><?php echo $totalTurnos; ?></p>
            </div>

            <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-100 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Disco</th>
                            <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Conductor</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Fecha</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if ($totalTurnos === 0): ?>
                            <tr>
                                <td colspan="3" class="px-6 py-12 text-center text-gray-500">
                                    <i class="fas fa-clock text-3xl mb-3 text-gray-300"></i>
                                    <p>Aún no se han abierto turnos hoy.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($turnos as $turno): ?>
                                <?php
                                    $fechaApertura = DateTime::createFromFormat(
                                        'Y-m-d H:i:s',
                                        $turno['fecha'] . ' ' . $turno['hora_apertura']
                                    );
                                ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold bg-blue-100 text-blue-800 border border-blue-200">
                                            <?php echo htmlspecialchars($turno['disco']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-mono font-semibold text-gray-700">
                                        <?php echo htmlspecialchars($turno['codigo_conductor'] ?? 'Sin código'); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                        <?php echo htmlspecialchars($fechaApertura ? $fechaApertura->format('d/m/Y H:i:s') : $turno['fecha'] . ' ' . $turno['hora_apertura']); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>
