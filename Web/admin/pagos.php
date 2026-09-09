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

$conteos = ['en_espera' => 0, 'aprobado' => 0, 'anulado' => 0];
foreach ($pagos as $p) {
    $conteos[$p['estado']] = ($conteos[$p['estado']] ?? 0) + 1;
}

function formatearFechaAdmin($fecha) {
    return date('d/m/Y', strtotime($fecha));
}

function baseComprobante($comprobante) {
    return $comprobante ? basename($comprobante) : '—';
}
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

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                <div class="bg-white rounded-xl border border-amber-200 shadow-sm p-5 flex items-center gap-4">
                    <span class="w-12 h-12 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center shrink-0">
                        <i class="fas fa-clock text-2xl"></i>
                    </span>
                    <div>
                        <p class="text-3xl font-bold text-amber-600"><?php echo $conteos['en_espera']; ?></p>
                        <p class="text-sm font-semibold text-gray-500">En espera</p>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-green-200 shadow-sm p-5 flex items-center gap-4">
                    <span class="w-12 h-12 rounded-xl bg-green-100 text-green-600 flex items-center justify-center shrink-0">
                        <i class="fas fa-circle-check text-2xl"></i>
                    </span>
                    <div>
                        <p class="text-3xl font-bold text-green-600"><?php echo $conteos['aprobado']; ?></p>
                        <p class="text-sm font-semibold text-gray-500">Aprobados</p>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-red-200 shadow-sm p-5 flex items-center gap-4">
                    <span class="w-12 h-12 rounded-xl bg-red-100 text-red-600 flex items-center justify-center shrink-0">
                        <i class="fas fa-circle-xmark text-2xl"></i>
                    </span>
                    <div>
                        <p class="text-3xl font-bold text-red-600"><?php echo $conteos['anulado']; ?></p>
                        <p class="text-sm font-semibold text-gray-500">Anulados</p>
                    </div>
                </div>
            </div>

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
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($pagos)): ?>
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                    <i class="fas fa-receipt text-3xl mb-3 text-gray-300"></i>
                                    <p>No hay pagos registrados todavía.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pagos as $p):
                                $esEspera = $p['estado'] === 'en_espera';
                                $esAnulado = $p['estado'] === 'anulado';
                                $esAprobado = $p['estado'] === 'aprobado';
                                $conductor = trim(($p['nombres'] ?? '') . ' ' . ($p['apellidos'] ?? ''));
                                $rutaComprobante = '../../App/conductor/' . htmlspecialchars($p['comprobante'] ?: '');
                            ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <p class="text-sm font-bold text-gray-800"><?php echo htmlspecialchars($conductor); ?></p>
                                    <p class="text-xs text-gray-500 font-mono">Código: <?php echo htmlspecialchars($p['codigo_conductor'] ?: '—'); ?></p>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-center">
                                    <?php foreach ($p['discos'] as $disco): ?>
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold bg-blue-100 text-blue-800 border border-blue-200 mr-1">
                                            <?php echo htmlspecialchars($disco); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </td>
                                <td class="px-5 py-4">
                                    <?php foreach ($p['fechas'] as $fecha): ?>
                                        <p class="text-sm text-gray-700 whitespace-nowrap">
                                            <i class="far fa-calendar mr-1 text-blue-400"></i><?php echo formatearFechaAdmin($fecha); ?>
                                        </p>
                                    <?php endforeach; ?>
                                </td>
                                <td class="px-5 py-4 max-w-60 text-left">
                                    <?php $rutas = $p['rutas'] ?: ['—']; ?>
                                    <?php foreach ($rutas as $ruta): ?>
                                        <p class="text-sm text-gray-600 truncate"><i class="fas fa-route mr-1 text-indigo-400"></i><?php echo htmlspecialchars($ruta); ?></p>
                                    <?php endforeach; ?>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-center">
                                    <span class="text-base font-extrabold text-gray-800">$ <?php echo number_format((float)$p['monto_total'], 2, '.', ','); ?></span>
                                </td>
                                <td class="px-5 py-4 min-w-64">
                                    <p class="text-xs font-mono text-gray-500 break-all max-w-44" title="<?php echo htmlspecialchars(baseComprobante($p['comprobante'])); ?>">
                                        <?php echo htmlspecialchars(baseComprobante($p['comprobante'])); ?>
                                    </p>
                                    <div class="flex flex-col gap-1.5 mt-2" data-pago="<?php echo (int)$p['id']; ?>" data-codigos="<?php echo htmlspecialchars($p['nro_comprobante'] ?? ''); ?>">
                                        <div class="contenedorCodigos flex flex-col gap-1.5"></div>
                                        <div class="flex items-center gap-1.5">
                                            <button type="button" data-agregar-codigo title="Agregar otro código"
                                                    class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-gray-100 text-gray-600 hover:bg-blue-100 hover:text-blue-700 transition-colors">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                            <button type="button" data-guardar-codigos
                                                    class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-2.5 py-1 text-xs font-bold text-white shadow hover:bg-blue-700 transition-colors">
                                                <i class="fas fa-floppy-disk mr-1.5"></i>Guardar
                                            </button>
                                            <span data-estado-codigos class="text-xs font-bold"></span>
                                        </div>
                                    </div>
                                    <?php if ($esAprobado): ?>
                                        <span class="inline-flex items-center rounded-full border border-green-200 bg-green-50 px-2.5 py-0.5 text-xs font-bold text-green-700 mt-1">
                                            <i class="fas fa-circle-check mr-1"></i>Aprobado
                                        </span>
                                    <?php elseif ($esAnulado): ?>
                                        <span class="inline-flex items-center rounded-full border border-red-200 bg-red-50 px-2.5 py-0.5 text-xs font-bold text-red-700 mt-1">
                                            <i class="fas fa-circle-xmark mr-1"></i>Anulado
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-2.5 py-0.5 text-xs font-bold text-amber-700 mt-1">
                                            <i class="fas fa-clock mr-1"></i>En espera
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-4">
                                    <?php if ($esEspera): ?>
                                        <div class="flex flex-col gap-2">
                                            <button type="button" data-ver-comprobante="<?php echo $rutaComprobante; ?>"
                                                    class="inline-flex items-center justify-center rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-bold text-blue-700 hover:bg-blue-100 transition-colors">
                                                <i class="fas fa-eye mr-1.5"></i>Ver comprobante
                                            </button>
                                            <div class="flex gap-2">
                                                <button type="button" data-aprobar="<?php echo (int)$p['id']; ?>"
                                                        class="inline-flex items-center justify-center rounded-lg bg-green-600 px-3 py-1.5 text-xs font-bold text-white shadow hover:bg-green-700 transition-colors">
                                                    <i class="fas fa-check mr-1.5"></i>Aprobar
                                                </button>
                                                <button type="button" data-desaprobar="<?php echo (int)$p['id']; ?>"
                                                        class="inline-flex items-center justify-center rounded-lg bg-red-600 px-3 py-1.5 text-xs font-bold text-white shadow hover:bg-red-700 transition-colors">
                                                    <i class="fas fa-xmark mr-1.5"></i>Desaprobar
                                                </button>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="flex flex-col gap-2">
                                            <button type="button" data-ver-comprobante="<?php echo $rutaComprobante; ?>"
                                                    class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs font-bold text-gray-600 hover:bg-gray-100 transition-colors">
                                                <i class="fas fa-eye mr-1.5"></i>Ver comprobante
                                            </button>
                                            <?php if ($esAnulado): ?>
                                                <div class="rounded-lg bg-red-50 border border-red-200 px-3 py-2 max-w-56">
                                                    <p class="text-xs font-bold text-red-700">Motivo:</p>
                                                    <p class="text-xs text-red-600 break-words"><?php echo htmlspecialchars($p['motivo_rechazo'] ?: 'Sin motivo especificado.'); ?></p>
                                                </div>
                                            <?php endif; ?>
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

    <script src="../../Assets/js/pagos_admin.js"></script>
</body>
</html>