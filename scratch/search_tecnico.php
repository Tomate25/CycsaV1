<?php
$folders = ['C:/xampp/htdocs/Cycsa/modulos', 'C:/xampp/htdocs/Cycsa/nucleo', 'C:/xampp/htdocs/Cycsa/publico'];
foreach ($folders as $folder) {
    if (!is_dir($folder)) continue;
    $dir = new RecursiveDirectoryIterator($folder);
    foreach (new RecursiveIteratorIterator($dir) as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $content = file_get_contents($file->getPathname());
            if (strpos($content, 'tecnico_muestreo') !== false) {
                echo "Found 'tecnico_muestreo' in: " . $file->getPathname() . "\n";
            }
        }
    }
}
