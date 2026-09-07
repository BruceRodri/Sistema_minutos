<?php
// index.php
session_start();
if (isset($_SESSION['usuario_id'])) {
    if ($_SESSION['rol'] === 'admin') {
        header("Location: admin/usuarios.php");
    } elseif ($_SESSION['rol'] === 'secretaria' || $_SESSION['rol'] === 'gerente') {
        header("Location: secretaria/gestion_resoluciones.php");
    } else {
        header("Location: operador/ingreso_resolucion.php");
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Conteo - Ingreso</title>
    <link rel="icon" href="Assets/icons/icon-192x192.png" type="image/png">
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#1f2937">
    <link rel="apple-touch-icon" href="Assets/icons/icon-192x192.png">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 flex items-center justify-center h-screen">

    <div class="bg-gray-800 p-8 rounded-lg shadow-lg w-full max-w-sm">
        <h2 class="text-2xl text-white font-bold text-center mb-6">Acceso al Sistema</h2>
        
        <div id="alertaError" class="hidden mb-4 p-3 bg-red-500 text-white rounded text-sm text-center"></div>
        
        <form id="loginForm">
            <div class="mb-4">
                <label for="cedula" class="block text-gray-400 text-sm font-bold mb-2">Número de Cédula</label>
                <input type="text" id="cedula" name="cedula" required autocomplete="off"
                    class="w-full px-3 py-2 bg-gray-700 text-white rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            
            <div class="mb-6">
                <label for="password" class="block text-gray-400 text-sm font-bold mb-2">Contraseña</label>
                <input type="password" id="password" name="password" required 
                    class="w-full px-3 py-2 bg-gray-700 text-white rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            
            <button type="submit" id="btnSubmit"
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition duration-200">
                Ingresar
            </button>
        </form>
    </div>

    <script src="Assets/js/auth.js"></script>
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('sw.js')
                    .then(reg => console.log('Service Worker registrado correctamente.', reg))
                    .catch(err => console.log('Falló el registro del Service Worker.', err));
            });
        }
    </script>
</body>
</html>