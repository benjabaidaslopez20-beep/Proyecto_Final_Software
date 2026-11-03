<?php

require_once "../config/auth.php";
require_once "../config/conexion.php";

// Hace que los errores de MySQL lancen excepciones (así podemos deshacer la transacción)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);


class ErrorProducto extends Exception {}


// Verificamos que el formulario haya sido enviado mediante POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}


// =====================================================
// RECIBIR DATOS DEL FORMULARIO
// =====================================================

$id = intval($_POST["id"] ?? 0);

$codigo = trim($_POST["codigo"] ?? "");
$codigo_barras = trim($_POST["codigo_barras"] ?? "");
$nombre = trim($_POST["nombre"] ?? "");
$descripcion = trim($_POST["descripcion"] ?? "");

$precio_compra = floatval($_POST["precio_compra"] ?? 0);
$precio_venta = floatval($_POST["precio_venta"] ?? 0);

$stock = intval($_POST["stock"] ?? 0);
$stock_minimo = intval($_POST["stock_minimo"] ?? 0);


// =====================================================
// VALIDACIONES
// =====================================================

if ($id <= 0) {

    die("Producto inválido.");

}

if ($codigo === "" || $nombre === "") {

    die("El código y el nombre del producto son obligatorios.");

}

if ($precio_compra < 0 || $precio_venta < 0) {

    die("Los precios no pueden ser negativos.");

}

if ($stock < 0 || $stock_minimo < 0) {

    die("El stock no puede ser negativo.");

}


// =====================================================
// ACTUALIZAR (todo o nada)
// =====================================================

try {

    $conexion->begin_transaction();


    // ---------- El producto tiene que existir (se bloquea mientras se edita) ----------

    $actual = $conexion->prepare("SELECT stock FROM productos WHERE id = ? FOR UPDATE");
    $actual->bind_param("i", $id);
    $actual->execute();

    $producto = $actual->get_result()->fetch_assoc();

    $actual->close();

    if (!$producto) {

        throw new ErrorProducto("El producto no existe.");
    }

    $stock_anterior = (int) $producto["stock"];


    // ---------- El código no puede repetirse en OTRO producto ----------

    $repetido = $conexion->prepare(
        "SELECT id FROM productos WHERE codigo = ? AND id <> ?"
    );

    $repetido->bind_param("si", $codigo, $id);
    $repetido->execute();

    $existe = $repetido->get_result()->num_rows > 0;

    $repetido->close();

    if ($existe) {

        throw new ErrorProducto("Ya existe otro producto con ese código (puede estar dado de baja).");
    }


    // ---------- Guardar los cambios ----------

    $actualizar = $conexion->prepare(
        "UPDATE productos
         SET codigo = ?, codigo_barras = ?, nombre = ?, descripcion = ?,
             precio_compra = ?, precio_venta = ?, stock = ?, stock_minimo = ?
         WHERE id = ?"
    );

    $actualizar->bind_param(
        "ssssddiii",
        $codigo,
        $codigo_barras,
        $nombre,
        $descripcion,
        $precio_compra,
        $precio_venta,
        $stock,
        $stock_minimo,
        $id
    );

    $actualizar->execute();

    $actualizar->close();


    // ---------- Si cambió el stock, queda registrado en movimientos ----------

    $diferencia = $stock - $stock_anterior;

    if ($diferencia !== 0) {

        $tipo = $diferencia > 0 ? "INGRESO" : "EGRESO";
        $cantidad = abs($diferencia);
        $motivo = "Ajuste al editar el producto";

        $movimiento = $conexion->prepare(
            "INSERT INTO movimientos_stock (producto_id, tipo, cantidad, motivo)
             VALUES (?, ?, ?, ?)"
        );

        $movimiento->bind_param("isis", $id, $tipo, $cantidad, $motivo);
        $movimiento->execute();

        $movimiento->close();
    }


    $conexion->commit();

    // Volver a la lista de productos
    header("Location: index.php");
    exit;

} catch (ErrorProducto $e) {

    $conexion->rollback();

    die($e->getMessage());

} catch (Throwable $e) {

    $conexion->rollback();

    // Para ver el error real mientras probás, cambiá el texto por: $e->getMessage()
    die("No se pudo actualizar el producto.");
}

?>
