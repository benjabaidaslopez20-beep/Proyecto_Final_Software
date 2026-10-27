<?php

require_once "../config/conexion.php";


// =====================================================
// ¿LA TABLA TIENE COLUMNA DE FECHA?
// =====================================================

$tiene_fecha = $conexion
    ->query("SHOW COLUMNS FROM movimientos_stock LIKE 'fecha'")
    ->num_rows > 0;

$columna_fecha = $tiene_fecha ? ", m.fecha" : "";


// =====================================================
// MOVIMIENTOS (del más reciente al más antiguo)
// =====================================================

$consulta = "
    SELECT m.id, m.tipo, m.cantidad, m.motivo,
           p.codigo, p.nombre
           $columna_fecha
    FROM movimientos_stock m
    LEFT JOIN productos p ON p.id = m.producto_id
    ORDER BY m.id DESC
    LIMIT 100
";

$resultado = $conexion->query($consulta);

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Movimientos - Sistema de Gestión</title>

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
                <a href="../productos/index.php">
                    📦 Productos
                </a>
            </li>

            <li>
                <a href="index.php" class="active">
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

            <h2>Movimientos</h2>

            <p>
                Actividad reciente: ingresos y salidas de stock.
            </p>

        </header>


        <section class="content-box">

            <h3>Últimos movimientos</h3>

            <?php if ($resultado->num_rows === 0) { ?>

                <p>Todavía no hay movimientos registrados.</p>

            <?php } else { ?>

                <div class="tabla-contenedor">

                    <table class="tabla">

                        <thead>
                            <tr>
                                <th class="col-num">#</th>
                                <?php if ($tiene_fecha) { ?>
                                    <th>Fecha</th>
                                <?php } ?>
                                <th>Código</th>
                                <th>Producto</th>
                                <th>Tipo</th>
                                <th class="col-stock">Cantidad</th>
                                <th>Motivo</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php while ($m = $resultado->fetch_assoc()) { ?>

                                <tr>
                                    <td class="col-num"><?php echo $m["id"]; ?></td>

                                    <?php if ($tiene_fecha) { ?>
                                        <td><?php echo date("d/m/Y H:i", strtotime($m["fecha"])); ?></td>
                                    <?php } ?>

                                    <td class="col-codigo"><?php echo htmlspecialchars($m["codigo"] ?? "-"); ?></td>
                                    <td class="col-nombre"><?php echo htmlspecialchars($m["nombre"] ?? "Producto eliminado"); ?></td>

                                    <td>
                                        <?php $clase = ($m["tipo"] === "INGRESO") ? "badge-ok" : "badge-bajo"; ?>
                                        <span class="badge <?php echo $clase; ?>">
                                            <?php echo htmlspecialchars($m["tipo"]); ?>
                                        </span>
                                    </td>

                                    <td class="col-stock"><?php echo $m["cantidad"]; ?></td>

                                    <td><?php echo htmlspecialchars($m["motivo"] ?? ""); ?></td>
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
