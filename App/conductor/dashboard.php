<?php
// conductor/dashboard.php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'conductor') {
    header("Location: ../../index.php");
    exit;
}

$nombreCorto = explode(' ', $_SESSION['nombre'] ?? 'Conductor')[0];
$fechaHoy = date('d/m/Y');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Conductor - Sistema de Minutos</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        #qr-reader {
            width: min(82vw, 340px);
            height: min(62vh, 340px);
            border-radius: 1.25rem;
            overflow: hidden;
            background: #000;
            box-shadow: 0 0 40px rgba(59, 130, 246, 0.35), 0 0 0 1px rgba(255, 255, 255, 0.12);
        }
        #qr-reader video {
            width: 100% !important;
            height: 100% !important;
            object-fit: cover;
        }
        #qr-shaded-region {
            border-width: 3px !important;
            border-style: solid !important;
            border-color: rgba(59, 130, 246, 0.85) !important;
            box-shadow: 0 0 0 100vmax rgba(0, 0, 0, 0.55) !important;
        }
    </style>
</head>
<body class="bg-gray-900 min-h-screen">

    <div class="flex flex-col min-h-screen max-w-md mx-auto p-4">

        <!-- Encabezado -->
        <div class="flex items-center justify-between py-4">
            <div>
                <h1 class="text-xl font-bold text-white">Hola, <?php echo htmlspecialchars($nombreCorto); ?></h1>
                <p class="text-sm text-gray-500"><?php echo $fechaHoy; ?></p>
            </div>
        </div>

        <!-- Botones principales -->
        <div class="flex-1 flex flex-col justify-center gap-6">
            <button id="btnAbrirTurno" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-20 px-4 rounded-2xl shadow-lg text-3xl transition-all flex flex-col items-center justify-center active:scale-95">
                <i class="fas fa-qrcode mb-4 text-6xl"></i>
                ABRIR TURNO
            </button>

            <a href="pagos.php" class="bg-green-600 hover:bg-green-700 text-white font-bold py-20 px-4 rounded-2xl shadow-lg text-3xl transition-all flex flex-col items-center justify-center active:scale-95">
                <i class="fas fa-money-bill-wave mb-4 text-6xl"></i>
                PAGOS
            </a>
        </div>
    </div>

    <!-- Overlay del escáner QR -->
    <div id="scannerOverlay" class="hidden fixed inset-0 bg-black bg-opacity-95 z-50 flex flex-col">
        <div class="flex items-center justify-between p-4 text-white">
            <h3 class="text-xl font-bold">Escanear QR del Bus</h3>
            <button id="btnCerrarScanner" class="bg-gray-700 hover:bg-gray-600 w-10 h-10 rounded-full flex items-center justify-center" title="Cerrar">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="flex-1 flex items-center justify-center p-4">
            <div id="qr-reader"></div>
        </div>
        <p class="text-center text-gray-400 p-4 text-sm">Apunta la cámara al código QR del bus (disco 1-90)</p>
    </div>

    <!-- Modal de confirmación -->
    <div id="modalConfirmacion" class="hidden fixed inset-0 bg-black bg-opacity-80 z-50 flex items-center justify-center p-6">
        <div class="bg-gray-800 rounded-2xl p-8 w-full max-w-sm text-center shadow-2xl border border-gray-700">
            <div id="modalIcono" class="mx-auto mb-4 w-20 h-20 rounded-full flex items-center justify-center bg-green-600">
                <i class="fas fa-check text-white text-4xl"></i>
            </div>
            <h2 id="modalTitulo" class="text-2xl font-bold text-white mb-3">Turno Abierto</h2>
            <p id="modalMensaje" class="text-gray-400 mb-2"></p>
            <p id="modalInfo" class="text-white font-bold mb-6 text-lg"></p>
            <button id="btnCerrarModal" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-xl transition-all">Aceptar</button>
        </div>
    </div>

    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script src="../../Assets/js/conductor.js"></script>
</body>
</html>