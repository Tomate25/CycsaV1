<?php
/**
 * Parcial de Subnavegación: Módulo de Compras (Bootstrap 5)
 */
$uriActual = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
if (!isset($subnavActiva)) {
    if (str_contains($uriActual, '/compras/proveedores')) {
        $subnavActiva = 'proveedores';
    } elseif (str_contains($uriActual, '/compras/recepciones')) {
        $subnavActiva = 'recepciones';
    } else {
        $subnavActiva = 'ordenes';
    }
}

$items = [
    'proveedores' => [
        'label' => 'Maestro de Proveedores',
        'url' => '/Cycsa/publico/compras/proveedores',
        'icon' => 'fa-truck-field'
    ],
    'ordenes' => [
        'label' => 'Órdenes de Compra',
        'url' => '/Cycsa/publico/compras/ordenes',
        'icon' => 'fa-file-invoice-dollar'
    ],
    'recepciones' => [
        'label' => 'Recepciones de Compra',
        'url' => '/Cycsa/publico/compras/recepciones',
        'icon' => 'fa-boxes-packing'
    ]
];
?>
<ul class="nav nav-pills p-1 bg-light rounded-3 mb-4 gap-1 border">
    <?php foreach ($items as $clave => $item): 
        $isActive = ($subnavActiva === $clave);
    ?>
        <li class="nav-item">
            <a class="nav-link py-2 px-3 fw-medium <?= $isActive ? 'active shadow-sm' : 'text-secondary' ?>" 
               href="<?= $item['url'] ?>" 
               <?= $isActive ? 'aria-current="page"' : '' ?>>
                <i class="fa-solid <?= $item['icon'] ?> me-2"></i><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>
            </a>
        </li>
    <?php endforeach; ?>
</ul>
