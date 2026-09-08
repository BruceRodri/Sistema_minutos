<?php
// Web/admin/buses.php
session_start();
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['rol'] ?? '', ['admin', 'secretaria', 'operativo'])) {
    header("Location: ../../index.php");
    exit;
}

require_once '../../Config/conexion.php';
require_once '../../Dao/BusDao.php';

$busDao = new BusDao($conexion);
$listaBuses = $busDao->obtenerTodos();
$totalBuses = count($listaBuses);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buses - Ejecuttrans</title>
    <link rel="icon" href="../../Assets/icons/icon-192x192.png" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
</head>
<body class="bg-gray-100 flex h-screen overflow-hidden">

    <?php include 'components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col overflow-y-auto mt-16 md:mt-0 w-full">
        <header class="h-16 bg-white shadow-sm flex items-center px-4 md:px-8 justify-between border-b border-gray-200">
            <div>
                <h2 class="text-xl md:text-2xl font-bold text-gray-800 text-transparent bg-clip-text bg-gradient-to-r from-blue-700 to-gray-800">
                    Buses
                </h2>
                <p class="text-xs text-gray-500">Visualización de discos y sus códigos QR</p>
            </div>
            <a href="crear_bus.php" class="bg-gradient-to-r from-blue-600 to-blue-800 hover:from-blue-700 hover:to-blue-900 text-white px-5 py-2 rounded-lg shadow-lg transition-all text-sm font-bold flex items-center">
                <i class="fas fa-circle-plus mr-2"></i> Nuevo Bus
            </a>
        </header>

        <div class="p-4 md:p-8 w-full max-w-7xl mx-auto">

            <!-- Resumen -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
                    <p class="text-sm text-gray-500">Total de buses</p>
                    <p class="text-3xl font-bold text-blue-700"><?php echo $totalBuses; ?></p>
                </div>
                <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
                    <p class="text-sm text-gray-500">Habilitados</p>
                    <p class="text-3xl font-bold text-green-600">
                        <?php echo count(array_filter($listaBuses, fn($b) => $b['activo'] == 1)); ?>
                    </p>
                </div>
                <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
                    <p class="text-sm text-gray-500">Deshabilitados</p>
                    <p class="text-3xl font-bold text-red-600">
                        <?php echo count(array_filter($listaBuses, fn($b) => $b['activo'] == 0)); ?>
                    </p>
                </div>
            </div>

            <!-- Tabla de Buses -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-100 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Disco</th>
                            <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Placa</th>
                            <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Estado</th>
                            <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if ($totalBuses === 0): ?>
                        <tr>
                            <td colspan="4" class="px-6 py-10 text-center text-gray-500">
                                <i class="fas fa-bus text-3xl mb-3 text-gray-300"></i>
                                <p>Aún no hay buses registrados.</p>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($listaBuses as $bus): ?>
                            <tr class="hover:bg-gray-50 transition-colors <?php echo ($bus['activo'] == 1) ? '' : 'bg-red-50 opacity-70'; ?>">
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold bg-blue-100 text-blue-800 border border-blue-200">
                                        Disco <?php echo htmlspecialchars($bus['disco']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-mono text-gray-700">
                                    <?php echo htmlspecialchars($bus['placa']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <?php if ($bus['activo'] == 1): ?>
                                        <span class="inline-flex px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-800 border border-green-200">Habilitado</span>
                                    <?php else: ?>
                                        <span class="inline-flex px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-red-800 border border-red-200">Deshabilitado</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <button class="btnVerQR text-blue-700 hover:text-white bg-blue-50 hover:bg-blue-700 border border-blue-200 text-xs font-bold px-3 py-2 rounded-lg transition-all mr-1"
                                            data-disco="<?php echo htmlspecialchars($bus['disco']); ?>">
                                        <i class="fas fa-qrcode mr-1"></i> Ver QR
                                    </button>
                                    <button class="btnToggleBus text-xs font-bold px-3 py-2 rounded-lg transition-all <?php echo ($bus['activo'] == 1) ? 'bg-red-600 hover:bg-red-700 text-white' : 'bg-green-600 hover:bg-green-700 text-white'; ?>"
                                            data-bus-id="<?php echo $bus['id']; ?>"
                                            data-estado="<?php echo $bus['activo']; ?>"
                                            data-disco="<?php echo htmlspecialchars($bus['disco']); ?>">
                                        <?php echo ($bus['activo'] == 1) ? '<i class="fas fa-ban mr-1"></i> Deshabilitar' : '<i class="fas fa-check-circle mr-1"></i> Habilitar'; ?>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Modal QR -->
    <div id="qrModal" class="hidden fixed inset-0 bg-black bg-opacity-60 z-50 flex items-center justify-center p-6">
        <div class="bg-white rounded-2xl p-8 w-full max-w-sm text-center shadow-2xl">
            <div class="mb-4 w-12 h-12 mx-auto rounded-full bg-blue-100 flex items-center justify-center">
                <i class="fas fa-qrcode text-blue-600 text-xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-800 mb-1">Código QR del Bus</h3>
            <p id="qrDiscoLabel" class="text-sm text-gray-500 mb-6"></p>
            <div id="qrCodigo" class="mx-auto inline-block p-3 border border-gray-200 rounded-xl"></div>
            <button id="btnCerrarQR" class="mt-6 w-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-2.5 px-4 rounded-xl transition-all">
                Cerrar
            </button>
        </div>
    </div>

    <script src="../../Assets/js/buses.js"></script>
</body>
</html>
