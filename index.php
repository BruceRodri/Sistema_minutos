<?php
// index.php
session_start();
require_once __DIR__ . '/Config/rutas.php';

if (isset($_SESSION['usuario_id'])) {
    $destino = obtenerRutaInicio($_SESSION['rol'] ?? '');

    if ($destino !== null) {
        header("Location: $destino");
        exit;
    }

    // Una sesión con un rol sin módulo no debe provocar un ciclo de redirecciones.
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    session_destroy();
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
    <meta name="theme-color" content="#2563eb">
    <link rel="apple-touch-icon" href="Assets/icons/icon-192x192.png">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-blue-50 via-gray-50 to-blue-100 flex items-center justify-center h-screen">

    <div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-sm">
        <h2 class="text-2xl text-gray-800 font-bold text-center mb-2">Acceso al Sistema</h2>
        <p class="text-sm text-gray-500 text-center mb-6">Ingrese sus credenciales para continuar</p>
        
        <div id="alertaError" class="hidden mb-4 p-3 bg-red-500 text-white rounded text-sm text-center"></div>
        
        <form id="loginForm">
            <div class="mb-4">
                <label for="cedula" class="block text-gray-700 text-sm font-bold mb-2">Número de Cédula</label>
                <input type="text" id="cedula" name="cedula" required autocomplete="off" inputmode="numeric" minlength="10" maxlength="10" pattern="[0-9]{10}" data-validar-cedula data-mensaje-cedula="mensajeCedulaLogin"
                    class="w-full px-3 py-2 bg-gray-50 border border-gray-300 text-gray-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <p id="mensajeCedulaLogin" class="hidden"></p>
            </div>
            
            <div class="mb-6">
                <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Contraseña</label>
                <input type="password" id="password" name="password" required 
                    class="w-full px-3 py-2 bg-gray-50 border border-gray-300 text-gray-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            
            <button type="submit" id="btnSubmit"
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-4 rounded-lg focus:outline-none focus:shadow-outline transition duration-200">
                Ingresar
            </button>
            <p class="mt-4 text-xs text-gray-500 text-center">Ingrese su número de cédula en ambos campos.</p>
        </form>
    </div>

    <script src="Assets/js/cedula-ecuatoriana.js?v=1"></script>
    <script src="Assets/js/auth.js?v=2"></script>
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
