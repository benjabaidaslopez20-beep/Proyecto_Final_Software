<?php

require_once "../config/auth.php";
require_once "../config/conexion.php";


// =====================================================
// REACTIVAR UN PRODUCTO DADO DE BAJA
// =====================================================

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {

    header("Location: index.php?ver=bajas");
    exit;

}

$id = intval($_GET["id"]);

$consulta = $conexion->prepare(
    "UPDATE productos SET activo = 1 WHERE id = ?"
);

$consulta->bind_param("i", $id);

$consulta->execute();

$consulta->close();

header("Location: index.php");
exit;

?>
