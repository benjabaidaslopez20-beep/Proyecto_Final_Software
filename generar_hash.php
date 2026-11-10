<?php

// =====================================================
// SCRIPT TEMPORAL — generar un hash de contraseña nueva
// Usalo para restablecer el acceso y DESPUÉS BORRALO.
// No lo dejes en el proyecto ni lo subas a ningún repositorio.
// =====================================================

$password_nueva = "Prueba123"; // <-- cambiá esto por la contraseña que quieras usar

$hash = password_hash($password_nueva, PASSWORD_DEFAULT);

echo "Contraseña: " . htmlspecialchars($password_nueva) . "<br>";
echo "Hash: " . $hash;

?>
