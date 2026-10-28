<?php

/**
 * Migración 022: Soporte para cliente_informe, cliente_factura y condición comercial (Fase C TAREA-059).
 * Permite emitir informes y matrices técnicas a una figura jurídica distinta de quien paga (ISO/IEC 17025),
 * y flexibiliza la facturación en cualquier etapa distinguiendo condiciones contado vs crédito.
 */

require_once dirname(__DIR__, 2) . '/app/Core/Conexion.php';

function ejecutarMigracion022(PDO $db): void {
    // 1. Agregar columnas a cotizaciones si no existen
    $colsCot = $db->query("SHOW COLUMNS FROM cotizaciones")->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('id_cliente_factura', $colsCot, true)) {
        $db->exec("ALTER TABLE cotizaciones ADD COLUMN id_cliente_factura INT NULL DEFAULT NULL AFTER id_cliente");
    }
    if (!in_array('id_cliente_informe', $colsCot, true)) {
        $db->exec("ALTER TABLE cotizaciones ADD COLUMN id_cliente_informe INT NULL DEFAULT NULL AFTER id_cliente_factura");
    }
    if (!in_array('condicion_comercial', $colsCot, true)) {
        $db->exec("ALTER TABLE cotizaciones ADD COLUMN condicion_comercial VARCHAR(50) DEFAULT 'contado' AFTER condicion_pago");
    }

    // Poblar valores por defecto en cotizaciones
    $db->exec("UPDATE cotizaciones SET id_cliente_factura = id_cliente WHERE id_cliente_factura IS NULL");
    $db->exec("UPDATE cotizaciones SET id_cliente_informe = id_cliente WHERE id_cliente_informe IS NULL");
    $db->exec("UPDATE cotizaciones SET condicion_comercial = CASE 
        WHEN LOWER(condicion_pago) LIKE '%credito%' THEN 'credito'
        WHEN LOWER(condicion_pago) LIKE '%anticipo%' THEN 'anticipo'
        WHEN LOWER(condicion_pago) LIKE '%contra%' THEN 'contra_entrega'
        ELSE 'contado'
    END WHERE condicion_comercial IS NULL OR condicion_comercial = ''");

    // 2. Agregar columnas a ordenes_servicio si no existen
    $colsOS = $db->query("SHOW COLUMNS FROM ordenes_servicio")->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('id_cliente_factura', $colsOS, true)) {
        $db->exec("ALTER TABLE ordenes_servicio ADD COLUMN id_cliente_factura INT NULL DEFAULT NULL AFTER id_cliente");
    }
    if (!in_array('id_cliente_informe', $colsOS, true)) {
        $db->exec("ALTER TABLE ordenes_servicio ADD COLUMN id_cliente_informe INT NULL DEFAULT NULL AFTER id_cliente_factura");
    }
    if (!in_array('condicion_comercial', $colsOS, true)) {
        $db->exec("ALTER TABLE ordenes_servicio ADD COLUMN condicion_comercial VARCHAR(50) DEFAULT 'contado' AFTER forma_pago");
    }

    // Poblar valores por defecto en ordenes_servicio
    $db->exec("UPDATE ordenes_servicio SET id_cliente_factura = id_cliente WHERE id_cliente_factura IS NULL AND id_cliente IS NOT NULL");
    $db->exec("UPDATE ordenes_servicio SET id_cliente_informe = id_cliente WHERE id_cliente_informe IS NULL AND id_cliente IS NOT NULL");
    $db->exec("UPDATE ordenes_servicio SET condicion_comercial = CASE 
        WHEN LOWER(forma_pago) LIKE '%credito%' THEN 'credito'
        WHEN LOWER(forma_pago) LIKE '%anticipo%' THEN 'anticipo'
        WHEN LOWER(forma_pago) LIKE '%contra%' THEN 'contra_entrega'
        ELSE 'contado'
    END WHERE condicion_comercial IS NULL OR condicion_comercial = ''");
}

if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
    ejecutarMigracion022($db);
    echo "[OK] Migracion 022 ejecutada correctamente en la base de datos principal.\n";
}
