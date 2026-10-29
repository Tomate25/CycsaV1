<?php
/**
 * Migración 023: Corrección de Catálogos de Formatos de Compactación y Normalización Comercial
 * 
 * - Corrige la asociación errónea del Producto 23 (Proctor Modificado) de formato_id = 21 (compresión núcleos) a 15 (proctor_estandar.md).
 * - Garantiza que los ensayos de Densímetro Nuclear (ASTM D6938) apunten a formato_id = 1 (compactacion_densimetro_nuclear.md).
 * - Garantiza que Cono de Arena (ASTM D1556) apunte a formato_id = 3 y Reemplazo de Agua (ASTM D5030) a formato_id = 4.
 * - Corrige condiciones comerciales en O/S que nacieron erróneamente como contado teniendo texto libre de crédito (ej. 'Crédito 30 días').
 */

use Cycsa\Nucleo\Conexion;

if (!class_exists(Conexion::class) && file_exists(dirname(__DIR__, 2) . '/vendor/autoload.php')) {
    require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
}

if (!isset($db)) {
    $db = Conexion::obtenerInstancia();
}

try {
    // 1. Corregir Producto 23: Proctor Modificado (debe ser formato_id = 15 Proctor, no 21 compresión)
    $db->exec("UPDATE productos SET formato_id = 15 WHERE id = 23 AND (formato_id = 21 OR formato_id IS NULL)");

    // 2. Corregir productos de Densímetro Nuclear (ASTM D6938)
    $db->exec("UPDATE productos 
               SET formato_id = 1 
               WHERE (norma_astm LIKE '%D6938%' OR norma_astm LIKE '%D 6938%' OR nombre_comercial LIKE '%densímetro%' OR nombre_comercial LIKE '%densimetro%') 
                 AND (formato_id != 1 OR formato_id IS NULL)");

    // 3. Corregir productos de Cono de Arena (ASTM D1556)
    $db->exec("UPDATE productos 
               SET formato_id = 3 
               WHERE (norma_astm LIKE '%D1556%' OR norma_astm LIKE '%D 1556%' OR nombre_comercial LIKE '%cono de arena%') 
                 AND (formato_id != 3 OR formato_id IS NULL)");

    // 4. Corregir productos de Reemplazo de Agua (ASTM D5030)
    $db->exec("UPDATE productos 
               SET formato_id = 4 
               WHERE (norma_astm LIKE '%D5030%' OR norma_astm LIKE '%D 5030%' OR nombre_comercial LIKE '%reemplazo de agua%') 
                 AND (formato_id != 4 OR formato_id IS NULL)");

    // 5. Corregir productos de Proctor
    $db->exec("UPDATE productos 
               SET formato_id = 15 
               WHERE (norma_astm LIKE '%D698%' OR norma_astm LIKE '%D1557%' OR nombre_comercial LIKE '%proctor%') 
                 AND (formato_id != 15 OR formato_id IS NULL)");

    // 6. Normalización retroactiva de condición comercial en O/S que tengan forma_pago custom de crédito
    $db->exec("UPDATE ordenes_servicio 
               SET condicion_comercial = 'credito' 
               WHERE (condicion_comercial = 'contado' OR condicion_comercial IS NULL) 
                 AND forma_pago IS NOT NULL 
                 AND (
                    LOWER(forma_pago) LIKE '%crédito%' 
                    OR LOWER(forma_pago) LIKE '%credito%' 
                    OR LOWER(forma_pago) LIKE '%días%' 
                    OR LOWER(forma_pago) LIKE '%dias%'
                 )
                 AND LOWER(forma_pago) NOT LIKE '%contado%'
                 AND LOWER(forma_pago) NOT LIKE '%contra entrega%'
                 AND LOWER(forma_pago) NOT LIKE '%contra-entrega%'");

    echo "[023 OK] Catálogo de formatos de compactación y condiciones comerciales normalizados exitosamente.\n";
} catch (\Throwable $e) {
    echo "[023 ERROR] Error en migración 023: " . $e->getMessage() . "\n";
}
