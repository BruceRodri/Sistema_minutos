<?php
// conductor/pagar.php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'conductor') {
    header("Location: ../../index.php");
    exit;
}

require_once '../../Config/conexion.php';
require_once '../../Dao/PagoDao.php';

$nombreCorto = explode(' ', $_SESSION['nombre'] ?? 'Conductor')[0];
$dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
$diaSemana = $dias[(int)date('w')];

$pagoDao = new PagoDao($conexion);
$turnoHoy = $pagoDao->obtenerTurnoHoyConductor($_SESSION['usuario_id']);

$disco = $_SESSION['turno_disco'] ?? null;
if ($disco === null && $turnoHoy) {
    $disco = $turnoHoy['disco'];
}
$discoMostrar = $disco !== null ? htmlspecialchars($disco) : '--';

$montoHoy = $turnoHoy ? (float)$turnoHoy['valor'] : 0.00;
$montoMostrar = number_format($montoHoy, 2, '.', ',');
$rutaHoy = $turnoHoy ? ($turnoHoy['ruta'] ?: 'Sin ruta') : '--';
$hoyPagado = $turnoHoy && (int)$turnoHoy['pagado'] === 1;

$pendientes = $pagoDao->obtenerTurnosPendientesConductor($_SESSION['usuario_id']);
function formatearFecha($fecha) {
    global $dias;
    $ts = strtotime($fecha);
    return $dias[(int)date('w', $ts)] . ' ' . date('d/m/Y', $ts);
}
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
</head>
<body class="bg-gradient-to-b from-blue-50 to-gray-100 min-h-screen text-gray-800">

    <div class="flex flex-col min-h-screen max-w-xl mx-auto px-6 pt-6 pb-28">

        <!-- Encabezado -->
        <header class="flex items-center justify-between gap-3">
            <div>
                <p class="text-lg text-gray-500">Hola,</p>
                <h1 class="text-3xl font-bold text-gray-800 truncate"><?php echo htmlspecialchars($nombreCorto); ?></h1>
            </div>
            <div class="bg-blue-600 text-white font-bold text-xl px-5 py-2.5 rounded-full shadow-lg whitespace-nowrap">
                Disco <?php echo $discoMostrar; ?>
            </div>
        </header>

        <!-- Día de la semana -->
        <p class="text-center text-xl font-semibold text-gray-500 mt-4"><?php echo $diaSemana; ?></p>

        <!-- Monto a pagar (hoy) -->
        <div class="mt-6">
            <div class="bg-gradient-to-br from-blue-500 to-blue-700 rounded-3xl p-8 shadow-xl text-center">
                <p class="text-blue-100 text-lg font-bold uppercase tracking-widest mb-4">Valor del turno de hoy</p>
                <div class="text-7xl font-extrabold text-white mb-3">
                    <span class="align-top text-4xl">$</span><?php echo $montoMostrar; ?>
                </div>
                <p class="text-blue-200 text-base mb-1">Ruta: <span class="font-bold text-white"><?php echo htmlspecialchars($rutaHoy); ?></span></p>
                <p class="text-blue-200 text-sm">Corresponde únicamente al turno del día de hoy.</p>
            </div>

            <div id="alertaComprobante" class="hidden mt-6 p-4 rounded-2xl text-base text-center"></div>

            <?php if ($turnoHoy && !$hoyPagado): ?>
            <div class="mt-6">
                <input type="file" id="inputComprobante" accept="image/*,application/pdf" class="hidden">
                <button id="btnComprobante" type="button"
                    class="w-full bg-green-600 hover:bg-green-700 active:scale-95 transition-all text-white text-xl font-bold py-5 px-4 rounded-2xl shadow-lg flex items-center justify-center gap-3">
                    <i class="fas fa-paper-plane text-2xl"></i>
                    Subir Comprobante
                </button>
                <p id="archivoSeleccionado" class="hidden mt-3 text-center text-lg text-gray-600 truncate px-2"></p>
            </div>
            <?php elseif ($turnoHoy && $hoyPagado): ?>
            <div class="mt-6 w-full bg-green-100 border border-green-300 text-green-800 text-xl font-bold py-5 px-4 rounded-2xl text-center">
                <i class="fas fa-check-circle mr-2"></i> Turno de hoy ya pagado
            </div>
            <?php else: ?>
            <p class="mt-6 text-center text-lg text-gray-500 italic">Aún no has abierto un turno hoy.</p>
            <?php endif; ?>
        </div>

        <!-- Pagar varios días -->
        <div class="mt-10">
            <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-3">
                <i class="fas fa-calendar-check text-blue-600"></i> Pagar varios días
            </h2>
            <p class="text-gray-500 mt-1 mb-4">Selecciona los días atrasados que quieres pagar.</p>

            <?php if (empty($pendientes)): ?>
                <div class="bg-white rounded-2xl p-6 text-center shadow-sm border border-gray-200">
                    <i class="fas fa-check-circle text-green-500 text-3xl mb-2"></i>
                    <p class="text-lg text-gray-600">No tienes días pendientes de pago.</p>
                </div>
            <?php else: ?>
                <form id="formPagarVarios">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 divide-y divide-gray-100 max-h-96 overflow-y-auto">
                        <?php foreach ($pendientes as $t): ?>
                        <label class="flex items-center gap-3 p-4 cursor-pointer hover:bg-blue-50 transition-colors">
                            <input type="checkbox" name="turnos_ids[]" value="<?php echo $t['id']; ?>"
                                   class="checkDia w-6 h-6 accent-blue-600 shrink-0"
                                   data-valor="<?php echo (float)$t['valor']; ?>">
                            <div class="flex-1 min-w-0">
                                <p class="font-bold text-gray-800"><?php echo formatearFecha($t['fecha']); ?></p>
                                <p class="text-sm text-gray-500 truncate">Disco <?php echo htmlspecialchars($t['disco']); ?> · <?php echo htmlspecialchars($t['ruta'] ?: 'Sin ruta'); ?></p>
                            </div>
                            <p class="font-bold text-blue-700 text-lg whitespace-nowrap">$ <?php echo number_format((float)$t['valor'], 2, '.', ','); ?></p>
                        </label>
                        <?php endforeach; ?>
                    </div>

                    <div id="alertaVarios" class="hidden mt-4 p-4 rounded-2xl text-base text-center"></div>

                    <div class="mt-4 bg-white rounded-2xl shadow-sm border border-gray-200 p-4 flex justify-between items-center">
                        <span class="text-lg font-semibold text-gray-600">Total a pagar</span>
                        <span id="totalVarios" class="text-3xl font-extrabold text-blue-700">$ 0.00</span>
                    </div>

                    <input type="file" id="inputVarios" accept="image/*,application/pdf" class="hidden">
                    <button id="btnPagarVarios" type="button"
                        class="mt-4 w-full bg-blue-600 hover:bg-blue-700 active:scale-95 transition-all text-white text-xl font-bold py-5 px-4 rounded-2xl shadow-lg flex items-center justify-center gap-3">
                        <i class="fas fa-paper-plane text-2xl"></i>
                        Subir comprobante de los días seleccionados
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Barra de navegación inferior -->
    <nav class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 max-w-xl mx-auto shadow-[0_-4px_12px_rgba(0,0,0,0.05)]">
        <div class="grid grid-cols-3 w-full">
            <a href="dashboard.php" class="flex flex-col items-center py-3.5 text-gray-500 hover:text-blue-600 transition-colors">
                <i class="fas fa-user text-2xl"></i>
                <span class="text-sm font-semibold mt-1">Perfil</span>
            </a>
            <a href="pagar.php" class="flex flex-col items-center py-3.5 text-white bg-blue-600 rounded-t-xl -mt-1 shadow-lg transition-colors">
                <i class="fas fa-money-bill-wave text-2xl"></i>
                <span class="text-sm font-bold mt-1">Pagar</span>
            </a>
            <a href="pagos.php" class="flex flex-col items-center py-3.5 text-gray-500 hover:text-blue-600 transition-colors">
                <i class="fas fa-receipt text-2xl"></i>
                <span class="text-sm font-semibold mt-1">Pagos realizados</span>
            </a>
        </div>
    </nav>

    <script src="../../Assets/js/pagar.js"></script>
</body>
</html>
