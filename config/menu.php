<?php

// =====================================================
// MENÚ LATERAL
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

?>

<aside class="sidebar">

    <div class="logo">
        <h1>📦 Mi Gestión</h1>
        <p>Sistema de gestión</p>
    </div>

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

</aside>
