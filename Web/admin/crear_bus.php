<?php
// Web/admin/crear_bus.php
session_start();
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['rol'] ?? '', ['admin', 'secretaria', 'operativo'])) {
    header("Location: ../../index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Bus - Ejecuttrans</title>
    <link rel="icon" href="../../Assets/icons/icon-192x192.png" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 flex h-screen overflow-hidden">

    <?php include 'components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col overflow-y-auto mt-16 md:mt-0 w-full">
        <header class="h-16 bg-white shadow-sm flex items-center px-4 md:px-8 justify-between border-b border-gray-200">
            <h2 class="text-xl md:text-2xl font-bold text-gray-800 text-transparent bg-clip-text bg-gradient-to-r from-blue-700 to-gray-800">
                Crear Bus
            </h2>
        </header>

        <div class="flex-1 flex items-center justify-center p-4 md:p-8">
            <div class="bg-white rounded-2xl shadow-lg p-8 w-full max-w-sm border border-gray-100">
                <div class="mx-auto mb-6 w-16 h-16 rounded-full bg-gradient-to-br from-blue-500 to-blue-700 flex items-center justify-center">
                    <i class="fas fa-bus text-white text-2xl"></i>
                </div>

                <div id="alerta" class="hidden mb-4 p-3 rounded text-sm text-center"></div>

                <form id="formCrearBus">
                    <div class="mb-4">
                        <label for="disco" class="block text-gray-600 text-sm font-bold mb-2">Número de Disco</label>
                        <input type="text" id="disco" name="disco" required placeholder="Ej: 1, 2, 3..."
                            class="w-full px-3 py-2.5 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                    </div>

                    <div class="mb-6">
                        <label for="placa" class="block text-gray-600 text-sm font-bold mb-2">Placa <span class="text-gray-400 font-normal">(opcional)</span></label>
                        <input type="text" id="placa" name="placa" placeholder="Ej: ABC-1234"
                            class="w-full px-3 py-2.5 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                    </div>

                    <button type="submit" id="btnSubmit"
                        class="w-full bg-gradient-to-r from-blue-600 to-blue-800 hover:from-blue-700 hover:to-blue-900 text-white font-bold py-3 px-4 rounded-lg shadow-lg transition-all flex items-center justify-center">
                        <i class="fas fa-save mr-2"></i> Guardar Bus
                    </button>
                </form>

                <a href="buses.php" class="mt-4 inline-block text-center w-full text-sm text-gray-500 hover:text-blue-700 transition-colors">
                    Volver a Buses
                </a>
            </div>
        </div>
    </main>

    <script src="../../Assets/js/buses.js"></script>
</body>
</html>