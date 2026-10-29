<?php

require_once "../config/auth.php";
require_once "../config/conexion.php";


// =====================================================
// DAR DE BAJA UN PRODUCTO
// No se borra de la base de datos: se marca como inactivo
// para conservar el historial (movimientos, ventas, facturas).
// =====================================================

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {

    header("Location: index.php");
    exit;

}

$id = intval($_GET["id"]);

$consulta = $conexion->prepare(
    "UPDATE productos SET activo = 0 WHERE id = ?"
);

$consulta->bind_param("i", $id);

$consulta->execute();

$consulta->close();

header("Location: index.php");
exit;

?>
