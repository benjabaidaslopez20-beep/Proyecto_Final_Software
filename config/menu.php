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
    "facturacion" => ["🧾 Facturación",   "#"],
    "clientes"    => ["👥 Clientes",      "clientes/index.php"],
    "reportes"    => ["📊 Reportes",      "#"],
];

$seccion_actual = isset($menu_items[$menu_activo]) ? $menu_items[$menu_activo][0] : "";

?>

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
