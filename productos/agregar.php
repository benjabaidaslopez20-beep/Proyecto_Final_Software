<?php

require_once "../config/conexion.php";

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Agregar producto</title>

    <link rel="stylesheet" href="../css/estilos.css">

    <link rel="stylesheet" href="../css/formularios.css?v=1">

</head>

<body>

    <main class="main" style="margin-left: 0;">

        <header class="header">

            <h2>Agregar producto</h2>

            <p>
                Cargá los datos del nuevo producto.
            </p>

        </header>


        <section class="content-box">

            <form action="guardar.php" method="POST" class="formulario">

                <div class="campo">
                    <label>Código:</label>

                    <input
                        type="text"
                        name="codigo"
                        required
                    >
                </div>




                <div class="campo">
                    <label>Código de barras:</label>

                    <input
                        type="text"
                        name="codigo_barras"
                    >
                </div>




                <div class="campo campo-completo">
                    <label>Nombre:</label>

                    <input
                        type="text"
                        name="nombre"
                        required
                    >
                </div>




                <div class="campo campo-completo">
                    <label>Descripción:</label>

                    <textarea
                        name="descripcion"
                    ></textarea>
                </div>




                <div class="campo">
                    <label>Precio de compra:</label>

                    <input
                        type="number"
                        name="precio_compra"
                        step="0.01"
                        min="0"
                        required
                    >
                </div>




                <div class="campo">
                    <label>Precio de venta:</label>

                    <input
                        type="number"
                        name="precio_venta"
                        step="0.01"
                        min="0"
                        required
                    >
                </div>




                <div class="campo">
                    <label>Stock inicial:</label>

                    <input
                        type="number"
                        name="stock"
                        min="0"
                        required
                    >
                </div>




                <div class="campo">
                    <label>Stock mínimo:</label>

                    <input
                        type="number"
                        name="stock_minimo"
                        min="0"
                        required
                    >
                </div>




                <div class="formulario-botones">

                    <button type="submit" class="btn-guardar">Guardar producto</button>

                    <a href="index.php" class="btn-cancelar">Cancelar</a>

                </div>

            </form>

        </section>

    </main>

</body>

</html>