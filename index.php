<?php
// Punto de entrada principal para hosting compartido (Bluehost/cPanel)
// Carga directamente el frontal desde publico/index.php sin redirecciones mod_rewrite

chdir(__DIR__ . '/publico');
require_once __DIR__ . '/publico/index.php';
