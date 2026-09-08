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
        <header class="lg:mt-8 mb-6 lg:mb-12 flex items-center justify-between gap-3">
            <div>
                <p class="text-lg lg:text-2xl text-gray-500">Hola,</p>
                <h1 class="text-3xl lg:text-5xl font-bold text-gray-800 truncate"><?php echo htmlspecialchars($nombreCorto); ?></h1>
            </div>
            <i class="fas fa-receipt text-blue-600 text-5xl lg:text-6xl"></i>
        </header>

        <div class="mt-4 lg:mt-6">
            <h2 class="text-center text-2xl lg:text-3xl font-bold text-gray-600 mb-6 lg:mb-8">Pagos realizados</h2>

            <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-4 lg:gap-6">
                <?php if (empty($pagos)): ?>
                    <div class="lg:col-span-full bg-white rounded-3xl p-10 text-center shadow-sm border border-gray-200">
                        <i class="fas fa-receipt text-gray-300 text-6xl mb-4"></i>
                        <p class="text-2xl text-gray-500">Aún no has realizado ningún pago.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($pagos as $p): ?>
                    <div class="bg-white rounded-3xl p-6 lg:p-7 shadow-sm border border-gray-200">
                        <div class="flex items-center justify-between gap-3 mb-3">
                            <p class="text-xl lg:text-2xl font-bold text-gray-800"><?php echo formatearFechaPago($p['fecha_pago']); ?></p>
                            <span class="bg-green-100 text-green-700 text-lg font-bold px-4 py-1.5 rounded-full flex items-center gap-2 shrink-0">
                                <i class="fas fa-check-circle"></i> Pagado
                            </span>
                        </div>
                        <hr class="border-gray-100 my-3">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-lg lg:text-xl text-gray-500">
                                <?php echo (int)$p['dias']; ?> día(s) · <span class="font-bold text-gray-700">$ <?php echo number_format((float)$p['monto'], 2, '.', ','); ?></span>
                            </p>
                            <i class="fas fa-check-circle text-green-500 text-4xl lg:text-5xl shrink-0"></i>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
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
</body>
</html>