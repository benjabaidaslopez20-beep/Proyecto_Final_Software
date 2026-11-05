<?php

require_once "../config/auth.php";
require_once "../config/conexion.php";

$id = (int) ($_GET["id"] ?? $_POST["id"] ?? 0);

$consulta = $conexion->prepare("SELECT * FROM facturas WHERE id = ?");
$consulta->bind_param("i", $id);
$consulta->execute();

$factura = $consulta->get_result()->fetch_assoc();

if (!$factura) {

    header("Location: index.php");
    exit;
}

$error = "";


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $motivo = trim($_POST["motivo"] ?? "");

    if ($factura["estado"] === "ANULADA") {

        header("Location: ver.php?id=" . $id);
        exit;
    }

    if ($motivo === "") {

        $error = "Indicá el motivo de la anulación.";

    } else {

        $actualizar = $conexion->prepare(
            "UPDATE facturas
             SET estado = 'ANULADA', fecha_anulacion = NOW(), motivo_anulacion = ?
             WHERE id = ?"
        );

        $actualizar->bind_param("si", $motivo, $id);

        if ($actualizar->execute()) {

            header("Location: ver.php?id=" . $id);
            exit;

        } else {

            $error = "No se pudo anular la factura.";
        }
    }
}


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

    <title>Anular factura - Sistema de Gestión</title>

    <link rel="stylesheet" href="../css/estilos.css">

    <link rel="stylesheet" href="../css/formularios.css?v=1">

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
                Anular factura
            </h2>

            <p>
                <?php echo formato_numero($factura['tipo'], $factura['punto_venta'], $factura['numero']); ?>
                — <?php echo htmlspecialchars($factura["cliente_nombre"]); ?>
            </p>

        </header>


        <div class="content-box" style="max-width: 500px;">

            <?php if ($error !== "") { ?>

                <div class="alerta-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>

            <?php } ?>


            <?php if ($factura["estado"] === "ANULADA") { ?>

                <p>Esta factura ya está anulada.</p>

                <div class="formulario-botones">
                    <a href="ver.php?id=<?php echo $id; ?>" class="btn-cancelar">
                        Volver a la factura
                    </a>
                </div>

            <?php } else { ?>

                <form method="POST" class="formulario">

                    <div class="campo campo-completo">
                        <label>Motivo de la anulación *</label>
                        <textarea name="motivo" required><?php echo htmlspecialchars($_POST["motivo"] ?? ""); ?></textarea>
                    </div>

                    <div class="formulario-botones">

                        <a href="ver.php?id=<?php echo $id; ?>" class="btn-cancelar">
                            Cancelar
                        </a>

                        <button type="submit" class="btn-guardar">
                            Confirmar anulación
                        </button>

                    </div>

                </form>

            <?php } ?>

        </div>


    </main>


</body>

</html>
