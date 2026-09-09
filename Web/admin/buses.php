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
$todosLosBuses = $busDao->obtenerTodos();
$filtroDisco = isset($_GET['disco']) && is_scalar($_GET['disco']) ? trim((string)$_GET['disco']) : '';
$filtroPlaca = isset($_GET['placa']) && is_scalar($_GET['placa']) ? trim((string)$_GET['placa']) : '';
$listaBuses = $busDao->obtenerFiltrados($filtroDisco, $filtroPlaca);
$totalBuses = count($todosLosBuses);
$totalResultados = count($listaBuses);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buses - Ejecuttrans</title>
    <link rel="icon" href="../../Assets/icons/icon-192x192.png" type="image/png">
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#2563eb">
    <link rel="apple-touch-icon" href="/Assets/icons/icon-192x192.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
</head>
<body class="bg-gray-100 flex h-screen overflow-hidden">

    <?php include 'components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col overflow-y-auto mt-16 md:mt-0 w-full">
        <header class="min-h-16 py-3 gap-3 flex-wrap bg-white shadow-sm flex items-center px-4 md:px-8 justify-between border-b border-gray-200">
            <div>
                <h2 class="text-xl md:text-2xl font-bold text-gray-800 text-transparent bg-clip-text bg-gradient-to-r from-blue-700 to-gray-800">
                    Buses
                </h2>
                <p class="text-xs text-gray-500">Gestión de discos: QR, edición, estado y eliminación</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
            <a href="imprimir_qrs.php" target="_blank" rel="noopener" class="bg-white hover:bg-blue-50 text-blue-700 border border-blue-200 px-5 py-2 rounded-lg transition-all text-sm font-bold flex items-center">
                <i class="fas fa-print mr-2"></i> Imprimir todos los QR
            </a>
            <button id="btnNuevoBus" type="button" class="bg-gradient-to-r from-blue-600 to-blue-800 hover:from-blue-700 hover:to-blue-900 text-white px-5 py-2 rounded-lg shadow-lg transition-all text-sm font-bold flex items-center">
                <i class="fas fa-circle-plus mr-2"></i> Nuevo Bus
            </button>
            </div>
        </header>

        <div class="p-4 md:p-8 w-full max-w-7xl mx-auto">

            <!-- Filtros -->
            <form method="GET" action="buses.php" class="mb-5 rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="filtroDiscoBus" class="block mb-2 text-sm font-bold text-gray-700">
                            <i class="fas fa-compact-disc mr-2 text-blue-600"></i>Número de disco
                        </label>
                        <input id="filtroDiscoBus" name="disco" type="search" value="<?php echo htmlspecialchars($filtroDisco); ?>" placeholder="Ej.: 002"
                               class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2.5 text-center font-mono outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                    </div>
                    <div>
                        <label for="filtroPlacaBus" class="block mb-2 text-sm font-bold text-gray-700">
                            <i class="fas fa-id-card mr-2 text-indigo-600"></i>Placa
                        </label>
                        <input id="filtroPlacaBus" name="placa" type="search" value="<?php echo htmlspecialchars($filtroPlaca); ?>" placeholder="Ej.: GHA-4587"
                               class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2.5 text-center font-mono uppercase outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                    </div>
                </div>
                <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                    <p class="text-sm text-gray-500">
                        Registros encontrados: <span class="font-bold text-blue-700"><?php echo $totalResultados; ?></span>
                    </p>
                    <div class="flex gap-2">
                        <a href="buses.php" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-bold text-gray-700 hover:bg-gray-50">
                            <i class="fas fa-eraser mr-2"></i>Limpiar
                        </a>
                        <button type="submit" class="inline-flex items-center rounded-lg bg-blue-600 px-5 py-2 text-sm font-bold text-white shadow hover:bg-blue-700">
                            <i class="fas fa-magnifying-glass mr-2"></i>Buscar
                        </button>
                    </div>
                </div>
            </form>

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
                        <?php if ($totalResultados === 0): ?>
                        <tr>
                            <td colspan="4" class="px-6 py-10 text-center text-gray-500">
                                <i class="fas fa-bus text-3xl mb-3 text-gray-300"></i>
                                <p><?php echo $totalBuses === 0 ? 'Aún no hay buses registrados.' : 'No se encontraron buses con los filtros seleccionados.'; ?></p>
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
                                    <div class="flex flex-wrap justify-center gap-1">
                                        <button class="btnVerQR text-blue-700 hover:text-white bg-blue-50 hover:bg-blue-700 border border-blue-200 text-xs font-bold px-3 py-2 rounded-lg transition-all"
                                                data-disco="<?php echo htmlspecialchars($bus['disco']); ?>">
                                            <i class="fas fa-qrcode mr-1"></i> QR
                                        </button>
                                        <button class="btnEditarBus text-yellow-700 hover:text-white bg-yellow-50 hover:bg-yellow-600 border border-yellow-200 text-xs font-bold px-3 py-2 rounded-lg transition-all"
                                                data-id="<?php echo $bus['id']; ?>"
                                                data-disco="<?php echo htmlspecialchars($bus['disco']); ?>"
                                                data-placa="<?php echo htmlspecialchars($bus['placa']); ?>">
                                            <i class="fas fa-pen mr-1"></i> Editar
                                        </button>
                                        <button class="btnToggleBus text-xs font-bold px-3 py-2 rounded-lg transition-all <?php echo ($bus['activo'] == 1) ? 'bg-red-600 hover:bg-red-700 text-white' : 'bg-green-600 hover:bg-green-700 text-white'; ?>"
                                                data-bus-id="<?php echo $bus['id']; ?>"
                                                data-estado="<?php echo $bus['activo']; ?>"
                                                data-disco="<?php echo htmlspecialchars($bus['disco']); ?>">
                                            <?php echo ($bus['activo'] == 1) ? '<i class="fas fa-ban mr-1"></i> Deshabilitar' : '<i class="fas fa-check-circle mr-1"></i> Habilitar'; ?>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div id="modalCrearBus" class="fixed inset-0 z-50 hidden items-center justify-center bg-gray-900/60 p-4" role="dialog" aria-modal="true" aria-labelledby="tituloCrearBus">
        <div class="flex max-h-[92vh] w-full max-w-md flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div class="flex items-start justify-between bg-gradient-to-r from-blue-700 to-blue-900 px-5 py-4 text-white md:px-6">
                <h3 id="tituloCrearBus" class="text-lg font-bold"><i class="fas fa-bus mr-2"></i>Crear nuevo bus</h3>
                <button id="btnCerrarCrearBus" type="button" class="ml-4 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-blue-100 hover:bg-white/15 hover:text-white" aria-label="Cerrar formulario">
                    <i class="fas fa-times"></i>
                </button>
            </div>
                <form id="formCrearBus" class="overflow-y-auto p-5 md:p-6">
                    <div id="alerta" role="status" class="hidden mb-4 p-3 rounded text-sm text-center"></div>
                    <div class="mb-4">
                        <label for="disco" class="block text-gray-600 text-sm font-bold mb-2">Número de Disco</label>
                        <input type="text" id="disco" name="disco" required placeholder="Ej: 1, 2, 3..."
                            class="w-full px-3 py-2.5 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                    </div>

                    <div class="mb-6">
                        <label for="placa" class="block text-gray-600 text-sm font-bold mb-2">Placa <span class="text-gray-400 font-normal">(opcional)</span></label>
                        <input type="text" id="placa" name="placa" placeholder="Ej: ABC-1234"
                            class="w-full px-3 py-2.5 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                    </div>

                    <button type="submit" id="btnSubmit"
                        class="w-full bg-gradient-to-r from-blue-600 to-blue-800 hover:from-blue-700 hover:to-blue-900 text-white font-bold py-3 px-4 rounded-lg shadow-lg transition-all flex items-center justify-center">
                        <i class="fas fa-save mr-2"></i> Guardar Bus
                    </button>
                    <button id="btnCancelarCrearBus" type="button" class="mt-3 w-full rounded-lg bg-gray-100 px-4 py-2.5 font-bold text-gray-700 hover:bg-gray-200">Cancelar</button>
                </form>

        </div>
    </div>

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

    <!-- Modal Editar Bus -->
    <div id="modalEditar" class="hidden fixed inset-0 bg-black bg-opacity-60 z-50 flex items-center justify-center p-6">
        <div class="bg-white rounded-2xl p-8 w-full max-w-sm shadow-2xl">
            <div class="mb-6 w-12 h-12 mx-auto rounded-full bg-blue-100 flex items-center justify-center">
                <i class="fas fa-pen text-blue-600 text-xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-800 text-center mb-6">Editar Bus</h3>

            <form id="formEditarBus">
                <input type="hidden" id="editId" name="id">
                <div class="mb-4">
                    <label for="editDisco" class="block text-gray-600 text-sm font-bold mb-2">Número de Disco</label>
                    <input type="text" id="editDisco" name="disco" required class="w-full px-3 py-2.5 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                </div>
                <div class="mb-6">
                    <label for="editPlaca" class="block text-gray-600 text-sm font-bold mb-2">Placa</label>
                    <input type="text" id="editPlaca" name="placa" class="w-full px-3 py-2.5 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                </div>
                <div class="flex gap-3">
                    <button type="button" id="btnCancelarEditar" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-2.5 px-4 rounded-lg transition-all">Cancelar</button>
                    <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-4 rounded-lg transition-all">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../../Assets/js/buses.js?v=<?php echo hash_file('sha256', __DIR__ . '/../../Assets/js/buses.js'); ?>"></script>
<script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(reg => console.log('Service Worker registrado correctamente.', reg))
                    .catch(err => console.log('Falló el registro del Service Worker.', err));
            });
        }
    </script>
</body>
</html>
