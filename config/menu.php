<?php

// =====================================================
// MENÚ SUPERIOR DESPLEGABLE
// Antes de incluirlo, definir:
//   $menu_activo  -> "inicio", "productos", "ventas", etc.
//   $prefijo      -> "" (raíz) o "../" (dentro de una carpeta)
// =====================================================

$menu_items = [
    "inicio"      => ["🏠 Inicio",        "index.php"],
    "productos"   => ["📦 Productos",     "productos/index.php"],
    "movimientos" => ["↕️ Movimientos",   "movimientos/index.php"],
    "ventas"      => ["🛒 Ventas",        "ventas/index.php"],
    "facturacion" => ["🧾 Facturación",   "facturacion/index.php"],
    "clientes"    => ["👥 Clientes",      "clientes/index.php"],
    "reportes"    => ["📊 Reportes",      "reportes/index.php"],
];

$menu_activo = $menu_activo ?? "";

// Si la página no definió $menu_activo (o puso uno desconocido),
// se deduce por la carpeta en la que está. "usuarios" no figura en
// $menu_items porque solo lo ven los administradores.

if (!isset($menu_items[$menu_activo]) && $menu_activo !== "usuarios") {

    $ruta_actual = str_replace("\\", "/", $_SERVER["SCRIPT_NAME"] ?? "");

    $menu_activo = "";

    foreach (array_merge(array_keys($menu_items), ["usuarios"]) as $clave_menu) {

        if ($clave_menu !== "inicio" && strpos($ruta_actual, "/" . $clave_menu . "/") !== false) {

            $menu_activo = $clave_menu;
            break;
        }
    }
}

if ($menu_activo === "usuarios") {

    $seccion_actual = "👤 Usuarios";

} else {

    $seccion_actual = isset($menu_items[$menu_activo]) ? $menu_items[$menu_activo][0] : "";
}


// =====================================================
// PRODUCTOS CON STOCK BAJO
// (activos, con stock menor o igual al mínimo configurado,
// o con 4 unidades o menos, aunque no tengan mínimo cargado)
// El aviso desaparece solo cuando se actualiza el stock.
// =====================================================

$aviso_lista = [];

if (isset($conexion) && $conexion instanceof mysqli) {

    try {

        $aviso_res = $conexion->query(
            "SELECT id, codigo, nombre, stock, stock_minimo
             FROM productos
             WHERE activo = 1 AND (stock <= stock_minimo OR stock <= 4)
             ORDER BY stock ASC, nombre ASC"
        );

        if ($aviso_res) {

            while ($aviso_fila = $aviso_res->fetch_assoc()) {

                $aviso_lista[] = $aviso_fila;
            }
        }

    } catch (Throwable $e) {

        $aviso_lista = [];
    }
}

$aviso_cant = count($aviso_lista);

$productos_stock_bajo = $aviso_cant;

// Firma de la lista actual: el popup se muestra de nuevo solo si cambia
$aviso_firma = "";

foreach ($aviso_lista as $aviso_fila) {

    $aviso_firma .= $aviso_fila["id"] . ":" . $aviso_fila["stock"] . ";";
}

$aviso_firma = md5($aviso_firma);

$aviso_link = $prefijo . "productos/actualizar_stock.php";

?>

<link rel="stylesheet" href="<?php echo $prefijo; ?>css/alerta.css?v=1">

<!-- Estilos del ícono de alerta de stock bajo -->
<style>

    .topbar-alerta {
        /* Anula los estilos viejos de .topbar-alerta que pueda tener estilos.css */
        width: auto;
        height: auto;
        padding: 0;
        background: none;
        border: none;
        border-radius: 0;
        animation: none;
        position: static;
        font-size: inherit;

        margin-left: auto;              /* la empuja al extremo derecho de la barra */
        display: inline-flex;
        align-items: center;
        gap: 6px;

        text-decoration: none;
        white-space: nowrap;
    }

    .topbar-alerta:hover {
        background: none;
    }

    .topbar-alerta-icono {
        display: inline-flex;
        align-items: center;
        justify-content: center;

        width: 30px;                    /* tamaño del círculo */
        height: 30px;

        border-radius: 50%;             /* lo vuelve un círculo */
        background-color: #e11d48;      /* rojo de alerta */
        color: #ffffff;

        font-weight: 800;
        font-size: 16px;

        box-shadow: 0 0 0 3px rgba(225, 29, 72, 0.18); /* resplandor alrededor */

        animation: topbar-alerta-latido 1.8s ease-in-out infinite; /* animación suave */
    }

    .topbar-alerta-texto {
        font-size: 13px;
        font-weight: 600;
        color: #e11d48;
    }

    .topbar-alerta:hover .topbar-alerta-icono {
        background-color: #be123c;
    }

    /* Pulso suave para llamar la atención sin ser molesto */
    @keyframes topbar-alerta-latido {

        0%, 100% {
            box-shadow: 0 0 0 3px rgba(225, 29, 72, 0.18);
        }

        50% {
            box-shadow: 0 0 0 6px rgba(225, 29, 72, 0.12);
        }
    }

    @media (max-width: 700px) {

        .topbar-alerta-texto {
            display: none;              /* en celular solo queda el círculo */
        }
    }

    @media (prefers-reduced-motion: reduce) {

        .topbar-alerta-icono {
            animation: none;
        }
    }

</style>

<!-- Barra superior: siempre visible, con el botón que abre el menú -->

<header class="topbar">

    <button type="button"
            class="menu-toggle"
            id="menuToggle"
            aria-label="Abrir menú"
            aria-expanded="false"
            aria-controls="menuPanel">
        <span></span>
        <span></span>
        <span></span>
    </button>

    <div class="topbar-logo">
        <span class="topbar-titulo">📦 Mi Gestión</span>
        <span class="topbar-sub">Sistema de gestión</span>
    </div>

    <?php if ($seccion_actual !== "") { ?>
        <span class="topbar-seccion"><?php echo $seccion_actual; ?></span>
    <?php } ?>

    <!-- ALERTA DE STOCK BAJO -->

    <?php if ($productos_stock_bajo > 0) { ?>

        <a
            href="<?php echo $prefijo; ?>productos/index.php"
            class="topbar-alerta"
            title="<?php echo $productos_stock_bajo; ?> producto(s) con stock bajo"
        >
            <span class="topbar-alerta-icono">!</span>

            <span class="topbar-alerta-texto">
                <?php echo $productos_stock_bajo; ?>
                <?php echo $productos_stock_bajo === 1 ? "producto con stock bajo" : "productos con stock bajo"; ?>
            </span>
        </a>

    <?php } ?>

</header>


<!-- Panel horizontal que se despliega debajo de la barra -->

<nav class="menu-panel" id="menuPanel">

    <ul class="menu">

        <?php foreach ($menu_items as $clave => $item) { ?>

            <li>
                <a href="<?php echo $item[1] === '#' ? '#' : $prefijo . $item[1]; ?>"
                   <?php echo $clave === $menu_activo ? 'class="active"' : ''; ?>>
                    <?php echo $item[0]; ?>
                </a>
            </li>

        <?php } ?>

        <?php if (es_admin()) { ?>

            <li>
                <a href="<?php echo $prefijo; ?>usuarios/index.php"
                   <?php echo $menu_activo === "usuarios" ? 'class="active"' : ''; ?>>
                    👤 Usuarios
                </a>
            </li>

        <?php } ?>

        <li>
            <a href="<?php echo $prefijo; ?>logout.php">
                🚪 Cerrar sesión
            </a>
        </li>

    </ul>

</nav>

<!-- Popup de stock bajo (solo si hay productos en el límite) -->

<?php if ($aviso_cant > 0) { ?>

    <dialog class="popup-stock" id="alertaStock" data-firma="<?php echo $aviso_firma; ?>">

        <div class="popup-stock-cabecera">

            <span class="popup-stock-icono">!</span>

            <div>
                <h3>Stock bajo</h3>

                <p>
                    <?php echo $aviso_cant === 1
                        ? "1 producto necesita reposición"
                        : $aviso_cant . " productos necesitan reposición"; ?>
                </p>
            </div>

        </div>


        <div class="popup-stock-cuerpo">

            <p class="popup-stock-texto">
                <?php echo $aviso_cant === 1
                    ? "Este producto llegó al límite mínimo de stock:"
                    : "Estos productos llegaron al límite mínimo de stock:"; ?>
            </p>

            <ul class="popup-stock-lista">

                <?php foreach (array_slice($aviso_lista, 0, 8) as $aviso_fila) { ?>

                    <li>
                        <span class="popup-stock-nombre">
                            <?php echo htmlspecialchars($aviso_fila["nombre"]); ?>
                        </span>

                        <span class="popup-stock-pill <?php echo (int) $aviso_fila["stock"] === 0 ? 'agotado' : ''; ?>">
                            <?php echo (int) $aviso_fila["stock"] === 0 ? "Sin stock" : (int) $aviso_fila["stock"]; ?>
                            <small>mín. <?php echo (int) $aviso_fila["stock_minimo"]; ?></small>
                        </span>
                    </li>

                <?php } ?>

                <?php if ($aviso_cant > 8) { ?>
                    <li class="popup-stock-mas">
                        <span>y <?php echo $aviso_cant - 8; ?> más...</span>
                    </li>
                <?php } ?>

            </ul>

            <div class="popup-stock-botones">

                <button type="button" class="popup-stock-btn secundario" id="alertaStockCerrar">
                    Cerrar
                </button>

                <a href="<?php echo $aviso_link; ?>" class="popup-stock-btn">
                    Actualizar stock
                </a>

            </div>

        </div>

    </dialog>

<?php } ?>

<!-- Fondo oscuro: al tocarlo se cierra el menú -->
<div class="menu-fondo" id="menuFondo"></div>

<script>
(function () {

    var cuerpo = document.body;
    var boton = document.getElementById("menuToggle");
    var fondo = document.getElementById("menuFondo");

    function cambiar(abrir) {
        cuerpo.classList.toggle("menu-abierto", abrir);
        boton.setAttribute("aria-expanded", abrir ? "true" : "false");
        boton.setAttribute("aria-label", abrir ? "Cerrar menú" : "Abrir menú");
    }

    boton.addEventListener("click", function () {
        cambiar(!cuerpo.classList.contains("menu-abierto"));
    });

    fondo.addEventListener("click", function () {
        cambiar(false);
    });

    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape") {
            cambiar(false);
        }
    });

    // ---------- Popup de stock bajo ----------
    // Se muestra una vez por sesión; vuelve a aparecer solo si la lista cambia
    // (por ejemplo, después de una venta que deja otro producto en el límite).

    var alerta = document.getElementById("alertaStock");

    if (alerta && alerta.showModal) {

        var firma = alerta.getAttribute("data-firma");
        var vista = null;

        try { vista = sessionStorage.getItem("alertaStockVista"); } catch (err) {}

        if (vista !== firma) {

            alerta.showModal();

            try { sessionStorage.setItem("alertaStockVista", firma); } catch (err) {}
        }

        document.getElementById("alertaStockCerrar").addEventListener("click", function () {
            alerta.close();
        });

        // Cerrar al hacer click en el fondo oscuro
        alerta.addEventListener("click", function (e) {
            if (e.target === alerta) {
                alerta.close();
            }
        });
    }

})();
</script>