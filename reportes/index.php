<?php

require_once "../config/auth.php";
require_once "../config/conexion.php";
require_once "../config/helpers.php";


// =====================================================
// FUNCIONES
// =====================================================

function consultar($conexion, $sql, $tipos = "", $params = [])
{
    $st = $conexion->prepare($sql);

    if ($tipos !== "") {
        $st->bind_param($tipos, ...$params);
    }

    $st->execute();

    $filas = $st->get_result()->fetch_all(MYSQLI_ASSOC);

    $st->close();

    return $filas;
}

function fecha_valida($f)
{
    $d = DateTime::createFromFormat("Y-m-d", $f);

    return $d && $d->format("Y-m-d") === $f;
}

function fecha_corta($f)
{
    return date("d/m/Y", strtotime($f));
}


// =====================================================
// QUÉ REPORTE Y QUÉ PERÍODO
// =====================================================

$reportes = [
    "ventas"    => "📈 Ventas",
    "productos" => "🏆 Más vendidos",
    "stock"     => "📦 Stock",
    "pagares"   => "💳 Pagarés pendientes",
];

$r = (string) ($_GET["r"] ?? "ventas");

if (!isset($reportes[$r])) {
    $r = "ventas";
}

$hoy = date("Y-m-d");

$desde = (string) ($_GET["desde"] ?? date("Y-m-01"));
$hasta = (string) ($_GET["hasta"] ?? $hoy);

if (!fecha_valida($desde)) { $desde = date("Y-m-01"); }
if (!fecha_valida($hasta)) { $hasta = $hoy; }

if ($desde > $hasta) {
    $tmp = $desde; $desde = $hasta; $hasta = $tmp;
}

// Condición reutilizable para filtrar ventas por fecha
$rango = "v.estado = 'COMPLETADA'
          AND v.fecha >= ?
          AND v.fecha < DATE_ADD(?, INTERVAL 1 DAY)";


// =====================================================
// DATOS DE CADA REPORTE
// =====================================================

// ---------- VENTAS ----------

if ($r === "ventas") {

    $por_dia = consultar($conexion, "
        SELECT DATE(v.fecha) AS dia,
               COUNT(*) AS cant,
               SUM(v.subtotal) AS subtotal,
               SUM(v.descuento) AS descuento,
               SUM(v.impuestos) AS iva,
               SUM(v.total) AS total
        FROM ventas v
        WHERE $rango
        GROUP BY DATE(v.fecha)
        ORDER BY dia DESC
    ", "ss", [$desde, $hasta]);

    $por_metodo = consultar($conexion, "
        SELECT p.metodo_pago,
               COUNT(*) AS cant,
               SUM(p.monto) AS monto
        FROM pagos p
        JOIN ventas v ON v.id = p.venta_id
        WHERE $rango
        GROUP BY p.metodo_pago
        ORDER BY monto DESC
    ", "ss", [$desde, $hasta]);

    $t = ["cant" => 0, "subtotal" => 0, "descuento" => 0, "iva" => 0, "total" => 0];

    foreach ($por_dia as $f) {
        foreach ($t as $k => $v) {
            $t[$k] += $f[$k];
        }
    }

    $max_metodo = 0;

    foreach ($por_metodo as $f) {
        $max_metodo = max($max_metodo, $f["monto"]);
    }
}


// ---------- PRODUCTOS MÁS VENDIDOS ----------

if ($r === "productos") {

    $vendidos = consultar($conexion, "
        SELECT p.codigo, p.nombre,
               SUM(d.cantidad) AS unidades,
               SUM(d.subtotal) AS importe,
               SUM((d.precio_unitario - p.precio_compra) * d.cantidad) AS ganancia
        FROM detalle_ventas d
        JOIN ventas v ON v.id = d.venta_id
        JOIN productos p ON p.id = d.producto_id
        WHERE $rango
        GROUP BY p.id, p.codigo, p.nombre
        ORDER BY unidades DESC, importe DESC
    ", "ss", [$desde, $hasta]);

    $unidades = 0;
    $importe = 0;
    $ganancia = 0;

    foreach ($vendidos as $f) {
        $unidades += $f["unidades"];
        $importe  += $f["importe"];
        $ganancia += $f["ganancia"];
    }

    $max_unidades = count($vendidos) ? $vendidos[0]["unidades"] : 0;

    $top = array_slice($vendidos, 0, 20);
}


// ---------- STOCK ----------

if ($r === "stock") {

    $bajos = consultar($conexion, "
        SELECT codigo, nombre, stock, stock_minimo
        FROM productos
        WHERE activo = 1 AND stock <= stock_minimo
        ORDER BY (stock - stock_minimo) ASC, nombre
    ");

    $inv = consultar($conexion, "
        SELECT COUNT(*) AS cant,
               COALESCE(SUM(stock * precio_compra), 0) AS costo,
               COALESCE(SUM(stock * precio_venta), 0) AS venta
        FROM productos
        WHERE activo = 1
    ")[0];
}


// ---------- PAGARÉS PENDIENTES ----------

if ($r === "pagares") {

    $pagares = consultar($conexion, "
        SELECT p.venta_id, p.monto, p.fecha_vencimiento,
               c.nombre AS cliente
        FROM pagos p
        JOIN ventas v ON v.id = p.venta_id
        LEFT JOIN clientes c ON c.id = v.cliente_id
        WHERE p.metodo_pago = 'PAGARE'
          AND p.estado IN ('PENDIENTE', 'VENCIDO')
          AND v.estado <> 'ANULADA'
        ORDER BY p.fecha_vencimiento ASC
    ");

    $cant_venc = 0;
    $monto_venc = 0;
    $monto_total = 0;

    foreach ($pagares as $f) {

        $monto_total += $f["monto"];

        if ($f["fecha_vencimiento"] && $f["fecha_vencimiento"] < $hoy) {
            $cant_venc++;
            $monto_venc += $f["monto"];
        }
    }
}


// =====================================================
// ATAJOS DE FECHA
// =====================================================

$atajos = [
    "Hoy"          => [$hoy, $hoy],
    "Últimos 7 días" => [date("Y-m-d", strtotime("-6 days")), $hoy],
    "Este mes"     => [date("Y-m-01"), $hoy],
    "Mes anterior" => [
        date("Y-m-01", strtotime("first day of last month")),
        date("Y-m-t", strtotime("last day of last month")),
    ],
];

$usa_fechas = in_array($r, ["ventas", "productos"], true);

$menu_activo = "reportes";
$prefijo = "../";

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Reportes - Sistema de Gestión</title>

    <link rel="stylesheet" href="../css/estilos.css">

    <link rel="stylesheet" href="../css/tablas.css?v=1">

    <link rel="stylesheet" href="../css/formularios.css?v=1">

    <link rel="stylesheet" href="../css/ventas.css?v=1">

    <link rel="stylesheet" href="../css/reportes.css?v=1">

</head>

<body>

    <?php require "../config/menu.php"; ?>

    <main class="main">

        <header class="header">

            <h2>Reportes</h2>

            <p>
                Resúmenes de ventas, stock y cobros.
            </p>

        </header>


        <!-- PESTAÑAS -->

        <nav class="tabs no-imprimir">

            <?php foreach ($reportes as $clave => $titulo) { ?>

                <a href="index.php?r=<?php echo $clave; ?>"
                   class="<?php echo $clave === $r ? 'activa' : ''; ?>">
                    <?php echo $titulo; ?>
                </a>

            <?php } ?>

        </nav>


        <!-- FILTRO DE FECHAS -->

        <?php if ($usa_fechas) { ?>

            <form method="GET" class="filtros no-imprimir">

                <input type="hidden" name="r" value="<?php echo $r; ?>">

                <div class="campo">
                    <label>Desde</label>
                    <input type="date" name="desde" value="<?php echo $desde; ?>">
                </div>

                <div class="campo">
                    <label>Hasta</label>
                    <input type="date" name="hasta" value="<?php echo $hasta; ?>">
                </div>

                <button type="submit">Filtrar</button>

                <div class="filtros-rapidos">

                    <?php foreach ($atajos as $nombre => $rango_a) { ?>

                        <a href="index.php?r=<?php echo $r; ?>&desde=<?php echo $rango_a[0]; ?>&hasta=<?php echo $rango_a[1]; ?>">
                            <?php echo $nombre; ?>
                        </a>

                    <?php } ?>

                </div>

            </form>

            <p class="reporte-periodo">
                Período: del <strong><?php echo fecha_corta($desde); ?></strong>
                al <strong><?php echo fecha_corta($hasta); ?></strong>
                · Solo ventas completadas (no incluye anuladas).
            </p>

        <?php } ?>


        <!-- ==================================================
             REPORTE: VENTAS
        =================================================== -->

        <?php if ($r === "ventas") { ?>

            <section class="cards">

                <div class="card">
                    <div class="card-title">Ventas realizadas</div>
                    <div class="card-value"><?php echo $t["cant"]; ?></div>
                </div>

                <div class="card">
                    <div class="card-title">Neto (sin IVA)</div>
                    <div class="card-value"><?php echo dinero($t["subtotal"] - $t["descuento"]); ?></div>
                </div>

                <div class="card">
                    <div class="card-title">IVA</div>
                    <div class="card-value"><?php echo dinero($t["iva"]); ?></div>
                </div>

                <div class="card">
                    <div class="card-title">Total vendido</div>
                    <div class="card-value"><?php echo dinero($t["total"]); ?></div>
                </div>

            </section>


            <section class="content-box">

                <h3>Ventas por día</h3>

                <?php if (count($por_dia) === 0) { ?>

                    <p class="sin-datos">No hay ventas en este período.</p>

                <?php } else { ?>

                    <div class="tabla-contenedor">

                        <table class="tabla">

                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th class="col-num">Ventas</th>
                                    <th class="col-precio">Subtotal</th>
                                    <th class="col-precio">Descuento</th>
                                    <th class="col-precio">IVA</th>
                                    <th class="col-precio">Total</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php foreach ($por_dia as $f) { ?>

                                    <tr>
                                        <td><?php echo fecha_corta($f["dia"]); ?></td>
                                        <td class="col-num"><?php echo $f["cant"]; ?></td>
                                        <td class="col-precio"><?php echo dinero($f["subtotal"]); ?></td>
                                        <td class="col-precio"><?php echo dinero($f["descuento"]); ?></td>
                                        <td class="col-precio"><?php echo dinero($f["iva"]); ?></td>
                                        <td class="col-precio"><strong><?php echo dinero($f["total"]); ?></strong></td>
                                    </tr>

                                <?php } ?>

                                <tr class="fila-total">
                                    <td>TOTAL</td>
                                    <td class="col-num"><?php echo $t["cant"]; ?></td>
                                    <td class="col-precio"><?php echo dinero($t["subtotal"]); ?></td>
                                    <td class="col-precio"><?php echo dinero($t["descuento"]); ?></td>
                                    <td class="col-precio"><?php echo dinero($t["iva"]); ?></td>
                                    <td class="col-precio"><?php echo dinero($t["total"]); ?></td>
                                </tr>

                            </tbody>

                        </table>

                    </div>

                <?php } ?>

            </section>


            <section class="content-box" style="margin-top: 20px;">

                <h3>Ventas por método de pago</h3>

                <?php if (count($por_metodo) === 0) { ?>

                    <p class="sin-datos">No hay pagos en este período.</p>

                <?php } else { ?>

                    <div class="tabla-contenedor">

                        <table class="tabla">

                            <thead>
                                <tr>
                                    <th>Método</th>
                                    <th class="col-num">Ventas</th>
                                    <th class="col-precio">Monto</th>
                                    <th style="width: 30%;">Proporción</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php foreach ($por_metodo as $f) { ?>

                                    <tr>
                                        <td class="col-nombre"><?php echo nombre_metodo($f["metodo_pago"]); ?></td>
                                        <td class="col-num"><?php echo $f["cant"]; ?></td>
                                        <td class="col-precio"><?php echo dinero($f["monto"]); ?></td>
                                        <td>
                                            <div class="barra">
                                                <span style="width: <?php echo $max_metodo > 0 ? round($f['monto'] / $max_metodo * 100) : 0; ?>%;"></span>
                                            </div>
                                        </td>
                                    </tr>

                                <?php } ?>

                            </tbody>

                        </table>

                    </div>

                <?php } ?>

            </section>

        <?php } ?>


        <!-- ==================================================
             REPORTE: PRODUCTOS MÁS VENDIDOS
        =================================================== -->

        <?php if ($r === "productos") { ?>

            <section class="cards">

                <div class="card">
                    <div class="card-title">Unidades vendidas</div>
                    <div class="card-value"><?php echo $unidades; ?></div>
                </div>

                <div class="card">
                    <div class="card-title">Importe vendido (sin IVA)</div>
                    <div class="card-value"><?php echo dinero($importe); ?></div>
                </div>

                <div class="card">
                    <div class="card-title">Ganancia estimada</div>
                    <div class="card-value"><?php echo dinero($ganancia); ?></div>
                </div>

                <div class="card">
                    <div class="card-title">Productos distintos</div>
                    <div class="card-value"><?php echo count($vendidos); ?></div>
                </div>

            </section>


            <section class="content-box">

                <h3>Top 20 productos más vendidos</h3>

                <?php if (count($top) === 0) { ?>

                    <p class="sin-datos">No hay ventas en este período.</p>

                <?php } else { ?>

                    <div class="tabla-contenedor">

                        <table class="tabla">

                            <thead>
                                <tr>
                                    <th class="col-num">#</th>
                                    <th>Código</th>
                                    <th>Producto</th>
                                    <th class="col-num">Unidades</th>
                                    <th class="col-precio">Importe</th>
                                    <th class="col-precio">Ganancia est.</th>
                                    <th style="width: 20%;">Proporción</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php foreach ($top as $i => $f) { ?>

                                    <tr>
                                        <td class="col-num"><?php echo $i + 1; ?></td>
                                        <td class="col-codigo"><?php echo htmlspecialchars($f["codigo"]); ?></td>
                                        <td class="col-nombre"><?php echo htmlspecialchars($f["nombre"]); ?></td>
                                        <td class="col-num"><?php echo $f["unidades"]; ?></td>
                                        <td class="col-precio"><?php echo dinero($f["importe"]); ?></td>
                                        <td class="col-precio"><?php echo dinero($f["ganancia"]); ?></td>
                                        <td>
                                            <div class="barra">
                                                <span style="width: <?php echo $max_unidades > 0 ? round($f['unidades'] / $max_unidades * 100) : 0; ?>%;"></span>
                                            </div>
                                        </td>
                                    </tr>

                                <?php } ?>

                            </tbody>

                        </table>

                    </div>

                    <p class="reporte-periodo" style="margin-top: 15px;">
                        La ganancia estimada usa el precio de compra actual de cada producto.
                    </p>

                <?php } ?>

            </section>

        <?php } ?>


        <!-- ==================================================
             REPORTE: STOCK
        =================================================== -->

        <?php if ($r === "stock") { ?>

            <section class="cards">

                <div class="card">
                    <div class="card-title">Productos activos</div>
                    <div class="card-value"><?php echo $inv["cant"]; ?></div>
                </div>

                <div class="card">
                    <div class="card-title">Con stock bajo</div>
                    <div class="card-value"><?php echo count($bajos); ?></div>
                </div>

                <div class="card">
                    <div class="card-title">Inventario a costo</div>
                    <div class="card-value"><?php echo dinero($inv["costo"]); ?></div>
                </div>

                <div class="card">
                    <div class="card-title">Inventario a precio de venta</div>
                    <div class="card-value"><?php echo dinero($inv["venta"]); ?></div>
                </div>

            </section>


            <section class="content-box">

                <h3>Productos para reponer</h3>

                <?php if (count($bajos) === 0) { ?>

                    <p class="sin-datos">Todos los productos tienen stock por encima del mínimo. 👌</p>

                <?php } else { ?>

                    <div class="tabla-contenedor">

                        <table class="tabla">

                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Producto</th>
                                    <th class="col-stock">Stock</th>
                                    <th class="col-stock">Mínimo</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php foreach ($bajos as $f) { ?>

                                    <tr>
                                        <td class="col-codigo"><?php echo htmlspecialchars($f["codigo"]); ?></td>
                                        <td class="col-nombre"><?php echo htmlspecialchars($f["nombre"]); ?></td>
                                        <td class="col-stock"><?php echo $f["stock"]; ?></td>
                                        <td class="col-stock"><?php echo $f["stock_minimo"]; ?></td>
                                        <td>
                                            <?php if ($f["stock"] == 0) { ?>
                                                <span class="badge badge-bajo">Sin stock</span>
                                            <?php } else { ?>
                                                <span class="badge badge-aviso">Stock bajo</span>
                                            <?php } ?>
                                        </td>
                                    </tr>

                                <?php } ?>

                            </tbody>

                        </table>

                    </div>

                <?php } ?>

            </section>

        <?php } ?>


        <!-- ==================================================
             REPORTE: PAGARÉS PENDIENTES
        =================================================== -->

        <?php if ($r === "pagares") { ?>

            <section class="cards">

                <div class="card">
                    <div class="card-title">Pagarés pendientes</div>
                    <div class="card-value"><?php echo count($pagares); ?></div>
                </div>

                <div class="card">
                    <div class="card-title">Monto pendiente</div>
                    <div class="card-value"><?php echo dinero($monto_total); ?></div>
                </div>

                <div class="card">
                    <div class="card-title">Vencidos</div>
                    <div class="card-value"><?php echo $cant_venc; ?></div>
                </div>

                <div class="card">
                    <div class="card-title">Monto vencido</div>
                    <div class="card-value"><?php echo dinero($monto_venc); ?></div>
                </div>

            </section>


            <section class="content-box">

                <h3>Pagarés por cobrar</h3>

                <?php if (count($pagares) === 0) { ?>

                    <p class="sin-datos">No hay pagarés pendientes de cobro.</p>

                <?php } else { ?>

                    <div class="tabla-contenedor">

                        <table class="tabla">

                            <thead>
                                <tr>
                                    <th class="col-num">Venta</th>
                                    <th>Cliente</th>
                                    <th class="col-precio">Monto</th>
                                    <th>Vencimiento</th>
                                    <th>Estado</th>
                                    <th class="col-acciones no-imprimir">Acciones</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php foreach ($pagares as $f) {

                                    $dias = null;

                                    if ($f["fecha_vencimiento"]) {
                                        $dias = (int) round(
                                            (strtotime($f["fecha_vencimiento"]) - strtotime($hoy)) / 86400
                                        );
                                    }
                                ?>

                                    <tr>
                                        <td class="col-num"><?php echo $f["venta_id"]; ?></td>

                                        <td class="col-nombre">
                                            <?php echo htmlspecialchars($f["cliente"] ?? "Consumidor final"); ?>
                                        </td>

                                        <td class="col-precio"><?php echo dinero($f["monto"]); ?></td>

                                        <td>
                                            <?php echo $f["fecha_vencimiento"] ? fecha_corta($f["fecha_vencimiento"]) : "-"; ?>
                                        </td>

                                        <td>
                                            <?php if ($dias === null) { ?>
                                                <span class="badge badge-aviso">Sin fecha</span>
                                            <?php } elseif ($dias < 0) { ?>
                                                <span class="badge badge-bajo">Vencido hace <?php echo abs($dias); ?> d</span>
                                            <?php } elseif ($dias === 0) { ?>
                                                <span class="badge badge-aviso">Vence hoy</span>
                                            <?php } else { ?>
                                                <span class="badge badge-ok">Vence en <?php echo $dias; ?> d</span>
                                            <?php } ?>
                                        </td>

                                        <td class="col-acciones no-imprimir">
                                            <a class="btn-accion btn-editar"
                                               href="../ventas/ver.php?id=<?php echo $f['venta_id']; ?>">Ver venta</a>
                                        </td>
                                    </tr>

                                <?php } ?>

                            </tbody>

                        </table>

                    </div>

                <?php } ?>

            </section>

        <?php } ?>


        <!-- IMPRIMIR -->

        <p class="no-imprimir" style="margin-top: 20px;">
            <button type="button" onclick="window.print();">🖨️ descargar reporte</button>
        </p>

    </main>

</body>

</html>
