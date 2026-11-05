<?php

require_once "../config/auth.php";
require_once "../config/conexion.php";


// =====================================================
// BUSCADOR (por número de factura o por nombre de cliente)
// =====================================================

$busqueda = trim($_GET["q"] ?? "");

$sql = "SELECT id, tipo, punto_venta, numero, fecha_emision,
               cliente_nombre, total, estado
        FROM facturas";

$tipos      = "";
$parametros = [];

if ($busqueda !== "") {

    $sql .= " WHERE cliente_nombre LIKE ? OR numero LIKE ?";

    $parametros[] = "%" . $busqueda . "%";
    $parametros[] = "%" . preg_replace("/\D/", "", $busqueda) . "%";

    $tipos = "ss";
}

$sql .= " ORDER BY fecha_emision DESC";

$consulta = $conexion->prepare($sql);

if ($tipos !== "") {
    $consulta->bind_param($tipos, ...$parametros);
}

$consulta->execute();

$facturas = $consulta->get_result();

$total_facturas = $facturas->num_rows;


// Número formateado: B 0001-00000023
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

    <title>Facturación - Sistema de Gestión</title>

    <link rel="stylesheet" href="../css/estilos.css">

    <link rel="stylesheet" href="../css/formularios.css?v=1">

    <link rel="stylesheet" href="../css/tablas.css?v=1">

</head>


<body>


    <!-- ==========================================
         BARRA SUPERIOR / MENÚ
    =========================================== -->

    <?php
        $menu_activo = "facturacion";
        $prefijo = "../";
        require "../config/menu.php";
    ?>



    <!-- ==========================================
         CONTENIDO PRINCIPAL
    =========================================== -->

    <main class="main">


        <header class="header">

            <h2>
                Facturación
            </h2>

            <p>
                Facturas emitidas y pendientes de generar.
            </p>

        </header>



        <!-- ==========================================
             BARRA: buscador + botón nueva factura
        =========================================== -->

        <div class="content-box">

            <div class="barra-acciones">

                <h3>
                    Facturas
                </h3>

                <a href="generar.php" class="btn-secundario">
                    🧾 Nueva factura
                </a>

            </div>


            <form method="GET" class="formulario" style="margin-bottom: 20px;">

                <div class="campo campo-completo">
                    <label>Buscar por cliente o número</label>
                    <input
                        type="text"
                        name="q"
                        value="<?php echo htmlspecialchars($busqueda); ?>"
                        placeholder="Nombre del cliente o número de factura..."
                    >
                </div>

                <div class="formulario-botones">

                    <button type="submit" class="btn-guardar">
                        🔍 Buscar
                    </button>

                    <?php if ($busqueda !== "") { ?>

                        <a href="index.php" class="btn-cancelar">
                            Limpiar
                        </a>

                    <?php } ?>

                </div>

            </form>


            <!-- ==========================================
                 TABLA
            =========================================== -->

            <div class="tabla-contenedor">

                <table class="tabla">

                    <thead>

                        <tr>
                            <th>Número</th>
                            <th>Fecha</th>
                            <th>Cliente</th>
                            <th class="col-precio">Total</th>
                            <th>Estado</th>
                            <th class="col-acciones">Acciones</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php if ($total_facturas == 0) { ?>

                            <tr>
                                <td colspan="6" style="text-align:center; padding: 30px;">
                                    <?php echo $busqueda !== ""
                                        ? "No se encontraron facturas con esa búsqueda."
                                        : "Todavía no hay facturas emitidas."; ?>
                                </td>
                            </tr>

                        <?php } ?>


                        <?php while ($f = $facturas->fetch_assoc()) { ?>

                            <tr>

                                <td class="col-codigo">
                                    <?php echo formato_numero($f["tipo"], $f["punto_venta"], $f["numero"]); ?>
                                </td>

                                <td>
                                    <?php echo date("d/m/Y H:i", strtotime($f["fecha_emision"])); ?>
                                </td>

                                <td class="col-nombre">
                                    <?php echo htmlspecialchars($f["cliente_nombre"]); ?>
                                </td>

                                <td class="col-precio">
                                    $<?php echo number_format($f["total"], 2, ",", "."); ?>
                                </td>

                                <td>

                                    <?php if ($f["estado"] === "EMITIDA") { ?>

                                        <span class="badge badge-ok">Emitida</span>

                                    <?php } else { ?>

                                        <span class="badge badge-bajo">Anulada</span>

                                    <?php } ?>

                                </td>

                                <td class="col-acciones">

                                    <a href="ver.php?id=<?php echo $f['id']; ?>" class="btn-accion btn-editar">
                                        Ver
                                    </a>

                                    <?php if ($f["estado"] === "EMITIDA") { ?>

                                        <a href="anular.php?id=<?php echo $f['id']; ?>" class="btn-accion btn-eliminar">
                                            Anular
                                        </a>

                                    <?php } ?>

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
