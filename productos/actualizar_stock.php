<?php

require_once "../config/auth.php";
require_once "../config/conexion.php";

// Hace que los errores de MySQL lancen excepciones (así podemos deshacer la transacción)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);


class ErrorStock extends Exception {}


// =====================================================
// GUARDAR LA ACTUALIZACIÓN (cuando se envía el formulario)
// =====================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $producto_id = intval($_POST["producto_id"] ?? 0);

    $modo = $_POST["modo"] ?? "";

    $cantidad_texto = trim($_POST["cantidad"] ?? "");

    $cantidad = intval($cantidad_texto);

    try {

        // ---------- Validaciones ----------

        if ($producto_id <= 0) {

            throw new ErrorStock("Elegí un producto de la lista.");
        }

        if (!in_array($modo, ["sumar", "restar", "fijar"], true)) {

            throw new ErrorStock("Elegí el tipo de actualización.");
        }

        if ($cantidad_texto === "" || $cantidad < 0 || ($modo !== "fijar" && $cantidad === 0)) {

            throw new ErrorStock("Ingresá una cantidad válida.");
        }


        $conexion->begin_transaction();


        // ---------- Producto (se bloquea mientras se actualiza) ----------

        $q = $conexion->prepare(
            "SELECT id, nombre, stock, stock_minimo
             FROM productos
             WHERE id = ? AND activo = 1
             FOR UPDATE"
        );

        $q->bind_param("i", $producto_id);
        $q->execute();

        $p = $q->get_result()->fetch_assoc();

        $q->close();

        if (!$p) {

            throw new ErrorStock("El producto no existe o está dado de baja.");
        }

        $anterior = (int) $p["stock"];
        $minimo   = (int) $p["stock_minimo"];


        // ---------- Stock nuevo ----------

        if ($modo === "sumar") {

            $nuevo = $anterior + $cantidad;

        } elseif ($modo === "restar") {

            $nuevo = $anterior - $cantidad;

        } else {

            $nuevo = $cantidad;
        }

        if ($nuevo < 0) {

            throw new ErrorStock(
                "No se pueden quitar " . $cantidad . " unidades: el stock actual es " . $anterior . "."
            );
        }

        $diferencia = $nuevo - $anterior;

        if ($diferencia === 0) {

            throw new ErrorStock("El stock ya es " . $anterior . ". No hay nada para actualizar.");
        }


        // ---------- Guardar el stock ----------

        $upd = $conexion->prepare("UPDATE productos SET stock = ? WHERE id = ?");
        $upd->bind_param("ii", $nuevo, $producto_id);
        $upd->execute();
        $upd->close();


        // ---------- Registrar el movimiento ----------

        $tipo = $diferencia > 0 ? "INGRESO" : "EGRESO";
        $cant_mov = abs($diferencia);
        $motivo = "Actualización de stock";

        $mov = $conexion->prepare(
            "INSERT INTO movimientos_stock (producto_id, tipo, cantidad, motivo)
             VALUES (?, ?, ?, ?)"
        );

        $mov->bind_param("isis", $producto_id, $tipo, $cant_mov, $motivo);
        $mov->execute();
        $mov->close();


        $conexion->commit();


        $mensaje = "Stock de \"" . $p["nombre"] . "\" actualizado: de " . $anterior . " a " . $nuevo . ".";

        if ($nuevo <= $minimo) {

            $mensaje .= " Atención: sigue en el límite mínimo (" . $minimo . ").";
        }

        $_SESSION["ok_stock"] = $mensaje;

        header("Location: actualizar_stock.php");
        exit;

    } catch (ErrorStock $e) {

        $conexion->rollback();

        $_SESSION["error_stock"] = $e->getMessage();

        header("Location: actualizar_stock.php" . ($producto_id > 0 ? "?id=" . $producto_id : ""));
        exit;

    } catch (Throwable $e) {

        $conexion->rollback();

        // Para ver el error real mientras probás, cambiá el texto por: $e->getMessage()
        $_SESSION["error_stock"] = "No se pudo actualizar el stock. Intentá nuevamente.";

        header("Location: actualizar_stock.php");
        exit;
    }
}


// =====================================================
// DATOS PARA MOSTRAR LA PÁGINA
// =====================================================

$ok    = $_SESSION["ok_stock"] ?? "";
$error = $_SESSION["error_stock"] ?? "";

unset($_SESSION["ok_stock"], $_SESSION["error_stock"]);


$productos = $conexion->query(
    "SELECT id, codigo, nombre, stock, stock_minimo
     FROM productos
     WHERE activo = 1
     ORDER BY nombre"
)->fetch_all(MYSQLI_ASSOC);

// Productos que llegaron al límite mínimo
$bajos = array_filter($productos, function ($p) {
    return (int) $p["stock"] <= (int) $p["stock_minimo"];
});

// Producto preseleccionado (viene de los botones "Actualizar" de la tabla)
$seleccionado = intval($_GET["id"] ?? 0);

$menu_activo = "productos";
$prefijo = "../";

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Actualizar productos - Sistema de Gestión</title>

    <link rel="stylesheet" href="../css/estilos.css">

    <link rel="stylesheet" href="../css/tablas.css?v=2">

    <link rel="stylesheet" href="../css/formularios.css?v=2">

    <link rel="stylesheet" href="../css/ventas.css?v=2">

</head>

<body>

    <?php require "../config/menu.php"; ?>

    <main class="main">

        <header class="header">

            <h2>Actualizar productos</h2>

            <p>
                Actualizá el stock de un producto. El cambio queda registrado en Movimientos.
            </p>

        </header>


        <?php if ($ok !== "") { ?>
            <div class="alerta-ok"><?php echo htmlspecialchars($ok); ?></div>
        <?php } ?>

        <?php if ($error !== "") { ?>
            <div class="alerta-error"><?php echo htmlspecialchars($error); ?></div>
        <?php } ?>


        <!-- ==========================================
             FORMULARIO
        =========================================== -->

        <section class="content-box" id="form-stock">

            <h3>Actualizar stock</h3>

            <?php if (count($productos) === 0) { ?>

                <p>Todavía no hay productos registrados.</p>

            <?php } else { ?>

                <form action="actualizar_stock.php" method="POST" class="formulario">

                    <div class="campo campo-completo">

                        <label>Producto</label>

                        <select name="producto_id" id="producto" class="select-campo" required>

                            <option value="">— Elegir producto —</option>

                            <?php foreach ($productos as $p) { ?>

                                <option
                                    value="<?php echo $p['id']; ?>"
                                    data-stock="<?php echo (int) $p['stock']; ?>"
                                    data-minimo="<?php echo (int) $p['stock_minimo']; ?>"
                                    <?php echo (int) $p['id'] === $seleccionado ? 'selected' : ''; ?>
                                >
                                    <?php echo (int) $p['stock'] <= (int) $p['stock_minimo'] ? '⚠ ' : ''; ?>
                                    <?php echo htmlspecialchars($p['codigo'] . ' · ' . $p['nombre']); ?>
                                    (stock <?php echo (int) $p['stock']; ?>)
                                </option>

                            <?php } ?>

                        </select>

                    </div>


                    <div class="campo">

                        <label>Tipo de actualización</label>

                        <select name="modo" id="modo" class="select-campo" required>
                            <option value="sumar">Agregar unidades (llegó mercadería)</option>
                            <option value="restar">Quitar unidades (pérdida, rotura)</option>
                            <option value="fijar">Establecer el stock total (conteo)</option>
                        </select>

                    </div>


                    <div class="campo">

                        <label>Cantidad</label>

                        <input type="number" name="cantidad" id="cantidad" min="0" step="1" required>

                    </div>


                    <div class="stock-preview" id="preview">
                        Elegí un producto para ver su stock actual.
                    </div>


                    <div class="formulario-botones">

                        <button type="submit" class="btn-guardar">Actualizar stock</button>

                        <a href="index.php" class="btn-cancelar">Volver a productos</a>

                    </div>

                </form>

            <?php } ?>

        </section>


        <!-- ==========================================
             PRODUCTOS EN EL LÍMITE MÍNIMO
        =========================================== -->

        <?php if (count($bajos) > 0) { ?>

            <section class="content-box" style="margin-top: 20px;">

                <h3>Productos en el límite mínimo</h3>

                <div class="tabla-contenedor">

                    <table class="tabla">

                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Producto</th>
                                <th class="col-num">Stock</th>
                                <th class="col-num">Mínimo</th>
                                <th class="col-acciones">Acciones</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php foreach ($bajos as $b) { ?>

                                <tr>
                                    <td class="col-codigo"><?php echo htmlspecialchars($b["codigo"]); ?></td>
                                    <td class="col-nombre"><?php echo htmlspecialchars($b["nombre"]); ?></td>
                                    <td class="col-num"><span class="badge badge-bajo"><?php echo (int) $b["stock"]; ?></span></td>
                                    <td class="col-num"><?php echo (int) $b["stock_minimo"]; ?></td>
                                    <td class="col-acciones">
                                        <a class="btn-accion btn-editar"
                                           href="actualizar_stock.php?id=<?php echo $b['id']; ?>#form-stock">Actualizar</a>
                                    </td>
                                </tr>

                            <?php } ?>

                        </tbody>

                    </table>

                </div>

            </section>

        <?php } ?>

    </main>


    <script>
    (function () {

        const producto = document.getElementById("producto");
        const modo = document.getElementById("modo");
        const cantidad = document.getElementById("cantidad");
        const preview = document.getElementById("preview");

        if (!producto) return;

        // Muestra el stock actual y cómo va a quedar después de actualizar
        function mostrar() {

            preview.className = "stock-preview";

            if (producto.value === "") {
                preview.textContent = "Elegí un producto para ver su stock actual.";
                return;
            }

            const opcion = producto.options[producto.selectedIndex];
            const actual = parseInt(opcion.getAttribute("data-stock"), 10);
            const minimo = parseInt(opcion.getAttribute("data-minimo"), 10);
            const c = parseInt(cantidad.value, 10);

            if (isNaN(c)) {
                preview.innerHTML = "Stock actual: <strong>" + actual + "</strong> (mínimo " + minimo + ")";
                return;
            }

            let nuevo;

            if (modo.value === "sumar") {
                nuevo = actual + c;
            } else if (modo.value === "restar") {
                nuevo = actual - c;
            } else {
                nuevo = c;
            }

            if (nuevo < 0) {
                preview.className = "stock-preview alerta";
                preview.innerHTML = "No se pueden quitar " + c + " unidades: el stock actual es <strong>" + actual + "</strong>.";
                return;
            }

            let texto = "Stock actual: <strong>" + actual + "</strong> &rarr; quedará en <strong>" + nuevo + "</strong>";

            if (nuevo <= minimo) {
                preview.className = "stock-preview alerta";
                texto += " &middot; seguirá en el límite mínimo (" + minimo + ")";
            }

            preview.innerHTML = texto;
        }

        producto.onchange = mostrar;
        modo.onchange = mostrar;
        cantidad.oninput = mostrar;

        mostrar();

    })();
    </script>

</body>

</html>