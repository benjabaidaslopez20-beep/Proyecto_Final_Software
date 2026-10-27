<?php

require_once "../config/conexion.php";

// Verificamos que el formulario haya sido enviado mediante POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}


// =====================================================
// RECIBIR DATOS DEL FORMULARIO
// =====================================================

$codigo = trim($_POST["codigo"]);
$codigo_barras = trim($_POST["codigo_barras"]);
$nombre = trim($_POST["nombre"]);
$descripcion = trim($_POST["descripcion"]);

$precio_compra = floatval($_POST["precio_compra"]);
$precio_venta = floatval($_POST["precio_venta"]);

$stock = intval($_POST["stock"]);
$stock_minimo = intval($_POST["stock_minimo"]);


// =====================================================
// VALIDACIONES
// =====================================================

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
// COMPROBAR SI EL CÓDIGO YA EXISTE
// =====================================================

$consulta = $conexion->prepare(
    "SELECT id FROM productos WHERE codigo = ?"
);

$consulta->bind_param("s", $codigo);

$consulta->execute();

$resultado = $consulta->get_result();

if ($resultado->num_rows > 0) {

    die("Ya existe un producto con ese código.");

}

$consulta->close();


// =====================================================
// INSERTAR PRODUCTO
// =====================================================

$insertar = $conexion->prepare(
    "INSERT INTO productos
    (codigo, codigo_barras, nombre, descripcion,
     precio_compra, precio_venta, stock, stock_minimo)
    
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
);

$insertar->bind_param(
    "ssssddii",
    $codigo,
    $codigo_barras,
    $nombre,
    $descripcion,
    $precio_compra,
    $precio_venta,
    $stock,
    $stock_minimo
);


// =====================================================
// EJECUTAR
// =====================================================

if ($insertar->execute()) {

    // Obtener el ID del producto recién creado
    $producto_id = $conexion->insert_id;

    // Si se ingresó stock inicial, registramos el movimiento
    if ($stock > 0) {

        $movimiento = $conexion->prepare(
            "INSERT INTO movimientos_stock
            (producto_id, tipo, cantidad, motivo)
            VALUES (?, 'INGRESO', ?, 'Stock inicial')"
        );

        $movimiento->bind_param(
            "ii",
            $producto_id,
            $stock
        );

        $movimiento->execute();

        $movimiento->close();
    }

    $insertar->close();

    // Volver a la lista de productos
    header("Location: index.php");
    exit;

} else {

    echo "Error al guardar el producto: " . $insertar->error;

    $insertar->close();
}

?>