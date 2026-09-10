<?php

function validarCedulaEcuatoriana(string $cedula): bool {
    if (!preg_match('/^\d{10}$/', $cedula)) {
        return false;
    }

    $provincia = (int)substr($cedula, 0, 2);
    $tercerDigito = (int)$cedula[2];
    if ($provincia < 1 || $provincia > 24 || $tercerDigito > 5) {
        return false;
    }

    $suma = 0;
    for ($i = 0; $i < 9; $i++) {
        $valor = (int)$cedula[$i] * ($i % 2 === 0 ? 2 : 1);
        $suma += $valor > 9 ? $valor - 9 : $valor;
    }

    $verificador = (10 - ($suma % 10)) % 10;
    return $verificador === (int)$cedula[9];
}

