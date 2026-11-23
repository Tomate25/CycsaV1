<?php
/**
 * Migración 041: Espacio de firma adicional configurable en Hoja de Servicio RT-FM-13
 * vinculada a la impresión/PDF oficial del informe (abajo a la izquierda).
 */
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../app/Helpers/funciones.php';

$db = \Cycsa\Nucleo\Conexion::obtenerInstancia();

$columnas = [
    'incluir_firma_adicional' => "ALTER TABLE hojas_solicitud ADD COLUMN incluir_firma_adicional TINYINT(1) NOT NULL DEFAULT 0 AFTER firma_cliente",
    'firma_adicional_nombre' => "ALTER TABLE hojas_solicitud ADD COLUMN firma_adicional_nombre VARCHAR(180) NULL DEFAULT NULL AFTER incluir_firma_adicional",
    'firma_adicional_cargo' => "ALTER TABLE hojas_solicitud ADD COLUMN firma_adicional_cargo VARCHAR(180) NULL DEFAULT NULL AFTER firma_adicional_nombre",
];

foreach ($columnas as $col => $sqlAlter) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'hojas_solicitud' AND COLUMN_NAME = :col");
    $stmt->execute(['col' => $col]);
    if ((int)$stmt->fetchColumn() === 0) {
        $db->exec($sqlAlter);
        echo "[OK] Columna hojas_solicitud.{$col} agregada.\n";
    } else {
        echo "[SKIP] Columna hojas_solicitud.{$col} ya existe.\n";
    }
}
