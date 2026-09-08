<?php
// Web/admin/valores.php
session_start();
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['rol'] ?? '', ['admin', 'secretaria', 'operativo'])) {
    header("Location: ../../index.php");
    exit;
}

require_once '../../Config/conexion.php';
require_once '../../Dao/ValoresDao.php';

$valoresDao = new ValoresDao($conexion);
$existe = $valoresDao->archivoExiste();
$fechaSubida = $valoresDao->fechaSubida();
$filas = $existe ? $valoresDao->leerFilas() : [];
$totalFilas = count($filas);
$vistaPrevia = array_slice($filas, 0, 10);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Valores Diarios - Ejecuttrans</title>
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
                    Valores Diarios
                </h2>
                <p class="text-xs text-gray-500">Archivo Excel con DISCO, FECHA, VALOR y RUTA</p>
            </div>
        </header>

        <div class="p-4 md:p-8 w-full max-w-7xl mx-auto">

            <div id="alerta" class="hidden mb-6 p-4 rounded-xl text-sm font-bold text-center"></div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Estado actual -->
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-file-excel text-green-600 mr-2"></i> Estado del archivo
                    </h3>
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between border-b border-gray-100 pb-2">
                            <span class="text-gray-500">Archivo:</span>
                            <span class="font-bold text-gray-700"><?php echo $existe ? 'valores_diarios.xlsx' : 'No subido'; ?></span>
                        </div>
                        <div class="flex justify-between border-b border-gray-100 pb-2">
                            <span class="text-gray-500">Última subida:</span>
                            <span class="font-bold text-gray-700"><?php echo $fechaSubida ?: '--'; ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Filas leídas:</span>
                            <span class="font-bold text-gray-700"><?php echo $totalFilas; ?></span>
                        </div>
                    </div>

                    <?php if (!$existe): ?>
                    <p class="mt-6 p-3 bg-amber-50 border border-amber-200 text-amber-700 text-xs rounded-lg">
                        <i class="fas fa-triangle-exclamation mr-1"></i>
                        Aún no hay archivo de valores. Los conductores no podrán abrir turnos hasta subirlo.
                    </p>
                    <?php endif; ?>
                </div>

                <!-- Subir archivo -->
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-upload text-blue-600 mr-2"></i> Subir nuevo archivo
                    </h3>
                    <form id="formSubirValores">
                        <label class="block w-full border-2 border-dashed border-gray-300 rounded-xl p-6 text-center cursor-pointer hover:bg-blue-50 transition-all">
                            <i class="fas fa-file-excel text-4xl text-green-600 mb-2 block"></i>
                            <span id="nombreArchivo" class="text-sm font-bold text-gray-600">Selecciona un archivo .xlsx</span>
                            <span class="block text-xs text-gray-400 mt-1">Columnas requeridas: DISCO, FECHA, VALOR, RUTA</span>
                            <input type="file" id="inputArchivo" name="archivo" accept=".xlsx" class="hidden" required>
                        </label>
                        <button type="submit" id="btnSubir"
                            class="mt-4 w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg shadow-lg transition-all">
                            <i class="fas fa-upload mr-2"></i> Subir archivo
                        </button>
                    </form>
                </div>

                <!-- Vista previa -->
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 lg:col-span-1">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-table text-purple-600 mr-2"></i> Vista previa
                    </h3>
                    <?php if (empty($vistaPrevia)): ?>
                        <p class="text-sm text-gray-400">Sin datos para mostrar.</p>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-bold text-gray-500">Disco</th>
                                        <th class="px-3 py-2 text-left font-bold text-gray-500">Fecha</th>
                                        <th class="px-3 py-2 text-right font-bold text-gray-500">Valor</th>
                                        <th class="px-3 py-2 text-left font-bold text-gray-500">Ruta</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <?php foreach ($vistaPrevia as $fila): ?>
                                    <tr>
                                        <td class="px-3 py-2 font-mono"><?php echo htmlspecialchars($fila['disco']); ?></td>
                                        <td class="px-3 py-2"><?php echo htmlspecialchars($fila['fecha']); ?></td>
                                        <td class="px-3 py-2 text-right font-bold text-blue-700">$ <?php echo number_format($fila['valor'], 2, '.', ','); ?></td>
                                        <td class="px-3 py-2"><?php echo htmlspecialchars($fila['ruta']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <script src="../../Assets/js/valores.js"></script>
</body>
</html>