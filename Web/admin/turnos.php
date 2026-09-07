<?php
// Web/admin/turnos.php
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
    <title>Turnos - Ejecuttrans</title>
    <link rel="icon" href="../../Assets/icons/icon-192x192.png" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 flex h-screen overflow-hidden">

    <?php include 'components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col overflow-y-auto mt-16 md:mt-0 w-full">
        <header class="h-16 bg-white shadow-sm flex items-center px-4 md:px-8 justify-between border-b border-gray-200">
            <h2 class="text-xl md:text-2xl font-bold text-gray-800 text-transparent bg-clip-text bg-gradient-to-r from-blue-700 to-gray-800">
                Turnos
            </h2>
        </header>

        <div class="flex-1 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-lg p-10 max-w-lg w-full text-center border border-gray-100">
                <div class="mx-auto mb-6 w-24 h-24 rounded-full bg-gradient-to-br from-indigo-500 to-indigo-700 flex items-center justify-center shadow-lg">
                    <i class="fas fa-clock text-white text-4xl"></i>
                </div>
                <h3 class="text-3xl font-bold text-gray-800 mb-3">Bienvenido al módulo de Turnos</h3>
                <p class="text-gray-500 mb-8">
                    Aquí podrá visualizar los turnos del día: qué disco abrió turno y con qué conductor.
                </p>
                <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-bold bg-indigo-100 text-indigo-800 border border-indigo-200">
                    <i class="fas fa-tools mr-2"></i> Módulo en construcción
                </span>
            </div>
        </div>
    </main>
</body>
</html>