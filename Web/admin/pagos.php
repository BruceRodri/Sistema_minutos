<?php
// Web/admin/pagos.php
session_start();
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['rol'] ?? '', ['admin', 'secretaria', 'operativo'])) {
    header("Location: ../../index.php");
    exit;
}

require_once '../../Config/conexion.php';
require_once '../../Dao/PagoDao.php';
require_once '../../Config/vistas_pagos.php';

$filtros = obtenerFiltrosPagosAdmin();
$pagoDao = new PagoDao($conexion);
$pagos = $pagoDao->obtenerPagosParaAdmin($filtros);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validación de pagos - Ejecuttrans</title>
    <link rel="icon" href="../../Assets/icons/icon-192x192.png" type="image/png">
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#2563eb">
    <link rel="apple-touch-icon" href="/Assets/icons/icon-192x192.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 flex h-screen overflow-hidden">

    <?php include 'components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col overflow-y-auto mt-16 md:mt-0 w-full">
        <header class="h-16 bg-white shadow-sm flex items-center px-4 md:px-8 justify-between border-b border-gray-200">
            <div>
                <h2 class="text-xl md:text-2xl font-bold text-gray-800 text-transparent bg-clip-text bg-gradient-to-r from-blue-700 to-gray-800">
                    Validación de pagos
                </h2>
                <p class="text-xs text-gray-500">Revisa y aprueba los comprobantes enviados por los conductores</p>
            </div>
        </header>

        <div class="p-4 md:p-8 w-full max-w-full">

            <form method="GET" action="pagos.php" class="mb-5 rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3">
                    <div>
                        <label for="filtroConductorPago" class="block mb-1.5 text-xs font-bold uppercase tracking-wide text-gray-500">
                            <i class="fas fa-user mr-1 text-blue-600"></i>Conductor
                        </label>
                        <input id="filtroConductorPago" name="conductor" type="search" value="<?php echo htmlspecialchars($filtros['conductor']); ?>"
                               placeholder="Nombre o código"
                               class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                    </div>
                    <div>
                        <label for="filtroDiscoPago" class="block mb-1.5 text-xs font-bold uppercase tracking-wide text-gray-500">
                            <i class="fas fa-compact-disc mr-1 text-blue-600"></i>Disco
                        </label>
                        <input id="filtroDiscoPago" name="disco" type="search" value="<?php echo htmlspecialchars($filtros['disco']); ?>"
                               placeholder="Ej.: 002"
                               class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2.5 font-mono text-sm text-center outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                    </div>
                    <div>
                        <label for="filtroFechaDesdePago" class="block mb-1.5 text-xs font-bold uppercase tracking-wide text-gray-500">
                            <i class="far fa-calendar mr-1 text-blue-600"></i>Fecha desde
                        </label>
                        <input id="filtroFechaDesdePago" name="fecha_desde" type="date" value="<?php echo htmlspecialchars($filtros['fecha_desde']); ?>"
                               class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                    </div>
                    <div>
                        <label for="filtroFechaHastaPago" class="block mb-1.5 text-xs font-bold uppercase tracking-wide text-gray-500">
                            <i class="far fa-calendar-check mr-1 text-blue-600"></i>Fecha hasta
                        </label>
                        <input id="filtroFechaHastaPago" name="fecha_hasta" type="date" value="<?php echo htmlspecialchars($filtros['fecha_hasta']); ?>"
                               class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                    </div>
                    <div>
                        <label for="filtroRutaPago" class="block mb-1.5 text-xs font-bold uppercase tracking-wide text-gray-500">
                            <i class="fas fa-route mr-1 text-blue-600"></i>Ruta
                        </label>
                        <input id="filtroRutaPago" name="ruta" type="search" value="<?php echo htmlspecialchars($filtros['ruta']); ?>"
                               placeholder="Buscar ruta..."
                               class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                    </div>
                    <div>
                        <label for="filtroEstadoPago" class="block mb-1.5 text-xs font-bold uppercase tracking-wide text-gray-500">
                            <i class="fas fa-filter mr-1 text-blue-600"></i>Estado
                        </label>
                        <select id="filtroEstadoPago" name="estado" class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                            <option value="">Todos los estados</option>
                            <option value="en_espera" <?php echo $filtros['estado'] === 'en_espera' ? 'selected' : ''; ?>>En espera</option>
                            <option value="aprobado" <?php echo $filtros['estado'] === 'aprobado' ? 'selected' : ''; ?>>Aprobado</option>
                            <option value="anulado" <?php echo $filtros['estado'] === 'anulado' ? 'selected' : ''; ?>>Anulado</option>
                        </select>
                    </div>
                </div>
                <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                    <p class="text-sm text-gray-500">
                        Resultados: <span class="font-bold text-blue-700"><?php echo count($pagos); ?></span>
                    </p>
                    <div class="flex gap-2">
                        <a href="pagos.php" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-bold text-gray-700 hover:bg-gray-50">
                            <i class="fas fa-eraser mr-2"></i>Limpiar
                        </a>
                        <button type="submit" class="inline-flex items-center rounded-lg bg-blue-600 px-5 py-2 text-sm font-bold text-white shadow hover:bg-blue-700">
                            <i class="fas fa-magnifying-glass mr-2"></i>Buscar
                        </button>
                    </div>
                </div>
            </form>

            <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-100 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 w-full min-w-[1350px]">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Conductor</th>
                            <th class="px-5 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Disco</th>
                            <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Fecha(s) pagada(s)</th>
                            <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Ruta(s)</th>
                            <th class="px-5 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Valor Total</th>
                            <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Nro. Comprobante</th>
                            <th class="px-5 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Estado</th>
                            <th class="px-5 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tablaPagos" data-hash="<?php echo hash('sha256', json_encode($pagos)); ?>" class="bg-white divide-y divide-gray-200">
                        <?php include __DIR__ . '/components/filas_pagos.php'; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Modal: Aprobar -->
    <div id="modalAprobar" class="hidden fixed inset-0 z-[70] items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/60"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full p-8 text-center">
            <div class="w-20 h-20 mx-auto rounded-full bg-green-100 flex items-center justify-center mb-5">
                <i class="fas fa-check text-green-600 text-4xl"></i>
            </div>
            <h3 class="text-2xl font-extrabold text-gray-800 mb-3">Aprobar pago</h3>
            <p class="text-gray-600 mb-6">¿Está seguro de aprobar este pago?</p>
            <p id="errorAprobar" class="hidden text-sm font-bold text-red-600 mb-4"></p>
            <div class="grid grid-cols-2 gap-3">
                <button id="cancelarAprobar" type="button" class="py-3 rounded-xl font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 transition-colors">
                    Cancelar
                </button>
                <button id="confirmarAprobar" type="button" class="py-3 rounded-xl font-bold text-white bg-green-600 hover:bg-green-700 transition-colors">
                    <i class="fas fa-check mr-1.5"></i>Aprobar
                </button>
            </div>
        </div>
    </div>

    <!-- Modal: Desaprobar -->
    <div id="modalDesaprobar" class="hidden fixed inset-0 z-[70] items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/60"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full p-8">
            <div class="w-20 h-20 mx-auto rounded-full bg-red-100 flex items-center justify-center mb-5">
                <i class="fas fa-xmark text-red-600 text-4xl"></i>
            </div>
            <h3 class="text-2xl font-extrabold text-gray-800 mb-3 text-center">Rechazar pago</h3>
            <p class="text-gray-600 mb-4">Indique el motivo del rechazo:</p>
            <textarea id="motivoRechazo" rows="4" maxlength="255" required
                      placeholder="Ej.: el comprobante no coincide con el número de disco"
                      class="w-full rounded-xl border-2 border-gray-300 focus:border-red-500 focus:ring-2 focus:ring-red-200 outline-none px-4 py-3 text-base resize-none"></textarea>
            <div class="text-right text-xs text-gray-400 mt-1"><span id="contadorMotivo">0</span>/255</div>
            <p id="errorDesaprobar" class="hidden text-sm font-bold text-red-600 mt-2"></p>
            <div class="grid grid-cols-2 gap-3 mt-4">
                <button id="cancelarDesaprobar" type="button" class="py-3 rounded-xl font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 transition-colors">
                    Cancelar
                </button>
                <button id="confirmarDesaprobar" type="button" class="py-3 rounded-xl font-bold text-white bg-red-600 hover:bg-red-700 transition-colors">
                    <i class="fas fa-xmark mr-1.5"></i>Rechazar
                </button>
            </div>
        </div>
    </div>

    <!-- Modal: Comprobante requerido -->
    <div id="modalAlertaComprobante" class="hidden fixed inset-0 z-[70] items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" data-cerrar="modalAlertaComprobante"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl max-w-sm w-full p-8 text-center">
            <div class="w-20 h-20 mx-auto rounded-full bg-amber-100 flex items-center justify-center mb-5">
                <i class="fas fa-triangle-exclamation text-amber-600 text-4xl"></i>
            </div>
            <h3 class="text-xl font-extrabold text-gray-800 mb-3">Comprobante requerido</h3>
            <p class="text-gray-600 mb-6">Ingresa el número de comprobante antes de aprobar.</p>
            <button id="btnCerrarAlertaComprobante" type="button" class="w-full py-3 rounded-xl font-bold text-white bg-blue-600 hover:bg-blue-700 transition-colors">
                Entendido
            </button>
        </div>
    </div>

    <script src="../../Assets/js/pagos_admin.js?v=<?php echo hash_file('sha256', __DIR__ . '/../../Assets/js/pagos_admin.js'); ?>"></script>
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