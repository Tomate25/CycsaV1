<?php
$dir = new RecursiveDirectoryIterator('C:/xampp/htdocs/Cycsa/modulos');
foreach (new RecursiveIteratorIterator($dir) as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        if (strpos($content, 'crear-os') !== false) {
            echo "Found in: " . $file->getPathname() . "\n";
        }
    }
}
