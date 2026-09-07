<?php
// Config/conexion.php

// 1. Forzar a PHP a usar la hora de Ecuador
date_default_timezone_set('America/Guayaquil');

$host = 'db'; // Si estás en Hostinger, recuerda cambiarlo a 'localhost'
$dbname = 'sistema_minutos_db'; 
$username = 'root'; 
$password = 'minutos.2026*'; 

try {
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $opciones = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        // 2. Forzar a MySQL a registrar la hora de Ecuador (-05:00)
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '-05:00'" 
    ];
    
    $conexion = new PDO($dsn, $username, $password, $opciones);
} catch (PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}
?>