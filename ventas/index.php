<?php

require_once "../config/auth.php";
require_once "../config/conexion.php";
require_once "../config/helpers.php";


// =====================================================
// LISTADO DE VENTAS (de la más reciente a la más antigua)
// =====================================================

$resultado = $conexion->query("
    SELECT v.id, v.fecha, v.total, v.estado,
           c.nombre AS cliente,
           p.metodo_pago, p.estado AS pago_estado
    FROM ventas v
    LEFT JOIN clientes c ON c.id = v.cliente_id
    LEFT JOIN pagos p ON p.venta_id = v.id
    ORDER BY v.id DESC
    LIMIT 200
");

$menu_activo = "ventas";
$prefijo = "../";

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Ventas - Sistema de Gestión</title>

    <link rel="stylesheet" href="../css/estilos.css">

    <link rel="stylesheet" href="../css/tablas.css?v=1">

    <link rel="stylesheet" href="../css/ventas.css?v=1">

</head>

<body>

    <?php require "../config/menu.php"; ?>

    <main class="main">

        <header class="header">

            <h2>Ventas</h2>

            <p>
                Registro de ventas realizadas.
            </p>

        </header>


        <section class="content-box">

            <div class="barra-acciones">

                <h3>Últimas ventas</h3>

                <a href="nueva.php">
                    <button type="button">+ Nueva venta</button>
                </a>

            </div>


            <?php if ($resultado->num_rows === 0) { ?>

                <p>Todavía no hay ventas registradas.</p>

            <?php } else { ?>

                <div class="tabla-contenedor">

                    <table class="tabla">

                        <thead>
                            <tr>
                                <th class="col-num">N°</th>
                                <th>Fecha</th>
                                <th>Cliente</th>
                                <th>Pago</th>
                                <th class="col-precio">Total</th>
                                <th>Estado</th>
                                <th class="col-acciones">Acciones</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php while ($v = $resultado->fetch_assoc()) { ?>

                                <tr>
                                    <td class="col-num"><?php echo $v["id"]; ?></td>

                                    <td><?php echo date("d/m/Y H:i", strtotime($v["fecha"])); ?></td>

                                    <td class="col-nombre">
                                        <?php echo htmlspecialchars($v["cliente"] ?? "Consumidor final"); ?>
                                    </td>

                                    <td>
                                        <?php echo $v["metodo_pago"] ? nombre_metodo($v["metodo_pago"]) : "-"; ?>
                                        <?php if ($v["pago_estado"] === "PENDIENTE") { ?>
                                            <span class="badge badge-aviso">Pendiente</span>
                                        <?php } ?>
                                    </td>

                                    <td class="col-precio"><?php echo dinero($v["total"]); ?></td>

                                    <td><?php echo badge_estado_venta($v["estado"]); ?></td>

                                    <td class="col-acciones">
                                        <a class="btn-accion btn-editar" href="ver.php?id=<?php echo $v['id']; ?>">Ver</a>
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
