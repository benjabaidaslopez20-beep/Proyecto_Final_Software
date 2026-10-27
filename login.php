<?php

define("SIN_LOGIN", true);

require_once "config/auth.php";
require_once "config/conexion.php";


// Si ya inició sesión, lo mandamos al inicio
if (!empty($_SESSION["usuario_id"])) {

    header("Location: index.php");
    exit;
}


// =====================================================
// ¿HAY USUARIOS? Si no hay ninguno, se crea el primer admin
// =====================================================

$total_usuarios = $conexion->query("SELECT COUNT(*) AS total FROM usuarios")
    ->fetch_assoc()["total"];

$primer_uso = ($total_usuarios == 0);

$error = "";


// =====================================================
// PROCESAR FORMULARIO
// =====================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $usuario  = trim($_POST["usuario"] ?? "");
    $password = $_POST["password"] ?? "";


    // ---------- CREAR PRIMER ADMINISTRADOR ----------

    if ($primer_uso) {

        $nombre    = trim($_POST["nombre"] ?? "");
        $password2 = $_POST["password2"] ?? "";

        if ($nombre === "" || $usuario === "" || $password === "") {

            $error = "Completá todos los campos.";

        } elseif (strlen($password) < 6) {

            $error = "La contraseña debe tener al menos 6 caracteres.";

        } elseif ($password !== $password2) {

            $error = "Las contraseñas no coinciden.";

        } else {

            $hash = password_hash($password, PASSWORD_DEFAULT);

            $insertar = $conexion->prepare(
                "INSERT INTO usuarios (nombre, usuario, password, rol)
                 VALUES (?, ?, ?, 'ADMIN')"
            );

            $insertar->bind_param("sss", $nombre, $usuario, $hash);

            if ($insertar->execute()) {

                iniciar_sesion($conexion->insert_id, $nombre, "ADMIN");

                header("Location: index.php");
                exit;

            } else {

                $error = "No se pudo crear el usuario.";
            }
        }


    // ---------- INICIAR SESIÓN ----------

    } else {

        $consulta = $conexion->prepare(
            "SELECT id, nombre, password, rol, activo
             FROM usuarios
             WHERE usuario = ?"
        );

        $consulta->bind_param("s", $usuario);
        $consulta->execute();

        $fila = $consulta->get_result()->fetch_assoc();

        $consulta->close();

        if ($fila && $fila["activo"] == 1 && password_verify($password, $fila["password"])) {

            iniciar_sesion($fila["id"], $fila["nombre"], $fila["rol"]);

            header("Location: index.php");
            exit;
        }

        // Mismo mensaje siempre, para no revelar si el usuario existe
        $error = "Usuario o contraseña incorrectos.";
    }
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Ingresar - Sistema de Gestión</title>

    <link rel="stylesheet" href="css/estilos.css">

    <link rel="stylesheet" href="css/formularios.css?v=1">

    <link rel="stylesheet" href="css/login.css?v=1">

</head>

<body class="login-fondo">

    <div class="login-caja">

        <div class="login-logo">

            <h1>📦 Mi Gestión</h1>

            <p>
                <?php echo $primer_uso
                    ? "Primer uso: creá el administrador del sistema"
                    : "Ingresá con tu usuario para continuar"; ?>
            </p>

        </div>


        <?php if ($error !== "") { ?>

            <div class="login-error">
                <?php echo htmlspecialchars($error); ?>
            </div>

        <?php } ?>


        <form method="POST" class="login-form">

            <?php if ($primer_uso) { ?>

                <div class="campo">
                    <label>Nombre completo</label>
                    <input type="text" name="nombre" required autofocus>
                </div>

            <?php } ?>


            <div class="campo">
                <label>Usuario</label>
                <input
                    type="text"
                    name="usuario"
                    required
                    <?php echo $primer_uso ? "" : "autofocus"; ?>
                >
            </div>


            <div class="campo">
                <label>Contraseña</label>
                <input type="password" name="password" required>
            </div>


            <?php if ($primer_uso) { ?>

                <div class="campo">
                    <label>Repetir contraseña</label>
                    <input type="password" name="password2" required>
                </div>

            <?php } ?>


            <button type="submit" class="btn-guardar login-boton">
                <?php echo $primer_uso ? "Crear administrador" : "Ingresar"; ?>
            </button>

        </form>

    </div>

</body>

</html>
