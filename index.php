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
                <div class="relative">
                    <input type="password" id="password" name="password" required autocomplete="current-password"
                        class="w-full bg-gray-50 border border-gray-300 py-2 pl-3 pr-12 text-gray-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <button id="togglePasswordLogin" type="button" aria-label="Mostrar contraseña" aria-pressed="false"
                        class="absolute inset-y-0 right-0 flex w-12 items-center justify-center rounded-r-lg text-gray-500 hover:text-blue-600 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500">
                        <svg data-icono-mostrar aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        <svg data-icono-ocultar aria-hidden="true" class="hidden h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="m3 3 18 18"></path>
                            <path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"></path>
                            <path d="M9.9 4.2A10.8 10.8 0 0 1 12 4c6.5 0 10 8 10 8a18 18 0 0 1-2.1 3.2"></path>
                            <path d="M6.6 6.6C3.6 8.5 2 12 2 12s3.5 8 10 8a9.8 9.8 0 0 0 4.1-.9"></path>
                        </svg>
                    </button>
                </div>
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
