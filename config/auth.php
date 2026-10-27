<?php

// =====================================================
// SESIONES Y CONTROL DE ACCESO
// Se incluye al principio de cada página protegida.
// =====================================================

if (session_status() === PHP_SESSION_NONE) {

    session_set_cookie_params([
        "httponly" => true,
        "samesite" => "Lax"
    ]);

    session_start();
}


// -----------------------------------------------------
// URL base del proyecto (ej: /sistema_gestion)
// -----------------------------------------------------

$raiz_proyecto = str_replace("\\", "/", realpath(__DIR__ . "/.."));
$raiz_servidor = str_replace("\\", "/", realpath($_SERVER["DOCUMENT_ROOT"]));

define("BASE_URL", rtrim(substr($raiz_proyecto, strlen($raiz_servidor)), "/"));


// -----------------------------------------------------
// Funciones
// -----------------------------------------------------

function requerir_login()
{
    if (empty($_SESSION["usuario_id"])) {

        header("Location: " . BASE_URL . "/login.php");
        exit;
    }
}

function es_admin()
{
    return ($_SESSION["usuario_rol"] ?? "") === "ADMIN";
}

function requerir_admin()
{
    requerir_login();

    if (!es_admin()) {

        http_response_code(403);
        die("No tenés permiso para ver esta página.");
    }
}

function iniciar_sesion($id, $nombre, $rol)
{
    session_regenerate_id(true);

    $_SESSION["usuario_id"]     = (int) $id;
    $_SESSION["usuario_nombre"] = $nombre;
    $_SESSION["usuario_rol"]    = $rol;
}


// -----------------------------------------------------
// Por defecto TODA página que incluya este archivo exige login.
// Solo login.php lo desactiva con define("SIN_LOGIN", true).
// -----------------------------------------------------

if (!defined("SIN_LOGIN")) {

    requerir_login();
}

?>
