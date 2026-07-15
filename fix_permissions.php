<?php
/**
 * Script para corregir permisos en Bluehost (Hosting Compartido)
 * Ajusta carpetas a 755 y archivos a 644 de forma recursiva para evitar errores HTTP 500.
 */

// Desactivar almacenamiento en caché
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <title>Corrección de Permisos - CYCSA</title>
    <style>
        body { font-family: monospace; background-color: #111827; color: #10b981; padding: 20px; line-height: 1.4; }
        .container { max-width: 900px; margin: 0 auto; }
        h1 { color: #f59e0b; border-bottom: 1px solid #374151; padding-bottom: 10px; }
        .success { color: #34d399; }
        .fail { color: #f87171; font-weight: bold; }
        .info { color: #60a5fa; }
        .warn { color: #fbbf24; }
        .stats { background-color: #1f2937; padding: 15px; border-radius: 6px; margin: 20px 0; border: 1px solid #374151; }
    </style>
</head>
<body>
<div class='container'>
    <h1>🛠️ Reparador de Permisos del Servidor - CYCSA</h1>";

$rootPath = __DIR__;
echo "<p class='info'>Directorio raíz a escanear: $rootPath</p>";

$stats = [
    'dirs_fixed' => 0,
    'dirs_failed' => 0,
    'files_fixed' => 0,
    'files_failed' => 0,
    'skipped' => 0
];

try {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($rootPath, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    // Primero corregir el propio directorio raíz
    $rootPerms = fileperms($rootPath) & 0777;
    if ($rootPerms !== 0755) {
        if (@chmod($rootPath, 0755)) {
            echo "<p class='success'>[Raíz] Cambiado permiso de $rootPath de " . sprintf('%o', $rootPerms) . " a 755</p>";
        } else {
            echo "<p class='fail'>[Raíz] ERROR: No se pudo cambiar permiso de $rootPath (Permiso actual: " . sprintf('%o', $rootPerms) . ")</p>";
        }
    }

    foreach ($iterator as $item) {
        $path = $item->getRealPath();
        $relativePath = substr($path, strlen($rootPath) + 1);

        // Omitir la carpeta .git y este propio archivo
        if (strpos($relativePath, '.git') === 0 || $relativePath === 'fix_permissions.php') {
            $stats['skipped']++;
            continue;
        }

        $currentPerms = fileperms($path) & 0777;

        if ($item->isDir()) {
            // Carpetas deben ser 755
            if ($currentPerms !== 0755) {
                if (@chmod($path, 0755)) {
                    $stats['dirs_fixed']++;
                    echo "<div>Carpeta: <span class='success'>[FIXED]</span> <code>$relativePath/</code> (de " . sprintf('%o', $currentPerms) . " a 755)</div>";
                } else {
                    $stats['dirs_failed']++;
                    echo "<div>Carpeta: <span class='fail'>[FAILED]</span> <code>$relativePath/</code> (actual: " . sprintf('%o', $currentPerms) . ")</div>";
                }
            }
        } else {
            // Archivos deben ser 644
            if ($currentPerms !== 0644) {
                if (@chmod($path, 0644)) {
                    $stats['files_fixed']++;
                    echo "<div>Archivo: <span class='success'>[FIXED]</span> <code>$relativePath</code> (de " . sprintf('%o', $currentPerms) . " a 644)</div>";
                } else {
                    $stats['files_failed']++;
                    echo "<div>Archivo: <span class='fail'>[FAILED]</span> <code>$relativePath</code> (actual: " . sprintf('%o', $currentPerms) . ")</div>";
                }
            }
        }
    }

    echo "<div class='stats'>";
    echo "<h3>📊 Resumen del Proceso:</h3>";
    echo "• Carpetas corregidas a 755: " . $stats['dirs_fixed'] . "<br>";
    echo "• Carpetas fallidas: " . $stats['dirs_failed'] . "<br>";
    echo "• Archivos corregidos a 644: " . $stats['files_fixed'] . "<br>";
    echo "• Archivos fallidos: " . $stats['files_failed'] . "<br>";
    echo "• Archivos/Carpetas omitidas: " . $stats['skipped'] . "<br>";
    echo "</div>";

    echo "<p class='warn'><strong>⚠️ IMPORTANTE:</strong> Recuerda eliminar este archivo (<code>fix_permissions.php</code>) y <code>prueba.php</code> una vez que el sistema esté funcionando correctamente para evitar riesgos de seguridad.</p>";

} catch (Exception $e) {
    echo "<p class='fail'>ERROR CRÍTICO: " . $e->getMessage() . "</p>";
}

echo "</div>
</body>
</html>";
?>
