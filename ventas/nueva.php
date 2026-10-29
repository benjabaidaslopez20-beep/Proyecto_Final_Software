<?php

require_once "../config/auth.php";
require_once "../config/conexion.php";
require_once "../config/helpers.php";


// Mensaje de error (si guardar.php rechazó la venta)
$error = $_SESSION["error_venta"] ?? "";
unset($_SESSION["error_venta"]);


// =====================================================
// PRODUCTOS DISPONIBLES (activos y con stock)
// =====================================================

$productos = [];

$r = $conexion->query("
    SELECT id, codigo, nombre, precio_venta, stock
    FROM productos
    WHERE activo = 1 AND stock > 0
    ORDER BY nombre
");

while ($f = $r->fetch_assoc()) {

    $productos[] = [
        "id"     => (int) $f["id"],
        "codigo" => $f["codigo"],
        "nombre" => $f["nombre"],
        "precio" => (float) $f["precio_venta"],
        "stock"  => (int) $f["stock"],
    ];
}


// =====================================================
// CLIENTES
// =====================================================

$clientes = $conexion->query("SELECT id, nombre FROM clientes ORDER BY nombre");

$menu_activo = "ventas";
$prefijo = "../";

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Nueva venta - Sistema de Gestión</title>

    <link rel="stylesheet" href="../css/estilos.css">

    <link rel="stylesheet" href="../css/tablas.css?v=1">

    <link rel="stylesheet" href="../css/formularios.css?v=1">

    <link rel="stylesheet" href="../css/ventas.css?v=1">

</head>

<body>

    <?php require "../config/menu.php"; ?>

    <main class="main">

        <header class="header">

            <h2>Nueva venta</h2>

            <p>
                Elegí los productos y registrá el pago.
            </p>

        </header>


        <?php if ($error !== "") { ?>

            <div class="alerta-error">
                <?php echo htmlspecialchars($error); ?>
            </div>

        <?php } ?>


        <?php if (count($productos) === 0) { ?>

            <section class="content-box">

                <p>No hay productos con stock disponible para vender.</p>

            </section>

        <?php } else { ?>

        <form action="guardar.php" method="POST" id="form-venta">

            <!-- DATOS -->

            <section class="content-box">

                <h3>Datos de la venta</h3>

                <div class="formulario">

                    <div class="campo">
                        <label>Cliente</label>

                        <select name="cliente_id" class="select-campo">
                            <option value="0">Consumidor final</option>

                            <?php while ($c = $clientes->fetch_assoc()) { ?>
                                <option value="<?php echo $c['id']; ?>">
                                    <?php echo htmlspecialchars($c["nombre"]); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="campo">
                        <label>Método de pago</label>

                        <select name="metodo_pago" id="metodo" class="select-campo">
                            <option value="EFECTIVO">Efectivo</option>
                            <option value="TRANSFERENCIA">Transferencia</option>
                            <option value="TARJETA">Tarjeta</option>
                            <option value="MERCADO_PAGO">Mercado Pago</option>
                            <option value="PAGARE">Pagaré</option>
                        </select>
                    </div>

                    <div class="campo" id="campo-venc" style="display: none;">
                        <label>Vencimiento del pagaré</label>
                        <input type="date" name="fecha_vencimiento" id="fecha-venc">
                    </div>

                </div>

            </section>


            <!-- PRODUCTOS -->

            <section class="content-box" style="margin-top: 20px;">

                <h3>Productos</h3>

                <div class="tabla-contenedor">

                    <table class="tabla items-tabla">

                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Cantidad</th>
                                <th class="col-precio">Precio</th>
                                <th class="col-precio">Subtotal</th>
                                <th></th>
                            </tr>
                        </thead>

                        <tbody id="filas"></tbody>

                    </table>

                </div>

                <button type="button" id="btn-agregar" class="btn-secundario">
                    + Agregar producto
                </button>

            </section>


            <!-- TOTALES -->

            <section class="content-box" style="margin-top: 20px;">

                <div class="totales">

                    <div class="totales-fila">
                        <span>Subtotal</span>
                        <strong id="t-subtotal">$0,00</strong>
                    </div>

                    <div class="totales-fila">
                        <span>Descuento ($)</span>
                        <input type="number" name="descuento" id="descuento" min="0" step="0.01" value="0">
                    </div>

                    <div class="totales-fila totales-total">
                        <span>Total</span>
                        <span id="t-total">$0,00</span>
                    </div>

                </div>

                <div class="venta-botones">

                    <a href="index.php" class="btn-cancelar">Cancelar</a>

                    <button type="submit" class="btn-guardar">Registrar venta</button>

                </div>

            </section>

        </form>

        <?php } ?>

    </main>


    <script>
    (function () {

        const productos = <?php echo json_encode($productos, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

        const porId = {};
        productos.forEach(function (p) { porId[p.id] = p; });

        const filas = document.getElementById("filas");
        const descuento = document.getElementById("descuento");
        const form = document.getElementById("form-venta");

        if (!filas) return;

        function dinero(n) {
            return "$" + n.toLocaleString("es-AR", {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function celda(clase) {
            const td = document.createElement("td");
            if (clase) td.className = clase;
            return td;
        }

        function agregarFila() {

            const tr = document.createElement("tr");

            // Producto
            const select = document.createElement("select");
            select.name = "producto_id[]";
            select.className = "select-campo";
            select.required = true;
            select.add(new Option("— Elegir producto —", ""));
            productos.forEach(function (p) {
                select.add(new Option(p.codigo + " · " + p.nombre + " (stock " + p.stock + ")", p.id));
            });

            // Cantidad
            const cant = document.createElement("input");
            cant.type = "number";
            cant.name = "cantidad[]";
            cant.className = "input-cant";
            cant.min = 1;
            cant.value = 1;
            cant.required = true;

            // Quitar
            const quitar = document.createElement("button");
            quitar.type = "button";
            quitar.className = "btn-quitar";
            quitar.textContent = "Quitar";
            quitar.onclick = function () {
                tr.remove();
                recalcular();
            };

            const tdProd = celda();   tdProd.appendChild(select);
            const tdCant = celda();   tdCant.appendChild(cant);
            const tdPrecio = celda("col-precio");  tdPrecio.textContent = "-";
            const tdSub = celda("col-precio");     tdSub.textContent = "-";
            const tdQuitar = celda("col-acciones"); tdQuitar.appendChild(quitar);

            tr.append(tdProd, tdCant, tdPrecio, tdSub, tdQuitar);

            tr._refs = { select: select, cant: cant, precio: tdPrecio, sub: tdSub };

            select.onchange = recalcular;
            cant.oninput = recalcular;

            filas.appendChild(tr);
        }

        function recalcular() {

            let subtotal = 0;

            filas.querySelectorAll("tr").forEach(function (tr) {

                const r = tr._refs;
                const p = porId[r.select.value];

                if (!p) {
                    r.precio.textContent = "-";
                    r.sub.textContent = "-";
                    return;
                }

                r.cant.max = p.stock;

                let c = parseInt(r.cant.value, 10) || 0;
                if (c > p.stock) { c = p.stock; r.cant.value = c; }

                const linea = p.precio * c;
                subtotal += linea;

                r.precio.textContent = dinero(p.precio);
                r.sub.textContent = dinero(linea);
            });

            let desc = parseFloat(descuento.value) || 0;
            if (desc < 0) desc = 0;
            if (desc > subtotal) desc = subtotal;

            document.getElementById("t-subtotal").textContent = dinero(subtotal);
            document.getElementById("t-total").textContent = dinero(subtotal - desc);
        }

        // Pagaré: pedir fecha de vencimiento
        const metodo = document.getElementById("metodo");
        const campoVenc = document.getElementById("campo-venc");
        const fechaVenc = document.getElementById("fecha-venc");

        metodo.onchange = function () {
            const esPagare = metodo.value === "PAGARE";
            campoVenc.style.display = esPagare ? "flex" : "none";
            fechaVenc.required = esPagare;
        };

        document.getElementById("btn-agregar").onclick = agregarFila;
        descuento.oninput = recalcular;

        form.onsubmit = function (e) {
            const hayProducto = Array.from(filas.querySelectorAll("select"))
                .some(function (s) { return s.value !== ""; });

            if (!hayProducto) {
                e.preventDefault();
                alert("Agregá al menos un producto.");
            }
        };

        agregarFila();

    })();
    </script>

</body>

</html>
