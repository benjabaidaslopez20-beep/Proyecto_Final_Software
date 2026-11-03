<?php

require_once "../config/auth.php";
require_once "../config/conexion.php";


// =====================================================
// AGREGAR CLIENTE (formulario de la ventana emergente)
// =====================================================

$error = "";

$datos = [
    "nombre"    => "",
    "documento" => "",
    "telefono"  => "",
    "email"     => "",
    "cuit"      => "",
    "direccion" => ""
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    foreach ($datos as $campo => $valor) {
        $datos[$campo] = trim($_POST[$campo] ?? "");
    }

    // El DNI se guarda solo con números (sin puntos ni espacios)
    $datos["documento"] = preg_replace("/\D/", "", $datos["documento"]);


    if ($datos["nombre"] === "") {

        $error = "El nombre y apellido es obligatorio.";

    } elseif ($datos["email"] !== "" && !filter_var($datos["email"], FILTER_VALIDATE_EMAIL)) {

        $error = "El email no tiene un formato válido.";

    } else {

        // Si cargaron DNI, verificamos que no exista ya
        $existe = false;

        if ($datos["documento"] !== "") {

            $verificar = $conexion->prepare(
                "SELECT id FROM clientes WHERE documento = ? LIMIT 1"
            );

            $verificar->bind_param("s", $datos["documento"]);
            $verificar->execute();

            $existe = $verificar->get_result()->num_rows > 0;

            $verificar->close();
        }

        if ($existe) {

            $error = "Ya existe un cliente con ese DNI.";

        } else {

            // Los campos vacíos se guardan como NULL
            $nombre    = $datos["nombre"];
            $documento = $datos["documento"] !== "" ? $datos["documento"] : null;
            $telefono  = $datos["telefono"]  !== "" ? $datos["telefono"]  : null;
            $email     = $datos["email"]     !== "" ? $datos["email"]     : null;
            $cuit      = $datos["cuit"]      !== "" ? $datos["cuit"]      : null;
            $direccion = $datos["direccion"] !== "" ? $datos["direccion"] : null;

            $insertar = $conexion->prepare(
                "INSERT INTO clientes (nombre, documento, telefono, email, cuit, direccion)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );

            $insertar->bind_param(
                "ssssss",
                $nombre, $documento, $telefono, $email, $cuit, $direccion
            );

            if ($insertar->execute()) {

                header("Location: index.php?ok=1");
                exit;

            } else {

                $error = "No se pudo guardar el cliente.";
            }
        }
    }
}


// =====================================================
// BUSCADOR (por DNI o por nombre y apellido)
// Cada palabra escrita debe aparecer en el nombre o en el DNI,
// así "Juan Pérez" y "Pérez Juan" encuentran al mismo cliente.
// =====================================================

$busqueda = trim($_GET["q"] ?? "");

$sql = "SELECT id, nombre, documento, telefono, email, direccion, cuit, fecha_creacion
        FROM clientes";

$tipos      = "";
$parametros = [];

if ($busqueda !== "") {

    $condiciones = [];

    foreach (preg_split("/\s+/", $busqueda) as $palabra) {

        $solo_numeros = preg_replace("/\D/", "", $palabra);

        $condiciones[] = "(nombre LIKE ? OR documento LIKE ?)";

        $parametros[] = "%" . $palabra . "%";
        $parametros[] = "%" . ($solo_numeros !== "" ? $solo_numeros : $palabra) . "%";

        $tipos .= "ss";
    }

    $sql .= " WHERE " . implode(" AND ", $condiciones);
}

$sql .= " ORDER BY nombre ASC";

$consulta = $conexion->prepare($sql);

if ($tipos !== "") {
    $consulta->bind_param($tipos, ...$parametros);
}

$consulta->execute();

$clientes = $consulta->get_result();

$total_clientes = $clientes->num_rows;


// Función chica para mostrar "-" cuando un dato está vacío
function mostrar($valor)
{
    return ($valor === null || $valor === "")
        ? "-"
        : htmlspecialchars($valor);
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Clientes - Sistema de Gestión</title>

    <link rel="stylesheet" href="../css/estilos.css">

    <link rel="stylesheet" href="../css/formularios.css?v=1">

    <link rel="stylesheet" href="../css/clientes.css?v=1">

</head>


<body>


    <!-- ==========================================
         BARRA LATERAL
    =========================================== -->

    <aside class="sidebar">

        <div class="logo">

            <h1>📦 Mi Gestión</h1>

            <p>
                Sistema de gestión
            </p>

        </div>


        <ul class="menu">

            <li>
                <a href="../index.php">
                    🏠 Inicio
                </a>
            </li>

            <li>
                <a href="../productos/index.php">
                    📦 Productos
                </a>
            </li>

            <li>
                <a href="../movimientos/index.php">
                    ↕️ Movimientos
                </a>
            </li>

            <li>
                <a href="#">
                    🛒 Ventas
                </a>
            </li>

            <li>
                <a href="#">
                    🧾 Facturación
                </a>
            </li>

            <li>
                <a href="index.php" class="active">
                    👥 Clientes
                </a>
            </li>

            <li>
                <a href="#">
                    📊 Reportes
                </a>
            </li>

            <?php if (es_admin()) { ?>

                <li>
                    <a href="../usuarios/index.php">
                        👤 Usuarios
                    </a>
                </li>

            <?php } ?>

            <li>
                <a href="../logout.php">
                    🚪 Cerrar sesión
                </a>
            </li>

        </ul>

    </aside>



    <!-- ==========================================
         CONTENIDO PRINCIPAL
    =========================================== -->

    <main class="main">


        <!-- ENCABEZADO -->

        <header class="header">

            <h2>
                Clientes
            </h2>

            <p>
                Buscá, consultá y agregá clientes.
            </p>

        </header>


        <?php if (isset($_GET["ok"])) { ?>

            <div class="cli-mensaje cli-mensaje-ok">
                Cliente agregado correctamente.
            </div>

        <?php } ?>



        <!-- ==========================================
             BARRA DE BÚSQUEDA + BOTÓN AGREGAR
        =========================================== -->

        <section class="cli-barra">

            <form method="GET" class="cli-buscador">

                <input
                    type="text"
                    name="q"
                    value="<?php echo htmlspecialchars($busqueda); ?>"
                    placeholder="Buscar por DNI o por nombre y apellido..."
                    autocomplete="off"
                >

                <button type="submit" class="cli-btn-buscar">
                    🔍 Buscar
                </button>

                <?php if ($busqueda !== "") { ?>

                    <a href="index.php" class="cli-btn-limpiar">
                        Limpiar
                    </a>

                <?php } ?>

            </form>

            <button type="button" class="cli-btn-nuevo" id="btnNuevoCliente">
                ➕ Nuevo cliente
            </button>

        </section>


        <p class="cli-contador">
            <?php echo $total_clientes; ?>
            <?php echo $total_clientes == 1 ? "cliente" : "clientes"; ?>
            <?php echo $busqueda !== "" ? "encontrado(s)" : ""; ?>
        </p>



        <!-- ==========================================
             TABLA DE CLIENTES
        =========================================== -->

        <section class="cli-tabla-caja">

            <table class="cli-tabla">

                <thead>

                    <tr>
                        <th>Nombre y apellido</th>
                        <th>DNI</th>
                        <th>Contacto</th>
                        <th>Email</th>
                        <th>Dirección</th>
                        <th>CUIT</th>
                        <th>Fecha de registro</th>
                    </tr>

                </thead>

                <tbody>

                    <?php if ($total_clientes == 0) { ?>

                        <tr>
                            <td colspan="7" class="cli-vacio">
                                <?php echo $busqueda !== ""
                                    ? "No se encontraron clientes con esa búsqueda."
                                    : "Todavía no hay clientes cargados."; ?>
                            </td>
                        </tr>

                    <?php } ?>


                    <?php while ($c = $clientes->fetch_assoc()) { ?>

                        <tr>

                            <td class="cli-nombre">
                                <?php echo htmlspecialchars($c["nombre"]); ?>
                            </td>

                            <td><?php echo mostrar($c["documento"]); ?></td>

                            <td><?php echo mostrar($c["telefono"]); ?></td>

                            <td><?php echo mostrar($c["email"]); ?></td>

                            <td><?php echo mostrar($c["direccion"]); ?></td>

                            <td><?php echo mostrar($c["cuit"]); ?></td>

                            <td>
                                <?php echo date("d/m/Y H:i", strtotime($c["fecha_creacion"])); ?>
                            </td>

                        </tr>

                    <?php } ?>

                </tbody>

            </table>

        </section>


    </main>



    <!-- ==========================================
         VENTANA EMERGENTE: NUEVO CLIENTE
    =========================================== -->

    <dialog class="cli-modal" id="modalCliente">

        <form method="POST" class="cli-modal-form">

            <div class="cli-modal-titulo">

                <h3>Nuevo cliente</h3>

                <button type="button" class="cli-modal-cerrar" id="btnCerrarModal">
                    ✕
                </button>

            </div>


            <?php if ($error !== "") { ?>

                <div class="cli-mensaje cli-mensaje-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>

            <?php } ?>


            <div class="campo">
                <label>Nombre y apellido *</label>
                <input
                    type="text"
                    name="nombre"
                    maxlength="150"
                    required
                    value="<?php echo htmlspecialchars($datos["nombre"]); ?>"
                >
            </div>


            <div class="cli-fila">

                <div class="campo">
                    <label>DNI</label>
                    <input
                        type="text"
                        name="documento"
                        maxlength="20"
                        inputmode="numeric"
                        value="<?php echo htmlspecialchars($datos["documento"]); ?>"
                    >
                </div>

                <div class="campo">
                    <label>CUIT</label>
                    <input
                        type="text"
                        name="cuit"
                        maxlength="20"
                        placeholder="20-12345678-9"
                        value="<?php echo htmlspecialchars($datos["cuit"]); ?>"
                    >
                </div>

            </div>


            <div class="cli-fila">

                <div class="campo">
                    <label>Número de contacto</label>
                    <input
                        type="text"
                        name="telefono"
                        maxlength="50"
                        value="<?php echo htmlspecialchars($datos["telefono"]); ?>"
                    >
                </div>

                <div class="campo">
                    <label>Email</label>
                    <input
                        type="email"
                        name="email"
                        maxlength="150"
                        value="<?php echo htmlspecialchars($datos["email"]); ?>"
                    >
                </div>

            </div>


            <div class="campo">
                <label>Dirección</label>
                <input
                    type="text"
                    name="direccion"
                    maxlength="255"
                    value="<?php echo htmlspecialchars($datos["direccion"]); ?>"
                >
            </div>


            <div class="cli-modal-botones">

                <button type="button" class="cli-btn-cancelar" id="btnCancelarModal">
                    Cancelar
                </button>

                <button type="submit" class="btn-guardar">
                    Guardar cliente
                </button>

            </div>

        </form>

    </dialog>



    <script>

        var modal = document.getElementById("modalCliente");

        document.getElementById("btnNuevoCliente").addEventListener("click", function () {
            modal.showModal();
        });

        document.getElementById("btnCerrarModal").addEventListener("click", function () {
            modal.close();
        });

        document.getElementById("btnCancelarModal").addEventListener("click", function () {
            modal.close();
        });

        // Cerrar al hacer click en el fondo oscuro
        modal.addEventListener("click", function (e) {
            if (e.target === modal) {
                modal.close();
            }
        });

        <?php if ($error !== "") { ?>
        // Si hubo un error al guardar, se vuelve a abrir con los datos cargados
        modal.showModal();
        <?php } ?>

    </script>


</body>

</html>
