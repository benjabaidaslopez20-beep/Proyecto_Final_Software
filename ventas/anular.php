<?php

require_once "../config/auth.php";
require_once "../config/conexion.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);


class ErrorAnulacion extends Exception {}


if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: index.php");
    exit;
}

$id = intval($_POST["id"] ?? 0);


try {

    $conexion->begin_transaction();


    // ---------- Venta ----------

    $q = $conexion->prepare("SELECT estado FROM ventas WHERE id = ? FOR UPDATE");
    $q->bind_param("i", $id);
    $q->execute();

    $venta = $q->get_result()->fetch_assoc();

    $q->close();

    if (!$venta) {

        throw new ErrorAnulacion("La venta no existe.");
    }

    if ($venta["estado"] === "ANULADA") {

        throw new ErrorAnulacion("La venta ya estaba anulada.");
    }


    // ---------- No anular si tiene una factura emitida ----------

    $f = $conexion->prepare(
        "SELECT id FROM facturas WHERE venta_id = ? AND estado = 'EMITIDA'"
    );

    $f->bind_param("i", $id);
    $f->execute();

    if ($f->get_result()->num_rows > 0) {

        throw new ErrorAnulacion("La venta tiene una factura emitida. Anulá primero la factura.");
    }

    $f->close();


    // ---------- Devolver el stock ----------

    $det = $conexion->prepare(
        "SELECT producto_id, cantidad FROM detalle_ventas WHERE venta_id = ?"
    );

    $det->bind_param("i", $id);
    $det->execute();

    $lineas = $det->get_result()->fetch_all(MYSQLI_ASSOC);

    $det->close();

    $upd = $conexion->prepare("UPDATE productos SET stock = stock + ? WHERE id = ?");

    $mov = $conexion->prepare(
        "INSERT INTO movimientos_stock (producto_id, tipo, cantidad, motivo)
         VALUES (?, 'INGRESO', ?, ?)"
    );

    $motivo = "Anulación de venta #" . $id;

    foreach ($lineas as $l) {

        $upd->bind_param("ii", $l["cantidad"], $l["producto_id"]);
        $upd->execute();

        $mov->bind_param("iis", $l["producto_id"], $l["cantidad"], $motivo);
        $mov->execute();
    }

    $upd->close();
    $mov->close();


    // ---------- Marcar como anulada ----------

    $a = $conexion->prepare("UPDATE ventas SET estado = 'ANULADA' WHERE id = ?");
    $a->bind_param("i", $id);
    $a->execute();
    $a->close();


    $conexion->commit();

    header("Location: ver.php?id=" . $id . "&ok=anulada");
    exit;

} catch (ErrorAnulacion $e) {

    $conexion->rollback();

    $_SESSION["error_venta"] = $e->getMessage();

    header("Location: ver.php?id=" . $id);
    exit;

} catch (Throwable $e) {

    $conexion->rollback();

    $_SESSION["error_venta"] = "No se pudo anular la venta.";

    header("Location: ver.php?id=" . $id);
    exit;
}

?>
