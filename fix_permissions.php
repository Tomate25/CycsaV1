<?php
// fix_permissions.php - Corrector de permisos recursivo para Bluehost
header('Content-Type: text/plain; charset=utf-8');

$dir = __DIR__;
echo "🛠️ Iniciando corrección de permisos en: $dir\n\n";

function corregirPermisos($ruta) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($ruta, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    $directoriosCorregidos = 0;
    $archivosCorregidos = 0;
    $errores = 0;

    // Corregir la carpeta raíz
    if (@chmod($ruta, 0755)) {
        $directoriosCorregidos++;
    } else {
        $errores++;
    }

    foreach ($iterator as $item) {
        $pathname = $item->getPathname();
        
        // Evitar bucles infinitos u operar sobre directorios especiales
        if (strpos($pathname, '..') !== false || strpos($pathname, '.') === 0) {
            continue;
        }

        if ($item->isDir()) {
            if (@chmod($pathname, 0755)) {
                $directoriosCorregidos++;
            } else {
                echo "⚠️ No se pudo cambiar permiso al directorio: $pathname\n";
                $errores++;
            }
        } else {
            if (@chmod($pathname, 0644)) {
                $archivosCorregidos++;
            } else {
                echo "⚠️ No se pudo cambiar permiso al archivo: $pathname\n";
                $errores++;
            }
        }
    }

    echo "\n📊 Resumen de operación:\n";
    echo "--------------------------\n";
    echo "✔️ Directorios corregidos a 755: $directoriosCorregidos\n";
    echo "✔️ Archivos corregidos a 644: $archivosCorregidos\n";
    echo "❌ Errores encontrados: $errores\n";

    // 🔍 Inspección específica del archivo conflictivo
    $targetFile = $ruta . '/vendor/thecodingmachine/safe/deprecated/apc.php';
    echo "\n🔍 Inspección específica de apc.php:\n";
    if (file_exists($targetFile)) {
        echo "Existe: SÍ\n";
        echo "Permisos actuales (octal): " . substr(sprintf('%o', fileperms($targetFile)), -4) . "\n";
        echo "Es leíble (is_readable): " . (is_readable($targetFile) ? 'SÍ' : 'NO') . "\n";
        echo "Propietario del archivo (UID): " . fileowner($targetFile) . "\n";
        echo "UID del proceso PHP actual: " . (function_exists('posix_getuid') ? posix_getuid() : 'desconocido') . "\n";
        
        // Intentar corregir los permisos de forma agresiva
        if (@chmod($targetFile, 0644)) {
            echo "chmod(0644) manual: ÉXITO\n";
            clearstatcache();
            echo "Permisos después de corregir (octal): " . substr(sprintf('%o', fileperms($targetFile)), -4) . "\n";
            echo "Es leíble después de corregir: " . (is_readable($targetFile) ? 'SÍ' : 'NO') . "\n";
        } else {
            echo "chmod(0644) manual: FALLÓ (no tienes permisos suficientes para este archivo)\n";
        }
    } else {
        echo "Existe: NO (El archivo no se encuentra en esa ruta)\n";
    }
}

try {
    corregirPermisos($dir);
} catch (Exception $e) {
    echo "❌ Error general: " . $e->getMessage() . "\n";
}
