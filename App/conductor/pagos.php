<?php
// conductor/pagos.php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'conductor') {
    header("Location: ../../index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagos - Sistema de Minutos</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-900 min-h-screen flex items-center justify-center p-4">
    <div class="bg-gray-800 rounded-2xl p-8 w-full max-w-sm text-center border border-gray-700">
        <div class="mx-auto mb-4 w-16 h-16 rounded-full bg-yellow-600 flex items-center justify-center">
            <i class="fas fa-money-bill-wave text-white text-2xl"></i>
        </div>
        <h2 class="text-2xl font-bold text-white mb-2">Pagos</h2>
        <p class="text-gray-400 mb-6">Este módulo estará disponible próximamente.</p>
        <a href="dashboard.php" class="inline-block w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-xl transition-all">
            Volver al inicio
        </a>
    </div>
</body>
</html>