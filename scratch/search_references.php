<?php
$dir = 'C:/xampp/htdocs/Cycsa';
$results = [];

function search_in_dir($dir, &$results) {
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..' || $file === 'vendor' || $file === '.git' || $file === 'scratch') continue;
        $path = $dir . '/' . $file;
        if (is_dir($path)) {
            search_in_dir($path, $results);
        } else {
            if (pathinfo($path, PATHINFO_EXTENSION) === 'php') {
                $content = file_get_contents($path);
                if (strpos($content, 'cuenta_cxc') !== false || strpos($content, 'cuenta_cxp') !== false) {
                    $results[] = $path;
                }
            }
        }
    }
}

search_in_dir($dir, $results);
echo "FILES REFERENCING cuenta_cxc OR cuenta_cxp:\n";
foreach ($results as $r) {
    echo "- $r\n";
}
