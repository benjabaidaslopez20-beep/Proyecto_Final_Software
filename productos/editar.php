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
// BUSCAR PRODUCTO
// =====================================================

$consulta = $conexion->prepare(
    "SELECT * FROM productos WHERE id = ?"
);

$consulta->bind_param("i", $id);

$consulta->execute();

$resultado = $consulta->get_result();


// Si no existe
if ($resultado->num_rows === 0) {

    die("El producto no existe.");

}

$producto = $resultado->fetch_assoc();

$consulta->close();

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Editar producto</title>

    <link rel="stylesheet" href="../css/estilos.css">

    <link rel="stylesheet" href="../css/formularios.css?v=1">

</head>

<body>

    <main class="main" style="margin-left: 0;">

        <header class="header">

            <h2>Editar producto</h2>

            <p>
                Modificá los datos del producto.
            </p>

        </header>


        <section class="content-box">

            <form action="actualizar.php" method="POST" class="formulario">

                <!-- ID OCULTO -->

                <input
                    type="hidden"
                    name="id"
                    value="<?php echo $producto['id']; ?>"
                >


                <!-- CÓDIGO -->

                <div class="campo">

                    <label>Código:</label>

                    <input
                        type="text"
                        name="codigo"
                        value="<?php echo htmlspecialchars($producto['codigo']); ?>"
                        required
                    >

                </div>




                <!-- CÓDIGO DE BARRAS -->

                <div class="campo">

                    <label>Código de barras:</label>

                    <input
                        type="text"
                        name="codigo_barras"
                        value="<?php echo htmlspecialchars($producto['codigo_barras'] ?? ''); ?>"
                    >

                </div>




                <!-- NOMBRE -->

                <div class="campo campo-completo">

                    <label>Nombre:</label>

                    <input
                        type="text"
                        name="nombre"
                        value="<?php echo htmlspecialchars($producto['nombre']); ?>"
                        required
                    >

                </div>




                <!-- DESCRIPCIÓN -->

                <div class="campo campo-completo">

                    <label>Descripción:</label>

                    <textarea name="descripcion"><?php echo htmlspecialchars($producto['descripcion'] ?? ''); ?></textarea>

                </div>




                <!-- PRECIO DE COMPRA -->

                <div class="campo">

                    <label>Precio de compra:</label>

                    <input
                        type="number"
                        name="precio_compra"
                        value="<?php echo $producto['precio_compra']; ?>"
                        step="0.01"
                        min="0"
                        required
                    >

                </div>




                <!-- PRECIO DE VENTA -->

                <div class="campo">

                    <label>Precio de venta:</label>

                    <input
                        type="number"
                        name="precio_venta"
                        value="<?php echo $producto['precio_venta']; ?>"
                        step="0.01"
                        min="0"
                        required
                    >

                </div>




                <!-- STOCK -->

                <div class="campo">

                    <label>Stock:</label>

                    <input
                        type="number"
                        name="stock"
                        value="<?php echo $producto['stock']; ?>"
                        min="0"
                        required
                    >

                </div>




                <!-- STOCK MÍNIMO -->

                <div class="campo">

                    <label>Stock mínimo:</label>

                    <input
                        type="number"
                        name="stock_minimo"
                        value="<?php echo $producto['stock_minimo']; ?>"
                        min="0"
                        required
                    >

                </div>




                <div class="formulario-botones">

                    <button type="submit" class="btn-guardar">Guardar cambios</button>

                    <a href="index.php" class="btn-cancelar">Cancelar</a>

                </div>

            </form>

        </section>

    </main>

</body>

</html>