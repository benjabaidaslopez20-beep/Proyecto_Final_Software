<?php

require_once "../config/conexion.php";

// Productos del más nuevo al más antiguo
$resultado = $conexion->query(
    "SELECT * FROM productos ORDER BY id DESC"
);

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Productos - Sistema de Gestión</title>

    <link rel="stylesheet" href="../css/estilos.css">

    <link rel="stylesheet" href="../css/tablas.css?v=1">

</head>

<body>

    <!-- BARRA LATERAL -->

    <aside class="sidebar">

        <div class="logo">
            <h1>📦 Mi Gestión</h1>
            <p>Sistema de gestión</p>
        </div>

        <ul class="menu">

            <li>
                <a href="../index.php">
                    🏠 Inicio
                </a>
            </li>

            <li>
                <a href="index.php" class="active">
                    📦 Productos
                </a>
            </li>

            <li>
                <a href="../movimientos/index.php">
                    ↕️ Movimientos
                </a>
            </li>

            <li>
                <a href="#">
                    🛒 Ventas
                </a>
            </li>

            <li>
                <a href="#">
                    🧾 Facturación
                </a>
            </li>

            <li>
                <a href="#">
                    👥 Clientes
                </a>
            </li>

            <li>
                <a href="#">
                    📊 Reportes
                </a>
            </li>

        </ul>

    </aside>


    <!-- CONTENIDO -->

    <main class="main">

        <header class="header">

            <h2>Productos</h2>

            <p>
                Gestión de productos y stock.
            </p>

        </header>


        <section class="content-box">

            <h3>Agregar producto</h3>

            <a href="agregar.php">
                <button type="button">
                    + Agregar producto
                </button>
            </a>

        </section>


        <section class="content-box" style="margin-top: 20px;">

            <h3>Listado de productos</h3>

            <?php if ($resultado->num_rows === 0) { ?>

                <p>Todavía no hay productos cargados.</p>

            <?php } else { ?>

                <div class="tabla-contenedor">

                <table class="tabla">

                    <thead>
                        <tr>
                            <th class="col-num">#</th>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th class="col-precio">Compra</th>
                            <th class="col-precio">Venta</th>
                            <th class="col-stock">Stock</th>
                            <th class="col-acciones">Acciones</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php while ($p = $resultado->fetch_assoc()) { ?>

                            <tr>
                                <td class="col-num"><?php echo $p["id"]; ?></td>
                                <td class="col-codigo"><?php echo htmlspecialchars($p["codigo"]); ?></td>
                                <td class="col-nombre"><?php echo htmlspecialchars($p["nombre"]); ?></td>
                                <td class="col-precio">$<?php echo number_format($p["precio_compra"], 2, ",", "."); ?></td>
                                <td class="col-precio">$<?php echo number_format($p["precio_venta"], 2, ",", "."); ?></td>
                                <td class="col-stock">
                                    <?php $clase = ($p["stock"] <= $p["stock_minimo"]) ? "badge-bajo" : "badge-ok"; ?>
                                    <span class="badge <?php echo $clase; ?>"><?php echo $p["stock"]; ?></span>
                                </td>
                                <td class="col-acciones">
                                    <a class="btn-accion btn-editar" href="editar.php?id=<?php echo $p['id']; ?>">Editar</a>
                                    <a class="btn-accion btn-eliminar" href="eliminar.php?id=<?php echo $p['id']; ?>"
                                       onclick="return confirm('¿Eliminar este producto?');">Eliminar</a>
                                </td>
                            </tr>

                        <?php } ?>

                    </tbody>

                </table>

                </div>

            <?php } ?>

        </section>

    </main>

</body>

</html>