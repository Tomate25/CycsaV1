<?php
/**
 * Migración 024: descripciones largas de ítems de cotización (Sprint C.1).
 *
 * descripcion_ensayo estaba en VARCHAR(255). descripcion_adicional puede
 * no existir o ser VARCHAR. Ambas quedan en TEXT para conservar texto
 * multilínea de más de 1000 caracteres sin recorte.
 *
 * Ejecución CLI: php database/migrations/024_cotizacion_descripcion_multilinea.php
 */

if (!isset($db) || !($db instanceof PDO)) {
    require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
    $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}

echo "--- Iniciando Migración 024: Descripciones multilínea de cotización ---\n";

$tabla = $db->query("SHOW TABLES LIKE 'cotizacion_detalles'")->fetch(PDO::FETCH_NUM);
if (!$tabla) {
    echo "[INFO] La tabla cotizacion_detalles no existe. Migración omitida.\n";
    return;
}

/**
 * @return array<string, string>|null
 */
$tipoColumna = static function (PDO $db, string $columna): ?array {
    $columna = str_replace('`', '', $columna);
    $fila = $db->query("SHOW COLUMNS FROM `cotizacion_detalles` LIKE " . $db->quote($columna))->fetch(PDO::FETCH_ASSOC);
    return $fila ?: null;
};

$esTextoLargo = static function (?string $tipo): bool {
    if ($tipo === null || $tipo === '') {
        return false;
    }
    return (bool)preg_match('/\b(tinytext|text|mediumtext|longtext)\b/i', $tipo);
};

$colEnsayo = $tipoColumna($db, 'descripcion_ensayo');
if ($colEnsayo && !$esTextoLargo($colEnsayo['Type'] ?? '')) {
    $db->exec("ALTER TABLE `cotizacion_detalles` MODIFY `descripcion_ensayo` TEXT NOT NULL");
    echo "[OK] descripcion_ensayo ampliada a TEXT (antes: {$colEnsayo['Type']}).\n";
} else {
    echo "[OK] descripcion_ensayo ya admite texto largo.\n";
}

$colAdicional = $tipoColumna($db, 'descripcion_adicional');
if (!$colAdicional) {
    $despues = $tipoColumna($db, 'observaciones') ? ' AFTER `observaciones`' : ' AFTER `descripcion_ensayo`';
    $db->exec("ALTER TABLE `cotizacion_detalles` ADD COLUMN `descripcion_adicional` TEXT NULL{$despues}");
    echo "[OK] Columna descripcion_adicional TEXT creada.\n";
} elseif (!$esTextoLargo($colAdicional['Type'] ?? '')) {
    $db->exec("ALTER TABLE `cotizacion_detalles` MODIFY `descripcion_adicional` TEXT NULL");
    echo "[OK] descripcion_adicional ampliada a TEXT (antes: {$colAdicional['Type']}).\n";
} else {
    echo "[OK] descripcion_adicional ya admite texto largo.\n";
}

echo "--- Migración 024 finalizada ---\n";
