<?php

require_once "../config/auth.php";
require_once "../config/conexion.php";

// Hace que los errores de MySQL lancen excepciones (así podemos deshacer la transacción)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);


class ErrorVenta extends Exception {}


function volver_con_error($mensaje)
{
    $_SESSION["error_venta"] = $mensaje;

    header("Location: nueva.php");
    exit;
}


if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: index.php");
    exit;
}


// =====================================================
// RECIBIR Y VALIDAR DATOS
// =====================================================

$cliente_id = intval($_POST["cliente_id"] ?? 0);

$metodo = $_POST["metodo_pago"] ?? "";

$metodos_validos = ["EFECTIVO", "TRANSFERENCIA", "TARJETA", "MERCADO_PAGO", "PAGARE"];

if (!in_array($metodo, $metodos_validos, true)) {

    volver_con_error("Método de pago inválido.");
}

$descuento = round(max(0, floatval($_POST["descuento"] ?? 0)), 2);

// Pagaré: requiere fecha de vencimiento
$fecha_venc = null;

if ($metodo === "PAGARE") {

    $fecha_venc = $_POST["fecha_vencimiento"] ?? "";

    $d = DateTime::createFromFormat("Y-m-d", $fecha_venc);

    if (!$d || $d->format("Y-m-d") !== $fecha_venc) {

        volver_con_error("Ingresá una fecha de vencimiento válida para el pagaré.");
    }
}

// Productos: se agrupan por si se eligió el mismo más de una vez
$ids   = $_POST["producto_id"] ?? [];
$cants = $_POST["cantidad"] ?? [];

$items = [];

foreach ($ids as $i => $pid) {

    $pid = intval($pid);
    $cant = intval($cants[$i] ?? 0);

    if ($pid > 0 && $cant > 0) {

        $items[$pid] = ($items[$pid] ?? 0) + $cant;
    }
}

if (count($items) === 0) {

    volver_con_error("Agregá al menos un producto.");
}


// =====================================================
// REGISTRAR LA VENTA (todo o nada)
// =====================================================

try {

    $conexion->begin_transaction();


    // ---------- Cliente (0 = consumidor final) ----------

    $cliente = null;

    if ($cliente_id > 0) {

        $c = $conexion->prepare("SELECT id FROM clientes WHERE id = ?");
        $c->bind_param("i", $cliente_id);
        $c->execute();

        if ($c->get_result()->num_rows === 0) {

            throw new ErrorVenta("El cliente seleccionado no existe.");
        }

        $c->close();

        $cliente = $cliente_id;
    }


    // ---------- Productos: precios y stock desde la base ----------
    // (nunca se confía en el precio que manda el navegador)

    $sel = $conexion->prepare(
        "SELECT id, nombre, precio_venta, stock
         FROM productos
         WHERE id = ? AND activo = 1
         FOR UPDATE"
    );

    $subtotal = 0;
    $detalle = [];

    foreach ($items as $pid => $cant) {

        $sel->bind_param("i", $pid);
        $sel->execute();

        $p = $sel->get_result()->fetch_assoc();

        if (!$p) {

            throw new ErrorVenta("Uno de los productos ya no está disponible.");
        }

        if ($p["stock"] < $cant) {

            throw new ErrorVenta(
                "Stock insuficiente de \"" . $p["nombre"] . "\" (disponible: " . $p["stock"] . ")."
            );
        }

        $precio = (float) $p["precio_venta"];
        $linea  = round($precio * $cant, 2);

        $subtotal += $linea;

        $detalle[] = [
            "producto_id" => $pid,
            "cantidad"    => $cant,
            "precio"      => $precio,
            "subtotal"    => $linea,
        ];
    }

    $sel->close();

    $subtotal = round($subtotal, 2);

    if ($descuento > $subtotal) {

        throw new ErrorVenta("El descuento no puede ser mayor al subtotal.");
    }

    // IVA: se calcula sobre el subtotal menos el descuento.
    // Total = subtotal (neto) + IVA.
    $neto = round($subtotal - $descuento, 2);

    $cfg = $conexion
        ->query("SELECT iva_porcentaje FROM configuracion_empresa WHERE id = 1")
        ->fetch_assoc();

    $iva_pct = $cfg ? (float) $cfg["iva_porcentaje"] : 21.0;

    $impuestos = round($neto * $iva_pct / 100, 2);

    $total = round($neto + $impuestos, 2);


    // ---------- Venta ----------

    $ins = $conexion->prepare(
        "INSERT INTO ventas (cliente_id, subtotal, descuento, impuestos, total, estado)
         VALUES (?, ?, ?, ?, ?, 'COMPLETADA')"
    );

    $ins->bind_param("idddd", $cliente, $subtotal, $descuento, $impuestos, $total);
    $ins->execute();

    $venta_id = $conexion->insert_id;

    $ins->close();


    // ---------- Detalle, stock y movimientos ----------

    $ins_det = $conexion->prepare(
        "INSERT INTO detalle_ventas (venta_id, producto_id, cantidad, precio_unitario, subtotal)
         VALUES (?, ?, ?, ?, ?)"
    );

    $upd_stock = $conexion->prepare(
        "UPDATE productos SET stock = stock - ? WHERE id = ?"
    );

    $ins_mov = $conexion->prepare(
        "INSERT INTO movimientos_stock (producto_id, tipo, cantidad, motivo)
         VALUES (?, 'EGRESO', ?, ?)"
    );

    $motivo = "Venta #" . $venta_id;

    foreach ($detalle as $d) {

        $ins_det->bind_param("iiidd", $venta_id, $d["producto_id"], $d["cantidad"], $d["precio"], $d["subtotal"]);
        $ins_det->execute();

        $upd_stock->bind_param("ii", $d["cantidad"], $d["producto_id"]);
        $upd_stock->execute();

        $ins_mov->bind_param("iis", $d["producto_id"], $d["cantidad"], $motivo);
        $ins_mov->execute();
    }

    $ins_det->close();
    $upd_stock->close();
    $ins_mov->close();


    // ---------- Pago ----------
    // Pagaré: queda pendiente hasta que se cobre. El resto se paga en el acto.

    $estado_pago = ($metodo === "PAGARE") ? "PENDIENTE" : "PAGADO";

    $ins_pago = $conexion->prepare(
        "INSERT INTO pagos (venta_id, metodo_pago, monto, estado, fecha_vencimiento)
         VALUES (?, ?, ?, ?, ?)"
    );

    $ins_pago->bind_param("isdss", $venta_id, $metodo, $total, $estado_pago, $fecha_venc);
    $ins_pago->execute();

    $ins_pago->close();


    $conexion->commit();

    header("Location: ver.php?id=" . $venta_id . "&ok=creada");
    exit;

} catch (ErrorVenta $e) {

    $conexion->rollback();

    volver_con_error($e->getMessage());

} catch (Throwable $e) {

    $conexion->rollback();

    volver_con_error("No se pudo registrar la venta. Intentá nuevamente.");
}

?>