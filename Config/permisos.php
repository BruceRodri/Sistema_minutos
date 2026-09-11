<?php

function basePath(): string {
    static $base = null;
    if ($base !== null) return $base;
    $raiz = dirname(__DIR__);
    $docRoot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\');
    $raizNorm = str_replace('\\', '/', $raiz);
    $base = ($docRoot && str_starts_with($raizNorm, $docRoot))
        ? rtrim(substr($raizNorm, strlen($docRoot)), '/') . '/'
        : '/';
    return $base;
}

function ruta(string $relativa): string {
    return basePath() . ltrim($relativa, '/');
}

function catalogoModulosSistema(): array {
    return [
        'app_qr' => ['grupo' => 'APP', 'nombre' => 'QR', 'icono' => 'fa-qrcode', 'ruta' => ruta('App/conductor/dashboard.php')],
        'app_pagos' => ['grupo' => 'APP', 'nombre' => 'Pagos', 'icono' => 'fa-money-bill-wave', 'ruta' => ruta('App/conductor/pagar.php')],
        'web_dashboard' => ['grupo' => 'WEB', 'nombre' => 'Dashboard', 'icono' => 'fa-chart-line', 'ruta' => ruta('Web/admin/dashboard.php')],
        'web_buses' => ['grupo' => 'WEB', 'nombre' => 'Buses', 'icono' => 'fa-bus', 'ruta' => ruta('Web/admin/buses.php')],
        'web_turnos' => ['grupo' => 'WEB', 'nombre' => 'Turnos', 'icono' => 'fa-clock', 'ruta' => ruta('Web/admin/turnos.php')],
        'web_pagos' => ['grupo' => 'WEB', 'nombre' => 'Pagos', 'icono' => 'fa-file-circle-check', 'ruta' => ruta('Web/admin/pagos.php')],
        'web_socios' => ['grupo' => 'WEB', 'nombre' => 'Socios', 'icono' => 'fa-id-card', 'ruta' => ruta('Web/admin/socios.php')],
        'web_valores' => ['grupo' => 'WEB', 'nombre' => 'Valores diarios', 'icono' => 'fa-file-excel', 'ruta' => ruta('Web/admin/valores.php')],
    ];
}

function permisosPredeterminadosRol(string $rol): array {
    if ($rol === 'admin') return array_keys(catalogoModulosSistema());
    if ($rol === 'conductor') return ['app_qr', 'app_pagos'];
    if ($rol === 'socio') return ['app_qr', 'app_pagos'];
    return ['web_dashboard', 'web_buses', 'web_turnos', 'web_pagos', 'web_socios', 'web_valores'];
}

function rutaPrimeraInterfaz(array $permisos, string $grupo): ?string {
    foreach (catalogoModulosSistema() as $codigo => $modulo) {
        if ($modulo['grupo'] === $grupo && in_array($codigo, $permisos, true)) return $modulo['ruta'];
    }
    return null;
}

function permisosEfectivosUsuario(PDO $conexion, int $usuarioId, string $rol): array {
    if ($rol === 'admin') return array_keys(catalogoModulosSistema());
    try {
        $stmt = $conexion->prepare('SELECT modulo, habilitado FROM usuario_permiso_modulo WHERE usuario_id=?');
        $stmt->execute([$usuarioId]);
        $filas = $stmt->fetchAll();
        if (!$filas) return permisosPredeterminadosRol($rol);
        return array_values(array_map(static fn($fila) => $fila['modulo'], array_filter($filas, static fn($fila) => (int)$fila['habilitado'] === 1)));
    } catch (PDOException $e) {
        return permisosPredeterminadosRol($rol);
    }
}

function usuarioPuedeVerModulo(PDO $conexion, string $modulo): bool {
    return in_array($modulo, permisosEfectivosUsuario($conexion, (int)($_SESSION['usuario_id'] ?? 0), (string)($_SESSION['rol'] ?? '')), true);
}

function exigirPermisoModulo(PDO $conexion, string $modulo, string $salida): void {
    if (!usuarioPuedeVerModulo($conexion, $modulo)) {
        header('Location: ' . $salida);
        exit;
    }
}
