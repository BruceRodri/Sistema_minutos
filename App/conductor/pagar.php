<?php
// conductor/pagar.php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'conductor') {
    header("Location: ../../index.php");
    exit;
}

require_once '../../Config/conexion.php';
require_once '../../Dao/PagoDao.php';
require_once '../../Dao/ValoresDao.php';

$nombreCorto = explode(' ', $_SESSION['nombre'] ?? 'Conductor')[0];
$dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];

function formatearFecha($fecha) {
    global $dias;
    $ts = strtotime($fecha);
    return $dias[(int)date('w', $ts)] . ' ' . date('d/m/Y', $ts);
}

$valoresDao = new ValoresDao($conexion);
$valoresDao->sincronizarObligacionesConArchivo();

$pagoDao = new PagoDao($conexion);
$pagables = [];

foreach ($pagoDao->obtenerObligacionesPendientes() as $pendiente) {
    $pagables[] = [
        'id' => (int)$pendiente['id'],
        'fecha' => $pendiente['fecha'],
        'valor' => (float)$pendiente['valor'],
        'ruta' => $pendiente['ruta'] ?: 'Sin ruta',
        'disco' => $pendiente['disco'],
        'hoy' => $pendiente['fecha'] === date('Y-m-d')
    ];
}

foreach ($pagables as $i => $p) {
    $pagables[$i]['fechaLegible'] = formatearFecha($p['fecha']);
    $pagables[$i]['valorFmt'] = number_format($p['valor'], 2, '.', ',');
}

$discoInicial = null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Pagar - Sistema de Minutos</title>
    <link rel="icon" href="../../Assets/icons/icon-192x192.png" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .sin-scrollbar::-webkit-scrollbar { display: none; }
        .sin-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-gradient-to-b from-blue-50 to-gray-100 min-h-screen text-gray-800">

    <!-- Navegación superior (escritorio) -->
    <nav class="hidden lg:flex fixed top-0 left-0 right-0 bg-white border-b border-gray-200 shadow-sm z-30">
        <div class="w-full max-w-7xl mx-auto flex items-center justify-between px-10 py-4">
            <span class="font-bold text-gray-800 text-lg"><i class="fas fa-clock text-blue-600 mr-2"></i>Minutos</span>
            <div class="flex gap-2">
                <a href="dashboard.php" class="px-5 py-2.5 rounded-xl font-semibold text-gray-500 hover:bg-gray-100 hover:text-blue-600 transition-colors">
                    <i class="fas fa-user mr-2"></i>Perfil
                </a>
                <a href="pagar.php" class="px-5 py-2.5 rounded-xl font-bold text-white bg-blue-600 shadow-lg transition-colors">
                    <i class="fas fa-money-bill-wave mr-2"></i>Pagar
                </a>
                <a href="pagos.php" class="px-5 py-2.5 rounded-xl font-semibold text-gray-500 hover:bg-gray-100 hover:text-blue-600 transition-colors">
                    <i class="fas fa-receipt mr-2"></i>Pagos realizados
                </a>
            </div>
        </div>
    </nav>

    <div class="flex flex-col min-h-screen max-w-7xl mx-auto px-4 pt-6 lg:px-10 lg:pt-20 pb-32 lg:pb-16">

        <!-- Encabezado -->
        <header class="lg:mt-8 mb-6 lg:mb-12 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 lg:w-16 lg:h-16 rounded-full bg-blue-600 text-white flex items-center justify-center text-xl lg:text-2xl font-extrabold shadow-lg">
                    <?php echo htmlspecialchars(mb_strtoupper(mb_substr($nombreCorto, 0, 1))); ?>
                </div>
                <div>
                    <p class="text-lg lg:text-2xl text-gray-500">Hola,</p>
                    <h1 class="text-3xl lg:text-5xl font-bold text-gray-800 truncate"><?php echo htmlspecialchars($nombreCorto); ?></h1>
                </div>
            </div>

            <div class="relative flex items-center gap-3 bg-white rounded-2xl border-2 border-blue-200 shadow-sm px-5 py-3 lg:py-4">
                <label for="buscarDisco" class="text-lg lg:text-2xl font-bold text-gray-600 whitespace-nowrap">
                    <i class="fas fa-compact-disc text-blue-600 mr-1.5"></i>Disco
                </label>
                <input type="text" id="buscarDisco" name="buscarDisco" inputmode="numeric" autocomplete="off" maxlength="6"
                    placeholder="Buscar…"
                    class="w-24 lg:w-44 text-2xl lg:text-4xl font-extrabold text-blue-700 outline-none bg-transparent placeholder:font-normal placeholder:text-gray-400">
                <button id="limpiarDisco" type="button" title="Limpiar" class="hidden text-gray-400 hover:text-red-500 transition-colors">
                    <i class="fas fa-times-circle text-2xl"></i>
                </button>
                <div id="listaDiscos" class="hidden absolute left-0 right-0 top-full mt-2 bg-white border-2 border-blue-100 rounded-2xl shadow-2xl overflow-hidden z-20"></div>
            </div>
        </header>

        <!-- ================= VISTA CARRUSEL ================= -->
        <section id="vistaCarousel">

            <div id="sinSeleccion" class="bg-white rounded-3xl p-10 text-center shadow-sm border-2 border-dashed border-blue-200">
                <i class="fas fa-compact-disc text-blue-300 text-6xl mb-4"></i>
                <p class="text-2xl lg:text-3xl font-bold text-gray-700 mb-2">Selecciona un disco</p>
                <p class="text-xl lg:text-2xl text-gray-500">Usa la casilla <span class="font-bold text-blue-700">Disco</span> de arriba para ver sus pagos pendientes.</p>
            </div>

            <button id="verVarios" type="button"
                class="hidden w-full mb-6 lg:mb-8 flex items-center justify-center gap-3 text-xl lg:text-2xl font-bold text-white bg-green-600 hover:bg-green-700 border-2 border-green-800 rounded-2xl py-5 lg:py-6 px-4 shadow-lg transition-colors">
                <i class="fas fa-calendar-check text-4xl"></i>
                ¿Prefieres agrupar pagos de varios días?
            </button>

            <div id="bloqueCarrusel" class="hidden">
                <div id="carrusel" class="relative flex gap-4 overflow-x-auto snap-x snap-mandatory sin-scrollbar px-4 py-1 items-stretch md:grid md:grid-cols-2 xl:grid-cols-3 md:gap-6 md:overflow-visible md:px-0">

                    <?php foreach ($pagables as $card): ?>
                    <article data-id="<?php echo $card['id']; ?>"
                             data-disco="<?php echo htmlspecialchars($card['disco']); ?>"
                             data-fecha="<?php echo htmlspecialchars($card['fechaLegible']); ?>"
                             data-valor="<?php echo $card['valorFmt']; ?>"
                             class="cardPagar snap-center shrink-0 w-[82%] max-w-[340px] md:w-auto md:max-w-none md:shrink min-h-0 bg-gradient-to-br from-blue-500 to-blue-700 rounded-3xl p-7 lg:p-9 shadow-xl text-white cursor-pointer active:scale-95 transition-transform">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-blue-100 text-sm lg:text-base font-bold uppercase tracking-widest"><?php echo $card['hoy'] ? 'Pago de hoy' : 'Pago pendiente'; ?></p>
                                <p class="text-2xl lg:text-3xl font-bold mt-1"><?php echo htmlspecialchars($card['fechaLegible']); ?></p>
                            </div>
                            <span class="bg-white text-blue-700 font-extrabold px-4 py-2 rounded-full text-lg lg:text-xl shadow whitespace-nowrap">Disco <?php echo htmlspecialchars($card['disco']); ?></span>
                        </div>
                        <div class="mt-9 lg:mt-11 text-center">
                            <div class="text-7xl lg:text-8xl font-extrabold leading-none">
                                <span class="align-top text-4xl lg:text-5xl">$</span><?php echo $card['valorFmt']; ?>
                            </div>
                        </div>
                        <div class="mt-9 lg:mt-11 flex items-center gap-3 justify-center bg-white/20 rounded-2xl px-4 py-4">
                            <i class="fas fa-route text-2xl"></i>
                            <span class="text-xl lg:text-2xl font-bold truncate"><?php echo htmlspecialchars($card['ruta']); ?></span>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>

                <div id="puntosTarjetas" class="hidden md:hidden flex items-center justify-center gap-2 mt-5"></div>

                <p id="sinResultadosDisco" class="hidden mt-6 text-center text-xl lg:text-2xl text-gray-500 italic">
                    No hay pagos pendientes para el disco seleccionado.
                </p>
            </div>

            <div id="alertaTarjetas" class="hidden mt-6 p-5 rounded-2xl text-center"></div>
        </section>

        <!-- ================= VISTA PAGO MULTIPLE ================= -->
        <section id="vistaMulti" class="hidden">

            <button id="volverTarjetas" type="button"
                class="mb-5 flex items-center gap-3 text-xl lg:text-2xl font-bold text-gray-600 hover:text-blue-700 transition-colors">
                <i class="fas fa-arrow-left text-2xl"></i> Volver a las tarjetas
            </button>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-10 items-start">

                <!-- Lista de días pendientes -->
                <div class="bg-white rounded-3xl shadow-sm border border-gray-200 divide-y divide-gray-100 overflow-hidden">
                    <?php if (empty($pagables)): ?>
                        <div class="p-8 text-center">
                            <i class="fas fa-circle-check text-green-500 text-4xl mb-3"></i>
                            <p class="text-2xl text-gray-600">No hay pagos pendientes para este disco.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($pagables as $dia): ?>
                        <label class="checkDiaFila flex items-center gap-4 p-5 lg:p-6 cursor-pointer hover:bg-blue-50 transition-colors">
                            <input type="checkbox" class="checkDia w-7 h-7 lg:w-8 lg:h-8 accent-blue-600 shrink-0"
                                   value="<?php echo $dia['id']; ?>"
                                   data-valor="<?php echo $dia['valor']; ?>"
                                   data-disco="<?php echo htmlspecialchars($dia['disco']); ?>"
                                   data-fecha="<?php echo htmlspecialchars($dia['fechaLegible']); ?>"
                                   data-valorfmt="<?php echo $dia['valorFmt']; ?>">
                            <div class="flex-1 min-w-0">
                                <p class="font-bold text-gray-800 text-xl lg:text-2xl"><?php echo htmlspecialchars($dia['fechaLegible']); ?></p>
                                <p class="text-lg text-gray-500 truncate mt-1">
                                    <i class="fas fa-route mr-1"></i><?php echo htmlspecialchars($dia['ruta']); ?>
                                    <span class="mx-1">·</span>Disco <?php echo htmlspecialchars($dia['disco']); ?>
                                </p>
                            </div>
                            <p class="font-extrabold text-blue-700 text-2xl lg:text-3xl whitespace-nowrap">$ <?php echo $dia['valorFmt']; ?></p>
                        </label>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Resumen total -->
                <div class="bg-gradient-to-br from-blue-600 to-blue-800 rounded-3xl p-6 lg:p-9 text-white shadow-xl lg:sticky lg:top-24">
                    <p class="text-blue-100 text-xl lg:text-2xl font-bold uppercase tracking-widest mb-3">Total a pagar</p>
                    <p class="text-6xl lg:text-7xl font-extrabold mb-2"><span class="align-top text-4xl lg:text-5xl">$</span><span id="totalVarios">0.00</span></p>
                    <p id="detalleVarios" class="text-blue-200 text-lg lg:text-xl mt-1 mb-7">Selecciona al menos un día.</p>

                    <input type="file" id="inputComprobante" accept="image/*,application/pdf" class="hidden">
                    <button id="btnPagarVarios" type="button"
                        class="w-full bg-green-500 hover:bg-green-400 active:scale-95 transition-all text-white text-xl lg:text-2xl font-bold py-5 lg:py-6 px-4 rounded-2xl shadow-lg flex items-center justify-center gap-3">
                        <i class="fas fa-upload text-3xl"></i>
                        Subir comprobante
                    </button>
                    <div id="alertaVarios" class="hidden mt-4 p-5 rounded-2xl text-center"></div>
                </div>
            </div>
        </section>
    </div>

    <!-- Modal de confirmación -->
    <div id="modalConfirmar" class="hidden fixed inset-0 z-50 items-center justify-center p-4">
        <div id="modalFondo" class="absolute inset-0 bg-black/60"></div>
        <div class="relative bg-white rounded-3xl shadow-2xl max-w-md w-full p-8 lg:p-10 text-center">
            <div class="w-20 h-20 lg:w-24 lg:h-24 mx-auto rounded-full bg-amber-100 flex items-center justify-center mb-5">
                <i class="fas fa-triangle-exclamation text-amber-500 text-4xl lg:text-5xl"></i>
            </div>
            <h3 class="text-3xl lg:text-4xl font-extrabold text-gray-800 mb-4">Confirmar pago</h3>
            <p id="modalMensaje" class="text-gray-700 text-2xl lg:text-3xl font-medium mb-8"></p>
            <div class="grid grid-cols-2 gap-3">
                <button id="modalCancelar" type="button"
                    class="py-4 lg:py-5 rounded-xl font-bold text-xl lg:text-2xl text-gray-600 bg-gray-100 hover:bg-gray-200 transition-colors">
                    Cancelar
                </button>
                <button id="modalAceptar" type="button"
                    class="py-4 lg:py-5 rounded-xl font-bold text-xl lg:text-2xl text-white bg-blue-600 hover:bg-blue-700 transition-colors">
                    <i class="fas fa-check mr-1.5"></i>Sí, pagar
                </button>
            </div>
        </div>
    </div>

    <!-- Barra de navegación inferior (móvil) -->
    <nav class="lg:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 shadow-[0_-4px_12px_rgba(0,0,0,0.05)]">
        <div class="grid grid-cols-3 w-full max-w-xl mx-auto">
            <a href="dashboard.php" class="flex flex-col items-center py-3 text-gray-500 hover:text-blue-600 transition-colors">
                <i class="fas fa-user text-2xl"></i>
                <span class="text-base font-semibold mt-1">Perfil</span>
            </a>
            <a href="pagar.php" class="flex flex-col items-center py-3 text-white bg-blue-600 rounded-t-xl -mt-1 shadow-lg transition-colors">
                <i class="fas fa-money-bill-wave text-2xl"></i>
                <span class="text-base font-bold mt-1">Pagar</span>
            </a>
            <a href="pagos.php" class="flex flex-col items-center py-3 text-gray-500 hover:text-blue-600 transition-colors">
                <i class="fas fa-receipt text-2xl"></i>
                <span class="text-base font-semibold mt-1">Pagos realizados</span>
            </a>
        </div>
    </nav>

    <script id="datosPagar" type="application/json">
    <?php echo json_encode([
        'pagables' => $pagables,
        'discoInicial' => $discoInicial
    ], JSON_UNESCAPED_UNICODE); ?>
    </script>
    <script src="../../Assets/js/pagar.js"></script>
</body>
</html>
