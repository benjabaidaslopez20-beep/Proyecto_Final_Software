<?php

// =====================================================
// FUNCIONES DE AYUDA (formatos)
// =====================================================

function dinero($n)
{
    return "$" . number_format((float) $n, 2, ",", ".");
}

function nombre_metodo($m)
{
    $nombres = [
        "EFECTIVO"      => "Efectivo",
        "TRANSFERENCIA" => "Transferencia",
        "TARJETA"       => "Tarjeta",
        "MERCADO_PAGO"  => "Mercado Pago",
        "PAGARE"        => "Pagaré",
    ];

    return $nombres[$m] ?? $m;
}

function badge_estado_venta($estado)
{
    $clases = [
        "COMPLETADA" => "badge-ok",
        "PENDIENTE"  => "badge-aviso",
        "ANULADA"    => "badge-bajo",
    ];

    $clase = $clases[$estado] ?? "badge-aviso";

    return '<span class="badge ' . $clase . '">' . htmlspecialchars($estado) . '</span>';
}

?>
