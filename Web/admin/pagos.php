<?php
// Web/admin/pagos.php
session_start();
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['rol'] ?? '', ['admin', 'secretaria', 'operativo'])) {
    header("Location: ../../index.php");
    exit;
}

require_once '../../Config/conexion.php';
require_once '../../Dao/PagoDao.php';

$pagoDao = new PagoDao($conexion);
$pagos = $pagoDao->obtenerPagosParaAdmin();



?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validación de pagos - Ejecuttrans</title>
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
                    Validación de pagos
                </h2>
                <p class="text-xs text-gray-500">Revisa y aprueba los comprobantes enviados por los conductores</p>
            </div>
        </header>

        <div class="p-4 md:p-8 w-full max-w-7xl mx-auto">



            <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-100 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 w-[1150px]">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Conductor</th>
                            <th class="px-5 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Disco</th>
                            <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Fecha(s) pagada(s)</th>
                            <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Ruta(s)</th>
                            <th class="px-5 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Valor Total</th>
                            <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Nro. Comprobante</th>
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

    <!-- Modal: Ver comprobante -->
    <div id="modalComprobante" class="hidden fixed inset-0 z-[70] items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/70" data-cerrar="modalComprobante"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl max-w-3xl w-full p-5 lg:p-6 flex flex-col max-h-[90vh]">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-gray-800">
                    <i class="fas fa-file-invoice mr-2 text-blue-600"></i>Comprobante de pago
                </h3>
                <button type="button" data-cerrar="modalComprobante" class="text-gray-400 hover:text-gray-700 transition-colors">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            <a id="descargarComprobante" class="mb-4 inline-flex self-start rounded-lg bg-blue-600 px-4 py-2 font-bold text-white">Descargar comprobante</a>
            <div id="contenedorComprobante" class="flex-1 overflow-auto rounded-xl bg-gray-100 flex items-center justify-center min-h-80"></div>
        </div>
    </div>

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

    <script src="../../Assets/js/pagos_admin.js?v=<?php echo hash_file('sha256', __DIR__ . '/../../Assets/js/pagos_admin.js'); ?>"></script>
</body>
</html>