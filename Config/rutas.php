<?php

function obtenerRutaInicio(string $rol): ?string {
    return match ($rol) {
        'admin' => 'Web/admin/dashboard.php',
        'secretaria', 'operativo' => 'Web/admin/turnos.php',
        'conductor', 'socio' => 'App/conductor/dashboard.php',
        default => null,
    };
}
