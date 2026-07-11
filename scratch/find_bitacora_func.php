<?php
$base = 'C:/xampp/htdocs/Cycsa';
$dir = new RecursiveDirectoryIterator($base, RecursiveDirectoryIterator::SKIP_DOTS);
foreach (new RecursiveIteratorIterator($dir) as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $path = $file->getPathname();
        if (strpos($path, 'vendor') !== false) continue;
        $content = file_get_contents($path);
        if (strpos($content, 'function registrarBitacora') !== false) {
            echo "FOUND: " . $path . "\n";
            $lines = explode("\n", $content);
            foreach ($lines as $i => $line) {
                if (strpos($line, 'function registrarBitacora') !== false) {
                    for ($j = max(0, $i-1); $j < min($i + 25, count($lines)); $j++) {
                        echo ($j+1) . ": " . $lines[$j] . "\n";
                    }
                }
            }
        }
    }
}
