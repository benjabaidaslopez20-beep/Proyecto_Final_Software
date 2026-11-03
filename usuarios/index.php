<?php

require_once "../config/auth.php";
require_once "../config/conexion.php";

requerir_admin();

$error = "";
$ok = "";


// =====================================================
// ACCIONES (crear / eliminar)
// =====================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $accion = $_POST["accion"] ?? "";


    // ---------- CREAR USUARIO ----------

    if ($accion === "crear") {

        $nombre   = trim($_POST["nombre"] ?? "");
        $usuario  = trim($_POST["usuario"] ?? "");
        $password = $_POST["password"] ?? "";
        $rol      = ($_POST["rol"] ?? "") === "ADMIN" ? "ADMIN" : "USUARIO";

        if ($nombre === "" || $usuario === "" || $password === "") {

            $error = "Completá todos los campos.";

        } elseif (strlen($password) < 6) {

            $error = "La contraseña debe tener al menos 6 caracteres.";

        } else {

            $existe = $conexion->prepare("SELECT id FROM usuarios WHERE usuario = ?");
            $existe->bind_param("s", $usuario);
            $existe->execute();

            if ($existe->get_result()->num_rows > 0) {

                $error = "Ya existe un usuario con ese nombre de usuario.";

            } else {

                $hash = password_hash($password, PASSWORD_DEFAULT);

                $insertar = $conexion->prepare(
                    "INSERT INTO usuarios (nombre, usuario, password, rol)
                     VALUES (?, ?, ?, ?)"
                );

                $insertar->bind_param("ssss", $nombre, $usuario, $hash, $rol);

                if ($insertar->execute()) {
                    $ok = "Usuario creado correctamente.";
                } else {
                    $error = "No se pudo crear el usuario.";
                }
            }

            $existe->close();
        }
    }


    // ---------- ELIMINAR USUARIO ----------

    if ($accion === "eliminar") {

        $id = intval($_POST["id"] ?? 0);

        if ($id === $_SESSION["usuario_id"]) {

            $error = "No podés eliminar tu propio usuario.";

        } else {

            $borrar = $conexion->prepare("DELETE FROM usuarios WHERE id = ?");
            $borrar->bind_param("i", $id);
            $borrar->execute();

            $ok = "Usuario eliminado.";
        }
    }
}


// =====================================================
// LISTADO
// =====================================================

$resultado = $conexion->query(
    "SELECT id, nombre, usuario, rol, creado_en FROM usuarios ORDER BY id ASC"
);

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Usuarios - Sistema de Gestión</title>

    <link rel="stylesheet" href="../css/estilos.css">

    <link rel="stylesheet" href="../css/tablas.css?v=1">

    <link rel="stylesheet" href="../css/formularios.css?v=1">

    <link rel="stylesheet" href="../css/login.css?v=1">

</head>

<body>

    <!-- BARRA LATERAL -->

    <aside class="sidebar">

        <div class="logo">
            <h1>📦 Mi Gestión</h1>
            <p>Sistema de gestión</p>
        </div>

        <ul class="menu">

            <li><a href="../index.php">🏠 Inicio</a></li>

            <li><a href="../productos/index.php">📦 Productos</a></li>

            <li><a href="../movimientos/index.php">↕️ Movimientos</a></li>

            <li><a href="../ventas/index.php">🛒 Ventas</a></li>

            <li><a href="#">🧾 Facturación</a></li>

            <li><a href="../clientes/index.php">👥 Clientes</a></li>

            <li><a href="#">📊 Reportes</a></li>

            <li><a href="index.php" class="active">👤 Usuarios</a></li>

            <li><a href="../logout.php">🚪 Cerrar sesión</a></li>

        </ul>

    </aside>


    <!-- CONTENIDO -->

    <main class="main">

        <header class="header">

            <h2>Usuarios</h2>

            <p>
                Quiénes pueden ingresar al sistema.
            </p>

        </header>


        <?php if ($error !== "") { ?>
            <div class="login-error"><?php echo htmlspecialchars($error); ?></div>
        <?php } ?>

        <?php if ($ok !== "") { ?>
            <div class="mensaje-ok"><?php echo htmlspecialchars($ok); ?></div>
        <?php } ?>


        <!-- NUEVO USUARIO -->

        <section class="content-box">

            <h3>Nuevo usuario</h3>

            <form method="POST" class="formulario">

                <input type="hidden" name="accion" value="crear">

                <div class="campo">
                    <label>Nombre completo</label>
                    <input type="text" name="nombre" required>
                </div>

                <div class="campo">
                    <label>Usuario</label>
                    <input type="text" name="usuario" required>
                </div>

                <div class="campo">
                    <label>Contraseña (mínimo 6 caracteres)</label>
                    <input type="password" name="password" required>
                </div>

                <div class="campo">
                    <label>Rol</label>
                    <select name="rol" class="select-campo">
                        <option value="USUARIO">Usuario</option>
                        <option value="ADMIN">Administrador</option>
                    </select>
                </div>

                <div class="formulario-botones">
                    <button type="submit" class="btn-guardar">Crear usuario</button>
                </div>

            </form>

        </section>


        <!-- LISTADO -->

        <section class="content-box" style="margin-top: 20px;">

            <h3>Usuarios registrados</h3>

            <div class="tabla-contenedor">

                <table class="tabla">

                    <thead>
                        <tr>
                            <th class="col-num">#</th>
                            <th>Nombre</th>
                            <th>Usuario</th>
                            <th>Rol</th>
                            <th>Creado</th>
                            <th class="col-acciones">Acciones</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php while ($u = $resultado->fetch_assoc()) { ?>

                            <tr>
                                <td class="col-num"><?php echo $u["id"]; ?></td>
                                <td class="col-nombre"><?php echo htmlspecialchars($u["nombre"]); ?></td>
                                <td class="col-codigo"><?php echo htmlspecialchars($u["usuario"]); ?></td>
                                <td>
                                    <span class="badge <?php echo $u["rol"] === "ADMIN" ? "badge-ok" : "badge-bajo"; ?>">
                                        <?php echo $u["rol"] === "ADMIN" ? "Admin" : "Usuario"; ?>
                                    </span>
                                </td>
                                <td><?php echo date("d/m/Y", strtotime($u["creado_en"])); ?></td>
                                <td class="col-acciones">

                                    <?php if ($u["id"] != $_SESSION["usuario_id"]) { ?>

                                        <form method="POST" style="display:inline;"
                                              onsubmit="return confirm('¿Eliminar este usuario?');">
                                            <input type="hidden" name="accion" value="eliminar">
                                            <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                            <button type="submit" class="btn-accion btn-eliminar" style="border:none;cursor:pointer;">
                                                Eliminar
                                            </button>
                                        </form>

                                    <?php } else { ?>

                                        <span style="color:#94a3b8;font-size:13px;">(vos)</span>

                                    <?php } ?>

                                </td>
                            </tr>

                        <?php } ?>

                    </tbody>

                </table>

            </div>

        </section>

    </main>

</body>

</html>