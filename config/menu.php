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
    "reportes"    => ["📊 Reportes",      "#"],
];

$seccion_actual = isset($menu_items[$menu_activo]) ? $menu_items[$menu_activo][0] : "";


// =====================================================
// PRODUCTOS CON STOCK BAJO
// (activos, con stock menor o igual al mínimo configurado)
// =====================================================

$productos_stock_bajo = 0;

if (isset($conexion)) {

    $consulta_stock_bajo = $conexion->query(
        "SELECT COUNT(*) AS total
         FROM productos
         WHERE activo = 1 AND stock <= stock_minimo"
    );

    if ($consulta_stock_bajo) {
        $productos_stock_bajo = (int) $consulta_stock_bajo->fetch_assoc()["total"];
    }
}

?>

<!-- Estilos del ícono de alerta de stock bajo -->
<style>

    .topbar-alerta {
        margin-left: auto;              /* la empuja al extremo derecho de la barra */
        display: inline-flex;
        align-items: center;
        gap: 6px;

        text-decoration: none;
        white-space: nowrap;
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
                <a href="<?php echo $prefijo; ?>usuarios/index.php">
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

})();
</script>
