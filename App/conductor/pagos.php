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

    <div class="flex flex-col min-h-screen max-w-xl mx-auto px-6 pt-6 pb-28">

        <header class="flex items-center justify-between gap-3">
            <div>
                <p class="text-lg text-gray-500">Hola,</p>
                <h1 class="text-3xl font-bold text-gray-800 truncate"><?php echo htmlspecialchars($nombreCorto); ?></h1>
            </div>
        </header>

        <p class="text-center text-xl font-semibold text-gray-500 mt-4">Pagos realizados</p>

        <div class="mt-6 space-y-4">
            <?php if (empty($pagos)): ?>
                <div class="bg-white rounded-2xl p-8 text-center shadow-sm border border-gray-200">
                    <i class="fas fa-receipt text-gray-300 text-4xl mb-3"></i>
                    <p class="text-lg text-gray-500">Aún no has realizado ningún pago.</p>
                </div>
            <?php else: ?>
                <?php foreach ($pagos as $p): ?>
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-200">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-lg font-bold text-gray-800"><?php echo formatearFechaPago($p['fecha_pago']); ?></p>
                        <span class="bg-green-100 text-green-700 text-sm font-bold px-3 py-1 rounded-full flex items-center gap-1">
                            <i class="fas fa-check-circle"></i> Pagado
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-500">
                            <p><?php echo (int)$p['dias']; ?> día(s) · <span class="font-bold text-gray-600">$ <?php echo number_format((float)$p['monto'], 2, '.', ','); ?></span></p>
                            <?php if (!empty($p['comprobante'])): ?>
                                <p class="mt-1 truncate"><i class="fas fa-paperclip mr-1"></i><?php echo htmlspecialchars(basename($p['comprobante'])); ?></p>
                            <?php endif; ?>
                        </div>
                        <i class="fas fa-circle-check text-green-500 text-3xl"></i>
                    </div>
                </div>
                <?php endforeach; ?>
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
            <a href="pagar.php" class="flex flex-col items-center py-3.5 text-gray-500 hover:text-blue-600 transition-colors">
                <i class="fas fa-money-bill-wave text-2xl"></i>
                <span class="text-sm font-semibold mt-1">Pagar</span>
            </a>
            <a href="pagos.php" class="flex flex-col items-center py-3.5 text-white bg-blue-600 rounded-t-xl -mt-1 shadow-lg transition-colors">
                <i class="fas fa-receipt text-2xl"></i>
                <span class="text-sm font-bold mt-1">Pagos realizados</span>
            </a>
        </div>
    </nav>
</body>
</html>
