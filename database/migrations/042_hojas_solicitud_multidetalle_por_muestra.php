<?php
/**
 * Migración 042: Asignación de Producto / Ensayo Cotizado por Muestra en RT-FM-13
 * Permite que una misma Hoja CYCSA-RT-FM-13 contenga N muestras (ej. 8 muestras)
 * y que Atención al Cliente o Recepción asigne cada muestra a cualquiera de los
 * productos/ensayos cotizados de la Orden de Servicio (ej. 3 productos distintos).
 */
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../app/Helpers/funciones.php';

$db = \Cycsa\Nucleo\Conexion::obtenerInstancia();

$stmtCol = $db->query("SHOW COLUMNS FROM hojas_solicitud_detalles LIKE 'id_detalle_cotizacion'");
$existeCol = (bool)($stmtCol && $stmtCol->fetch(\PDO::FETCH_ASSOC));

if (!$existeCol) {
    $db->exec("ALTER TABLE hojas_solicitud_detalles ADD COLUMN id_detalle_cotizacion INT(11) NULL DEFAULT NULL AFTER id_hoja_solicitud");
    $db->exec("ALTER TABLE hojas_solicitud_detalles ADD INDEX idx_hsd_detalle_cot (id_detalle_cotizacion)");
    echo "[OK] Columna hojas_solicitud_detalles.id_detalle_cotizacion agregada.\n";
} else {
    echo "[SKIP] Columna hojas_solicitud_detalles.id_detalle_cotizacion ya existe.\n";
}

// Backfill seguro desde hojas_solicitud.id_detalle_cotizacion para hojas existentes
$actualizadas = $db->exec("
    UPDATE hojas_solicitud_detalles hsd
    INNER JOIN hojas_solicitud hs ON hs.id = hsd.id_hoja_solicitud
    SET hsd.id_detalle_cotizacion = hs.id_detalle_cotizacion
    WHERE hsd.id_detalle_cotizacion IS NULL
      AND hs.id_detalle_cotizacion IS NOT NULL
      AND hs.id_detalle_cotizacion > 0
");
echo "[OK] Backfill completado: {$actualizadas} muestra(s) sincronizadas.\n";
