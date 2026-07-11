<?php
$folders = ['C:/xampp/htdocs/Cycsa/modulos', 'C:/xampp/htdocs/Cycsa/rutas', 'C:/xampp/htdocs/Cycsa/nucleo'];
foreach ($folders as $folder) {
    if (!is_dir($folder)) continue;
    $dir = new RecursiveDirectoryIterator($folder);
    foreach (new RecursiveIteratorIterator($dir) as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $content = file_get_contents($file->getPathname());
            if (strpos($content, 'bitacora') !== false || strpos($content, 'Bitacora') !== false) {
                echo "Found in: " . $file->getPathname() . "\n";
            }
        }
    }
}
