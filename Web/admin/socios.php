<?php
// Web/admin/socios.php
session_start();
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['rol'] ?? '', ['admin', 'secretaria', 'operativo'])) {
    header("Location: ../../index.php");
    exit;
}

require_once '../../Config/conexion.php';
require_once '../../Dao/SocioDao.php';

$socioDao = new SocioDao($conexion);
$sociosConBuses = $socioDao->obtenerSociosConBuses();
$socios = $socioDao->obtenerTodosSocios();
$discosDisponibles = $socioDao->obtenerDiscosDisponibles();

$sociosAgrupados = [];
foreach ($sociosConBuses as $fila) {
    $sociosAgrupados[$fila['usuario_id']]['usuario_id'] = $fila['usuario_id'];
    $sociosAgrupados[$fila['usuario_id']]['cedula'] = $fila['cedula'];
    $sociosAgrupados[$fila['usuario_id']]['nombres'] = $fila['nombres'];
    $sociosAgrupados[$fila['usuario_id']]['apellidos'] = $fila['apellidos'];
    if ($fila['bus_id'] !== null) {
        $sociosAgrupados[$fila['usuario_id']]['discos'][] = $fila;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Socios - Ejecuttrans</title>
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
                    Socios
                </h2>
                <p class="text-xs text-gray-500">Directorio de socios con sus discos asignados</p>
            </div>
        </header>

        <div class="p-4 md:p-8 w-full max-w-7xl mx-auto">

            <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-100 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Cédula</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Nombres</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Apellidos</th>
                            <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Discos</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($sociosAgrupados)): ?>
                        <tr>
                            <td colspan="4" class="px-6 py-10 text-center text-gray-500">
                                <i class="fas fa-id-card text-3xl mb-3 text-gray-300"></i>
                                <p>Aún no hay socios registrados.</p>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($sociosAgrupados as $socio): ?>
                            <tr class="hover:bg-gray-50 transition-colors align-top">
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-mono text-gray-700">
                                    <?php echo htmlspecialchars($socio['cedula']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                    <?php echo htmlspecialchars($socio['nombres']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                    <?php echo htmlspecialchars($socio['apellidos']); ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if (empty($socio['discos'])): ?>
                                        <div class="flex flex-wrap items-center gap-3">
                                            <span class="text-sm text-gray-400 italic">Sin discos asignados</span>
                                            <button type="button"
                                                    class="btnAgregarDisco inline-flex items-center rounded-lg bg-blue-600 px-3 py-2 text-xs font-bold text-white shadow hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-gray-300 transition-colors"
                                                    data-usuario-id="<?php echo (int)$socio['usuario_id']; ?>"
                                                    data-socio="<?php echo htmlspecialchars($socio['nombres'] . ' ' . $socio['apellidos'], ENT_QUOTES); ?>"
                                                    <?php echo empty($discosDisponibles) ? 'disabled title="No hay discos disponibles"' : ''; ?>>
                                                <i class="fas fa-circle-plus mr-2"></i>Agregar disco
                                            </button>
                                            <?php if (empty($discosDisponibles)): ?>
                                                <span class="text-xs font-semibold text-amber-600">No hay discos disponibles</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="flex flex-col gap-2">
                                            <?php foreach ($socio['discos'] as $disco): ?>
                                            <div class="flex flex-wrap items-center gap-2 p-2 rounded-lg border <?php echo ($disco['bus_activo'] == 1) ? 'bg-gray-50 border-gray-200' : 'bg-red-50 border-red-200'; ?>">
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold <?php echo ($disco['bus_activo'] == 1) ? 'bg-blue-100 text-blue-800 border border-blue-200' : 'bg-red-100 text-red-800 border border-red-200'; ?>">
                                                    Disco <?php echo htmlspecialchars($disco['disco']); ?>
                                                </span>
                                                <?php if ($disco['bus_activo'] == 1): ?>
                                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-bold bg-green-100 text-green-800 border border-green-200">Activo</span>
                                                <?php else: ?>
                                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-800 border border-red-200">Deshabilitado</span>
                                                <?php endif; ?>
                                                <div class="flex gap-1 ml-auto">
                                                    <button class="btnToggleDisco text-xs font-bold px-3 py-1.5 rounded-lg transition-all <?php echo ($disco['bus_activo'] == 1) ? 'bg-red-600 hover:bg-red-700 text-white' : 'bg-green-600 hover:bg-green-700 text-white'; ?>"
                                                            data-bus-id="<?php echo $disco['bus_id']; ?>"
                                                            data-estado="<?php echo $disco['bus_activo']; ?>"
                                                            data-disco="<?php echo htmlspecialchars($disco['disco']); ?>">
                                                        <?php echo ($disco['bus_activo'] == 1) ? '<i class="fas fa-ban mr-1"></i> Deshabilitar disco' : '<i class="fas fa-check-circle mr-1"></i> Habilitar disco'; ?>
                                                    </button>
                                                    <button class="btnCambiarSocio text-blue-700 hover:text-white bg-blue-50 hover:bg-blue-700 border border-blue-200 text-xs font-bold px-3 py-1.5 rounded-lg transition-all"
                                                            data-bus-id="<?php echo $disco['bus_id']; ?>"
                                                            data-disco="<?php echo htmlspecialchars($disco['disco']); ?>">
                                                        <i class="fas fa-arrows-rotate mr-1"></i> Cambiar socio
                                                    </button>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Modal Cambiar Socio -->
    <div id="modalCambiarSocio" class="hidden fixed inset-0 bg-black bg-opacity-60 z-50 flex items-center justify-center p-6">
        <div class="bg-white rounded-2xl p-6 w-full max-w-md max-h-[92vh] overflow-y-auto shadow-2xl">
            <div class="mb-6 w-12 h-12 mx-auto rounded-full bg-blue-100 flex items-center justify-center">
                <i class="fas fa-arrows-rotate text-blue-600 text-xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-800 text-center mb-1">Cambiar Socio</h3>
            <p id="modalDiscoLabel" class="text-sm text-gray-500 text-center mb-6"></p>

            <form id="formCambiarSocio">
                <input type="hidden" id="cambioBusId" name="bus_id">
                <div class="mb-6">
                    <label for="buscarSocioCambio" class="block text-gray-600 text-sm font-bold mb-2">Buscar por cédula o nombre</label>
                    <div class="relative mb-3">
                        <i class="fas fa-search absolute left-3 top-3.5 text-gray-400"></i>
                        <input id="buscarSocioCambio" type="search" placeholder="Cédula o nombre del socio..." autocomplete="off"
                               class="w-full rounded-lg border border-gray-300 bg-white pl-10 pr-3 py-2.5 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none">
                    </div>
                    <p id="coincidenciasSocio" role="status" class="mb-3 text-xs text-gray-500"></p>
                    <input type="hidden" id="cambioUsuario" name="usuario_id">
                    <ul id="sugerenciasSocio" class="max-h-64 overflow-y-auto divide-y divide-gray-100" aria-label="Socios encontrados"></ul>
                    <script id="datosSociosCambio" type="application/json"><?php echo json_encode($socios, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>
                </div>
                <div class="flex gap-3">
                    <button type="button" id="btnCancelarCambio" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-2.5 px-4 rounded-lg transition-all">Cancelar</button>
                    <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-4 rounded-lg transition-all">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Asignar Disco -->
    <div id="modalAgregarDisco" class="hidden fixed inset-0 bg-black bg-opacity-60 z-50 flex items-center justify-center p-6">
        <div class="bg-white rounded-2xl p-8 w-full max-w-sm shadow-2xl">
            <div class="mb-6 w-12 h-12 mx-auto rounded-full bg-blue-100 flex items-center justify-center">
                <i class="fas fa-bus text-blue-600 text-xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-800 text-center mb-1">Agregar Disco</h3>
            <p id="modalSocioLabel" class="text-sm text-gray-500 text-center mb-6"></p>

            <form id="formAgregarDisco">
                <input type="hidden" id="agregarUsuarioId" name="usuario_id">
                <div class="mb-6">
                    <label for="agregarBusId" class="block text-gray-600 text-sm font-bold mb-2">Discos disponibles</label>
                    <select id="agregarBusId" name="bus_id" class="w-full px-3 py-2.5 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" required>
                        <option value="">Seleccione un disco...</option>
                        <?php foreach ($discosDisponibles as $disco): ?>
                            <option value="<?php echo (int)$disco['id']; ?>">
                                Disco <?php echo htmlspecialchars($disco['disco']); ?> — <?php echo htmlspecialchars($disco['placa']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex gap-3">
                    <button type="button" id="btnCancelarAgregarDisco" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-2.5 px-4 rounded-lg transition-all">Cancelar</button>
                    <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-4 rounded-lg transition-all">Asignar</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../../Assets/js/socios.js?v=<?php echo filemtime('../../Assets/js/socios.js'); ?>"></script>
</body>
</html>
