<?php

$servidor = "localhost";
$usuario = "root";
$password = "";
$base_datos = "sistema_gestion";

$conexion = new mysqli(
    $servidor,
    $usuario,
    $password,
    $base_datos
);

// Comprobar conexión
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Configuración de caracteres
$conexion->set_charset("utf8mb4");

?>
