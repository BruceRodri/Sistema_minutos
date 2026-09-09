<?php
// conductor/pagos.php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'conductor') {
    header("Location: ../../index.php");
    exit;
}

require_once '../../Config/conexion.php';
require_once '../../Dao/PagoDao.php';

$nombreCorto = explode(' ', $_SESSION['nombre'] ?? 'Conductor')[0];
$dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];

$pagoDao = new PagoDao($conexion);
$pagos = $pagoDao->obtenerPagosConductor($_SESSION['usuario_id']);
$discosTodos = $pagoDao->obtenerTodosDiscos();

function formatearFechaPago($fecha) {
    global $dias;
    $ts = strtotime($fecha);
    return ($dias[(int)date('w', $ts)] ?? $dias[0]) . ' ' . date('d/m/Y', $ts);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Pagos realizados - Sistema de Minutos</title>
    <link rel="icon" href="../../Assets/icons/icon-192x192.png" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                <a href="pagar.php" class="px-5 py-2.5 rounded-xl font-semibold text-gray-500 hover:bg-gray-100 hover:text-blue-600 transition-colors">
                    <i class="fas fa-money-bill-wave mr-2"></i>Pagar
                </a>
                <a href="pagos.php" class="px-5 py-2.5 rounded-xl font-bold text-white bg-blue-600 shadow-lg transition-colors">
                    <i class="fas fa-receipt mr-2"></i>Pagos realizados
                </a>
            </div>
        </div>
    </nav>

    <div class="flex flex-col min-h-screen max-w-7xl mx-auto px-4 pt-6 lg:px-10 lg:pt-20 pb-32 lg:pb-16">

        <!-- Encabezado -->
        <header class="lg:mt-8 mb-6 lg:mb-12 flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-lg lg:text-2xl text-gray-500">Hola,</p>
                <h1 class="text-3xl lg:text-5xl font-bold text-gray-800 truncate"><?php echo htmlspecialchars($nombreCorto); ?></h1>
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

        <div class="mt-4 lg:mt-6">
            <h2 class="text-center text-2xl lg:text-3xl font-bold text-gray-600 mb-6 lg:mb-8">Pagos realizados</h2>

            <div id="grillaPagos" class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-4 lg:gap-6">
                <?php if (empty($pagos)): ?>
                    <div class="lg:col-span-full bg-white rounded-3xl p-10 text-center shadow-sm border border-gray-200">
                        <i class="fas fa-receipt text-gray-300 text-6xl mb-4"></i>
                        <p class="text-2xl text-gray-500">Aún no has realizado ningún pago.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($pagos as $p):
                        $estado = $p['estado'] ?? 'aprobado';
                        $esEspera = $estado === 'en_espera';
                        $esAnulado = $estado === 'anulado';
                        $etiquetaEstado = $esAnulado ? 'Anulado' : ($esEspera ? 'En espera…' : 'Aprobado');
                        $etiquetaClase = $esAnulado
                            ? 'bg-red-100 text-red-700'
                            : ($esEspera ? 'bg-amber-100 text-amber-700' : 'bg-green-100 text-green-700');
                        $etiquetaIcono = $esAnulado ? 'fa-circle-xmark' : ($esEspera ? 'fa-clock' : 'fa-check-circle');
                        $bordeTarjeta = $esAnulado ? 'border-red-200' : ($esEspera ? 'border-amber-200' : 'border-green-200');
                        $iconoEstado = $esAnulado ? 'text-red-500' : ($esEspera ? 'text-amber-500' : 'text-green-500');
                        $verboPago = $esAnulado ? 'Ingresado el' : ($esEspera ? 'Subido el' : 'Pagado el');
                        $diaClase = $esAnulado ? 'bg-red-600' : ($esEspera ? 'bg-amber-500' : 'bg-green-600');
                    ?>
                    <div class="cardPago bg-white rounded-3xl p-6 lg:p-7 shadow-sm border-2 <?php echo $bordeTarjeta; ?>" data-discos="<?php echo htmlspecialchars(implode(' ', $p['discos'] ?? [])); ?>">
                        <div class="flex items-center justify-between gap-3 mb-3">
                            <?php
                            $fechasPagos = !empty($p['fechas']) ? $p['fechas'] : [$p['fecha_pago']];
                            $cantidadDias = count($fechasPagos);
                            if ($cantidadDias > 1):
                                $desde = min($fechasPagos);
                                $hasta = max($fechasPagos);
                                $rango = date('d/m/Y', strtotime($desde));
                                if ($hasta !== $desde) $rango .= ' al ' . date('d/m/Y', strtotime($hasta));
                            ?>
                            <div class="flex flex-col gap-1">
                                <p class="text-2xl lg:text-3xl font-extrabold text-gray-800">
                                    Se pagaron <span class="inline-block align-middle <?php echo $diaClase; ?> text-white px-3 py-0.5 rounded-full text-xl lg:text-2xl"><?php echo $cantidadDias; ?> días</span>
                                </p>
                                <p class="text-base lg:text-lg text-gray-500 font-semibold">del <?php echo $rango; ?></p>
                            </div>
                            <?php else: ?>
                            <p class="text-2xl lg:text-3xl font-extrabold text-gray-800">
                                <?php echo formatearFechaPago($fechasPagos[0]); ?>
                            </p>
                            <?php endif; ?>
                            <span class="<?php echo $etiquetaClase; ?> text-lg font-bold px-4 py-1.5 rounded-full flex items-center gap-2 shrink-0">
                                <i class="fas <?php echo $etiquetaIcono; ?>"></i> <?php echo $etiquetaEstado; ?>
                            </span>
                        </div>
                        <p class="text-lg lg:text-xl text-gray-500 mt-1">
                            <?php if (!empty($p['discos'])): ?>
                            <span class="inline-flex items-center gap-1 mr-2 font-extrabold text-blue-700 text-xl lg:text-2xl"><i class="fas fa-compact-disc"></i>Disco <?php echo htmlspecialchars(implode(' · ', $p['discos'])); ?></span>
                            <?php endif; ?>
                            <span class="font-bold text-gray-600"><?php echo $verboPago; ?> <?php echo formatearFechaPago($p['fecha_pago']); ?></span>
                        </p>
                        <hr class="border-gray-100 my-3">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-lg lg:text-xl text-gray-500">
                                <?php echo (int)$p['dias']; ?> día(s) · <span class="font-bold text-gray-700">$ <?php echo number_format((float)$p['monto'], 2, '.', ','); ?></span>
                            </p>
                            <i class="fas <?php echo $esAnulado ? 'fa-circle-xmark' : ($esEspera ? 'fa-clock' : 'fa-check-circle'); ?> <?php echo $iconoEstado; ?> text-4xl lg:text-5xl shrink-0"></i>
                        </div>
                        <?php if ($esAnulado): ?>
                        <div class="mt-3 rounded-xl bg-red-50 border border-red-200 p-4">
                            <p class="font-bold text-red-700 text-lg">Motivo: <span class="font-semibold"><?php echo htmlspecialchars($p['motivo_rechazo'] ?: 'Sin motivo especificado.'); ?></span></p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <p id="sinResultados" class="hidden mt-6 text-center text-xl lg:text-2xl text-blue-700 italic">Selecciona un disco para ver tu historial.</p>
        </div>
    </div>

    <!-- Barra de navegación inferior (móvil) -->
    <nav class="lg:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 shadow-[0_-4px_12px_rgba(0,0,0,0.05)]">
        <div class="grid grid-cols-3 w-full max-w-xl mx-auto">
            <a href="dashboard.php" class="flex flex-col items-center py-3 text-gray-500 hover:text-blue-600 transition-colors">
                <i class="fas fa-user text-2xl"></i>
                <span class="text-base font-semibold mt-1">Perfil</span>
            </a>
            <a href="pagar.php" class="flex flex-col items-center py-3 text-gray-500 hover:text-blue-600 transition-colors">
                <i class="fas fa-money-bill-wave text-2xl"></i>
                <span class="text-base font-semibold mt-1">Pagar</span>
            </a>
            <a href="pagos.php" class="flex flex-col items-center py-3 text-white bg-blue-600 rounded-t-xl -mt-1 shadow-lg transition-colors">
                <i class="fas fa-receipt text-2xl"></i>
                <span class="text-base font-bold mt-1">Pagos realizados</span>
            </a>
        </div>
    </nav>

    <script id="datosDiscos" type="application/json">
    <?php echo json_encode($discosTodos, JSON_UNESCAPED_UNICODE); ?>
    </script>
    <script src="../../Assets/js/pagos.js"></script>
</body>
</html>