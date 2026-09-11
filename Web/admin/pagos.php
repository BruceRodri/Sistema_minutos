<?php
// Web/admin/pagos.php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../index.php");
    exit;
}

require_once '../../Config/conexion.php';
require_once '../../Config/permisos.php';
exigirPermisoModulo($conexion, 'web_pagos', 'dashboard.php');
require_once '../../Dao/PagoDao.php';
require_once '../../Config/vistas_pagos.php';

$filtros = obtenerFiltrosPagosAdmin();
$filtrosManuales = obtenerFiltrosPagosManuales();
$pagoDao = new PagoDao($conexion);
$pagos = $pagoDao->obtenerPagosParaAdmin($filtros);
$pagosManuales = $pagoDao->obtenerPagosManuales($filtrosManuales);
$seccionManuales = ($_GET['seccion'] ?? '') === 'manuales';
$pendientesManual = obtenerPagablesVista($pagoDao);
$discosPendientes = array_values(array_unique(array_map(static fn($p) => $p['disco'], $pendientesManual)));
sort($discosPendientes, SORT_NATURAL);
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

        <!-- Subsecciones del módulo de pagos -->
        <nav class="px-4 md:px-8 pt-4">
            <div class="flex flex-wrap gap-1 border-b border-gray-200">
                <button type="button" data-seccion="seccionPagosApp"
                    class="tab-pagos inline-flex items-center gap-2 rounded-t-xl px-5 py-2.5 text-sm font-bold border-b-4 border-blue-600 bg-blue-50 text-blue-700 transition-colors">
                    <i class="fas fa-file-circle-check text-blue-600"></i>Pagos del aplicativo
                </button>
                <button type="button" data-seccion="seccionPagosManuales"
                    class="tab-pagos inline-flex items-center gap-2 rounded-t-xl px-5 py-2.5 text-sm font-bold border-b-4 border-transparent text-gray-500 hover:text-blue-600 transition-colors">
                    <i class="fas fa-hand-holding-dollar text-blue-600"></i>Pagos manuales
                </button>
            </div>
        </nav>

        <div class="p-4 md:p-8 w-full max-w-full" id="seccionPagosApp">

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
                            <option value="incompleto" <?php echo $filtros['estado'] === 'incompleto' ? 'selected' : ''; ?>>Incompleto</option>
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
                            <th class="px-5 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Valores individuales</th>
                            <th class="px-5 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Valor Total</th>
                            <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Nro. Comprobante</th>
                            <th class="px-5 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Comprobante</th>
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

        <!-- ===================== Pagos manuales (interfaz) ===================== -->
        <section id="seccionPagosManuales" class="hidden p-4 md:p-8 w-full max-w-full">
            <div class="flex justify-end mb-4"><button id="abrirPagoManual" type="button" class="rounded-xl bg-blue-600 px-5 py-3 font-bold text-white">Registrar pago manual</button></div>
            <div id="modalPagoManual" role="dialog" aria-modal="true" aria-label="Registrar pago manual" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-950/60 p-4">
            <div class="max-h-[90vh] w-full max-w-4xl overflow-y-auto rounded-xl border border-gray-100 bg-white p-4 shadow-sm md:p-6">
                <button id="cerrarPagoManual" type="button" aria-label="Cerrar" class="float-right rounded-lg px-3 py-2 text-gray-600">✕</button>
                <div class="mb-5 flex items-start gap-3">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-700">
                        <i class="fas fa-hand-holding-dollar text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-extrabold text-gray-800">Pagos manuales</h3>
                        <p class="text-sm text-gray-500">Registra aquí pagos que no fueron enviados desde el aplicativo móvil.</p>
                    </div>
                </div>

                <form id="formPagoManual" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <div class="relative">
                        <label for="mDisco" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-500"><i class="fas fa-compact-disc mr-1 text-blue-600"></i>Disco</label>
                        <input id="mDisco" name="disco" type="text" inputmode="numeric" placeholder="Ej.: 02" autocomplete="off"
                               class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2.5 text-center font-mono text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                        <ul id="sugMDisco" class="hidden absolute left-0 right-0 z-30 mt-1 max-h-64 overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-xl" aria-label="Discos"></ul>
                    </div>
                    <div>
                        <label for="mCodigoIngreso" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-500"><i class="fas fa-hashtag mr-1 text-blue-600"></i>Código de ingreso</label>
                        <input id="mCodigoIngreso" name="codigo_ingreso" type="text" value="ING" placeholder="Código único (no se repite)" autocomplete="off"
                               class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                        <p id="msgCodigoIngreso" class="mt-1 hidden text-xs font-bold text-red-600">
                            <i class="fas fa-circle-exclamation mr-1"></i>Este código ya fue utilizado y no debe repetirse.
                        </p>
                    </div>
                    <div>
                        <label for="mComprobante" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-500"><i class="fas fa-paperclip mr-1 text-blue-600"></i>Comprobante</label>
                        <input id="mComprobante" name="comprobante" type="file" accept="image/*,application/pdf"
                               class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2.5 text-sm outline-none file:mr-3 file:rounded-lg file:border-0 file:bg-blue-600 file:px-4 file:py-2 file:text-sm file:font-bold file:text-white">
                    </div>
                </form>

                <!-- Checklist de días pendientes del disco -->
                <div id="bloquePendientes" class="hidden mt-6 rounded-xl border border-blue-100 bg-blue-50/40 p-4">
                    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <h4 class="text-sm font-extrabold text-gray-800 uppercase tracking-wide"><i class="fas fa-calendar-check mr-1 text-blue-600"></i>Días pendientes</h4>
                            <p id="pendientesSub" class="text-sm text-gray-500">Selecciona qué días cancela este comprobante.</p>
                        </div>
                        <p class="text-sm text-gray-600">Total: <span id="pendientesTotal" class="text-base font-extrabold text-blue-700">$0.00</span>
                            <span class="hidden text-xs font-bold text-gray-400" id="pendientesCount"></span>
                        </p>
                    </div>
                    <div id="listaPendientes" class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-72 overflow-y-auto"></div>
                    <div id="sinPendientesDisco" class="hidden mt-2 rounded-lg bg-white p-4 text-center text-sm text-gray-500">
                        No hay pagos pendientes para este disco.
                    </div>
                </div>

                <div class="mt-5 flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 pt-4">
                    <div>
                        <p id="estadoPagoManual" class="mt-1 hidden text-sm font-bold text-red-600"></p>
                    </div>
                    <div class="flex gap-2">
                        <button type="button" id="btnLimpiarPagoManual" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-bold text-gray-700 hover:bg-gray-50">
                            <i class="fas fa-eraser mr-2"></i>Limpiar
                        </button>
                        <button type="submit" form="formPagoManual" id="btnRegistrarPagoManual" class="inline-flex items-center rounded-lg bg-blue-600 px-5 py-2 text-sm font-bold text-white shadow hover:bg-blue-700">
                            <i class="fas fa-file-circle-check mr-2"></i>Registrar pago manual
                        </button>
                    </div>
                </div>
            </div>

            </div>
            <form method="GET" action="pagos.php" id="formFiltrosPagosManuales" class="mt-5 mb-5 rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
                <input type="hidden" name="seccion" value="manuales">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
                    <div>
                        <label for="filtroDiscoManual" class="block mb-1.5 text-xs font-bold uppercase tracking-wide text-gray-500">
                            <i class="fas fa-compact-disc mr-1 text-blue-600"></i>Disco
                        </label>
                        <input id="filtroDiscoManual" name="m_disco" type="search" value="<?php echo htmlspecialchars($filtrosManuales['disco']); ?>"
                               placeholder="Ej.: 002"
                               class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2.5 font-mono text-sm text-center outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                    </div>
                    <div>
                        <label for="filtroFechaDesdeManual" class="block mb-1.5 text-xs font-bold uppercase tracking-wide text-gray-500">
                            <i class="far fa-calendar mr-1 text-blue-600"></i>Fecha desde
                        </label>
                        <input id="filtroFechaDesdeManual" name="m_fecha_desde" type="date" value="<?php echo htmlspecialchars($filtrosManuales['fecha_desde']); ?>"
                               class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                    </div>
                    <div>
                        <label for="filtroFechaHastaManual" class="block mb-1.5 text-xs font-bold uppercase tracking-wide text-gray-500">
                            <i class="far fa-calendar-check mr-1 text-blue-600"></i>Fecha hasta
                        </label>
                        <input id="filtroFechaHastaManual" name="m_fecha_hasta" type="date" value="<?php echo htmlspecialchars($filtrosManuales['fecha_hasta']); ?>"
                               class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                    </div>
                    <div>
                        <label for="filtroRutaManual" class="block mb-1.5 text-xs font-bold uppercase tracking-wide text-gray-500">
                            <i class="fas fa-route mr-1 text-blue-600"></i>Ruta
                        </label>
                        <input id="filtroRutaManual" name="m_ruta" type="search" value="<?php echo htmlspecialchars($filtrosManuales['ruta']); ?>"
                               placeholder="Buscar ruta..."
                               class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                    </div>
                </div>
                <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                    <p class="text-sm text-gray-500">
                        Resultados: <span class="font-bold text-blue-700" id="resultadosManuales"><?php echo count($pagosManuales); ?></span>
                    </p>
                    <div class="flex gap-2">
                        <a href="pagos.php?seccion=manuales" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-bold text-gray-700 hover:bg-gray-50">
                            <i class="fas fa-eraser mr-2"></i>Limpiar
                        </a>
                        <button type="submit" class="inline-flex items-center rounded-lg bg-blue-600 px-5 py-2 text-sm font-bold text-white shadow hover:bg-blue-700">
                            <i class="fas fa-magnifying-glass mr-2"></i>Buscar
                        </button>
                    </div>
                </div>
            </form>

            <div class="mt-5 overflow-hidden rounded-xl border border-gray-100 bg-white shadow-md overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 min-w-[1150px]">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-5 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Disco</th>
                            <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Fecha(s) pagada(s)</th>
                            <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Ruta(s)</th>
                            <th class="px-5 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Valor Total</th>
                            <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Código de ingreso</th>
                            <th class="px-5 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Comprobante</th>
                            <th class="px-5 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Estado</th>
                        </tr>
                    </thead>
                    <tbody id="tablaPagosManuales" class="bg-white divide-y divide-gray-200">
                        <?php if (empty($pagosManuales)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                <p class="mb-2">
                                    <i class="fas fa-hand-holding-dollar text-3xl text-gray-300"></i>
                                </p>
                                <p>Aún no hay pagos manuales que coincidan con los filtros seleccionados.</p>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($pagosManuales as $manual):
                                $esManualAnulado = $manual['estado'] === 'anulado';
                                $esManualAprobado = $manual['estado'] === 'aprobado';
                                $esManualIncompleto = $manual['estado'] === 'incompleto';
                                $rutasManual = $manual['rutas'] ?: ['—'];
                            ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-5 py-4 whitespace-nowrap text-center">
                                    <?php foreach ($manual['discos'] as $discoManual): ?>
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold bg-blue-100 text-blue-800 border border-blue-200 mr-1">
                                            <?php echo htmlspecialchars($discoManual); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </td>
                                <td class="px-5 py-4">
                                    <?php foreach ($manual['fechas'] as $fechaManual): ?>
                                        <p class="text-sm text-gray-700 whitespace-nowrap">
                                            <i class="far fa-calendar mr-1 text-blue-400"></i><?php echo date('d/m/Y', strtotime($fechaManual)); ?>
                                        </p>
                                    <?php endforeach; ?>
                                    <?php if (!$manual['fechas']): ?>
                                        <p class="text-sm text-gray-400">—</p>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-4 max-w-60 text-left">
                                    <?php foreach ($rutasManual as $rutaManual): ?>
                                        <p class="text-sm text-gray-600 truncate"><i class="fas fa-route mr-1 text-indigo-400"></i><?php echo htmlspecialchars($rutaManual); ?></p>
                                    <?php endforeach; ?>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-center">
                                    <span class="text-base font-extrabold text-gray-800">$ <?php echo number_format((float)$manual['monto_total'], 2, '.', ','); ?></span>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <span class="inline-flex rounded-lg border border-blue-200 bg-blue-50 px-3 py-1 text-sm font-mono font-bold text-blue-700"><?php echo htmlspecialchars($manual['codigo_ingreso']); ?></span>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-center">
                                    <?php if (trim((string)$manual['comprobante']) !== ''): ?>
                                        <a href="../../Controllers/ComprobanteController.php?pago_id=<?php echo (int)$manual['id']; ?>" target="_blank" rel="noopener"
                                           title="Abrir comprobante en otra pestaña"
                                           class="inline-flex items-center justify-center rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-bold text-blue-700 hover:bg-blue-100 transition-colors">
                                            <i class="fas fa-eye mr-1.5"></i>Ver
                                        </a>
                                    <?php else: ?>
                                        <span class="text-xs font-bold text-gray-400">Sin comprobante</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-center">
                                    <?php if ($esManualAprobado): ?>
                                        <span class="inline-flex items-center rounded-full border border-green-200 bg-green-50 px-2.5 py-0.5 text-xs font-bold text-green-700">
                                            <i class="fas fa-circle-check mr-1"></i>Aprobado
                                        </span>
                                    <?php elseif ($esManualAnulado): ?>
                                        <span class="inline-flex items-center rounded-full border border-red-200 bg-red-50 px-2.5 py-0.5 text-xs font-bold text-red-700">
                                            <i class="fas fa-circle-xmark mr-1"></i>Anulado
                                        </span>
                                    <?php elseif ($esManualIncompleto): ?>
                                        <span class="inline-flex items-center rounded-full border border-orange-200 bg-orange-50 px-2.5 py-0.5 text-xs font-bold text-orange-700">
                                            <i class="fas fa-exclamation-circle mr-1"></i>Incompleto
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-2.5 py-0.5 text-xs font-bold text-amber-700">
                                            <i class="fas fa-clock mr-1"></i>En espera
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
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

    <!-- Modal: Incompleto -->
    <div id="modalIncompleto" class="hidden fixed inset-0 z-[70] items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/60"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full p-8">
            <div class="w-20 h-20 mx-auto rounded-full bg-amber-100 flex items-center justify-center mb-5">
                <i class="fas fa-triangle-exclamation text-amber-600 text-4xl"></i>
            </div>
            <h3 class="text-2xl font-extrabold text-gray-800 mb-3 text-center">Marcar como incompleto</h3>
            <p class="text-gray-600 mb-4">Indique la cantidad faltante que el conductor debe adjuntar:</p>
            <textarea id="motivoIncompleto" rows="4" maxlength="255" required
                      placeholder="Ej.: Faltan $3.00. Adjunte el comprobante del valor restante."
                      class="w-full rounded-xl border-2 border-gray-300 focus:border-amber-500 focus:ring-2 focus:ring-amber-200 outline-none px-4 py-3 text-base resize-none"></textarea>
            <div class="text-right text-xs text-gray-400 mt-1"><span id="contadorMotivoIncompleto">0</span>/255</div>
            <p id="errorIncompleto" class="hidden text-sm font-bold text-red-600 mt-2"></p>
            <div class="grid grid-cols-2 gap-3 mt-4">
                <button id="cancelarIncompleto" type="button" class="py-3 rounded-xl font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 transition-colors">
                    Cancelar
                </button>
                <button id="confirmarIncompleto" type="button" class="py-3 rounded-xl font-bold text-white bg-amber-500 hover:bg-orange-600 transition-colors">
                    <i class="fas fa-triangle-exclamation mr-1.5"></i>Marcar incompleto
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
            <p class="text-gray-600 mb-6">Ingresa un número de comprobante para cada uno de los archivos subidos antes de aprobar.</p>
            <button id="btnCerrarAlertaComprobante" type="button" class="w-full py-3 rounded-xl font-bold text-white bg-blue-600 hover:bg-blue-700 transition-colors">
                Entendido
            </button>
        </div>
    </div>

    <script id="datosPagoManual" type="application/json">
    <?php echo json_encode([
        'pendientes' => $pendientesManual,
        'discos' => $discosPendientes
    ], JSON_UNESCAPED_UNICODE); ?>
    </script>
    <script src="../../Assets/js/pagos_admin.js?v=<?php echo hash_file('sha256', __DIR__ . '/../../Assets/js/pagos_admin.js'); ?>"></script>
    <script>
        (() => {
            const URL_PAGO_MANUAL = '../../Controllers/AdminPagoManualController.php';

            const pestanas = document.querySelectorAll('.tab-pagos');
            const cambiar = (seccion) => {
                document.getElementById('seccionPagosApp')?.classList.toggle('hidden', seccion !== 'seccionPagosApp');
                document.getElementById('seccionPagosManuales')?.classList.toggle('hidden', seccion !== 'seccionPagosManuales');
                pestanas.forEach((b) => {
                    const activo = b.dataset.seccion === seccion;
                    b.classList.toggle('border-blue-600', activo);
                    b.classList.toggle('bg-blue-50', activo);
                    b.classList.toggle('text-blue-700', activo);
                    b.classList.toggle('border-transparent', !activo);
                    b.classList.toggle('text-gray-500', !activo);
                });
            };
            pestanas.forEach((b) => b.addEventListener('click', () => {
                cambiar(b.dataset.seccion);
                const url = new URL(window.location.href);
                if (b.dataset.seccion === 'seccionPagosManuales') url.searchParams.set('seccion', 'manuales');
                else url.searchParams.delete('seccion');
                window.history.replaceState({}, '', url.pathname + url.search);
            }));
            if (new URLSearchParams(window.location.search).get('seccion') === 'manuales') {
                cambiar('seccionPagosManuales');
            }

            const esc = (texto) => String(texto ?? '').replace(/[&<>"']/g, (c) => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
            }[c]));

            // ---------- Disco (lista desplegable) + checklist de días pendientes ----------
            const datosManual = JSON.parse(String(document.getElementById('datosPagoManual')?.textContent || '{"pendientes":[],"discos":[]}'));
            let pendientes = Array.isArray(datosManual.pendientes) ? datosManual.pendientes : [];

            const inputDisco = document.getElementById('mDisco');
            const listaDiscos = document.getElementById('sugMDisco');
            const bloquePendientes = document.getElementById('bloquePendientes');
            const listaPendientes = document.getElementById('listaPendientes');
            const sinPendientesDisco = document.getElementById('sinPendientesDisco');
            const pendientesSub = document.getElementById('pendientesSub');
            const pendientesTotal = document.getElementById('pendientesTotal');
            const pendientesCount = document.getElementById('pendientesCount');
            let temporizadorDiscos = null;
            let discoActivo = '';

            const normDisco = (valor) => {
                const d = String(valor ?? '').replace(/\D+/g, '');
                return d ? String(parseInt(d, 10)) : '';
            };

            const actualizarResumenPendientes = () => {
                if (!listaPendientes) return;
                const checks = [...listaPendientes.querySelectorAll('.cb-pendiente:checked')];
                const total = checks.reduce((suma, c) => suma + parseFloat(c.dataset.valor || '0'), 0);
                if (pendientesTotal) pendientesTotal.textContent = '$ ' + total.toFixed(2);
                if (pendientesCount) {
                    pendientesCount.textContent = checks.length ? '(' + checks.length + (checks.length === 1 ? ' día)' : ' días)') : '';
                    pendientesCount.classList.toggle('hidden', !checks.length);
                }
            };

            const renderPendientes = (disco) => {
                const d = normDisco(disco);
                const bloque = document.getElementById('bloquePendientes');
                if (!bloque) return;
                if (!d) {
                    bloque.classList.add('hidden');
                    listaPendientes?.replaceChildren();
                    return;
                }
                const dias = pendientes.filter((p) => normDisco(p.disco) === d);
                listaPendientes?.replaceChildren();
                bloque.classList.remove('hidden');
                if (!dias.length) {
                    sinPendientesDisco?.classList.remove('hidden');
                    if (pendientesSub) pendientesSub.textContent = 'Sin pagos pendientes para el disco ' + disco + '.';
                    actualizarResumenPendientes();
                    return;
                }
                sinPendientesDisco?.classList.add('hidden');
                const discoMostrado = String(dias[0].disco);
                discoActivo = discoMostrado;
                if (pendientesSub) pendientesSub.textContent = 'Disco ' + discoMostrado + ' — marca los días que cancela este comprobante.';
                dias.forEach((dia) => {
                    const etiqueta = document.createElement('label');
                    etiqueta.className = 'flex items-start gap-3 rounded-lg border border-gray-200 bg-white p-3 hover:border-blue-300 transition-colors cursor-pointer';
                    const check = document.createElement('input');
                    check.type = 'checkbox';
                    check.dataset.id = dia.id;
                    check.dataset.valor = dia.valor;
                    check.className = 'cb-pendiente mt-0.5 h-4 w-4 shrink-0 accent-blue-600 cursor-pointer';
                    check.addEventListener('change', actualizarResumenPendientes);
                    const contenido = document.createElement('div');
                    contenido.className = 'flex-1';
                    const nombreDia = document.createElement('p');
                    nombreDia.className = 'text-sm font-bold text-gray-800';
                    nombreDia.textContent = dia.fechaLegible;
                    const rutaDia = document.createElement('p');
                    rutaDia.className = 'text-xs text-gray-500';
                    rutaDia.textContent = 'Ruta: ' + (dia.ruta || 'Sin ruta') + ' · Disco ' + dia.disco;
                    const valorDia = document.createElement('p');
                    valorDia.className = 'text-sm font-extrabold text-blue-700 mt-0.5';
                    valorDia.textContent = '$ ' + dia.valorFmt;
                    contenido.append(nombreDia, rutaDia, valorDia);
                    etiqueta.append(check, contenido);
                    listaPendientes?.appendChild(etiqueta);
                });
                actualizarResumenPendientes();
            };

            const renderListaDiscos = (termino) => {
                if (!listaDiscos) return;
                const t = normDisco(termino);
                listaDiscos.replaceChildren();
                if (!t) {
                    listaDiscos.classList.add('hidden');
                    return;
                }
                const filtradas = (datosManual.discos || []).filter((disco) => normDisco(disco).startsWith(t));
                if (!filtradas.length) {
                    listaDiscos.classList.add('hidden');
                    return;
                }
                filtradas.forEach((disco) => {
                    const boton = document.createElement('button');
                    boton.type = 'button';
                    boton.className = 'w-full px-3 py-2.5 text-center font-mono text-sm font-bold text-gray-800 hover:bg-blue-50 focus:bg-blue-50 focus:outline-none transition-colors';
                    boton.textContent = 'Disco ' + disco;
                    boton.addEventListener('click', () => {
                        inputDisco.value = disco;
                        listaDiscos.classList.add('hidden');
                        renderPendientes(disco);
                    });
                    listaDiscos.appendChild(boton);
                });
                listaDiscos.classList.remove('hidden');
            };

            inputDisco?.addEventListener('input', () => {
                clearTimeout(temporizadorDiscos);
                temporizadorDiscos = setTimeout(() => {
                    renderListaDiscos(inputDisco.value);
                    const d = normDisco(inputDisco.value);
                    const coincide = (datosManual.discos || []).some((disco) => normDisco(disco) === d);
                    renderPendientes(coincide ? d : '');
                }, 250);
            });
            inputDisco?.addEventListener('focus', () => {
                clearTimeout(temporizadorDiscos);
                temporizadorDiscos = setTimeout(() => renderListaDiscos(inputDisco.value), 120);
            });
            document.addEventListener('pointerdown', (evento) => {
                if (listaDiscos && !listaDiscos.contains(evento.target) && evento.target !== inputDisco) {
                    listaDiscos.classList.add('hidden');
                }
            });

            const formManual = document.getElementById('formPagoManual');
            const estadoManual = document.getElementById('estadoPagoManual');
            const btnRegistrar = document.getElementById('btnRegistrarPagoManual');
            const btnLimpiar = document.getElementById('btnLimpiarPagoManual');
            const tablaManual = document.getElementById('tablaPagosManuales');
            const textoOriginalBtn = btnRegistrar?.textContent;

            // ---------- Verificación en tiempo real del código de ingreso ----------
            const inputCodigoIngreso = document.getElementById('mCodigoIngreso');
            const msgCodigoIngreso = document.getElementById('msgCodigoIngreso');
            let temporizadorCodigo = null;
            let peticionCodigoActual = 0;

            const marcarCodigoDuplicado = (duplicado) => {
                if (!inputCodigoIngreso) return;
                if (duplicado) {
                    inputCodigoIngreso.classList.add('border-red-500', 'bg-red-50', 'focus:border-red-500', 'focus:ring-red-200');
                    msgCodigoIngreso?.classList.remove('hidden');
                } else {
                    inputCodigoIngreso.classList.remove('border-red-500', 'bg-red-50', 'focus:border-red-500', 'focus:ring-red-200');
                    msgCodigoIngreso?.classList.add('hidden');
                }
            };

            const verificarCodigoIngreso = () => {
                if (!inputCodigoIngreso) return;
                const codigo = inputCodigoIngreso.value.trim();
                if (codigo === '') {
                    marcarCodigoDuplicado(false);
                    return;
                }
                const numero = ++peticionCodigoActual;
                const params = new URLSearchParams({ accion: 'verificar_codigo_ingreso', codigo_ingreso: codigo });
                fetch(URL_PAGO_MANUAL + '?' + params.toString())
                    .then((r) => r.json())
                    .then((data) => {
                        if (numero !== peticionCodigoActual) return;
                        marcarCodigoDuplicado(!(data && data.disponible));
                    })
                    .catch(() => {
                        if (numero === peticionCodigoActual) marcarCodigoDuplicado(false);
                    });
            };

            inputCodigoIngreso?.addEventListener('input', () => {
                inputCodigoIngreso.value = inputCodigoIngreso.value.toLocaleUpperCase('es');
                clearTimeout(temporizadorCodigo);
                temporizadorCodigo = setTimeout(verificarCodigoIngreso, 400);
            });
            inputCodigoIngreso?.addEventListener('focus', () => {
                if (!inputCodigoIngreso.value.trim()) return;
                clearTimeout(temporizadorCodigo);
                temporizadorCodigo = setTimeout(verificarCodigoIngreso, 300);
            });
            verificarCodigoIngreso();
            const modalPagoManual = document.getElementById('modalPagoManual');
            const cerrarPagoManual = () => {
                modalPagoManual.classList.add('hidden');
                modalPagoManual.classList.remove('flex');
                document.getElementById('abrirPagoManual').focus();
            };
            document.getElementById('abrirPagoManual').addEventListener('click', () => {
                modalPagoManual.classList.remove('hidden');
                modalPagoManual.classList.add('flex');
                document.getElementById('mDisco').focus();
            });
            document.getElementById('cerrarPagoManual').addEventListener('click', cerrarPagoManual);
            modalPagoManual.addEventListener('click', (e) => { if (e.target === modalPagoManual) cerrarPagoManual(); });
            document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !modalPagoManual.classList.contains('hidden')) cerrarPagoManual(); });

            const mostrarEstado = (tipo, mensaje) => {
                if (!estadoManual) return;
                estadoManual.textContent = mensaje;
                estadoManual.classList.toggle('hidden', !mensaje);
                estadoManual.className = 'mt-1 text-sm font-bold ' +
                    (tipo === 'success' ? 'text-green-600' : 'text-red-600');
                clearTimeout(estadoManual.dataset.timer);
                if (tipo === 'success') {
                    estadoManual.dataset.timer = setTimeout(() => { estadoManual.classList.add('hidden'); }, 6000);
                }
            };

            const estadoBadge = (estado) => {
                if (estado === 'aprobado') {
                    return '<span class="inline-flex items-center rounded-full border border-green-200 bg-green-50 px-2.5 py-0.5 text-xs font-bold text-green-700"><i class="fas fa-circle-check mr-1"></i>Aprobado</span>';
                }
                if (estado === 'anulado') {
                    return '<span class="inline-flex items-center rounded-full border border-red-200 bg-red-50 px-2.5 py-0.5 text-xs font-bold text-red-700"><i class="fas fa-circle-xmark mr-1"></i>Anulado</span>';
                }
                return '<span class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-2.5 py-0.5 text-xs font-bold text-amber-700"><i class="fas fa-clock mr-1"></i>En espera</span>';
            };

            const crearFilaManual = (data) => {
                const discosArr = (Array.isArray(data.discos) && data.discos.length ? data.discos : [data.disco]).filter(Boolean);
                const discosHtml = discosArr.map((d) => `<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold bg-blue-100 text-blue-800 border border-blue-200 mr-1">${esc(d)}</span>`).join('');
                const fechasHtml = (Array.isArray(data.fechas) ? data.fechas : []).map((f) =>
                    `<p class="text-sm text-gray-700 whitespace-nowrap"><i class="far fa-calendar mr-1 text-blue-400"></i>${esc(f)}</p>`).join('') || '<p class="text-sm text-gray-400">—</p>';
                const rutasHtml = (Array.isArray(data.rutas) && data.rutas.length ? data.rutas : ['—']).map((r) =>
                    `<p class="text-sm text-gray-600 truncate"><i class="fas fa-route mr-1 text-indigo-400"></i>${esc(r)}</p>`).join('');
                return `
                <tr>
                    <td class="px-5 py-4 whitespace-nowrap text-center">${discosHtml}</td>
                    <td class="px-5 py-4">${fechasHtml}</td>
                    <td class="px-5 py-4 max-w-60 text-left">${rutasHtml}</td>
                    <td class="px-5 py-4 whitespace-nowrap text-center"><span class="text-base font-extrabold text-gray-800">$ ${esc(data.valor)}</span></td>
                    <td class="px-5 py-4 whitespace-nowrap">
                        <span class="inline-flex rounded-lg border border-blue-200 bg-blue-50 px-3 py-1 text-sm font-mono font-bold text-blue-700">${esc(data.codigo_ingreso)}</span>
                    </td>
                    <td class="px-5 py-4 whitespace-nowrap text-center">
                        <a href="../../Controllers/ComprobanteController.php?pago_id=${esc(data.pago_id)}" target="_blank" rel="noopener"
                           class="inline-flex items-center justify-center rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-bold text-blue-700 hover:bg-blue-100 transition-colors">
                            <i class="fas fa-eye mr-1.5"></i>Ver
                        </a>
                    </td>
                    <td class="px-5 py-4 whitespace-nowrap text-center">${estadoBadge(data.estado || 'aprobado')}</td>
                </tr>`;
            };

            const insertarFilaManual = (data) => {
                if (!tablaManual) return;
                const vacio = tablaManual.querySelector('tr td[colspan]');
                if (vacio) vacio.closest('tr')?.remove();
                tablaManual.insertAdjacentHTML('afterbegin', crearFilaManual(data));
                const contadorManuales = document.getElementById('resultadosManuales');
                if (contadorManuales) contadorManuales.textContent = String((parseInt(contadorManuales.textContent, 10) || 0) + 1);
            };

            formManual?.addEventListener('submit', async (evento) => {
                evento.preventDefault();
                if (!formManual.reportValidity()) return;
                const seleccionadas = [...(listaPendientes?.querySelectorAll('.cb-pendiente:checked') || [])];
                if (!seleccionadas.length) {
                    mostrarEstado('error', 'Selecciona al menos un día para pagar.');
                    return;
                }
                mostrarEstado(null, '');
                if (btnRegistrar) {
                    btnRegistrar.disabled = true;
                    btnRegistrar.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Registrando…';
                }
                const formData = new FormData(formManual);
                formData.append('accion', 'registrar_pago_manual');
                seleccionadas.forEach((check) => formData.append('obligaciones[]', check.dataset.id));
                try {
                    const response = await fetch(URL_PAGO_MANUAL, { method: 'POST', body: formData });
                    const data = await response.json();
                    if (data.status === 'success') {
                        insertarFilaManual(data);
                        if (Array.isArray(data.obligaciones_ids)) {
                            const pagadas = new Set(data.obligaciones_ids.map(Number));
                            pendientes = pendientes.filter((p) => !pagadas.has(Number(p.id)));
                        }
                        formManual.reset();
                        marcarCodigoDuplicado(false);
                        renderPendientes(discoActivo);
                        mostrarEstado('success', data.message);
                    } else {
                        mostrarEstado('error', data.message || 'No se pudo registrar el pago manual.');
                    }
                } catch (error) {
                    mostrarEstado('error', 'Error de conexión con el servidor.');
                } finally {
                    if (btnRegistrar) {
                        btnRegistrar.innerHTML = textoOriginalBtn;
                        btnRegistrar.disabled = false;
                    }
                }
            });

            btnLimpiar?.addEventListener('click', () => {
                formManual?.reset();
                marcarCodigoDuplicado(false);
                renderPendientes('');
                mostrarEstado(null, '');
            });
        })();
    </script>
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
