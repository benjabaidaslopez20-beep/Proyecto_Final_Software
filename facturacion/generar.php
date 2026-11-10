<?php

require_once "../config/auth.php";
require_once "../config/conexion.php";

$error = "";


// =====================================================
// GENERAR LA FACTURA (al confirmar una venta de la lista)
// =====================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $venta_id = (int) ($_POST["venta_id"] ?? 0);

    // Traemos la venta junto con el cliente (si tiene)
    $consulta_venta = $conexion->prepare(
        "SELECT v.id, v.subtotal, v.descuento, v.impuestos, v.total, v.estado,
                c.id AS cliente_id, c.nombre AS cliente_nombre,
                c.documento AS cliente_documento, c.direccion AS cliente_direccion,
                c.condicion_iva AS cliente_condicion_iva
         FROM ventas v
         LEFT JOIN clientes c ON c.id = v.cliente_id
         WHERE v.id = ?"
    );

    $consulta_venta->bind_param("i", $venta_id);
    $consulta_venta->execute();

    $venta = $consulta_venta->get_result()->fetch_assoc();


    // Verificamos que la venta exista, esté completada y no tenga ya una factura
    $ya_facturada = false;

    if ($venta) {

        $verificar = $conexion->prepare("SELECT id FROM facturas WHERE venta_id = ? LIMIT 1");
        $verificar->bind_param("i", $venta_id);
        $verificar->execute();

        $ya_facturada = $verificar->get_result()->num_rows > 0;
    }

    if (!$venta || $venta["estado"] !== "COMPLETADA") {

        $error = "La venta no existe o no está completada.";

    } elseif ($ya_facturada) {

        $error = "Esa venta ya tiene una factura generada.";

    } else {

        // Datos de la empresa: de ahí salen el tipo de factura y el punto de venta
        $config = $conexion->query(
            "SELECT tipo_factura_defecto, punto_venta FROM configuracion_empresa WHERE id = 1"
        )->fetch_assoc();

        $tipo        = $config["tipo_factura_defecto"];
        $punto_venta = $config["punto_venta"];

        // Datos del cliente (o "Consumidor Final" si la venta no tiene cliente)
        $cliente_id            = $venta["cliente_id"];
        $cliente_nombre        = $venta["cliente_nombre"] ?? "Consumidor Final";
        $cliente_documento     = $venta["cliente_documento"];
        $cliente_direccion     = $venta["cliente_direccion"];
        $cliente_condicion_iva = $venta["cliente_condicion_iva"] ?? "Consumidor Final";

        $conexion->begin_transaction();

        try {

            // Buscamos el próximo número para ese tipo y punto de venta,
            // bloqueando la fila para evitar que dos facturas salgan con el mismo número
            $conexion->query("SELECT GET_LOCK('numeracion_facturas', 5)");

            $siguiente = $conexion->prepare(
                "SELECT COALESCE(MAX(numero), 0) + 1 AS siguiente
                 FROM facturas
                 WHERE tipo = ? AND punto_venta = ?"
            );

            $siguiente->bind_param("si", $tipo, $punto_venta);
            $siguiente->execute();

            $numero = $siguiente->get_result()->fetch_assoc()["siguiente"];

            $insertar = $conexion->prepare(
                "INSERT INTO facturas
                    (venta_id, tipo, punto_venta, numero, cliente_id, cliente_nombre,
                     cliente_documento, cliente_condicion_iva, cliente_direccion,
                     subtotal, descuento, impuestos, total, usuario_id)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );

            $usuario_id = $_SESSION["usuario_id"] ?? null;

            $insertar->bind_param(
                "isiiissssddddi",
                $venta_id, $tipo, $punto_venta, $numero, $cliente_id, $cliente_nombre,
                $cliente_documento, $cliente_condicion_iva, $cliente_direccion,
                $venta["subtotal"], $venta["descuento"], $venta["impuestos"], $venta["total"],
                $usuario_id
            );

            $insertar->execute();

            $factura_id = $conexion->insert_id;

            $conexion->query("SELECT RELEASE_LOCK('numeracion_facturas')");

            $conexion->commit();

            header("Location: ver.php?id=" . $factura_id);
            exit;

        } catch (Exception $e) {

            $conexion->rollback();

            $error = "No se pudo generar la factura. Probá de nuevo.";
        }
    }
}


// =====================================================
// VENTAS COMPLETADAS QUE TODAVÍA NO TIENEN FACTURA
// =====================================================

$pendientes = $conexion->query(
    "SELECT v.id, v.fecha, v.total,
            COALESCE(c.nombre, 'Consumidor Final') AS cliente_nombre
     FROM ventas v
     LEFT JOIN clientes c ON c.id = v.cliente_id
     LEFT JOIN facturas f ON f.venta_id = v.id
     WHERE v.estado = 'COMPLETADA' AND f.id IS NULL
     ORDER BY v.fecha DESC"
);

$total_pendientes = $pendientes->num_rows;

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Nueva factura - Sistema de Gestión</title>

    <link rel="stylesheet" href="../css/estilos.css">

    <link rel="stylesheet" href="../css/formularios.css?v=1">

    <link rel="stylesheet" href="../css/tablas.css?v=1">

    <link rel="stylesheet" href="../css/ventas.css?v=1">

</head>


<body>


    <?php
        $menu_activo = "facturacion";
        $prefijo = "../";
        require "../config/menu.php";
    ?>


    <main class="main">


        <header class="header">

            <h2>
                Nueva factura
            </h2>

            <p>
                Elegí una venta completada para generar su factura.
            </p>

        </header>


        <?php if ($error !== "") { ?>

            <div class="alerta-error">
                <?php echo htmlspecialchars($error); ?>
            </div>

        <?php } ?>


        <div class="content-box">

            <div class="barra-acciones">

                <h3>
                    Ventas sin facturar
                </h3>

                <a href="index.php" class="btn-secundario">
                    ← Volver al listado
                </a>

            </div>


            <div class="tabla-contenedor">

                <table class="tabla">

                    <thead>

                        <tr>
                            <th>Venta</th>
                            <th>Fecha</th>
                            <th>Cliente</th>
                            <th class="col-precio">Total</th>
                            <th class="col-acciones">Acción</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php if ($total_pendientes == 0) { ?>

                            <tr>
                                <td colspan="5" style="text-align:center; padding: 30px;">
                                    No hay ventas completadas pendientes de facturar.
                                </td>
                            </tr>

                        <?php } ?>


                        <?php while ($v = $pendientes->fetch_assoc()) { ?>

                            <tr>

                                <td class="col-codigo">
                                    #<?php echo $v["id"]; ?>
                                </td>

                                <td>
                                    <?php echo date("d/m/Y H:i", strtotime($v["fecha"])); ?>
                                </td>

                                <td class="col-nombre">
                                    <?php echo htmlspecialchars($v["cliente_nombre"]); ?>
                                </td>

                                <td class="col-precio">
                                    $<?php echo number_format($v["total"], 2, ",", "."); ?>
                                </td>

                                <td class="col-acciones">

                                    <form method="POST">

                                        <input type="hidden" name="venta_id" value="<?php echo $v['id']; ?>">

                                        <button type="submit" class="btn-accion btn-editar">
                                            Generar factura
                                        </button>

                                    </form>

                                </td>

                            </tr>

                        <?php } ?>

                    </tbody>

                </table>

            </div>

        </div>


    </main>


</body>

</html>
