<?php

require_once "../config/auth.php";
require_once "../config/conexion.php";
require_once "../config/helpers.php";


if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {

    header("Location: index.php");
    exit;
}

$id = intval($_GET["id"]);


// =====================================================
// VENTA
// =====================================================

$q = $conexion->prepare("
    SELECT v.*, c.nombre AS cliente_nombre, c.documento AS cliente_documento
    FROM ventas v
    LEFT JOIN clientes c ON c.id = v.cliente_id
    WHERE v.id = ?
");

$q->bind_param("i", $id);
$q->execute();

$venta = $q->get_result()->fetch_assoc();

$q->close();

if (!$venta) {

    die("La venta no existe.");
}


// =====================================================
// ÍTEMS
// =====================================================

$q = $conexion->prepare("
    SELECT d.cantidad, d.precio_unitario, d.subtotal, p.codigo, p.nombre
    FROM detalle_ventas d
    JOIN productos p ON p.id = d.producto_id
    WHERE d.venta_id = ?
");

$q->bind_param("i", $id);
$q->execute();

$items = $q->get_result();


// =====================================================
// PAGO
// =====================================================

$q2 = $conexion->prepare("SELECT * FROM pagos WHERE venta_id = ? LIMIT 1");
$q2->bind_param("i", $id);
$q2->execute();

$pago = $q2->get_result()->fetch_assoc();

$q2->close();


// Mensajes
$error = $_SESSION["error_venta"] ?? "";
unset($_SESSION["error_venta"]);

$ok = $_GET["ok"] ?? "";

$menu_activo = "ventas";
$prefijo = "../";

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Venta #<?php echo $venta["id"]; ?> - Sistema de Gestión</title>

    <link rel="stylesheet" href="../css/estilos.css">

    <link rel="stylesheet" href="../css/tablas.css?v=1">

    <link rel="stylesheet" href="../css/ventas.css?v=1">

</head>

<body>

    <?php require "../config/menu.php"; ?>

    <main class="main">

        <header class="header">

            <h2>Venta #<?php echo $venta["id"]; ?></h2>

            <p>
                Detalle de la venta.
            </p>

        </header>


        <?php if ($error !== "") { ?>
            <div class="alerta-error"><?php echo htmlspecialchars($error); ?></div>
        <?php } ?>

        <?php if ($ok === "creada") { ?>
            <div class="alerta-ok">Venta registrada correctamente. El stock fue descontado.</div>
        <?php } ?>

        <?php if ($ok === "anulada") { ?>
            <div class="alerta-ok">Venta anulada. El stock fue devuelto.</div>
        <?php } ?>


        <section class="content-box">

            <div class="info-grid">

                <div class="info-item">
                    <span>Fecha</span>
                    <strong><?php echo date("d/m/Y H:i", strtotime($venta["fecha"])); ?></strong>
                </div>

                <div class="info-item">
                    <span>Cliente</span>
                    <strong><?php echo htmlspecialchars($venta["cliente_nombre"] ?? "Consumidor final"); ?></strong>
                </div>

                <div class="info-item">
                    <span>Estado</span>
                    <strong><?php echo badge_estado_venta($venta["estado"]); ?></strong>
                </div>

                <div class="info-item">
                    <span>Pago</span>
                    <strong>
                        <?php if ($pago) { ?>
                            <?php echo nombre_metodo($pago["metodo_pago"]); ?>
                            <?php if ($pago["estado"] === "PENDIENTE") { ?>
                                <span class="badge badge-aviso">Pendiente</span>
                            <?php } ?>
                        <?php } else { echo "-"; } ?>
                    </strong>
                </div>

                <?php if ($pago && $pago["fecha_vencimiento"]) { ?>
                    <div class="info-item">
                        <span>Vencimiento</span>
                        <strong><?php echo date("d/m/Y", strtotime($pago["fecha_vencimiento"])); ?></strong>
                    </div>
                <?php } ?>

            </div>


            <div class="tabla-contenedor">

                <table class="tabla">

                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Producto</th>
                            <th class="col-num">Cantidad</th>
                            <th class="col-precio">Precio</th>
                            <th class="col-precio">Subtotal</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php while ($i = $items->fetch_assoc()) { ?>

                            <tr>
                                <td class="col-codigo"><?php echo htmlspecialchars($i["codigo"]); ?></td>
                                <td class="col-nombre"><?php echo htmlspecialchars($i["nombre"]); ?></td>
                                <td class="col-num"><?php echo $i["cantidad"]; ?></td>
                                <td class="col-precio"><?php echo dinero($i["precio_unitario"]); ?></td>
                                <td class="col-precio"><?php echo dinero($i["subtotal"]); ?></td>
                            </tr>

                        <?php } ?>

                    </tbody>

                </table>

            </div>


            <div class="totales" style="margin-top: 20px;">

                <div class="totales-fila">
                    <span>Subtotal</span>
                    <strong><?php echo dinero($venta["subtotal"]); ?></strong>
                </div>

                <?php if ($venta["descuento"] > 0) { ?>
                    <div class="totales-fila">
                        <span>Descuento</span>
                        <strong>- <?php echo dinero($venta["descuento"]); ?></strong>
                    </div>
                <?php } ?>

                <div class="totales-fila">
                    <span>IVA</span>
                    <strong><?php echo dinero($venta["impuestos"]); ?></strong>
                </div>

                <div class="totales-fila totales-total">
                    <span>Total</span>
                    <span><?php echo dinero($venta["total"]); ?></span>
                </div>

            </div>


            <div class="venta-botones">

                <a href="index.php" class="btn-volver">
                    <span class="btn-volver-flecha">←</span>
                    Volver a ventas
                </a>

                <?php if ($venta["estado"] !== "ANULADA") { ?>

                    <form action="anular.php" method="POST" style="display: inline;"
                          onsubmit="return confirm('¿Anular esta venta? El stock de los productos será devuelto.');">

                        <input type="hidden" name="id" value="<?php echo $venta['id']; ?>">

                        <button type="submit" class="btn-quitar">Anular venta</button>

                    </form>

                <?php } ?>

            </div>

        </section>

    </main>

</body>

</html>