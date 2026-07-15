<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = new \Cycsa\Nucleo\Aplicacion();
$modelo = new \Cycsa\Modulos\Contabilidad\Modelos\ContabilidadModelo();
if ($modelo->reconstruirDiario()) {
    echo "✓ Diario contable sincronizado exitosamente con el IVA de las facturas!\n";
} else {
    echo "Error al reconstruir el diario.\n";
}
