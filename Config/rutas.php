<?php

function obtenerRutaInicio(string $rol): ?string {
    return match ($rol) {
        'admin', 'secretaria', 'operativo' => 'Web/admin/turnos.php',
        'conductor' => 'App/conductor/dashboard.php',
        default => null,
    };
}
