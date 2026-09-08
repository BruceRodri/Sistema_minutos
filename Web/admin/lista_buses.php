<?php
// Web/admin/lista_buses.php
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
    <title>Lista de Buses - Ejecuttrans</title>
    <link rel="icon" href="../../Assets/icons/icon-192x192.png" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 flex h-screen overflow-hidden">

    <?php include 'components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col overflow-y-auto mt-16 md:mt-0 w-full">
        <header class="h-16 bg-white shadow-sm flex items-center px-4 md:px-8 justify-between border-b border-gray-200">
            <h2 class="text-xl md:text-2xl font-bold text-gray-800 text-transparent bg-clip-text bg-gradient-to-r from-blue-700 to-gray-800">
                Lista de Buses
            </h2>
        </header>

        <div class="p-4 md:p-8 w-full max-w-7xl mx-auto">

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
                            <tr class="hover:bg-gray-50 transition-colors">
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
                                    <button class="btnEditarBus text-blue-700 hover:text-white bg-blue-50 hover:bg-blue-700 border border-blue-200 text-xs font-bold px-3 py-2 rounded-lg transition-all mr-1"
                                            data-id="<?php echo $bus['id']; ?>"
                                            data-disco="<?php echo htmlspecialchars($bus['disco']); ?>"
                                            data-placa="<?php echo htmlspecialchars($bus['placa']); ?>">
                                        <i class="fas fa-pen mr-1"></i> Editar
                                    </button>
                                    <button class="btnEliminarBus text-red-700 hover:text-white bg-red-50 hover:bg-red-700 border border-red-200 text-xs font-bold px-3 py-2 rounded-lg transition-all"
                                            data-id="<?php echo $bus['id']; ?>"
                                            data-disco="<?php echo htmlspecialchars($bus['disco']); ?>">
                                        <i class="fas fa-trash mr-1"></i> Eliminar
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

    <script src="../../Assets/js/buses.js"></script>
</body>
</html>
