<?php

require_once "../config/auth.php";
require_once "../config/conexion.php";

$id = (int) ($_GET["id"] ?? 0);

$consulta = $conexion->prepare("SELECT * FROM facturas WHERE id = ?");
$consulta->bind_param("i", $id);
$consulta->execute();

$factura = $consulta->get_result()->fetch_assoc();

if (!$factura) {

    header("Location: index.php");
    exit;
}


// Ítems de la venta asociada
$items = $conexion->prepare(
    "SELECT dv.cantidad, dv.precio_unitario, dv.subtotal,
            p.nombre AS producto_nombre, p.codigo AS producto_codigo
     FROM detalle_ventas dv
     JOIN productos p ON p.id = dv.producto_id
     WHERE dv.venta_id = ?"
);

$items->bind_param("i", $factura["venta_id"]);
$items->execute();

$items = $items->get_result();


// Datos de la empresa, para el encabezado de la factura
$empresa = $conexion->query(
    "SELECT * FROM configuracion_empresa WHERE id = 1"
)->fetch_assoc();


function formato_numero($tipo, $punto_venta, $numero)
{
    return $tipo . " " . str_pad($punto_venta, 4, "0", STR_PAD_LEFT)
        . "-" . str_pad($numero, 8, "0", STR_PAD_LEFT);
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Factura <?php echo formato_numero($factura['tipo'], $factura['punto_venta'], $factura['numero']); ?></title>

    <link rel="stylesheet" href="../css/estilos.css">

    <link rel="stylesheet" href="../css/tablas.css?v=1">

    <style>

        /* ==========================================
           Al imprimir, se oculta todo menos la factura
        =========================================== */

        @media print {

            .sidebar,
            .topbar,
            .btn-secundario,
            .fac-acciones {
                display: none !important;
            }

            .main {
                margin-left: 0 !important;
                padding: 0 !important;
            }
        }

        .fac-hoja {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            padding: 35px;
        }

        .fac-encabezado {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #1e293b;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }

        .fac-encabezado h2 {
            margin-bottom: 4px;
        }

        .fac-numero {
            text-align: right;
        }

        .fac-numero .tipo {
            display: inline-block;
            border: 2px solid #1e293b;
            border-radius: 6px;
            padding: 2px 10px;
            font-weight: bold;
            font-size: 18px;
            margin-bottom: 6px;
        }

        .fac-datos-cliente {
            margin-bottom: 25px;
        }

        .fac-datos-cliente p {
            margin-bottom: 4px;
            font-size: 14px;
        }

        .fac-totales {
            max-width: 320px;
            margin-left: auto;
            margin-top: 20px;
        }

        .fac-totales-fila {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            font-size: 14px;
        }

        .fac-totales-final {
            border-top: 1px solid #e2e8f0;
            margin-top: 6px;
            padding-top: 10px;
            font-size: 20px;
            font-weight: bold;
        }

        .fac-acciones {
            max-width: 800px;
            margin: 0 auto 16px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

    </style>

</head>


<body>


    <?php
        $menu_activo = "facturacion";
        $prefijo = "../";
        require "../config/menu.php";
    ?>


    <main class="main">


        <div class="fac-acciones">

            <a href="index.php" class="btn-secundario">
                ← Volver al listado
            </a>

            <button type="button" class="btn-secundario" onclick="window.print()">
                🖨️ Descargar
            </button>

        </div>


        <?php if ($factura["estado"] === "ANULADA") { ?>

            <div class="alerta-error" style="max-width: 800px; margin: 0 auto 16px;">
                Esta factura está anulada.
                <?php if ($factura["motivo_anulacion"]) { ?>
                    Motivo: <?php echo htmlspecialchars($factura["motivo_anulacion"]); ?>
                <?php } ?>
            </div>

        <?php } ?>


        <section class="fac-hoja">

            <div class="fac-encabezado">

                <div>
                    <h2><?php echo htmlspecialchars($empresa["razon_social"]); ?></h2>
                    <p>CUIT: <?php echo mostrar_vacio($empresa["cuit"]); ?></p>
                    <p><?php echo mostrar_vacio($empresa["condicion_iva"]); ?></p>
                    <p><?php echo mostrar_vacio($empresa["direccion"]); ?></p>
                </div>

                <div class="fac-numero">
                    <span class="tipo"><?php echo htmlspecialchars($factura["tipo"]); ?></span>
                    <p><?php echo formato_numero($factura['tipo'], $factura['punto_venta'], $factura['numero']); ?></p>
                    <p><?php echo date("d/m/Y H:i", strtotime($factura["fecha_emision"])); ?></p>
                </div>

            </div>


            <div class="fac-datos-cliente">

                <p><strong>Cliente:</strong> <?php echo htmlspecialchars($factura["cliente_nombre"]); ?></p>

                <p><strong>Documento:</strong> <?php echo mostrar_vacio($factura["cliente_documento"]); ?></p>

                <p><strong>Condición frente al IVA:</strong> <?php echo htmlspecialchars($factura["cliente_condicion_iva"]); ?></p>

                <p><strong>Dirección:</strong> <?php echo mostrar_vacio($factura["cliente_direccion"]); ?></p>

            </div>


            <table class="tabla">

                <thead>

                    <tr>
                        <th>Código</th>
                        <th>Producto</th>
                        <th class="col-num">Cantidad</th>
                        <th class="col-precio">Precio unit.</th>
                        <th class="col-precio">Subtotal</th>
                    </tr>

                </thead>

                <tbody>

                    <?php while ($i = $items->fetch_assoc()) { ?>

                        <tr>
                            <td class="col-codigo"><?php echo htmlspecialchars($i["producto_codigo"]); ?></td>
                            <td><?php echo htmlspecialchars($i["producto_nombre"]); ?></td>
                            <td class="col-num"><?php echo $i["cantidad"]; ?></td>
                            <td class="col-precio">$<?php echo number_format($i["precio_unitario"], 2, ",", "."); ?></td>
                            <td class="col-precio">$<?php echo number_format($i["subtotal"], 2, ",", "."); ?></td>
                        </tr>

                    <?php } ?>

                </tbody>

            </table>


            <div class="fac-totales">

                <div class="fac-totales-fila">
                    <span>Subtotal</span>
                    <span>$<?php echo number_format($factura["subtotal"], 2, ",", "."); ?></span>
                </div>

                <div class="fac-totales-fila">
                    <span>Descuento</span>
                    <span>$<?php echo number_format($factura["descuento"], 2, ",", "."); ?></span>
                </div>

                <div class="fac-totales-fila">
                    <span>Impuestos</span>
                    <span>$<?php echo number_format($factura["impuestos"], 2, ",", "."); ?></span>
                </div>

                <div class="fac-totales-fila fac-totales-final">
                    <span>Total</span>
                    <span>$<?php echo number_format($factura["total"], 2, ",", "."); ?></span>
                </div>

            </div>

        </section>


    </main>


</body>

</html>
<?php

// Función chica para mostrar "-" cuando un dato está vacío
function mostrar_vacio($valor)
{
    return ($valor === null || $valor === "")
        ? "-"
        : htmlspecialchars($valor);
}

?>
