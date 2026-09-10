<?php
function verificarClaveUsuario(array $usuario, string $clave): bool {
    return $usuario['password_hash'] === null
        ? hash_equals((string)$usuario['cedula'], $clave)
        : password_verify($clave, $usuario['password_hash']);
}
