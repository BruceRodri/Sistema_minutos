<?php
session_start();
if (empty($_SESSION['usuario_id'])) {
    header('Location: ../../index.php');
    exit;
}
require_once '../../Config/conexion.php';
require_once '../../Config/permisos.php';
require_once '../../Dao/UsuarioDao.php';
$usuario = (new UsuarioDao($conexion))->obtenerUsuarioAdministrablePorId((int)$_SESSION['usuario_id']);
if (!$usuario || !$usuario['activo']) {
    header('Location: ../../auth/logout.php');
    exit;
}
$_SESSION['csrf_perfil'] ??= bin2hex(random_bytes(32));
$permisosPerfil = permisosEfectivosUsuario($conexion, (int)$usuario['id'], (string)$usuario['rol']);
if (rutaPrimeraInterfaz($permisosPerfil, 'WEB') === null) {
    header('Location: ../../App/conductor/perfil.php');
    exit;
}
function escaparPerfil($valor) { return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8'); }
$datosPerfil = [
    'Nombres' => $usuario['nombres'],
    'Apellidos' => $usuario['apellidos'],
    'Cédula / usuario' => $usuario['cedula'],
    'Fecha de nacimiento' => date('d/m/Y', strtotime($usuario['fecha_nacimiento'])),
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#2563eb">
    <title>Mi perfil - Sistema de Minutos</title>
    <link rel="icon" href="../../Assets/icons/icon-192x192.png" type="image/png">
    <link rel="manifest" href="/manifest.json">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 text-gray-800 font-sans h-screen flex overflow-hidden">
    <?php include __DIR__ . '/components/sidebar.php'; ?>
    <main class="flex-1 min-w-0 overflow-y-auto p-4 pt-24 md:p-8">
        <div class="max-w-6xl mx-auto">
            <header class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Mi perfil</h1>
                <p class="mt-2 text-gray-500">Consulta tus datos y actualiza tu contraseña.</p>
            </header>
            <div class="grid gap-6 items-start lg:grid-cols-2">
                <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm" aria-labelledby="tituloDatosPerfil">
            <h2 id="tituloDatosPerfil" class="text-xl font-bold mb-4">Mis datos</h2>
            <dl class="grid gap-4 sm:grid-cols-2">
                <?php foreach ($datosPerfil as $etiqueta => $valor): ?>
                    <div class="min-w-0"><dt class="text-sm text-gray-500"><?php echo escaparPerfil($etiqueta); ?></dt><dd class="font-semibold break-words"><?php echo escaparPerfil($valor); ?></dd></div>
                <?php endforeach; ?>
            </dl>
        </section>

                <div class="[&>section]:my-0">
                    <?php include __DIR__ . '/../../App/conductor/components/clave_perfil.php'; ?>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
