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
$puedePagos = usuarioPuedeVerModulo($conexion, 'app_pagos');
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
<body class="min-h-screen bg-gradient-to-b from-blue-50 to-gray-100 text-gray-800">
    <?php include __DIR__ . '/components/cambio_interfaz.php'; ?>
    <main class="mx-auto max-w-2xl px-4 pt-6 pb-32">
        <header class="my-6 flex items-center gap-4">
            <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-blue-600 text-3xl text-white"><i class="fas fa-user"></i></div>
            <div><h1 class="text-3xl font-bold">Mi perfil</h1><p class="text-gray-600">Tus datos y tu contraseña</p></div>
        </header>
        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm" aria-labelledby="tituloDatosPerfil">
            <h2 id="tituloDatosPerfil" class="text-xl font-bold mb-4">Mis datos</h2>
            <dl class="grid gap-4 sm:grid-cols-2">
                <?php foreach ($datosPerfil as $etiqueta => $valor): ?>
                    <div class="min-w-0"><dt class="text-sm text-gray-500"><?php echo escaparPerfil($etiqueta); ?></dt><dd class="font-semibold break-words"><?php echo escaparPerfil($valor); ?></dd></div>
                <?php endforeach; ?>
            </dl>
        </section>
        <?php include __DIR__ . '/components/clave_perfil.php'; ?>
        <a href="../../auth/logout.php" class="block rounded-xl border border-red-200 bg-white p-3 text-center font-bold text-red-600">Cerrar sesión</a>
    </main>
    <nav aria-label="Navegación de la app" class="fixed bottom-0 left-0 right-0 border-t border-gray-200 bg-white shadow-lg">
        <div class="mx-auto flex max-w-2xl">
            <a href="dashboard.php" class="flex flex-1 flex-col items-center py-3 text-gray-500"><i class="fas fa-house text-xl sm:text-2xl"></i><span class="mt-1 whitespace-nowrap text-xs sm:text-sm font-semibold leading-tight">Menú</span></a>
            <a href="perfil.php" aria-current="page" class="flex flex-1 flex-col items-center rounded-t-xl bg-blue-600 py-3 text-white"><i class="fas fa-user text-xl sm:text-2xl"></i><span class="mt-1 whitespace-nowrap text-xs sm:text-sm font-bold leading-tight">Perfil</span></a>
            <?php if ($puedePagos): ?>
                <a href="pagar.php" class="flex flex-1 flex-col items-center py-3 text-gray-500"><i class="fas fa-money-bill-wave text-xl sm:text-2xl"></i><span class="mt-1 whitespace-nowrap text-xs sm:text-sm font-semibold leading-tight">Pagar</span></a>
                <a href="pagos.php" class="flex flex-1 flex-col items-center py-3 text-gray-500"><i class="fas fa-receipt text-xl sm:text-2xl"></i><span class="mt-1 whitespace-nowrap text-xs sm:text-sm font-semibold leading-tight"><span class="sm:hidden">Pagos</span><span class="hidden sm:inline">Pagos realizados</span></span></a>
            <?php endif; ?>
        </div>
    </nav>
</body>
</html>
