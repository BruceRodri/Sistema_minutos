<?php
// conductor/dashboard.php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'conductor') {
    header("Location: ../../index.php");
    exit;
}

$nombreCorto = explode(' ', $_SESSION['nombre'] ?? 'Conductor')[0];
$codigoConductor = $_SESSION['codigo_conductor'] ?? null;
$fechaHoy = date('d/m/Y');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="color-scheme" content="light">
    <meta name="theme-color" content="#f3f4f6">
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
            box-shadow: 0 18px 45px rgba(15, 23, 42, 0.2), 0 0 0 4px #fff;
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
<body class="bg-gray-100 text-gray-800 min-h-screen">

    <div class="flex flex-col min-h-screen max-w-md mx-auto p-4 md:py-6">

        <!-- Encabezado -->
        <div class="flex items-center justify-between bg-white border border-gray-200 rounded-2xl shadow-sm p-5">
            <div>
                <p class="text-sm text-gray-500 mb-1">Bienvenido</p>
                <h1 class="text-xl font-bold text-gray-900">Hola, <?php echo htmlspecialchars($nombreCorto); ?></h1>
                <p class="text-sm text-gray-500 mt-1"><?php echo $fechaHoy; ?></p>
                <?php if ($codigoConductor): ?>
                    <span class="inline-flex mt-2 px-3 py-1 rounded-full bg-blue-50 border border-blue-200 text-xs font-mono font-bold text-blue-700">
                        Conductor <?php echo htmlspecialchars($codigoConductor); ?>
                    </span>
                <?php endif; ?>
            </div>
            <div class="w-14 h-14 rounded-2xl bg-blue-100 text-blue-700 flex items-center justify-center">
                <i class="fas fa-user text-2xl"></i>
            </div>
        </div>

        <!-- Botones principales -->
        <div class="flex-1 flex flex-col justify-center gap-6">
            <button id="btnAbrirTurno" class="group bg-gradient-to-br from-blue-50 to-blue-100 hover:from-blue-100 hover:to-blue-200 border-2 border-blue-300 hover:border-blue-500 text-blue-900 font-bold py-14 px-4 rounded-2xl shadow-md hover:shadow-lg text-2xl transition-all flex flex-col items-center justify-center active:scale-95">
                <span class="w-20 h-20 mb-5 rounded-2xl bg-blue-600 group-hover:bg-blue-700 text-white flex items-center justify-center shadow-lg transition-colors">
                    <i class="fas fa-qrcode text-5xl"></i>
                </span>
                ABRIR TURNO
                <span class="mt-2 text-sm font-normal text-blue-800/80">Escanear código QR del bus</span>
            </button>

            <a href="pagar.php" class="group bg-white hover:bg-green-50 border-2 border-green-200 hover:border-green-500 text-gray-900 font-bold py-14 px-4 rounded-2xl shadow-md hover:shadow-lg text-2xl transition-all flex flex-col items-center justify-center active:scale-95">
                <span class="w-20 h-20 mb-5 rounded-2xl bg-green-600 group-hover:bg-green-700 text-white flex items-center justify-center shadow-lg transition-colors">
                    <i class="fas fa-money-bill-wave text-5xl"></i>
                </span>
                PAGOS
                <span class="mt-2 text-sm font-normal text-gray-500">Consultar y registrar pagos</span>
            </a>
        </div>
    </div>

    <!-- Overlay del escáner QR -->
    <div id="scannerOverlay" class="hidden fixed inset-0 bg-gray-100 z-50 flex flex-col">
        <div class="flex items-center justify-end p-4 bg-white border-b border-gray-200 text-gray-900 shadow-sm">
            <button id="btnCerrarScanner" class="bg-gray-100 hover:bg-gray-200 text-gray-700 w-10 h-10 rounded-full flex items-center justify-center transition-colors" title="Cerrar">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="flex-1 overflow-y-auto p-4">
            <div class="mx-auto flex min-h-full w-full max-w-md flex-col items-center justify-center py-5">
                <div class="mb-6 w-full rounded-2xl border border-blue-100 bg-white p-5 text-center shadow-sm">
                    <span class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-xl bg-blue-100 text-blue-700">
                        <i class="fas fa-qrcode text-2xl"></i>
                    </span>
                    <h3 class="text-xl font-bold text-gray-900">Por favor, escanee el código QR de su unidad</h3>
                    <p class="mt-2 text-sm text-gray-500">Mantenga el código completo y bien iluminado.</p>
                </div>
                <div id="qr-reader"></div>
                <p class="mt-5 text-center text-sm font-medium text-gray-600">
                    <i class="fas fa-camera mr-2 text-blue-600"></i>Apunta la cámara al código QR del bus
                </p>
            </div>
        </div>
    </div>

    <!-- Modal de confirmación -->
    <div id="modalConfirmacion" class="hidden fixed inset-0 bg-gray-900 bg-opacity-40 backdrop-blur-sm z-50 flex items-center justify-center p-6">
        <div class="bg-white rounded-2xl p-8 w-full max-w-sm text-center shadow-2xl border border-gray-200">
            <div id="modalIcono" class="mx-auto mb-4 w-20 h-20 rounded-full flex items-center justify-center bg-green-600">
                <i class="fas fa-check text-white text-4xl"></i>
            </div>
            <h2 id="modalTitulo" class="text-2xl font-bold text-gray-900 mb-3">Turno Abierto</h2>
            <p id="modalMensaje" class="text-gray-600 mb-2"></p>
            <p id="modalInfo" class="text-gray-900 font-bold mb-6 text-lg"></p>
            <button id="btnCerrarModal" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-xl transition-all">Aceptar</button>
        </div>
    </div>

    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script src="../../Assets/js/conductor.js?v=3"></script>
</body>
</html>
