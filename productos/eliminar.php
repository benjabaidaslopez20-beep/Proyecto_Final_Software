<?php

require_once "../config/conexion.php";


// =====================================================
// COMPROBAR QUE RECIBIMOS EL ID
// =====================================================

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {

    header("Location: index.php");
    exit;

}

$id = intval($_GET["id"]);


// =====================================================
// ELIMINAR PRODUCTO
// =====================================================

$consulta = $conexion->prepare(
    "DELETE FROM productos WHERE id = ?"
);

$consulta->bind_param("i", $id);


if ($consulta->execute()) {

    header("Location: index.php");
    exit;

} else {

    echo "No se pudo eliminar el producto.";

}


$consulta->close();

?>