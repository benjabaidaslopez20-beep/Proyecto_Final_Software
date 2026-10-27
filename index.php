<?php

require_once "config/conexion.php";


// =====================================================
// CANTIDAD DE PRODUCTOS
// =====================================================

$consulta_productos = "SELECT COUNT(*) AS total FROM productos";

$resultado_productos = $conexion->query($consulta_productos);

$fila_productos = $resultado_productos->fetch_assoc();

$total_productos = $fila_productos["total"];


// =====================================================
// STOCK TOTAL
// =====================================================

$consulta_stock = "SELECT COALESCE(SUM(stock), 0) AS total FROM productos";

$resultado_stock = $conexion->query($consulta_stock);

$fila_stock = $resultado_stock->fetch_assoc();

$total_stock = $fila_stock["total"];


// =====================================================
// VENTAS DEL DÍA
// =====================================================

$consulta_ventas_dia = "
    SELECT COALESCE(SUM(total), 0) AS total
    FROM ventas
    WHERE DATE(fecha) = CURDATE()
    AND estado = 'COMPLETADA'
";

$resultado_ventas_dia = $conexion->query($consulta_ventas_dia);

$fila_ventas_dia = $resultado_ventas_dia->fetch_assoc();

$ventas_dia = $fila_ventas_dia["total"];


// =====================================================
// VENTAS DEL MES
// =====================================================

$consulta_ventas_mes = "
    SELECT COALESCE(SUM(total), 0) AS total
    FROM ventas
    WHERE MONTH(fecha) = MONTH(CURDATE())
    AND YEAR(fecha) = YEAR(CURDATE())
    AND estado = 'COMPLETADA'
";

$resultado_ventas_mes = $conexion->query($consulta_ventas_mes);

$fila_ventas_mes = $resultado_ventas_mes->fetch_assoc();

$ventas_mes = $fila_ventas_mes["total"];



?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Sistema de Gestión</title>

    <link rel="stylesheet" href="css/estilos.css">

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


            <!-- INICIO -->

            <li>

                <a href="index.php" class="active">

                    🏠 Inicio

                </a>

            </li>


            <!-- PRODUCTOS -->

            <li>

                <a href="productos/index.php">

                    📦 Productos

                </a>

            </li>


            <!-- MOVIMIENTOS -->

            <li>

                <a href="movimientos/index.php">

                    ↕️ Movimientos

                </a>

            </li>


            <!-- VENTAS -->

            <li>

                <a href="#">

                    🛒 Ventas

                </a>

            </li>


            <!-- FACTURACIÓN -->

            <li>

                <a href="#">

                    🧾 Facturación

                </a>

            </li>


            <!-- CLIENTES -->

            <li>

                <a href="#">

                    👥 Clientes

                </a>

            </li>


            <!-- REPORTES -->

            <li>

                <a href="#">

                    📊 Reportes

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
                Inicio
            </h2>

            <p>
                Bienvenido al sistema de gestión.
            </p>

        </header>



        <!-- ==========================================
             TARJETAS
        =========================================== -->

        <section class="cards">


            <!-- PRODUCTOS -->

            <div class="card">

                <div class="card-title">

                    Productos

                </div>

                <div class="card-value">

                    <?php echo $total_productos; ?>

                </div>

            </div>



            <!-- STOCK -->

            <div class="card">

                <div class="card-title">

                    Stock disponible

                </div>

                <div class="card-value">

                    <?php echo $total_stock; ?>

                </div>

            </div>



            <!-- VENTAS DEL DÍA -->

            <div class="card">

                <div class="card-title">

                    Ventas del día

                </div>

                <div class="card-value">

                    $<?php echo number_format($ventas_dia, 2, ",", "."); ?>

                </div>

            </div>



            <!-- VENTAS DEL MES -->

            <div class="card">

                <div class="card-title">

                    Ventas del mes

                </div>

                <div class="card-value">

                    $<?php echo number_format($ventas_mes, 2, ",", "."); ?>

                </div>

            </div>


        </section>



    </main>


</body>

</html>